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
        'dashboard' => 'Πίνακας ελέγχου',
        'login' => 'Σύνδεση',
        'register' => 'Εγγραφή',
        'events' => 'Εκδηλώσεις',
        'favorites' => 'Αγαπημένα',
        'profile' => 'Προφίλ',
        'logout' => 'Αποσύνδεση',
        'language' => 'Γλώσσα',
        'chooseLanguage' => 'Επιλέξτε γλώσσα',
        'chooseLanguageDescription' => 'Ο ιστότοπος θα φορτώσει ξανά στη γλώσσα που θα επιλέξετε.',
        'locales' => [
            'en' => 'English',
            'el' => 'Ελληνικά',
        ],
    ],

    'home' => [
        'title' => 'Βρες τον επόμενο αγώνα σου',
        'subtitle' => 'Δες τις επερχόμενες εκδηλώσεις και δήλωσε συμμετοχή σε λίγα λεπτά.',
        'cta' => 'Δες τις εκδηλώσεις',
    ],

    'events' => [
        'participants' => 'συμμετέχοντες',
        'back' => 'Πίσω στις εκδηλώσεις',
        'register' => 'Δήλωση συμμετοχής',
        'view_map' => 'Δες στον χάρτη',
        'about' => 'Σχετικά με τον αγώνα',
        'no_description' => 'Δεν έχει προστεθεί ακόμη περιγραφή.',
        'fields' => [
            'date' => 'Ημερομηνία',
            'time' => 'Ώρα εκκίνησης',
            'city' => 'Πόλη',
            'location' => 'Τοποθεσία',
            'distance' => 'Απόσταση',
        ],
        'categories' => [
            '5k' => '5K',
            '10k' => '10K',
            'half_marathon' => 'Ημιμαραθώνιος',
            'marathon' => 'Μαραθώνιος',
            'trail' => 'Trail',
            'ultra' => 'Ultra',
        ],
        'filters' => [
            'label' => 'Φίλτρα εκδηλώσεων',
            'category' => 'Κατηγορία',
            'city' => 'Πόλη',
            'all' => 'Όλες',
            'reset' => 'Καθαρισμός φίλτρων',
        ],
        'index' => [
            'title' => 'Επερχόμενοι αγώνες',
            'subtitle' => 'Αγώνες δρόμου σε όλη την Ελλάδα, ξεκινώντας από την Κρήτη.',
            'count' => 'Εμφανίζονται {count} εκδηλώσεις',
            'empty' => 'Δεν υπάρχουν ακόμη επερχόμενοι αγώνες. Ξαναδές σύντομα.',
            'no_matches' => 'Κανένας αγώνας δεν ταιριάζει με αυτά τα φίλτρα.',
        ],
        'details' => 'Δες λεπτομέρειες',
        'favorite' => [
            'add' => 'Προσθήκη στα αγαπημένα',
            'remove' => 'Αφαίρεση από τα αγαπημένα',
        ],
    ],

    'favorites' => [
        'title' => 'Τα αγαπημένα σου',
        'empty' => 'Δεν έχεις αποθηκεύσει ακόμη καμία εκδήλωση.',
        'cta' => 'Δες τις εκδηλώσεις',
    ],
    'profile' => [
        'title' => 'Το προφίλ σου',
    ],
];
