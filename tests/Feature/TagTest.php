<?php

use spawaskar\Tags\Facades\TagManager;
use spawaskar\Tags\Models\Tag;
use spawaskar\Tags\Tests\Fixtures\TestModel;

it('can attach a tag to a model', function () {
    // Arrange
    $model = TestModel::create(['name' => 'Example']);

    // Act
    $model->attachTags('tag1');

    // Assert
    expect($model->tags)->toHaveCount(1)
        ->and($model->tags->first()->name)->toBe('tag1');
});

it('can detach a tag from a model', function () {
    // Arrange
    $model = TestModel::create(['name' => 'Example']);
    $model->attachTags(['tag1', 'tag2']);

    // Act
    $model->detachTags('tag2');

    // Assert
    expect($model->tags()->pluck('name')->all())->toBe(['tag1']);
});

it('can sync the tags on a model', function () {
    // Arrange
    $model = TestModel::create(['name' => 'Example']);
    $model->attachTags(['tag1', 'tag2']);

    // Act
    $model->syncTags(['tag2', 'tag3']);

    // Assert
    expect($model->tags()->pluck('name')->sort()->values()->all())->toBe(['tag2', 'tag3']);
});

it('can check whether a model has a tag', function () {
    // Arrange
    $model = TestModel::create(['name' => 'Example']);

    // Act
    $model->attachTags('tag1');

    // Assert
    expect($model->hasTag('tag1'))->toBeTrue()
        ->and($model->hasTag('tag2'))->toBeFalse();
});

it('can find models with any of the given tags', function () {
    // Arrange
    $first = TestModel::create(['name' => 'First'])->attachTags('tag1');
    $second = TestModel::create(['name' => 'Second'])->attachTags('tag2');
    TestModel::create(['name' => 'Untagged']);

    // Act
    $results = TestModel::withAnyTags(['tag1', 'tag2'])->pluck('id')->all();

    // Assert
    expect($results)->toEqualCanonicalizing([$first->id, $second->id]);
});

it('can find models with all of the given tags', function () {
    // Arrange
    $both = TestModel::create(['name' => 'Both'])->attachTags(['tag1', 'tag2']);
    TestModel::create(['name' => 'Tag1 only'])->attachTags('tag1');

    // Act
    $results = TestModel::withAllTags(['tag1', 'tag2'])->pluck('id')->all();

    // Assert
    expect($results)->toBe([$both->id]);
});

it('trims and lowercases tag names so different spellings are one tag', function () {
    // Arrange
    $first = TestModel::create(['name' => 'First']);
    $second = TestModel::create(['name' => 'Second']);

    // Act
    $first->attachTags('Tag1');
    $second->attachTags(['  tag1  ', 'TAG1']);

    // Assert
    expect(Tag::pluck('name')->all())->toBe(['tag1'])
        ->and($first->tags->first()->id)->toBe($second->tags->first()->id);
});

it('checks and detaches tags by name regardless of case', function () {
    // Arrange
    $model = TestModel::create(['name' => 'Example'])->attachTags(['tag1', 'tag2']);

    // Act
    $model->detachTags(' TAG2 ');

    // Assert
    expect($model->hasTag('TAG1'))->toBeTrue()
        ->and($model->hasTag('Tag2'))->toBeFalse();
});

it('finds models by tag regardless of case', function () {
    // Arrange
    $both = TestModel::create(['name' => 'Test'])->attachTags(['tag1', 'tag2']);

    // Act
    $any = TestModel::withAnyTags(['TAG1'])->pluck('id')->all();
    $all = TestModel::withAllTags([' Tag1 ', 'TAG2'])->pluck('id')->all();

    // Assert
    expect($any)->toBe([$both->id])
        ->and($all)->toBe([$both->id]);
});

it('skips blank tag names', function () {
    // Arrange
    $model = TestModel::create(['name' => 'Example']);

    // Act
    $model->attachTags(['tag1', '', '   ']);

    // Assert
    expect(Tag::pluck('name')->all())->toBe(['tag1']);
});

