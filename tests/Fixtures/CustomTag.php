<?php

declare(strict_types=1);

namespace spawaskar\Tags\Tests\Fixtures;

use spawaskar\Tags\Models\Tag;

class CustomTag extends Tag
{
    protected $table = 'tags';
}
