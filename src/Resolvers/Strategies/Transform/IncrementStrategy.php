<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers\Strategies\Transform;

use DemonDextralHorn\Resolvers\Contracts\StrategyInterface;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;
use Illuminate\Support\Arr;

/**
 * Increment strategy for transforming data, it increments the value of a specified key by a given amount.
 *
 * @class IncrementStrategy
 */
final class IncrementStrategy implements StrategyInterface
{
    /**
     * @inheritDoc
     */
    public function handle(?RequestData $requestData, ?array $options = []): int
    {
        $key = Arr::get($options, 'key');
        $increment = (int) Arr::get($options, 'increment', 1);

        // Throw an exception if the required "key" option is missing.
        if ($key === null) {
            throw new MissingStrategyOptionException('IncrementStrategy', 'key');
        }

        // Get the current value from the request data, setting a default 1 if not present (e.g. query parameter 'page' for the pagination)
        $currentValue = (int) Arr::get($requestData?->queryParams ?? [], $key, 1);

        // Increment the current value.
        return $currentValue + $increment;
    }
}