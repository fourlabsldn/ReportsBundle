<?php

namespace FL\ReportsBundle\Storage\DoctrineORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query;
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
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function resultsFromParsedRuleGroup(AbstractParsedRuleGroup $parsedRuleGroup, int $currentPage = null, int $resultsPerPage = null): array
    {
        if (
            $currentPage !== null &&
            $resultsPerPage !== null &&
            ($currentPage === 0 || $resultsPerPage === 0)
        ) {
            return [];
        }

        // SELECT only the root entity id's column
        // Ids are the only thing we need, it's better for performance
        // Use DISTINCT to get one row per root entity
        $selectPartialDql = sprintf(
            'SELECT DISTINCT %s.id AS root_entity_id',
            SelectPartialParser::OBJECT_WORD
        );
        $modifiedDql = $parsedRuleGroup
            ->copyWithReplacedStringRegex('/SELECT.+FROM/', $selectPartialDql.' FROM', '')
            ->getQueryString();

        // Create Query
        $query = $this->entityManager->createQuery($modifiedDql);
        $query->setParameters($parsedRuleGroup->getParameters());

        if (
            $currentPage !== null &&
            $resultsPerPage !== null
        ) {
            $query->setMaxResults($resultsPerPage)
                ->setFirstResult(($currentPage - 1) * $resultsPerPage);
        }

        // Use query to get resultIds
        $resultIds = [];
        foreach ($query->getResult(Query::HYDRATE_SCALAR) as $result) {
            $resultIds[] = $result['root_entity_id'];
        }
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
        $this->modifyMetadata($parsedRuleGroup->getClassName());
        $unsortedObjects = $this->entityManager->createQueryBuilder()
            ->select('object')
            ->from($parsedRuleGroup->getClassName(), 'object')
            ->where('object.id IN (:ids)')
            ->setParameter('ids', $resultIds)
            ->getQuery()
            ->getResult();

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
    public function countResultsFromParsedRuleGroup(AbstractParsedRuleGroup $parsedRuleGroup): int
    {
        $selectPartialDql = sprintf(
            'SELECT %s',
            SelectPartialParser::OBJECT_WORD
        );
        $modifiedDql = $parsedRuleGroup
            ->copyWithReplacedStringRegex('/SELECT.+FROM/', $selectPartialDql.' FROM', '')
            ->getQueryString();

        $query = $this->entityManager
            ->createQuery($modifiedDql)
            ->setParameters($parsedRuleGroup->getParameters());

        $paginator = new Paginator($query, false);

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
