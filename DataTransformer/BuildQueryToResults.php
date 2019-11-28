<?php

namespace FL\ReportsBundle\DataTransformer;

use FL\QBJSParser\Parsed\AbstractParsedRuleGroup;
use FL\QBJSParserBundle\Service\JsonQueryParserInterface;
use FL\ReportsBundle\Event\ResultColumnCreatedEvent;
use FL\ReportsBundle\Event\ResultsArrayCreatedEvent;
use FL\ReportsBundle\Model\ReportInterface;
use FL\ReportsBundle\Model\ReportResultColumn;
use FL\ReportsBundle\Storage\ReportResultsStorageInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use FL\ReportsBundle\DataObjects\BuildReportQuery;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Converts @see BuildReportQuery into results that can be used in controllers.
 */
class BuildQueryToResults
{
    /**
     * @var JsonQueryParserInterface
     */
    protected $jsonQueryParser;

    /**
     * @var ReportResultsStorageInterface
     */
    protected $reportResultsStorage;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param JsonQueryParserInterface      $jsonQueryParser
     * @param ReportResultsStorageInterface $reportResultsStorage
     * @param EventDispatcherInterface      $dispatcher
     * @param TranslatorInterface           $translator
     */
    public function __construct(
        JsonQueryParserInterface $jsonQueryParser,
        ReportResultsStorageInterface $reportResultsStorage,
        EventDispatcherInterface $dispatcher,
        TranslatorInterface $translator
    ) {
        $this->jsonQueryParser = $jsonQueryParser;
        $this->reportResultsStorage = $reportResultsStorage;
        $this->dispatcher = $dispatcher;
        $this->translator = $translator;
    }

    /**
     * Return value is suitable to be put into an HTML template, and used as an HTTP response.
     *
     * @param BuildReportQuery $buildReportQuery
     * @param bool             $allResults       (if true, ignores the PaginationQuery inside BuildReportQuery)
     *
     * @return array
     */
    public function transformToResultsArray(BuildReportQuery $buildReportQuery, bool $allResults = false): array
    {
        $report = $buildReportQuery->getReport();
        $columnsHumanReadable = $this->resolveColumnsHumanReadable($report, $buildReportQuery);
        $currentPage = $buildReportQuery->getPaginationQuery()->getCurrentPage();
        $resultsPerPage = $buildReportQuery->getPaginationQuery()->getMaxResultsPerPage();

        $resultsArray = [
            'data' => [
                'header' => $columnsHumanReadable,
                'results' => [],
            ],
            'state' => [
                'currentPage' => $currentPage,
                'maxResultsPerPage' => $resultsPerPage,
                'resultsInThisPage' => 0,
                'totalResults' => 0,
                'totalPages' => 0,
            ],
        ];

        $parsedRuleGroup = $this->jsonQueryParser->parseJsonString(
            $buildReportQuery->getReport()->getRulesJsonString(),
            $buildReportQuery->getReportBuilder()->getClassName(),
            $buildReportQuery->getReport()->getSortColumns()
        );

        if ($allResults) {
            $rawResults = $this->reportResultsStorage->resultsFromParsedRuleGroup($parsedRuleGroup, null, null);
        } else {
            $rawResults = $this->reportResultsStorage->resultsFromParsedRuleGroup($parsedRuleGroup, $currentPage, $resultsPerPage);
        }

        $objectResultsToArray = $this->transformObjectResultsToArray($rawResults, $report, ResultColumnCreatedEvent::RESULTS_TYPE_HTML);
        $totalResults = $this->reportResultsStorage->countResultsFromParsedRuleGroup($parsedRuleGroup);
        $totalPages = ceil($totalResults / $resultsPerPage);

        $resultsArray['data']['results'] = $objectResultsToArray;
        $resultsArray['state']['resultsInThisPage'] = count($objectResultsToArray);
        $resultsArray['state']['totalResults'] = $totalResults;
        $resultsArray['state']['totalPages'] = $totalPages;
        $resultsArray['state']['previousPage'] = ($currentPage > 1) ? ($currentPage - 1) : null;
        $resultsArray['state']['nextPage'] = ($currentPage < $totalPages) ? ($currentPage + 1) : null;

        return $resultsArray;
    }

