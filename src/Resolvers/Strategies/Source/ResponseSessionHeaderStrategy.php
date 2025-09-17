<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers\Strategies\Source;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;
use DemonDextralHorn\Resolvers\Contracts\StrategyInterface;
use Illuminate\Support\Arr;

/**
 * This strategy extracts session cookies from the response headers (from `Set-Cookie` for laravel session authentication).
 *
 * @class ResponseSessionHeaderStrategy
 */
final class ResponseSessionHeaderStrategy implements StrategyInterface
{
    /**
     * {@inheritDoc}
     */
    public function handle(
        ?RequestData $requestData,
        ?ResponseData $responseData,
        ?array $options = []
    ): mixed {
        $sourceKey = Arr::get($options, 'source_key'); // The key for the session cookie e.g. laravel_session

        if ($sourceKey === null) {
            throw new MissingStrategyOptionException(self::class, 'source_key');
        }

        $headersData = $responseData?->headers;

        $sessionCookie = null;

        foreach ($headersData?->setCookie ?? [] as $cookie) {
            // Check if the cookie matches the session cookie name (laravel_session)
            if (str_contains($cookie, $sourceKey)) {
                $sessionCookie = $cookie;

                break;
            }
        }

        return $sessionCookie;
    }
}
