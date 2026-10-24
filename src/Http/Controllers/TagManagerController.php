<?php

declare(strict_types=1);

namespace spawaskar\Tags\Http\Controllers;

use Closure;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use spawaskar\Tags\Models\Tag;
use spawaskar\Tags\TagManager;

// The tag manager page: every tag, with the New tag popup and a delete button for each.
class TagManagerController
{
    /**
     * Show every tag, with how many models use it.
     */
    public function index(TagManager $manager): Response
    {
        if (! class_exists(Inertia::class)) {
            throw new RuntimeException('The tag manager page needs Inertia. Run: composer require inertiajs/inertia-laravel');
        }

        $model = TagManager::tagModel();
        $table = (new $model)->getTable();

        $tags = $model::query()
            ->addSelect(['uses' => DB::table('taggables')->selectRaw('count(*)')->whereColumn('taggables.tag_id', "{$table}.id")])
            ->orderBy('name')
            ->get()
            ->map(fn (Tag $tag): array => [
                'id' => $tag->id,
                'name' => $tag->name,
                'color' => $tag->color,
                'icon' => $tag->icon,
                'description' => $tag->description,
                'model' => $tag->model_type === null ? null : class_basename(Relation::getMorphedModel($tag->model_type) ?? $tag->model_type),
                'uses' => (int) $tag->getAttribute('uses'),
                'deleteUrl' => route('tags.manager.destroy', $tag->id),
            ]);

        return Inertia::render((string) config('tags.manager.component', 'tags/Manager'), [
            'tags' => $tags,
            'models' => $manager->models(),
            'icons' => $manager->icons(),
            'storeUrl' => route('tags.manager.store'),
            'maxDescriptionLength' => Tag::MAX_DESCRIPTION_LENGTH,
        ]);
    }

    /**
     * Create a tag from the New tag popup, without attaching it to anything.
     */
    public function store(Request $request, TagManager $manager): RedirectResponse
    {
        $model = TagManager::tagModel();

        $request->merge(['name' => Tag::normalizeName((string) $request->string('name'))]);

        /** @var array{name: string, description?: string|null, color?: string|null, icon?: string|null, modelType?: string|null} $validated */
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:32', Rule::unique((new $model)->getTable(), 'name')],
            'description' => ['nullable', 'string', 'max:'.Tag::MAX_DESCRIPTION_LENGTH],
            'color' => ['nullable', 'string', function (string $attribute, mixed $value, Closure $fail) use ($model): void {
                if (! is_string($value) || ! $model::isValidColor($value)) {
                    $fail('The color must be a hex color like #eb5e41.');
                }
            }],
            'icon' => ['nullable', 'string', function (string $attribute, mixed $value, Closure $fail) use ($model): void {
                if (! is_string($value) || ! $model::isValidIcon($value)) {
                    $fail('The icon is not one of the enabled icons.');
                }
            }],
            'modelType' => ['nullable', 'string', Rule::in(array_column($manager->models(), 'value'))],
        ]);

        $description = trim((string) ($validated['description'] ?? ''));

        $attributes = [
            'name' => $validated['name'],
            'description' => $description === '' ? null : $description,
            'color' => $validated['color'] ?? null,
        ];

        if ($model::recordsModelType()) {
            $attributes['icon'] = $validated['icon'] ?? null;
            $attributes['model_type'] = $validated['modelType'] ?? null;
        }

        $model::query()->create($attributes);

        return redirect()->route('tags.manager.index');
    }

    /**
     * Delete a tag, removing it from every model it's on.
     */
    public function destroy(int $tag): RedirectResponse
    {
        $found = TagManager::tagModel()::query()->findOrFail($tag);

        DB::transaction(function () use ($found): void {
            DB::table('taggables')->where('tag_id', $found->id)->delete();
            $found->delete();
        });

        return redirect()->route('tags.manager.index');
    }
}
