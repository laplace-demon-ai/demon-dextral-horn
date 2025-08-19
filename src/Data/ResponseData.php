<?php

declare(strict_types=1);

namespace DemonDextralHorn\Data;

use Spatie\LaravelData\Data;
use Symfony\Component\HttpFoundation\Response;

final class ResponseData extends Data
{
    public function __construct(
        public int $status,
        public ?HeadersData $headers = null,
        public string|bool|null $content = null,
    ) {}

    /**
     * Create a new instance from a ResponseData object.
     *
     * @param Response $response
     * 
     * @return static
     */
    public static function fromResponse(Response $response): static
    {
        return new static(
            status: $response->getStatusCode(),
            headers: HeadersData::fromHeaders(
                $response->headers->all(),
                $response->headers->getCookies()
            ),
            content: $response->getContent(),
        );
    }
}