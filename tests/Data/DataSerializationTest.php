<?php

declare(strict_types=1);

namespace Tests\Data;

use DemonDextralHorn\Data\CookiesData;
use DemonDextralHorn\Data\HeadersData;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Enums\PrefetchType;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;

#[CoversClass(RequestData::class)]
#[CoversClass(HeadersData::class)]
#[CoversClass(CookiesData::class)]
#[CoversClass(ResponseData::class)]
final class DataSerializationTest extends TestCase
{
    private RequestData $originalRequestData;
    private HeadersData $originalHeadersData;
    private CookiesData $originalCookiesData;
    private ResponseData $originalResponseData;

    public function setUp(): void
    {
        parent::setUp();

        $this->originalRequestData = new RequestData(
            uri: '/sample/route',
            method: Request::METHOD_POST,
            headers: new HeadersData(
                authorization: 'Bearer sample_token',
                accept: 'application/json',
                acceptLanguage: 'en-US',
                prefetchHeader: PrefetchType::NONE->value,
                setCookie: ['cookie1=value1']
            ),
            cookies: new CookiesData(sessionCookie: 'session123'),
            routeName: 'sample.route',
            routeParams: ['id' => 42],
            queryParams: ['page' => 1],
        );
        $this->originalHeadersData = new HeadersData(
            authorization: 'Bearer sample_token',
            accept: 'application/json',
            acceptLanguage: 'en-US',
            prefetchHeader: PrefetchType::NONE->value,
            setCookie: ['cookie1=value1']
        );
        $this->originalCookiesData = new CookiesData(sessionCookie: 'session123');
        $this->originalResponseData = new ResponseData(
            status: 200,
            headers: new HeadersData(
                setCookie: ['response_cookie=response_value']
            ),
            content: '{"message":"success"}',
        );
    }

    #[Test]
    public function it_serializes_request_data_correctly(): void
    {
        /* EXECUTE */
        $serializedRequestData = $this->originalRequestData->__serialize();

        /* ASSERT */
        $this->assertIsArray($serializedRequestData);
        $this->assertArrayHasKey('uri', $serializedRequestData);
        $this->assertArrayHasKey('method', $serializedRequestData);
        $this->assertArrayHasKey('headers', $serializedRequestData);
        $this->assertArrayHasKey('cookies', $serializedRequestData);
        $this->assertArrayHasKey('routeName', $serializedRequestData);
        $this->assertArrayHasKey('routeParams', $serializedRequestData);
        $this->assertArrayHasKey('queryParams', $serializedRequestData);
        $this->assertEquals('/sample/route', $serializedRequestData['uri']);
        $this->assertEquals(Request::METHOD_POST, $serializedRequestData['method']);
        $this->assertIsArray($serializedRequestData['headers']);
        $this->assertIsArray($serializedRequestData['cookies']);
        $this->assertEquals($this->originalRequestData->routeName, $serializedRequestData['routeName']);
        $this->assertEquals($this->originalRequestData->routeParams, $serializedRequestData['routeParams']);
        $this->assertEquals($this->originalRequestData->queryParams, $serializedRequestData['queryParams']);
    }

    #[Test]
    public function it_unserializes_request_data_correctly(): void
    {
        
        $serializedRequestData = $this->originalRequestData->__serialize();
        $newRequestData = new RequestData(
            uri: '',
            method: '',
            headers: new HeadersData(),
            cookies: new CookiesData(),
            routeName: '',
            routeParams: [],
            queryParams: [],
        );

        /* EXECUTE */
        $newRequestData->__unserialize($serializedRequestData);

        /* ASSERT */
        $this->assertEquals($this->originalRequestData->uri, $newRequestData->uri);
        $this->assertEquals($this->originalRequestData->method, $newRequestData->method);
        $this->assertEquals($this->originalRequestData->headers->authorization, $newRequestData->headers->authorization);
        $this->assertEquals($this->originalRequestData->headers->accept, $newRequestData->headers->accept);
        $this->assertEquals($this->originalRequestData->headers->acceptLanguage, $newRequestData->headers->acceptLanguage);
        $this->assertEquals($this->originalRequestData->headers->prefetchHeader, $newRequestData->headers->prefetchHeader);
        $this->assertEquals($this->originalRequestData->headers->setCookie, $newRequestData->headers->setCookie);
        $this->assertEquals($this->originalRequestData->cookies->sessionCookie, $newRequestData->cookies->sessionCookie);
        $this->assertEquals($this->originalRequestData->routeName, $newRequestData->routeName);
        $this->assertEquals($this->originalRequestData->routeParams, $newRequestData->routeParams);
        $this->assertEquals($this->originalRequestData->queryParams, $newRequestData->queryParams);
    }

