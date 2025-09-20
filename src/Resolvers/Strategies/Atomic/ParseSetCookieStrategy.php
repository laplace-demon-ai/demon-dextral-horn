<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers\Strategies\Atomic;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;
use DemonDextralHorn\Resolvers\Strategies\AbstractStrategy;
use Illuminate\Support\Arr;

/**
 * [Atomic]: Strategy to retrieve a specific cookie (session cookie) from the Set-Cookie headers (provided with $value) in an HTTP response.
 *
 * @class ParseSetCookieStrategy
 */
final readonly class ParseSetCookieStrategy extends AbstractStrategy
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
        $sourceKey = Arr::get($options, 'source_key'); // The key for the session cookie e.g. laravel_session

        if ($sourceKey === null) {
            throw new MissingStrategyOptionException(self::class, 'source_key');
        }

        $sessionCookie = null;

        foreach ($value ?? [] as $cookie) {
            // Check if the cookie matches the session cookie name (laravel_session)
            if (str_contains($cookie, $sourceKey)) {
                $sessionCookie = $cookie;

                break;
            }
        }

        return $sessionCookie;
    }
}
