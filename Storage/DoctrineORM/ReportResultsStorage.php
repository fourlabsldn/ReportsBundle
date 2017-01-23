<?php

namespace FL\ReportsBundle\Storage\DoctrineORM;

use Doctrine\ORM\EntityManagerInterface;
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
            $resultsPerPage !== null &&
            $resultsPerPage !== null &&
            ($currentPage === 0 || $resultsPerPage === 0)
        ) {
            return [];
        }

        // Grouping by Id lets us paginate without the need for a paginator
        // And since Ids are the only thing we need, it's better for performance
        $groupByPartialDql = sprintf(
            'GROUP BY %s.id',
            SelectPartialParser::OBJECT_WORD
        );

        // GROUP BY must be placed before ORDER BY
        $dqlWithGroupBy = $parsedRuleGroup
            ->copyWithReplacedString('ORDER BY', $groupByPartialDql.' ORDER BY', $groupByPartialDql)
            ->getQueryString();

        // Create Query
        $query = $this->entityManager->createQuery($dqlWithGroupBy);
        $query->setParameters($parsedRuleGroup->getParameters());

        if (
            $resultsPerPage !== null &&
            $currentPage !== null
        ) {
            $query->setMaxResults($resultsPerPage)
                ->setFirstResult(($currentPage - 1) * $resultsPerPage);
        }

        // Use query to get resultIds
        $idColumnName = sprintf('%s_id', SelectPartialParser::OBJECT_WORD);
        $resultIds = [];
        foreach ($query->getResult(Query::HYDRATE_SCALAR) as $result) {
            $resultIds[] = $result[$idColumnName];
        }
        if (count($resultIds) === 0) {
            return [];
        }

        // We are using resultIds because this means we don't have
        // to hydrate the complete object graph and its associations.
        // If those associations are OneToMany or ManyToMany,
        // there would have been a serious performance degradation.
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('object')
            ->from($parsedRuleGroup->getClassName(), 'object')
            ->where('object.id IN (:ids)')
            ->setParameter('ids', $resultIds);

        // @todo insert event for better hydration
        // subscribers should only add Joins to this, not where/sort/group/etc.

        // Query::HYDRATE_SIMPLEOBJECT does not hydrate OneToOne/ManyToOne associations.
        // If performance of this method becomes a problem, consider
        // adapting to ->getResult(Query::HYDRATE_SCALAR).
        $unsortedObjects = $qb->getQuery()->getResult();

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
        $dql = $parsedRuleGroup->getQueryString();
        $query = $this->entityManager->createQuery($dql);
        $query->setParameters($parsedRuleGroup->getParameters());

        // using Paginator lets us easily know the total rows in the database
        // without manually modifying the original query
        $paginator = new Paginator($query, $fetchJoinCollection = true);

        return $paginator->count();
    }
}
