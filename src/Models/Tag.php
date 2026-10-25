<?php

declare(strict_types=1);

namespace spawaskar\Tags\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * @property int $id
 * @property string $name
 * @property string|null $color
 * @property string|null $icon
 * @property string|null $model_type The model the tag belongs to. Required in advanced mode.
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Tag extends Model
{
    /**
     * The palette: the only colors in simple mode. Advanced mode also accepts hex colors.
     * Keep in sync with tagColors in resources/js/tagColors.js.
     */
    public const array COLORS = ['gray', 'red', 'orange', 'yellow', 'green', 'blue', 'purple'];

    /**
     * What an icon name looks like: a Lucide icon name (https://lucide.dev/icons), e.g. map-pin.
     * Matches NAME in resources/js/tagIcons.js.
     */
    public const string ICON_NAME_PATTERN = '/^[a-z0-9]+(-[a-z0-9]+)*$/';

    protected $fillable = [
        'name',
        'color',
        'icon',
        'model_type',
        'description',
    ];

    /**
     * The longest description a tag can have, matching the description column.
     */
    public const int MAX_DESCRIPTION_LENGTH = 255;

    /**
     * Refuse to save a tag with a blank name, a description that's too long, a color or icon
     * that isn't valid, or in advanced mode, no model type.
     */
    protected static function booted(): void
    {
        static::saving(function (Tag $tag): void {
            if ($tag->name === '') {
                throw new InvalidArgumentException('A tag name cannot be blank.');
            }

            if ($tag->description !== null && mb_strlen($tag->description) > self::MAX_DESCRIPTION_LENGTH) {
                throw new InvalidArgumentException('A tag description cannot be longer than '.self::MAX_DESCRIPTION_LENGTH.' characters.');
            }

            if ($tag->color !== null && ! static::isValidColor($tag->color)) {
                throw new InvalidArgumentException("[{$tag->color}] is not a tag color.");
            }

            if ($tag->icon !== null && ! static::isValidIcon($tag->icon)) {
                throw new InvalidArgumentException("[{$tag->icon}] is not a tag icon.");
            }

            if (blank($tag->model_type) && static::recordsModelType()) {
                throw new InvalidArgumentException('A tag needs a model type in advanced mode.');
            }
        });
    }

    /**
     * Limit a query to one model's tags, e.g. Post::class, in advanced mode. In simple mode,
     * or without a model, every tag.
     *
     * @param  Builder<static>  $query
     */
    public function scopeForModel(Builder $query, ?string $type): void
    {
        if ($type !== null && static::recordsModelType()) {
            $query->where('model_type', static::morphClassFor($type));
        }
    }

    /**
     * The value model_type stores for a model class, respecting the app's morph map. A value
     * that isn't a model class, like a morph alias, is returned as is.
     */
    public static function morphClassFor(string $type): string
    {
        return is_subclass_of($type, Model::class) ? (new $type)->getMorphClass() : $type;
    }

    /**
     * Whether a color is one of the palette's, or in advanced mode, a hex color.
     */
    public static function isValidColor(string $color): bool
    {
        if (in_array($color, self::COLORS, true)) {
            return true;
        }

        return config('tags.mode') === 'advanced' && preg_match('/^#[0-9a-f]{6}$/i', $color) === 1;
    }

    /**
     * Whether an icon is allowed. Icons are only available in advanced mode. When tags.icons is
     * '*', any name in Lucide's format (e.g. map-pin, up to 64 characters) is allowed; otherwise,
     * only the names it lists. Names aren't checked against Lucide, so one it doesn't have shows
     * no icon.
     */
    public static function isValidIcon(string $icon): bool
    {
        if (config('tags.mode') !== 'advanced') {
            return false;
        }

        $icons = static::icons();

        return $icons === null
            ? strlen($icon) <= 64 && preg_match(self::ICON_NAME_PATTERN, $icon) === 1
            : in_array($icon, $icons, true);
    }

    /**
     * The enabled icons, from the tags.icons config option: null when it's '*', for every
     * Lucide icon, or else the names it lists.
     *
     * @return list<string>|null
     */
    public static function icons(): ?array
    {
        /** @var '*'|list<string> $enabled */
        $enabled = config('tags.icons', '*');

        if ($enabled === '*') {
            return null;
        }

        return array_values(array_filter($enabled, fn (string $icon): bool => preg_match(self::ICON_NAME_PATTERN, $icon) === 1));
    }

    /**
     * Get all models of the given class that have this tag.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $related
     * @return MorphToMany<TModel, $this>
     */
    public function taggables(string $related): MorphToMany
    {
        return $this->morphedByMany($related, 'taggable');
    }

    /**
     * Find a tag by name, or create it if it doesn't exist. In advanced mode, tags belong to
     * a model, so this finds or creates $for's own tag, and $for is required.
     */
    public static function findOrCreateByName(string $name, ?Model $for = null): self
    {
        $name = static::normalizeName($name);

        if (! static::recordsModelType()) {
            return static::firstOrCreate(['name' => $name]);
        }

        if ($for === null) {
            throw new InvalidArgumentException('In advanced mode, a tag belongs to a model, so pass the model it\'s for.');
        }

        return static::firstOrCreate(['name' => $name, 'model_type' => $for->getMorphClass()]);
    }

    /**
     * Whether new tags record their model type: in advanced mode, when the tags table has the
     * model_type column. The column is missing if the tables were created in simple mode.
     */
    public static function recordsModelType(): bool
    {
        return config('tags.mode') === 'advanced'
            && Schema::hasColumn(static::query()->getModel()->getTable(), 'model_type');
    }

    /**
     * Lowercase and trim a tag name.
     */
    public static function normalizeName(string $name): string
    {
        return mb_strtolower(trim($name));
    }

    /**
     * Normalize the name whenever it's set.
     *
     * @return Attribute<string, string>
     */
    protected function name(): Attribute
    {
        return Attribute::make(set: fn (string $value) => static::normalizeName($value));
    }
}
