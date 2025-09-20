<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers\Strategies\Atomic;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;
use DemonDextralHorn\Resolvers\Strategies\AbstractStrategy;
use Illuminate\Support\Arr;

/**
 * [Atomic]: Fetches a value from the request query parameters using a specified source_key.
 *
 * @class FromRequestQueryStrategy
 */
final readonly class FromRequestQueryStrategy extends AbstractStrategy
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
        $sourceKey = Arr::get($options, 'source_key');

        // Validate presence of source_key in options, because we cannot fetch something without knowing what to fetch.
        if ($sourceKey === null) {
            throw new MissingStrategyOptionException(self::class, 'source_key');
        }

        return Arr::get($requestData?->queryParams ?? [], $sourceKey);
    }
}
