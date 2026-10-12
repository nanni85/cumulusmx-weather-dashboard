<?php
// config.example.php -> Rinomina questo file in config.php e personalizza i campi

return [
    // Versione del template
    'version'        => 'v1.0.0',
    // Informazioni generali Stazione Meteo
    'station_name'   => 'Stazione Meteo Demo',
    'station_city'   => 'Città, Regione',
    'station_alt'    => 250, // Quota altimetrica in metri s.l.m.
    'site_url'       => 'https://demo.nickk.biz',
    
    // Coordinate geografiche (per centrare il Radar Windy)
    'lat'            => 43.44,
    'lon'            => 11.85,

    // Percorsi file locali CumulusMX
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

        // Singola Webcam (imposta 'enabled' => false per disattivare)
        'webcam' => [
            'enabled'     => true,
            'name'        => 'Webcam Live',
            'url'         => 'https://esempio-meteo.it/webcam/index.php',
            'gallery_url' => '', // es. 'gallery/' o lasciare vuoto se assente
        ],

        // Grafici storici (richiede alldaily*.json generati da CumulusMX)
        'historical_charts' => [
            'enabled'        => true,
            'show_prev_year' => true // Mostra tab dell'anno precedente
        ]
    ]
];