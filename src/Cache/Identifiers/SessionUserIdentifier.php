<?php

declare(strict_types=1);

namespace DemonDextralHorn\Cache\Identifiers;

use DemonDextralHorn\Cache\Identifiers\Contracts\UserIdentifierInterface;
use DemonDextralHorn\Data\TargetRouteData;
use DemonDextralHorn\Enums\AuthDriverType;
use DemonDextralHorn\Traits\RequestParsingTrait;
use Illuminate\Support\Arr;

/**
 * User identifier based on session cookies.
 * 
 * @class SessionUserIdentifier
 */
final class SessionUserIdentifier implements UserIdentifierInterface
{
    use RequestParsingTrait;

    /**
     * {@inheritDoc}
     */
    public function getIdentifierFor(TargetRouteData $targetRouteData): string
    {
        $cookies = $targetRouteData->cookies;

        $rawSessionCookie = Arr::get($cookies, 'session_cookie'); // e.g. 'laravel_session=cookie_value; Path=/; HttpOnly; SameSite=Lax'

        $parsedSessionCookie = $this->extractSessionCookieFromString($rawSessionCookie);

        // If no session cookie is found, return the guest user identifier.
        if (! $parsedSessionCookie) {
            return self::GUEST_USER;
        }

        $cookieName = Arr::get($parsedSessionCookie, 'name');
        $cookieValue = Arr::get($parsedSessionCookie, 'value');

        // Hash the cookie name and value to create a unique identifier (it also prevents leaking sensitive info in logs etc even though we do not use it directly).
        $cookieHash = hash(self::HASH_ALGORITHM, $cookieName . '=' . $cookieValue);

        return AuthDriverType::SESSION->value . ':' . $cookieHash;
    }
}