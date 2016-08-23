<?php

namespace Larawos\Generators;

use Illuminate\Support\ServiceProvider;

class GeneratorsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerModuleGenerator();
    }

    protected function registerModuleGenerator()
    {
        $this->app->singleton('command.larawos.module', function ($app) {
            return $app['Larawos\Generators\Command\ModuleMakeCommands'];
        });

        $this->commands('command.larawos.module');
    }
}
