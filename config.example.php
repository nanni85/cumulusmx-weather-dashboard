return [
    'version'        => 'v1.0.0',
    'lang'           => 'en', // 'en' or 'it'

    // Station Profile
    'station_name'   => 'My Weather Station',
    'station_city'   => 'City, Country',
    'station_alt'    => 250, // Altitude in meters a.s.l.
    'site_url'       => '[https://myweatherstation.com](https://myweatherstation.com)',

    // GPS Coordinates (used to center the Windy radar)
    'lat'            => 43.44,
    'lon'            => 11.85,

    // CumulusMX Data Paths (relative or absolute)
    'realtime_path'  => __DIR__ . '/realtime.txt',
    'json_base_path' => __DIR__ . '/',

    // Optional Modules
    'modules' => [
        'windy_radar' => [
            'enabled' => true,
            'zoom'    => 11,
        ],
        'webcam' => [
            'enabled'     => true,
            'name'        => 'Panoramic Webcam',
            'url'         => 'webcam.jpg', // Local path or remote URL
            'thumb_url'   => '',           // Leave blank to use 'url' directly
            'gallery_url' => '',           // Optional gallery link
        ],
        'historical_charts' => [
            'enabled'        => true,
            'show_prev_year' => true
        ]
    ]
];
