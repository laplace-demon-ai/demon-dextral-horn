<?php

declare(strict_types=1);

namespace DemonDextralHorn\Cache\Identifiers;

use DemonDextralHorn\Cache\Identifiers\Contracts\UserIdentifierInterface;
use DemonDextralHorn\Data\TargetRouteData;
use DemonDextralHorn\Enums\AuthDriverType;
use DemonDextralHorn\Enums\HttpHeaderType;
use Illuminate\Support\Arr;

/**
 * User identifier based on JWT token.
 * 
 * @class JwtUserIdentifier
 */
final class JwtUserIdentifier implements UserIdentifierInterface
{
    /**
     * {@inheritDoc}
     */
    public function getIdentifierFor(TargetRouteData $targetRouteData): string
    {
        $headers = $targetRouteData->headers;

        $token = Arr::get($headers, HttpHeaderType::AUTHORIZATION->value);

        // If no token is found, return the guest user identifier.
        if(! $token) {
            return self::GUEST_USER;
        }

        // Hash the token to avoid storing sensitive information directly (it also prevents leaking token in logs etc even though we do not use it directly).
        $tokenHash = hash(self::HASH_ALGORITHM, $token);

        return AuthDriverType::JWT->value . ':' . $tokenHash;
    }
}