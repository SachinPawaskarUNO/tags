<?php

declare(strict_types=1);

namespace spawaskar\Tags\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Database\Eloquent\Collection<int, \spawaskar\Tags\Models\Tag> unused()
 *
 * @see \spawaskar\Tags\TagManager
 */
class TagManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \spawaskar\Tags\TagManager::class;
    }
}
