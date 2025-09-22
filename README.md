# Demon Dextral Horn

**Demon Dextral Horn** is a Laravel package for **API prefetching**. It **predicts** and **caches** upcoming **API requests** based on configurable rules, so responses are instantly available when users need them.

## Table of Contents

- [Requirements](#requirements)
- [Get Started](#get-started)
- [Configuration](#configuration)
- [Usage](#usage)
  - [Steps](#steps)
    - [1. Define Your Prefetching Rules](#1-define-your-prefetching-rules)
    - [2. Add Middleware to Routes](#2-add-middleware-to-routes)
    - [3. Done!](#3-done)
  - [Built-in Strategies](#built-in-strategies)
  - [Real World Use Cases](#real-world-use-cases)
- [Advanced Usage](#advanced-usage)
  - [Creating Custom Strategies](#creating-custom-strategies)
  - [Clearing The Cache](#clearing-the-cache)
  - [Monitoring and Debugging Through Events](#monitoring-and-debugging-through-events)
- [Troubleshooting](#troubleshooting)
- [TODO](#todo)
- [Contributing](#contributing)
- [Authors](#authors)
- [License](#license)

## Requirements
- **PHP 8.2** or **higher**

Package has only 2 dependencies:

- [Spatie laravel-data](https://github.com/spatie/laravel-data)
- [Spatie laravel-responsecache](https://github.com/spatie/laravel-responsecache)

## Get Started
You can **install** the package via composer:

```bash
composer require laplace-demon-ai/demon-dextral-horn
```

You can **publish** the **config file** with:

```bash
php artisan vendor:publish --tag=demon-dextral-horn
```

## Configuration
After publishing the [`demon-dextral-horn`](config/demon-dextral-horn.php) config file, you'll need to:
- Decide the **config defaults**.
- Set up the **prefetching rules** including **trigger** and **target** routes, **strategies** to resolve parameters etc.

<details>
<summary> Config defaults: </summary>

```php
'defaults' => [
    'enabled' => env('PREFETCH_ENABLED', true),
    'cache_ttl' => env('PREFETCH_CACHE_TTL', 60), // in seconds, e.g. 60 seconds = 1 minute
    'prefetch_prefix' => env('PREFETCH_PREFIX', 'demon_dextral_horn'),
    'cache_max_size' => env('PREFETCH_CACHE_MAX_SIZE', 1048576), // 1 MB = 1024 * 1024 bytes = 1048576 bytes
    'queue_connection' => env('PREFETCH_QUEUE_CONNECTION', 'redis'),
    'queue_name' => env('PREFETCH_QUEUE_NAME', 'prefetch'),
    'prefetch_header' => env('PREFETCH_HEADER', 'Demon-Prefetch-Call'),
    'auth_driver' => config('auth.guards.api.driver', AuthDriverType::SESSION->value),
    'session_cookie_name' => config('session.cookie', 'laravel_session'),
    'cache_store' => env('PREFETCH_RESPONSE_CACHE_DRIVER', 'redis'),
]
```

- `enabled`: **Enable** or **disable** prefetching globally. Default is `true`.
- `cache_ttl`: **Time-to-live** for cached responses in seconds. It should be **short** because it is for prefetching which is expected to be used soon, otherwise should be flushed not to cause cluttering in cache. Default is `60 seconds`.
- `prefetch_prefix`: **Prefix for cache keys** to avoid collisions and tag the cached responses.
- `cache_max_size`: **Maximum size** of cached responses in bytes. If a response exceeds this size, it won't be cached. Default is `1 MB`.
- `queue_connection`: The **queue connection** to use for prefetching jobs. Default is `redis`.
- `queue_name`: The **name of the queue** to use for prefetching jobs. Default is `prefetch`.
- `prefetch_header`: The **custom HTTP header** used to identify prefetch requests. It prevents circular prefetching and identifies prefetch calls. Default is `Demon-Prefetch-Call`.
- `auth_driver`: The **authentication driver/method** used in your application. It can be `session` or `jwt` at the moment (values of AuthDriverType enum). Default is `session`.
- `session_cookie_name`: The **name of the session cookie** used for Laravel default session-based authentication. It can be ignored if you are using `jwt`, or other authentication methods. Default is `laravel_session`.
- `cache_store`: The **cache store** to use for storing cached responses. It maps to Spatie laravel-responsecache config `cache_store`. Default is `redis`.

</details>

<details>
<summary> Prefetching Rules: </summary>

Here you define the **rules for prefetching**, including **trigger** routes, **target** routes, and **strategies** to resolve parameters.

> [!NOTE]
> `Prefetching Rules` will be covered in the [Usage](#usage) section in details including some real world examples regarding different scenarios.

A sample rule definition:

```php
'rules' => [
    [
        'description' => 'Sample description',
        'trigger' => [
            'method' => Request::METHOD_GET,
            'route' => 'sample.trigger.route.name',
        ],
        'targets' => [
            [
                'method' => Request::METHOD_GET,
                'route' => 'sample.target.route.with.query.param',
                'query_params' => [
                    'query_key' => [
                        'strategy' => IncrementQueryParamStrategy::class,
                        'options' => [
                            'source_key' => 'query_key',
                            'increment' => 1,
                        ],
                    ],
                ],
            ],
            [
                'method' => Request::METHOD_GET,
                'route' => 'sample.target.route.with.route.param',
                'route_params' => [
                    'route_key' => [
                        'strategy' => FromResponseBodyStrategy::class,
                        'options' => [
                            'position' => 'data.id',
                        ],
                    ],
                ],
            ],
            [
                'method' => Request::METHOD_GET,
                'route' => 'sample.target.route.without.params',
            ],
            ..
        ],
    ],
]
```

- `description`: A **brief description** of the rule for clarity (has no functional impact).
- `trigger`: Defines the **route and HTTP method** that will trigger the prefetching. It includes:
  - `method`: The **HTTP method** (e.g., GET, POST) of the trigger route.
  - `route`: The **name of the trigger route** (as defined in your Laravel routes).
- `targets`: An array of **target routes** to prefetch when the trigger route is accessed. Each target includes:
  - `method`: The **HTTP method** of the target route (**only GET** is supported  because target routes have to be **idempotent** to be able to prefetch).
  - `route`: The **name of the target route** (as defined in your Laravel routes).
  - `query_params` (optional): Defines how to resolve **query parameters** for the target route. Each entry includes:
    - `query_key` (dynamic): The **name of the query parameter** that needs to be resolved for the target route (e.g. `page`). Each query parameter includes:
      - `strategy`: The **strategy class** used to resolve the parameter (e.g., `IncrementQueryParamStrategy`, `FromResponseBodyStrategy`).
      - `options`: An array of **options** specific to the chosen strategy (e.g., `source_key`, `increment`, `position`).
  - `route_params` (optional): Similar to `query_params`, but for route parameters. Each entry includes:
    - `route_key` (dynamic): The **name of the route parameter** that needs to be resolved for the target route (e.g., `id`). Each route parameter includes:
      - `strategy`: The **strategy class** used to resolve the parameter.
      - `options`: An array of **options** specific to the chosen strategy (e.g., `source_key`, `increment`, `position`).
  - `headers` (optional): Defines any **headers** to include in the prefetch target request (e.g., for authentication or custom headers). Each entry includes:
    - `header_key` (dynamic): The **name of the header** to include in the request (e.g., `Authorization`). Each header includes:
      - `strategy`: The **strategy class** used to resolve the header value (e.g., `ResponseJwtStrategy`)
      - `options`: An array of **options** specific to the chosen strategy (e.g., `source_key`, `position`).
  - `cookies` (optional): Defines any **cookies** to include in the prefetch target request (e.g., for Laravel session-based authentication). Each entry includes:
    - `cookie_key` (dynamic): The **name of the cookie** to include in the request (e.g., `session_cookie`). Each cookie includes:
      - `strategy`: The **strategy class** used to resolve the cookie value (e.g., `FromRequestCookieStrategy`)
      - `options`: An array of **options** specific to the chosen strategy (e.g., `source_key` as `laravel_session`).

</details>

## Usage

### Steps
Using **Demon Dextral Horn** involves **3 main steps**:
- [1. Define Your Prefetching Rules](#1-define-your-prefetching-rules)
- [2. Add middleware to routes](#2-add-middleware-to-routes)
- [3. Done!](#3-done)

#### 1. Define Your Prefetching Rules:
In this step, you need to **identify** which routes will serve as **trigger routes** and which will be **target routes**. The best approach is to analyze your **frontend application** or any **application** that calls your **Laravel API routes**.
Once you understand how these **API calls** are made and their **sequence**, you can begin defining your **rules** in the configuration file.

In your config file [`demon-dextral-horn.php`](config/demon-dextral-horn.php), configure:
- Which routes will act as **triggers**.
- Which routes will be prefetched as **targets**.
- **Strategies** needed to resolve query parameters, route parameters, headers, or cookies for your targets. (See [Built-in Strategies](#built-in-strategies) section for available strategies.)

> [!CAUTION]
> - Target routes have to be **idempotent**, so only **GET requests** are supported for target routes!
> 
> - A route can be **both** a **trigger** and a **target**. It can have its own targets while also being a target for other triggers.
>   - **Prefetched requests** include a **custom header** (`defaults.prefetch_header`, default `Demon-Prefetch-Call=auto`) to identify them. This **prevents** prefetched routes from **triggering** further prefetching, **avoiding circular requests**.
>   - Since prefetched requests have **custom header**, it can be used for **debugging** or **monitoring** purposes as well.
> 
> - **Triggers without authentication** cannot prefetch **auth-protected targets** (Prefetching **forwards** the trigger’s **auth credentials** to the targets)
>   - ✅Valid: 
>     - trigger(auth) → target(public)
>     - trigger(auth) → target(auth)
>     - trigger(public) → target(public)
>   - ❌Invalid: 
>     - trigger(public) → target(auth) (except for trigger is the login request, see below)
>   
> - No need to add **headers** or **cookies** fields for **targets**, because the system **automatically forwards** the **auth credentials** (session cookie or JWT token) from the **trigger** to the **targets**.
>   - When **login request** is the **trigger**, you need to explicitly extract the JWT **token** from the response using `ResponseJwtStrategy`, or `ForwardSetCookieHeaderStrategy` for session-based auth. And `Demon Dextral Horn` will forward it to the **targets** automatically.

<details>
<summary> Here's a sample response body for a login request: </summary>

```json
{
    "data": {
        "user": {
            "id": 1,
            "public_uuid": "45d15c51-8329-4a96-9521-77e15ec0ee82",
            "name": "Moe Mizrak",
            "username": "moe.mizrak",
            "description": "Author of the Demon Dextral Horn",
            "views": 19,
            "avatar": null,
            "email": "moe.mizrak@mail.com"
        },
        "message": "Login successful!",
        "token": "sample_jwt_token"
    }
}
```

</details>

Below is a **sample rule definition**:

```php
'rules' => [
    [
        'description' => 'Prefetch next page when current page is requested',
        'trigger' => [
            'method' => Request::METHOD_GET,
            'route' => 'posts.index', // e.g. /posts?page=1
        ],
        'targets' => [
            [
                'method' => Request::METHOD_GET,
                'route' => 'posts.index', // e.g. /posts?page=2
                'query_params' => [
                    'page' => [
                        'strategy' => IncrementQueryParamStrategy::class,
                        'options' => ['source_key' => 'page', 'increment' => 1],
                    ],
                ],
            ],
        ],
    ],
    [
        'description' => 'After login, prefetch profile and activity for the user',
        'trigger' => [
            'method' => Request::METHOD_POST,
            'route' => 'auth.login',
        ],
        'targets' => [
            [
                'method' => Request::METHOD_GET,
                'route' => 'user.profile', // e.g. /user/profile
                'headers' => [
                    'authorization' => [
                        'strategy' => ResponseJwtStrategy::class,
                        'options' => [
                            'position' => 'data.token', // extract token from login response
                        ],
                    ],
                ],
            ],
            [
                'method' => Request::METHOD_GET,
                'route' => 'users.activity', // e.g. /users/{user}/activity
                'headers' => [
                    'authorization' => [
                        'strategy' => ResponseJwtStrategy::class,
                        'options' => [
                            'position' => 'data.token', // extract token from login response
                        ],
                    ],
                ],
                'route_params' => [
                    'user' => [
                        'strategy' => FromResponseBodyStrategy::class,
                        'options' => [
                            'position' => 'data.user.id', // extract route param {user} from the response body
                        ],
                    ],
                ],
            ],
        ],
    ],
];
```

> [!TIP]
> - Check [Real World Use Cases](#real-world-use-cases) section for more **diverse** and **practical** examples.
> 
> - Also check [Built-in Strategies](#built-in-strategies) section to see the **strategies** that are provided **out of the box**.
> 
> - And you can also create your own **custom strategies** if needed, which will be covered in [Creating Custom Strategies](#creating-custom-strategies) section.

---
#### 2. Add middleware to routes:
- Add [`PrefetchTriggerMiddleware`](src/Middleware/PrefetchTriggerMiddleware.php) to the **trigger routes**.
- Add [`PrefetchCacheMiddleware`](src/Middleware/PrefetchCacheMiddleware.php) to the **target routes**.

Here's how to add middleware to your routes:

```php
use DemonDextralHorn\Middleware\PrefetchTriggerMiddleware;
use DemonDextralHorn\Middleware\PrefetchCacheMiddleware;

/**
 * In this sample, the `/posts` route is both a trigger and a target. 
 * It uses `PrefetchTriggerMiddleware` to trigger prefetching when accessed, and `PrefetchCacheMiddleware` to serve cached responses if available. 
 */
Route::get('/posts', [PostController::class, 'index'])
    ->middleware([PrefetchTriggerMiddleware::class, PrefetchCacheMiddleware::class])
    ->name('posts.index');

/**
 * The `/auth/login` route is a trigger only.
 * When hit, it will trigger prefetching of user profile and activity as defined in the rules.
 */
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware(PrefetchTriggerMiddleware::class)
    ->name('auth.login');

/**
 * The `/user/profile` route is a target.
 * It requires JWT authentication via `auth:api`.
 * It checks the prefetch cache first using `PrefetchCacheMiddleware`.
 */
Route::get('/user/profile', [UserController::class, 'profile'])
    ->middleware(['auth:api', PrefetchCacheMiddleware::class])
    ->name('user.profile');

/**
 * The `/users/{user}/activity` route is also a target.
 * It requires JWT authentication via `auth:api`.
 * Like `/user/profile`, it checks the prefetch cache first.
 */
Route::get('/users/{user}/activity', [UserController::class, 'activity'])
    ->middleware(['auth:api', PrefetchCacheMiddleware::class])
    ->name('users.activity');
```

---
#### 3. Done!
**You are all set!** Now when a user accesses a **trigger route**, the defined **target routes** will be **prefetched** and **cached** in the background by the **Demon Dextral Horn**.
And when a user accesses a **target route**, if a **cached response** is **available**, it will be served instantly **from the cache**.

> **What to expect:**
> - When a **trigger route** is accessed, the [`PrefetchTriggerMiddleware`](src/Middleware/PrefetchTriggerMiddleware.php) will enqueue [`DemonDextralHornJob`](src/Jobs/DemonDextralHornJob.php) for the defined **target routes**.
>   - The `terminate` method of the middleware is used to **enqueue the job** after the response is sent to the user, so it does **not** affect the performance of the actual request at all.
>   - The `DemonDextralHornJob` will be processed in the background using **Laravel's queue system**, so it **won't block** the main request cycle (**asynchronous processing**).
> - The job will make **requests** to the **target routes internally**, using the strategies defined in the rules to resolve any parameters, headers, or cookies needed.
> - The **responses** from the **target routes** will be **cached** (whole response including headers, status code, body etc.) using Spatie's [laravel-responsecache](https://github.com/spatie/laravel-responsecache) package.
> - When a **target route** is accessed, the `PrefetchCacheMiddleware` will check if a **cached response** is available. If so, it will return the **cached response** immediately instead of hitting the controller.

> [!NOTE]
> Spatie's `laravel-responsecache` package is **NOT** used directly, instead only its caching mechanism is utilized to **store/serialize** responses and **retrieve/unserialize** the cached responses.
> Since `laravel-responsecache` is designed to cache all `GET` requests automatically, which is **NOT desired** for our case.

##### Quick checklist:
- Config **defaults** are set correctly in [`demon-dextral-horn.php`](config/demon-dextral-horn.php):
    - `enabled` is set to `true`.
    - `auth_driver` is referencing to correct authentication method used in your application (`session` or `jwt`).
    - `queue_connection` is set to a valid queue connection that is configured in your application.
    - `cache_store` is set to a valid cache store that is configured in your application.
    - You can use default values for other config options or adjust them as needed.
- Prefetching **rules** are defined correctly in [`demon-dextral-horn.php`](config/demon-dextral-horn.php) for valid trigger and target routes, and any needed strategies.
- [`PrefetchTriggerMiddleware`](src/Middleware/PrefetchTriggerMiddleware.php) is added to all trigger routes.
- [`PrefetchCacheMiddleware`](src/Middleware/PrefetchCacheMiddleware.php) is added to all target routes.
- Your queue worker is running to process the prefetch jobs:

```bash
php artisan queue:work --queue=prefetch
```

> [!TIP]
> Check [Troubleshooting](#troubleshooting) section if you encounter any issues.

---
### Built-in Strategies
There are mainly 2 types of strategies provided out of the box:
- **Atomic Strategies**: These are simple, single-purpose strategies that perform a specific task.
    - [`FromRequestQueryStrategy`](src/Resolvers/Strategies/Atomic/FromRequestQueryStrategy.php): Fetches a value from the request query parameters using a specified `source_key`.
    - [`FromResponseBodyStrategy`](src/Resolvers/Strategies/Atomic/FromResponseBodyStrategy.php): Fetches a value from the response body using a specified `position`.
    - [`FromResponseHeaderStrategy`](src/Resolvers/Strategies/Atomic/FromResponseHeaderStrategy.php): Strategy to extract a value from the response header using a specified `header_name`.
    - [`IncrementValueStrategy`](src/Resolvers/Strategies/Atomic/IncrementValueStrategy.php): Strategy to increment a numeric value by a specified amount or by a default value if none is provided.
    - [`AffixStrategy`](src/Resolvers/Strategies/Atomic/AffixStrategy.php): Strategy to add a prefix and/or suffix to a given string value.
    - [`ArrayLimitStrategy`](src/Resolvers/Strategies/Atomic/ArrayLimitStrategy.php): Strategy to limit the number of elements in an array.
    - [`ArraySortStrategy`](src/Resolvers/Strategies/Atomic/ArraySortStrategy.php): Sort an array in ascending or descending order.
    - [`ParseSetCookieStrategy`](src/Resolvers/Strategies/Atomic/ParseSetCookieStrategy.php): Strategy to retrieve a specific cookie (session cookie) from the Set-Cookie headers (provided with $value) in an HTTP response.
- **Composite Strategies**: These strategies combine/chain multiple atomic strategies to achieve more complex behavior.
    - [`IncrementQueryParamStrategy`](src/Resolvers/Strategies/Composite/IncrementQueryParamStrategy.php): Chain `FromRequestQueryStrategy` and `IncrementValueStrategy` to increment a query parameter from the request.
    - [`ResponseJwtStrategy`](src/Resolvers/Strategies/Composite/ResponseJwtStrategy.php): Chain `FromResponseBodyStrategy` and `AffixStrategy` to extract a JWT token from the response body and format it as a `Bearer` token.
    - [`ForwardSetCookieHeaderStrategy`](src/Resolvers/Strategies/Composite/ForwardSetCookieHeaderStrategy.php): Chain `FromResponseHeaderStrategy` and `ParseSetCookieStrategy` to extract a `Set-Cookie` header from the response header and forward the session cookie.
    - [`ForwardQueryParamStrategy`](src/Resolvers/Strategies/Composite/ForwardQueryParamStrategy.php): Chain `FromRequestQueryStrategy` to forward a query parameter from the request.
    - [`ResponsePluckStrategy`](src/Resolvers/Strategies/Composite/ResponsePluckStrategy.php): Chain `FromResponseBodyStrategy`, `ArraySortStrategy`, and `ArrayLimitStrategy` to extract, sort, and limit an array from the response body.

> [!Note]
> These built-in strategies are opinionated solutions for common use cases I've encountered in real-world applications.
> 
> Feel free to contribute more strategies that you find useful!
> 
> Check [Creating Custom Strategies](#creating-custom-strategies) section if you need to create your own strategies.

### Real World Use Cases
Here are some practical scenarios where **Demon Dextral Horn** can significantly improve user experience:

---
<details>
<summary> E-commerce Product Listing with Pagination </summary>

When users browse product listings, they often navigate to the next page. Prefetch the next page while they're viewing the current one.

```php
'rules' => [
    [
        'description' => 'Prefetch next page of products when current page is viewed',
        'trigger' => [
            'method' => Request::METHOD_GET,
            'route' => 'products.index', // e.g. /products?page=1&category=electronics
        ],
        'targets' => [
            [
                'method' => Request::METHOD_GET,
                'route' => 'products.index', // e.g. /products?page=2&category=electronics
                'query_params' => [
                    'page' => [
                        'strategy' => IncrementQueryParamStrategy::class,
                        'options' => ['source_key' => 'page', 'increment' => 1],
                    ],
                    'category' => [
                        'strategy' => ForwardQueryParamStrategy::class,
                        'options' => ['source_key' => 'category'],
                    ],
                ],
            ],
        ],
    ],
],
```

</details>

---
<details>
<summary> User Authentication Flow </summary>

After login, users typically view their profile, dashboard and feed. Prefetch these common destinations.

```php
'rules' => [
    [
        'description' => 'Prefetch user data after successful login',
        'trigger' => [
            'method' => Request::METHOD_POST,
            'route' => 'auth.login', // e.g. /auth/login
        ],
        'targets' => [
            [
                'method' => Request::METHOD_GET,
                'route' => 'user.profile', // e.g. /user/profile
                'headers' => [
                    'authorization' => [
                        'strategy' => ResponseJwtStrategy::class,
                        'options' => ['position' => 'data.token'],
                    ],
                ],
            ],
            [
                'method' => Request::METHOD_GET,
                'route' => 'user.dashboard', // e.g. /user/dashboard
                'headers' => [
                    'authorization' => [
                        'strategy' => ResponseJwtStrategy::class,
                        'options' => ['position' => 'data.token'],
                    ],
                ],
            ],
            [
                'method' => Request::METHOD_GET,
                'route' => 'home.feed', // e.g. /home/feed, no auth route so this is enough to prefetch (no params, headers or cookies needed)
            ],
        ],
    ],
],
```

</details>

---
<details>
<summary> E-learning Course Navigation </summary>

In online courses, students typically progress through lessons sequentially. Prefetch the next lesson.

```php
'rules' => [
    [
        'description' => 'Prefetch next lesson when current lesson is viewed',
        'trigger' => [
            'method' => Request::METHOD_GET,
            'route' => 'courses.lessons.show', // e.g. /courses/5/lessons/3
        ],
        'targets' => [
            [
                'method' => Request::METHOD_GET,
                'route' => 'courses.lessons.show', // e.g. /courses/5/lessons/4
                'route_params' => [
                    'course' => [
                        'strategy' => ForwardRouteParamStrategy::class,
                        'options' => ['source_key' => 'course'],
                    ],
                    'lesson' => [
                        'strategy' => IncrementValueStrategy::class,
                        'options' => ['source_key' => 'lesson', 'increment' => 1],
                    ],
                ],
            ],
        ],
    ],
],
```

</details>

---
<details>
<summary> Blog/Article Reading Flow </summary>

When users read an article, they might want to see related articles or the next article in a series.

```php
'rules' => [
    [
        'description' => 'Prefetch related articles when viewing an article',
        'trigger' => [
            'method' => Request::METHOD_GET,
            'route' => 'articles.show', // e.g. /articles/123
        ],
        'targets' => [
            [
                'method' => Request::METHOD_GET,
                'route' => 'articles.related', // e.g. /articles/123/related
                'route_params' => [
                    'article' => [
                        'strategy' => ForwardRouteParamStrategy::class,
                        'options' => ['source_key' => 'article'],
                    ],
                ],
            ],
            [
                'method' => Request::METHOD_GET,
                'route' => 'articles.comments', // e.g. /articles/123/comments
                'route_params' => [
                    'article' => [
                        'strategy' => ForwardRouteParamStrategy::class,
                        'options' => ['source_key' => 'article'],
                    ],
                ],
            ],
        ],
    ],
],
```

</details>

---
<details>
<summary> Admin Dashboard Reports </summary>

Admins often check multiple reports. Prefetch related ones when opening the dashboard.

```php
'rules' => [
    [
        'description' => 'Prefetch related reports',
        'trigger' => [
            'method' => Request::METHOD_GET,
            'route' => 'admin.dashboard', // e.g. /admin/dashboard
        ],
        'targets' => [
            ['method' => Request::METHOD_GET, 'route' => 'admin.reports.users'], // e.g. /admin/reports/users
            ['method' => Request::METHOD_GET, 'route' => 'admin.reports.sales'], // e.g. /admin/reports/sales
        ],
    ],
],
```

</details>

---
<details>
<summary> Prefetch Updated Post Comments </summary>

When a user posts a comment, they often want to see the updated comments list. Prefetch it right after posting.

```php
'rules' => [
    'description' => 'After posting a comment, prefetch comments list for the post',
    'trigger' => [
        'method' => Request::METHOD_POST,
        'route'  => 'comments.store', // e.g. /comments/create
    ],
    'targets' => [
        [
            'method' => Request::METHOD_GET,
            'route'  => 'posts.comments', // e.g. /posts/{post}/comments
            'route_params' => [
                'post' => [
                    'strategy' => FromResponseBodyStrategy::class,
                    'options'  => ['position' => 'data.comment.post_id'],
                ],
            ],
        ],
    ],
],
```

</details>

---
<details>
<summary> Prefetch Updated Profile </summary>

When a user updates their avatar, they often want to see their updated profile. Prefetch it right after the upload.

```php
'rules' => [
    'description' => 'After avatar upload, prefetch updated user profile',
    'trigger' => [
        'method' => Request::METHOD_POST,
        'route'  => 'user.avatar.upload', // e.g. /user/avatar/upload
    ],
    'targets' => [
        [
            'method' => Request::METHOD_GET,
            'route'  => 'user.profile', // e.g. /user/profile
        ],
    ],
],
```

</details>

## Advanced Usage

### Creating Custom Strategies
If the built-in strategies do not meet your specific needs, you can create your own custom strategies by:
- Extend the [`AbstractStrategy`](src/Resolvers/Strategies/AbstractStrategy.php) class which implements [`StrategyInterface`](src/Resolvers/Strategies/StrategyInterface.php).
- Implement the `handle` method, which receives the request/response data and returns the transformed value.
    - `handle` method parameters:
        - [`RequestData`](src/Data/RequestData.php) $requestData: Contains the original request data including query parameters, route parameters, headers, cookies etc.
        - [`ResponseData`](src/Data/ResponseData.php) $responseData: Contains the response data from the trigger request including body, headers, status code etc.
        - `$options`: The options defined in the rule for this strategy.
        - `$value`: The value/input that is being transferred through the strategy chain. For atomic strategies, this will be `null`. For composite strategies, this will be the output from the previous strategy in the chain.
- If you create a composite strategy which chains other atomic strategies, make sure you added `$chain` array with related `options` if needed.
    - For the composite strategies, make sure you call `chainExecutor` method with the params including `$chain`, [`$requestData`](src/Data/RequestData.php) and [`$responseData`](src/Data/ResponseData.php) to execute the chained strategies in order.
- Your strategy is ready to use in your prefetching rules! Feel free to **contribute** useful strategies back to the project.
- Check the existing [`Strategies`](src/Resolvers/Strategies) for reference.

### Clearing The Cache

For clearing the Demon Dextral Horn created cache, you can use the flowing sample command [`ResponseCacheCommand`](src/Commands/ResponseCacheCommand.php):

```bash
php artisan demon-dextral-horn:response-cache --tags=user_profile --tags=posts_index
```

Or you can clear all the cached responses created by **Demon Dextral Horn**:

```bash
php artisan demon-dextral-horn:response-cache --all
```

You can specify one or more tags to clear only certain cached responses created by **Demon Dextral Horn**.  
Tags are automatically generated from the target route names (normalized, e.g. `user.show` → `user_show`), and each tag is prefixed with the value defined in `defaults.prefetch_prefix` (default: `demon_dextral_horn`).

In addition, every cached response is always tagged with the default prefix tag alone (`defaults.prefetch_prefix`).

> [!NOTE]
> This command is safe to use because all cached responses created by **Demon Dextral Horn** are automatically tagged with a default prefix (config `defaults.prefetch_prefix`, default is `demon_dextral_horn`).  
> As a result, clearing cache through this command will not affect any other cache entries created outside of **Demon Dextral Horn**.

> [!TIP]
> Check [`CacheTagGenerator`](src/Cache/CacheTagGenerator.php) to see how the tags are generated for the cached responses.

You can also clear the cache programmatically if needed by using the Facade [`ResponseCache`](src/Facades/ResponseCache.php):

```php
use DemonDextralHorn\Facades\ResponseCache;

// Clear specific cached responses by tags
$tags = ['user_profile', 'posts_index'];

ResponseCache::clear($tags);

// or clear all cached responses by using the default prefix tag
$tags = [config('demon-dextral-horn.defaults.prefetch_prefix')];

ResponseCache::clear($tags);
```

### Monitoring and Debugging Through Events
There are several events can be used to monitor and debug the Demon Dextral Horn's operations:
- [`DemonDextralHornJobFailedEvent`](src/Events/DemonDextralHornJobFailedEvent.php): Fired when a prefetch job is failed.
- [`NonCacheableResponseEvent`](src/Events/NonCacheableResponseEvent.php): Fired when a response is not cacheable (e.g. exceeds max size). (See [`ResponseCacheValidator`](src/Validators/ResponseCacheValidator.php) for details about validation rules.)
- [`ResponseCachedEvent`](src/Events/ResponseCachedEvent.php): Fired when a response is successfully cached.
- [`RouteDispatchFailedEvent`](src/Events/RouteDispatchFailedEvent.php): Fired when dispatching a target route fails.
- [`TaggedCacheClearFailedEvent`](src/Events/TaggedCacheClearFailedEvent.php): Fired when clearing tagged cache fails (in case the driver doesn't support tags e.g. file, database etc. does not support tags, while redis, memcached etc. support tags).

## Troubleshooting

### Solutions & Checks
1. Middleware & Route Names
    - Ensure that `PrefetchTriggerMiddleware` is added to all trigger routes.
    - Ensure that `PrefetchCacheMiddleware` is added to all target routes.
    - Verify that the route names in your prefetching rules match the actual route names defined in your Laravel application.
    - Make sure you use **route names**, not URLs in the rules. (e.g. use `posts.index` instead of `/posts`)
2. Queue Workers
    - Run worker for prefetch queue and check for failed jobs:
    ```bash
    php artisan queue:work --queue=prefetch
    php artisan queue:failed
    ```

3. Cache Store & Tags
    - Ensure that the cache store defined in `defaults.cache_store` is properly configured and operational.
    - Verify that the cache driver supports tags (e.g., Redis, Memcached). If using a driver that does not support tags (e.g., file, database), clearing cache by tags will not work.
    - Check if the cached responses are being created by inspecting the cache store directly (e.g., using Redis CLI).

    ```bash
    > redis-cli
    > SELECT <your_redis_db_index_for_caching>
    > KEYS *

    > redis-cli --scan --pattern "demon_dextral_horn*"
    ```

4. Response Cacheability
    - Responses exceeding config `defaults.cache_max_size` are skipped.
    - Monitor `NonCacheableResponseEvent` to identify non-cacheable responses and understand why they were skipped.

5. Logs & Job Errors
    - Check Laravel logs for any errors related to the prefetching process.
    - Monitor `DemonDextralHornJobFailedEvent` to capture and log job failures.
    - If using a logging service (e.g., Sentry), again look for Demon Dextral Horn related errors.

6. Configuration & Environment
    - Ensure that `demon-dextral-horn.php` config file is published and properly set up.
    - Verify that the `defaults.enabled` flag is set to `true`.
    - Check that the `defaults.auth_driver` matches your application's authentication method (`session` or `jwt`).
    - Ensure that the `defaults.queue_connection` is set to a valid queue connection configured in your application.
    - Ensure that the `defaults.cache_store` is set to a valid cache store configured in your application.
    - Make sure your environment (e.g., local, staging, production) is correctly set up to use queues and caching.

## TODO
> - [ ] **Signalling Route**: A specialized endpoint that allows clients to signal the backend to prepare results proactively, without waiting for actual trigger routes.
For example, a client could call this endpoint when a user hovers over a link or focuses on an input field, indicating an intent to navigate or submit data soon. The backend can then prefetch and cache the relevant data in anticipation of the user's action.
> - [ ] **New middleware**: Log user activities to enable dynamic, personalized rules using Markov Chain predictions.
> - [ ] **Code cleanup**: Remove unnecessary docblocks and comments, no need to repeat what is already clear from the code itself.

## Contributing

> **Your contributions are welcome!** If you'd like to improve this project, simply create a pull request with your changes. Your efforts help enhance its functionality and documentation.

> If you find this project useful, please consider ⭐ it to show your support!

## Authors
This project is created and maintained by [Moe Mizrak](https://github.com/moe-mizrak) with contributions from the [Open Source Community](https://github.com/laplace-demon-ai/demon-dextral-horn/graphs/contributors).

## License
Demon Dextral Horn is an open-sourced software licensed under the **[MIT license](LICENSE)**.