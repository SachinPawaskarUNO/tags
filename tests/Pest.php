<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use spawaskar\Tags\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

// Switch a test to advanced mode. The test tables are created in simple mode, so this runs the
// package migration again in advanced mode, for its icon and model_type columns and its index.
function useAdvancedMode(): void
{
    config(['tags.mode' => 'advanced']);

    $migration = require __DIR__.'/../database/migrations/2026_01_01_000000_create_tags_table.php';
    $migration->down();
    $migration->up();
}

// Turn on teams. Like useAdvancedMode(), this runs the package migration again, for the team_id
// column, and gives the fixture model a team_id column for tags to take their team from.
function useTeams(): void
{
    config(['tags.teams' => true]);

    $migration = require __DIR__.'/../database/migrations/2026_01_01_000000_create_tags_table.php';
    $migration->down();
    $migration->up();

    Schema::table('test_models', fn (Blueprint $table) => $table->foreignId('team_id')->nullable());
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
