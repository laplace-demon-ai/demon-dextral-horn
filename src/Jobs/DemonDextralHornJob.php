<?php

declare(strict_types=1);

namespace DemonDextralHorn\Jobs;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Events\DemonDextralHornJobFailedEvent;
use DemonDextralHorn\Facades\DemonDextralHorn;
use DemonDextralHorn\Resolvers\Contracts\TargetRouteResolverInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Queue job to handle prefetching.
 *
 * @class DemonDextralHornJob
 */
final class DemonDextralHornJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param RequestData $requestData
     * @param ResponseData $responseData
     */
    public function __construct(protected RequestData $requestData, protected ResponseData $responseData) {}

    /**
     * Execute the job.
     *
     * @param TargetRouteResolverInterface $targetRouteResolver
     *
     * @return void
     */
    public function handle(TargetRouteResolverInterface $targetRouteResolver): void
    {
        // Resolve the target routes so we can prefetch them
        $targetRoutes = $targetRouteResolver->resolve(requestData: $this->requestData);

        // Handle the target routes
        DemonDextralHorn::handle($targetRoutes, $this->requestData, $this->responseData);
    }

    /**
     * Get the tags for the job.
     *
     * @return array
     */
    public function tags(): array
    {
        $prefetchPrefix = config('demon-dextral-horn.defaults.prefetch_prefix');

        return [
            $prefetchPrefix,
            'attempt:' . $this->attempts(),
        ];
    }

    /**
     * Handle a job failure.
     *
     * @param Throwable $exception
     *
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        // Fire event for job failure which can be used for logging etc.
        event(new DemonDextralHornJobFailedEvent($this->requestData, $this->responseData));
    }
}
