<?php

namespace FL\ReportsBundle\Action\Reports\Rest;

use FL\ReportsBundle\Storage\ReportStorageInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use FL\ReportsBundle\DataTransformer\RequestTransformer\RequestToReport;
use FL\ReportsBundle\DataTransformer\RequestTransformer\RequestTransformationException;

class Save
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
     * @var mixed
     */
    protected $serializer;

    /**
     * @param RequestToReport        $requestToReportTransformer
     * @param ReportStorageInterface $reportStorage
     * @param mixed                  $serializer
     */
    public function __construct(
        RequestToReport $requestToReportTransformer,
        ReportStorageInterface $reportStorage,
        $serializer // allow other serializers
    ) {
        $this->requestToReportTransformer = $requestToReportTransformer;
        $this->reportStorage = $reportStorage;
        $this->serializer = $serializer;
    }

    /**
     * @param Request $request
     * @param string  $reportName
     *
     * @return JsonResponse
     */
    public function __invoke(Request $request, string $reportName): JsonResponse
    {
        try {
            $report = $this->requestToReportTransformer->fromRestSaveRequest($request, $reportName);
        } catch (RequestTransformationException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], $exception->getHttpErrorCode());
        }

        $this->reportStorage->persist($report);

        return new JsonResponse($this->serializer->serialize($report, 'json'), JsonResponse::HTTP_OK);
    }
}
