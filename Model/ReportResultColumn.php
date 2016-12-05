<?php

namespace FL\ReportsBundle\Model;

class ReportResultColumn implements ReportResultColumnInterface
{
    /**
     * @var string
     */
    protected $column;

    /**
     * @var string
     */
    protected $columnValue;

    /**
     * @param string $column
     * @param string $columnValue
     */
    public function __construct(
        string $column,
        string $columnValue
    ) {
        $this->column = $column;
        $this->columnValue = $columnValue;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumn(): string
    {
        return $this->column;
    }

    /**
     * {@inheritdoc}
     */
    public function setColumn(string $column)
    {
        $this->column = $column;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnValue(): string
    {
        return $this->columnValue;
    }

    /**
     * {@inheritdoc}
     */
    public function setColumnValue(string $columnValue)
    {
        $this->columnValue = $columnValue;
    }
}