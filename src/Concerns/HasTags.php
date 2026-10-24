<?php

declare(strict_types=1);

namespace spawaskar\Tags\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use spawaskar\Tags\Models\Tag;
use spawaskar\Tags\TagManager;

// Add tagging to any Eloquent model.
trait HasTags
{
    /**
     * Detach the model's tags when it's deleted. If the app's model uses
     * soft deletes, the tags are kept so a restored model still has them.
     */
    public static function bootHasTags(): void
    {
        static::deleting(function (self $model): void {
            if (! method_exists($model, 'isForceDeleting') || $model->isForceDeleting()) {
                $model->tags()->detach();
            }
        });
    }

    /** @return MorphToMany<Tag, $this> */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(TagManager::tagModel(), 'taggable');
    }

    /**
     * Attach tags by name, creating any that don't exist.
     * Names are trimmed and lowercased.
     *
     * @param  string|array<int, string>  $names
     */
    public function attachTags(string|array $names): static
    {
        $this->tags()->syncWithoutDetaching($this->findOrCreateTagIds((array) $names));

        return $this;
    }

    /**
     * Detach tags by name. The tags themselves aren't deleted.
     *
     * @param  string|array<int, string>  $names
     */
    public function detachTags(string|array $names): static
    {
        $names = array_map(Tag::normalizeName(...), (array) $names);

        $this->tags()->detach(TagManager::tagModel()::whereIn('name', $names)->pluck('id'));

        return $this;
    }

    /**
     * Replace the model's tags with exactly the tag names passed in, creating any that don't exist.
     *
     * @param  string|array<int, string>  $names
     */
    public function syncTags(string|array $names): static
    {
        $this->tags()->sync($this->findOrCreateTagIds((array) $names));

        return $this;
    }

    /**
     * Whether the model has the given tag.
     */
    public function hasTag(string $name): bool
    {
        return $this->tags()->where('name', Tag::normalizeName($name))->exists();
    }

    /**
     * Models with at least one of the given tags.
     *
     * @param  Builder<static>  $query
     * @param  array<int, string>  $names
     */
    public function scopeWithAnyTags(Builder $query, array $names): void
    {
        $names = array_map(Tag::normalizeName(...), $names);

        $query->whereHas('tags', fn (Builder $tags) => $tags->whereIn('name', $names));
    }

    /**
     * Models with every one of the given tags.
     *
     * @param  Builder<static>  $query
     * @param  array<int, string>  $names
     */
    public function scopeWithAllTags(Builder $query, array $names): void
    {
        $names = array_map(Tag::normalizeName(...), $names);

        foreach ($names as $name) {
            $query->whereHas('tags', fn (Builder $tags) => $tags->where('name', $name));
        }
    }

    /**
     * Turn tag names into tag IDs, creating any tags that don't exist for this model.
     * Blank names are skipped.
     *
     * @param  array<int, string>  $names
     * @return array<int, int>
     */
    protected function findOrCreateTagIds(array $names): array
    {
        $names = array_filter(array_map(Tag::normalizeName(...), $names), fn (string $name) => $name !== '');

        $model = TagManager::tagModel();

        return array_map(fn (string $name): int => $model::findOrCreateByName($name, $this)->id, $names);
    }
}
