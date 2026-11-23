<?php

return [
    'shared_rsvp_seat_limit' => (int) env('WEDDING_SHARED_RSVP_SEAT_LIMIT', 8),
    'music_library_path' => base_path('../music-selections'),
    'music_credits' => [
        'library-31' => [
            'title' => 'Canon in D Major',
            'artist' => 'Kevin MacLeod',
            'source' => 'https://incompetech.com/music/royalty-free/index.html?isrc=USUAN1100301',
            'license' => 'CC BY 3.0',
            'license_url' => 'https://creativecommons.org/licenses/by/3.0/',
        ],
    ],
];
