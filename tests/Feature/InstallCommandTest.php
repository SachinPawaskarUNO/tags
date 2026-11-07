<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use spawaskar\Tags\Tests\Fixtures\TestModel;

const MODE_QUESTION = 'Which mode do you want? Advanced adds any color, icons, descriptions, and tags per model.';
const TEAMS_QUESTION = 'Does your application need to support access across different teams?';
const TEAM_COLUMN_QUESTION = 'Which attribute on tagged records holds their team?';
const MODELS_QUESTION = 'Which models will have tags? Separate them with commas.';
const ICONS_QUESTION = 'Which icons can users pick? All of the Lucide icons, or only a list you choose?';
const ICON_LIST_QUESTION = 'Which icons? Separate them with commas.';
const MANAGER_QUESTION = 'Turn on the tag manager page?';
const PATH_QUESTION = 'What URL should the tag manager page be at?';
const MIDDLEWARE_QUESTION = 'Which middleware should protect it? Separate them with commas.';
const COMPONENT_QUESTION = 'Which Inertia page component should it render?';
const USER_TEAM_QUESTION = 'Which attribute on the logged-in user holds their current team?';
const MIGRATE_QUESTION = 'Create the tags tables now?';

/**
 * The tags migration copies published into the Testbench app.
 *
 * @return list<string>
 */
function publishedTagsMigrations(): array
{
    return glob(database_path('migrations/*_create_tags_table.php')) ?: [];
}

// The command publishes into the Testbench app, which is shared between tests.
function removePublishedTagsFiles(): void
{
    File::delete([config_path('tags.php'), ...publishedTagsMigrations()]);
}

// As if the app has never run the tags migration.
function dropTagsTables(): void
{
    Schema::dropIfExists('taggables');
    Schema::dropIfExists('tags');
    DB::table('migrations')->delete();
}

beforeEach(fn () => removePublishedTagsFiles());
afterEach(fn () => removePublishedTagsFiles());

it('saves every answer and creates the matching columns in advanced mode with teams and the tag manager', function () {
    // Arrange
    dropTagsTables();

    // Act
    $this->artisan('tags:install')
        ->expectsQuestion(MODE_QUESTION, 'advanced')
        ->expectsConfirmation(TEAMS_QUESTION, 'yes')
        ->expectsQuestion(TEAM_COLUMN_QUESTION, 'owner_team')
        ->expectsQuestion(MODELS_QUESTION, '\\'.TestModel::class)
        ->expectsQuestion(ICONS_QUESTION, 'list')
        ->expectsQuestion(ICON_LIST_QUESTION, 'star, flag, map-pin')
        ->expectsConfirmation(MANAGER_QUESTION, 'yes')
        ->expectsQuestion(PATH_QUESTION, '/admin/labels/')
        ->expectsQuestion(MIDDLEWARE_QUESTION, 'web, auth, throttle:60,1')
        ->expectsQuestion(COMPONENT_QUESTION, 'tags/Custom')
        ->expectsQuestion(USER_TEAM_QUESTION, 'team_id')
        ->expectsConfirmation(MIGRATE_QUESTION, 'yes')
        ->assertSuccessful();

    // Assert
    $config = require config_path('tags.php');

    expect($config['mode'])->toBe('advanced')
        ->and($config['teams'])->toBeTrue()
        ->and($config['team_column'])->toBe('owner_team')
        ->and($config['models'])->toBe([TestModel::class])
        ->and($config['icons'])->toBe(['star', 'flag', 'map-pin'])
        ->and($config['manager']['enabled'])->toBeTrue()
        ->and($config['manager']['path'])->toBe('admin/labels')
        ->and($config['manager']['middleware'])->toBe(['web', 'auth', 'throttle:60,1'])
        ->and($config['manager']['component'])->toBe('tags/Custom')
        ->and($config['manager']['team'])->toBe('team_id')
        ->and(publishedTagsMigrations())->toHaveCount(1)
        ->and(Schema::hasColumn('tags', 'model_type'))->toBeTrue()
        ->and(Schema::hasColumn('tags', 'team_id'))->toBeTrue()
        ->and(Schema::hasTable('taggables'))->toBeTrue();
});

it('only asks about the mode and teams in simple mode without teams', function () {
    // Act
    $this->artisan('tags:install')
        ->expectsQuestion(MODE_QUESTION, 'simple')
        ->expectsConfirmation(TEAMS_QUESTION, 'no')
        ->assertSuccessful();

    // Assert
    $config = require config_path('tags.php');

    expect($config['mode'])->toBe('simple')
        ->and($config['teams'])->toBeFalse()
        ->and($config['manager']['enabled'])->toBeFalse();
});

