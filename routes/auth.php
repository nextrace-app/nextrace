<?php

use Illuminate\Support\Facades\Route;

/*
| Fortify's own route file, which App\Providers\FortifyServiceProvider stops it from
| registering. Loading it here puts it inside routes/web.php's per-locale groups, so
| the auth pages exist under every locale prefix and keep their bare route names
| ('login', 'en.login', ...) like the rest of the application.
*/

Route::group([], base_path('vendor/laravel/fortify/routes/routes.php'));
