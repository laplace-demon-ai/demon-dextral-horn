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
     * Resolves based on the provided target route definition, request data, and response data.
     *
     * @param array|null $targetRouteDefinition
     * @param RequestData|null $requestData
     * @param ResponseData|null $responseData
     *
     * @return array
     */
    public function resolve(
        ?array $targetRouteDefinition = null,
        ?RequestData $requestData = null,
        ?ResponseData $responseData = null
    ): array;
}
