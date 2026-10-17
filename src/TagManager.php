<?php

declare(strict_types=1);

namespace spawaskar\Tags;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use spawaskar\Tags\Models\Tag;

// Queries about all tags, rather than one model's tags.
class TagManager
{
    /**
     * Tags that aren't attached to any model.
     *
     * @return Collection<int, Tag>
     */
    public function unused(): Collection
    {
        return Tag::query()
            ->whereNotExists($this->usages(...))
            ->orderBy('name')
            ->get();
    }

    /**
     * Limit a subquery to the pivot rows for the tag in the outer query.
     */
    protected function usages(Builder $query): Builder
    {
        return $query->from('taggables')->whereColumn('taggables.tag_id', 'tags.id');
    }
}
