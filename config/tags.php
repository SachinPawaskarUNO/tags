<?php

declare(strict_types=1);
use spawaskar\Tags\Models\Tag;

return [

    /*
     * 'simple'   – tags with just a name and pre-set color.
     * 'advanced' – tags with a color picker and icon.
     */
    'mode' => 'simple',

    /*
     * The tag model. To add behavior, extend spawaskar\Tags\Models\Tag and set your class here.
     */
    'model' => Tag::class,

    /*
     * The icons users can pick in advanced mode. Available icons: see Tag::ICONS.
     * Icons are provided by Lucide (https://lucide.dev/icons).
     */
    'icons' => ['file', 'folder'],

    /*
     * The models users can choose from when creating a tag in advanced mode,
     * e.g. [App\Models\Post::class, App\Models\Video::class].
     */
    'models' => [],

];
