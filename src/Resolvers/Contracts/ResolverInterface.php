<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers\Contracts;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;

/**
 * Interface for resolvers.
 *
 * @interface ResolverInterface
 */
interface ResolverInterface
{
    /**
     * Resolves based on the provided route, request data, and response data.
     *
     * @param array|null $route
     * @param RequestData|null $requestData
     * @param ResponseData|null $responseData
     * 
     * @return array
     */
    public function resolve(
        ?array $route = null,
        ?RequestData $requestData = null,
        ?ResponseData $responseData = null
    ): array;
}