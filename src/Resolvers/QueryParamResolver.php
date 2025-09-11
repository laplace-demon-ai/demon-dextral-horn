<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Factories\StrategyFactory;
use Illuminate\Support\Arr;

/**
 * Resolves query parameters for the target route.
 *
 * @class QueryParamResolver
 */
final readonly class QueryParamResolver extends AbstractResolver
{
    /**
     * Constructor for the resolver.
     *
     * @param StrategyFactory $strategyFactory
     */
    public function __construct(private StrategyFactory $strategyFactory) {}

    /**
     * {@inheritDoc}
     */
    public function resolve(
        ?array $targetRouteDefinition = null,
        ?RequestData $requestData = null,
        ?ResponseData $responseData = null
    ): array {
        $params = Arr::get($targetRouteDefinition, 'query_params', []);

        $resolvedQueryParams = [];

        foreach ($params as $key => $value) {
            $strategyClass = Arr::get($value, 'strategy');
            $options = Arr::get($value, 'options', []);

            // Create the strategy instance by using the strategy factory.
            $strategy = $this->strategyFactory->make($strategyClass);

            // Resolve the query parameter using the strategy, set the resolved value with its key.
            $resolvedQueryParams[$key] = $strategy->handle(
                requestData: $requestData,
                responseData: $responseData,
                options: $options
            );
        }

        return $resolvedQueryParams;
    }
}
