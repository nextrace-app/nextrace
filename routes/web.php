<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Middleware\HideAdminArea;
use App\Http\Middleware\RememberLocale;
use App\Support\Locale;
use Illuminate\Support\Facades\Route;

$localized = function () {
    Route::get('/', HomeController::class)->name('home');

    Route::get('events', [EventController::class, 'index'])->name('events.index');
    Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');

    Route::middleware('auth')->group(function () {
        Route::inertia('profile', 'Profile')->name('profile.show');

        Route::get('favorites', [FavoriteController::class, 'index'])->name('favorites.index');
        Route::post('events/{event}/favorite', [FavoriteController::class, 'store'])->name('events.favorite.store');
        Route::delete('events/{event}/favorite', [FavoriteController::class, 'destroy'])->name('events.favorite.destroy');
    });

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    });

    // The admin area is open to whoever holds the permission, whatever their role;
    // everyone else, guests included, gets a 404.
    Route::middleware(HideAdminArea::class)->prefix('admin')->name('admin.')->group(function () {
        Route::inertia('/', 'Admin/Overview')->name('dashboard');
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

// Google sign-in - outside locale prefix, since Google accepts one fixed callback URL
// RememberLocale would bounce them onto a prefixed URL that does not exist.
Route::middleware('guest')->withoutMiddleware(RememberLocale::class)->prefix('auth/google')->name('auth.google.')->group(function () {
    Route::get('redirect', [SocialAuthController::class, 'redirect'])->name('redirect');
    Route::get('callback', [SocialAuthController::class, 'callback'])->name('callback');
});

// Crawler files - outside locale prefix (robots.txt is static: php artisan seo:robots)
Route::get('sitemap.xml', SitemapController::class)->name('sitemap')->withoutMiddleware(RememberLocale::class);
