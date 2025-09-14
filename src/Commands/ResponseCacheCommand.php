<?php

declare(strict_types=1);

namespace DemonDextralHorn\Commands;

use Illuminate\Console\Command;
use DemonDextralHorn\Facades\ResponseCache;

/**
 * Artisan command to clear the response cache by tags or all cache.
 *
 * @class ResponseCacheCommand
 */
final class ResponseCacheCommand extends Command
{
    protected $signature = 'demon-dextral-horn:response-cache
        {--tags=* : Tags to clear}
        {--all : Clear all response cache (default tag will be added automatically)}';

    protected $description = 'Clear the response cache by tags or all response cache.';

    /**
     * Execute the console command.
     * 
     * @return int
     */
    public function handle(): int
    {
        $tags = $this->option('tags');
        $all = $this->option('all');

        // Check if at least one tag is provided or --all is used
        if (! $all && empty($tags)) {
            $this->warn('You must provide at least one tag (--tags=tag1) or use --all to clear all cache.');

            return self::FAILURE;
        }

        $defaultTag = config('demon-dextral-horn.defaults.prefetch_prefix');

        $tagsToClear = $all ? [] : $tags;

        $this->info(
            'Clearing response cache for tags: ' . ($all ? $defaultTag : implode(', ', $tagsToClear))
        );

        // Attempt to clear the cache
        if (! ResponseCache::clear($tagsToClear)) {
            $this->warn('Cache not cleared. Driver may not support tags.');

            return self::FAILURE;
        }

        $this->info('Cache cleared successfully.');

        return self::SUCCESS;
    }
}