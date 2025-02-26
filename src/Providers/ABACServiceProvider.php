<?php

namespace joey\abac\Providers;


use Illuminate\Support\ServiceProvider;
use joey\abac\Models\Role;
use Illuminate\Database\Eloquent\Relations\Relation;
use joey\abac\Console\Commands\InstallAbacCommand;
use joey\abac\Console\Commands\PublishModelsCommand;
use joey\abac\Console\Commands\PublishControllersCommand;
use joey\abac\Console\Commands\RouteCommand;
use joey\abac\Console\Commands\UninstallModelsCommand;


class ABACServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Relation::morphMap([
            'user' => config('abac.user_model', App\Models\User::class),
            'role' => Role::class
        ]);

        $this->publishes([
            __DIR__.'/../../config/abac.php' => config_path('abac.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../../database/seeders/AbacDatabaseSeeder.php' => database_path('seeders/AbacDatabaseSeeder.php'),
        ], 'abac-seeders');

        $this->publishes([
            __DIR__.'/../../Models' => app_path('Models'),
        ], 'abac-models');

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallAbacCommand::class,
                PublishModelsCommand::class,
                PublishControllersCommand::class,
                RouteCommand::class,
                UninstallModelsCommand::class
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/abac.php', 'abac'
        );
    }
}