<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers\Strategies\Source;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;
use DemonDextralHorn\Resolvers\Contracts\StrategyInterface;
use Illuminate\Support\Arr;

/**
 * This strategy forwards the value of a specified key without any transformation.
 *
 * @class ForwardValueStrategy
 */
final class ForwardValueStrategy implements StrategyInterface
{
    /**
     * {@inheritDoc}
     */
    public function handle(
        ?RequestData $requestData,
        ?ResponseData $responseData,
        ?array $options = []
    ): mixed {
        $key = Arr::get($options, 'key');

        // Throw an exception if the required "key" option is missing, or request does not have the key.
        if ($key === null || ! Arr::has($requestData?->queryParams ?? [], $key)) {
            throw new MissingStrategyOptionException(self::class, 'key');
        }

        // Return the current value as is.
        return Arr::get($requestData?->queryParams ?? [], $key);
    }
}
