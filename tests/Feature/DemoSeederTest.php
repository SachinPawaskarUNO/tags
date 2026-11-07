<?php

use spawaskar\Tags\Database\Seeders\TagsDemoSeeder;
use spawaskar\Tags\Models\Tag;
use spawaskar\Tags\Tests\Fixtures\TestModel;

it('attaches the demo tags in a fixed rotation and adds nothing new on a second run', function () {
    // Arrange
    config(['tags.models' => [TestModel::class]]);
    $first = TestModel::create(['name' => 'First']);
    $second = TestModel::create(['name' => 'Second']);

    // Act
    (new TagsDemoSeeder)->run();
    (new TagsDemoSeeder)->run();

    // Assert
    expect($first->tags()->pluck('name')->all())->toBe(['urgent'])
        ->and($second->tags()->pluck('name')->all())->toEqualCanonicalizing(['reviewed', 'archived'])
        ->and(Tag::count())->toBe(count(config('tags.demo_tags')));
});

it('does not add anything in production', function () {
    // Arrange
    app()->detectEnvironment(fn () => 'production');
    config(['tags.models' => [TestModel::class]]);
    TestModel::create(['name' => 'Example']);

    // Act
    $this->artisan('db:seed', ['--class' => TagsDemoSeeder::class, '--force' => true])
        ->expectsOutputToContain('does not run in production')
        ->run();

    // Back to testing, since rolling back the test's migrations asks for confirmation in production.
    app()->detectEnvironment(fn () => 'testing');

    // Assert
    expect(Tag::count())->toBe(0);
});

it('gives each tag its color, icon, and the model of its record in advanced mode', function () {
    // Arrange
    useAdvancedMode();
    config(['tags.models' => [TestModel::class]]);
    TestModel::create(['name' => 'Example']);

    // Act
    (new TagsDemoSeeder)->run();

    // Assert
    expect(Tag::first()->model_type)->toBe((new TestModel)->getMorphClass())
        ->and(Tag::first()->color)->toBe('red')
        ->and(Tag::first()->icon)->toBe('flag');
});

it('keeps a color the tag already has', function () {
    // Arrange
    config(['tags.models' => [TestModel::class]]);
    Tag::findOrCreateByName('urgent')->update(['color' => 'purple']);
    TestModel::create(['name' => 'Example']);

    // Act
    (new TagsDemoSeeder)->run();

    // Assert
    expect(Tag::where('name', 'urgent')->first()->color)->toBe('purple');
});

it('only tags the given team\'s records and skips records without a team', function () {
    // Arrange
    useTeams();
    config(['tags.models' => [TestModel::class]]);
    $mine = TestModel::create(['name' => 'Mine', 'team_id' => 1]);
    $other = TestModel::create(['name' => 'Other', 'team_id' => 2]);
    $none = TestModel::create(['name' => 'None']);

    // Act
    (new TagsDemoSeeder)->run(1);

    // Assert
    expect($mine->tags()->first()->team_id)->toBe(1)
        ->and($other->tags()->count())->toBe(0)
        ->and($none->tags()->count())->toBe(0);
});

it('creates the tags in simple mode and says no records were tagged when there are none', function () {
    // Act
    $this->artisan('db:seed', ['--class' => TagsDemoSeeder::class])
        ->expectsOutputToContain('No records were tagged')
        ->assertSuccessful();

    // Assert
    expect(Tag::pluck('color', 'name')->sortKeys()->all())
        ->toBe(collect(config('tags.demo_tags'))->map(fn (array $details) => $details['color'])->sortKeys()->all());
});
