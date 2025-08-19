<?php

declare(strict_types=1);

namespace DemonDextralHorn\Data;

use Spatie\LaravelData\Data;
use Illuminate\Support\Arr;
use DemonDextralHorn\Enums\PrefetchType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Symfony\Component\HttpFoundation\Cookie;

final class HeadersData extends Data
{
    public function __construct(
        public ?string $authorization = null,
        public ?string $accept = null,
        public ?string $acceptLanguage = null,
        #[Enum(PrefetchType::class)]
        public ?string $demonPrefetchCall = null,
        public ?string $laravelSessionCookie = null,
    ) {}

    /**
     * Create a new instance from an array of headers.
     *
     * @param array $headers
     * @param array|null $cookies
     *
     * @return static
     */
    public static function fromHeaders(array $headers, ?array $cookies = []): static
    {
        // Custom prefetch header.
        $prefetchHeaderName = strtolower(config('demon-dextral-horn.defaults.prefetch_header'));

        // Get the prefetch header value; default to PrefetchType::NONE if it's not set (coming from the client)
        $prefetchHeaderValue = Arr::get($headers, "{$prefetchHeaderName}.0", PrefetchType::NONE->value);

        // Other headers retrieved from the headers
        $authorization = Arr::get($headers, 'authorization.0');
        $accept = Arr::get($headers, 'accept.0');
        $acceptLanguage = Arr::get($headers, 'accept-language.0');

        // here get the laravel session
        $laravelSession = null;

        // If cookies are provided, check for the laravel session cookie.
        if (! empty($cookies)) {
            foreach ($cookies as $cookie) {
                if ($cookie instanceof Cookie && strcasecmp($cookie->getName(), 'laravel_session') === 0) {
                    $laravelSession = $cookie->getValue();
                    
                    break;
                }
            }
        }

        return new static(
            authorization: $authorization,
            accept: $accept,
            acceptLanguage: $acceptLanguage,
            demonPrefetchCall: $prefetchHeaderValue,
            laravelSessionCookie: $laravelSession,
        );
    }
}