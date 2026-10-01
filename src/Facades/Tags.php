<?php

declare(strict_types=1);

namespace spawaskar\Tags\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \spawaskar\Tags\Tags
 */
class Tags extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \spawaskar\Tags\Tags::class;
    }
}
