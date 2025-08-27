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
     * Constructor for the resolver.
     *
     * @param StrategyFactory $strategyFactory
     */
    public function __construct(private readonly StrategyFactory $strategyFactory) {}

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
            $strategy = $this->strategyFactory->make($strategyClass);

            $resolvedParams[$key] = $strategy->handle(
                requestData: $requestData,
                responseData: $responseData,
                options: $options
            );
        }

        return $this->cartesianExpandParams($resolvedParams);
    }

    /**
     * Expands route parameters into a Cartesian product.
     *
     * This method takes an array of parameters, where some values may be arrays,
     * and generates a new array containing all possible combinations of these values.
     *
     * Example: ['color' => ['red', 'blue'], 'size' => 'small']
     * Result: [['color' => 'red', 'size' => 'small'], ['color' => 'blue', 'size' => 'small']]
     *
     * @param array $params
     *
     * @return array<int, array<string, mixed>>
     */
    private function cartesianExpandParams(array $params): array
    {
        // Normalize parameter values to arrays.
        $paramValues = array_map(
            fn ($value) => is_array($value) ? $value : [$value],
            $params
        );

        // Generate the Cartesian product of parameter values.
        return array_reduce(
            array_keys($paramValues),
            function (array $combinations, string $paramName) use ($paramValues): array {
                $nextCombinations = [];

                foreach ($combinations as $combination) {
                    foreach ($paramValues[$paramName] as $paramValue) {
                        $nextCombinations[] = $combination + [$paramName => $paramValue];
                    }
                }

                return $nextCombinations;
            },
            [[]]
        );
    }
}
