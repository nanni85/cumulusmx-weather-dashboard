<?php
// includes/lang.php

function loadTranslations(string $lang): array {
    $translations = [
        'it' => [
            // Header & Stato
            'live'          => 'LIVE',
            'delay'         => 'RITARDO',
            'offline'       => 'OFFLINE',
            'last_update'   => 'Aggiornamento',
            'auto_refresh'  => 'Aggiornamento automatico ogni 60s',
            'theme_light'   => '☀ Chiaro',
            'theme_dark'    => '🌙 Scuro',

            // Meteo Principale
            'temperature'   => 'Temperatura',
            'humidity'      => 'Umidità relativa',
            'dew_point'     => 'Punto di rugiada',
            'apparent'      => 'Percepita',
            'wet_bulb'      => 'Wet Bulb',
            'pressure'      => 'Pressione barometrica',
            'trend'         => 'Tendenza',
            'trend_up'      => '↑ In salita',
            'trend_down'    => '↓ In discesa',
            'trend_steady'  => '→ Stabile',

            // Pioggia
            'precipitation' => 'Precipitazioni',
            'rain_today'    => 'Oggi',
            'rain_month'    => 'Mese',
            'rain_year'     => 'Anno',
            'rain_rate'     => 'Intensità',
            'last_rain'     => 'Ultima pioggia',
            'today'         => 'Oggi',

            // Vento
            'wind'          => 'Vento',
            'wind_speed'    => 'Velocità',
            'wind_avg'      => 'Media',
            'wind_gust_10m' => 'Raffica 10\'',
            'wind_gust_max' => 'Raffica max',
            'wind_avg_max'  => 'Max media oggi',
            'wind_cardinals'=> ['N','NNE','NE','ENE','E','ESE','SE','SSE','S','SSO','SO','OSO','O','ONO','NO','NNO'],

            // Riepilogo giornaliero
            'daily_summary' => 'Riepilogo giornata',
            'temp_max'      => 'Temp. max',
            'temp_min'      => 'Temp. min',
            'press_max'     => 'Press. max',
            'press_min'     => 'Press. min',

            // Moduli
            'radar_title'   => '🌧 Radar precipitazioni – Windy',
            'webcam_title'  => 'Webcam live',
            'webcam_zoom'   => '🔍 clicca per ingrandire',
            'gallery_link'  => 'Gallery & Timelapse →',
            'charts_title'  => 'Grafici storici',
            'archive_link'  => 'Archivio climatico completo →',
            'back_to_live'  => '← Meteo Live',

            // Tab & Grafici
            'tab_monthly'   => '📅 Mese corrente',
            'tab_prev_month'=> '📅 Mese prec.',
            'tab_yearly'    => '📆 Anno',
            'chart_temp_m'  => 'Temperatura mensile',
            'chart_rain_m'  => 'Pioggia mensile',
            'chart_wind_m'  => 'Vento medio mensile',
            'chart_gust_m'  => 'Raffica max mensile',

            // Mesi e giorni
            'months'        => ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'],
            'months_short'  => ['','Gen','Feb','Mar','Apr','Mag','Giu','Lug','Ago','Set','Ott','Nov','Dic'],
            
            // Storico
            'climatic_archive' => 'Storico Climatico',
            'period'           => 'Periodo',
            'month_summary'    => 'Riepilogo',
            'temp_daily'       => 'Temperatura giornaliera',
            'dew_daily'        => 'Punto di rugiada',
            'feels_daily'      => 'Temperatura percepita',
            'rain_daily'       => 'Pioggia giornaliera',
            'rain_rate_max'    => 'Intensità massima pioggia',
            'press_daily'      => 'Pressione giornaliera',
            'annual_rain'      => 'Pioggia totale per anno',
            'distribution'     => 'Distribuzione',
            'no_data_found'    => 'Nessun dato storico trovato',
            'check_json_files' => 'Verifica che i file alldaily*.json di CumulusMX siano presenti nella cartella configurata.'
        ],

        'en' => [
            // Header & Status
            'live'          => 'LIVE',
            'delay'         => 'DELAY',
            'offline'       => 'OFFLINE',
            'last_update'   => 'Updated',
            'auto_refresh'  => 'Automatic refresh every 60s',
            'theme_light'   => '☀ Light',
            'theme_dark'    => '🌙 Dark',

            // Main Weather
            'temperature'   => 'Temperature',
            'humidity'      => 'Relative Humidity',
            'dew_point'     => 'Dew Point',
            'apparent'      => 'Feels Like',
            'wet_bulb'      => 'Wet Bulb',
            'pressure'      => 'Barometric Pressure',
            'trend'         => 'Trend',
            'trend_up'      => '↑ Rising',
            'trend_down'    => '↓ Falling',
            'trend_steady'  => '→ Steady',

            // Rain
            'precipitation' => 'Precipitation',
            'rain_today'    => 'Today',
            'rain_month'    => 'Month',
            'rain_year'     => 'Year',
            'rain_rate'     => 'Rain Rate',
            'last_rain'     => 'Last Rain',
            'today'         => 'Today',

            // Wind
            'wind'          => 'Wind',
            'wind_speed'    => 'Speed',
            'wind_avg'      => 'Average',
            'wind_gust_10m' => '10m Gust',
            'wind_gust_max' => 'Max Gust',
            'wind_avg_max'  => 'Max Avg Today',
            'wind_cardinals'=> ['N','NNE','NE','ENE','E','ESE','SE','SSE','S','SSW','SW','WSW','W','WNW','NW','NNW'],

            // Daily Summary
            'daily_summary' => 'Daily Summary',
            'temp_max'      => 'Max Temp',
            'temp_min'      => 'Min Temp',
            'press_max'     => 'Max Pressure',
            'press_min'     => 'Min Pressure',

            // Modules
            'radar_title'   => '🌧 Precipitation Radar – Windy',
            'webcam_title'  => 'Live Webcam',
            'webcam_zoom'   => '🔍 click to enlarge',
            'gallery_link'  => 'Gallery & Timelapse →',
            'charts_title'  => 'Historical Charts',
            'archive_link'  => 'Full Climate Archive →',
            'back_to_live'  => '← Live Weather',

            // Tabs & Charts
            'tab_monthly'   => '📅 Current Month',
            'tab_prev_month'=> '📅 Prev. Month',
            'tab_yearly'    => '📆 Year',
            'chart_temp_m'  => 'Monthly Temperature',
            'chart_rain_m'  => 'Monthly Rainfall',
            'chart_wind_m'  => 'Monthly Average Wind',
            'chart_gust_m'  => 'Monthly Max Gust',

            // Months
            'months'        => ['','January','February','March','April','May','June','July','August','September','October','November','December'],
            'months_short'  => ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],

            // Archive
            'climatic_archive' => 'Climate Archive',
            'period'           => 'Period',
            'month_summary'    => 'Summary',
            'temp_daily'       => 'Daily Temperature',
            'dew_daily'        => 'Dew Point',
            'feels_daily'      => 'Feels Like Temperature',
            'rain_daily'       => 'Daily Rain',
            'rain_rate_max'    => 'Max Rain Rate',
            'press_daily'      => 'Daily Pressure',
            'annual_rain'      => 'Total Rainfall by Year',
            'distribution'     => 'Distribution',
            'no_data_found'    => 'No historical data found',
            'check_json_files' => 'Please verify that CumulusMX alldaily*.json files exist in the configured directory.'
        ]
    ];

    $l = $lang === 'it' ? 'it' : 'en';
    return $translations[$l];
}
