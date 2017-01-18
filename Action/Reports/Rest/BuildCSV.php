<?php

namespace FL\ReportsBundle\Action\Reports\Rest;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\RouterInterface;
use FL\QBJSParserBundle\Service\JavascriptBuilders;
use FL\ReportsBundle\DataTransformer\BuildReportQueryTransformer\QueryToResponseArray;
use FL\ReportsBundle\DataTransformer\RequestTransformer\RequestToPaginationQuery;
use FL\ReportsBundle\DataTransformer\RequestTransformer\RequestToReport;
use FL\ReportsBundle\DataTransformer\RequestTransformer\RequestTransformationException;
use FL\ReportsBundle\DataObjects\BuildReportQuery;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

class BuildCSV
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
     * @var EncoderInterface
     */
    protected $csvEncoder;

    /**
     * @param RequestToReport          $requestToReportTransformer
     * @param RequestToPaginationQuery $requestToPaginationQuery
     * @param QueryToResponseArray     $queryToResponseArray
     * @param JavascriptBuilders       $javascriptBuilders
     * @param RouterInterface          $router
     * @param EncoderInterface         $csvEncoder
     */
    public function __construct(
        RequestToReport $requestToReportTransformer,
        RequestToPaginationQuery $requestToPaginationQuery,
        QueryToResponseArray $queryToResponseArray,
        JavascriptBuilders $javascriptBuilders,
        RouterInterface $router,
        EncoderInterface $csvEncoder
    ) {
        $this->requestToReportTransformer = $requestToReportTransformer;
        $this->requestToPaginationQuery = $requestToPaginationQuery;
        $this->queryToResponseArray = $queryToResponseArray;
        $this->javascriptBuilders = $javascriptBuilders;
        $this->router = $router;
        $this->csvEncoder = $csvEncoder;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function __invoke(Request $request): Response
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

        if (!($correspondingBuilder = $this->javascriptBuilders->getBuilderById($report->getReportBuilderId()))) {
            return new JsonResponse(['message' => 'Invalid parameter: reportName'], JsonResponse::HTTP_NOT_FOUND);
        }

        $responseArray = $this->queryToResponseArray->transformToRestArray(new BuildReportQuery(
            $report,
            $paginationQuery,
            $correspondingBuilder,
            $request->getSchemeAndHttpHost().$request->getRequestUri()
        ));

        // do not use CsvEncoder::FORMAT -- this class was introduced in Symfony 3.2
        $response = new Response($this->csvEncoder->encode($responseArray, 'csv'), 200);
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $report->getReportName(), // name
            'report.csv' // fallback name
        );
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'text/csv');

        return $response;
    }
}
