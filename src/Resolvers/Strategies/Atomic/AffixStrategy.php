<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers\Strategies\Atomic;

use DemonDextralHorn\Resolvers\Strategies\AbstractStrategy;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use Illuminate\Support\Arr;

/**
 * [Atomic]: AffixStrategy adds a prefix and/or suffix to a given string value.
 *
 * @class AffixStrategy
 */
final readonly class AffixStrategy extends AbstractStrategy
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
        if (! is_string($value)) {
            return $value;
        }

        // Retrieve prefix and suffix from options, defaulting to empty strings if not provided.
        $prefix = Arr::get($options, 'prefix', '');
        $suffix = Arr::get($options, 'suffix', '');

        return $prefix . $value . $suffix;
    }
}