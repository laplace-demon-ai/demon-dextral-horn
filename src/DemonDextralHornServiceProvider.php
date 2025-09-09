<?php

declare(strict_types=1);

namespace DemonDextralHorn;

use DemonDextralHorn\Cache\CacheKeyGenerator;
use DemonDextralHorn\Cache\ResponseCache;
use DemonDextralHorn\Cache\ResponseCacheValidator;
use DemonDextralHorn\Cache\Identifiers\Contracts\UserIdentifierInterface;
use DemonDextralHorn\Cache\Identifiers\GuestUserIdentifier;
use DemonDextralHorn\Cache\Identifiers\JwtUserIdentifier;
use DemonDextralHorn\Cache\Identifiers\SessionUserIdentifier;
use DemonDextralHorn\Enums\AuthDriverType;
use DemonDextralHorn\Handlers\TargetRouteHandler;
use DemonDextralHorn\Resolvers\Contracts\TargetRouteResolverInterface;
use DemonDextralHorn\Resolvers\CookiesResolver;
use DemonDextralHorn\Resolvers\HeadersResolver;
use DemonDextralHorn\Resolvers\QueryParamResolver;
use DemonDextralHorn\Resolvers\RouteParamResolver;
use DemonDextralHorn\Resolvers\TargetRouteResolver;
use DemonDextralHorn\Routing\RouteDispatcher;
use Illuminate\Container\Container;
use Illuminate\Support\ServiceProvider;
use Spatie\ResponseCache\ResponseCacheRepository;

/**
 * Service provider for DemonDextralHorn.
 *
 * @class DemonDextralHornServiceProvider
 */
final class DemonDextralHornServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerPublishing();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->configure();

        // Bind the target route resolver interface to its implementation
        $this->app->bind(
            TargetRouteResolverInterface::class,
            TargetRouteResolver::class
        );

        // Register the facade for the TargetRouteHandler (DemonDextralHorn)
        $this->app->singleton('demon-dextral-horn', function (Container $app) {
            return new TargetRouteHandler(
                $app->make(RouteParamResolver::class),
                $app->make(QueryParamResolver::class),
                $app->make(HeadersResolver::class),
                $app->make(CookiesResolver::class),
                $app->make(RouteDispatcher::class),
                $app->make(ResponseCacheValidator::class),
            );
        });

        // Bind UserIdentifierInterface to specific identifier implementations based on the config
        $this->app->bind(UserIdentifierInterface::class, function (Container $app) {
            $driver = config('demon-dextral-horn.defaults.auth_driver');

            return match ($driver) {
                AuthDriverType::JWT->value => $app->make(JwtUserIdentifier::class),
                AuthDriverType::SESSION->value => $app->make(SessionUserIdentifier::class),
                default => $app->make(GuestUserIdentifier::class),
            };
        });

        // Register the facade for the ResponseCache, and bind the ResponseCacheRepository from Spatie's package, and CacheKeyGenerator
        $this->app->singleton('response-cache', function (Container $app) {
            return new ResponseCache(
                $app->make(ResponseCacheRepository::class),
                $app->make(CacheKeyGenerator::class)
            );
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return ['demon-dextral-horn'];
    }

    /**
     * Setup the configuration.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/demon-dextral-horn.php', 'demon-dextral-horn'
        );
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/demon-dextral-horn.php' => config_path('demon-dextral-horn.php'),
            ], 'demon-dextral-horn');
        }
    }
}
