<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers\Strategies\Atomic;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;
use DemonDextralHorn\Resolvers\Strategies\AbstractStrategy;
use Illuminate\Support\Arr;

/**
 * [Atomic]: Strategy to limit the number of elements in an array.
 *
 * @class ArrayLimitStrategy
 */
final readonly class ArrayLimitStrategy extends AbstractStrategy
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
        $limitRaw = Arr::get($options, 'limit', null);
        $limit = $limitRaw === null ? null : (int) $limitRaw;

        // if it's not an array, do nothing
        if (! is_array($value)) {
            return $value;
        }

        if ($limit === null || $limit <= 0) {
            throw new MissingStrategyOptionException(self::class, 'limit: positive integer');
        }

        // return the limited array by slicing it with the given limit
        return array_slice($value, 0, $limit);
    }
}
