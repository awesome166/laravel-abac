<?php

namespace awesome166\abac\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class PublishModelsCommand extends Command
{
    protected $signature = 'abac:publish-models
                            {--force : Overwrite existing files}';
    protected $description = 'Publish ABAC models to application models directory';

    public function handle(Filesystem $filesystem)
    {
        $this->info('Publishing ABAC models...');

        $models = [
            'Account',
            'UserAccount',
            'AssignedPermission',
            'Permission',
            'PermissionCategory',
            'Role',
            'UserRole'
        ];

        $sourcePath = base_path('vendor/awesome166/abac/src/Models');
        $destinationPath = app_path('Models');

        $filesystem->ensureDirectoryExists($destinationPath, 0755, true, true);

        foreach ($models as $model) {
            $source = $sourcePath . '/' . $model . '.php';
            $destination = $destinationPath . '/' . $model . '.php';

            if (!$filesystem->exists($source)) {
                $this->error("Model not found: {$model}.php");
                continue;
            }

            $content = $filesystem->get($source);

            // Convert namespace
            $content = preg_replace(
                '/namespace\s+awesome166\\\\abac\\\\Models;/',
                'namespace App\Models;',
                $content
            );

            // Convert model references in the code
            $content = str_replace(
                'awesome166\\abac\\Models\\',
                'App\\Models\\',
                $content
            );

            // Convert class PHPDoc annotations
            $content = str_replace(
                '* @see awesome166\\\abac\\\Models\\',
                '* @see App\\\Models\\',
                $content
            );

            // Only write file if it doesn't exist or --force is used
            if (!$filesystem->exists($destination) || $this->option('force')) {
                $filesystem->put($destination, $content);
                $this->info("Published: {$model}.php");
            } else {
                $this->error("File exists: {$model}.php (use --force to overwrite)");
            }
        }

        $userModelPath = app_path('Models/User.php');

        if ($filesystem->isWritable($userModelPath)) {
            $content = $filesystem->get($userModelPath);

            // Inject the use statement at the beginning of the class definition
            $content = preg_replace(
                '/class User extends Authenticatable/',
                'use App\Traits\HasPermission;' . PHP_EOL . 'class User extends Authenticatable',
                $content,
                1
            );


            $injectionCode = <<<'EOD'
                protected static function boot()
                {
                    parent::boot();

                    static::created(function ($user) {
                        // Assign to default user account
                        $userAccount = Account::where('type', 'user')->first();
                        $userRole = Role::where('name', 'Customer')->first();

                        $user->update(['account_id' => $userAccount->id]);
                        $user->roles()->attach($userRole);
                    });
                }

            EOD;

            // Inject the code at the end of the class definition
            $lastBracePosition = strrpos($content, '}');
            if ($lastBracePosition !== false) {
                $content = substr_replace($content, $injectionCode . '}', $lastBracePosition);
                $filesystem->put($userModelPath, $content);
                $this->info('Code injected into User model successfully.');
            }
        } else {
            $this->error("Cannot write to User.php. Please ensure the file is writable or manually add the following code:");
            $this->line('---');
            $this->line('protected static function boot()');
            $this->line('{');
            $this->line('    parent::boot();');
            $this->line('');
            $this->line('    static::created(function ($user) {');
            $this->line('        // Assign to default user account');
            $this->line('        $userAccount = Account::where(\'type\', \'user\')->first();');
            $this->line('        $userRole = Role::where(\'name\', \'Customer\')->first();');
            $this->line('');
            $this->line('        $user->update([\'account_id\' => $userAccount->id]);');
            $this->line('        $user->roles()->attach($userRole);');
            $this->line('    });');
            $this->line('}');
            $this->line('---');
            $this->line('For more details, please refer to the documentation.');
        }


        $this->line('');
        $this->info('Models published successfully!');
        $this->line(' Run: <comment>composer dump-autoload</comment> to refresh autoloader');
    }
}
