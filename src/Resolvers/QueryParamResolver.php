<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Resolvers\AbstractResolver;
use Illuminate\Support\Arr;

/**
 * Resolves query parameters for the target route.
 *
 * @class QueryParamResolver
 */
final class QueryParamResolver extends AbstractResolver
{
    /**
     * @inheritDoc
     */
    public function resolve(
        ?array $route = null,
        ?RequestData $requestData = null,
        ?ResponseData $responseData = null
    ): array {
        $params = Arr::get($route, 'query_params', []);

        // todo check if that covers all kind of query params, for instance when multiple is sent will it add as ?items[]=1&items[]=2
        $resolvedQueryParams = [];

        // todo here we will resolve query paramsfrom validation logic (maybe call a method for it), and below we add them to url
        foreach ($params as $key => $value) {
            $resolvedQueryParams[$key] = rand(1, 100); // todo this is for testing purpose before addding validation like logic to config file
        }

        return $resolvedQueryParams;
    }
}