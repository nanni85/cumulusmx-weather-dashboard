<?php
ini_set('memory_limit', '256M');

$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    die("File config.php mancante. Copia config.example.php in config.php e inserisci i parametri.");
}
$config = require $configFile;
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/lang.php';

$L = loadTranslations($config['lang'] ?? 'it');

// Flag moduli
$modWindy  = !empty($config['modules']['windy_radar']['enabled']);
$modCam    = !empty($config['modules']['webcam']['enabled']) && !empty($config['modules']['webcam']['url']);
$modCharts = !empty($config['modules']['historical_charts']['enabled']);

$camFullUrl  = $modCam ? $config['modules']['webcam']['url'] : '';
$camThumbUrl = $modCam ? (!empty($config['modules']['webcam']['thumb_url']) ? $config['modules']['webcam']['thumb_url'] : $camFullUrl) : '';

// Lettura dati realtime
$p = fetchRealtime($config['realtime_path']);
$date = rt($p, 0, '--');
$time = rt($p, 1, '--');

$data_ts = null;
if ($date !== '--' && $time !== '--') {
    $parts_date = explode('/', $date);
    if (count($parts_date) === 3) {
        $data_ts = strtotime(($parts_date[2] + 2000) . "-{$parts_date[1]}-{$parts_date[0]} $time");
    }
}
$data_age_seconds = $data_ts ? (time() - $data_ts) : 9999;

$temp          = rt($p,  2);
$hum           = rt($p,  3);
$dew           = rt($p,  4);
$wind_avg      = rt($p,  5);
$wind_latest   = rt($p,  6);
$wind_bear     = rt($p,  7);
$rain_rate     = rt($p,  8);
$rain_today    = rt($p,  9);
$baro          = rt($p, 10);
$press_tend    = rt($p, 18);
$rain_month    = rt($p, 19);
$rain_year     = rt($p, 20);
$temp_max      = rt($p, 26);
$temp_max_t    = rt($p, 27);
$temp_min      = rt($p, 28);
$temp_min_t    = rt($p, 29);
$wind_avg_max  = rt($p, 30);
$gust_max      = rt($p, 32);
$gust_max_t    = rt($p, 33);
$press_max     = rt($p, 34);
$press_min     = rt($p, 36);
$wind_gust_10m = rt($p, 40);
$apparent_temp = rt($p, 54);

$wetbulb       = calculateWetBulb($temp !== null ? (float)$temp : null, $hum !== null ? (float)$hum : null);
$wind_dir_name = $wind_bear !== null ? ($L['wind_cardinals'][round((int)$wind_bear / 22.5) % 16] ?? '--') : '--';
$tempCol       = tempColor($temp !== null ? (float)$temp : null);

// Dati Storici da JSON
$currYear     = (int)date('Y');
$currMonth    = (int)date('n');
$prevM        = $currMonth > 1 ? $currMonth - 1 : 12;
$prevY        = $currMonth > 1 ? $currYear : $currYear - 1;
$prevFullYear = $currYear - 1;
$monthNames   = $L['months_short'];

$thisMaxTemp = $thisMinTemp = $thisAvgTemp = $thisRain = $thisGust = $thisWind = '[]';
$prevMaxTemp = $prevMinTemp = $prevAvgTemp = $prevRain = $prevGust = $prevWind = '[]';
$yearAvgMaxTemp = $yearAvgMinTemp = $yearAvgTemp = $yearRain = $yearGust = $yearWind = '[]';
$prevYearAvgMaxTemp = $prevYearAvgMinTemp = $prevYearAvgTemp = $prevYearRain = $prevYearGust = $prevYearWind = '[]';
$lastRainDate = $lastRainLabel = null;

