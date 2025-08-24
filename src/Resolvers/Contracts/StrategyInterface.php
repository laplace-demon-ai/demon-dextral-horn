<?php

declare(strict_types=1);

namespace DemonDextralHorn\Resolvers\Contracts;

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
     *
     * @return mixed
     */
    public function handle(
        ?RequestData $requestData,
        ?ResponseData $responseData,
        ?array $options = []
    ): mixed;
}