it('also asks for the team column in simple mode with teams', function () {
    // Act
    $this->artisan('tags:install')
        ->expectsQuestion(MODE_QUESTION, 'simple')
        ->expectsConfirmation(TEAMS_QUESTION, 'yes')
        ->expectsQuestion(TEAM_COLUMN_QUESTION, 'owner_team')
        ->assertSuccessful();

    // Assert
    $config = require config_path('tags.php');

    expect($config['teams'])->toBeTrue()
        ->and($config['team_column'])->toBe('owner_team')
        ->and($config['manager']['enabled'])->toBeFalse();
});

it('keeps the settings it does not ask about in a config the app already published', function () {
    // Arrange
    $this->artisan('vendor:publish', ['--tag' => 'tags-config']);
    File::replaceInFile("'path' => 'tags'", "'path' => 'my/labels'", config_path('tags.php'));

    // Act
    $this->artisan('tags:install')
        ->expectsQuestion(MODE_QUESTION, 'simple')
        ->expectsConfirmation(TEAMS_QUESTION, 'no')
        ->assertSuccessful();

    // Assert
    expect((require config_path('tags.php'))['manager']['path'])->toBe('my/labels');
});

it('leaves a setting that uses env() alone and says what to set by hand', function () {
    // Arrange
    $this->artisan('vendor:publish', ['--tag' => 'tags-config']);
    File::replaceInFile("'mode' => 'simple'", "'mode' => env('TAGS_MODE', 'simple')", config_path('tags.php'));

    // Act & Assert
    $this->artisan('tags:install')
        ->expectsQuestion(MODE_QUESTION, 'simple')
        ->expectsConfirmation(TEAMS_QUESTION, 'no')
        ->expectsOutputToContain('Set it to')
        ->assertSuccessful();

    expect(file_get_contents(config_path('tags.php')))->toContain("env('TAGS_MODE', 'simple')");
});

it('publishes one copy of the migration over two runs and does not migrate when answered no', function () {
    // Arrange
    dropTagsTables();

    // Act
    foreach ([1, 2] as $run) {
        $this->artisan('tags:install')
            ->expectsQuestion(MODE_QUESTION, 'simple')
            ->expectsConfirmation(TEAMS_QUESTION, 'no')
            ->expectsConfirmation(MIGRATE_QUESTION, 'no')
            ->assertSuccessful()
            ->run();
    }

    // Assert
    expect(publishedTagsMigrations())->toHaveCount(1)
        ->and(Schema::hasTable('tags'))->toBeFalse();
});

it('does not warn when the existing tables match the answers', function () {
    // The test database has the simple mode tables, without teams.
    $this->artisan('tags:install')
        ->expectsQuestion(MODE_QUESTION, 'simple')
        ->expectsConfirmation(TEAMS_QUESTION, 'no')
        ->doesntExpectOutputToContain("doesn't match")
        ->assertSuccessful();
});

it('warns when the existing tables do not match the answers', function () {
    // The test database has the simple mode tables, without teams.
    $this->artisan('tags:install')
        ->expectsQuestion(MODE_QUESTION, 'simple')
        ->expectsConfirmation(TEAMS_QUESTION, 'yes')
        ->expectsQuestion(TEAM_COLUMN_QUESTION, 'team_id')
        ->expectsOutputToContain("doesn't match")
        ->assertSuccessful();
});

it('rejects a models answer with a class that is not an Eloquent model', function () {
    $this->artisan('tags:install')
        ->expectsQuestion(MODE_QUESTION, 'advanced')
        ->expectsConfirmation(TEAMS_QUESTION, 'no')
        ->expectsQuestion(MODELS_QUESTION, 'App\Nope')
        ->expectsOutputToContain("[App\Nope] isn't an Eloquent model.")
        ->assertFailed();
});

it('rejects an icon name that is not in Lucide\'s format', function () {
    $this->artisan('tags:install')
        ->expectsQuestion(MODE_QUESTION, 'advanced')
        ->expectsConfirmation(TEAMS_QUESTION, 'no')
        ->expectsQuestion(MODELS_QUESTION, '')
        ->expectsQuestion(ICONS_QUESTION, 'list')
        ->expectsQuestion(ICON_LIST_QUESTION, 'Bad Name')
        ->expectsOutputToContain("[Bad Name] isn't an icon name")
        ->assertFailed();
});