if ($modCharts) {
    $JSON_BASE = rtrim($config['json_base_path'], '/') . '/';
    $jTemp  = loadJsonFile($JSON_BASE . 'alldailytempdata.json');
    $jRain  = loadJsonFile($JSON_BASE . 'alldailyraindata.json');
    $jWind  = loadJsonFile($JSON_BASE . 'alldailywinddata.json');
    $jPress = loadJsonFile($JSON_BASE . 'alldailypressdata.json');

    $maxTemp = filterSeries($jTemp['maxTemp'] ?? [], -25, 50);
    $minTemp = filterSeries($jTemp['minTemp'] ?? [], -25, 50);
    $avgTemp = filterSeries($jTemp['avgTemp'] ?? [], -25, 50);
    $rainDay = filterSeries($jRain['rain']    ?? [],   0, 200);
    $maxGust = filterSeries($jWind['maxGust'] ?? [],   0, 130);
    $maxWind = filterSeries($jWind['maxWind'] ?? [],   0, 100);

    if ($rain_today !== null && (float)$rain_today > 0) {
        $lastRainDate  = date('d/m/Y');
        $lastRainLabel = $L['today'];
    } elseif (!empty($rainDay)) {
        foreach (array_reverse($rainDay) as [$ts, $v]) {
            if ($v > 0) {
                $dt = new DateTime('@'.intval($ts/1000));
                $dt->setTimezone(new DateTimeZone('Europe/Rome'));
                $lastRainDate  = $dt->format('d/m/Y');
                $lastRainLabel = $lastRainDate;
                break;
            }
        }
    }

    $thisMaxTemp = toChartJson(filterByYearMonth($maxTemp, $currYear, $currMonth));
    $thisMinTemp = toChartJson(filterByYearMonth($minTemp, $currYear, $currMonth));
    $thisAvgTemp = toChartJson(filterByYearMonth($avgTemp, $currYear, $currMonth));
    $thisRain    = toChartJson(filterByYearMonth($rainDay, $currYear, $currMonth));
    $thisGust    = toChartJson(filterByYearMonth($maxGust, $currYear, $currMonth));
    $thisWind    = toChartJson(filterByYearMonth($maxWind, $currYear, $currMonth));

    $prevMaxTemp = toChartJson(filterByYearMonth($maxTemp, $prevY, $prevM));
    $prevMinTemp = toChartJson(filterByYearMonth($minTemp, $prevY, $prevM));
    $prevAvgTemp = toChartJson(filterByYearMonth($avgTemp, $prevY, $prevM));
    $prevRain    = toChartJson(filterByYearMonth($rainDay, $prevY, $prevM));
    $prevGust    = toChartJson(filterByYearMonth($maxGust, $prevY, $prevM));
    $prevWind    = toChartJson(filterByYearMonth($maxWind, $prevY, $prevM));

    $yearAvgMaxTemp = json_encode(aggregateByMonth($maxTemp, $currYear, 'avg'), JSON_NUMERIC_CHECK);
    $yearAvgMinTemp = json_encode(aggregateByMonth($minTemp, $currYear, 'avg'), JSON_NUMERIC_CHECK);
    $yearAvgTemp    = json_encode(aggregateByMonth($avgTemp, $currYear, 'avg'), JSON_NUMERIC_CHECK);
    $yearRain       = json_encode(aggregateByMonth($rainDay, $currYear, 'sum'), JSON_NUMERIC_CHECK);
    $yearGust       = json_encode(aggregateByMonth($maxGust, $currYear, 'max'), JSON_NUMERIC_CHECK);
    $yearWind       = json_encode(aggregateByMonth($maxWind, $currYear, 'avg'), JSON_NUMERIC_CHECK);

    if (!empty($config['modules']['historical_charts']['show_prev_year'])) {
        $prevYearAvgMaxTemp = json_encode(aggregateByMonth($maxTemp, $prevFullYear, 'avg'), JSON_NUMERIC_CHECK);
        $prevYearAvgMinTemp = json_encode(aggregateByMonth($minTemp, $prevFullYear, 'avg'), JSON_NUMERIC_CHECK);
        $prevYearAvgTemp    = json_encode(aggregateByMonth($avgTemp, $prevFullYear, 'avg'), JSON_NUMERIC_CHECK);
        $prevYearRain       = json_encode(aggregateByMonth($rainDay, $prevFullYear, 'sum'), JSON_NUMERIC_CHECK);
        $prevYearGust       = json_encode(aggregateByMonth($maxGust, $prevFullYear, 'max'), JSON_NUMERIC_CHECK);
        $prevYearWind       = json_encode(aggregateByMonth($maxWind, $prevFullYear, 'avg'), JSON_NUMERIC_CHECK);
    }
}

