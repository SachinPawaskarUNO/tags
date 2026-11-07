<?php

use Illuminate\Support\Facades\Route;
use Workbench\App\Http\Controllers\PostController;

Route::redirect('/', '/posts');

Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{post}/edit', [PostController::class, 'edit']);
Route::put('/posts/{post}', [PostController::class, 'update']);

// The tag manager page needs a logged-in user, so this logs in the seeded one.
Route::redirect('/login', '/_workbench/login/1')->name('login');
