<?php

namespace joey\abac\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class UninstallModelsCommand extends Command
{
    protected $signature = 'abac:uninstall';
    protected $description = 'Remove ABAC models from the application directory';

    public function handle(Filesystem $filesystem)
    {
        $this->info('Uninstalling ABAC models...');

        $models = [
            'Account.php',
            'UserAccount.php',
            'AssignedPermission.php',
            'Permission.php',
            'PermissionCategory.php',
            'Role.php',
            'UserRole.php'
        ];



        $destinationPath = app_path('Models');

        foreach ($models as $model) {
            $filePath = $destinationPath . '/' . $model;

            if ($filesystem->exists($filePath)) {
                $filesystem->delete($filePath);
                $this->info("Deleted: $model");
            } else {
                $this->warn("File not found: $model");
            }
        }

        $controllers = [
            'UserAccountController.php',
            'ApiAuthentication.php',
            'PermissionsController.php',
        ];

        $destinationPath = app_path('Http/Controllers');

        foreach ($controllers as $controller) {
            $filePath = $destinationPath . '/' . $controller;

            if ($filesystem->exists($filePath)) {
                $filesystem->delete($filePath);
                $this->info("Deleted: $controller");
            } else {
                $this->warn("File not found: $controller");
            }
        }


        $this->info('ABAC models have been removed from app/Models/.');
    }
}
