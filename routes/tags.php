<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use spawaskar\Tags\Http\Controllers\TagManagerController;

// The tag manager page, when the tags.manager.enabled config option is on.
if (config('tags.manager.enabled') === true) {
    /** @var list<string> $middleware */
    $middleware = config('tags.manager.middleware', ['web', 'auth']);

    Route::middleware($middleware)
        ->prefix((string) config('tags.manager.path', 'tags'))
        ->name('tags.manager.')
        ->group(function (): void {
            Route::get('/', [TagManagerController::class, 'index'])->name('index');
            Route::post('/', [TagManagerController::class, 'store'])->name('store');
            Route::delete('{tag}', [TagManagerController::class, 'destroy'])->whereNumber('tag')->name('destroy');
        });
}
