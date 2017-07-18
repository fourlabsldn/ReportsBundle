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
    public function setReportName(string $reportName): ReportInterface;

    /**
     * @return string
     */
    public function getReportBuilderId(): string;

    /**
     * @param string $reportBuilderId
     *
     * @return ReportInterface
     */
    public function setReportBuilderId(string $reportBuilderId): ReportInterface;

    /**
     * @return string
     */
    public function getReportBuilderHumanReadableName(): string;

    /**
     * @param string $reportBuilderHumanReadableName
     *
     * @return ReportInterface
     */
    public function setReportBuilderHumanReadableName(string $reportBuilderHumanReadableName): ReportInterface;

    /**
     * @return ReportRuleSetInterface[]
     */
    public function getRuleSets(): array;

    /**
     * @param ReportRuleSetInterface[] $ruleSets
     * @return $this
     */
    public function setRuleSets(array $ruleSets);

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
    public function addColumn(string $column): ReportInterface;

    /**
     * @param string $column
     *
     * @return ReportInterface
     */
    public function removeColumn(string $column): ReportInterface;

    /**
     * @return ReportInterface
     */
    public function clearColumns(): ReportInterface;

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
    public function setColumns(array $columns): ReportInterface;

    /**
     * @param string $sortColumn
     * @param string $order
     *
     * @return ReportInterface
     */
    public function addSortColumn(string $sortColumn, string $order): ReportInterface;

    /**
     * @param string $sortColumn
     *
     * @return ReportInterface
     */
    public function removeSortColumn(string $sortColumn): ReportInterface;

    /**
     * @return ReportInterface
     */
    public function clearSortColumns(): ReportInterface;

    /**
     * @param array $sortColumns
     *
     * @return ReportInterface
     */
    public function setSortColumns(array $sortColumns): ReportInterface;
}
