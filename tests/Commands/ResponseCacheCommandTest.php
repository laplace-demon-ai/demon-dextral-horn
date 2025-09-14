<?php

declare(strict_types=1);

namespace Tests\Commands;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use DemonDextralHorn\Commands\ResponseCacheCommand;
use DemonDextralHorn\Facades\ResponseCache;

#[CoversClass(ResponseCacheCommand::class)]
final class ResponseCacheCommandTest extends TestCase
{
    #[Test]
    public function it_fails_when_no_tags_and_no_all_option(): void
    {
        /* EXECUTE */
        $this->artisan(ResponseCacheCommand::class)
            ->expectsOutput('You must provide at least one tag (--tags=tag1) or use --all to clear all cache.')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_clears_cache_with_specific_tags(): void
    {
        /* SETUP */
        $firstTag = 'tag1';
        $secondTag = 'tag2';
        ResponseCache::shouldReceive('clear')
            ->once()
            ->with([$firstTag, $secondTag])
            ->andReturn(true);

        /* EXECUTE */
        $this->artisan(ResponseCacheCommand::class, ['--tags' => [$firstTag, $secondTag]])
            ->expectsOutput('Clearing response cache for tags: ' . $firstTag . ', ' . $secondTag)
            ->expectsOutput('Cache cleared successfully.')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_clears_all_cache_when_all_option_is_used(): void
    {
        /* SETUP */
        ResponseCache::shouldReceive('clear')
            ->once()
            ->with([])
            ->andReturn(true);

        /* EXECUTE */
        $this->artisan(ResponseCacheCommand::class, ['--all' => true])
            ->expectsOutput('Clearing response cache for tags: '. config('demon-dextral-horn.defaults.prefetch_prefix'))
            ->expectsOutput('Cache cleared successfully.')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_fails_when_cache_clearing_fails(): void
    {
        /* SETUP */
        $tag = 'sample-tag';
        ResponseCache::shouldReceive('clear')
            ->once()
            ->with([$tag])
            ->andReturn(false);

        /* EXECUTE */
        $this->artisan(ResponseCacheCommand::class, ['--tags' => [$tag]])
            ->expectsOutput('Clearing response cache for tags: '.$tag)
            ->expectsOutput('Cache not cleared. Driver may not support tags.')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_handles_single_tag(): void
    {
        /* SETUP */
        $tag = 'sample-tag';
        ResponseCache::shouldReceive('clear')
            ->once()
            ->with([$tag])
            ->andReturn(true);

        /* EXECUTE */
        $this->artisan(ResponseCacheCommand::class, ['--tags' => [$tag]])
            ->expectsOutput('Clearing response cache for tags: '.$tag)
            ->expectsOutput('Cache cleared successfully.')
            ->assertExitCode(0);
    }
}