    #[Test]
    public function it_serializes_headers_data_correctly(): void
    {
        /* EXECUTE */
        $serializedHeadersData = $this->originalHeadersData->__serialize();

        /* ASSERT */
        $this->assertIsArray($serializedHeadersData);
        $this->assertArrayHasKey('authorization', $serializedHeadersData);
        $this->assertArrayHasKey('accept', $serializedHeadersData);
        $this->assertArrayHasKey('acceptLanguage', $serializedHeadersData);
        $this->assertArrayHasKey('prefetchHeader', $serializedHeadersData);
        $this->assertArrayHasKey('setCookie', $serializedHeadersData);
        $this->assertEquals('Bearer sample_token', $serializedHeadersData['authorization']);
        $this->assertEquals('application/json', $serializedHeadersData['accept']);
        $this->assertEquals('en-US', $serializedHeadersData['acceptLanguage']);
        $this->assertEquals(PrefetchType::NONE->value, $serializedHeadersData['prefetchHeader']);
        $this->assertEquals(['cookie1=value1'], $serializedHeadersData['setCookie']);
    }

    #[Test]
    public function it_unserializes_headers_data_correctly(): void
    {
        /* SETUP */
        $serializedHeadersData = $this->originalHeadersData->__serialize();
        $newHeadersData = new HeadersData();

        /* EXECUTE */
        $newHeadersData->__unserialize($serializedHeadersData);

        /* ASSERT */
        $this->assertEquals($this->originalHeadersData->authorization, $newHeadersData->authorization);
        $this->assertEquals($this->originalHeadersData->accept, $newHeadersData->accept);
        $this->assertEquals($this->originalHeadersData->acceptLanguage, $newHeadersData->acceptLanguage);
        $this->assertEquals($this->originalHeadersData->prefetchHeader, $newHeadersData->prefetchHeader);
        $this->assertEquals($this->originalHeadersData->setCookie, $newHeadersData->setCookie);
    }

    #[Test]
    public function it_serializes_cookies_data_correctly(): void
    {
        /* EXECUTE */
        $serializedCookiesData = $this->originalCookiesData->__serialize();

        /* ASSERT */
        $this->assertIsArray($serializedCookiesData);
        $this->assertArrayHasKey('sessionCookie', $serializedCookiesData);
        $this->assertEquals('session123', $serializedCookiesData['sessionCookie']);
    }

    #[Test]
    public function it_unserializes_cookies_data_correctly(): void
    {
        /* SETUP */
        $serializedCookiesData = $this->originalCookiesData->__serialize();
        $newCookiesData = new CookiesData();

        /* EXECUTE */
        $newCookiesData->__unserialize($serializedCookiesData);

        /* ASSERT */
        $this->assertEquals($this->originalCookiesData->sessionCookie, $newCookiesData->sessionCookie);
    }

    #[Test]
    public function it_serializes_response_data_correctly(): void
    {
        /* EXECUTE */
        $serializedResponseData = $this->originalResponseData->__serialize();

        /* ASSERT */
        $this->assertIsArray($serializedResponseData);
        $this->assertArrayHasKey('status', $serializedResponseData);
        $this->assertArrayHasKey('headers', $serializedResponseData);
        $this->assertArrayHasKey('content', $serializedResponseData);
        $this->assertEquals(200, $serializedResponseData['status']);
        $this->assertIsArray($serializedResponseData['headers']);
        $this->assertEquals('{"message":"success"}', $serializedResponseData['content']);
    }

    #[Test]
    public function it_unserializes_response_data_correctly(): void
    {
        /* SETUP */
        $serializedResponseData = $this->originalResponseData->__serialize();
        $newResponseData = new ResponseData(
            status: 0,
            headers: new HeadersData(),
            content: '',
        );

        /* EXECUTE */
        $newResponseData->__unserialize($serializedResponseData);

        /* ASSERT */
        $this->assertEquals($this->originalResponseData->status, $newResponseData->status);
        $this->assertEquals($this->originalResponseData->headers->setCookie, $newResponseData->headers->setCookie);
        $this->assertEquals($this->originalResponseData->content, $newResponseData->content);
    }

    #[Test]
    public function it_converts_eloquent_route_parameters_to_route_keys(): void
    {
        /* SETUP */
        $id = 42;
        $model = new class extends Model {
            public $id = 42;
            public function getRouteKey()
            {
                return $this->id;
            }
        };
        $request = Request::create('/sample/' . $id, 'GET');
        $route = new Route(['GET'], '/sample/{id}', ['uses' => function () {}]);
        $route->bind($request); // Bind the request to the route
        $route->setParameter('id', $model);
        $request->setRouteResolver(static fn () => $route);
    
        // execute
        $requestData = RequestData::fromRequest($request);
    
        // assertions
        $this->assertIsArray($requestData->routeParams);
        $this->assertArrayHasKey('id', $requestData->routeParams);
        $this->assertEquals($id, $requestData->routeParams['id']);
    }
}