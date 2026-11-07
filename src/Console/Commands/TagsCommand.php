<?php

declare(strict_types=1);

namespace spawaskar\Tags\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use spawaskar\Tags\Models\Tag;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

// Asks how the app will use tags, saves the answers in its config file, and sets up the tables.
class TagsCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'tags:install';

    /**
     * The command description.
     */
    protected $description = 'Choose the tags settings, save them to config/tags.php, and set up the tables.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->saveSettings($this->askForSettings());
        $this->setUpTables();
        $this->showNextSteps();

        return self::SUCCESS;
    }

    /**
     * Ask about each setting. Follow-up questions only appear
     * when they matter, and settings that aren't asked about stay as they are.
     *
     * @return array<string, string|bool|list<string>>
     */
    protected function askForSettings(): array
    {
        // Plain lists of options, since on Windows users type the option instead of picking it.
        /** @var string $mode */
        $mode = select(
            label: 'Which mode do you want? Advanced adds any color, icons, descriptions, and tags per model.',
            options: ['simple', 'advanced'],
            default: (string) config('tags.mode', 'simple'),
        );

        $teams = confirm(
            label: 'Does your application need to support access across different teams?',
            default: (bool) config('tags.teams'),
        );

        $settings = ['mode' => $mode, 'teams' => $teams, 'manager.enabled' => false];

        if ($teams) {
            $settings['team_column'] = text(
                label: 'Which attribute on tagged records holds their team?',
                default: (string) config('tags.team_column', 'team_id'),
                required: true,
            );
        }

        // Models, icons, and the tag manager page are only for advanced mode.
        if ($mode !== 'advanced') {
            return $settings;
        }

        $settings['models'] = $this->askForModels();
        $settings['icons'] = $this->askForIcons();
        $settings['manager.enabled'] = confirm(
            label: 'Turn on the tag manager page?',
            default: (bool) config('tags.manager.enabled'),
        );

        if (! $settings['manager.enabled']) {
            return $settings;
        }

        $settings['manager.path'] = trim(text(
            label: 'What URL should the tag manager page be at?',
            default: (string) config('tags.manager.path', 'tags'),
            required: true,
        ), '/');

        /** @var list<string> $middleware */
        $middleware = config('tags.manager.middleware', ['web', 'auth']);

        // A comma before a number is part of one middleware, like throttle:60,1.
        $settings['manager.middleware'] = $this->parseList(text(
            label: 'Which middleware should protect it? Separate them with commas.',
            default: implode(', ', $middleware),
            required: true,
            hint: 'Keep auth or your own admin middleware, since anyone who can open the page can delete tags.',
        ), '/,(?!\d)/');

        $settings['manager.component'] = text(
            label: 'Which Inertia page component should it render?',
            default: (string) config('tags.manager.component', 'tags/Manager'),
            required: true,
        );

        if ($teams) {
            $settings['manager.team'] = text(
                label: 'Which attribute on the logged-in user holds their current team?',
                default: (string) config('tags.manager.team', 'current_team_id'),
                required: true,
            );
        }

        return $settings;
    }

    /**
     * The models users can create tags for, checking that each one is an Eloquent model.
     *
     * @return list<string>
     */
    protected function askForModels(): array
    {
        /** @var list<string> $models */
        $models = config('tags.models', []);

        $answer = text(
            label: 'Which models will have tags? Separate them with commas.',
            placeholder: 'App\Models\Post, App\Models\Video',
            default: implode(', ', $models),
            validate: function (string $value): ?string {
                foreach ($this->parseList($value) as $model) {
                    if (! is_subclass_of(ltrim($model, '\\'), Model::class)) {
                        return "[{$model}] isn't an Eloquent model.";
                    }
                }

                return null;
            },
            hint: 'The New tag popup and the tag manager page can only create tags for these models.',
        );

        return array_map(fn (string $model): string => ltrim($model, '\\'), $this->parseList($answer));
    }

    /**
     * The icons users can pick: '*' for every Lucide icon, or a list of names.
     *
     * @return string|list<string>
     */
    protected function askForIcons(): string|array
    {
        $icons = config('tags.icons', '*');

        $choice = select(
            label: 'Which icons can users pick? All of the Lucide icons, or only a list you choose?',
            options: ['all', 'list'],
            default: is_array($icons) ? 'list' : 'all',
        );

        if ($choice === 'all') {
            return '*';
        }

        return $this->parseList(text(
            label: 'Which icons? Separate them with commas.',
            placeholder: 'star, flag, map-pin',
            default: is_array($icons) ? implode(', ', $icons) : '',
            required: true,
            validate: function (string $value): ?string {
                foreach ($this->parseList($value) as $icon) {
                    if (preg_match(Tag::ICON_NAME_PATTERN, $icon) !== 1) {
                        return "[{$icon}] isn't an icon name, like map-pin.";
                    }
                }

                return null;
            },
            hint: 'Names from https://lucide.dev/icons. They aren\'t checked against Lucide, so one it doesn\'t have shows no icon.',
        ));
    }

    /**
     * Split an answer into a list, without blanks.
     *
     * @return list<string>
     */
    protected function parseList(string $answer, string $separator = '/,/'): array
    {
        return array_values(array_filter(array_map(trim(...), preg_split($separator, $answer) ?: [])));
    }

    /**
     * Save the answers in config/tags.php, publishing it first if needed. Only these settings
     * change, so the rest of the file, including the app's own edits, stays as it is.
     *
     * @param  array<string, string|bool|list<string>>  $settings
     */
    protected function saveSettings(array $settings): void
    {
        $this->callSilently('vendor:publish', ['--tag' => 'tags-config']);

        $path = config_path('tags.php');
        $contents = (string) file_get_contents($path);

        foreach ($settings as $key => $value) {
            $code = $this->toPhp($key, $value);
            $section = str_starts_with($key, 'manager.') ? "'manager'\s*=>\s*\[.*?" : '';
            $name = Str::afterLast($key, '.');

            // Only a plain value can be replaced, not something like env('TAGS_MODE').
            $contents = (string) preg_replace_callback(
                "/({$section}'{$name}'\s*=>\s*)(?:'[^']*'|true|false|\[[^\]]*\])/s",
                fn (array $match): string => $match[1].$code,
                $contents,
                1,
                $count,
            );

            if ($count === 0) {
                $this->components->warn("Couldn't update {$key} in config/tags.php. Set it to {$code} by hand.");
            }

            // Config was loaded when the command started, and the migration reads these settings.
            config(["tags.{$key}" => $value]);
        }

        file_put_contents($path, $contents);

        $this->components->info('Saved your settings in config/tags.php.');
    }

    /**
     * A setting's value, written as PHP code for the config file.
     *
     * @param  string|bool|list<string>  $value
     */
    protected function toPhp(string $key, string|bool|array $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            $items = array_map(fn (string $item): string => $key === 'models' ? "{$item}::class" : $this->toPhp($key, $item), $value);

            return '['.implode(', ', $items).']';
        }

        return "'".addslashes($value)."'";
    }

    /**
     * Publish the migration and offer to run it. Only this migration runs, not the app's other
     * pending ones. If the tables already exist, warn when they don't match the settings.
     */
    protected function setUpTables(): void
    {
        $table = (new (Tag::modelClass()))->getTable();

        if (rescue(fn (): bool => Schema::hasTable($table), false, false)) {
            if (Schema::hasColumn($table, 'model_type') !== (config('tags.mode') === 'advanced') || Schema::hasColumn($table, 'team_id') !== (bool) config('tags.teams')) {
                $this->components->warn("The {$table} table doesn't match these settings. The mode and teams settings only choose its columns when it's first created, so change its columns with a migration of your own.");
            }

            return;
        }

        if ($this->publishedMigration() === null) {
            $this->callSilently('vendor:publish', ['--tag' => 'tags-migrations']);
        }

        $migration = $this->publishedMigration();

        if ($migration !== null && confirm(label: 'Create the tags tables now?', default: false)) {
            $this->call('migrate', ['--path' => $migration, '--realpath' => true]);

            return;
        }

        $this->components->info('The migration is published. Run php artisan migrate when you\'re ready.');
    }

    /**
     * The tags migration in the app, if it's been published.
     */
    protected function publishedMigration(): ?string
    {
        $migrations = glob(database_path('migrations/*_create_tags_table.php')) ?: [];

        return $migrations[0] ?? null;
    }

    /**
     * What's left for the app to do by hand.
     */
    protected function showNextSteps(): void
    {
        /** @var list<string> $models */
        $models = config('tags.models', []);

        $steps = config('tags.mode') === 'advanced' && $models !== []
            ? array_map(fn (string $model): string => "Add the spawaskar\\Tags\\Concerns\\HasTags trait to {$model}.", $models)
            : ['Add the spawaskar\Tags\Concerns\HasTags trait to the models you want to tag.'];

        $steps[] = 'To use the Vue components, run: php artisan vendor:publish --tag="tags-vue"';

        if (config('tags.manager.enabled') === true && ! class_exists(Inertia::class)) {
            $steps[] = 'The tag manager page needs Inertia. Run: composer require inertiajs/inertia-laravel';
        }

        $this->components->bulletList($steps);
    }
}
