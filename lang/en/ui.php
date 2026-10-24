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
        'races' => 'Races',
        'main' => 'Main navigation',
        'home' => 'NextRace home',
        'favorites' => 'Favorites',
        'profile' => 'Profile',
        'logout' => 'Log out',
        'language' => 'Language',
        'theme' => [
            'label' => 'Theme',
            'light' => 'Light',
            'dark' => 'Dark',
            'system' => 'System',
        ],
        'chooseLanguage' => 'Choose a language',
        'chooseLanguageDescription' => 'The site will reload in the language you pick.',
        'locales' => [
            'en' => 'English',
            'el' => 'Ελληνικά',
        ],
    ],

    'home' => [
        'title' => 'Find your next race',
        'subtitle' => 'Browse the upcoming events and sign up in a couple of minutes.',
        'cta' => 'Browse events',
        'eyebrow' => 'Race calendar / Crete',
        'heading' => 'Find a race that feels like Crete.',
        'lead' => 'Sea-level 10Ks, mountain trails and the Mediterranean marathon. Calm, clear and up to date.',
        'features' => [
            'calendar' => [
                'title' => 'One calendar',
                'text' => 'Every race by month, with the date, start time and distance up front.',
            ],
            'filters' => [
                'title' => 'Filter in a tap',
                'text' => 'Narrow the list by distance, region, month or category.',
            ],
            'favorites' => [
                'title' => 'Keep your shortlist',
                'text' => 'Save the races you like and come back to them later.',
            ],
        ],
    ],

    'footer' => [
        'tagline' => 'Crete first, then all of Greece.',
        'disclaimer' => 'Dates are collected from organisers. Always confirm on the official site before you travel.',
        'copyright' => '© {year} NextRace',
        'browse' => 'Browse',
    ],

    'events' => [
        'participants' => 'participants',
        'time_tba' => 'Time TBA',
        'map' => [
            'title' => 'Crete, Greece',
            'note' => 'Approximate locations.',
            'show' => 'Show map',
            'hide' => 'Hide map',
            'pin' => '{name}, {city}',
        ],
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
            'kids' => 'Kids',
            'other' => 'Other',
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
            'eyebrow' => 'Race calendar',
            'month_count' => '{count} race | {count} races',
            'count' => '{count} event shown | {count} events shown',
            'empty' => 'There are no upcoming races yet. Check back soon.',
            'no_matches' => 'No races match these filters.',
        ],
        'status' => [
            'postponed' => 'Postponed',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
            'date_unconfirmed' => 'Date to be confirmed',
            'banner_unconfirmed' => 'The date of this event is not confirmed by the organiser yet. Check the official website before you plan your trip.',
            'banner_postponed' => 'This event has been postponed. Check the official website for the new date.',
            'banner_cancelled' => 'This event has been cancelled.',
        ],
        'more_races' => '+{count}',
        'distance_classes' => [
            '5k' => '5K',
            '10k' => '10K',
            'half_marathon' => 'Half marathon',
            'marathon' => 'Marathon',
            'ultra' => 'Ultra',
            'kids_other' => 'Kids and other',
        ],
        'month_filter' => 'Month',
        'region_filter' => 'Region',
        'distance_filter' => 'Distance',
        'races' => [
            'title' => 'Races',
            'count' => '{count} races',
            'main' => 'Main race',
            'start' => 'Start',
            'time_limit' => 'Time limit',
            'min_age' => 'Minimum age',
            'min_age_value' => '{age}+ years',
            'participant_limit' => 'Participant limit',
            'elevation' => 'Elevation gain',
            'certification' => 'Certification',
            'prices' => 'Entry fee',
            'free' => 'Free',
            'until' => 'until {date}',
            'from' => 'from {date}',
            'register' => 'Register',
            'cancelled' => 'Race cancelled',
            'discipline' => [
                'run' => 'Run',
                'walk' => 'Walk',
                'relay' => 'Relay',
                'cycle' => 'Cycling',
                'hike' => 'Hike',
                'other' => 'Other',
            ],
            'terrain' => [
                'road' => 'Road',
                'trail' => 'Trail',
                'cross_country' => 'Cross country',
                'track' => 'Track',
                'mixed' => 'Mixed terrain',
            ],
            'certifications' => [
                'aims' => 'AIMS certified',
                'world_athletics' => 'World Athletics certified',
            ],
        ],
        'facts' => [
            'title' => 'Event details',
            'organizer' => 'Organiser',
            'venue' => 'Venue',
            'region' => 'Region',
            'series' => 'Edition of',
            'edition' => 'Edition no. {number}',
            'registration' => 'Registration',
            'registration_opens' => 'Opens {date}',
            'registration_closes' => 'Closes {date}',
            'contact' => 'Contact',
            'links' => 'Links',
        ],
        'registration_status' => [
            'unknown' => 'Registration status unknown',
            'not_open' => 'Registration not open yet',
            'open' => 'Registration open',
            'closed' => 'Registration closed',
            'sold_out' => 'Sold out',
        ],
        'link_types' => [
            'website' => 'Website',
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'regulations' => 'Regulations',
            'route' => 'Route',
            'results' => 'Results',
            'photos' => 'Photos',
            'timing' => 'Timing',
            'other' => 'Link',
        ],
        'details' => 'View details',
        'favorite' => [
            'add' => 'Add to favorites',
            'remove' => 'Remove from favorites',
            'add_named' => 'Add {name} to favorites',
            'remove_named' => 'Remove {name} from favorites',
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
