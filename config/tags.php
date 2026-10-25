<?php

declare(strict_types=1);
use spawaskar\Tags\Models\Tag;

return [

    /*
     * 'simple'   – tags with a name and a color from a preset palette.
     * 'advanced' – adds any color from a color picker, icons, and descriptions. Tags are created
     *              for a model with a New tag popup, and each page shows its model's tags.
     *
     * Set this before running the migration: advanced mode adds the icon and model_type columns.
     */
    'mode' => 'simple',

    /*
     * Give each tag a team, so each team has its own tags and two teams can each have a tag
     * with the same name. Set this before running the migration: it adds the team_id column.
     */
    'teams' => false,

    /*
     * With teams on, the attribute on tagged records that holds their team, e.g. a Case's team_id.
     * A new tag gets the team of the record it's first attached to. For a model whose team is
     * somewhere else, override tagTeamId() on that model.
     */
    'team_column' => 'team_id',

    /*
     * The tag model. To add behavior, extend spawaskar\Tags\Models\Tag and set your class here.
     */
    'model' => Tag::class,

    /*
     * The icons users can pick in advanced mode, from Lucide (https://lucide.dev/icons).
     * '*' offers every Lucide icon. To offer fewer, list their names, e.g. ['star', 'flag', 'map-pin'].
     */
    'icons' => '*',

    /*
     * The models users can choose from when creating a tag in advanced mode,
     * e.g. [App\Models\Post::class, App\Models\Video::class].
     */
    'models' => [],

    /*
     * The tag manager page, for listing, searching, creating, and deleting tags in advanced mode.
     * It's an Inertia page, so it needs inertiajs/inertia-laravel and the page component from
     * the README's tag manager section. The routes are only registered when it's enabled.
     */
    'manager' => [
        'enabled' => false,

        // The URL the page is at, e.g. 'admin/tags'.
        'path' => 'tags',

        // Keep auth (or your own admin middleware), since anyone who can open the page can delete tags.
        'middleware' => ['web', 'auth'],

        // The Inertia page component to render, e.g. resources/js/pages/tags/Manager.vue.
        'component' => 'tags/Manager',

        // With teams on, the attribute on the logged-in user that holds their current team.
        // The page only shows, creates, and deletes that team's tags.
        'team' => 'current_team_id',
    ],

];
