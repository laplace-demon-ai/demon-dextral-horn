<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers\Strategies\Source;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;
use DemonDextralHorn\Resolvers\Contracts\StrategyInterface;
use Illuminate\Support\Arr;

/**
 * This strategy extracts the JWT token from the response access token.
 *
 * @class ResponseJwtStrategy
 */
final class ResponseJwtStrategy implements StrategyInterface
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
        $position = Arr::get($options, 'position');

        if ($key === null || $position === null) {
            throw new MissingStrategyOptionException(self::class, 'key/position');
        }

        // Get the JWT token from the response data provided position
        $token = Arr::get(json_decode($responseData?->content, true), $position);

        // Return the token as a Bearer token if it exists, otherwise return null so that request header token will be forwarded.
        return $token ? 'Bearer ' . $token : null;
    }
}
