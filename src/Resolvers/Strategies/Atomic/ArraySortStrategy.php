<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers\Strategies\Atomic;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Enums\OrderType;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;
use DemonDextralHorn\Resolvers\Strategies\AbstractStrategy;
use Illuminate\Support\Arr;

/**
 * [Atomic]: Sort an array in ascending or descending order.
 * Return the value as is if it's not an array.
 *
 * @class ArraySortStrategy
 */
final readonly class ArraySortStrategy extends AbstractStrategy
{
    /**
     * {@inheritDoc}
     */
    public function handle(
        ?RequestData $requestData = null,
        ?ResponseData $responseData = null,
        ?array $options = [],
        mixed $value = null
    ): mixed {
        $order = strtolower((string) Arr::get($options, 'order', ''));

        // if it's not an array or order is empty, do nothing
        if (! is_array($value) || $order === '') {
            return $value;
        }

        if (! in_array($order, [OrderType::ASC->value, OrderType::DESC->value], true)) {
            throw new MissingStrategyOptionException(self::class, 'order:asc|desc');
        }

        return $this->sortValue($value, $order);
    }

    /**
     * Sort the content array based on the specified position and order.
     *
     * @param array $value
     * @param string $order
     *
     * @return array
     */
    private function sortValue(array $value, string $order): array
    {
        usort($value, function ($firstItem, $secondItem) use ($order) {
            // Handle null values by placing them at the end
            if ($firstItem === null && $secondItem === null) {
                return 0;
            }
            if ($firstItem === null) {
                return 1;
            }
            if ($secondItem === null) {
                return -1;
            }

            // Compare values with spaceship operator
            $comparison = $firstItem <=> $secondItem;

            return $order === OrderType::DESC->value
                ? -($comparison)
                : $comparison;
        });

        return $value;
    }
}
