<?php

namespace FL\ReportsBundle\DataTransformer\RequestTransformer;

use FL\QBJSParserBundle\Service\JavascriptBuilders;
use FL\ReportsBundle\Model\ReportInterface;
use FL\ReportsBundle\Storage\ReportStorageInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RequestToReport
{
    /**
     * @var JavascriptBuilders
     */
    private $javascriptBuilders;

    /**
     * @var ReportStorageInterface
     */
    private $reportStorage;

    /**
     * @var string
     */
    private $reportClass;

    /**
     * @param JavascriptBuilders     $javascriptBuilders
     * @param ReportStorageInterface $reportStorage
     * @param string                 $reportClass
     */
    public function __construct(
        JavascriptBuilders $javascriptBuilders,
        ReportStorageInterface $reportStorage,
        string $reportClass
    ) {
        $this->javascriptBuilders = $javascriptBuilders;
        $this->reportStorage = $reportStorage;
        $this->reportClass = $reportClass;
    }

    /**
     * @param Request $request
     * @param string  $reportName
     *
     * @return ReportInterface
     *
     * @throws RequestTransformationException
     */
    public function fromRestSaveRequest(Request $request, string $reportName): ReportInterface
    {
        if (!($rulesJsonString = $request->get('rulesJsonString'))) {
            throw new RequestTransformationException('Missing parameter: rulesJsonString', JsonResponse::HTTP_BAD_REQUEST);
        }
        if (!($reportBuilderId = $request->get('reportBuilderId'))) {
            throw new RequestTransformationException('Missing parameter: reportBuilderId', JsonResponse::HTTP_BAD_REQUEST);
        }
        if (!($correspondingBuilder = $this->javascriptBuilders->getBuilderById($reportBuilderId))) {
            throw new RequestTransformationException('Invalid parameter: reportBuilderId', JsonResponse::HTTP_BAD_REQUEST);
        }

        /** @var ReportInterface $report */
        if (!($report = $this->reportStorage->findOneBy(['reportName' => $reportName]))) {
            $report = new $this->reportClass();
        }
        $report
            ->setReportName($reportName)
            ->setRulesJsonString($rulesJsonString)
            ->setReportBuilderId($correspondingBuilder->getBuilderId())
            ->setReportBuilderHumanReadableName($correspondingBuilder->getHumanReadableName())
            ->clearColumns()
        ;

        if (is_null(($reportColumns = $request->get('reportColumns')))) { // might be empty array
            throw new RequestTransformationException('Missing parameter: reportColumns', JsonResponse::HTTP_BAD_REQUEST);
        }
        if (!is_array($reportColumns)) {
            throw new RequestTransformationException('Invalid parameter: reportColumns must be an array', JsonResponse::HTTP_BAD_REQUEST);
        }
        foreach ($reportColumns as $column) {
            if (!is_string($column)) {
                throw new RequestTransformationException('Invalid parameter: each element of reportColumns must be a string', JsonResponse::HTTP_BAD_REQUEST);
            }
            $report->addColumn($column);
        }

        if (is_null(($reportSortColumns = $request->get('reportSortColumns')))) { // might be empty array
            throw new RequestTransformationException('Missing parameter: reportSortColumns', JsonResponse::HTTP_BAD_REQUEST);
        }
        if (!is_array($reportSortColumns)) {
            throw new RequestTransformationException('Invalid parameter: reportSortColumns must be an array', JsonResponse::HTTP_BAD_REQUEST);
        }
        foreach ($reportSortColumns as $reportSortColumn => $order) {
            if ($order !== 'ASC' && $order !== 'DESC') {
                throw new RequestTransformationException('Invalid parameter: each element of reportSortColumns must be ASC or DESC', JsonResponse::HTTP_BAD_REQUEST);
            }
            $report->addSortColumn($reportSortColumn, $order);
        }

        return $report;
    }

    /**
     * @param string $reportName
     *
     * @return ReportInterface|null
     */
    public function fromRestDeleteRequest(string $reportName)
    {
        return $this->reportStorage->findOneBy(['reportName' => $reportName]);
    }

    /**
     * @param string $reportName
     *
     * @return ReportInterface
     *
     * @throws RequestTransformationException
     */
    public function fromRestShowRequest(string $reportName)
    {
        if (!($report = $this->reportStorage->findOneBy(['reportName' => $reportName]))) {
            throw new RequestTransformationException('No report Found', JsonResponse::HTTP_NOT_FOUND);
        }

        return $report;
    }

    /**
     * @param Request $request
     *
     * @return ReportInterface
     *
     * @throws RequestTransformationException
     */
    public function fromRestBuildRequest(Request $request): ReportInterface
    {
        if (!($rulesJsonString = $request->get('rulesJsonString'))) {
            throw new RequestTransformationException('Missing parameter: rulesJsonString', JsonResponse::HTTP_BAD_REQUEST);
        }
        if (!($reportBuilderId = $request->get('reportBuilderId'))) {
            throw new RequestTransformationException('Missing parameter: reportBuilderId', JsonResponse::HTTP_BAD_REQUEST);
        }
        if (!($correspondingBuilder = $this->javascriptBuilders->getBuilderById($reportBuilderId))) {
            throw new RequestTransformationException('Invalid parameter: reportBuilderId', JsonResponse::HTTP_BAD_REQUEST);
        }

        /** @var ReportInterface $report */
        $report = new $this->reportClass();
        $report
            ->setReportName('')
            ->setRulesJsonString($rulesJsonString)
            ->setReportBuilderId($correspondingBuilder->getBuilderId())
            ->setReportBuilderHumanReadableName($correspondingBuilder->getHumanReadableName())
            ->clearColumns()
        ;

        if (is_null(($reportColumns = $request->get('reportColumns')))) { // might be empty array
            throw new RequestTransformationException('Missing parameter: reportColumns', JsonResponse::HTTP_BAD_REQUEST);
        }
        if (!is_array($reportColumns)) {
            throw new RequestTransformationException('Invalid parameter: reportColumns must be an array', JsonResponse::HTTP_BAD_REQUEST);
        }
        foreach ($reportColumns as $column) {
            if (!is_string($column)) {
                throw new RequestTransformationException('Invalid parameter: each element of reportColumns must be a string', JsonResponse::HTTP_BAD_REQUEST);
            }
            $report->addColumn($column);
        }

        if (is_null(($reportSortColumns = $request->get('reportSortColumns')))) { // might be empty array
            throw new RequestTransformationException('Missing parameter: reportSortColumns', JsonResponse::HTTP_BAD_REQUEST);
        }
        if (!is_array($reportSortColumns)) {
            throw new RequestTransformationException('Invalid parameter: reportSortColumns must be an array', JsonResponse::HTTP_BAD_REQUEST);
        }
        foreach ($reportSortColumns as $reportSortColumn => $order) {
            if ($order !== 'ASC' && $order !== 'DESC') {
                throw new RequestTransformationException('Invalid parameter: each element of reportSortColumns must be ASC or DESC', JsonResponse::HTTP_BAD_REQUEST);
            }
            $report->addSortColumn($reportSortColumn, $order);
        }

        return $report;
    }
}
