<?php

declare(strict_types=1);

namespace DemonDextralHorn\Cache;

use DemonDextralHorn\Data\TargetRouteData;

/**
 * Generates cache tags based on route names and user identifiers.
 *
 * @class CacheTagGenerator
 */
final class CacheTagGenerator
{
    public const UNNAMED_TAG = 'unnamed';

    /**
     * Generate cache tags based on the provided target route data.
     *
     * @param TargetRouteData $targetRouteData
     *
     * @return array
     */
    public function generate(TargetRouteData $targetRouteData): array
    {
        $defaultTag = config('demon-dextral-horn.defaults.prefetch_prefix');

        $tags = [
            // Normalize and add route name as a tag (use default tag as prefix)
            $defaultTag . ':' . $this->normalizeTag($targetRouteData->routeName),
        ];

        // Remove duplicates and empty values, ensure default tag is always included
        $allTags = array_values(array_unique(array_filter(array_merge([$defaultTag], $tags))));

        return $allTags;
    }

    /**
     * Normalize a tag by converting to lowercase, replacing non-alphanumeric characters with underscores, and trimming.
     * If the tag is empty, return a default unnamed tag. e.g. "User.Profile" becomes "user_profile" etc.
     *
     * @param string $name
     *
     * @return string
     */
    public function normalizeTag(string $name): string
    {
        if ($name === '') {
            return self::UNNAMED_TAG;
        }

        // convert to lowercase for consistency
        $name = strtolower($name);

        // replace any non-alphanumeric characters with underscores
        $name = preg_replace('/[^a-z0-9]+/', '_', $name);

        // collapse repeated underscores
        $name = preg_replace('/_+/', '_', $name);

        // trim leading/trailing underscores
        $name = trim($name, '_');

        return $name;
    }
}
