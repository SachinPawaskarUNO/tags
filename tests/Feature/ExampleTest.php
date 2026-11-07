<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use spawaskar\Tags\Facades\Tag as TagFacade;
use spawaskar\Tags\Models\Tag;
use spawaskar\Tags\Tests\Fixtures\CustomTag;

it('resolves the tag facade to the tag model', function () {
    expect(TagFacade::getFacadeRoot())->toBeInstanceOf(Tag::class);
});

it('resolves the tag facade to the model set in the config', function () {
    config(['tags.model' => CustomTag::class]);

    expect(TagFacade::getFacadeRoot())->toBeInstanceOf(CustomTag::class)
        ->and(TagFacade::modelClass())->toBe(CustomTag::class);
});

it('forwards tag facade calls to the tag model', function () {
    Tag::findOrCreateByName('never-used');

    expect(TagFacade::unused()->pluck('name')->all())->toBe(['never-used'])
        ->and(TagFacade::names())->toBe(['never-used']);
});

it('merges the package config', function () {
    expect(config('tags.model'))->toBe(Tag::class);
});

it('loads the package translations', function () {
    expect(trans('tags::messages.placeholder'))->toBe('Tags placeholder translation.');
});

it('loads the package views', function () {
    expect(view()->exists('tags::placeholder'))->toBeTrue();
});

it('registers the artisan command', function () {
    expect(Artisan::all())->toHaveKey('tags:install');
});
