<?php

declare(strict_types=1);

namespace DemonDextralHorn\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Facades\DemonDextralHorn;
use DemonDextralHorn\Resolvers\Contracts\TargetRouteResolverInterface;

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
     * @return void
     */
    public function handle(TargetRouteResolverInterface $targetRouteResolver): void
    {
        // Resolve the target routes so we can prefetch them
        $targetRoutes = $targetRouteResolver->resolve(requestData: $this->requestData);

        // Handle the target routes
        DemonDextralHorn::handle($targetRoutes, $this->requestData, $this->responseData);
    }
}

/*
todo:

    public $tries = 1; => we can set it to 1 or 2 in case default is more than that, we dont want it so override it here to make sure it will not be retiried much
    - (?) Maybe right in the seconds when the endpoints resolved/decided/handled, save some info in the db or redis so that the the endpoints are called so they are pending,, so that when it is requested, this status will be checked before making the actual call
*/