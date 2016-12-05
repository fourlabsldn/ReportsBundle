<?php

namespace FL\ReportsBundle\Model;

interface ReportResultColumnInterface
{
    /**
     * The outputted value to the end-user.
     *
     * @return string
     */
    public function getColumnValue(): string;

    /**
     * @param string $columnValue
     *
     * @return ReportResultColumnInterface
     */
    public function setColumnValue(string $columnValue);

    /**
     * The property accessor string. E.g. 'price', for a
     * Report on AppBundle\Entity\Product with property price.
     *
     * @return string
     */
    public function getColumn(): string;

    /**
     * @param string $column
     *
     * @return ReportResultColumnInterface
     */
    public function setColumn(string $column);
}
