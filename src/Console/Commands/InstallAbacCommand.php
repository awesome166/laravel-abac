<?php

namespace joey\abac\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;


class InstallAbacCommand extends Command
{
    protected $signature = 'abac:install';
    protected $description = 'Install Access Base Access Control package components';

    public function handle()
    {




        $this->info('Installing ABAC package...');
        $this->info('Publishing configuration...');
        $this->call('vendor:publish', ['--tag' => 'abac-config']);
        $this->info('Running migrations...');
        $this->call('migrate');
        $this->info('Seeding database...');


        $this->publishAndRunSeeders();

        $this->call('abac:publish-controllers');
        $this->call('abac:publish-models');
        $this->call('abac:publish-routes');
        $this->info('ABAC package installed successfully.');


    }


    protected function publishAndRunSeeders()
    {
        // Get package seeders path
        $packageSeederPath = __DIR__.'/../../../database/seeders/AbacDatabaseSeeder.php';

        // Local seeders path
        $localSeederPath = database_path('seeders/AbacDatabaseSeeder.php');

        // Copy seeder file if not exists
        if (!File::exists($localSeederPath)) {
            File::copy($packageSeederPath, $localSeederPath);
        }

        try {
            // Run the seeder using fully qualified namespace
            $this->call('db:seed', [
                '--class' => 'Database\Seeders\AbacDatabaseSeeder'
            ]);
        } catch (\Exception $e) {
            $this->warn('An error occurred while seeding: ' . $e->getMessage());
        }
    }
}