$rain_rate_pct = $rain_rate !== null ? min(100, (float)$rain_rate / 20 * 100) : 0;
$rr = (float)($rain_rate ?? 0);
if ($rr <= 0)      $rain_rate_color = '#4ade80';
elseif ($rr <= 2)  $rain_rate_color = '#86efac';
elseif ($rr <= 5)  $rain_rate_color = '#fbbf24';
elseif ($rr <= 10) $rain_rate_color = '#f97316';
else               $rain_rate_color = '#f87171';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($config['lang'] ?? 'it') ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($config['station_name']) ?> – <?= htmlspecialchars($config['station_city']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="ico/favicon.svg">
    <meta name="theme-color" content="#0ea5e9">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Source+Code+Pro:wght@400;500&display=swap">
    
    <?php if ($modCharts): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js" defer></script>
    <?php endif; ?>

    <style>
        :root {
            --bg: #0a0f1e; --bg2: #111827; --bg3: #1a2236;
            --border: rgba(255,255,255,0.07); --text: #f0f4ff; --muted: #94a3b8;
            --accent: #38bdf8; --accent2: #818cf8; --good: #34d399; --warn: #fbbf24; --bad: #f87171;
            --card-shadow: 0 8px 32px rgba(0,0,0,0.45); --radius: 1rem;
            --font-head: 'Source Sans 3', sans-serif; --font-mono: 'Source Code Pro', monospace;
        }
        [data-theme="light"] {
            --bg: #f0f4ff; --bg2: #ffffff; --bg3: #e8edf8;
            --border: rgba(0,0,0,0.08); --text: #0f172a; --muted: #475569;
            --good: #059669; --warn: #d97706; --bad: #dc2626;
            --card-shadow: 0 4px 24px rgba(0,0,0,0.10);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: var(--font-mono); background: var(--bg); color: var(--text); min-height: 100vh; }
        .wrap { max-width: 1280px; margin: 0 auto; padding: 0 1.25rem 3rem; }
        header { display: flex; align-items: center; justify-content: space-between; padding: 1.5rem 0 1.25rem; border-bottom: 1px solid var(--border); gap: 1rem; flex-wrap: wrap; }
        .station-name { font-family: var(--font-head); font-size: clamp(1.4rem, 3vw, 2rem); font-weight: 800; }
        .station-name span { color: var(--accent); }
        .station-meta { font-size: 0.78rem; color: var(--muted); text-transform: uppercase; }
        .header-right { display: flex; align-items: center; gap: 0.75rem; }
        .live-dot { display: flex; align-items: center; gap: 0.4rem; font-size: 0.75rem; color: var(--good); }
        .live-dot::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: var(--good); animation: blink 2s infinite; }
        .live-dot.stale { color: var(--warn); } .live-dot.stale::before { background: var(--warn); }
        .live-dot.offline { color: var(--bad); } .live-dot.offline::before { background: var(--bad); }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.3} }
        .btn-theme { background: var(--bg3); border: 1px solid var(--border); color: var(--text); border-radius: 2rem; padding: 0.4rem 1rem; cursor: pointer; }
        .ts-bar { font-size: 0.75rem; color: var(--muted); padding: 0.6rem 0; border-bottom: 1px solid var(--border); margin-bottom: 1.5rem; }
        .card { background: var(--bg2); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--card-shadow); padding: 1.25rem 1.4rem; }
        .card-label { font-size: 0.7rem; text-transform: uppercase; color: var(--muted); margin-bottom: 0.5rem; }
        .card-value { font-family: var(--font-head); font-size: 2.2rem; font-weight: 700; line-height: 1; }
        .card-unit { font-size: 0.85rem; color: var(--muted); margin-left: 0.2rem; }
        .card-sub { font-size: 0.75rem; color: var(--muted); margin-top: 0.4rem; }
        
        .hero-grid { display: grid; grid-template-columns: 1.4fr 1fr 1.6fr; gap: 1rem; margin-bottom: 1rem; }
        .hero-grid.no-radar { grid-template-columns: 1fr 1fr; }
        @media(max-width:1024px){ .hero-grid { grid-template-columns: 1fr 1fr; } .hero-grid > :last-child { grid-column: span 2; min-height: 240px; } }
        @media(max-width:560px){ .hero-grid, .hero-grid.no-radar { grid-template-columns: 1fr; } .hero-grid > :last-child { grid-column: span 1; min-height: 220px; } }
        
        .card-temp { background: var(--bg2); border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--card-shadow); padding: 1.5rem 1.75rem; }
        .temp-big { font-family: var(--font-head); font-size: clamp(3rem, 7vw, 4.5rem); font-weight: 800; line-height: 1; color: var(--temp-color, var(--accent)); }
        .temp-minmax { display: flex; gap: 1.2rem; margin-top: 0.75rem; font-size: 0.8rem; }
        .temp-minmax span { color: var(--muted); }
        .windy-container { position: relative; flex: 1; width: 100%; min-height: 200px; background: var(--bg3); display: flex; align-items: center; justify-content: center; }
        .main-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1rem; }
        @media(max-width:1024px){ .main-grid { grid-template-columns: repeat(2,1fr); } }
        @media(max-width:560px){ .main-grid { grid-template-columns: 1fr; } }
        .wind-card { display: flex; flex-direction: column; align-items: center; gap: 0.4rem; }
        .compass { position: relative; width: 110px; height: 110px; border-radius: 50%; border: 2px solid var(--border); background: radial-gradient(circle at center, var(--bg3) 0%, var(--bg2) 80%); display: flex; align-items: center; justify-content: center; }
        .compass-dial { width: 100%; height: 100%; }
        .compass-labels { position: absolute; inset: 0; font-size: 0.65rem; font-weight: 700; color: var(--muted); pointer-events: none; }
        .compass-labels span { position: absolute; transform: translate(-50%, -50%); }
        .compass-n { top: 12%; left: 50%; color: var(--accent); } .compass-s { top: 88%; left: 50%; }
        .compass-e { top: 50%; left: 88%; } .compass-w { top: 50%; left: 12%; }
        .baro-tend { font-size: 1.1rem; font-weight: 600; color: var(--accent); }
        .rain-bars { display: flex; flex-direction: column; gap: 0.4rem; margin-top: 0.5rem; }
        .rain-row { display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; }
        .rain-bar-bg { flex: 1; height: 6px; border-radius: 3px; background: var(--bg3); overflow: hidden; }
        .rain-bar-fill { height: 100%; border-radius: 3px; background: linear-gradient(to right, var(--accent), var(--accent2)); }
        .rain-val { color: var(--text); font-weight: 500; min-width: 3rem; text-align: right; }
        .section-title { font-family: var(--font-head); font-size: 1rem; font-weight: 700; color: var(--muted); text-transform: uppercase; margin: 2rem 0 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border); }
        .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
        @media(max-width:768px){ .charts-grid { grid-template-columns: 1fr; } }
        .chart-wrap { position: relative; height: 200px; }
        
        .webcam-card-single { max-width: 860px; margin: 0 auto 1.5rem; background: var(--bg2); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; box-shadow: var(--card-shadow); }
        .webcam-frame-wrap { position: relative; width: 100%; aspect-ratio: 16/9; background: #000; cursor: pointer; }
        .webcam-frame-wrap img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        .webcam-label { padding: 0.6rem 1rem; font-size: 0.8rem; color: var(--muted); }
        .webcam-zoom-hint { position: absolute; bottom: 0.5rem; right: 0.6rem; background: rgba(0,0,0,0.65); color: #fff; font-size: 0.72rem; padding: 0.2rem 0.5rem; border-radius: 0.3rem; opacity: 0; transition: opacity .2s; }
        .webcam-frame-wrap:hover .webcam-zoom-hint { opacity: 1; }
        
        #lightbox { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.88); z-index: 9999; align-items: center; justify-content: center; }
        #lightbox.open { display: flex; }
        #lightbox-inner { position: relative; max-width: 95vw; max-height: 90vh; display: flex; flex-direction: column; align-items: center; }
        #lightbox-img { max-width: 95vw; max-height: 82vh; border-radius: 0.5rem; object-fit: contain; }
        #lightbox-close { position: absolute; top: -2.2rem; right: 0; background: rgba(255,255,255,0.15); border: none; color: #fff; width: 2rem; height: 2rem; border-radius: 50%; cursor: pointer; }
        .btn-tab { background: var(--bg3); border: 1px solid var(--border); color: var(--text); border-radius: 0.5rem; padding: 0.4rem 1rem; cursor: pointer; }
        .btn-tab.active { background: rgba(56,189,248,0.15); border-color: var(--accent); color: var(--accent); }
        .flex-between { display: flex; justify-content: space-between; align-items: center; }
        .mt-xs { margin-top: 0.3rem; } .mt-sm { margin-top: 0.6rem; }
        footer { border-top: 1px solid var(--border); padding: 1rem 0 0; font-size: 0.72rem; color: var(--muted); display: flex; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; }
    </style>
</head>
<body>
<main class="wrap">

    <header>
        <div class="header-left">
            <h1 class="station-name">⛅ <?= htmlspecialchars($config['station_name']) ?></h1>
            <div class="station-meta"><?= htmlspecialchars($config['station_city']) ?> · <?= (int)$config['station_alt'] ?>m s.l.m. · CumulusMX</div>
        </div>
        <div class="header-right">
            <div class="live-dot<?= $data_age_seconds > 900 ? ' offline' : ($data_age_seconds > 300 ? ' stale' : '') ?>" id="liveDot">
                <?= $data_age_seconds > 900 ? $L['offline'] : ($data_age_seconds > 300 ? $L['delay'] : $L['live']) ?>
            </div>
            <button class="btn-theme" id="themeToggle">☀ Light</button>
        </div>
    </header>

    <div class="ts-bar">
        <?= $L['last_update'] ?>: <?= htmlspecialchars($date) ?> - <?= htmlspecialchars($time) ?> · <?= $L['auto_refresh'] ?>
    </div>

    <!-- HERO GRID -->
    <div class="hero-grid <?= !$modWindy ? 'no-radar' : '' ?>">
        <div class="card-temp" style="--temp-color:<?= $tempCol ?>;">
            <div class="card-label"><?= $L['temperature'] ?></div>
            <div class="temp-big"><?= $temp !== null ? number_format((float)$temp,1) : '--' ?><span style="font-size:1.8rem">°C</span></div>
            <div class="temp-minmax">
                <div><span>▲ Max </span><b><?= $temp_max ?? '--' ?>°</b><span> <?= $temp_max_t ?? '' ?></span></div>
                <div><span>▼ Min </span><b><?= $temp_min ?? '--' ?>°</b><span> <?= $temp_min_t ?? '' ?></span></div>
            </div>
            <div class="card-sub" style="margin-top:0.5rem">
                <?= $L['apparent'] ?>: <b><?= $apparent_temp ?? '--' ?>°C</b>
                <?php if ($wetbulb !== null): ?>&nbsp;·&nbsp; <?= $L['wet_bulb'] ?>: <b><?= $wetbulb ?>°C</b><?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-label"><?= $L['humidity'] ?></div>
            <div class="card-value" style="color:var(--accent)"><?= $hum ?? '--' ?><span class="card-unit">%</span></div>
            <div class="card-sub mt-xs"><?= $L['dew_point'] ?>: <b><?= $dew ?? '--' ?>°C</b></div>
            <?php if ($hum !== null): ?>
            <div style="margin-top:0.7rem; height:6px; background:var(--bg3); border-radius:3px; overflow:hidden;">
                <div style="width:<?= min(100,(float)$hum) ?>%; height:100%; background:linear-gradient(to right,var(--accent),var(--accent2)); border-radius:3px;"></div>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($modWindy): ?>
        <div class="card" style="padding:0; overflow:hidden; display:flex; flex-direction:column;">
            <div class="windy-container">
                <iframe
                    id="windyFrame"
                    data-src="https://embed.windy.com/embed.html?type=map&location=coordinates&zoom=<?= (int)$config['modules']['windy_radar']['zoom'] ?>&overlay=radar&product=radar&lat=<?= $config['lat'] ?>&lon=<?= $config['lon'] ?>&message=true"
                    loading="lazy"
                    frameborder="0"
                    style="position:absolute; inset:0; width:100%; height:100%; border:none; opacity:0; transition:opacity 0.4s ease-in-out;"
                    onload="this.style.opacity='1'"
                    allowfullscreen>
                </iframe>
            </div>
            <div class="webcam-label"><?= $L['radar_title'] ?></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- MAIN METEO GRID -->
    <div class="main-grid">
        <div class="card wind-card">
            <div class="card-label"><?= $L['wind'] ?></div>
            <div class="compass">
                <div class="compass-labels"><span class="compass-n">N</span><span class="compass-s">S</span><span class="compass-e">E</span><span class="compass-w">O</span></div>
                <svg class="compass-dial" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="41" fill="none" stroke="var(--border)" stroke-width="1.5" stroke-dasharray="2 4" />
                    <g style="transform: rotate(<?= (int)($wind_bear ?? 0) ?>deg); transform-origin: 50px 50px;">
                        <line x1="50" y1="50" x2="50" y2="18" stroke="var(--accent)" stroke-width="2.5" />
                        <polygon points="50,11 43,24 50,21 57,24" fill="var(--accent)" />
                        <circle cx="50" cy="50" r="4.5" fill="var(--bg2)" stroke="var(--accent)" stroke-width="2" />
                    </g>
                </svg>
            </div>
            <div style="font-size:0.8rem; color:var(--muted);"><?= htmlspecialchars($wind_dir_name) ?> (<?= $wind_bear ?? '--' ?>°)</div>
            <div class="flex-between mt-sm" style="width:100%">
                <div><div class="card-label"><?= $L['wind_speed'] ?></div><div style="font-size:1.4rem;font-weight:700;color:var(--accent)"><?= $wind_latest ?? '--' ?><span class="card-unit">km/h</span></div></div>
                <div style="text-align:center"><div class="card-label"><?= $L['wind_avg'] ?></div><div style="font-size:1.4rem;font-weight:700"><?= $wind_avg ?? '--' ?><span class="card-unit">km/h</span></div></div>
                <div style="text-align:right"><div class="card-label"><?= $L['wind_gust_10m'] ?></div><div style="font-size:1.4rem;font-weight:700;color:var(--warn)"><?= $wind_gust_10m ?? '--' ?><span class="card-unit">km/h</span></div></div>
            </div>
            <div class="card-sub"><?= $L['wind_gust_max'] ?>: <b><?= $gust_max ?? '--' ?> km/h</b> <?= $gust_max_t ? '- ' . $gust_max_t : '' ?></div>
        </div>

        <div class="card">
            <div class="card-label"><?= $L['pressure'] ?></div>
            <div class="card-value"><?= $baro ?? '--' ?><span class="card-unit">hPa</span></div>
            <div class="mt-sm baro-tend">
                <?= pressTendIcon($press_tend) ?> 
                <?= $press_tend !== null ? ((float)$press_tend > 0.5 ? $L['trend_up'] : ((float)$press_tend < -0.5 ? $L['trend_down'] : $L['trend_steady'])) : '--' ?>
            </div>
            <div class="card-sub mt-xs">▲ <?= $press_max ?? '--' ?> hPa · ▼ <?= $press_min ?? '--' ?> hPa</div>
            <div class="card-sub"><?= $L['trend'] ?>: <?= $press_tend ?? '--' ?> hPa/h</div>
        </div>

        <div class="card">
            <div class="card-label"><?= $L['precipitation'] ?></div>
            <div class="card-value" style="color:var(--accent2)"><?= $rain_today ?? '--' ?><span class="card-unit">mm</span></div>
            <div class="rain-bars mt-sm">
                <div class="rain-row">
                    <span style="color:var(--muted);min-width:3.5rem"><?= $L['rain_today'] ?></span>
                    <div class="rain-bar-bg"><div class="rain-bar-fill" style="width:<?= min(100, (float)($rain_today ?? 0) * 5) ?>%"></div></div>
                    <span class="rain-val"><?= $rain_today ?? '--' ?> mm</span>
                </div>
                <div class="rain-row">
                    <span style="color:var(--muted);min-width:3.5rem"><?= $L['rain_month'] ?></span>
                    <div class="rain-bar-bg"><div class="rain-bar-fill" style="width:<?= min(100, (float)($rain_month ?? 0) / 2) ?>%"></div></div>
                    <span class="rain-val"><?= $rain_month ?? '--' ?> mm</span>
                </div>
                <div class="rain-row">
                    <span style="color:var(--muted);min-width:3.5rem"><?= $L['rain_year'] ?></span>
                    <div class="rain-bar-bg"><div class="rain-bar-fill" style="width:<?= min(100, (float)($rain_year ?? 0) / 12) ?>%"></div></div>
                    <span class="rain-val"><?= $rain_year ?? '--' ?> mm</span>
                </div>
            </div>
            <div class="card-sub mt-xs">
                <?= $L['rain_rate'] ?>: <?= $rain_rate ?? '--' ?> mm/h
                <div style="margin-top:0.3rem;height:5px;background:var(--bg3);border-radius:3px;overflow:hidden;">
                    <div style="width:<?= $rain_rate_pct ?>%;height:100%;background:<?= $rain_rate_color ?>;"></div>
                </div>
            </div>
            <?php if ($lastRainDate): ?>
            <div class="card-sub mt-xs"><?= $L['last_rain'] ?>: <b><?= $lastRainLabel ?></b></div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-label"><?= $L['daily_summary'] ?></div>
            <div style="display:flex;flex-direction:column;gap:0.55rem;margin-top:0.3rem;">
                <div class="flex-between" style="font-size:0.82rem"><span style="color:var(--muted)"><?= $L['temp_max'] ?></span><b><?= $temp_max ?? '--' ?>°C</b></div>
                <div class="flex-between" style="font-size:0.82rem"><span style="color:var(--muted)"><?= $L['temp_min'] ?></span><b><?= $temp_min ?? '--' ?>°C</b></div>
                <div class="flex-between" style="font-size:0.82rem"><span style="color:var(--muted)"><?= $L['wind_gust_max'] ?></span><b><?= $gust_max ?? '--' ?> km/h</b></div>
                <div class="flex-between" style="font-size:0.82rem"><span style="color:var(--muted)"><?= $L['press_max'] ?></span><b><?= $press_max ?? '--' ?> hPa</b></div>
                <div class="flex-between" style="font-size:0.82rem"><span style="color:var(--muted)"><?= $L['press_min'] ?></span><b><?= $press_min ?? '--' ?> hPa</b></div>
                <div class="flex-between" style="font-size:0.82rem"><span style="color:var(--muted)"><?= $L['precipitation'] ?></span><b><?= $rain_today ?? '--' ?> mm</b></div>
            </div>
        </div>
    </div>

    <!-- MODULO: WEBCAM -->
    <?php if ($modCam): ?>
    <div style="display:flex;align-items:baseline;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;margin-bottom:0.75rem;">
        <div class="section-title" style="margin-bottom:0"><?= $L['webcam_title'] ?></div>
        <?php if (!empty($config['modules']['webcam']['gallery_url'])): ?>
        <a href="<?= htmlspecialchars($config['modules']['webcam']['gallery_url']) ?>" style="color:var(--muted);font-size:0.75rem;text-decoration:none;"><?= $L['gallery_link'] ?></a>
        <?php endif; ?>
    </div>

    <div class="webcam-card-single">
        <div class="webcam-frame-wrap" onclick="openLightbox('<?= htmlspecialchars($camFullUrl) ?>', '<?= htmlspecialchars($config['modules']['webcam']['name']) ?>')">
            <img id="webcamImg" src="<?= htmlspecialchars($camThumbUrl) ?>" alt="<?= htmlspecialchars($config['modules']['webcam']['name']) ?>" loading="lazy" />
            <div class="webcam-zoom-hint"><?= $L['webcam_zoom'] ?></div>
        </div>
        <div class="webcam-label">📷 <?= htmlspecialchars($config['modules']['webcam']['name']) ?></div>
    </div>

    <div id="lightbox" onclick="closeLightbox()">
        <div id="lightbox-inner" onclick="event.stopPropagation()">
            <button id="lightbox-close" onclick="closeLightbox()">✕</button>
            <img id="lightbox-img" src="" alt="" />
            <div id="lightbox-caption" style="color:#e5e7eb;font-size:0.85rem;margin-top:0.4rem;"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- MODULO: GRAFICI STORICI -->
    <?php if ($modCharts): ?>
    <div style="display:flex;align-items:baseline;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;margin-bottom:0.75rem;">
        <div class="section-title" style="margin-bottom:0"><?= $L['charts_title'] ?></div>
        <a href="storico.php" style="color:var(--muted);font-size:0.75rem;text-decoration:none;"><?= $L['archive_link'] ?></a>
    </div>
    <div style="display:flex;gap:0.5rem;margin-bottom:1rem;flex-wrap:wrap;">
        <button class="btn-tab active" data-tab="monthly"><?= $L['tab_monthly'] ?></button>
        <button class="btn-tab" data-tab="prev"><?= $L['tab_prev_month'] ?> (<?= $monthNames[$prevM] ?> <?= $prevY ?>)</button>
        <button class="btn-tab" data-tab="yearly"><?= $L['tab_yearly'] ?> <?= $currYear ?></button>
        <?php if (!empty($config['modules']['historical_charts']['show_prev_year'])): ?>
        <button class="btn-tab" data-tab="prevYear"><?= $L['tab_yearly'] ?> <?= $prevFullYear ?></button>
        <?php endif; ?>
    </div>

    <div id="panelMonthly">
        <div class="charts-grid">
            <div class="card"><div class="card-label"><?= $L['chart_temp_m'] ?> — <?= $monthNames[$currMonth] ?> <?= $currYear ?></div><div class="chart-wrap"><canvas id="chartTempMonth"></canvas></div></div>
            <div class="card"><div class="card-label"><?= $L['chart_rain_m'] ?> — <?= $monthNames[$currMonth] ?> <?= $currYear ?></div><div class="chart-wrap"><canvas id="chartRainMonth"></canvas></div></div>
            <div class="card"><div class="card-label"><?= $L['chart_wind_m'] ?> — <?= $monthNames[$currMonth] ?> <?= $currYear ?></div><div class="chart-wrap"><canvas id="chartWindMonth"></canvas></div></div>
            <div class="card"><div class="card-label"><?= $L['chart_gust_m'] ?> — <?= $monthNames[$currMonth] ?> <?= $currYear ?></div><div class="chart-wrap"><canvas id="chartGustMonth"></canvas></div></div>
        </div>
    </div>

    <div id="panelPrev" style="display:none">
        <div class="charts-grid">
            <div class="card"><div class="card-label"><?= $L['chart_temp_m'] ?> — <?= $monthNames[$prevM] ?> <?= $prevY ?></div><div class="chart-wrap"><canvas id="chartTempPrev"></canvas></div></div>
            <div class="card"><div class="card-label"><?= $L['chart_rain_m'] ?> — <?= $monthNames[$prevM] ?> <?= $prevY ?></div><div class="chart-wrap"><canvas id="chartRainPrev"></canvas></div></div>
            <div class="card"><div class="card-label"><?= $L['chart_wind_m'] ?> — <?= $monthNames[$prevM] ?> <?= $prevY ?></div><div class="chart-wrap"><canvas id="chartWindPrev"></canvas></div></div>
            <div class="card"><div class="card-label"><?= $L['chart_gust_m'] ?> — <?= $monthNames[$prevM] ?> <?= $prevY ?></div><div class="chart-wrap"><canvas id="chartGustPrev"></canvas></div></div>
        </div>
    </div>

    <div id="panelYearly" style="display:none">
        <div class="charts-grid">
            <div class="card"><div class="card-label"><?= $L['chart_temp_m'] ?> <?= $currYear ?></div><div class="chart-wrap"><canvas id="chartTempYear"></canvas></div></div>
            <div class="card"><div class="card-label"><?= $L['chart_rain_m'] ?> <?= $currYear ?></div><div class="chart-wrap"><canvas id="chartRainYear"></canvas></div></div>
            <div class="card"><div class="card-label"><?= $L['chart_wind_m'] ?> <?= $currYear ?></div><div class="chart-wrap"><canvas id="chartWindYear"></canvas></div></div>
        </div>
    </div>

    <?php if (!empty($config['modules']['historical_charts']['show_prev_year'])): ?>
    <div id="panelPrevYear" style="display:none">
        <div class="charts-grid">
            <div class="card"><div class="card-label"><?= $L['chart_temp_m'] ?> <?= $prevFullYear ?></div><div class="chart-wrap"><canvas id="chartTempPrevYear"></canvas></div></div>
            <div class="card"><div class="card-label"><?= $L['chart_rain_m'] ?> <?= $prevFullYear ?></div><div class="chart-wrap"><canvas id="chartRainPrevYear"></canvas></div></div>
            <div class="card"><div class="card-label"><?= $L['chart_wind_m'] ?> <?= $prevFullYear ?></div><div class="chart-wrap"><canvas id="chartWindPrevYear"></canvas></div></div>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <footer>
        <span>© <?= date('Y') ?> <?= htmlspecialchars($config['station_name']) ?> · Powered by CumulusMX · <?= htmlspecialchars($config['version'] ?? 'v1.0.0') ?></span>
        <span><?= $L['last_update'] ?>: <?= htmlspecialchars($date) ?> <?= htmlspecialchars($time) ?></span>
    </footer>

</main>

<script>
const html = document.documentElement;
const btnTheme = document.getElementById('themeToggle');
const savedTheme = localStorage.getItem('theme') || 'light';
html.setAttribute('data-theme', savedTheme);
btnTheme.textContent = savedTheme === 'dark' ? '<?= $L['theme_light'] ?>' : '<?= $L['theme_dark'] ?>';
btnTheme.addEventListener('click', () => {
    const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
    btnTheme.textContent = next === 'dark' ? '<?= $L['theme_light'] ?>' : '<?= $L['theme_dark'] ?>';
});

<?php if ($modCharts): ?>
const MONTH_NAMES = <?= json_encode($L['months_short']) ?>;
const thisMaxTemp = <?= $thisMaxTemp ?>;
const thisMinTemp = <?= $thisMinTemp ?>;
const thisAvgTemp = <?= $thisAvgTemp ?>;
const thisRain    = <?= $thisRain ?>;
const thisGust    = <?= $thisGust ?>;
const thisWind    = <?= $thisWind ?>;

const prevMaxTemp = <?= $prevMaxTemp ?>;
const prevMinTemp = <?= $prevMinTemp ?>;
const prevAvgTemp = <?= $prevAvgTemp ?>;
const prevRain    = <?= $prevRain ?>;
const prevGust    = <?= $prevGust ?>;
const prevWind    = <?= $prevWind ?>;

const yearAvgMaxTemp = <?= $yearAvgMaxTemp ?>;
const yearAvgMinTemp = <?= $yearAvgMinTemp ?>;
const yearAvgTemp    = <?= $yearAvgTemp ?>;
const yearRain       = <?= $yearRain ?>;
const yearGust       = <?= $yearGust ?>;
const yearWind       = <?= $yearWind ?>;

const prevYearAvgMaxTemp = <?= $prevYearAvgMaxTemp ?>;
const prevYearAvgMinTemp = <?= $prevYearAvgMinTemp ?>;
const prevYearAvgTemp    = <?= $prevYearAvgTemp ?>;
const prevYearRain       = <?= $prevYearRain ?>;
const prevYearGust       = <?= $prevYearGust ?>;
const prevYearWind       = <?= $prevYearWind ?>;

const gridColor = 'rgba(128,128,128,0.08)';
const tickColor = '#8492a6';
const tickFont  = { size: 10 };

function toXY(arr) { return arr.map(p => ({ x: new Date(p.x), y: p.y })); }

function baseOpts(yLabel, tooltipUnit, beginAtZero = false) {
    return {
        responsive: true, maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { display: false } },
        scales: {
            x: { type:'time', time:{ unit:'day', displayFormats:{ day:'d' } }, ticks:{ color:tickColor, font:tickFont }, grid:{ color:gridColor } },
            y: { title:{ display:true, text:yLabel, color:tickColor, font:tickFont }, ticks:{ color:tickColor, font:tickFont }, grid:{ color:gridColor }, beginAtZero }
        }
    };
}

function baseOptsMonthly(yLabel, tooltipUnit, beginAtZero = false) {
    return {
        responsive: true, maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { display: false } },
        scales: {
            x: { type:'linear', min:1, max:12, ticks:{ color:tickColor, font:tickFont, callback: v => MONTH_NAMES[v] || '' }, grid:{ color:gridColor } },
            y: { title:{ display:true, text:yLabel, color:tickColor, font:tickFont }, ticks:{ color:tickColor, font:tickFont }, grid:{ color:gridColor }, beginAtZero }
        }
    };
}

