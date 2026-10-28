<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Events Per Page
    |--------------------------------------------------------------------------
    |
    | How many races the events list shows on one page before it paginates.
    | Set EVENTS_PER_PAGE in the environment to change it.
    |
    */

    'per_page' => max(1, (int) env('EVENTS_PER_PAGE', 12)),

];
