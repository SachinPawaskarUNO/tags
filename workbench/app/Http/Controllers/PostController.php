<?php

namespace Workbench\App\Http\Controllers;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use spawaskar\Tags\Facades\Tag;
use spawaskar\Tags\Models\Tag as TagModel;
use Workbench\App\Models\Post;

// Uses the package's advanced mode the way the README's advanced mode section describes.
class PostController
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => 'nullable|string',
            'tags' => 'array',
            'tags.*' => 'string',
        ]);

        return Inertia::render('Posts/Index', [
            'posts' => Post::with('tags')
                ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('title', 'like', "%{$search}%"))
                ->when($filters['tags'] ?? null, fn ($query, $tags) => $query->withAnyTags($tags))
                ->orderBy('id')
                ->get(),
            'filters' => $filters,
            'tags' => Tag::suggestions(Post::class),
            'details' => Tag::details(Post::class),
        ]);
    }

    public function edit(Post $post): Response
    {
        return Inertia::render('Posts/Edit', [
            'post' => $post->load('tags'),
            'suggestions' => Tag::suggestions(Post::class),
            'details' => Tag::details(Post::class),
            'models' => Tag::models(),
            'model' => $post->getMorphClass(),
            'icons' => Tag::icons(),
        ]);
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $request->validate([
            'tags' => 'array',
            'tags.*' => 'string|max:32',
            'colors' => 'array',
            'colors.*' => ['nullable', 'string', fn (string $attribute, string $value, Closure $fail) => TagModel::isValidColor($value) || $fail('Not a tag color.')],
            'icons' => 'array',
            'icons.*' => ['nullable', 'string', fn (string $attribute, string $value, Closure $fail) => TagModel::isValidIcon($value) || $fail('Not a tag icon.')],
            'descriptions' => 'array',
            'descriptions.*' => 'nullable|string|max:'.TagModel::MAX_DESCRIPTION_LENGTH,
        ]);

        $post->syncTags($request->input('tags', []));
        Tag::setColors($request->input('colors', []), Post::class);
        Tag::setIcons($request->input('icons', []), Post::class);
        Tag::setDescriptions($request->input('descriptions', []), Post::class);

        return redirect('/posts');
    }
}
