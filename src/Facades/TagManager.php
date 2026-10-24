<?php

declare(strict_types=1);

namespace spawaskar\Tags\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Database\Eloquent\Collection<int, \spawaskar\Tags\Models\Tag> unused()
 * @method static \Illuminate\Database\Eloquent\Collection<int, \spawaskar\Tags\Models\Tag> usedOn(class-string<\Illuminate\Database\Eloquent\Model> $type)
 * @method static list<string> types()
 * @method static array<string, list<string>> namesByType()
 * @method static list<string>|array<string, list<string>> suggestions(class-string<\Illuminate\Database\Eloquent\Model>|null $type = null)
 * @method static list<string> names()
 * @method static array<string, array{color: string|null, icon: string|null, description: string|null}> details()
 * @method static array<string, list<string>> namesByModelType()
 * @method static void setColors(array<string, string|null> $colors)
 * @method static list<string>|null icons()
 * @method static void setIcons(array<string, string|null> $icons)
 * @method static void setDescriptions(array<string, string|null> $descriptions)
 * @method static list<array{value: string, label: string}> models()
 * @method static void setModelTypes(array<string, string|null> $types)
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
