<?php

declare(strict_types=1);

namespace spawaskar\Tags\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * @property int $id
 * @property string $name
 * @property string|null $color
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Tag extends Model
{
    /**
     * The colors a tag can have. Keep in sync with resources/js/tagColors.js.
     */
    public const COLORS = ['gray', 'red', 'orange', 'yellow', 'green', 'blue', 'purple'];

    protected $fillable = [
        'name',
        'color',
        'description',
    ];

    /**
     * Refuse to save a tag with a blank name or a color outside the palette.
     */
    protected static function booted(): void
    {
        static::saving(function (Tag $tag): void {
            if ($tag->name === '') {
                throw new InvalidArgumentException('A tag name cannot be blank.');
            }

            if ($tag->color !== null && ! in_array($tag->color, self::COLORS, true)) {
                throw new InvalidArgumentException("[{$tag->color}] is not a tag color.");
            }
        });
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
     * Find a tag by name, or create it if it doesn't exist.
     */
    public static function findOrCreateByName(string $name): self
    {
        return static::firstOrCreate(['name' => static::normalizeName($name)]);
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
