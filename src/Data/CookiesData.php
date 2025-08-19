<?php

declare(strict_types=1);

namespace DemonDextralHorn\Data;

use Spatie\LaravelData\Data;
use Illuminate\Support\Arr;

final class CookiesData extends Data
{
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

        return new static(
            laravelSession: $laravelSession,
        );
    }
}