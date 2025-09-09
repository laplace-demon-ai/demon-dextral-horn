<?php

declare(strict_types=1);

namespace DemonDextralHorn\Events;

use DemonDextralHorn\Data\TargetRouteData;
use Symfony\Component\HttpFoundation\Response;

/**
 * Event triggered when a response is successfully cached (Hit the cache, might be useful for logging or analytics).
 *
 * @class ResponseCachedEvent
 */
final readonly class ResponseCachedEvent
{
    /**
     * Create a new ResponseCachedEvent instance.
     *
     * @param Response $response
     * @param TargetRouteData $targetRouteData
     */
    public function __construct(
        public Response $response,
        public TargetRouteData $targetRouteData
    ) {}
}
