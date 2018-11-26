<?php

namespace Stylemix\Listing;

use Illuminate\Support\ServiceProvider as BaseProvider;

class ServiceProvider extends BaseProvider
{

    /**
     * Register IoC bindings.
     */
    public function register()
    {
        // Bind the manager as a singleton on the container.
        $this->app->singleton('Stylemix\Listing\EntityManager', function ($app) {
            return EntityManager::getInstance();
        });
    }

    /**
     * Boot the package.
     */
    public function boot()
    {

    }

    /**
     * Which IoC bindings the provider provides.
     *
     * @return array
     */
    public function provides()
    {
        return array(
            'Stylemix\Listing\EntityManager',
        );
    }
}
