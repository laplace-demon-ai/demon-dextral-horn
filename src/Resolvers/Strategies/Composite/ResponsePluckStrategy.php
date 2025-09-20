<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers\Strategies\Composite;

use DemonDextralHorn\Resolvers\Strategies\AbstractStrategy;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Resolvers\Strategies\Atomic\FromResponseBodyStrategy;
use DemonDextralHorn\Resolvers\Strategies\Atomic\ArraySortStrategy;
use DemonDextralHorn\Resolvers\Strategies\Atomic\ArrayLimitStrategy;
use DemonDextralHorn\Enums\OrderType;
use Illuminate\Support\Arr;

/**
 * [Composite]: Strategy that plucks data from the response body, with optional sorting and limiting.
 * 
 * @class ResponsePluckStrategy
 */
final readonly class ResponsePluckStrategy extends AbstractStrategy
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
        $limitRaw = Arr::get($options, 'limit', null);
        $limit = $limitRaw === null ? null : (int) $limitRaw;
        $order = strtolower((string) Arr::get($options, 'order', ''));

        $chain = [
            // Step 1: Extract array from response body
            [
                'strategy' => FromResponseBodyStrategy::class,
                'options' => $options
            ]
        ];

        // Step 2: Sort if order specified
        if (in_array($order, [OrderType::ASC->value, OrderType::DESC->value], true)) {
            $chain[] = [
                'strategy' => ArraySortStrategy::class,
                'options' => $options
            ];
        }

        // Step 3: Limit if specified
        if ($limit !== null && $limit > 0) {
            $chain[] = [
                'strategy' => ArrayLimitStrategy::class,
                'options' => $options
            ];
        }

        return $this->chainExecutor($chain, $requestData, $responseData);
    }
}