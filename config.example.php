<?php
// config.example.php -> Rinomina in config.php e personalizza i campi

return [
    // Versione del template
    'version'        => 'v1.0.0',

    // Lingua interfaccia: 'it' oppure 'en'
    'lang'           => 'it',

    // Informazioni generali Stazione Meteo
    'station_name'   => 'Stazione Meteo Demo',
    'station_city'   => 'Città, Regione',
    'station_alt'    => 250, // Quota altimetrica in metri s.l.m.
    'site_url'       => 'https://esempio-meteo.it',
    
    // Coordinate geografiche (per centrare il Radar Windy)
    'lat'            => 43.44,
    'lon'            => 11.85,

    // Percorsi file locali generati da CumulusMX
    'realtime_path'  => __DIR__ . '/realtime.txt',
    'json_base_path' => __DIR__ . '/',

    // ==========================================
    // MODULI OPZIONALI
    // ==========================================
    'modules' => [
        // Radar precipitazioni Windy
        'windy_radar' => [
            'enabled' => true,
            'zoom'    => 11,
        ],

        // Singola Webcam (JPG locale o remoto)
        'webcam' => [
            'enabled'     => true,
            'name'        => 'Webcam Panoramica',
            'url'         => 'webcam.jpg',
            'thumb_url'   => '', // Lascia vuoto per usare direttamente 'url'
            'gallery_url' => '', // Link opzionale (es. 'gallery/') o vuoto '' se assente
        ],

        // Grafici storici (richiede i file alldaily*.json di CumulusMX)
        'historical_charts' => [
            'enabled'        => true,
            'show_prev_year' => true // Mostra tab dell'anno precedente nella dashboard
        ]
    ]
];
