<?php

namespace FL\ReportsBundle\Storage\DoctrineORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use FL\QBJSParser\Parsed\AbstractParsedRuleGroup;
use FL\QBJSParser\Parsed\Doctrine\ParsedRuleGroup;
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
        $dql = sprintf(
            '%s GROUP BY %s.id',
            $parsedRuleGroup->getQueryString(),
            SelectPartialParser::OBJECT_WORD
        );

        $query = $this->entityManager->createQuery($dql);
        $query->setParameters($parsedRuleGroup->getParameters());

        if (
            $resultsPerPage !== null &&
            $currentPage !== null
        ) {
            $query->setMaxResults($resultsPerPage)
                ->setFirstResult(($currentPage - 1) * $resultsPerPage);
        }


        $idColumnName = SelectPartialParser::OBJECT_WORD . '_id';
        foreach ($query->getResult(Query::HYDRATE_SCALAR) as $result) {
            $resultIds[] = $result[$idColumnName];
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('object')
            ->from($parsedRuleGroup->getClassName(), 'object')
            ->where('object.id IN (:ids)')
            ->setParameter('ids', $resultIds);

        // @todo insert event for better hydration
        // subscribers should only add Joins to this, not where/sort/group/etc.

        $unsortedObjects = $qb->getQuery()->getResult();
        $idsToObjects = [];
        foreach ($unsortedObjects as $object) {
            $idsToObjects[$object->getId()] = $object;
        }

        $sortedObjects =  array_map(function ($id) use ($idsToObjects) {
            return $idsToObjects[$id];
        }, $resultIds);

        return $sortedObjects;
    }

    /**
     * {@inheritdoc}
     */
    public function countResultsFromParsedRuleGroup(AbstractParsedRuleGroup $parsedRuleGroup): int
    {
        /* @var ParsedRuleGroup $parsedRuleGroup */
        $dql = $parsedRuleGroup->getQueryString();
        $query = $this->entityManager->createQuery($dql);
        $query->setParameters($parsedRuleGroup->getParameters());

        $paginator = new Paginator($query, $fetchJoinCollection = true);

        return $paginator->count();
    }
}
