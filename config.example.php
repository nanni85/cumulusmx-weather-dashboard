<?php
// config.example.php -> Rename this file to config.php and customize the values below

return [
    // Template release version
    'version'        => 'v1.0.0',

    // Interface language: 'en' or 'it'
    'lang'           => 'en',

    // Weather station general profile
    'station_name'   => 'Demo Weather Station',
    'station_city'   => 'City, Country',
    'station_alt'    => 250, // Elevation in meters a.s.l.
    'site_url'       => 'https://example-weather.com',
    
    // GPS coordinates (used to center the embedded Windy Radar)
    'lat'            => 43.44,
    'lon'            => 11.85,

    // File paths to CumulusMX output data
    'realtime_path'  => __DIR__ . '/realtime.txt',
    'json_base_path' => __DIR__ . '/',

    // ==========================================
    // OPTIONAL MODULES
    // ==========================================
    'modules' => [
        // Windy precipitation radar map
        'windy_radar' => [
            'enabled' => true,
            'zoom'    => 11,
        ],

        // Single Webcam support (local image path or remote URL)
        'webcam' => [
            'enabled'     => true,
            'name'        => 'Panoramic Webcam',
            'url'         => 'webcam.jpg', // e.g. 'webcam.jpg', 'images/webcam.jpg', or 'https://...'
            'thumb_url'   => '',           // Optional thumbnail URL (leave empty '' to use 'url' directly)
            'gallery_url' => '',           // Optional gallery link (e.g. 'gallery/') or leave empty ''
        ],

        // Historical charts (requires CumulusMX alldaily*.json files)
        'historical_charts' => [
            'enabled'        => true,
            'show_prev_year' => true // Display the previous year tab on the dashboard
        ]
    ]
];
