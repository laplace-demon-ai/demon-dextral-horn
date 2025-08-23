<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Resolvers\AbstractResolver;
use DemonDextralHorn\Resolvers\Contracts\TargetRouteResolverInterface;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;

/**
 * Resolve the target routes for a given request (get the target routes from the config for given request)
 *
 * @class TargetRouteResolver
 */
final class TargetRouteResolver extends AbstractResolver implements TargetRouteResolverInterface
{
    /**
     * @inheritDoc
     */
    public function resolve(
        ?array $route = null,
        ?RequestData $requestData = null,
        ?ResponseData $responseData = null
    ): array {
        // Get the rules from the config
        $rules = config('demon-dextral-horn.rules', []);

        // Set the route name which will be used for matching the appropriate rule
        $routeName = $requestData->routeName;

        // Find the matching rule for the given route name
        $rule = Arr::first($rules, function ($rule) use ($routeName) {
            return Arr::get($rule, 'trigger.route') === $routeName
                && strtoupper(Arr::get($rule, 'trigger.method', Request::METHOD_GET)) === Request::METHOD_GET;
        });

        // Get the target routes from the rule
        return Arr::get($rule, 'targets', []);
    }
}