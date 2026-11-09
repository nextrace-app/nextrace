<?php

use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\PostController;
use App\Models\ApiToken;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| JSON API, /api/v1
|--------------------------------------------------------------------------
|
| For scripts and agents. Every route needs a bearer token (php artisan
| api-token:create) that carries the ability the route asks for.
|
*/

Route::middleware('throttle:api')->group(function () {
    Route::get('events', [EventController::class, 'index'])
        ->middleware('api.token:'.ApiToken::EVENTS_READ)
        ->name('api.events.index');

    Route::post('events', [EventController::class, 'store'])
        ->middleware('api.token:'.ApiToken::EVENTS_WRITE)
        ->name('api.events.store');

    Route::post('posts', [PostController::class, 'store'])
        ->middleware('api.token:'.ApiToken::POSTS_WRITE)
        ->name('api.posts.store');
});
