<?php

declare(strict_types=1);

namespace DemonDextralHorn\Enums;

/**
 * Enum representing the types of prefetching used.
 */
enum PrefetchType: string
{
    // Auto means prefetching triggered automatically
    case AUTO = 'auto';

    // None means no prefetching (request is coming from the client)
    case NONE = 'none';
}