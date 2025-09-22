<?php

declare(strict_types=1);

namespace DemonDextralHorn\Facades;

use Illuminate\Support\Facades\Facade;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;

/**
 * Facade for the DemonDextralHorn.
 *
 * @method static void handle(array $routes, RequestData $requestData, ResponseData $responseData)
 *
 * @class DemonDextralHorn
 */
final class DemonDextralHorn extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'demon-dextral-horn';
    }
}
