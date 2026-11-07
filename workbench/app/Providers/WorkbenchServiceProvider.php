<?php

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;
use Workbench\App\Models\Post;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Try the package in advanced mode. Set before the migrations run, since the tags
        // migration adds the icon and model_type columns in advanced mode.
        config([
            'tags.mode' => 'advanced',
            'tags.models' => [Post::class],
            'tags.manager.enabled' => true,
            'tags.manager.path' => 'admin/tags',
        ]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
