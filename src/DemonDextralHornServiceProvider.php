<?php

declare(strict_types=1);

namespace DemonDextralHorn;

use Illuminate\Support\ServiceProvider;

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