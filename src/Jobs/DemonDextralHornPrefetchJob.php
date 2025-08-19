<?php

declare(strict_types=1);

namespace DemonDextralHorn\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Data\HeadersData;
use DemonDextralHorn\Enums\PrefetchType;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * @class DemonDextralHornPrefetchJob
 */
final class DemonDextralHornPrefetchJob implements ShouldQueue
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
    public function handle(): void
    {
        $config = config('demon-dextral-horn');

        $rules = Arr::get($config, 'rules', []);

        // Set the route name which will be used for matching the appropriate rule
        $routeName = $this->requestData->routeName;

        // Find the matching rule for the given route name
        $rule = Arr::first($rules, function ($rule) use ($routeName) {
            return Arr::get($rule, 'trigger.route') === $routeName
                && strtoupper(Arr::get($rule, 'trigger.method', Request::METHOD_GET)) === Request::METHOD_GET;
        });
        
        $targetRoutes = Arr::get($rule, 'targets', []);

        foreach ($targetRoutes as $targetRoute) {
            $this->handleTargetRoute($targetRoute, $this->requestData, $this->responseData);
        }
    }

    private function handleTargetRoute(array $targetRoute, RequestData $requestData, ResponseData $responseData): void
    {
        $targetMethod = Arr::get($targetRoute, 'method', Request::METHOD_GET);
        $targetRouteName = Arr::get($targetRoute, 'route');
        $targetRouteParams = Arr::get($targetRoute, 'route_params', []);
        $targetQueryParams = Arr::get($targetRoute, 'query_params', []);

        $routeParams = $this->prepareRouteParams($targetRouteParams, $requestData, $responseData);

        $this->dispatchRoute($targetRouteName, $routeParams, $targetQueryParams, $targetMethod, $requestData);
    }

    private function prepareRouteParams(array $params, RequestData $requestData, ResponseData $responseData): array
    {
        $resolvedParams = [];
        // todo Note that when routes are called, in case they return not success message for one of them, it should not block other requests,, - maybe somehow log them but app should work without breaking

        foreach ($params as $key => $value) {
            // todo here we will have the logic for NOT clousre calling, since config will not have closure,, instead form request validation like config and logic/method to resolve params by using these given config
            $resolvedParams[$key] = rand(1, 100); // todo this is for testing purpose before addding validation like logic to config file
        }

        return $resolvedParams;
    }

    private function prepareQueryParams(string $url, array $queryParams): string
    {
        // todo check if that covers all kind of query params, for instance when multiple is sent will it add as ?items[]=1&items[]=2
        $resolvedQueryParams = [];

        // todo here we will resolve query paramsfrom validation logic (maybe call a method for it), and below we add them to url
        foreach ($queryParams as $key => $value) {
            $resolvedQueryParams[$key] = rand(1, 100); // todo this is for testing purpose before addding validation like logic to config file
        }

        $url .= '?' . http_build_query($resolvedQueryParams); // todo this will also generate query params from the validation like given config

        return $url;
    }

    private function dispatchRoute(string $routeName, array $routeParams, array $queryParams, string $method, RequestData $triggerRequestData)
    {
        // Generate url from route name and parameters (e.g. "/89/69/sample-target-route-success/21/30")
        $url = route($routeName, $routeParams, false);

        // Add query parameters to the url (e.g. "/89/69/sample-target-route-success/21/30?query_param1=7")
        $urlWithQueryParams = $this->prepareQueryParams($url, $queryParams);

        // todo add trigger request headers to the logic, cascade the tokens, etc. check if it is standardized so for session laravle jwt we use Authorization header etc,, make it someting that will work with all auth
        // todo what about cookies,, what kind of cookies were there wbe,, should be cascade them too?

        // Get the custom prefetch_header so that it will be added to the target route
        $prefetchHeaderName = config('demon-dextral-horn.defaults.prefetch_header'); // Demon-Prefetch-Call
        $prefetchHeaderValue = PrefetchType::AUTO->value;
        $prefetchHeader = [
            $prefetchHeaderName => $prefetchHeaderValue
        ];

        // todo handle the cookie,, from request, or from response header considering the auth option choosed and edge case as it is in the header,, and add it to request create
        // todo same as cookie; prepare/generate server if necessary and also add it ( it might not be necessary so headers are automatically converted to server by laravel - HTTP_AUTHORIZATION,, but check it out)

        $request = Request::create($urlWithQueryParams, $method, [], [], [], []);

        // todo as mentioned, we might be getting some values from other places rather than request header, e.g. auth related login for jwt (access_token) will be retrieved from response payload and set to header,, so probably method will accept response too. think about other cases too.
        $headers = $this->prepareHeaders($triggerRequestData->headers, $prefetchHeader);

        $request->headers->replace($headers);

        $response = app('router')->dispatch($request);

        return $response; // todo should we return something?? => probabluy we should return in the service where it is send to another caching service which hadnles caching related part including setting cache ttl, cache prefix etc
    }

    private function prepareHeaders(HeadersData $headersData, array $prefetchHeader): array
    {
        // Map the headers to the correct format
        $mappedHeaders = [
            'Authorization' => $headersData->authorization,
            'Accept' => $headersData->accept,
            'Accept-Language' => $headersData->acceptLanguage,
        ];

        // Merge prefetchHeader and filter null values from the headers
        return array_filter(
            array_merge($mappedHeaders, $prefetchHeader),
            fn ($value) => ! is_null($value)
        );
    }
}

/*
todo:

    public $tries = 1; => we can set it to 1 or 2 in case default is more than that, we dont want it so override it here to make sure it will not be retiried much
    - (?) Maybe right in the seconds when the endpoints resolved/decided to call from the map, save some info in the db or redis so that the the endpoints are called so they are pending,, so that when it is requested, this status will be checked before making the actual call
*/