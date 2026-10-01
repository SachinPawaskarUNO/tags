<?php

declare(strict_types=1);

namespace spawaskar\Tags\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use spawaskar\Tags\TagsServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            TagsServiceProvider::class,
        ];
    }
}
