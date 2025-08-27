<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers\Strategies\Source;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Enums\OrderType;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;
use DemonDextralHorn\Resolvers\Contracts\StrategyInterface;
use Illuminate\Support\Arr;

/**
 * This strategy plucks the multiple values of a specified key from the response data.
 *
 * @class ResponsePluckStrategy
 */
final class ResponsePluckStrategy implements StrategyInterface
{
    /**
     * {@inheritDoc}
     */
    public function handle(
        ?RequestData $requestData,
        ?ResponseData $responseData,
        ?array $options = []
    ): mixed {
        $position = Arr::get($options, 'position');
        $key = Arr::get($options, 'key'); // e.g. route_key
        $limitRaw = Arr::get($options, 'limit', null);
        $limit = $limitRaw === null ? null : (int) $limitRaw;
        $order = strtolower((string) Arr::get($options, 'order', ''));

        if ($key === null || $position === null) {
            throw new MissingStrategyOptionException(self::class, 'key/position');
        }

        // Normalize position by removing any wildcard characters for consistent access. e.g. 'data.*.id' will be 'id'
        $normalizedPosition = preg_replace('/^.*\*\./', '', $position);

        $content = Arr::get(json_decode($responseData->content, true), 'data', []);

        // Check if the order is set, and if so, apply sorting
        if (in_array($order, [OrderType::ASC->value, OrderType::DESC->value], true)) {
            $content = $this->sortContent($content, $normalizedPosition, $order);
        }

        // Apply limit if set
        if ($limit !== null && $limit > 0) {
            $content = array_slice($content, 0, $limit);
        }

        return Arr::pluck($content, $normalizedPosition);
    }

    /**
     * Sort the content array based on the specified position and order.
     *
     * @param array $content
     * @param string $position
     * @param string $order
     *
     * @return array<int, array<string, mixed>>
     */
    private function sortContent(array $content, string $position, string $order): array
    {
        usort($content, function (array $firstItem, array $secondItem) use ($position, $order) {
            $firstValue = Arr::get($firstItem, $position);
            $secondValue = Arr::get($secondItem, $position);

            // Handle null values by placing them at the end
            if ($firstValue === null && $secondValue === null) {
                return 0;
            }
            if ($firstValue === null) {
                return 1;
            }
            if ($secondValue === null) {
                return -1;
            }

            // Compare values with spaceship operator
            $comparison = $firstValue <=> $secondValue;

            return $order === OrderType::DESC->value
                ? -($comparison)
                : $comparison;
        });

        return $content;
    }
}
