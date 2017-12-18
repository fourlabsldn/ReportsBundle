<?php

namespace FL\ReportsBundle\Event;

use FL\ReportsBundle\Model\ReportResultColumnInterface;
use Symfony\Component\EventDispatcher\Event;

class ResultColumnCreatedEvent extends Event
{
    const EVENT_NAME = 'fl_reports.result_column_created';

    const RESULTS_TYPE_HTML = 0;

    const RESULTS_TYPE_CSV = 1;

    const ALL_RESULTS_TYPES = [
        self::RESULTS_TYPE_HTML,
        self::RESULTS_TYPE_CSV,
    ];

    /**
     * @var object
     */
    protected $resultObject;

    /**
     * @var ReportResultColumnInterface
     */
    protected $reportResultColumn;

    /**
     * @var int
     */
    protected $resultsType;

    /**
     * @param object                      $resultObject
     * @param ReportResultColumnInterface $reportResultColumn
     * @param int                         $resultsType
     */
    public function __construct(
        $resultObject,
        ReportResultColumnInterface $reportResultColumn,
        int $resultsType
    ) {
        $this->resultObject = $resultObject;
        $this->reportResultColumn = $reportResultColumn;
        $this->resultsType = $resultsType;
        if (!in_array($resultsType, self::ALL_RESULTS_TYPES)) {
            throw new \InvalidArgumentException();
        }
    }

    /**
     * @return object
     */
    public function getResultObject()
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

    /**
     * @return int
     */
    public function getResultsType(): int
    {
        return $this->resultsType;
    }
}
