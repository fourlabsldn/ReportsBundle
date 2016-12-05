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
    public function resultsFromParsedRuleGroup(AbstractParsedRuleGroup $parsedRuleGroup, int $currentPage, int $resultsPerPage): array
    {
        /** @var ParsedRuleGroup $parsedRuleGroup */
        $dql = $parsedRuleGroup->getDqlString();
        $query = $this->entityManager->createQuery($dql);
        $query
            ->setParameters($parsedRuleGroup->getParameters())
            ->setFirstResult($currentPage - 1)
            ->setMaxResults($resultsPerPage)
        ;

        $paginator = new Paginator($query, $fetchJoinCollection = false);
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
    public function countResultsFromParsedRuleGroup(AbstractParsedRuleGroup $parsedRuleGroup, int $currentPage, int $resultsPerPage): int
    {
        /** @var ParsedRuleGroup $parsedRuleGroup */
        $dql = $parsedRuleGroup->getDqlString();
        $query = $this->entityManager->createQuery($dql);
        $query
            ->setParameters($parsedRuleGroup->getParameters())
            ->setFirstResult($currentPage - 1)
            ->setMaxResults($resultsPerPage)
        ;

        $paginator = new Paginator($query, $fetchJoinCollection = false);

        return $paginator->count();
    }
}
