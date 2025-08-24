<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Factories\StrategyFactory;
use Illuminate\Support\Arr;

/**
 * Resolves route parameters for the target route.
 *
 * @class RouteParamResolver
 */
final class RouteParamResolver extends AbstractResolver
{
    /**
     * {@inheritDoc}
     */
    public function resolve(
        ?array $targetRouteDefinition = null,
        ?RequestData $requestData = null,
        ?ResponseData $responseData = null
    ): array {
        $params = Arr::get($targetRouteDefinition, 'route_params', []);

        $resolvedParams = [];

        foreach ($params as $key => $value) {
            $strategyClass = Arr::get($value, 'strategy');
            $options = Arr::get($value, 'options', []);

            // Create the strategy instance by using the strategy factory.
            $strategyFactory = app(StrategyFactory::class);
            $strategy = $strategyFactory->make($strategyClass);

            $resolvedParams[$key] = $strategy->handle(
                requestData: $requestData,
                responseData: $responseData,
                options: $options
            );
        }

        return $resolvedParams;
    }
}
