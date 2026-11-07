<?php

declare(strict_types=1);

namespace spawaskar\Tags\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * @property int $id
 * @property string $name
 * @property string|null $color
 * @property string|null $icon
 * @property string|null $model_type The model the tag belongs to. Required in advanced mode.
 * @property string|null $description
 * @property int|null $team_id The team the tag belongs to. Required when tags.teams is on.
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
        'team_id',
    ];

    /**
     * The longest description a tag can have, matching the description column.
     */
    public const int MAX_DESCRIPTION_LENGTH = 255;

    /**
     * Refuse to save a tag with a blank name, a description that's too long, a color or icon
     * that isn't valid, in advanced mode, no model type, or with teams on, no team.
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

            if (blank($tag->team_id) && static::recordsTeam()) {
                throw new InvalidArgumentException('A tag needs a team when tags.teams is on.');
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
     * Limit a query to one team's tags, when teams are on. With teams off, or without a team,
     * every tag.
     *
     * @param  Builder<static>  $query
     */
    public function scopeForTeam(Builder $query, int|string|null $team): void
    {
        if ($team !== null && static::recordsTeam()) {
            $query->where('team_id', $team);
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
     * a model, and with teams on, to a team, so this finds or creates $for's own tag, and $for
     * is required.
     */
    public static function findOrCreateByName(string $name, ?Model $for = null): self
    {
        $attributes = ['name' => static::normalizeName($name)];

        if (! static::recordsModelType() && ! static::recordsTeam()) {
            return static::firstOrCreate($attributes);
        }

        if ($for === null) {
            throw new InvalidArgumentException('In advanced mode or with teams on, a tag belongs to a model, so pass the model it\'s for.');
        }

        if (static::recordsModelType()) {
            $attributes['model_type'] = $for->getMorphClass();
        }

        if (static::recordsTeam()) {
            $attributes['team_id'] = method_exists($for, 'tagTeamId') ? $for->tagTeamId() : null;
        }

        return static::firstOrCreate($attributes);
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
     * Whether new tags record their team: when tags.teams is on and the tags table has the
     * team_id column. The column is missing if the tables were created with teams off.
     */
    public static function recordsTeam(): bool
    {
        return (bool) config('tags.teams')
            && Schema::hasColumn(static::query()->getModel()->getTable(), 'team_id');
    }

    /**
     * Lowercase and trim a tag name.
     */
    public static function normalizeName(string $name): string
    {
        return mb_strtolower(trim($name));
    }

    /**
     * The tag model class, set by the tags.model config option.
     *
     * @return class-string<Tag>
     */
    public static function modelClass(): string
    {
        /** @var class-string<Tag> $model */
        $model = config('tags.model', self::class);

        return $model;
    }

    /**
     * Tags that aren't on anything: no record has them attached, e.g. new tags never used yet.
     *
     * @return Collection<int, Tag>
     */
    public static function unused(): Collection
    {
        return static::modelClass()::query()
            ->whereNotExists(static::usages(...))
            ->orderBy('name')
            ->get();
    }

    /**
     * Tags attached to at least one record of the given class, e.g. Post::class.
     *
     * @param  class-string<Model>  $type
     * @return Collection<int, Tag>
     */
    public static function usedOn(string $type): Collection
    {
        // Respects the app's morph map, if it has one.
        $morphClass = (new $type)->getMorphClass();

        return static::modelClass()::query()
            ->whereExists(fn (QueryBuilder $query) => static::usages($query)->where('taggables.taggable_type', $morphClass))
            ->orderBy('name')
            ->get();
    }

    /**
     * The classes of the models that have tags, e.g. ['App\Models\Post'].
     *
     * @return list<string>
     */
    public static function types(): array
    {
        return array_values(DB::table('taggables')
            ->distinct()
            ->orderBy('taggable_type')
            ->pluck('taggable_type')
            ->map(fn (string $type): string => Relation::getMorphedModel($type) ?? $type)
            ->all());
    }

    /**
     * Tag names for TagInputSimple's or TagInputAdvanced's suggestions. In simple mode, every
     * tag's name, shown as buttons.
     * In advanced mode, pass the page's model, e.g. Post::class, for that model's tags, most-used
     * first, so TagInputAdvanced can show the most-used ones before the user types. Without a model,
     * every tag, grouped by its model. With teams on, pass the team, for only that team's tags.
     *
     * @param  class-string<Model>|null  $type
     * @return list<string>|array<string, list<string>>
     */
    public static function suggestions(?string $type = null, int|string|null $team = null): array
    {
        if (config('tags.mode') !== 'advanced') {
            return static::names($team);
        }

        // Without the model_type column, fall back to where tags are attached.
        if (! static::modelClass()::recordsModelType()) {
            return $type === null
                ? static::namesByType()
                : array_values(static::usedOn($type)->pluck('name')->all());
        }

        if ($type === null) {
            return static::namesByModelType($team);
        }

        $table = static::modelClass()::query()->getModel()->getTable();

        return array_values(static::modelClass()::query()
            ->forModel($type)
            ->forTeam($team)
            ->orderByDesc(DB::table('taggables')->selectRaw('count(*)')->whereColumn('taggables.tag_id', "{$table}.id"))
            ->orderBy('name')
            ->pluck('name')
            ->all());
    }

    /**
     * Every tag's name, or with teams on, a team's tag names.
     *
     * @return list<string>
     */
    public static function names(int|string|null $team = null): array
    {
        return array_values(static::modelClass()::query()->forTeam($team)->orderBy('name')->pluck('name')->all());
    }

    /**
     * Tags' color, icon, and description, by name. Pass it to TagInputAdvanced's details,
     * so existing tags show their color and icon as soon as they're picked. In advanced mode,
     * pass the page's model, e.g. Post::class, since other models can have tags with the same names.
     * With teams on, pass the team too, since other teams can as well.
     *
     * @param  class-string<Model>|null  $type
     * @return array<string, array{color: string|null, icon: string|null, description: string|null}>
     */
    public static function details(?string $type = null, int|string|null $team = null): array
    {
        $details = [];

        foreach (static::modelClass()::query()->forModel($type)->forTeam($team)->orderBy('name')->get() as $tag) {
            $details[$tag->name] = [
                'color' => $tag->color,
                'icon' => $tag->icon,
                'description' => $tag->description,
            ];
        }

        return $details;
    }

    /**
     * Every tag's name, or with teams on, a team's tag names, grouped by the model it belongs to.
     *
     * @return array<string, list<string>>
     */
    public static function namesByModelType(int|string|null $team = null): array
    {
        $groups = [];

        $tags = static::modelClass()::query()->forTeam($team)->whereNotNull('model_type')->orderBy('model_type')->orderBy('name')->get(['name', 'model_type']);

        foreach ($tags as $tag) {
            $groups[class_basename(Relation::getMorphedModel((string) $tag->model_type) ?? (string) $tag->model_type)][] = $tag->name;
        }

        return $groups;
    }

    /**
     * Every tag in use, grouped by the type of model it's attached to.
     * Pass it to TagInputAdvanced's suggestions to show a heading for each model.
     *
     * @return array<string, list<string>>
     */
    public static function namesByType(): array
    {
        $groups = [];

        $rows = static::modelClass()::query()
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
     * Set tag colors by name, e.g. ['pottery' => 'red']. A null color resets the tag to the
     * default. Names that aren't tags yet are skipped. In advanced mode, pass the model whose
     * tags these are, e.g. Post::class, and with teams on, the team.
     *
     * @param  array<string, string|null>  $colors
     * @param  class-string<Model>|null  $type
     */
    public static function setColors(array $colors, ?string $type = null, int|string|null $team = null): void
    {
        foreach ($colors as $name => $color) {
            static::findByName((string) $name, $type, $team)?->update(['color' => $color]);
        }
    }

    /**
     * Set tag icons by name, e.g. ['file' => 'star']. A null icon removes the tag's icon.
     * Advanced mode only, so pass the model whose tags these are, e.g. Post::class, and with
     * teams on, the team.
     *
     * @param  array<string, string|null>  $icons
     * @param  class-string<Model>|null  $type
     */
    public static function setIcons(array $icons, ?string $type = null, int|string|null $team = null): void
    {
        foreach ($icons as $name => $icon) {
            static::findByName((string) $name, $type, $team)?->update(['icon' => $icon]);
        }
    }

    /**
     * Set tag descriptions by name, e.g. ['pottery' => 'Ceramic fragments']. A blank or null
     * description removes the tag's description. In advanced mode, pass the model whose tags
     * these are, e.g. Post::class, and with teams on, the team.
     *
     * @param  array<string, string|null>  $descriptions
     * @param  class-string<Model>|null  $type
     */
    public static function setDescriptions(array $descriptions, ?string $type = null, int|string|null $team = null): void
    {
        foreach ($descriptions as $name => $description) {
            $description = trim((string) $description);

            static::findByName((string) $name, $type, $team)?->update(['description' => $description === '' ? null : $description]);
        }
    }

    /**
     * The models users can choose from when creating a tag in advanced mode, from the
     * tags.models config option. Pass it to TagInputAdvanced's models.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function models(): array
    {
        /** @var list<class-string<Model>> $classes */
        $classes = config('tags.models', []);

        return array_map(fn (string $class): array => [
            'value' => (new $class)->getMorphClass(),
            'label' => class_basename($class),
        ], $classes);
    }

    /**
     * A tag by name, among the given model's tags in advanced mode, and the given team's with teams on.
     */
    protected static function findByName(string $name, ?string $type, int|string|null $team = null): ?Tag
    {
        return static::modelClass()::query()->forModel($type)->forTeam($team)->where('name', static::normalizeName($name))->first();
    }

    /**
     * Limit a subquery to the pivot rows for the tag in the outer query.
     */
    protected static function usages(QueryBuilder $query): QueryBuilder
    {
        return $query->from('taggables')->whereColumn('taggables.tag_id', 'tags.id');
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
