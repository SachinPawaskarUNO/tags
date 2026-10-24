<?php

declare(strict_types=1);

namespace spawaskar\Tags;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
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
     * Tags that aren't attached to any model.
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
     * Tags attached to at least one model of the given class, e.g. Post::class.
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
     * In advanced mode, pass the page's model, e.g. Post::class, for the tags created for it
     * (plus tags created without a model). Without a model, every tag, grouped by its model.
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

        $morphClass = (new $type)->getMorphClass();

        return array_values(static::tagModel()::query()
            ->where(fn (EloquentBuilder $query) => $query->where('model_type', $morphClass)->orWhereNull('model_type'))
            ->orderBy('name')
            ->pluck('name')
            ->all());
    }

    /**
     * Every tag's name. Pass it to the advanced TagInput's existing, so a name from another
     * model isn't created again.
     *
     * @return list<string>
     */
    public function names(): array
    {
        return array_values(static::tagModel()::orderBy('name')->pluck('name')->all());
    }

    /**
     * Every tag's name, grouped by the model it was created for. Tags created without a
     * model are listed last, under "Other".
     *
     * @return array<string, list<string>>
     */
    public function namesByModelType(): array
    {
        $groups = [];
        $other = [];

        $tags = static::tagModel()::query()->orderBy('model_type')->orderBy('name')->get(['name', 'model_type']);

        foreach ($tags as $tag) {
            if ($tag->model_type === null) {
                $other[] = $tag->name;
            } else {
                $groups[class_basename(Relation::getMorphedModel($tag->model_type) ?? $tag->model_type)][] = $tag->name;
            }
        }

        if ($other !== []) {
            $groups['Other'] = $other;
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
     * Set tag colors by name, e.g. ['pottery' => 'red']. A null color resets
     * the tag to the default. Names that aren't tags yet are skipped.
     *
     * @param  array<string, string|null>  $colors
     */
    public function setColors(array $colors): void
    {
        foreach ($colors as $name => $color) {
            static::tagModel()::where('name', Tag::normalizeName((string) $name))->first()?->update(['color' => $color]);
        }
    }

    /**
     * The icons users can pick in advanced mode. Pass it to TagInput's available-icons.
     *
     * @return list<string>
     */
    public function icons(): array
    {
        return static::tagModel()::icons();
    }

    /**
     * Set tag icons by name, e.g. ['file' => 'star']. A null icon removes
     * the tag's icon. Advanced mode only.
     *
     * @param  array<string, string|null>  $icons
     */
    public function setIcons(array $icons): void
    {
        foreach ($icons as $name => $icon) {
            static::tagModel()::where('name', Tag::normalizeName((string) $name))->first()?->update(['icon' => $icon]);
        }
    }

    /**
     * Set tag descriptions by name, e.g. ['pottery' => 'Ceramic fragments']. A blank or
     * null description removes the tag's description.
     *
     * @param  array<string, string|null>  $descriptions
     */
    public function setDescriptions(array $descriptions): void
    {
        foreach ($descriptions as $name => $description) {
            $description = trim((string) $description);

            static::tagModel()::where('name', Tag::normalizeName((string) $name))->first()
                ?->update(['description' => $description === '' ? null : $description]);
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
     * Set the model type tags were created for, by name, e.g. ['pottery' => 'App\Models\Post'].
     * Types must be one of models(). Does nothing if the tags table has no model_type column.
     *
     * @param  array<string, string|null>  $types
     */
    public function setModelTypes(array $types): void
    {
        if (! static::tagModel()::recordsModelType()) {
            return;
        }

        $allowed = array_column($this->models(), 'value');

        foreach ($types as $type) {
            if ($type !== null && ! in_array($type, $allowed, true)) {
                throw new InvalidArgumentException("[{$type}] is not one of the tags.models config option's models.");
            }
        }

        foreach ($types as $name => $type) {
            static::tagModel()::where('name', Tag::normalizeName((string) $name))->first()?->update(['model_type' => $type]);
        }
    }

    /**
     * Limit a subquery to the pivot rows for the tag in the outer query.
     */
    protected function usages(Builder $query): Builder
    {
        return $query->from('taggables')->whereColumn('taggables.tag_id', 'tags.id');
    }
}
