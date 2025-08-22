<?php

declare(strict_types=1);

namespace Tests\Factories;

use DemonDextralHorn\Resolvers\Contracts\StrategyInterface;
use DemonDextralHorn\Data\RequestData;

/**
 * This is a sample strategy class for testing purposes.
 *
 * @class StrategySample
 */
final class StrategySample implements StrategyInterface
{
    /**
     * @inheritDoc
     */
    public function handle(?RequestData $requestData, ?array $options = []): string
    {
        return 'sample_response';
    }
}