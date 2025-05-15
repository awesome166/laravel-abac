<?php

namespace awesome166\abac\Providers;


use Illuminate\Support\ServiceProvider;
use awesome166\abac\Models\Role;
use Illuminate\Database\Eloquent\Relations\Relation;
use awesome166\abac\Console\Commands\InstallAbacCommand;
use awesome166\abac\Console\Commands\PublishModelsCommand;
use awesome166\abac\Console\Commands\PublishControllersCommand;
use awesome166\abac\Console\Commands\RouteCommand;
use awesome166\abac\Console\Commands\UninstallModelsCommand;


class ABACServiceProvider extends ServiceProvider
{
    public function boot()
    {

        // Auto-load migrations (no publishing required)
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');

        // Publish config
        // $this->publishes([
        //     __DIR__ . '/Config/package.php' => config_path('your-package.php'),
        // ], 'config');

        // Publish models
        $this->publishes([
            __DIR__ . '/Models' => app_path('Models'),
        ], 'models');

        // Publish migrations (optional, if users need to modify them)
        $this->publishes([
            __DIR__ . '/Database/Migrations' => database_path('migrations'),
        ], 'migrations');

        // Publish controllers
        $this->publishes([
            __DIR__ . '/Http/Controllers' => app_path('Http/Controllers'),
        ], 'controllers');

        // Publish middleware
        $this->publishes([
            __DIR__ . '/Http/Middleware' => app_path('Http/Middleware'),
        ], 'middleware');

        // Publish commands
        $this->publishes([
            __DIR__ . '/Console/Commands' => app_path('Console/Commands'),
        ], 'commands');

        $this->publishes([
            __DIR__ . '/../Config/abac.php' => config_path('abac.php'),
        ], 'config');

        Relation::morphMap([
            'user' => config('abac.user_model', App\Models\User::class),
            'role' => Role::class
        ]);

        // $this->publishes([
        //     __DIR__.'/../../config/abac.php' => config_path('abac.php'),
        // ], 'config');

        // $this->publishes([
        //     __DIR__.'/../../database/seeders/AbacDatabaseSeeder.php' => database_path('seeders/AbacDatabaseSeeder.php'),
        // ], 'abac-seeders');

        // $this->publishes([
        //     __DIR__.'/../../Models' => app_path('Models'),
        // ], 'abac-models');

        // $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallAbacCommand::class,
                PublishModelsCommand::class,
                // PublishControllersCommand::class,
                RouteCommand::class,
                UninstallModelsCommand::class
            ]);
        }
    }

    public function register()
    {


        // Merge config
        // $this->mergeConfigFrom(
        //     __DIR__ . '/config/abac.php', 'abac'
        // );

        // Register middleware (optional)
        $this->app['router']->aliasMiddleware(
            'your-middleware', \awesome166\abac\Http\Middleware\CheckABAC::class
        );

    }
}