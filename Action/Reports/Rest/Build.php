<?php

namespace FL\ReportsBundle\Action\Reports\Rest;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use FL\QBJSParserBundle\Service\JavascriptBuilders;
use FL\ReportsBundle\DataTransformer\BuildReportQueryTransformer\QueryToResponseArray;
use FL\ReportsBundle\DataTransformer\RequestTransformer\RequestToPaginationQuery;
use FL\ReportsBundle\DataTransformer\RequestTransformer\RequestToReport;
use FL\ReportsBundle\DataTransformer\RequestTransformer\RequestTransformationException;
use FL\ReportsBundle\DataObjects\BuildReportQuery;

class Build
{
    /**
     * @var RequestToReport
     */
    protected $requestToReportTransformer;

    /**
     * @var RequestToPaginationQuery
     */
    protected $requestToPaginationQuery;

    /**
     * @var QueryToResponseArray
     */
    protected $queryToResponseArray;

    /**
     * @var JavascriptBuilders
     */
    protected $javascriptBuilders;
    
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @param RequestToReport $requestToReportTransformer
     * @param RequestToPaginationQuery $requestToPaginationQuery
     * @param QueryToResponseArray $queryToResponseArray
     * @param JavascriptBuilders $javascriptBuilders
     * @param RouterInterface $router
     */
    public function __construct(
        RequestToReport $requestToReportTransformer,
        RequestToPaginationQuery $requestToPaginationQuery,
        QueryToResponseArray $queryToResponseArray,
        JavascriptBuilders $javascriptBuilders,
        RouterInterface $router
    ) {
        $this->requestToReportTransformer = $requestToReportTransformer;
        $this->requestToPaginationQuery = $requestToPaginationQuery;
        $this->queryToResponseArray = $queryToResponseArray;
        $this->javascriptBuilders = $javascriptBuilders;
        $this->router = $router;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $report = $this->requestToReportTransformer->fromRestBuildRequest($request);
        } catch (RequestTransformationException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], $exception->getHttpErrorCode());
        }

        try {
            $paginationQuery = $this->requestToPaginationQuery->fromRestBuildRequest($request);
        } catch (RequestTransformationException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], $exception->getHttpErrorCode());
        }

        if (! ($correspondingBuilder = $this->javascriptBuilders->getBuilderById($report->getReportBuilderId()))) {
            return new JsonResponse(['message' => 'Invalid parameter: reportName'], JsonResponse::HTTP_NOT_FOUND);
        }

        $responseArray = $this->queryToResponseArray->defaultTransform(new BuildReportQuery(
            $report,
            $paginationQuery,
            $correspondingBuilder,
            $request->getSchemeAndHttpHost() . $request->getRequestUri()
        ));

        return new JsonResponse($responseArray, 200);
    }
}
