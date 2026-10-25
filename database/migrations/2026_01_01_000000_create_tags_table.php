<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $advanced = config('tags.mode') === 'advanced';
        $teams = (bool) config('tags.teams');

        Schema::create('tags', function (Blueprint $table) use ($advanced, $teams) {
            $table->id();
            $table->string('name', 32);
            $table->string('color')->nullable();
            $table->string('description', 255)->nullable();
            $table->timestamps();

            // With teams on, every tag belongs to one team, and names are unique per team.
            if ($teams) {
                $table->foreignId('team_id');
            }

            // In advanced mode, every tag belongs to one model, and names are unique per model,
            // so two models can each have their own tag with the same name.
            if ($advanced) {
                $table->string('icon')->nullable();
                $table->string('model_type');
            }

            $table->unique([
                ...($teams ? ['team_id'] : []),
                ...($advanced ? ['model_type'] : []),
                'name',
            ]);
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->morphs('taggable');
            $table->unique(['tag_id', 'taggable_id', 'taggable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
    }
};
