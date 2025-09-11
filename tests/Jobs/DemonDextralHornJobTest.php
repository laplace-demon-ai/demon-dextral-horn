<?php

declare(strict_types=1);

namespace Tests\Jobs;

use DemonDextralHorn\Jobs\DemonDextralHornJob;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Illuminate\Support\Facades\Queue;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Data\HeadersData;
use DemonDextralHorn\Enums\PrefetchType;
use DemonDextralHorn\Resolvers\Contracts\TargetRouteResolverInterface;
use DemonDextralHorn\Events\DemonDextralHornJobFailedEvent;
use Tests\TestCase;
use Exception;
use Illuminate\Support\Facades\Event;

#[CoversClass(DemonDextralHornJob::class)]
final class DemonDextralHornJobTest extends TestCase
{
    private DemonDextralHornJob $job;
    private RequestData $requestData;
    private ResponseData $responseData;
    private TargetRouteResolverInterface $targetRouteResolver;

    public function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->requestData = new RequestData(
            uri: '/prefetch-route-success/routeValue',
            method: Request::METHOD_GET,
            headers: new HeadersData(
                authorization: 'Bearer some-token',
                accept: 'application/json',
                acceptLanguage: 'en-US',
                prefetchHeader: PrefetchType::NONE->value,
            ),
            routeName: 'sample.trigger.route.name',
            routeParams: ['routeParam' => 'routeValue'],
            queryParams: ['queryParam1' => 'value1', 'queryParam2' => 'value2'],
        );
        $this->responseData = new ResponseData(
            status: Response::HTTP_OK,
            content: 'ok',
        );
        $this->job = $this->app->make(DemonDextralHornJob::class, [
            'requestData' => $this->requestData,
            'responseData' => $this->responseData,
        ]);
        $this->targetRouteResolver = $this->app->make(TargetRouteResolverInterface::class);
    }

    #[Test]
    public function it_calls_failed_method_when_job_fails(): void
    {
        /* SETUP */
        Event::fake();
        $exception = new Exception('Test failure');
    
        /* EXECUTE */
        $this->job->failed($exception);

        /* ASSERT */
        Event::assertDispatched(DemonDextralHornJobFailedEvent::class, function ($event) {
            return $event->requestData === $this->requestData
                && $event->responseData === $this->responseData;
        });
    }
}