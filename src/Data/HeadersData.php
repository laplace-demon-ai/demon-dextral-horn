<?php

declare(strict_types=1);

namespace DemonDextralHorn\Data;

use DemonDextralHorn\Enums\PrefetchType;
use Illuminate\Support\Arr;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Data;

/**
 * DTO representing HTTP headers data.
 *
 * @class HeadersData
 */
final class HeadersData extends Data
{
    /**
     * Create a new HeadersData object.
     *
     * @param string|null $authorization
     * @param string|null $accept
     * @param string|null $acceptLanguage
     * @param string|null $prefetchHeader
     * @param array|null $setCookie
     */
    public function __construct(
        public ?string $authorization = null,
        public ?string $accept = null,
        public ?string $acceptLanguage = null,
        #[Enum(PrefetchType::class)]
        public ?string $prefetchHeader = null,
        public ?array $setCookie = null,
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

        // Get the Set-Cookie header from the headers (There can be multiple Set-Cookie headers)
        $setCookie = Arr::get($headers, 'set-cookie');

        return new self(
            authorization: $authorization,
            accept: $accept,
            acceptLanguage: $acceptLanguage,
            prefetchHeader: $prefetchHeaderValue,
            setCookie: $setCookie,
        );
    }

    /**
     * Serialize the current object to an array.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return [
            'authorization' => $this->authorization,
            'accept' => $this->accept,
            'acceptLanguage' => $this->acceptLanguage,
            'prefetchHeader' => $this->prefetchHeader,
            'setCookie' => $this->setCookie,
        ];
    }

    /**
     * Unserialize the data into the current object.
     *
     * @param array $data
     *
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->authorization = Arr::get($data, 'authorization');
        $this->accept = Arr::get($data, 'accept');
        $this->acceptLanguage = Arr::get($data, 'acceptLanguage');
        $this->prefetchHeader = Arr::get($data, 'prefetchHeader');
        $this->setCookie = Arr::get($data, 'setCookie');
    }
}
