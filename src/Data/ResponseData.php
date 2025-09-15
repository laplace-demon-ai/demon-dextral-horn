<?php

declare(strict_types=1);

namespace DemonDextralHorn\Data;

use Spatie\LaravelData\Data;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Arr;

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

    /**
     * Serialize the current object to an array.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return [
            'status' => $this->status,
            'headers' => $this->headers ? $this->headers->__serialize() : null,
            'content' => $this->content,
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
        $this->status = Arr::get($data, 'status');

        // Unserialize HeadersData
        $headersArray = Arr::get($data, 'headers');
        $this->headers = $headersArray
            ? new HeadersData(
                authorization: Arr::get($headersArray, 'authorization'),
                accept: Arr::get($headersArray, 'accept'),
                acceptLanguage: Arr::get($headersArray, 'acceptLanguage'),
                prefetchHeader: Arr::get($headersArray, 'prefetchHeader'),
                setCookie: Arr::get($headersArray, 'setCookie'),
            ) 
            : null;

        $this->content = Arr::get($data, 'content');
    }
}
