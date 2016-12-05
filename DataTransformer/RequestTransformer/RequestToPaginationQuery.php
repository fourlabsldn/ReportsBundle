<?php

namespace FL\ReportsBundle\DataTransformer\RequestTransformer;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use FL\ReportsBundle\DataObjects\PaginationQuery;

class RequestToPaginationQuery
{
    /**
     * @param Request $request
     *
     * @return PaginationQuery
     */
    public function fromRestBuildRequest(Request $request): PaginationQuery
    {
        if (is_null($currentPage = $request->get('currentPage'))) {
            throw new RequestTransformationException('Required parameter: currentPage', JsonResponse::HTTP_BAD_REQUEST);
        }

        if (is_null($maxResultsPerPage = $request->get('maxResultsPerPage'))) {
            throw new RequestTransformationException('Required parameter: maxResultsPerPage', JsonResponse::HTTP_BAD_REQUEST);
        }

        return new PaginationQuery(intval($currentPage), intval($maxResultsPerPage));
    }
}