<?php

namespace awesome166\abac\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class PublishControllersCommand extends Command
{
    protected $signature = 'abac:publish-controllers';
    protected $description = 'Move controllers from the specified source directory to app/Http/Controllers';

    protected $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();
        $this->filesystem = $filesystem;
    }

    public function handle()
    {
        $sourcePath = base_path('vendor/awesome166/abac/src/Controllers');
        $destination = app_path('Http/Controllers');

        $this->info("Moving files from {$sourcePath} to {$destination}");

        if (!$this->filesystem->isDirectory($sourcePath)) {
            $this->error("Source directory does not exist.");
            return;
        }

        $files = $this->filesystem->allFiles($sourcePath);

        foreach ($files as $file) {
            $destinationPath = $destination . '/' . $file->getFilename();
            $this->filesystem->copy($file->getPathname(), $destinationPath);
            $this->info("Copied : {$file->getFilename()}");
        }

        $this->info("All files copied successfully.");
    }
}

