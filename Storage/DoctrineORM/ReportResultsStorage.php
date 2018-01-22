<?php

namespace FL\ReportsBundle\Storage\DoctrineORM;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
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
     * @param bool                    $exclude
     *
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
     * @param int                     $n
     *
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
     * we use wrapper queries to get mysql to only execute sub-queries once and store results in temp tables.
     *
     * @see https://www.xaprb.com/blog/2006/04/30/how-to-optimize-subqueries-and-joins-in-mysql/#how-to-force-the-inner-query-to-execute-first
     *
     * @return AbstractQuery
     */
    private function getQuery(?int $limit = null, ?int $offset = null): AbstractQuery
    {
        if (0 === count($this->includeRuleGroups)) {
            throw new \RuntimeException('Query requires at least one include rule group');
        }

        $queryBuilder = $this->entityManager
            ->createQueryBuilder()
            ->select('partial o.{id}')
            ->from($this->includeRuleGroups[0]->getClassName(), 'o')
        ;

        $query = $queryBuilder->getQuery();
        $mainSql = $query->getSQL();
        $mainEntityAlias = substr($mainSql, 7, 3);

        $n = 1;
        $where = [];
        $parameters = [];
        foreach ($this->includeRuleGroups as $ruleGroup) {
            $sql = $this->entityManager
                ->createQuery($this->getSubDql($ruleGroup, $n))
                ->getSQL()
            ;
            $where[] = $mainEntityAlias.'.id IN ( select * from ('.$sql.') sub'.$n.' )';
            $parameters = array_merge($parameters, $ruleGroup->getParameters());
            ++$n;
        }

        foreach ($this->excludeRuleGroups as $ruleGroup) {
            $sql = $this->entityManager
                ->createQuery($this->getSubDql($ruleGroup, $n))
                ->getSQL()
            ;
            $where[] = $mainEntityAlias.'.id NOT IN ( select * from ('.$sql.') sub'.$n.' )';
            $parameters = array_merge($parameters, $ruleGroup->getParameters());
            ++$n;
        }

        $sql = $mainSql.' WHERE '.implode(' AND ', $where);
        if ($limit || $offset) {
            $sql .= $offset ? sprintf(' LIMIT %u, %u', $offset, $limit) : sprintf(' LIMIT %u', $limit);
        }
        $rsm = (new ResultSetMapping())
            ->addScalarResult('id_0', 'id', 'integer')
        ;
        $query = $this->entityManager
            ->createNativeQuery($sql, $rsm)
            ->setParameters($parameters)
        ;

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function getIds(int $currentPage = null, int $resultsPerPage = null): array
    {
        if (0 === $currentPage || 0 === $resultsPerPage) {
            return [];
        }

        $query = null !== $currentPage && null !== $resultsPerPage
            ? $this->getQuery($resultsPerPage, ($currentPage - 1) * $resultsPerPage)
            : $this->getQuery()
        ;

        return array_map(function (array $row) {
            return $row['id'];
        }, $query->getScalarResult());
    }

    /**
     * {@inheritdoc}
     */
    public function getResults(int $currentPage = null, int $resultsPerPage = null): array
    {
        $resultIds = $this->getIds($currentPage, $resultsPerPage);

        if (0 === count($resultIds)) {
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
        return count($this->getQuery()->getResult(Query::HYDRATE_SCALAR));
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
