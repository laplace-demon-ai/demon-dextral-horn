<?php

declare(strict_types=1);

namespace DemonDextralHorn\Data;

use Spatie\LaravelData\Data;

/**
 * DTO representing target route prepared for the request.
 *
 * @class TargetRouteData
 */
final class TargetRouteData extends Data
{
    /**
     * Create a new TargetRouteData object.
     *
     * @param string $routeName
     * @param string $method
     * @param array $routeParams
     * @param array $queryParams
     * @param array $headers
     * @param array $cookies
     */
    public function __construct(
        public string $routeName,
        public string $method,
        public array $routeParams,
        public array $queryParams,
        public array $headers,
        public array $cookies
    ) {}
}
