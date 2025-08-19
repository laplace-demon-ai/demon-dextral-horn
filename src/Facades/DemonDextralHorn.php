<?php

declare(strict_types=1);

namespace DemonDextralHorn\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for the DemonDextralHorn.
 * 
 * @method static void handle(array $targetRoutes, RequestData $requestData, ResponseData $responseData)
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