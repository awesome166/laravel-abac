<?php

namespace awesome166\abac\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class RouteCommand extends Command
{
    protected $signature = 'abac:publish-routes';

    protected $description = 'Inject custom routes into web.php and api.php files';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $filesystem = new Filesystem();



        // Define the routes to be injected
        $routes = <<<'EOD'
          use App\Http\Controllers\AccountUserController;
          use App\Http\Controllers\PermissionsController;
          use App\Http\Controllers\ApiAuthentication;
          Route::prefix('abac')->group(function () {
            // Permissions
            Route::apiResource('permissions', PermissionsController::class);
            // Assignments
            Route::post('assign', [PermissionsController::class, 'assign']);
            Route::put('assignments/{assignment}', [PermissionsController::class, 'updateAssignment']);
            Route::delete('assignments/{assignment}', [PermissionsController::class, 'removeAssignment']);
            // Roles
            Route::apiResource('roles', PermissionsController::class)->only(['index', 'store']);
            Route::post('users/{user}/roles', [PermissionsController::class, 'assignRole']);
            // User Permissions
            Route::get('users/{user}/permissions', [PermissionsController::class, 'getUserPermissions']);
            // Account Permissions
            Route::post('accounts/{account}/permissions', [PermissionsController::class, 'syncAccountPermissions']);
            // Access Check
            Route::post('users/{user}/can-access', [PermissionsController::class, 'canAccess']);
          });

          Route::prefix('account-users')->group(function () {
            Route::post('/attach', [AccountUserController::class, 'attachUser']);
            Route::delete('/{account}/{user}', [AccountUserController::class, 'detachUser']);
            Route::post('/set-primary', [AccountUserController::class, 'setPrimaryAccount']);
            Route::get('/{user}', [AccountUserController::class, 'getUserAccounts']);
          });

          Route::post('register', [ApiAuthentication::class, 'register']);
          Route::post('verify-otp', [ApiAuthentication::class, 'verifyOtp']);
          Route::post('login', [ApiAuthentication::class, 'login']);
          Route::post('logout', [ApiAuthentication::class, 'logout'])->middleware('auth:api');
          Route::post('forgot-password', [ApiAuthentication::class, 'forgotPassword']);
          Route::post('reset-password', [ApiAuthentication::class, 'resetPassword']);
          EOD;

        // Inject routes into web.php
        $webPath = base_path('routes/web.php');
        $this->injectRoutes($filesystem, $webPath, $routes);

        // Inject routes into api.php
        $apiPath = base_path('routes/api.php');
        $this->injectRoutes($filesystem, $apiPath, $routes);

        $this->info('Routes injected successfully.');
    }

    private function injectRoutes(Filesystem $filesystem, $filePath, $routes)
    {
        if ($filesystem->exists($filePath)) {
            // $filesystem->append($filePath, $routes);
            $content = $filesystem->get($filePath);
            $content .= "\n\n" . $routes;
            $filesystem->put($filePath, $content);
        }
    }
}

