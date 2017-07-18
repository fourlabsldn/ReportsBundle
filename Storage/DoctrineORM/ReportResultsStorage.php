<?php

namespace FL\ReportsBundle\Storage\DoctrineORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use FL\QBJSParser\Parsed\AbstractParsedRuleGroup;
use FL\QBJSParser\Parser\Doctrine\SelectPartialParser;
use FL\ReportsBundle\Storage\ReportResultsStorageInterface;

class ReportResultsStorage implements ReportResultsStorageInterface
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var AbstractParsedRuleGroup[]
     */
    protected $includeRuleGroups;

    /**
     * @var AbstractParsedRuleGroup[]
     */
    protected $excludeRuleGroups;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param AbstractParsedRuleGroup $ruleGroup
     * @param bool $exclude
     * @return $this
     */
    public function addRuleGroup(AbstractParsedRuleGroup $ruleGroup, bool $exclude = false)
    {
        if ($exclude) {
            $this->excludeRuleGroups[] = $ruleGroup;
        } else {
            $this->includeRuleGroups[] = $ruleGroup;
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function clearRuleGroups()
    {
        $this->excludeRuleGroups = [];
        $this->includeRuleGroups = [];

        return $this;
    }

    /**
     * @param AbstractParsedRuleGroup $ruleGroup
     * @param int $n
     * @return string DQL
     */
    private function getSubDql(AbstractParsedRuleGroup $ruleGroup, int $n = 0): string
    {
        // SELECT only the root entity id's column
        // Ids are the only thing we need, it's better for performance
        // Use DISTINCT to get one row per root entity
        $selectPartialDql = sprintf(
            'SELECT DISTINCT %s.id',
            SelectPartialParser::OBJECT_WORD
        );

        $dql = $ruleGroup
            ->copyWithReplacedStringRegex('/SELECT.+FROM/', $selectPartialDql.' FROM', '')
            ->getQueryString()
        ;
        
        return str_replace(SelectPartialParser::OBJECT_WORD, SelectPartialParser::OBJECT_WORD.$n, $dql);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param AbstractParsedRuleGroup $ruleGroup
     * @return $this
     */
    private function addParameters(QueryBuilder $queryBuilder, AbstractParsedRuleGroup $ruleGroup)
    {
        // append numerically indexed parameters
        $queryBuilder->setParameters(
            array_merge(
                array_map(function (Query\Parameter $parameter) {
                    return $parameter->getValue();
                }, $queryBuilder->getParameters()->toArray()),
                $ruleGroup->getParameters()
            )
        );

        return $this;
    }

    private function reindexParameters(string $dql)
    {
        $n = -1;

        return preg_replace_callback('/\?\d+/', function (array $matches) use (&$n) {
            $n++;
            return '?'.$n;
        }, $dql);
    }

    private function getQuery(): Query
    {
        if (count($this->includeRuleGroups) == 0) {
            throw new \RuntimeException('Query requires at least one include rule group');
        }

        $queryBuilder = $this->entityManager
            ->createQueryBuilder()
            ->select('partial o.{id}')
            ->from($this->includeRuleGroups[0]->getClassName(), 'o')
        ;

        $n = 0;

        foreach ($this->includeRuleGroups as $ruleGroup) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in('o.id', $this->getSubDql($ruleGroup, $n))
            );

            $this->addParameters($queryBuilder, $ruleGroup);

            $n++;
        }

        foreach ($this->excludeRuleGroups as $ruleGroup) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->notIn('o.id', $this->getSubDql($ruleGroup, $n))
            );

            $this->addParameters($queryBuilder, $ruleGroup);

            $n++;
        }

        $query = $queryBuilder->getQuery();
        $query->setDQL($this->reindexParameters($queryBuilder->getDQL()));

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function getIds(int $currentPage = null, int $resultsPerPage = null): array
    {
        if ($currentPage === 0 || $resultsPerPage === 0) {
            return [];
        }

        $query = $this->getQuery();

        if ($currentPage !== null && $resultsPerPage !== null) {
            $query
                ->setMaxResults($resultsPerPage)
                ->setFirstResult(($currentPage - 1) * $resultsPerPage)
            ;
        }

        return array_map(function (array $row) {
            return $row['o_id'];
        }, $query->getResult(Query::HYDRATE_SCALAR));
    }

    /**
     * {@inheritdoc}
     */
    public function getResults(int $currentPage = null, int $resultsPerPage = null): array
    {
        $resultIds = $this->getIds($currentPage, $resultsPerPage);

        if (count($resultIds) === 0) {
            return []; // WHERE IN [] would have returned all results
        }

        /*
         * Let's not hydrate the complete object graph and its associations.
         *
         * Entity metadata is modified such that any __ToOne associations are
         * eagerly loaded (no repeated queries to access values in each row).
         *
         * Entity metadata is modified such that any __ToMany associations are
         * lazily loaded (prevents large memory consumption).
         *
         * @see http://ocramius.github.io/blog/doctrine-orm-optimization-hydration/
         *
         * Cannot use $queryBuilder->getResult(Query::HYDRATE_SIMPLEOBJECT) because
         * it will not hydrate associations.
         *
         * If performance remains a problem, consider $queryBuilder->getResult(Query::HYDRATE_SCALAR).
         * Note: More changes would be needed because $queryBuilder->getResult(Query::HYDRATE_SCALAR)
         * does not return an array of hydrated objects.
         */
        $this->entityManager->clear();
        $this->modifyMetadata($this->includeRuleGroups[0]->getClassName());
        $unsortedObjects = $this->entityManager->createQueryBuilder()
            ->select('object')
            ->from($this->includeRuleGroups[0]->getClassName(), 'object')
            ->where('object.id IN (:ids)')
            ->setParameter('ids', $resultIds)
            ->getQuery()
            ->getResult()
        ;

        $idsToObjects = [];
        foreach ($unsortedObjects as $object) {
            $idsToObjects[$object->getId()] = $object;
        }

        $sortedObjects = array_map(function ($id) use ($idsToObjects) {
            return $idsToObjects[$id];
        }, $resultIds);

        return $sortedObjects;
    }

    /**
     * {@inheritdoc}
     */
    public function countResults(): int
    {
        $paginator = new Paginator($this->getQuery(), false);

        return $paginator->count();
    }

    /**
     * Forces X_To_One relationship to be loaded eagerly.
     * Forces X_To_Many relationship to be loaded lazily.
     *
     * In the future, consider only loading eagerly for entities
     * defined in parameter "fl_qbjs_parser:doctrine_classes_and_mappings".
     * Documentation would be needed such that ResultColumnCreatedEvent
     * isn't misused. Not currently being done because it doesn't seem to
     * greatly affect performance.
     *
     * @param string $entityClass
     */
    private function modifyMetadata(string $entityClass)
    {
        $classMetadata = $this->entityManager->getClassMetadata($entityClass);
        $associationMappings = $classMetadata->getAssociationMappings();
        $associationCache = []; // used to prevent recursive execution

        foreach ($associationMappings as $mappingKey => $mapping) {
            $associationIdentifier = sprintf(
                '%s::%s____%s',
                $mapping['sourceEntity'],
                $mapping['fieldName'],
                $mapping['targetEntity']
            );
            if (in_array($associationIdentifier, $associationCache)) {
                continue;
            }
            $associationCache[] = $associationIdentifier;

            if (in_array($mapping['type'], [ClassMetadataInfo::MANY_TO_ONE, ClassMetadataInfo::ONE_TO_ONE, ClassMetadataInfo::TO_ONE])) {
                $associationMappings[$mappingKey]['fetch'] = ClassMetadataInfo::FETCH_EAGER;
            }
            if (in_array($mapping['type'], [ClassMetadataInfo::ONE_TO_MANY, ClassMetadataInfo::MANY_TO_MANY])) {
                $associationMappings[$mappingKey]['fetch'] = ClassMetadataInfo::FETCH_LAZY;
            }
        }

        // the property is public, but use reflection, in case it becomes private in the future.
        (new \ReflectionProperty(ClassMetadataInfo::class, 'associationMappings'))->setValue($classMetadata, $associationMappings);
    }
}
