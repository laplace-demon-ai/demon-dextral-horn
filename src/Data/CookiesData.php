<?php

declare(strict_types=1);

namespace DemonDextralHorn\Data;

use Illuminate\Support\Arr;
use Spatie\LaravelData\Data;

/**
 * DTO representing HTTP cookies data.
 *
 * @class CookiesData
 */
final class CookiesData extends Data
{
    /**
     * Create a new CookiesData object.
     *
     * @param string|null $sessionCookie
     */
    public function __construct(
        public ?string $sessionCookie = null,
    ) {}

    /**
     * Create a new instance from an array of cookies.
     *
     * @param array $cookies
     *
     * @return static
     */
    public static function fromCookies(array $cookies): static
    {
        $sessionCookie = Arr::get($cookies, config('demon-dextral-horn.defaults.session_cookie_name'));

        return new self(
            sessionCookie: $sessionCookie,
        );
    }
}