function lineDS(data, label, color, dash=[]) {
    return { label, data:toXY(data), borderColor:color, backgroundColor:color+'22', borderWidth:2, borderDash:dash, tension:0.3, pointRadius:2, fill:false, spanGaps:true };
}
function barDS(data, label, color) {
    return { label, data:toXY(data), backgroundColor:color+'bb', borderColor:color, borderWidth:1, borderRadius:3 };
}
function lineMonDS(data, label, color, dash=[]) {
    return { label, data:data.map(p=>({x:p.x, y:p.y})), borderColor:color, backgroundColor:color+'22', borderWidth:2, borderDash:dash, tension:0.3, pointRadius:4, fill:false };
}
function barMonDS(data, label, color) {
    return { label, data:data.map(p=>({x:p.x, y:p.y})), backgroundColor:color+'bb', borderColor:color, borderWidth:1, borderRadius:3 };
}

const chartRegistry = {};
function mkChart(id, type, datasets, opts, showLegend=false) {
    const ctx = document.getElementById(id)?.getContext('2d');
    if (!ctx) return;
    if (showLegend) opts.plugins.legend = { display:true, labels:{ color:tickColor, font:tickFont, usePointStyle:true, boxWidth:8 } };
    const chart = new Chart(ctx, { type, data:{ datasets }, options: opts });
    chartRegistry[id] = chart;
    return chart;
}

