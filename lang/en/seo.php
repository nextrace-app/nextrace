<?php

return [

    'og_locales' => ['en' => 'en_US', 'el' => 'el_GR'],

    'default_description' => 'A calendar of running races in Greece, across all of Greece: dates, distances and registration links.',

    'home' => [
        'title' => 'Running races in Greece',
        'description' => 'Find your next race: a calendar of running events across Greece, from 5K to ultra, with dates, distances and registration links.',
    ],

    'events' => [
        'title' => 'Running events in Greece: race calendar',
        'description' => 'Upcoming races across Greece, from Crete to Macedonia: 5K, 10K, half marathons, marathons, trail and ultra, with dates and registration.',
    ],

    'posts' => [
        'title' => 'Running news, guides and race results in Greece',
        'description' => 'News, guides, results and interviews from the running scene in Greece: what is new in the races, how to prepare and who won.',
    ],

    'event_news' => [
        'title' => 'News: :name',
        'description' => 'The latest news and announcements about :name.',
    ],

    'breadcrumbs' => [
        'home' => 'Home',
        'events' => 'Races',
        'posts' => 'Posts',
    ],

    'event' => [
        'title' => ':name: :distance race in :city',
        'title_short' => ':name: :distance',
        'description' => ':name is a :distance running race in :city, :country, on :date.',
        'distances' => '{1} One distance.|[2,*] :count distances to choose from.',
        'description_with_details' => ':name, :distance in :city on :date. :details',
    ],

];
