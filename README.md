<div align="center">
    <h1>Tags</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/spawaskar/tags"><img src="https://img.shields.io/packagist/v/spawaskar/tags.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/spawaskar/tags"><img src="https://img.shields.io/packagist/php-v/spawaskar/tags.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/spawaskar/tags"><img src="https://badge.laravel.cloud/badge/spawaskar/tags?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/spawaskar/tags/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/spawaskar/tags/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/spawaskar/tags"><img src="https://img.shields.io/packagist/dt/spawaskar/tags.svg?style=flat-square" alt="Total Downloads"></a>
</p>

This package allows you to add tags to any model

## Installation

You can install the package via Composer:

```bash
composer require spawaskar/tags
```

You may publish all of the package's resources at once:

```bash
php artisan vendor:publish --tag="tags"
```

Or, you may publish each resource individually:

### Publishing the Configuration File

```bash
php artisan vendor:publish --tag="tags-config"
```

### Publishing and Running the Migrations

```bash
php artisan vendor:publish --tag="tags-migrations"
php artisan migrate
```

### Publishing the Views

```bash
php artisan vendor:publish --tag="tags-views"
```

### Publishing the Vue Component

```bash
php artisan vendor:publish --tag="tags-vue"
```

### Publishing the Translations

```bash
php artisan vendor:publish --tag="tags-lang"
```

### Publishing the Public Assets

```bash
php artisan vendor:publish --tag="tags-assets"
```

## Usage

Add the `HasTags` trait to any model:

```php
use spawaskar\Tags\Concerns\HasTags;

class Post extends Model
{
    use HasTags;
}
```

Then tag it by name. Tags that don't exist yet are created, and names are trimmed and lowercased:

```php
$post->attachTags(['tag1', 'tag2']);
$post->detachTags('tag2');
$post->syncTags(['tag1', 'tag3']);   // exactly these tags
$post->hasTag('Tag1');               // true

Post::withAnyTags(['tag1', 'tag3'])->get();
Post::withAllTags(['tag1', 'tag3'])->get();
```

Find tags that aren't attached to any model:

```php
use spawaskar\Tags\Facades\TagManager;

TagManager::unused();
```

Give tags a color from a fixed palette (`gray`, `red`, `orange`, `yellow`, `green`, `blue`, `purple`). Tags without a color show as gray, and `null` resets a tag to gray:

```php
TagManager::setColors(['tag1' => 'red', 'tag2' => null]);
```

A color belongs to the tag, so it's the same everywhere the tag is used. Colors outside the palette throw an `InvalidArgumentException`; validate input with `Rule::in(Tag::COLORS)`.

### Blade

If your pages are Blade templates, show a model's tags with the `list` component:

```blade
<x-tags::list :model="$post" />
```

There's no Blade tag input. Submit the tags from your own form as an array (for example, inputs named `tags[]`) and save them with `syncTags()`.

### Vue

If your pages are built with Vue, the package includes two Vue 3 components (Vue 3.4 or later). After publishing them with `--tag="tags-vue"`, import them from your app:

```js
import TagInput from '@/vendor/tags/components/TagInput.vue';
import TagList from '@/vendor/tags/components/TagList.vue';
```

They're styled with Tailwind using shadcn-style theme colors (`bg-muted`, `border-input`, `text-muted-foreground` and so on), so they match the Laravel starter kits and dark mode. If your app doesn't define those colors, edit the published files.

#### Showing tags

`TagList` shows tags as chips. Pass tag names, or tag objects with a `name` and an optional `color`:

```vue
<TagList :tags="post.tags" />
```

#### Editing tags

`TagInput` lets users type a tag and press Enter or comma to add it, click × to remove it, or click one of the suggested tags. Pass the tags with `v-model` and a list of tag names with `suggestions`:

```php
use spawaskar\Tags\Models\Tag;

return view('posts.edit', [
    'post' => $post,
    'suggestions' => Tag::orderBy('name')->pluck('name'),
]);
```

```vue
<TagInput v-model="form.tags" :suggestions="suggestions" />
```

If some users shouldn't see tags other users created, only pass the names they're allowed to see.

Attributes like `id` and `aria-label` are passed to the text input, so a label can point at it. Tags are limited to 50 characters by default. Set `max-length` to match your validation:

```vue
<label for="tags">Tags</label>
<TagInput id="tags" v-model="form.tags" :max-length="30" />
```

In a regular (non-JavaScript) form submit, set `name` and the component renders hidden inputs for the tags:

```vue
<TagInput v-model="tags" name="tags" :suggestions="suggestions" />
```

The component doesn't save anything itself. Save the tags in your own controller:

```php
$post->syncTags($request->input('tags', []));
```

#### Tag colors

Users can click a tag in `TagInput` to pick its color. Pass the colors by tag name with `v-model:colors`:

```js
const form = useForm({
    tags: props.post.tags.map((tag) => tag.name),
    colors: Object.fromEntries(props.post.tags.map((tag) => [tag.name, tag.color])),
});
```

```vue
<TagInput v-model="form.tags" v-model:colors="form.colors" />
```

Then save them after the tags:

```php
use Illuminate\Validation\Rule;
use spawaskar\Tags\Facades\TagManager;
use spawaskar\Tags\Models\Tag;

$request->validate([
    'colors' => 'array',
    'colors.*' => ['nullable', Rule::in(Tag::COLORS)],
]);

$post->syncTags($request->input('tags', []));
TagManager::setColors($request->input('colors', []));
```

In a regular form submit, set `colors-name` to render hidden inputs for the colors, like `name` does for the tags.

The palette's Tailwind classes are in the published `tagColors.js`. You can restyle each color there, but keep the color names, since the server only accepts the names in `Tag::COLORS`.


## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Tags! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Sachin Pawaskar](https://github.com/spawaskar)
- [All Contributors](../../contributors)

## License

Tags is open-sourced software licensed under the [MIT license](LICENSE.md).
