<?php

declare(strict_types=1);

namespace DemonDextralHorn\Traits;

/**
 * Trait for parsing requests and extracting relevant information.
 * 
 * @trait RequestParsingTrait
 */
trait RequestParsingTrait
{
    /**
     * Extract the session cookie from the raw cookie string.
     * e.g. 'laravel_session=cookie_value; Path=/; HttpOnly; SameSite=Lax', it will return ['name' => 'laravel_session', 'value' => 'cookie_value']
     *
     * @param string|null $rawCookieString
     *
     * @return array|null
     */
    protected function extractSessionCookieFromString(?string $rawCookieString): ?array
    {
        $cookieName = config('demon-dextral-horn.defaults.session_cookie_name');

        // Cookie header may contain many parts separated by ';'
        foreach (explode(';', $rawCookieString ?? '') as $part) {
            $part = trim($part);

            if ($part === '' || ! str_contains($part, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $part, 2);

            if ($name === $cookieName) {
                // decode if urlencoded (Laravel session can be encoded)
                return ['name' => $name, 'value' => rawurldecode((string) $value)];
            }
        }

        return null;
    }
}