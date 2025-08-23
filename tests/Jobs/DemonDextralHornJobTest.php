<?php

declare(strict_types=1);

namespace Tests\Jobs;

use DemonDextralHorn\Jobs\DemonDextralHornJob;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Queue;
use Illuminate\Http\Request;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Data\HeadersData;
use DemonDextralHorn\Enums\PrefetchType;
use DemonDextralHorn\Resolvers\Contracts\TargetRouteResolverInterface;
use Tests\TestCase;

/**
 * Test the DemonDextralHornJob.
 *
 * @class DemonDextralHornJobTest
 */
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
                demonPrefetchCall: PrefetchType::NONE->value,
            ),
            routeName: 'sample.trigger.route.name',
            routeParams: ['routeParam' => 'routeValue'],
            queryParams: ['queryParam1' => 'value1', 'queryParam2' => 'value2'],
        );
        $this->responseData = new ResponseData(
            status: 200,
            content: 'ok',
        );
        $this->job = $this->app->make(DemonDextralHornJob::class, [
            'requestData' => $this->requestData,
            'responseData' => $this->responseData,
        ]);
        $this->targetRouteResolver = $this->app->make(TargetRouteResolverInterface::class);
    }

    // todo tests will be written
    #[Test]
    public function it_tests(): void
    {
        /* SETUP */

        /* EXECUTE */
        $this->job->handle($this->targetRouteResolver);

        /* ASSERT */
        
    }
}