const initializedTabs = { monthly: false, prev: false, yearly: false, prevYear: false };

function initMonthlyCharts() {
    if (initializedTabs.monthly) return;
    mkChart('chartTempMonth','line', [lineDS(thisMaxTemp,'Max °C','#f87171'), lineDS(thisMinTemp,'Min °C','#60a5fa'), lineDS(thisAvgTemp,'Media °C','#fbbf24',[4,3])], baseOpts('°C','°C'), true);
    mkChart('chartRainMonth', 'bar',  [barDS(thisRain,'mm','#38bdf8')], baseOpts('mm','mm',true));
    mkChart('chartWindMonth', 'line', [lineDS(thisWind,'km/h','#818cf8')], baseOpts('km/h','km/h',true));
    mkChart('chartGustMonth', 'line', [lineDS(thisGust,'km/h','#f97316')], baseOpts('km/h','km/h',true));
    initializedTabs.monthly = true;
}

function initPrevCharts() {
    if (initializedTabs.prev) return;
    mkChart('chartTempPrev','line', [lineDS(prevMaxTemp,'Max °C','#f87171'), lineDS(prevMinTemp,'Min °C','#60a5fa'), lineDS(prevAvgTemp,'Media °C','#fbbf24',[4,3])], baseOpts('°C','°C'), true);
    mkChart('chartRainPrev', 'bar',  [barDS(prevRain,'mm','#38bdf8')], baseOpts('mm','mm',true));
    mkChart('chartWindPrev', 'line', [lineDS(prevWind,'km/h','#818cf8')], baseOpts('km/h','km/h',true));
    mkChart('chartGustPrev', 'line', [lineDS(prevGust,'km/h','#f97316')], baseOpts('km/h','km/h',true));
    initializedTabs.prev = true;
}

