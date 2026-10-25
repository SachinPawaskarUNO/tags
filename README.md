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

This package allows you to add tags to any model.

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

If you'll use advanced mode, publish the config and set `'mode' => 'advanced'` first. The migration reads the mode to decide which columns to create.

```bash
php artisan vendor:publish --tag="tags-migrations"
php artisan migrate
```

### Publishing the Views

```bash
php artisan vendor:publish --tag="tags-views"
```

### Publishing the Vue Components

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

If your pages are built with Vue, the package includes Vue 3 components (Vue 3.4 or later). After publishing them with `--tag="tags-vue"`, import the ones you need from your app:

```js
import TagInput from '@/vendor/tags/components/simple/TagInput.vue';
import TagList from '@/vendor/tags/components/TagList.vue';
```

They're styled with Tailwind using shadcn-style theme colors (`bg-muted`, `border-input`, `text-muted-foreground` and so on), so they match the Laravel starter kits and dark mode. If your app doesn't define those colors, edit the published files.

#### Showing tags

`TagList` shows tags as chips. Pass tag names, or tag objects with a `name` and an optional `color` and `icon` (to show icons, see [Tag icons](#tag-icons)):

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

In simple mode, the suggestions are every tag's name, shown as buttons under the input. In advanced mode, they're the tags created for a model, shown in their colors in a dropdown under the input and filtered as the user types; see [Creating tags](#creating-tags).

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

In advanced mode, import `TagInput` from the `advanced` folder instead of `simple`. It's used the same way, but shows the page's model's tags in a dropdown under the input with their colors and icons, makes new tags with a popup, and lets users pick any color with a color picker instead of the palette:

```js
import TagInput from '@/vendor/tags/components/advanced/TagInput.vue';
```

The color picker is built on [Reka UI](https://reka-ui.com) and the icons come from [Lucide](https://lucide.dev), both of which Laravel's Vue starter kit already includes. If your app doesn't have them, install them first:

```bash
npm install reka-ui @lucide/vue
```

Icons come from your app's installed `@lucide/vue`, so the icons users can pick are the ones that version has. To get Lucide's newest icons, update it with `npm install @lucide/vue@latest`.

The picker is written in TypeScript, which the starter kit also supports. Apps in simple mode don't import anything from `advanced`, so they don't need these.

In advanced mode, every tag belongs to one model, in `$tag->model_type` (for example `App\Models\Post`), and is only used on that model. Tag names are unique per model, so posts and videos can each have their own `pottery` tag, with its own color, icon, and description. `attachTags()` and `syncTags()` find or create the model's own tags, and creating a tag without a model throws an `InvalidArgumentException`.

The `icon` and `model_type` columns, and the per-model unique index, are only created when the migration runs in advanced mode. Set the mode before migrating. If you switch to advanced mode after migrating, give every existing tag a model type and update the table with a migration of your own:

```php
Schema::table('tags', function (Blueprint $table) {
    $table->string('icon')->nullable();
    $table->string('model_type');           // fill this in for existing tags first
    $table->dropUnique(['name']);
    $table->unique(['model_type', 'name']);
});
```

Until then, tags work as in simple mode: one tag per name, shared by every model.

In advanced mode, tags also accept hex colors like `#eb5e41`. `Rule::in(Tag::COLORS)` would reject those, so validate with `Tag::isValidColor()` instead:

```php
$request->validate([
    'colors' => 'array',
    'colors.*' => ['nullable', 'string', fn ($attribute, $value, $fail) => Tag::isValidColor($value) || $fail('Not a tag color.')],
]);
```

#### Creating tags

In advanced mode, typing in `TagInput` only adds the page's model's tags. New tags are made with the **New tag** button, which opens a popup for the tag's name, description, color, and icon. If a user types a name that isn't one of the model's tags yet, `TagInput` offers to open the popup for it. Nothing is saved until the form is.

New tags are always for the page's model. The popup shows the model, but doesn't let users change it. List your models in the config, so the popup (and the tag manager page) can name them:

```php
// config/tags.php
'models' => [App\Models\Post::class, App\Models\Video::class],
```

Pass the page's model to `TagManager::suggestions()` and `TagManager::details()`, so users only see that model's tags, with their saved colors and icons as soon as they're picked:

```php
return Inertia::render('Posts/Edit', [
    'post' => $post->load('tags'),
    'suggestions' => TagManager::suggestions(Post::class),
    'details' => TagManager::details(Post::class),
    'models' => TagManager::models(),
    'model' => $post->getMorphClass(),
    'icons' => TagManager::icons(),
]);
```

Without a model, `TagManager::suggestions()` returns every tag, grouped by its model.

```js
const form = useForm({
    tags: props.post.tags.map((tag) => tag.name),
    colors: Object.fromEntries(props.post.tags.map((tag) => [tag.name, tag.color])),
    icons: Object.fromEntries(props.post.tags.map((tag) => [tag.name, tag.icon])),
    descriptions: Object.fromEntries(props.post.tags.map((tag) => [tag.name, tag.description])),
});
```

```vue
<TagInput
    v-model="form.tags"
    v-model:colors="form.colors"
    v-model:icons="form.icons"
    v-model:descriptions="form.descriptions"
    :suggestions="suggestions"
    :details="details"
    :models="models"
    :model="model"
    :available-icons="icons"
/>
```

Then save the tags first, so new ones exist, and their details after. Pass the model to the setters, since other models can have tags with the same names:

```php
$post->syncTags($request->input('tags', []));
TagManager::setColors($request->input('colors', []), Post::class);
TagManager::setIcons($request->input('icons', []), Post::class);
TagManager::setDescriptions($request->input('descriptions', []), Post::class);
```

Clicking a tag in `TagInput` also lets users change its description. Descriptions can be up to 255 characters (`Tag::MAX_DESCRIPTION_LENGTH`), and a blank description removes it. In a regular form submit, set `descriptions-name` to render hidden inputs, like `name` does for the tags.

#### Tag icons

In advanced mode, users can also give each tag an icon from [Lucide](https://lucide.dev/icons). Clicking a tag in the advanced `TagInput` shows an icon button under the color picker, which opens a popup of every icon with a search. The New tag popup has the same button. Pass the icons users can pick from the controller:

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

TagManager::setIcons($request->input('icons', []), Post::class);
```

In a regular form submit, set `icons-name` to render hidden inputs for the icons.

To show icons in `TagList`, pass it the icon components:

```vue
<script setup>
import { tagIcons } from '@/vendor/tags/tagIcons.js';
</script>

<TagList :tags="post.tags" :icons="tagIcons" />
```

Icons are saved by their Lucide name, like `map-pin`. Every Lucide icon is offered by default (`'icons' => '*'`). They're loaded the first time a page shows an icon, as a separate file, so pages without icons don't download them. To offer fewer, publish the config with `--tag="tags-config"` and list the ones users can pick:

```php
// config/tags.php
'icons' => ['star', 'flag', 'map-pin'],
```

With every icon offered, the server accepts any name in Lucide's format but doesn't check that Lucide has it, so a name it doesn't have shows no icon.

#### Tag manager page

In advanced mode, the package can add a page that lists every tag with its color, icon, description, model, and how many items it's on. Users can search the tags, show only unused ones (tags that aren't on anything), create new ones with the **New tag** popup, and delete them. Deleting a tag removes it from everything it's on.

The page uses Inertia, so install it if your app doesn't have it yet (the Laravel Vue starter kit already does):

```bash
composer require inertiajs/inertia-laravel
```

Publish the config with `--tag="tags-config"` and turn the page on:

```php
// config/tags.php
'manager' => [
    'enabled' => true,
    'path' => 'admin/tags',
    'middleware' => ['web', 'auth'],
    'component' => 'tags/Manager',
],
```

`path` is the page's URL. Anyone who can open the page can delete tags, so keep `auth` in `middleware`, or use your own admin middleware. The routes are named `tags.manager.index`, `tags.manager.store`, and `tags.manager.destroy`, so you can link to the page with `route('tags.manager.index')`.

`component` is the Inertia page the route renders. Create it in your app, wrapping the published `TagManagerPage` in your own layout:

```vue
<!-- resources/js/pages/tags/Manager.vue -->
<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import TagManagerPage from '@/vendor/tags/components/advanced/TagManagerPage.vue';

defineProps(['tags', 'existing', 'models', 'icons', 'storeUrl', 'maxDescriptionLength']);
</script>

<template>
    <AppLayout>
        <TagManagerPage
            :tags="tags"
            :existing="existing"
            :models="models"
            :icons="icons"
            :store-url="storeUrl"
            :max-description-length="maxDescriptionLength"
        />
    </AppLayout>
</template>
```

If your app sets a default layout for every page (in `app.ts` or `app.js`), skip the `<AppLayout>` wrapper, or the layout shows twice.

The page isn't for one model, so its New tag popup lets users choose the tag's model, from the `models` config option. Every tag needs a model, so list at least one there to create tags on this page.


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
