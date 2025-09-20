<?php

declare(strict_types=1);

namespace Tests\Factories;

use DemonDextralHorn\Resolvers\Strategies\StrategyInterface;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;

/**
 * This is a sample strategy class for testing purposes.
 *
 * @class StrategySample
 */
final class StrategySample implements StrategyInterface
{
    /**
     * {@inheritDoc}
     */
    public function handle(
        ?RequestData $requestData = null,
        ?ResponseData $responseData = null,
        ?array $options = [],
        mixed $value = null
    ): string {
        return 'sample_response';
    }
}