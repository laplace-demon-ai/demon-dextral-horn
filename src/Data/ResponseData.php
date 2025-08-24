<?php

declare(strict_types=1);

namespace DemonDextralHorn\Data;

use Spatie\LaravelData\Data;
use Symfony\Component\HttpFoundation\Response;

/**
 * DTO representing HTTP response data.
 *
 * @class ResponseData
 */
final class ResponseData extends Data
{
    /**
     * Create a new ResponseData object.
     *
     * @param int $status
     * @param HeadersData|null $headers
     * @param string|bool|null $content
     */
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
        return new self(
            status: $response->getStatusCode(),
            headers: HeadersData::fromHeaders(
                $response->headers->all(),
                $response->headers->getCookies()
            ),
            content: $response->getContent(),
        );
    }
}
