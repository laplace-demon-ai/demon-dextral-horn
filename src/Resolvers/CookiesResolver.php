<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Factories\StrategyFactory;
use DemonDextralHorn\Traits\RequestParsingTrait;
use Illuminate\Support\Arr;

/**
 * Resolve the cookies.
 *
 * @class CookiesResolver
 */
final readonly class CookiesResolver extends AbstractResolver
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

        return $this->prepareCookies($requestData, overrides: $resolvedCookies);
    }
}
