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
import TagInput from '@/vendor/tags/components/simple/TagInput.vue';
import TagList from '@/vendor/tags/components/TagList.vue';
```

They're styled with Tailwind using shadcn-style theme colors (`bg-muted`, `border-input`, `text-muted-foreground` and so on), so they match the Laravel starter kits and dark mode. If your app doesn't define those colors, edit the published files.

#### Showing tags

`TagList` shows tags as chips. Pass tag names, or tag objects with a `name` and an optional `color`:

```vue
<TagList :tags="post.tags" />
```

#### Editing tags

`TagInput` lets users type a tag and press Enter or comma to add it, click × to remove it, or click one of the suggested tags. Pass the tags with `v-model` and the tag names to suggest with `suggestions`:

```php
use spawaskar\Tags\Facades\TagManager;

return view('posts.edit', [
    'post' => $post,
    'suggestions' => TagManager::suggestions(),
]);
```

```vue
<TagInput v-model="form.tags" :suggestions="suggestions" />
```

In simple mode, the suggestions are every tag's name, shown as buttons under the input. In advanced mode, they're grouped by the model the tags are on, e.g. `['Post' => ['pottery'], 'Video' => ['tutorial']]`, for the [advanced `TagInput`](#advanced-mode)'s dropdown.

If some users shouldn't see tags other users created, only pass the names they're allowed to see.

Attributes like `id` and `aria-label` are passed to the text input, so a label can point at it. Tags are limited to 32 characters by default, the longest name the `tags` table allows. Set `max-length` to match your validation:

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

#### Advanced mode

In advanced mode, import `TagInput` from the `advanced` folder instead of `simple`. It's used the same way, but shows suggestions in a dropdown with a heading for each model, and lets users pick any color with a color picker instead of the palette:

```js
import TagInput from '@/vendor/tags/components/advanced/TagInput.vue';
```

The color picker uses [shadcn.io's Vue color picker](https://www.shadcn.io/ui/vue/color-picker). Install it in your app first:

```bash
npm install @ark-ui/vue lucide-vue-next
```

Then add the color-picker component from https://www.shadcn.io/ui/vue/color-picker. The picker is written in TypeScript, which Laravel's Vue starter kit already supports. Apps in simple mode don't import anything from `advanced`, so they don't need these installs.

In advanced mode, each new tag also records the type of model it was created for, in `$tag->model_type` (for example `App\Models\Post`). It's set automatically when `attachTags()` or `syncTags()` creates the tag, and it's only a label: the tag can still be attached to any model.

The `icon` and `model_type` columns are only created when the migration runs in advanced mode. If you switch to advanced mode after migrating, add them with a migration of your own:

```php
Schema::table('tags', function (Blueprint $table) {
    $table->string('icon')->nullable();
    $table->string('model_type')->nullable();
});
```

Until then, new tags don't record their model type.

In advanced mode, tags also accept hex colors like `#eb5e41`. `Rule::in(Tag::COLORS)` would reject those, so validate with `Tag::isValidColor()` instead:

```php
$request->validate([
    'colors' => 'array',
    'colors.*' => ['nullable', 'string', fn ($attribute, $value, $fail) => Tag::isValidColor($value) || $fail('Not a tag color.')],
]);
```

#### Tag icons

In advanced mode, users can also give each tag an icon. Clicking a tag in the advanced `TagInput` shows the icons under the color picker. Pass the icons users can pick from the controller:

```php
return Inertia::render('Posts/Edit', [
    'post' => $post->load('tags'),
    'icons' => TagManager::icons(),
]);
```

Then pass the tags' icons by name with `v-model:icons`, like the colors:

```js
const form = useForm({
    tags: props.post.tags.map((tag) => tag.name),
    colors: Object.fromEntries(props.post.tags.map((tag) => [tag.name, tag.color])),
    icons: Object.fromEntries(props.post.tags.map((tag) => [tag.name, tag.icon])),
});
```

```vue
<TagInput v-model="form.tags" v-model:colors="form.colors" v-model:icons="form.icons" :available-icons="icons" />
```

Then validate and save them after the tags. A `null` icon removes the tag's icon:

```php
$request->validate([
    'icons' => 'array',
    'icons.*' => ['nullable', 'string', fn ($attribute, $value, $fail) => Tag::isValidIcon($value) || $fail('Not a tag icon.')],
]);

TagManager::setIcons($request->input('icons', []));
```

In a regular form submit, set `icons-name` to render hidden inputs for the icons.

To show icons in `TagList`, pass it the icon components:

```vue
<script setup>
import { tagIcons } from '@/vendor/tags/tagIcons.js';
</script>

<TagList :tags="post.tags" :icons="tagIcons" />
```

The icons come from [Lucide](https://lucide.dev/icons). The package supports the icons in `Tag::ICONS`, and the `icons` config option picks which of them users can pick. To enable more, publish the config with `--tag="tags-config"` and add their names:

```php
// config/tags.php
'icons' => ['file', 'folder'],
```

Names that aren't in `Tag::ICONS` are ignored. To use an icon the package doesn't support, import its component from `lucide-vue-next` in the published `tagIcons.js`, and extend the `Tag` model (see the `model` config option) to add its name to `ICONS`.


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
