<?php

declare(strict_types=1);

namespace DemonDextralHorn\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for the ResponseCache.
 *
 * @method static void put(Response $response, TargetRouteData $targetRouteData)
 *
 * @class ResponseCache
 */
final class ResponseCache extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'response-cache';
    }
}