    /**
     * Return value is suitable to be serialized into a CSV file, and used as an HTTP response.
     * An array in this format:
     * [
     *  ['Column X Human Readable Name' => 'Green', 'Column Y Human Readable Name' => 'Shirt'],
     *  ['Column X Human Readable Name' => 'Blue', 'Column Y Human Readable Name' => 'Shirt'],
     * ].
     *
     * @param BuildReportQuery $buildReportQuery
     * @param bool             $allResults       (if true, ignores the PaginationQuery inside BuildReportQuery)
     *
     * @return array
     */
    public function transformToTableArray(BuildReportQuery $buildReportQuery, bool $allResults = false): array
    {
        $report = $buildReportQuery->getReport();
        $columnToHumanReadable = $this->resolveColumnsHumanReadable($report, $buildReportQuery);
        $currentPage = $buildReportQuery->getPaginationQuery()->getCurrentPage();
        $resultsPerPage = $buildReportQuery->getPaginationQuery()->getMaxResultsPerPage();

        $parsedRuleGroup = $this->transformBuildQueryToParsedRuleGroup($buildReportQuery);
        if ($allResults) {
            $rawResults = $this->reportResultsStorage->resultsFromParsedRuleGroup($parsedRuleGroup, null, null);
        } else {
            $rawResults = $this->reportResultsStorage->resultsFromParsedRuleGroup($parsedRuleGroup, $currentPage, $resultsPerPage);
        }

        $newRows = [];
        foreach ($this->transformObjectResultsToArray($rawResults, $report, ResultColumnCreatedEvent::RESULTS_TYPE_CSV) as $rowKey => $row) {
            $newRows[$rowKey] = [];
            foreach ($row as $columnMachineName => $columnValue) {
                $newRows[$rowKey][$columnToHumanReadable[$columnMachineName]] = $columnValue;
            }
        }

        $event = new ResultsArrayCreatedEvent($report, $newRows);
        $this->dispatcher->dispatch(ResultsArrayCreatedEvent::EVENT_NAME, $event);

        return $event->getResults();
    }

    /**
     * Explores the object graph and creates an array,
     * where each object is a row.
     *
     * @param object[]        $rawResults
     * @param ReportInterface $report
     * @param int             $resultsType
     *
     * @return array
     */
    protected function transformObjectResultsToArray(array $rawResults, ReportInterface $report, int $resultsType): array
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        $objectResultsToArray = [];

        foreach ($rawResults as $key => $result) { // go through a single page
            $objectResultsToArray[$key] = []; // rows still exist, even if there are no columns
            foreach ($report->getColumns() as $column) {
                try {
                    $columnValue = $this->transformValueToString($accessor->getValue($result, $column));
                } catch (\Exception $exception) { // when accessing several levels deep, one of the properties might be null, or something else might go wrong
                    $columnValue = '';
                }
                $reportResultColumn = new ReportResultColumn($column, $columnValue);
                $this->dispatcher->dispatch(ResultColumnCreatedEvent::EVENT_NAME, new ResultColumnCreatedEvent(
                    $result,
                    $reportResultColumn,
                    $resultsType
                ));
                $objectResultsToArray[$key][$column] = $reportResultColumn->getColumnValue();
            }
        }

        return $objectResultsToArray;
    }

    /**
     * @param BuildReportQuery $buildReportQuery
     *
     * @return AbstractParsedRuleGroup
     */
    protected function transformBuildQueryToParsedRuleGroup(BuildReportQuery $buildReportQuery)
    {
        return $this->jsonQueryParser->parseJsonString(
            $buildReportQuery->getReport()->getRulesJsonString(),
            $buildReportQuery->getReportBuilder()->getClassName(),
            $buildReportQuery->getReport()->getSortColumns()
        );
    }

    /**
     * Resolves human readable column names from the current configuration,
     * according to a report's machine name columns.
     *
     * @param ReportInterface  $report
     * @param BuildReportQuery $buildReportQuery
     *
     * @return array (column machine names as keys)
     */
    protected function resolveColumnsHumanReadable(ReportInterface $report, BuildReportQuery $buildReportQuery): array
    {
        $columnsHumanReadable = [];
        foreach ($report->getColumns() as $column) {
            if ($columnHumanReadable = $buildReportQuery->getReportBuilder()->getHumanReadableWithMachineName($column)) {
                $columnsHumanReadable[$column] = $columnHumanReadable;
            }
        }

        return $columnsHumanReadable;
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    protected function transformValueToString($value): string
    {
        if ($value instanceof \DateTime || $value instanceof  \DateTimeImmutable) {
            if ('00:00' === $value->format('H:i')) {
                $value = $value->format('d-m-Y');
            } else {
                $value = $value->format('d-m-Y H:i');
            }
        }

        if (true === $value) {
            $value = $this->translator->trans('Yes');
        }

        if (false === $value) {
            $value = $this->translator->trans('No');
        }

        return (string) $value;
    }
}