it('refuses to create a tag with a blank name', function () {
    // Act
    Tag::findOrCreateByName('   ');
})->throws(InvalidArgumentException::class);

it('detaches tags when a model is deleted', function () {
    // Arrange
    $model = TestModel::create(['name' => 'Example']);
    $model->attachTags(['tag1', 'tag2']);

    // Act
    $model->delete();

    // Assert
    $this->assertDatabaseCount('taggables', 0);
    $this->assertDatabaseCount('tags', 2);
});

it('suggests every tag name in simple mode', function () {
    // Arrange
    TestModel::create(['name' => 'Example'])->attachTags(['tag2', 'tag1']);
    Tag::findOrCreateByName('never-used');

    // Act
    $suggestions = TagManager::suggestions();

    // Assert
    expect($suggestions)->toBe(['never-used', 'tag1', 'tag2']);
});

it('suggests tag names grouped by model in advanced mode', function () {
    // Arrange
    config(['tags.mode' => 'advanced']);
    TestModel::create(['name' => 'Example'])->attachTags(['tag2', 'tag1']);
    Tag::findOrCreateByName('never-used');

    // Act
    $suggestions = TagManager::suggestions();

    // Assert
    expect($suggestions)->toBe(['TestModel' => ['tag1', 'tag2']]);
});

it('records the model a new tag is created for in advanced mode', function () {
    // Arrange
    useAdvancedMode();
    Tag::findOrCreateByName('tag2');

    // Act
    TestModel::create(['name' => 'Example'])->attachTags(['tag1', 'tag2']);

    // Assert
    expect(Tag::orderBy('name')->pluck('model_type', 'name')->all())->toBe([
        'tag1' => TestModel::class,
        'tag2' => null,
    ]);
});

it('only sets model types from the config', function () {
    // Arrange
    useAdvancedMode();
    config(['tags.models' => [TestModel::class]]);
    Tag::findOrCreateByName('tag1');

    // Act
    TagManager::setModelTypes(['tag1' => TestModel::class]);

    // Assert
    expect(Tag::first()->model_type)->toBe(TestModel::class)
        ->and(fn () => TagManager::setModelTypes(['tag1' => 'App\Models\Other']))->toThrow(InvalidArgumentException::class);
});

it('suggests only the tags created for a model in advanced mode', function () {
    // Arrange
    useAdvancedMode();
    TestModel::create(['name' => 'Example'])->attachTags('tag1');
    Tag::create(['name' => 'tag2', 'model_type' => 'App\Models\Other']);
    Tag::create(['name' => 'tag3']);

    // Act
    $suggestions = TagManager::suggestions(TestModel::class);

    // Assert
    expect($suggestions)->toBe(['tag1', 'tag3']);
});

it('lists tags that are not attached to any model', function () {
    // Arrange
    $model = TestModel::create(['name' => 'Example'])->attachTags(['tag1', 'tag2']);
    Tag::findOrCreateByName('never-used');

    // Act
    $model->detachTags('tag2');

    // Assert
    expect(TagManager::unused()->pluck('name')->all())->toBe(['never-used', 'tag2']);
});

it('accepts any Lucide icon name by default in advanced mode', function () {
    // Arrange
    useAdvancedMode();

    // Assert
    expect(TagManager::icons())->toBeNull()
        ->and(Tag::isValidIcon('grid-2x2'))->toBeTrue()
        ->and(Tag::isValidIcon('Not an icon'))->toBeFalse();
});

it('only accepts the icons listed in the config', function () {
    // Arrange
    useAdvancedMode();
    config(['tags.icons' => ['star', 'map-pin']]);

    // Assert
    expect(TagManager::icons())->toBe(['star', 'map-pin'])
        ->and(Tag::isValidIcon('star'))->toBeTrue()
        ->and(Tag::isValidIcon('heart'))->toBeFalse();
});
