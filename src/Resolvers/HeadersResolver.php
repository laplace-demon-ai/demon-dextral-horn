<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Enums\PrefetchType;
use DemonDextralHorn\Factories\StrategyFactory;
use Illuminate\Support\Arr;

/**
 * Resolves headers for the target route.
 *
 * @class HeadersResolver
 */
final class HeadersResolver extends AbstractResolver
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
        // Prepare custom prefetch header (Demon-Prefetch-Call)
        $prefetchHeaderName = config('demon-dextral-horn.defaults.prefetch_header');
        $prefetchHeaderValue = PrefetchType::AUTO->value;
        $prefetchHeader = [
            $prefetchHeaderName => $prefetchHeaderValue,
        ];

        $headersData = $requestData?->headers;

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

        // Map the headers to the correct format as hyphenated capitalization
        $mappedHeaders = [
            'Authorization' => Arr::get($resolvedHeaders, 'authorization') ?? $headersData?->authorization, // Prioritize resolved headers before forwarding which enables config override or response token extraction
            'Accept' => $headersData?->accept,
            'Accept-Language' => $headersData?->acceptLanguage,
        ];

        // Merge prefetchHeader and filter null values from the headers
        return array_filter(
            array_merge($mappedHeaders, $prefetchHeader),
            fn ($value) => ! is_null($value)
        );
    }
}
