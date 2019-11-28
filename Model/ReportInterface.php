<?php

namespace FL\ReportsBundle\Model;

interface ReportInterface
{
    /**
     * @return int|null
     */
    public function getId();

    /**
     * @return string
     */
    public function getReportName(): string;

    /**
     * @param string $reportName
     *
     * @return ReportInterface
     */
    public function setReportName(string $reportName): self;

    /**
     * @return string
     */
    public function getReportBuilderId(): string;

    /**
     * @param string $reportBuilderId
     *
     * @return ReportInterface
     */
    public function setReportBuilderId(string $reportBuilderId): self;

    /**
     * @return string
     */
    public function getReportBuilderHumanReadableName(): string;

    /**
     * @param string $reportBuilderHumanReadableName
     *
     * @return ReportInterface
     */
    public function setReportBuilderHumanReadableName(string $reportBuilderHumanReadableName): self;

    /**
     * @return string
     */
    public function getRulesJsonString(): string;

    /**
     * @param string $rulesJsonString
     *
     * @return ReportInterface
     */
    public function setRulesJsonString(string $rulesJsonString): self;

    /**
     * @return string
     */
    public function getColumnsAsJsonArray(): string;

    /**
     * Columns that this report will show, when rendered.
     *
     * @return string[]
     */
    public function getColumns(): array;

    /**
     * @param string $column
     *
     * @return ReportInterface
     */
    public function addColumn(string $column): self;

    /**
     * @param string $column
     *
     * @return ReportInterface
     */
    public function removeColumn(string $column): self;

    /**
     * @return ReportInterface
     */
    public function clearColumns(): self;

    /**
     * @return string
     */
    public function getSortColumnsAsJsonObject(): string;

    /**
     * Columns that this report will sort by, when rendered
     * Keys are columns, and values are 'ASC' or 'DESC'.
     *
     * @return string[]
     */
    public function getSortColumns(): array;

    /**
     * @param array $columns
     *
     * @return ReportInterface
     */
    public function setColumns(array $columns): self;

    /**
     * @param string $sortColumn
     * @param string $order
     *
     * @return ReportInterface
     */
    public function addSortColumn(string $sortColumn, string $order): self;

    /**
     * @param string $sortColumn
     *
     * @return ReportInterface
     */
    public function removeSortColumn(string $sortColumn): self;

    /**
     * @return ReportInterface
     */
    public function clearSortColumns(): self;

    /**
     * @param array $sortColumns
     *
     * @return ReportInterface
     */
    public function setSortColumns(array $sortColumns): self;
}
