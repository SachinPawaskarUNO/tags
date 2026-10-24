<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use spawaskar\Tags\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

// Switch a test to advanced mode. The test tables are created in simple mode, so this adds
// the columns the migration only creates in advanced mode.
function useAdvancedMode(): void
{
    config(['tags.mode' => 'advanced']);

    Schema::table('tags', function (Blueprint $table): void {
        $table->string('icon')->nullable();
        $table->string('model_type')->nullable();
    });
}
