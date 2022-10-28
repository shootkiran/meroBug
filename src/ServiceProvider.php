<?php

namespace MeroBug;

use Monolog\Logger;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function boot()
    {
        if (function_exists('config_path')) {
            $this->publishes([
                __DIR__ . '/../config/merobug.php' => config_path('merobug.php'),
            ]);
        }
        $this->app['view']->addNamespace('merobug', __DIR__ . '/../resources/views');
        if (class_exists(\Illuminate\Foundation\AliasLoader::class)) {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('MeroBug', 'MeroBug\Facade');
        }
        $this->loadMigrationsFrom(__DIR__.'/../migrations');
    }
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
}