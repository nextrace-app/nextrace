<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Interface Language Lines
    |--------------------------------------------------------------------------
    |
    | These lines are shared with the frontend through Inertia and rendered by
    | vue-i18n, so that the interface and the server speak the same language
    | from a single set of translation files.
    |
    */

    'nav' => [
        'dashboard' => 'Dashboard',
        'login' => 'Log in',
        'register' => 'Register',
        'events' => 'Events',
        'favorites' => 'Favorites',
        'profile' => 'Profile',
        'logout' => 'Log out',
        'language' => 'Language',
    ],

    'home' => [
        'title' => 'Find your next race',
        'subtitle' => 'Browse the upcoming events and sign up in a couple of minutes.',
        'cta' => 'Browse events',
    ],

    'events' => [
        'participants' => 'participants',
        'back' => 'Back to events',
        'register' => 'Register for this event',
        'view_map' => 'View on map',
        'about' => 'About this race',
        'no_description' => 'No description has been added yet.',
        'fields' => [
            'date' => 'Date',
            'time' => 'Start time',
            'city' => 'City',
            'location' => 'Location',
            'distance' => 'Distance',
        ],
        'categories' => [
            '5k' => '5K',
            '10k' => '10K',
            'half_marathon' => 'Half Marathon',
            'marathon' => 'Marathon',
            'trail' => 'Trail',
            'ultra' => 'Ultra',
        ],
        'filters' => [
            'label' => 'Filter events',
            'category' => 'Category',
            'city' => 'City',
            'all' => 'All',
            'reset' => 'Clear filters',
        ],
        'index' => [
            'title' => 'Upcoming races',
            'subtitle' => 'Running events across Greece, starting with Crete.',
            'count' => '{count} events shown',
            'empty' => 'There are no upcoming races yet. Check back soon.',
            'no_matches' => 'No races match these filters.',
        ],
        'details' => 'View details',
        'favorite' => [
            'add' => 'Add to favorites',
            'remove' => 'Remove from favorites',
        ],
    ],

    'favorites' => [
        'title' => 'Your favorites',
        'empty' => 'You have not saved any events yet.',
        'cta' => 'Browse events',
    ],
    'profile' => [
        'title' => 'Your profile',
    ],
];
