<?php

namespace FL\ReportsBundle\Action\Reports\Rest;

use FL\ReportsBundle\Storage\ReportsStorageInterface;
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
     * @var ReportsStorageInterface
     */
    protected $reportsStorage;

    /**
     * @param RequestToReport $requestToReportTransformer,
     * @param ReportsStorageInterface $reportsStorage
     */
    public function __construct(
        RequestToReport $requestToReportTransformer,
        ReportsStorageInterface $reportsStorage
    ) {
        $this->requestToReportTransformer = $requestToReportTransformer;
        $this->reportsStorage = $reportsStorage;
    }

    /**
     * @param Request $request
     * @param string $reportName
     *
     * @return JsonResponse
     */
    public function __invoke(Request $request, string $reportName): JsonResponse
    {
        if ($report = $this->requestToReportTransformer->fromRestDeleteRequest($reportName)) {
            $this->reportsStorage->remove($report);
        }

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
