<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\FavoriteController;
use App\Support\Locale;
use Illuminate\Support\Facades\Route;

$localized = function () {
    Route::inertia('/', 'Home')->name('home');

    Route::get('events', [EventController::class, 'index'])->name('events.index');
    Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');

    Route::middleware('auth')->group(function () {
        Route::get('favorites', [FavoriteController::class, 'index'])->name('favorites.index');
        Route::post('events/{event}/favorite', [FavoriteController::class, 'store'])->name('events.favorite.store');
        Route::delete('events/{event}/favorite', [FavoriteController::class, 'destroy'])->name('events.favorite.destroy');
    });

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    });

    require __DIR__.'/settings.php';
    require __DIR__.'/auth.php';
};

// Non-default locales live behind their own prefix and name, e.g. /en/events as 'en.events.index'...
foreach (Locale::prefixed() as $locale) {
    Route::prefix($locale)->name("{$locale}.")->group($localized);
}

// ...while the default locale is served from the root under the bare route names.
$localized();

// Passkey endpoints - outside locale prefix
Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
