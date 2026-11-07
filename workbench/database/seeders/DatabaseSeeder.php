<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use spawaskar\Tags\Facades\Tag;
use Workbench\App\Models\Post;
use Workbench\Database\Factories\UserFactory;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        UserFactory::new()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Posts with tags. attachTags() creates any tags that don't exist yet.
        Post::factory()->create(['title' => 'Recipes'])->attachTags(['blogPost']);
        Post::factory()->create(['title' => 'Article'])->attachTags(['interview', 'adviceColumn']);
        Post::factory()->create(['title' => 'Survey'])->attachTags(['blogPost', 'interview']);

        // Some posts without tags.
        Post::factory()->count(5)->create();

        // Colors and icons for some of the tags. Advanced mode also accepts hex colors, and icons
        // are Lucide icon names. Pass Post::class, since in advanced mode each model has its own tags.
        Tag::setColors(['blogPost' => 'red', 'adviceColumn' => '#0ea5e9', 'interview' => 'green'], Post::class);
        Tag::setIcons(['blogPost' => 'notebook-pen', 'interview' => 'mic'], Post::class);
    }
}
