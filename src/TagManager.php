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
     * Tag names for TagInput's suggestions: a list in simple mode, shown as buttons,
     * or grouped by model in advanced mode, shown in a dropdown.
     *
     * @return list<string>|array<string, list<string>>
     */
    public function suggestions(): array
    {
        if (config('tags.mode') === 'advanced') {
            return $this->namesByType();
        }

        return array_values(static::tagModel()::orderBy('name')->pluck('name')->all());
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
     * Limit a subquery to the pivot rows for the tag in the outer query.
     */
    protected function usages(Builder $query): Builder
    {
        return $query->from('taggables')->whereColumn('taggables.tag_id', 'tags.id');
    }
}
