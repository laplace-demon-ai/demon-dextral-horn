<?php

declare(strict_types=1);

namespace Tests\Cache\ResponseCacheValidatorTest;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Arr;
use DemonDextralHorn\Cache\ResponseCacheValidator;

#[CoversClass(ResponseCacheValidator::class)]
final class ResponseCacheValidatorTest extends TestCase
{
    private ResponseCacheValidator $responseCacheValidator;

    public function setUp(): void
    {
        parent::setUp();

        config()->set('demon-dextral-horn.defaults.cache_max_size', 1024 * 1024); // 1MB
        $this->responseCacheValidator = app(ResponseCacheValidator::class);
    }

    #[Test]
    public function it_validates_cacheable_response(): void
    {
        /* SETUP */
        $content = ['message' => 'This is a cacheable response'];
        $response = new Response(json_encode($content), Response::HTTP_OK);
        $response->headers->set('Content-Type', 'application/json');

        /* EXECUTE */
        $isValid = $this->responseCacheValidator->shouldCacheResponse($response);

        /* ASSERT */
        $this->assertTrue($isValid);
        $results = $this->responseCacheValidator->getValidationResults($response);
        $this->assertTrue(Arr::get($results, 'hasCacheableResponseCode'));
        $this->assertTrue(Arr::get($results, 'hasCacheableContentType'));
        $this->assertTrue(Arr::get($results, 'hasReasonableSize'));
        $this->assertFalse(Arr::get($results, 'isStreamingResponse'));
    }

    #[Test]
    public function it_validates_cacheable_redirects(): void
    {
        /* SETUP */
        $response = new Response('ok', Response::HTTP_FOUND, ['Content-Type' => 'text/html']);

        /* EXECUTE */
        $isValid = $this->responseCacheValidator->shouldCacheResponse($response);

        /* ASSERT */
        $this->assertTrue($isValid);
        $results = $this->responseCacheValidator->getValidationResults($response);
        $this->assertTrue(Arr::get($results, 'hasCacheableResponseCode'));
        $this->assertTrue(Arr::get($results, 'hasCacheableContentType'));
        $this->assertTrue(Arr::get($results, 'hasReasonableSize'));
        $this->assertFalse(Arr::get($results, 'isStreamingResponse'));
    }

    #[Test]
    public function it_rejects_non_cacheable_redirects(): void
    {
        /* SETUP */
        $response = new Response('', Response::HTTP_TEMPORARY_REDIRECT, ['Content-Type' => 'text/html']);

        /* EXECUTE */
        $isValid = $this->responseCacheValidator->shouldCacheResponse($response);

        /* ASSERT */
        $this->assertFalse($isValid);
        $results = $this->responseCacheValidator->getValidationResults($response);
        $this->assertFalse(Arr::get($results, 'hasCacheableResponseCode'));
        $this->assertTrue(Arr::get($results, 'hasCacheableContentType'));
        $this->assertTrue(Arr::get($results, 'hasReasonableSize'));
        $this->assertFalse(Arr::get($results, 'isStreamingResponse'));
    }

    #[Test]
    public function it_rejects_streaming_responses(): void
    {
        /* SETUP */
        $response = new Response('', Response::HTTP_OK, [
            'Content-Type' => 'text/event-stream',
        ]);

        /* EXECUTE */
        $isValid = $this->responseCacheValidator->shouldCacheResponse($response);

        /* ASSERT */
        $this->assertFalse($isValid);
        $results = $this->responseCacheValidator->getValidationResults($response);
        $this->assertTrue(Arr::get($results, 'hasCacheableResponseCode'));
        $this->assertTrue(Arr::get($results, 'hasCacheableContentType'));
        $this->assertTrue(Arr::get($results, 'hasReasonableSize'));
        $this->assertTrue(Arr::get($results, 'isStreamingResponse'));
    }

    #[Test]
    public function it_rejects_responses_exceeding_max_size(): void
    {
        /* SETUP */
        $response = new Response('bigContent', Response::HTTP_OK, ['Content-Type' => 'text/html']);
        $response->headers->set('Content-Length', (string) (2 * 1024 * 1024)); // 2MB

        /* EXECUTE */
        $isValid = $this->responseCacheValidator->shouldCacheResponse($response);

        /* ASSERT */
        $this->assertFalse($isValid);
        $results = $this->responseCacheValidator->getValidationResults($response);
        $this->assertTrue(Arr::get($results, 'hasCacheableResponseCode'));
        $this->assertTrue(Arr::get($results, 'hasCacheableContentType'));
        $this->assertFalse(Arr::get($results, 'hasReasonableSize'));
        $this->assertFalse(Arr::get($results, 'isStreamingResponse'));
    }
}