function initYearlyCharts() {
    if (initializedTabs.yearly) return;
    mkChart('chartTempYear','line', [lineMonDS(yearAvgMaxTemp,'Max °C','#f87171'), lineMonDS(yearAvgMinTemp,'Min °C','#60a5fa'), lineMonDS(yearAvgTemp,'Media °C','#fbbf24',[4,3])], baseOptsMonthly('°C','°C'), true);
    mkChart('chartRainYear',  'bar',  [barMonDS(yearRain,'mm','#38bdf8')], baseOptsMonthly('mm','mm',true));
    mkChart('chartWindYear',  'line', [lineMonDS(yearWind,'km/h','#818cf8')], baseOptsMonthly('km/h','km/h',true));
    initializedTabs.yearly = true;
}

function initPrevYearCharts() {
    if (initializedTabs.prevYear) return;
    mkChart('chartTempPrevYear','line', [lineMonDS(prevYearAvgMaxTemp,'Max °C','#f87171'), lineMonDS(prevYearAvgMinTemp,'Min °C','#60a5fa'), lineMonDS(prevYearAvgTemp,'Media °C','#fbbf24',[4,3])], baseOptsMonthly('°C','°C'), true);
    mkChart('chartRainPrevYear',  'bar',  [barMonDS(prevYearRain,'mm','#38bdf8')], baseOptsMonthly('mm','mm',true));
    mkChart('chartWindPrevYear',  'line', [lineMonDS(prevYearWind,'km/h','#818cf8')], baseOptsMonthly('km/h','km/h',true));
    initializedTabs.prevYear = true;
}

