<?php

use Illuminate\Support\ServiceProvider;
use spawaskar\Tags\TagsServiceProvider;
use spawaskar\Tags\Tests\Fixtures\TestModel;

it('renders the tags on a model', function () {
    // Arrange
    $model = TestModel::create(['name' => 'Example'])->attachTags(['php', 'vue']);

    // Act
    $view = $this->blade('<x-tags::list :model="$model" />', ['model' => $model]);

    // Assert
    $view->assertSeeInOrder(['php', 'vue']);
});

it('renders nothing when the model has no tags', function () {
    // Arrange
    $model = TestModel::create(['name' => 'Example']);

    // Act
    $view = $this->blade('<x-tags::list :model="$model" />', ['model' => $model]);

    // Assert
    $view->assertDontSee('<ul', false);
});

it('publishes the vue components', function () {
    // Act
    $paths = ServiceProvider::pathsToPublish(TagsServiceProvider::class, 'tags-vue');

    // Assert
    expect($paths)->toHaveCount(1)
        ->and(array_key_first($paths).'/components/TagInput.vue')->toBeFile()
        ->and(array_key_first($paths).'/components/TagList.vue')->toBeFile()
        ->and(array_values($paths)[0])->toBe(resource_path('js/vendor/tags'));
});
