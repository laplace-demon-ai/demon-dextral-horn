<?php

declare(strict_types=1);

namespace DemonDextralHorn\Middleware;

use Closure;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Enums\PrefetchType;
use DemonDextralHorn\Jobs\DemonDextralHornJob;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to handle prefetching of related data.
 *
 * @class DemonDextralHornMiddleware
 */
final readonly class DemonDextralHornMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     * (Handle prefetching)
     *
     * @param Request $request
     * @param Response $response
     */
    public function terminate(Request $request, Response $response): void
    {
        // Only prefetch for successful responses
        if ($response->isSuccessful()) {
            $requestData = RequestData::fromRequest($request);
            $responseData = ResponseData::fromResponse($response);

            // Get the queue name from the config
            $queueName = config('demon-dextral-horn.defaults.queue_name');
            $queueConnection = config('demon-dextral-horn.defaults.queue_connection');

            // Checks if the request is a prefetch call (triggered automatically by prefetching logic) so that we prevent circular/cascading prefetching
            if ($requestData->headers->demonPrefetchCall !== PrefetchType::AUTO->value) {
                // Dispatch the job to the specified queue
                DemonDextralHornJob::dispatch($requestData, $responseData)
                    ->onConnection($queueConnection)
                    ->onQueue($queueName);
            }
        }
    }
}
