<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use spawaskar\Tags\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

// Switch a test to advanced mode. The test tables are created in simple mode, so this adds
// the columns the migration only creates in advanced mode.
function useAdvancedMode(): void
{
    config(['tags.mode' => 'advanced']);

    Schema::table('tags', function (Blueprint $table): void {
        $table->string('icon')->nullable();
        $table->string('model_type')->nullable();
    });
}

// Turn on the tag manager page, at a path and middleware like an app might set. The package
// routes were loaded when the app booted, so this loads them again with the new config.
function enableTagManager(string $path = 'admin/tags'): void
{
    config([
        'tags.manager.enabled' => true,
        'tags.manager.path' => $path,
        'tags.manager.middleware' => ['web'],
    ]);

    require __DIR__.'/../routes/tags.php';

    Route::getRoutes()->refreshNameLookups();
}
