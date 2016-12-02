<?php

namespace FL\ReportsBundle\Model;

class Report implements ReportInterface
{
    /**
     * @var string
     */
    protected $reportName = '';

    /**
     * @var string
     */
    protected $reportBuilderId = '';

    /**
     * @var string
     */
    protected $reportBuilderHumanReadableName = '';

    /**
     * @var string
     */
    protected $rulesJsonString = '';

    /**
     * @var string[]
     */
    protected $columns = [];

    /**
     * @var string[]
     */
    protected $sortColumns = [];

    /**
     * @return string
     */
    public function getReportName(): string
    {
        return $this->reportName;
    }

    /**
     * @param string $reportName
     * @return ReportInterface
     */
    public function setReportName(string $reportName): ReportInterface
    {
        $this->reportName = $reportName;

        return $this;
    }

    /**
     * @return string
     */
    public function getReportBuilderId(): string
    {
        return $this->reportBuilderId;
    }

    /**
     * @param string $reportBuilderId
     * @return ReportInterface
     */
    public function setReportBuilderId(string $reportBuilderId): ReportInterface
    {
        $this->reportBuilderId = $reportBuilderId;

        return $this;
    }

    /**
     * @return string
     */
    public function getReportBuilderHumanReadableName(): string
    {
        return $this->reportBuilderHumanReadableName;
    }

    /**
     * @param string $reportBuilderHumanReadableName
     * @return ReportInterface
     */
    public function setReportBuilderHumanReadableName(string $reportBuilderHumanReadableName): ReportInterface
    {
        $this->reportBuilderHumanReadableName = $reportBuilderHumanReadableName;

        return $this;
    }

    /**
     * @return string
     */
    public function getRulesJsonString(): string
    {
        return $this->rulesJsonString;
    }

    /**
     * @param string $rulesJsonString
     * @return ReportInterface
     */
    public function setRulesJsonString(string $rulesJsonString): ReportInterface
    {
        $this->rulesJsonString = $rulesJsonString;

        return $this;
    }

    /**
     * @return string
     */
    public function getColumnsAsJsonArray(): string
    {
        $json = '[';
        foreach ($this->columns as $column) {
            $json .= sprintf('"%s", ', $column);
        }
        return rtrim($json,', ') . ']';
    }

    /**
     * @return string[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @param string $column
     * @return ReportInterface
     */
    public function addColumn(string $column): ReportInterface
    {
        $this->columns[$column] = $column;

        return $this;
    }

    /**
     * @param string $column
     * @return ReportInterface
     */
    public function removeColumn(string $column): ReportInterface
    {
        if (array_key_exists($column, $this->columns)) {
            unset($this->columns[$column]);
        }

        return $this;
    }

    /**
     * @return ReportInterface
     */
    public function clearColumns() : ReportInterface
    {
        $this->columns =[];

        return $this;
    }

    /**
     * @return string
     */
    public function getSortColumnsAsJsonObject(): string
    {
        $json = '{';
        foreach ($this->sortColumns as $sortColumn => $order) {
            $json .= sprintf(
                '"%s": "%s", ',
                $sortColumn,
                $order
            );
        }
        return rtrim($json,', ') . '}';
    }

    /**
     * @return string[]
     */
    public function getSortColumns(): array
    {
        return $this->sortColumns;
    }

    /**
     * @param string $sortColumn
     * @param string $order
     *
     * @return ReportInterface
     */
    public function addSortColumn(string $sortColumn, string $order): ReportInterface
    {
        if ($order !== 'ASC' && $order !== 'DESC') {
            throw new \InvalidArgumentException();
        }
        $this->sortColumns[$sortColumn] = $order;

        return $this;
    }

    /**
     * @param string $sortColumn
     * @return ReportInterface
     */
    public function removeSortColumn(string $sortColumn): ReportInterface
    {
        if (array_key_exists($sortColumn, $this->sortColumns)) {
            unset($this->sortColumns[$sortColumn]);
        }

        return $this;
    }

    /**
     * @return ReportInterface
     */
    public function clearSortColumns(): ReportInterface
    {
        $this->sortColumns =[];

        return $this;
    }
}