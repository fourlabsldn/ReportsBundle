<?php

namespace FL\ReportsBundle\Model;

class Report implements ReportInterface
{
    /**
     * @var int|null
     */
    protected $id;

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
    protected $rulesJsonString = '{"condition":"AND","rules":[],"valid":true}';

    /**
     * @var string[]
     */
    protected $columns = [];

    /**
     * @var string[]
     */
    protected $sortColumns = [];

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getReportName(): string
    {
        return $this->reportName;
    }

    /**
     * {@inheritdoc}
     */
    public function setReportName(string $reportName): ReportInterface
    {
        $this->reportName = $reportName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getReportBuilderId(): string
    {
        return $this->reportBuilderId;
    }

    /**
     * {@inheritdoc}
     */
    public function setReportBuilderId(string $reportBuilderId): ReportInterface
    {
        $this->reportBuilderId = $reportBuilderId;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getReportBuilderHumanReadableName(): string
    {
        return $this->reportBuilderHumanReadableName;
    }

    /**
     * {@inheritdoc}
     */
    public function setReportBuilderHumanReadableName(string $reportBuilderHumanReadableName): ReportInterface
    {
        $this->reportBuilderHumanReadableName = $reportBuilderHumanReadableName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRulesJsonString(): string
    {
        return $this->rulesJsonString;
    }

    /**
     * {@inheritdoc}
     */
    public function setRulesJsonString(string $rulesJsonString): ReportInterface
    {
        $this->rulesJsonString = $rulesJsonString;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnsAsJsonArray(): string
    {
        return json_encode(array_values($this->getColumns()));
    }

    /**
     * {@inheritdoc}
     */
    public function getColumns(): array
    {
        return $this->columns ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function addColumn(string $column): ReportInterface
    {
        $this->columns[$column] = $column;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeColumn(string $column): ReportInterface
    {
        if (array_key_exists($column, $this->columns)) {
            unset($this->columns[$column]);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function clearColumns(): ReportInterface
    {
        $this->columns = [];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortColumnsAsJsonObject(): string
    {
        $json = '{';
        foreach ($this->getSortColumns() as $sortColumn => $order) {
            $json .= sprintf(
                '"%s": "%s", ',
                $sortColumn,
                $order
            );
        }

        return rtrim($json, ', ').'}';
    }

    /**
     * {@inheritdoc}
     */
    public function getSortColumns(): array
    {
        return $this->sortColumns ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function setColumns(array $columns): ReportInterface
    {
        $this->clearColumns();

        foreach ($columns as $column) {
            $this->addColumn($column);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addSortColumn(string $sortColumn, string $order): ReportInterface
    {
        if ('ASC' !== $order && 'DESC' !== $order) {
            throw new \InvalidArgumentException();
        }
        $this->sortColumns[$sortColumn] = $order;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeSortColumn(string $sortColumn): ReportInterface
    {
        if (array_key_exists($sortColumn, $this->sortColumns)) {
            unset($this->sortColumns[$sortColumn]);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function clearSortColumns(): ReportInterface
    {
        $this->sortColumns = [];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setSortColumns(array $sortColumns): ReportInterface
    {
        $this->clearSortColumns();

        foreach ($sortColumns as $column => $order) {
            $this->addSortColumn($column, $order);
        }

        return $this;
    }
}
