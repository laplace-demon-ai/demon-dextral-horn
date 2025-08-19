<?php

declare(strict_types=1);

namespace Tests;

use DemonDextralHorn\Jobs\DemonDextralHornPrefetchJob;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Queue;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Data\HeadersData;
use DemonDextralHorn\Data\CookiesData;
use DemonDextralHorn\Enums\PrefetchType;

/**
 * Test the DemonDextralHornPrefetchJob.
 *
 * @class DemonDextralHornPrefetchJobTest
 */
final class DemonDextralHornPrefetchJobTest extends TestCase
{
    private DemonDextralHornPrefetchJob $job;
    private RequestData $requestData;
    private ResponseData $responseData;

    public function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->requestData = new RequestData(
            uri: '/prefetch-route-success/routeValue',
            method: 'GET',
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
        $this->job = $this->app->make(DemonDextralHornPrefetchJob::class, [
            'requestData' => $this->requestData,
            'responseData' => $this->responseData,
        ]);
    }

    #[Test]
    public function it_tests(): void
    {
        /* SETUP */

        /* EXECUTE */
        $this->job->handle();

        /* ASSERT */
        
    }
}
