<?php

namespace FL\ReportsBundle\Storage;

use FL\ReportsBundle\Model\ReportInterface;

interface ReportStorageInterface
{
    /**
     * Find reports by a set of criteria.
     *
     * @param array      $criteria
     * @param array|null $orderBy
     * @param int|null   $limit
     * @param int|null   $offset
     *
     * @return ReportInterface[]
     */
    public function findBy(array $criteria, array $orderBy = null, int $limit = null, int $offset = null): array;

    /**
     * Find a single report by a set of criteria.
     *
     * @param array $criteria
     *
     * @return ReportInterface|null
     */
    public function findOneBy(array $criteria);

    /**
     * @param ReportInterface $report
     */
    public function persist(ReportInterface $report);

    /**
     * @param ReportInterface $report
     */
    public function remove(ReportInterface $report);
}
