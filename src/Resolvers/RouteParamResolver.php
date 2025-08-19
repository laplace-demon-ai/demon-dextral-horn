<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Resolvers\AbstractResolver;
use Illuminate\Support\Arr;

/**
 * Resolves route parameters for the target route.
 *
 * @class RouteParamResolver
 */
final class RouteParamResolver extends AbstractResolver
{
    /**
     * @inheritDoc
     */
    public function resolve(
        ?array $route = null,
        ?RequestData $requestData = null,
        ?ResponseData $responseData = null
    ): array {
        $params = Arr::get($route, 'route_params', []);

        $resolvedParams = [];
        // todo Note that when routes are called, in case they return not success message for one of them, it should not block other requests,, - maybe somehow log them but app should work without breaking

        foreach ($params as $key => $value) {
            // todo here we will have the logic for NOT clousre calling, since config will not have closure,, instead form request validation like config and logic/method to resolve params by using these given config
            $resolvedParams[$key] = rand(1, 100); // todo this is for testing purpose before addding validation like logic to config file
        }

        return $resolvedParams;
    }
}