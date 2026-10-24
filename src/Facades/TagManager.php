<?php

declare(strict_types=1);

namespace spawaskar\Tags\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Database\Eloquent\Collection<int, \spawaskar\Tags\Models\Tag> unused()
 * @method static \Illuminate\Database\Eloquent\Collection<int, \spawaskar\Tags\Models\Tag> usedOn(class-string<\Illuminate\Database\Eloquent\Model> $type)
 * @method static list<string> types()
 * @method static array<string, list<string>> namesByType()
 * @method static list<string>|array<string, list<string>> suggestions()
 * @method static void setColors(array<string, string|null> $colors)
 * @method static list<string> icons()
 * @method static void setIcons(array<string, string|null> $icons)
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
