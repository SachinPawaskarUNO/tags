<?php

declare(strict_types=1);

namespace spawaskar\Tags\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use spawaskar\Tags\Models\Tag;

// Demo tags for trying the package: the tags.demo_tags tags, on existing records of the
// tags.models models. Running it again doesn't add anything new.
class TagsDemoSeeder extends Seeder
{
    /**
     * The most records to tag for each model.
     */
    public const int RECORDS_PER_MODEL = 10;

    /**
     * Add the demo tags, to only the given team's records if there is one.
     */
    public function run(int|string|null $teamId = null): void
    {
        if (app()->isProduction()) {
            $this->command->warn('The tags demo seeder does not run in production.');

            return;
        }

        /** @var array<string, array{color?: string, icon?: string}> $demoTags */
        $demoTags = config('tags.demo_tags', []);

        $model = Tag::modelClass();

        // Only a simple mode tag without a team can exist without a record to take them from.
        if (! $model::recordsModelType() && ! $model::recordsTeam()) {
            foreach ($demoTags as $name => $details) {
                $this->fillDetails($model::findOrCreateByName($name), $details);
            }
        }

        $tagged = 0;

        /** @var list<class-string<Model>> $classes */
        $classes = config('tags.models', []);

        foreach ($classes as $class) {
            $tagged += $this->tagRecords($class, $demoTags, $teamId);
        }

        if ($tagged === 0) {
            $this->command->warn('No records were tagged. Check the tags.demo_tags and tags.models config options, and that those models have records.');
        }
    }

    /**
     * Give a model's first records the demo tags in a fixed rotation, one or two tags each. With
     * teams on, only records that have a team, since a tag takes its team from the record, and
     * if a team was passed, only that team's.
     *
     * @param  class-string<Model>  $class
     * @param  array<string, array{color?: string, icon?: string}>  $demoTags
     * @return int How many records were tagged.
     */
    protected function tagRecords(string $class, array $demoTags, int|string|null $teamId): int
    {
        if ($demoTags === []) {
            return 0;
        }

        $model = Tag::modelClass();
        $recordsTeam = $model::recordsTeam();
        $names = array_keys($demoTags);
        $tagged = 0;

        foreach ($class::query()->lazy() as $record) {
            if (! method_exists($record, 'attachTags') || ! method_exists($record, 'tagTeamId')) {
                $this->command->warn("Skipped {$class}, since it doesn't use the HasTags trait.");

                return 0;
            }

            if ($recordsTeam && ($record->tagTeamId() === null || ($teamId !== null && $record->tagTeamId() != $teamId))) {
                continue;
            }

            $tags = [$names[$tagged % count($names)]];

            if ($tagged % 2 === 1) {
                $tags[] = $names[($tagged + 1) % count($names)];
            }

            foreach ($tags as $name) {
                $this->fillDetails($model::findOrCreateByName($name, $record), $demoTags[$name]);
            }

            $record->attachTags($tags);

            if (++$tagged === self::RECORDS_PER_MODEL) {
                break;
            }
        }

        return $tagged;
    }

    /**
     * Set a tag's color and icon if it doesn't have them yet, so an app's own choices are kept.
     * Icons are skipped outside advanced mode, or if they aren't in the tags.icons option.
     *
     * @param  array{color?: string, icon?: string}  $details
     */
    protected function fillDetails(Tag $tag, array $details): void
    {
        $tag->color ??= $details['color'] ?? null;

        if ($tag::recordsModelType() && isset($details['icon']) && $tag::isValidIcon($details['icon'])) {
            $tag->icon ??= $details['icon'];
        }

        $tag->save();
    }
}
