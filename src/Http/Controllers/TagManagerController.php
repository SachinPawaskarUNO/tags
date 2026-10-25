<?php

declare(strict_types=1);

namespace spawaskar\Tags\Http\Controllers;

use Closure;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
     * Show every tag, or with teams on, the user's team's tags, with how many models use each.
     */
    public function index(Request $request, TagManager $manager): Response
    {
        if (! class_exists(Inertia::class)) {
            throw new RuntimeException('The tag manager page needs Inertia. Run: composer require inertiajs/inertia-laravel');
        }

        $model = TagManager::tagModel();
        $table = (new $model)->getTable();

        $tags = $model::query()
            ->forTeam($this->team($request))
            ->addSelect(['uses' => DB::table('taggables')->selectRaw('count(*)')->whereColumn('taggables.tag_id', "{$table}.id")])
            ->orderBy('name')
            ->get()
            ->map(fn (Tag $tag): array => [
                'id' => $tag->id,
                'name' => $tag->name,
                'color' => $tag->color,
                'icon' => $tag->icon,
                'description' => $tag->description,
                'modelType' => $tag->model_type,
                'model' => $tag->model_type === null ? null : class_basename(Relation::getMorphedModel($tag->model_type) ?? $tag->model_type),
                'uses' => (int) $tag->getAttribute('uses'),
                'deleteUrl' => route('tags.manager.destroy', $tag->id),
            ]);

        // Names already taken, by model type, so the New tag popup can check the model it's set to.
        $existing = $tags->groupBy('modelType')->map(fn (Collection $group): Collection => $group->pluck('name')->values());

        return Inertia::render((string) config('tags.manager.component', 'tags/Manager'), [
            'tags' => $tags,
            'existing' => $existing,
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
        $team = $this->team($request);

        $request->merge(['name' => Tag::normalizeName((string) $request->string('name'))]);

        // In advanced mode, names only need to be unique within the tag's model, and with teams
        // on, within the team.
        $unique = Rule::unique((new $model)->getTable(), 'name');

        if ($model::recordsModelType()) {
            $unique->where('model_type', (string) $request->string('modelType'));
        }

        if ($team !== null) {
            $unique->where('team_id', $team);
        }

        /** @var array{name: string, description?: string|null, color?: string|null, icon?: string|null, modelType?: string|null} $validated */
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:32', $unique],
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
            'modelType' => [
                $model::recordsModelType() ? 'required' : 'nullable',
                'string',
                Rule::in(array_column($manager->models(), 'value')),
            ],
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

        if ($team !== null) {
            $attributes['team_id'] = $team;
        }

        $model::query()->create($attributes);

        return redirect()->route('tags.manager.index');
    }

    /**
     * Delete a tag, removing it from every model it's on. With teams on, only the user's team's
     * tags can be deleted: another team's tag is a 404.
     */
    public function destroy(Request $request, int $tag): RedirectResponse
    {
        $found = TagManager::tagModel()::query()->forTeam($this->team($request))->findOrFail($tag);

        DB::transaction(function () use ($found): void {
            DB::table('taggables')->where('tag_id', $found->id)->delete();
            $found->delete();
        });

        return redirect()->route('tags.manager.index');
    }

    /**
     * With teams on, the logged-in user's current team, from the attribute named by the
     * tags.manager.team config option. A user without a team can't use the page, rather than
     * seeing every team's tags. With teams off, null.
     */
    protected function team(Request $request): int|string|null
    {
        if (! TagManager::tagModel()::recordsTeam()) {
            return null;
        }

        $team = data_get($request->user(), (string) config('tags.manager.team', 'current_team_id'));

        abort_unless(is_int($team) || is_string($team), 403, 'Choose a team to manage its tags.');

        return $team;
    }
}