document.querySelectorAll('.btn-tab').forEach(b => {
    b.addEventListener('click', () => {
        document.querySelectorAll('.btn-tab').forEach(x => x.classList.remove('active'));
        b.classList.add('active');
        const tab = b.dataset.tab;
        document.getElementById('panelMonthly').style.display  = tab === 'monthly'  ? '' : 'none';
        document.getElementById('panelPrev').style.display     = tab === 'prev'     ? '' : 'none';
        document.getElementById('panelYearly').style.display   = tab === 'yearly'   ? '' : 'none';
        const panelPrevY = document.getElementById('panelPrevYear');
        if (panelPrevY) panelPrevY.style.display = tab === 'prevYear' ? '' : 'none';

        if (tab === 'monthly')  initMonthlyCharts();
        if (tab === 'prev')     initPrevCharts();
        if (tab === 'yearly')   initYearlyCharts();
        if (tab === 'prevYear') initPrevYearCharts();

        setTimeout(() => Object.values(chartRegistry).forEach(c => c?.resize()), 10);
    });
});
<?php endif; ?>

<?php if ($modWindy): ?>
setTimeout(() => {
    const wf = document.getElementById('windyFrame');
    if (wf && wf.dataset.src && !wf.src) wf.src = wf.dataset.src;
}, 1000);
<?php endif; ?>

let deferredLoaded = false;
function triggerHeavyResources() {
    if (deferredLoaded) return;
    deferredLoaded = true;
    <?php if ($modCharts): ?>
    initMonthlyCharts();
    <?php endif; ?>
}
['scroll', 'mousemove', 'touchstart', 'keydown'].forEach(ev => window.addEventListener(ev, triggerHeavyResources, { passive: true, once: true }));
setTimeout(triggerHeavyResources, 2500);

<?php if ($modCam): ?>
const camBaseThumb = '<?= htmlspecialchars($camThumbUrl) ?>';
setInterval(() => {
    const cam = document.getElementById('webcamImg');
    if (cam) {
        const sep = camBaseThumb.includes('?') ? '&' : '?';
        cam.src = camBaseThumb + sep + 't=' + Date.now();
    }
}, 30000);

function openLightbox(url, caption) {
    const sep = url.includes('?') ? '&' : '?';
    document.getElementById('lightbox-img').src = url + sep + 't=' + Date.now();
    document.getElementById('lightbox-caption').textContent = caption;
    document.getElementById('lightbox').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });
<?php endif; ?>

setTimeout(() => window.location.reload(), 60000);
</script>
</body>
</html>
