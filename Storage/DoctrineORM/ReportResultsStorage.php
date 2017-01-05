<?php

namespace FL\ReportsBundle\Storage\DoctrineORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use FL\QBJSParser\Parsed\AbstractParsedRuleGroup;
use FL\QBJSParser\Parsed\Doctrine\ParsedRuleGroup;
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

        /* @var ParsedRuleGroup $parsedRuleGroup */
        $dql = $parsedRuleGroup->getDqlString();
        $query = $this->entityManager->createQuery($dql);
        $query->setParameters($parsedRuleGroup->getParameters());

        if (
            $resultsPerPage !== null &&
            $currentPage !== null
        ) {
            $query->setMaxResults($resultsPerPage)
                ->setFirstResult(($currentPage - 1) * $resultsPerPage);
        }

        $paginator = new Paginator($query, $fetchJoinCollection = true);
        $results = [];
        // return an array, not a Paginator
        foreach ($paginator as $result) {
            $results[] = $result;
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function countResultsFromParsedRuleGroup(AbstractParsedRuleGroup $parsedRuleGroup): int
    {
        /* @var ParsedRuleGroup $parsedRuleGroup */
        $dql = $parsedRuleGroup->getDqlString();
        $query = $this->entityManager->createQuery($dql);
        $query->setParameters($parsedRuleGroup->getParameters());

        $paginator = new Paginator($query, $fetchJoinCollection = true);

        return $paginator->count();
    }
}
