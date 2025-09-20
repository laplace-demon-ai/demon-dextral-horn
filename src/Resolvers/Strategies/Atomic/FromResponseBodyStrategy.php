<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers\Strategies\Atomic;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;
use DemonDextralHorn\Resolvers\Strategies\AbstractStrategy;
use Illuminate\Support\Arr;

/**
 * [Atomic]: Fetches a value from the response body using a specified position.
 *
 * @class FromResponseBodyStrategy
 */
final readonly class FromResponseBodyStrategy extends AbstractStrategy
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
        $position = Arr::get($options, 'position');

        if ($position === null) {
            throw new MissingStrategyOptionException(self::class, 'position');
        }

        $data = json_decode($responseData?->content, true);

        // Use data_get to retrieve the value at the specified position, supporting dot notation and wildcards  (e.g., 'data.id' or 'data.*.id')
        return data_get($data, $position);
    }
}
