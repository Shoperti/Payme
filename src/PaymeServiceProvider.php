<?php

namespace Dinkbit\PayMe;

use Illuminate\Support\ServiceProvider;

class PaymeServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bindShared('Dinkbit\PayMe\Contracts\Factory', function ($app) {
            return new PayMeManager($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['Dinkbit\PayMe\Contracts\Factory'];
    }
}
