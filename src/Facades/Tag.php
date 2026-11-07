<?php

declare(strict_types=1);

namespace spawaskar\Tags\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Calls go to the tag model set by the tags.model config option. Constants like COLORS aren't
 * passed through, so use them from spawaskar\Tags\Models\Tag.
 *
 * @method static class-string<\spawaskar\Tags\Models\Tag> modelClass()
 * @method static \Illuminate\Database\Eloquent\Collection<int, \spawaskar\Tags\Models\Tag> unused()
 * @method static \Illuminate\Database\Eloquent\Collection<int, \spawaskar\Tags\Models\Tag> usedOn(class-string<\Illuminate\Database\Eloquent\Model> $type)
 * @method static list<string> types()
 * @method static array<string, list<string>> namesByType()
 * @method static list<string>|array<string, list<string>> suggestions(class-string<\Illuminate\Database\Eloquent\Model>|null $type = null, int|string|null $team = null)
 * @method static list<string> names(int|string|null $team = null)
 * @method static array<string, array{color: string|null, icon: string|null, description: string|null}> details(class-string<\Illuminate\Database\Eloquent\Model>|null $type = null, int|string|null $team = null)
 * @method static array<string, list<string>> namesByModelType(int|string|null $team = null)
 * @method static void setColors(array<string, string|null> $colors, class-string<\Illuminate\Database\Eloquent\Model>|null $type = null, int|string|null $team = null)
 * @method static list<string>|null icons()
 * @method static void setIcons(array<string, string|null> $icons, class-string<\Illuminate\Database\Eloquent\Model>|null $type = null, int|string|null $team = null)
 * @method static void setDescriptions(array<string, string|null> $descriptions, class-string<\Illuminate\Database\Eloquent\Model>|null $type = null, int|string|null $team = null)
 * @method static list<array{value: string, label: string}> models()
 * @method static \spawaskar\Tags\Models\Tag findOrCreateByName(string $name, \Illuminate\Database\Eloquent\Model|null $for = null)
 *
 * @see \spawaskar\Tags\Models\Tag
 */
class Tag extends Facade
{
    /**
     * Resolve the model again on every call, so a changed tags.model config option is picked up.
     */
    protected static $cached = false;

    protected static function getFacadeAccessor(): string
    {
        return 'tags.tag';
    }
}
