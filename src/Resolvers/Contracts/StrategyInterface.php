<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers\Contracts;

use DemonDextralHorn\Data\RequestData;

/**
 * Interface for strategy classes.
 *
 * @interface StrategyInterface
 */
interface StrategyInterface
{
    /**
     * Handle the strategy logic and return the relevant response.
     *
     * @param RequestData|null $requestData
     * @param array|null $options
     */
    public function handle(?RequestData $requestData, ?array $options = []): mixed;
}