<?php

declare(strict_types=1);

namespace spawaskar\Tags;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use spawaskar\Tags\Models\Tag;

// Queries about all tags, rather than one model's tags.
class TagManager
{
    /**
     * The tag model class, set by the tags.model config option.
     *
     * @return class-string<Tag>
     */
    public static function tagModel(): string
    {
        /** @var class-string<Tag> $model */
        $model = config('tags.model', Tag::class);

        return $model;
    }

    /**
     * Tags that aren't on anything: no record has them attached, e.g. new tags never used yet.
     *
     * @return Collection<int, Tag>
     */
    public function unused(): Collection
    {
        return static::tagModel()::query()
            ->whereNotExists($this->usages(...))
            ->orderBy('name')
            ->get();
    }

    /**
     * Tags attached to at least one record of the given class, e.g. Post::class.
     *
     * @param  class-string<Model>  $type
     * @return Collection<int, Tag>
     */
    public function usedOn(string $type): Collection
    {
        // Respects the app's morph map, if it has one.
        $morphClass = (new $type)->getMorphClass();

        return static::tagModel()::query()
            ->whereExists(fn (Builder $query) => $this->usages($query)->where('taggables.taggable_type', $morphClass))
            ->orderBy('name')
            ->get();
    }

    /**
     * The classes of the models that have tags, e.g. ['App\Models\Post'].
     *
     * @return list<string>
     */
    public function types(): array
    {
        return array_values(DB::table('taggables')
            ->distinct()
            ->orderBy('taggable_type')
            ->pluck('taggable_type')
            ->map(fn (string $type): string => Relation::getMorphedModel($type) ?? $type)
            ->all());
    }

    /**
     * Tag names for TagInput's suggestions. In simple mode, every tag's name, shown as buttons.
     * In advanced mode, pass the page's model, e.g. Post::class, for that model's tags.
     * Without a model, every tag, grouped by its model.
     *
     * @param  class-string<Model>|null  $type
     * @return list<string>|array<string, list<string>>
     */
    public function suggestions(?string $type = null): array
    {
        if (config('tags.mode') !== 'advanced') {
            return $this->names();
        }

        // Without the model_type column, fall back to where tags are attached.
        if (! static::tagModel()::recordsModelType()) {
            return $type === null
                ? $this->namesByType()
                : array_values($this->usedOn($type)->pluck('name')->all());
        }

        if ($type === null) {
            return $this->namesByModelType();
        }

        return array_values(static::tagModel()::query()->forModel($type)->orderBy('name')->pluck('name')->all());
    }

    /**
     * Every tag's name.
     *
     * @return list<string>
     */
    public function names(): array
    {
        return array_values(static::tagModel()::orderBy('name')->pluck('name')->all());
    }

    /**
     * Tags' color, icon, and description, by name. Pass it to the advanced TagInput's details,
     * so existing tags show their color and icon as soon as they're picked. In advanced mode,
     * pass the page's model, e.g. Post::class, since other models can have tags with the same names.
     *
     * @param  class-string<Model>|null  $type
     * @return array<string, array{color: string|null, icon: string|null, description: string|null}>
     */
    public function details(?string $type = null): array
    {
        $details = [];

        foreach (static::tagModel()::query()->forModel($type)->orderBy('name')->get() as $tag) {
            $details[$tag->name] = [
                'color' => $tag->color,
                'icon' => $tag->icon,
                'description' => $tag->description,
            ];
        }

        return $details;
    }

    /**
     * Every tag's name, grouped by the model it belongs to.
     *
     * @return array<string, list<string>>
     */
    public function namesByModelType(): array
    {
        $groups = [];

        $tags = static::tagModel()::query()->whereNotNull('model_type')->orderBy('model_type')->orderBy('name')->get(['name', 'model_type']);

        foreach ($tags as $tag) {
            $groups[class_basename(Relation::getMorphedModel((string) $tag->model_type) ?? (string) $tag->model_type)][] = $tag->name;
        }

        return $groups;
    }

    /**
     * Every tag in use, grouped by the type of model it's attached to.
     * Pass it to TagInput's suggestions to show a heading for each model.
     *
     * @return array<string, list<string>>
     */
    public function namesByType(): array
    {
        $groups = [];

        $rows = static::tagModel()::query()
            ->join('taggables', 'taggables.tag_id', '=', 'tags.id')
            ->select(['tags.name', 'taggables.taggable_type'])
            ->distinct()
            ->orderBy('taggables.taggable_type')
            ->orderBy('tags.name')
            ->get();

        foreach ($rows as $row) {
            $type = (string) $row->getAttribute('taggable_type');

            $groups[class_basename(Relation::getMorphedModel($type) ?? $type)][] = $row->name;
        }

        return $groups;
    }

    /**
     * Set tag colors by name, e.g. ['pottery' => 'red']. A null color resets the tag to the
     * default. Names that aren't tags yet are skipped. In advanced mode, pass the model whose
     * tags these are, e.g. Post::class.
     *
     * @param  array<string, string|null>  $colors
     * @param  class-string<Model>|null  $type
     */
    public function setColors(array $colors, ?string $type = null): void
    {
        foreach ($colors as $name => $color) {
            $this->findByName((string) $name, $type)?->update(['color' => $color]);
        }
    }

    /**
     * The icons users can pick in advanced mode: null for every Lucide icon, or a list of names.
     * Pass it to TagInput's available-icons.
     *
     * @return list<string>|null
     */
    public function icons(): ?array
    {
        return static::tagModel()::icons();
    }

    /**
     * Set tag icons by name, e.g. ['file' => 'star']. A null icon removes the tag's icon.
     * Advanced mode only, so pass the model whose tags these are, e.g. Post::class.
     *
     * @param  array<string, string|null>  $icons
     * @param  class-string<Model>|null  $type
     */
    public function setIcons(array $icons, ?string $type = null): void
    {
        foreach ($icons as $name => $icon) {
            $this->findByName((string) $name, $type)?->update(['icon' => $icon]);
        }
    }

    /**
     * Set tag descriptions by name, e.g. ['pottery' => 'Ceramic fragments']. A blank or null
     * description removes the tag's description. In advanced mode, pass the model whose tags
     * these are, e.g. Post::class.
     *
     * @param  array<string, string|null>  $descriptions
     * @param  class-string<Model>|null  $type
     */
    public function setDescriptions(array $descriptions, ?string $type = null): void
    {
        foreach ($descriptions as $name => $description) {
            $description = trim((string) $description);

            $this->findByName((string) $name, $type)?->update(['description' => $description === '' ? null : $description]);
        }
    }

    /**
     * The models users can choose from when creating a tag in advanced mode, from the
     * tags.models config option. Pass it to TagInput's models.
     *
     * @return list<array{value: string, label: string}>
     */
    public function models(): array
    {
        /** @var list<class-string<Model>> $classes */
        $classes = config('tags.models', []);

        return array_map(fn (string $class): array => [
            'value' => (new $class)->getMorphClass(),
            'label' => class_basename($class),
        ], $classes);
    }

    /**
     * A tag by name, among the given model's tags in advanced mode.
     */
    protected function findByName(string $name, ?string $type): ?Tag
    {
        return static::tagModel()::query()->forModel($type)->where('name', Tag::normalizeName($name))->first();
    }

    /**
     * Limit a subquery to the pivot rows for the tag in the outer query.
     */
    protected function usages(Builder $query): Builder
    {
        return $query->from('taggables')->whereColumn('taggables.tag_id', 'tags.id');
    }
}
