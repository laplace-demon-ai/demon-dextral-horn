<?php

declare(strict_types=1);

namespace DemonDextralHorn\Facades;

use Illuminate\Support\Facades\Facade;
use Symfony\Component\HttpFoundation\Response;
use DemonDextralHorn\Data\TargetRouteData;

/**
 * Facade for the ResponseCache.
 *
 * @method static void put(Response $response, TargetRouteData $targetRouteData)
 * @method static bool has(TargetRouteData $targetRouteData)
 * @method static Response|null get(TargetRouteData $targetRouteData)
 * @method static bool forget(TargetRouteData $targetRouteData)
 * @method static bool clear(?array $tags = [])
 * @method static Response|null pull(TargetRouteData $targetRouteData)
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
