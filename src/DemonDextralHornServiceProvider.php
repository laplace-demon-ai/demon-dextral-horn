<?php

declare(strict_types=1);

namespace DemonDextralHorn;

use Illuminate\Support\ServiceProvider;
use DemonDextralHorn\Handlers\TargetRouteHandler;
use DemonDextralHorn\Resolvers\RouteParamResolver;
use DemonDextralHorn\Resolvers\QueryParamResolver;
use DemonDextralHorn\Resolvers\HeadersResolver;
use DemonDextralHorn\Resolvers\TargetRouteResolver;
use DemonDextralHorn\Resolvers\Contracts\TargetRouteResolverInterface;
use DemonDextralHorn\Routing\RouteDispatcher;
use DemonDextralHorn\Factories\StrategyFactory;

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
        $this->app->singleton('demon-dextral-horn', function ($app) {
            return new TargetRouteHandler(
                $app->make(RouteParamResolver::class),
                $app->make(QueryParamResolver::class),
                $app->make(HeadersResolver::class),
                $app->make(RouteDispatcher::class),
            );
        });

        // Register the strategy factory
        $this->app->singleton(StrategyFactory::class);
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