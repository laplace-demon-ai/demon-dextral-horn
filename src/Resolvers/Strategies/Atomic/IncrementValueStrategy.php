<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers\Strategies\Atomic;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Resolvers\Strategies\AbstractStrategy;
use Illuminate\Support\Arr;

/**
 * [Atomic]: Strategy to increment a numeric value by a specified amount or by a default value if none is provided.
 *
 * @class IncrementValueStrategy
 */
final readonly class IncrementValueStrategy extends AbstractStrategy
{
    /**
     * {@inheritdoc}
     */
    public function handle(
        ?RequestData $requestData = null,
        ?ResponseData $responseData = null,
        ?array $options = [],
        mixed $value = null
    ): mixed {
        /*
         * When we have a value, we increment that value by the specified amount.
         * If no value is provided (i.e., null), we use a default value (which can also be specified in options) before incrementing.
         * e.g. page query param not provided, so value is null coming from previous strategy, so we use default value 1, and increment by 1, resulting in 2.
         *
         * Options:
         * - increment: int (default: 1) - The amount to increment by.
         * - default: int (default: 1) - The default value to use if value is null.
         */
        $incrementBy = (int) Arr::get($options, 'increment', 1);
        $defaultValue = (int) Arr::get($options, 'default', 1);

        return (int) ($value ?? $defaultValue) + $incrementBy;
    }
}
