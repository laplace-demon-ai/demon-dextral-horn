<?php

declare(strict_types=1);

namespace Tests\Factories;

use DemonDextralHorn\Data\RequestData;

/**
 * This is a sample strategy class without implementing the StrategyInterface.
 *
 * @class StrategySampleWithoutInterface
 */
final class StrategySampleWithoutInterface
{
    public function handle(?RequestData $requestData, ?array $options = []): string
    {
        return 'sample_response_without_interface';
    }
}