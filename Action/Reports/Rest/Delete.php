<?php

namespace FL\ReportsBundle\Action\Reports\Rest;

use FL\ReportsBundle\Storage\ReportStorageInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use FL\ReportsBundle\DataTransformer\RequestTransformer\RequestToReport;

class Delete
{
    /**
     * @var RequestToReport
     */
    protected $requestToReportTransformer;

    /**
     * @var ReportStorageInterface
     */
    protected $reportStorage;

    /**
     * @param RequestToReport        $requestToReportTransformer,
     * @param ReportStorageInterface $reportStorage
     */
    public function __construct(
        RequestToReport $requestToReportTransformer,
        ReportStorageInterface $reportStorage
    ) {
        $this->requestToReportTransformer = $requestToReportTransformer;
        $this->reportStorage = $reportStorage;
    }

    /**
     * @param Request $request
     * @param string  $reportName
     *
     * @return JsonResponse
     */
    public function __invoke(Request $request, string $reportName): JsonResponse
    {
        if ($report = $this->requestToReportTransformer->fromRestDeleteRequest($reportName)) {
            $this->reportStorage->remove($report);
        }

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
