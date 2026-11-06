<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Posts Per Page
    |--------------------------------------------------------------------------
    |
    | How many posts the posts list shows on one page before it paginates.
    | Set POSTS_PER_PAGE in the environment to change it.
    |
    */

    'per_page' => max(1, (int) env('POSTS_PER_PAGE', 12)),

];
