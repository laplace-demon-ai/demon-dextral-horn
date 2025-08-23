<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Enums\PrefetchType;

/**
 * Resolves headers for the target route.
 *
 * @class HeadersResolver
 */
final class HeadersResolver extends AbstractResolver
{
    /**
     * {@inheritDoc}
     */
    public function resolve(
        ?array $route = null,
        ?RequestData $requestData = null,
        ?ResponseData $responseData = null
    ): array {

        // todo as mentioned, we might be getting some values from other places rather than request header, e.g. auth related login for jwt (access_token) will be retrieved from response payload and set to header,, so probably method will accept response too. think about other cases too.

        $prefetchHeaderName = config('demon-dextral-horn.defaults.prefetch_header'); // Demon-Prefetch-Call
        $prefetchHeaderValue = PrefetchType::AUTO->value;
        $prefetchHeader = [
            $prefetchHeaderName => $prefetchHeaderValue,
        ];

        $headersData = $requestData->headers;

        // Map the headers to the correct format
        $mappedHeaders = [
            'Authorization' => $headersData->authorization,
            'Accept' => $headersData->accept,
            'Accept-Language' => $headersData->acceptLanguage,
        ];

        // Merge prefetchHeader and filter null values from the headers
        return array_filter(
            array_merge($mappedHeaders, $prefetchHeader),
            fn ($value) => ! is_null($value)
        );
    }
}
