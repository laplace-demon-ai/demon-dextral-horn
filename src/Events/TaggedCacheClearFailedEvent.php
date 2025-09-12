<?php

declare(strict_types=1);

namespace DemonDextralHorn\Events;

/**
 * Event triggered when the clear of a cache fails
 * (Cache driver may not support tags, or no tags are associated to clearing cache).
 *
 * @class TaggedCacheClearFailedEvent
 */
final readonly class TaggedCacheClearFailedEvent
{
    /**
     * Create a new TaggedCacheClearFailedEvent instance.
     *
     * @param array|null $tags
     */
    public function __construct(
        public ?array $tags = null
    ) {}
}