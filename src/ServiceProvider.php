<?php

namespace MeroBug;

use Monolog\Logger;
use MeroBug\Commands\TestCommand;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        // Publish configuration file
        if (function_exists('config_path')) {
            $this->publishes([
                __DIR__ . '/../config/merobug.php' => config_path('merobug.php'),
            ]);
        }

        // Register views
        $this->app['view']->addNamespace('merobug', __DIR__ . '/../resources/views');

        // Register facade
        if (class_exists(\Illuminate\Foundation\AliasLoader::class)) {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('MeroBug', 'MeroBug\Facade');
        }

        // Map any routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../migrations');

    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/merobug.php', 'merobug');

        $this->app->singleton('merobug', function ($app) {
            return new MeroBug();
        });

        if ($this->app['log'] instanceof \Illuminate\Log\LogManager) {
            $this->app['log']->extend('merobug', function ($app, $config) {
                $handler = new \MeroBug\Logger\MeroBugHandler(
                    $app['merobug']
                );

                return new Logger('merobug', [$handler]);
            });
        }
    }

    protected function mapMeroBugApiRoutes()
    {
        Route::group(
            [
                'namespace' => '\MeroBug\Http\Controllers',
                'prefix' => 'merobug'
            ],
            function ($router) {
                require __DIR__ . '/../routes/web.php';
            }
        );
    }
}
