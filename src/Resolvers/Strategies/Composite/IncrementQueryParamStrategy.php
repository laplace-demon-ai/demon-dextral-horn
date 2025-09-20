<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers\Strategies\Composite;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Resolvers\Strategies\AbstractStrategy;
use DemonDextralHorn\Resolvers\Strategies\Atomic\FromRequestQueryStrategy;
use DemonDextralHorn\Resolvers\Strategies\Atomic\IncrementValueStrategy;

/**
 * [Composite]: Strategy that increments a value from the request query parameters.
 *
 * @class IncrementQueryParamStrategy
 */
final readonly class IncrementQueryParamStrategy extends AbstractStrategy
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
        $chain = [
            [
                'strategy' => FromRequestQueryStrategy::class,
                'options' => $options, // expects 'source_key' in options - We pass all options because each strategy handles its own validation.
            ],
            [
                'strategy' => IncrementValueStrategy::class,
                'options' => $options, // expects 'increment' in options, optional 'default' in options - We pass all options because each strategy handles its own validation.
            ],
        ];

        return $this->chainExecutor($chain, $requestData, $responseData);
    }
}
