<?php

declare(strict_types=1);

namespace DemonDextralHorn\Events;

use Symfony\Component\HttpFoundation\Response;

/**
 * Event triggered when a response is determined to be non-cacheable.
 *
 * @class NonCacheableResponseEvent
 */
final readonly class NonCacheableResponseEvent
{
    /**
     * Create a new NonCacheableResponseEvent instance.
     *
     * @param Response $response
     * @param array<string, bool> $validationResults
     */
    public function __construct(
        public Response $response,
        public array $validationResults
    ) {}
}