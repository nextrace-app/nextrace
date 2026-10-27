<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Search Engine Indexing
    |--------------------------------------------------------------------------
    |
    | Whether search engines may index the site. It stays closed unless
    | SITE_INDEXABLE is set to true, so that a development, integration or
    | not yet launched environment never ends up in search results. Closed
    | means robots.txt disallows everything, every response carries a noindex
    | header and meta tag, and the sitemap is not served.
    |
    | robots.txt is a static file: run `php artisan seo:robots` after changing
    | this, which every deploy does.
    |
    */

    'indexable' => (bool) env('SITE_INDEXABLE', false),

];
