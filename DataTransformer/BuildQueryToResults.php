<?php

namespace FL\ReportsBundle\DataTransformer;

use FL\QBJSParserBundle\Model\Builder\ResultColumn;
use FL\QBJSParserBundle\Service\JsonQueryParserInterface;
use FL\ReportsBundle\Event\ResultColumnCreatedEvent;
use FL\ReportsBundle\Model\ReportInterface;
use FL\ReportsBundle\Model\ReportResultColumn;
use FL\ReportsBundle\Storage\ReportResultsStorageInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use FL\ReportsBundle\DataObjects\BuildReportQuery;
use Symfony\Component\Translation\TranslatorInterface;

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
     * @param BuildReportQuery $buildReportQuery
     *
     * @return array
     */
    public function transformToResultsArray(BuildReportQuery $buildReportQuery): array
    {
        $parsedRuleGroup = $this->jsonQueryParser->parseJsonString(
            $buildReportQuery->getReport()->getRulesJsonString(),
            $buildReportQuery->getReportBuilder()->getClassName(),
            $buildReportQuery->getReport()->getSortColumns()
        );

        $currentPage = $buildReportQuery->getPaginationQuery()->getCurrentPage();
        $resultsPerPage = $buildReportQuery->getPaginationQuery()->getMaxResultsPerPage();
        $rawResults = $this->reportResultsStorage->resultsFromParsedRuleGroup($parsedRuleGroup, $currentPage, $resultsPerPage);

        $report = $buildReportQuery->getReport();
        $serializedResults = $this->serializedResults($rawResults, $report);
        $columnsHumanReadable = $this->columnsHumanReadable($report, $buildReportQuery);

        $totalResults = $this->reportResultsStorage->countResultsFromParsedRuleGroup($parsedRuleGroup);
        $totalPages = ceil($totalResults / $resultsPerPage);

        $resultsArray = [
            'data' => [
                'header' => $columnsHumanReadable,
                'results' => $serializedResults,
            ],
            'state' => [
                'currentPage' => $currentPage,
                'maxResultsPerPage' => $resultsPerPage,
                'resultsInThisPage' => count($serializedResults),
                'totalResults' => $totalResults,
                'totalPages' => $totalPages,
            ],
        ];

        $resultsArray['state']['previousPage'] = ($currentPage > 1) ? ($currentPage - 1) : null;
        $resultsArray['state']['nextPage'] = ($currentPage < $totalPages) ? ($currentPage + 1) : null;

        return $resultsArray;
    }

    /**
     * Return value is suitable to be serialized into a CSV file, and used as an HTTP response.
     *
     * @param BuildReportQuery $buildReportQuery
     *
     * @return array
     */
    public function transformToTableArray(BuildReportQuery $buildReportQuery): array
    {
        $parsedRuleGroup = $this->jsonQueryParser->parseJsonString(
            $buildReportQuery->getReport()->getRulesJsonString(),
            $buildReportQuery->getReportBuilder()->getClassName(),
            $buildReportQuery->getReport()->getSortColumns()
        );
        $currentPage = $buildReportQuery->getPaginationQuery()->getCurrentPage();
        $resultsPerPage = $buildReportQuery->getPaginationQuery()->getMaxResultsPerPage();
        $rawResults = $this->reportResultsStorage->resultsFromParsedRuleGroup($parsedRuleGroup, $currentPage, $resultsPerPage);

        $report = $buildReportQuery->getReport();
        $serializedResults = $this->serializedResults($rawResults, $report);
        $columnsHumanReadable = $this->columnsHumanReadable($report, $buildReportQuery);

        $totalResults = $this->reportResultsStorage->countResultsFromParsedRuleGroup($parsedRuleGroup);
        $totalPages = ceil($totalResults / $resultsPerPage);

        return array_merge(
            [$columnsHumanReadable],
            $serializedResults
//            [ // cannot use yet, because they don't have the correct keys
//                sprintf('%s: %d', $this->translator->trans('Current Page'), $currentPage),
//                sprintf('%s: %d', $this->translator->trans('Max Results Per Page'), $resultsPerPage),
//                sprintf('%s: %d', $this->translator->trans('Results In This Page'), count($serializedResults)),
//                sprintf('%s: %d', $this->translator->trans('Total Results'), $totalResults),
//                sprintf('%s: %d', $this->translator->trans('Total Pages'), $totalPages),
//            ]
        );
    }

    /**
     * @param object[]        $results
     * @param ReportInterface $report
     *
     * @return array
     */
    protected function serializedResults(array $results, ReportInterface $report): array
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        $serializedResults = [];

        foreach ($results as $key => $result) { // go through a single page
            $serializedResults[$key] = []; // rows still exist, even if there are no columns
            foreach ($report->getColumns() as $column) {
                try {
                    $columnValue = $this->valueToString($accessor->getValue($result, $column));
                } catch (UnexpectedTypeException $exception) { // when accessing several levels deep, one of the properties might be null
                    $columnValue = '';
                }
                $reportResultColumn = new ReportResultColumn($column, $columnValue);
                $this->dispatcher->dispatch(ResultColumnCreatedEvent::EVENT_NAME, new ResultColumnCreatedEvent(
                    $result,
                    $reportResultColumn
                ));
                $serializedResults[$key][$column] = $this->htmlPurifier->purify($reportResultColumn->getColumnValue());
            }
        }

        return $serializedResults;
    }

    /**
     * @param ReportInterface  $report
     * @param BuildReportQuery $buildReportQuery
     *
     * @return array
     */
    protected function columnsHumanReadable(ReportInterface $report, BuildReportQuery $buildReportQuery): array
    {
        /** @var ResultColumn $column */
        $columnsHumanReadable = [];
        foreach ($report->getColumns() as $column) {
            if ($columnHumanReadable = $buildReportQuery->getReportBuilder()->getHumanReadableWithMachineName($column)) {
                $columnsHumanReadable[] = $columnHumanReadable;
            }
        }

        return $columnsHumanReadable;
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    protected function valueToString($value): string
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
