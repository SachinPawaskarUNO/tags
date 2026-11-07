<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use spawaskar\Tags\Concerns\HasTags;

class Post extends Model
{
    use HasFactory, HasTags;

    protected $fillable = ['title', 'content'];
}
