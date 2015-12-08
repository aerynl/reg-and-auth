<?php

namespace Aerynl\RegAuth;

use Illuminate\Support\ServiceProvider;

class RegAuthServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish migrations
        $this->publishes([__DIR__ . '/../migrations' => base_path('database/migrations')]);
        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'regauth');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app['regauth'] = $this->app->share(function($app){
            return new RegAuth();
        });
    }
}
