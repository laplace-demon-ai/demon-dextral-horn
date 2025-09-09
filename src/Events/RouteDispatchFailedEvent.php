<?php

declare(strict_types=1);

namespace DemonDextralHorn\Events;

use DemonDextralHorn\Data\TargetRouteData;
use Throwable;

/**
 * Event triggered when route dispatching fails.
 *
 * @class RouteDispatchFailedEvent
 */
final readonly class RouteDispatchFailedEvent
{
    /**
     * Create a new RouteDispatchFailedEvent instance.
     *
     * @param Throwable $exception
     * @param TargetRouteData $targetRouteData
     */
    public function __construct(
        public Throwable $exception,
        public TargetRouteData $targetRouteData
    ) {}
}