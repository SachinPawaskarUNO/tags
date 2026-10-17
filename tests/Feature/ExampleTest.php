<?php

declare(strict_types=1);

use spawaskar\Tags\TagManager;

it('resolves the singleton', function () {
    expect(app(TagManager::class))->toBeInstanceOf(TagManager::class);
});

it('returns the same instance from the container', function () {
    expect(app(TagManager::class))->toBe(app(TagManager::class));
});

it('merges the package config', function () {
    expect(config('tags.placeholder'))->toBe('default');
});

it('loads the package translations', function () {
    expect(trans('tags::messages.placeholder'))->toBe('Tags placeholder translation.');
});

it('loads the package views', function () {
    expect(view()->exists('tags::placeholder'))->toBeTrue();
});

it('registers the artisan command', function () {
    $this->artisan('tags:placeholder')
        ->expectsOutputToContain('Tags placeholder command executed.')
        ->assertSuccessful();
});
