<?php

namespace FL\ReportsBundle\Action\Reports\Rest;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use FL\ReportsBundle\DataTransformer\RequestTransformer\RequestToReport;
use FL\ReportsBundle\DataTransformer\RequestTransformer\RequestTransformationException;

class Show
{
    /**
     * @var RequestToReport
     */
    protected $requestToReportTransformer;

    /**
     * @var mixed
     */
    protected $serializer;

    /**
     * @param RequestToReport $requestToReportTransformer
     * @param mixed $serializer
     */
    public function __construct(
        RequestToReport $requestToReportTransformer,
        $serializer // allow other serializers
    ) {
        $this->requestToReportTransformer = $requestToReportTransformer;
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
            $report = $this->requestToReportTransformer->fromRestShowRequest($reportName);
        } catch (RequestTransformationException $exception) {
            return new JsonResponse(['message'=>$exception->getCode()], $exception->getHttpErrorCode());
        }

        return new JsonResponse($this->serializer->serialize($report, 'json'), JsonResponse::HTTP_OK);
    }
}
