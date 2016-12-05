<?php

namespace FL\ReportsBundle\DataObjects;

class PaginationQuery
{
    /**
     * @var int
     */
    protected $currentPage;

    /**
     * @var int
     */
    protected $maxResultsPerPage;

    /**
     * @param int $currentPage
     * @param int $maxResultsPerPage
     */
    public function __construct(int $currentPage, int $maxResultsPerPage)
    {
        $this->currentPage = $currentPage;
        $this->maxResultsPerPage = $maxResultsPerPage;
    }

    /**
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * @return int
     */
    public function getMaxResultsPerPage(): int
    {
        return $this->maxResultsPerPage;
    }
}