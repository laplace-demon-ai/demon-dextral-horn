<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers\Strategies\Atomic;

use DemonDextralHorn\Resolvers\Strategies\AbstractStrategy;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;
use Illuminate\Support\Arr;

/**
 * [Atomic]: Strategy to extract a value from the response header using a specified header_name.
 * 
 * @class FromResponseHeaderStrategy
 */
final readonly class FromResponseHeaderStrategy extends AbstractStrategy
{
    /**
     * {@inheritdoc}
     */
    public function handle(
        ?RequestData $requestData = null,
        ?ResponseData $responseData = null,
        ?array $options = [],
        mixed $value = null
    ): mixed {
        $headerName = Arr::get($options, 'header_name'); // The name of the header to extract e.g. setCookie, authorization, etc.

        if ($headerName === null) {
            throw new MissingStrategyOptionException(self::class, 'header_name');
        }

        $headersData = $responseData?->headers;

        // Serialize (convert to array) the HeadersData object to access its properties dynamically
        $headersArray = $headersData ? $headersData->__serialize() : [];

        return Arr::get($headersArray, $headerName);
    }
}