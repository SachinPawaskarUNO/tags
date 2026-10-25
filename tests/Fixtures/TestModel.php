<?php

declare(strict_types=1);

namespace spawaskar\Tags\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use spawaskar\Tags\Concerns\HasTags;

class TestModel extends Model
{
    use HasTags;

    protected $table = 'test_models';

    protected $guarded = [];
}
