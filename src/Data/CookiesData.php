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
     * @param string|null $laravelSession
     */
    public function __construct(
        public ?string $laravelSession = null,
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
        $laravelSession = Arr::get($cookies, 'laravel_session');

        return new self(
            laravelSession: $laravelSession,
        );
    }
}
