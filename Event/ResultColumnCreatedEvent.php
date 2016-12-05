<?php

namespace FL\ReportsBundle\Event;

use FL\ReportsBundle\Model\ReportResultColumnInterface;
use Symfony\Component\EventDispatcher\Event;

class ResultColumnCreatedEvent extends Event
{
    const EVENT_NAME = 'fl_reports.result_column_created';

    /**
     * @var object
     */
    protected $resultObject;

    /**
     * @var ReportResultColumnInterface
     */
    protected $reportResultColumn;

    /**
     * @param object $resultObject
     * @param ReportResultColumnInterface $reportResultColumn
     */
    public function __construct($resultObject, ReportResultColumnInterface $reportResultColumn)
    {
        $this->resultObject = $resultObject;
        $this->reportResultColumn = $reportResultColumn;
    }

    /**
     * @return object
     */
    public function getResultObject(): object
    {
        return $this->resultObject;
    }

    /**
     * @return ReportResultColumnInterface
     */
    public function getReportResultColumn(): ReportResultColumnInterface
    {
        return $this->reportResultColumn;
    }
}
