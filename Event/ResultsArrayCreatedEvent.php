<?php

namespace FL\ReportsBundle\Event;

use FL\ReportsBundle\Model\ReportInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Allows to make changes to the results array that is used to generate CSV files.
 */
class ResultsArrayCreatedEvent extends Event
{
    const EVENT_NAME = 'fl_reports.results_array_created';

    /**
     * @var ReportInterface
     */
    private $report;
    
    /**
     * @var array
     */
    private $results;

    /**
     * @param array $results
     */
    public function __construct(ReportInterface $report, array $results)
    {
        $this->report = $report;
        $this->results = $results;
    }

    /**
     * @return ReportInterface
     */
    public function getReport(): ReportInterface
    {
        return $this->report;
    }

    /**
     * @return array
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * @param array $results
     * @return $this
     */
    public function setResults(array $results)
    {
        $this->results = $results;

        return $this;
    }
}
