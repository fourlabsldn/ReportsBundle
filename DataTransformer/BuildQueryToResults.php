<?php

namespace FL\ReportsBundle\DataTransformer;

use FL\QBJSParserBundle\Service\JsonQueryParserInterface;
use FL\ReportsBundle\Event\ResultColumnCreatedEvent;
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
     * @var \HTMLPurifier
     */
    protected $htmlPurifier;

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
     * @param \HTMLPurifier                 $htmlPurifier
     * @param ReportResultsStorageInterface $reportResultsStorage
     * @param EventDispatcherInterface      $dispatcher
     * @param TranslatorInterface           $translator
     */
    public function __construct(
        JsonQueryParserInterface $jsonQueryParser,
        \HTMLPurifier $htmlPurifier,
        ReportResultsStorageInterface $reportResultsStorage,
        EventDispatcherInterface $dispatcher,
        TranslatorInterface $translator
    ) {
        $this->jsonQueryParser = $jsonQueryParser;
        $this->htmlPurifier = $htmlPurifier;
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

        $objectResultsToArray = $this->transformObjectResultsToArray($rawResults, $report);
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
     *
     * @param BuildReportQuery $buildReportQuery
     * @param bool             $allResults       (if true, ignores the PaginationQuery inside BuildReportQuery)
     *
     * @return array
     */
    public function transformToTableArray(BuildReportQuery $buildReportQuery, bool $allResults = false): array
    {
        $resultsArray = $this->transformToResultsArray($buildReportQuery, $allResults);
        $report = $buildReportQuery->getReport();
        $columnToHumanReadable = $this->resolveColumnsHumanReadable($report, $buildReportQuery);

        // return an array in this format:
        // [
        //  ['Column X Human Readable Name' => 'Hello', 'Column Y HumanReadableName' => 'Bye'],
        //  ['Column X Human Readable Name' => 'Hi', 'Column Y HumanReadableName' => 'See you'],
        // ]
        $newRows = [];
        foreach ($resultsArray['data']['results'] as $rowKey => $row) {
            $newRows[$rowKey] = [];
            foreach ($row as $columnMachineName => $columnValue) {
                $newRows[$rowKey][$columnToHumanReadable[$columnMachineName]] = $columnValue;
            }
        }

        return $newRows;
    }

    /**
     * Explores the object graph and creates an array,
     * where each object is a row.
     *
     * @param object[]        $rawResults
     * @param ReportInterface $report
     *
     * @return array
     */
    protected function transformObjectResultsToArray(array $rawResults, ReportInterface $report): array
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
                    $reportResultColumn
                ));
                $objectResultsToArray[$key][$column] = $this->htmlPurifier->purify($reportResultColumn->getColumnValue());
            }
        }

        return $objectResultsToArray;
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
            if ($value->format('H:i') === '00:00') {
                $value = $value->format('d-m-Y');
            } else {
                $value = $value->format('d-m-Y H:i');
            }
        }

        if ($value === true) {
            $value = $this->translator->trans('Yes');
        }

        if ($value === false) {
            $value = $this->translator->trans('No');
        }

        return (string) $value;
    }
}
