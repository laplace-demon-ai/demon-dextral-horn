<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Enums\HttpHeaderType;
use DemonDextralHorn\Factories\StrategyFactory;
use DemonDextralHorn\Traits\RequestParsingTrait;
use Illuminate\Support\Arr;

/**
 * Resolves headers for the target route.
 *
 * @class HeadersResolver
 */
final readonly class HeadersResolver extends AbstractResolver
{
    use RequestParsingTrait;

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
        $headers = Arr::get($targetRouteDefinition, 'headers', []);

        $resolvedHeaders = [];

        foreach ($headers as $key => $value) {
            $strategyClass = Arr::get($value, 'strategy');
            $options = Arr::get($value, 'options', []);

            // Create the strategy instance by using the strategy factory.
            $strategy = $this->strategyFactory->make($strategyClass);

            // Resolve the headers using the strategy, set the resolved value with its key.
            $resolvedHeaders[$key] = $strategy->handle(
                requestData: $requestData,
                responseData: $responseData,
                options: $options
            );
        }

        // Prepare the final headers by merging the resolved headers with the original request headers.
        return $this->prepareMappedHeaders(
            $requestData,
            overrides: array_filter([
                // Prioritize resolved headers over request headers
                HttpHeaderType::AUTHORIZATION->value => Arr::get($resolvedHeaders, 'authorization'),
            ], fn($value) => ! is_null($value))
        );
    }
}
