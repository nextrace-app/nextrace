<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class AdminMenu
{
    /**
     * The admin sections the user may open, with their URLs in the current locale.
     *
     * @return list<array{key: string, href: string}>
     */
    public static function for(?User $user): array
    {
        if ($user === null || ! $user->can(Permissions::ADMIN_ACCESS)) {
            return [];
        }

        $menu = [];

        foreach (config('admin.sections') as $section) {
            $route = Locale::routeName($section['route']);

            if (! Route::has($route) || ! $user->can($section['permission'])) {
                continue;
            }

            $menu[] = ['key' => $section['key'], 'href' => route($route, absolute: false)];
        }

        return $menu;
    }
}
