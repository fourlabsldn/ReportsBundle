<?php

namespace FL\ReportsBundle\DataObjects;

use FL\QBJSParserBundle\Model\Builder\Builder;
use FL\ReportsBundle\Model\ReportInterface;

class BuildReportQuery
{
    /**
     * @var ReportInterface
     */
    protected $report;

    /**
     * @var PaginationQuery
     */
    protected $paginationQuery;

    /**
     * @var Builder
     */
    protected $reportBuilder;

    /**
     * @param ReportInterface $report
     * @param PaginationQuery $paginationQuery
     * @param Builder         $reportBuilder
     */
    public function __construct(
        ReportInterface $report,
        PaginationQuery $paginationQuery,
        Builder $reportBuilder
    ) {
        $this->report = $report;
        $this->paginationQuery = $paginationQuery;
        $this->reportBuilder = $reportBuilder;
    }

    /**
     * @return ReportInterface
     */
    public function getReport(): ReportInterface
    {
        return $this->report;
    }

    /**
     * @return PaginationQuery
     */
    public function getPaginationQuery(): PaginationQuery
    {
        return $this->paginationQuery;
    }

    /**
     * @return Builder
     */
    public function getReportBuilder(): Builder
    {
        return $this->reportBuilder;
    }
}
