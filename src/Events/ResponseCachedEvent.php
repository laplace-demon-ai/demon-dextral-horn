<?php

declare(strict_types=1);

namespace DemonDextralHorn\Events;

use Symfony\Component\HttpFoundation\Response;
use DemonDextralHorn\Data\TargetRouteData;

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
