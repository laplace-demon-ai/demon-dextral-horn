<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers\Strategies\Source;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;
use DemonDextralHorn\Resolvers\Contracts\StrategyInterface;
use Illuminate\Support\Arr;

/**
 * This strategy extracts the value of a specified key from the response data.
 *
 * @class ResponseValueStrategy
 */
final class ResponseValueStrategy implements StrategyInterface
{
    /**
     * {@inheritDoc}
     */
    public function handle(
        ?RequestData $requestData,
        ?ResponseData $responseData,
        ?array $options = []
    ): mixed {
        $position = Arr::get($options, 'position'); // e.g. data.id

        if ($position === null) {
            throw new MissingStrategyOptionException(self::class, 'position');
        }

        return Arr::get(json_decode($responseData?->content, true), $position);
    }
}
