<?php

use spawaskar\Tags\Facades\Tags;
use spawaskar\Tags\Models\Tag;
use spawaskar\Tags\Tests\Fixtures\TestModel;

it('can attach a tag to a model', function () {
    // Arrange
    $model = TestModel::create(['name' => 'Example']);

    // Act
    $model->attachTags('first-tag');

    // Assert
    expect($model->tags)->toHaveCount(1)
        ->and($model->tags->first()->name)->toBe('first-tag');
});

it('can detach a tag from a model', function () {
    // Arrange
    $model = TestModel::create(['name' => 'Example']);
    $model->attachTags(['php', 'vue']);

    // Act
    $model->detachTags('php');

    // Assert
    expect($model->tags()->pluck('name')->all())->toBe(['vue']);
});

it('can sync the tags on a model', function () {
    // Arrange
    $model = TestModel::create(['name' => 'Example']);
    $model->attachTags(['php', 'vue']);

    // Act
    $model->syncTags(['vue', 'laravel']);

    // Assert
    expect($model->tags()->pluck('name')->sort()->values()->all())->toBe(['laravel', 'vue']);
});

it('can check whether a model has a tag', function () {
    // Arrange
    $model = TestModel::create(['name' => 'Example']);

    // Act
    $model->attachTags('php');

    // Assert
    expect($model->hasTag('php'))->toBeTrue()
        ->and($model->hasTag('vue'))->toBeFalse();
});

it('can find models with any of the given tags', function () {
    // Arrange
    $php = TestModel::create(['name' => 'PHP'])->attachTags('php');
    $vue = TestModel::create(['name' => 'Vue'])->attachTags('vue');
    TestModel::create(['name' => 'Untagged']);

    // Act
    $results = TestModel::withAnyTags(['php', 'vue'])->pluck('id')->all();

    // Assert
    expect($results)->toEqualCanonicalizing([$php->id, $vue->id]);
});

it('can find models with all of the given tags', function () {
    // Arrange
    $both = TestModel::create(['name' => 'Both'])->attachTags(['php', 'vue']);
    TestModel::create(['name' => 'PHP only'])->attachTags('php');

    // Act
    $results = TestModel::withAllTags(['php', 'vue'])->pluck('id')->all();

    // Assert
    expect($results)->toBe([$both->id]);
});

it('detaches tags when a model is deleted', function () {
    // Arrange
    $model = TestModel::create(['name' => 'Example']);
    $model->attachTags(['php', 'vue']);

    // Act
    $model->delete();

    // Assert
    $this->assertDatabaseCount('taggables', 0);
    $this->assertDatabaseCount('tags', 2);
});

it('lists the most-used tags first with their usage counts', function () {
    // Arrange
    TestModel::create(['name' => 'One'])->attachTags(['php', 'vue']);
    TestModel::create(['name' => 'Two'])->attachTags(['php', 'laravel']);
    TestModel::create(['name' => 'Three'])->attachTags('php');

    // Act
    $popular = Tags::popular();

    // Assert
    expect($popular->pluck('usage_count', 'name')->all())->toBe([
        'php' => 3,
        'laravel' => 1,
        'vue' => 1,
    ]);
});

it('limits the number of popular tags', function () {
    // Arrange
    TestModel::create(['name' => 'Example'])->attachTags(['php', 'vue', 'laravel']);

    // Act
    $popular = Tags::popular(2);

    // Assert
    expect($popular)->toHaveCount(2);
});

it('leaves unused tags out of the popular tags', function () {
    // Arrange
    TestModel::create(['name' => 'Example'])->attachTags('php');
    Tag::findOrCreateByName('unused');

    // Act
    $popular = Tags::popular();

    // Assert
    expect($popular->pluck('name')->all())->toBe(['php']);
});

it('lists tags that are not attached to any model', function () {
    // Arrange
    $model = TestModel::create(['name' => 'Example'])->attachTags(['php', 'vue']);
    Tag::findOrCreateByName('never-used');

    // Act
    $model->detachTags('vue');

    // Assert
    expect(Tags::unused()->pluck('name')->all())->toBe(['never-used', 'vue']);
});
