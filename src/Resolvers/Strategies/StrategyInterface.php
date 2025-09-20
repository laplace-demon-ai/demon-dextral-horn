<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers\Strategies;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;

/**
 * Interface for strategy classes.
 *
 * @interface StrategyInterface
 */
interface StrategyInterface
{
    /**
     * Handle the strategy logic and return the relevant response.
     *
     * @param RequestData|null $requestData
     * @param ResponseData|null $responseData
     * @param array|null $options
     * @param mixed $value
     *
     * @return mixed
     */
    public function handle(
        ?RequestData $requestData = null,
        ?ResponseData $responseData = null,
        ?array $options = [],
        mixed $value = null
    ): mixed;
}
