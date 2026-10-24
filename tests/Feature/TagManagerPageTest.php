<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use spawaskar\Tags\Models\Tag;
use spawaskar\Tags\Tests\Fixtures\TestModel;

// Ask for Inertia's JSON page, so the tests don't need the app's root view.
const INERTIA = ['X-Inertia' => 'true'];

it('does not register the tag manager routes unless enabled', function () {
    // Assert
    expect(Route::has('tags.manager.index'))->toBeFalse();
});

it('serves the tag manager page at the configured path', function () {
    // Arrange
    enableTagManager('admin/tags');

    // Act
    $response = $this->get('/admin/tags', INERTIA);

    // Assert
    $response->assertOk()
        ->assertJsonPath('component', 'tags/Manager')
        ->assertJsonPath('props.storeUrl', route('tags.manager.store'));
    expect(route('tags.manager.index'))->toEndWith('/admin/tags');
});

it('renders the configured page component', function () {
    // Arrange
    config(['tags.manager.component' => 'Admin/Tags']);
    enableTagManager();

    // Act
    $response = $this->get(route('tags.manager.index'), INERTIA);

    // Assert
    $response->assertJsonPath('component', 'Admin/Tags');
});

it('lists every tag with its details and how many models use it', function () {
    // Arrange
    useAdvancedMode();
    config(['tags.models' => [TestModel::class]]);
    enableTagManager();
    TestModel::create(['name' => 'First'])->attachTags(['pottery', 'bone']);
    TestModel::create(['name' => 'Second'])->attachTags('pottery');
    Tag::create(['name' => 'unused', 'color' => '#eb5e41', 'icon' => 'star', 'model_type' => (new TestModel)->getMorphClass()]);

    // Act
    $response = $this->get(route('tags.manager.index'), INERTIA);

    // Assert
    $response->assertOk()
        ->assertJsonPath('props.tags.*.name', ['bone', 'pottery', 'unused'])
        ->assertJsonPath('props.tags.*.uses', [1, 2, 0])
        ->assertJsonPath('props.tags.2.color', '#eb5e41')
        ->assertJsonPath('props.tags.2.icon', 'star')
        ->assertJsonPath('props.tags.2.model', 'TestModel')
        ->assertJsonPath('props.models.0.label', 'TestModel');
});

it('creates a tag from the new tag popup', function () {
    // Arrange
    useAdvancedMode();
    config(['tags.models' => [TestModel::class]]);
    enableTagManager();
    $modelType = (new TestModel)->getMorphClass();

    // Act
    $response = $this->post(route('tags.manager.store'), [
        'name' => ' Pottery ',
        'description' => 'Ceramic fragments',
        'color' => '#eb5e41',
        'icon' => 'star',
        'modelType' => $modelType,
    ]);

    // Assert
    $response->assertRedirect(route('tags.manager.index'));
    $tag = Tag::where('name', 'pottery')->firstOrFail();
    expect($tag->description)->toBe('Ceramic fragments')
        ->and($tag->color)->toBe('#eb5e41')
        ->and($tag->icon)->toBe('star')
        ->and($tag->model_type)->toBe($modelType);
});

it('refuses a tag name that already exists', function () {
    // Arrange
    enableTagManager();
    Tag::create(['name' => 'pottery']);

    // Act
    $response = $this->post(route('tags.manager.store'), ['name' => 'POTTERY']);

    // Assert
    $response->assertSessionHasErrors('name');
    expect(Tag::count())->toBe(1);
});

it('refuses colors, icons, and models that are not allowed', function () {
    // Arrange
    useAdvancedMode();
    enableTagManager();

    // Act
    $response = $this->post(route('tags.manager.store'), [
        'name' => 'pottery',
        'color' => 'not-a-color',
        'icon' => 'Not an icon!',
        'modelType' => 'App\Models\Unlisted',
    ]);

    // Assert
    $response->assertSessionHasErrors(['color', 'icon', 'modelType']);
    expect(Tag::count())->toBe(0);
});

it('deletes a tag and removes it from every model', function () {
    // Arrange
    enableTagManager();
    $model = TestModel::create(['name' => 'Example'])->attachTags(['pottery', 'bone']);
    $tag = Tag::where('name', 'pottery')->firstOrFail();

    // Act
    $response = $this->delete(route('tags.manager.destroy', $tag->id));

    // Assert
    $response->assertRedirect(route('tags.manager.index'));
    expect(Tag::pluck('name')->all())->toBe(['bone'])
        ->and(DB::table('taggables')->where('tag_id', $tag->id)->count())->toBe(0)
        ->and($model->tags()->pluck('name')->all())->toBe(['bone']);
});

it('returns not found when deleting a tag that does not exist', function () {
    // Arrange
    enableTagManager();

    // Act
    $response = $this->delete(route('tags.manager.destroy', 999));

    // Assert
    $response->assertNotFound();
});
