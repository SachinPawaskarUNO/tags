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

        Schema::create('tags', function (Blueprint $table) use ($advanced) {
            $table->id();
            $table->string('name', 32)->unique();
            $table->string('color')->nullable();
            $table->string('description', 255)->nullable();
            $table->timestamps();

            if ($advanced) {
                $table->string('icon')->nullable();
                $table->string('model_type')->nullable();
            }
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
