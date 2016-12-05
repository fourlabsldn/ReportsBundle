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
     * @var string
     */
    protected $baseUrl;

    /**
     * @param ReportInterface $report
     * @param PaginationQuery $paginationQuery
     * @param Builder         $reportBuilder
     * @param string          $baseUrl
     */
    public function __construct(
        ReportInterface $report,
        PaginationQuery $paginationQuery,
        Builder $reportBuilder,
        string $baseUrl
    ) {
        $this->report = $report;
        $this->paginationQuery = $paginationQuery;
        $this->reportBuilder = $reportBuilder;
        $this->baseUrl = $baseUrl;
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

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
