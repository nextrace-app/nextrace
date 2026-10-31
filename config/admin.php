<?php

use App\Support\Permissions;

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Menu
    |--------------------------------------------------------------------------
    |
    | The sections of the admin area, in menu order. A user sees a section when
    | they hold its permission and its route exists, so a section appears as
    | soon as its pages are built and for whichever role is given the permission.
    | `route` is the bare route name; the locale prefix is added when it is used.
    |
    */

    'sections' => [
        ['key' => 'overview', 'permission' => Permissions::ADMIN_ACCESS, 'route' => 'admin.dashboard'],
        ['key' => 'events', 'permission' => 'events.viewAny', 'route' => 'admin.events.index'],
        ['key' => 'regions', 'permission' => 'regions.viewAny', 'route' => 'admin.regions.index'],
        ['key' => 'series', 'permission' => 'series.viewAny', 'route' => 'admin.series.index'],
    ],

];
