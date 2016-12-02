<?php

namespace FL\ReportsBundle\Action\Reports\Rest;

use FL\ReportsBundle\Storage\ReportsStorageInterface;
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
     * @var ReportsStorageInterface
     */
    protected $reportsStorage;

    /**
     * @var mixed
     */
    protected $serializer;

    /**
     * @param RequestToReport $requestToReportTransformer
     * @param ReportsStorageInterface $reportsStorage
     * @param mixed $serializer
     */
    public function __construct(
        RequestToReport $requestToReportTransformer,
        ReportsStorageInterface $reportsStorage,
        $serializer // allow other serializers
    ) {
        $this->requestToReportTransformer = $requestToReportTransformer;
        $this->reportsStorage = $reportsStorage;
        $this->serializer = $serializer;
    }

    /**
     * @param Request $request
     * @param string $reportName
     * @return JsonResponse
     */
    public function __invoke(Request $request, string $reportName): JsonResponse
    {
        try {
            $report = $this->requestToReportTransformer->fromRestSaveRequest($request, $reportName);
        } catch (RequestTransformationException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], $exception->getHttpErrorCode());
        }

        $this->reportsStorage->persist($report);

        return new JsonResponse($this->serializer->serialize($report, 'json'), JsonResponse::HTTP_OK);
    }
}
