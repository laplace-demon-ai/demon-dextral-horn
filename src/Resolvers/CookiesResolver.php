<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Factories\StrategyFactory;
use Illuminate\Support\Arr;

/**
 * Resolve the cookies.
 *
 * @class CookiesResolver
 */
final class CookiesResolver extends AbstractResolver
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
        $cookies = Arr::get($targetRouteDefinition, 'cookies', []);

        $resolvedCookies = [];

        foreach ($cookies as $key => $value) {
            $strategyClass = Arr::get($value, 'strategy');
            $options = Arr::get($value, 'options', []);

            // Create the strategy instance by using the strategy factory.
            $strategy = $this->strategyFactory->make($strategyClass);

            $resolvedCookies[$key] = $strategy->handle(
                requestData: $requestData,
                responseData: $responseData,
                options: $options
            );
        }

        // Auto forward session cookie if not set by strategy and available in the original request.
        if (! Arr::has($resolvedCookies, 'session_cookie') || Arr::get($resolvedCookies, 'session_cookie') === null) {
            $resolvedCookies['session_cookie'] = $requestData?->cookies?->sessionCookie;
        }

        // Filter out any null values from the resolved cookies.
        return array_filter(
            $resolvedCookies,
            fn ($value) => ! is_null($value)
        );
    }
}
