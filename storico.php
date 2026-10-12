<?php
ini_set('memory_limit', '256M');

$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    die("File config.php mancante. Copia config.example.php in config.php e inserisci i parametri.");
}
$config = require $configFile;

$JSON_BASE = rtrim($config['json_base_path'] ?? __DIR__, '/') . '/';

$files = [
    'temp'  => $JSON_BASE . 'alldailytempdata.json',
    'rain'  => $JSON_BASE . 'alldailyraindata.json',
    'press' => $JSON_BASE . 'alldailypressdata.json',
    'hum'   => $JSON_BASE . 'alldailyhumdata.json',
    'wind'  => $JSON_BASE . 'alldailywinddata.json',
];

$LIMITS = [
    'maxTemp'     => [  -8,  45  ],
    'minTemp'     => [ -15,  35  ],
    'avgTemp'     => [ -10,  38  ],
    'derived'     => [ -20,  50  ],
    'maxBaro'     => [ 940, 1060 ],
    'minBaro'     => [ 940, 1060 ],
    'maxGust'     => [   0,  150 ],
    'maxWind'     => [   0,  120 ],
    'maxRainRate' => [   0,  300 ],
    'rain'        => [   0,  300 ],
    'minHum'      => [   5,  100 ],
    'maxHum'      => [   5,  100 ],
];

function loadJson(string $path): array {
    if (!file_exists($path)) return [];
    $raw = @file_get_contents($path);
    if (!$raw) return [];
    return json_decode($raw, true) ?: [];
}

function filterData(array $data, ?array $limits): array {
    if (!$limits) return $data;
    [$lo, $hi] = $limits;
    return array_values(array_filter($data, fn($p) => $p[1] >= $lo && $p[1] <= $hi));
}

function filterIQR(array $data, float $k = 3.0): array {
    if (count($data) < 8) return $data;
    $vals = array_column($data, 1);
    sort($vals);
    $n   = count($vals);
    $q1  = $vals[(int)floor($n * 0.25)];
    $q3  = $vals[(int)floor($n * 0.75)];
    $iqr = $q3 - $q1;
    if ($iqr < 0.01) return $data;
    $lo = $q1 - $k * $iqr;
    $hi = $q3 + $k * $iqr;
    return array_values(array_filter($data, fn($p) => $p[1] >= $lo && $p[1] <= $hi));
}

$temp  = loadJson($files['temp']);
$rain  = loadJson($files['rain']);
$press = loadJson($files['press']);
$hum   = loadJson($files['hum']);
$wind  = loadJson($files['wind']);

foreach (['maxTemp'] as $k) {
    if (isset($temp[$k])) $temp[$k] = filterIQR(filterData($temp[$k], $LIMITS['maxTemp']));
}
foreach (['minTemp'] as $k) {
    if (isset($temp[$k])) $temp[$k] = filterIQR(filterData($temp[$k], $LIMITS['minTemp']));
}
foreach (['avgTemp'] as $k) {
    if (isset($temp[$k])) $temp[$k] = filterIQR(filterData($temp[$k], $LIMITS['avgTemp']));
}
foreach (['maxDew','minDew','windChill','heatIndex','maxApp','minApp','maxFeels','minFeels','humidex'] as $k) {
    if (isset($temp[$k])) $temp[$k] = filterIQR(filterData($temp[$k], $LIMITS['derived']));
}
foreach (['maxBaro','minBaro'] as $k) {
    if (isset($press[$k])) $press[$k] = filterIQR(filterData($press[$k], $LIMITS[$k]), 3.0);
}
foreach (['maxGust','maxWind'] as $k) {
    if (isset($wind[$k])) $wind[$k] = filterIQR(filterData($wind[$k], $LIMITS[$k]), 3.0);
}
foreach (['maxRainRate','rain'] as $k) {
    if (isset($rain[$k])) $rain[$k] = filterData($rain[$k], $LIMITS[$k]);
}

function getAvailableYearsMonths(array $data, string $key): array {
    $result = [];
    foreach ($data[$key] ?? [] as [$ts, $v]) {
        $dt = new DateTime('@'.intval($ts/1000));
        $dt->setTimezone(new DateTimeZone('Europe/Rome'));
        $y = (int)$dt->format('Y');
        $m = (int)$dt->format('n');
        if (!isset($result[$y])) $result[$y] = [];
        if (!in_array($m, $result[$y])) $result[$y][] = $m;
    }
    krsort($result);
    foreach ($result as &$months) sort($months);
    return $result;
}

$ym = getAvailableYearsMonths($temp, 'minTemp');

$currentYear  = (int)($_GET['year'] ?? array_key_first($ym) ?? date('Y'));
$maxMonth     = max($ym[$currentYear] ?? [date('n')]);
$currentMonth = (int)($_GET['month'] ?? $maxMonth);
if (!isset($ym[$currentYear]) || !in_array($currentMonth, $ym[$currentYear])) {
    $currentYear  = array_key_first($ym) ?? (int)date('Y');
    $currentMonth = isset($ym[$currentYear]) ? max($ym[$currentYear]) : (int)date('n');
}

$monthNames = ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno',
               'Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];

function filterByYearMonth(array $series, int $y, int $m): array {
    $out = [];
    foreach ($series as [$ts, $v]) {
        $dt = new DateTime('@'.intval($ts/1000));
        $dt->setTimezone(new DateTimeZone('Europe/Rome'));
        if ((int)$dt->format('Y') === $y && (int)$dt->format('n') === $m) {
            $out[] = [$ts, $v];
        }
    }
    return $out;
}

$sel = [
    'maxTemp'     => filterByYearMonth($temp['maxTemp']     ?? [], $currentYear, $currentMonth),
    'minTemp'     => filterByYearMonth($temp['minTemp']     ?? [], $currentYear, $currentMonth),
    'avgTemp'     => filterByYearMonth($temp['avgTemp']     ?? [], $currentYear, $currentMonth),
    'maxDew'      => filterByYearMonth($temp['maxDew']      ?? [], $currentYear, $currentMonth),
    'minDew'      => filterByYearMonth($temp['minDew']      ?? [], $currentYear, $currentMonth),
    'windChill'   => filterByYearMonth($temp['windChill']   ?? [], $currentYear, $currentMonth),
    'heatIndex'   => filterByYearMonth($temp['heatIndex']   ?? [], $currentYear, $currentMonth),
    'maxFeels'    => filterByYearMonth($temp['maxFeels']    ?? [], $currentYear, $currentMonth),
    'minFeels'    => filterByYearMonth($temp['minFeels']    ?? [], $currentYear, $currentMonth),
    'maxBaro'     => filterByYearMonth($press['maxBaro']    ?? [], $currentYear, $currentMonth),
    'minBaro'     => filterByYearMonth($press['minBaro']    ?? [], $currentYear, $currentMonth),
    'rain'        => filterByYearMonth($rain['rain']        ?? [], $currentYear, $currentMonth),
    'maxRainRate' => filterByYearMonth($rain['maxRainRate'] ?? [], $currentYear, $currentMonth),
    'minHum'      => filterByYearMonth($hum['minHum']       ?? [], $currentYear, $currentMonth),
    'maxHum'      => filterByYearMonth($hum['maxHum']       ?? [], $currentYear, $currentMonth),
    'maxGust'     => filterByYearMonth($wind['maxGust']     ?? [], $currentYear, $currentMonth),
    'maxWind'     => filterByYearMonth($wind['maxWind']     ?? [], $currentYear, $currentMonth),
    'windRun'     => filterByYearMonth($wind['windRun']     ?? [], $currentYear, $currentMonth),
];

$rainTotal = !empty($sel['rain']) ? array_sum(array_column($sel['rain'], 1)) : 0;

$rainByYear = [];
foreach ($rain['rain'] ?? [] as [$ts, $v]) {
    $dtY = new DateTime('@'.intval($ts/1000));
    $dtY->setTimezone(new DateTimeZone('Europe/Rome'));
    $y = (int)$dtY->format('Y');
    $rainByYear[$y] = ($rainByYear[$y] ?? 0) + $v;
}
ksort($rainByYear);

function jsArr(array $data): string {
    return json_encode(array_values($data), JSON_NUMERIC_CHECK);
}
?>
<!doctype html>
<html lang="it" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Storico Climatico – <?= htmlspecialchars($config['station_name']) ?></title>
  <meta name="description" content="Archivio e dati storici climatici di <?= htmlspecialchars($config['station_name']) ?>.">
  <link rel="icon" type="image/svg+xml" href="ico/favicon.svg">
  <meta name="theme-color" content="#0ea5e9">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@300;400;600;700;800&family=Source+Code+Pro:wght@400;500&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>

  <style>
    :root {
      --bg: #0a0f1e; --bg2: #111827; --bg3: #1a2236;
      --border: rgba(255,255,255,0.07); --text: #f0f4ff; --muted: #8492a6;
      --accent: #38bdf8; --accent2: #818cf8; --good: #34d399; --warn: #fbbf24; --bad: #f87171;
      --card-shadow: 0 8px 32px rgba(0,0,0,0.45); --radius: 1rem;
      --font-head: 'Source Sans 3', sans-serif; --font-mono: 'Source Code Pro', monospace;
    }
    [data-theme="light"] {
      --bg: #f0f4ff; --bg2: #ffffff; --bg3: #e8edf8;
      --border: rgba(0,0,0,0.08); --text: #0f172a; --muted: #64748b;
      --accent: #0ea5e9; --accent2: #6366f1;
      --card-shadow: 0 4px 24px rgba(0,0,0,0.10);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    body { font-family: var(--font-mono); background: var(--bg); color: var(--text); min-height: 100vh; }
    .wrap { max-width: 1280px; margin: 0 auto; padding: 0 1.25rem 3rem; }
    header { display: flex; align-items: center; justify-content: space-between; padding: 1.5rem 0 1.25rem; border-bottom: 1px solid var(--border); gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
    .header-left a { text-decoration: none; }
    .station-name { font-family: var(--font-head); font-size: clamp(1.3rem, 2.5vw, 1.9rem); font-weight: 800; color: var(--text); }
    .station-name span { color: var(--accent); }
    .station-meta { font-size: 0.75rem; color: var(--muted); text-transform: uppercase; margin-top: 0.15rem; }
    .header-right { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
    .btn-back { display: inline-flex; align-items: center; gap: 0.4rem; color: var(--accent); font-size: 0.85rem; font-weight: 600; text-decoration: none; padding: 0.35rem 0.9rem; border: 1px solid var(--border); border-radius: 2rem; background: var(--bg3); }
    .btn-theme { background: var(--bg3); border: 1px solid var(--border); color: var(--muted); border-radius: 2rem; padding: 0.4rem 1rem; cursor: pointer; }
    .navigator { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem; background: var(--bg2); border: 1px solid var(--border); border-radius: var(--radius); padding: 1rem 1.25rem; box-shadow: var(--card-shadow); }
    .nav-label { font-size: 0.78rem; font-weight: 600; text-transform: uppercase; color: var(--muted); }
    .nav-selects { display: flex; gap: 0.75rem; flex-wrap: wrap; }
    select { font-family: var(--font-mono); font-size: 0.85rem; background: var(--bg3); color: var(--text); border: 1px solid var(--border); border-radius: 0.5rem; padding: 0.4rem 2rem 0.4rem 0.75rem; cursor: pointer; }
    .month-period { margin-left: auto; font-family: var(--font-head); font-size: 1.1rem; font-weight: 700; color: var(--text); }
    .month-period span { color: var(--accent); }
    .section-title { font-family: var(--font-head); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 0.75rem; margin-top: 1.5rem; display: flex; align-items: center; gap: 0.5rem; }
    .section-title::after { content: ''; flex: 1; height: 1px; background: var(--border); }
    .chart-card { background: var(--bg2); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.25rem; box-shadow: var(--card-shadow); margin-bottom: 1rem; }
    .chart-card h3 { font-family: var(--font-head); font-size: 0.9rem; font-weight: 700; color: var(--text); margin-bottom: 0.25rem; }
    .chart-subtitle { font-size: 0.72rem; color: var(--muted); margin-bottom: 0.9rem; }
    .chart-wrap { position: relative; height: 220px; }
    .chart-wrap-tall { position: relative; height: 260px; }
    .charts-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    @media (max-width: 800px) { .charts-2col { grid-template-columns: 1fr; } }
    .stats-row { display: flex; gap: 1.5rem; flex-wrap: wrap; padding: 0.75rem 0; border-bottom: 1px solid var(--border); font-size: 0.82rem; }
    .stat-item { display: flex; flex-direction: column; gap: 0.1rem; }
    .stat-lbl { font-size: 0.68rem; color: var(--muted); text-transform: uppercase; }
    .stat-val { font-family: var(--font-head); font-weight: 700; font-size: 1rem; color: var(--text); }
    .stat-val.hot { color: var(--bad); } .stat-val.cold { color: var(--accent); } .stat-val.rain { color: #60a5fa; } .stat-val.wind { color: var(--accent2); }
    .annual-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
    .annual-table th { font-family: var(--font-head); font-size: 0.7rem; font-weight: 600; text-transform: uppercase; color: var(--muted); padding: 0.5rem 0.75rem; text-align: left; border-bottom: 1px solid var(--border); }
    .annual-table td { padding: 0.55rem 0.75rem; border-bottom: 1px solid var(--border); }
    .annual-table tr:last-child td { border-bottom: none; }
    .annual-table .year-cell { font-family: var(--font-head); font-weight: 700; color: var(--accent); }
    .bar-cell { width: 40%; }
    .bar-bg { background: var(--bg3); border-radius: 99px; height: 8px; overflow: hidden; }
    .bar-fill { background: #60a5fa; border-radius: 99px; height: 8px; }
    footer { border-top: 1px solid var(--border); padding: 1.25rem 0 0; font-size: 0.72rem; color: var(--muted); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; margin-top: 2rem; }
    footer a { color: var(--accent); text-decoration: none; }
  </style>
</head>
<body>
<div class="wrap">

  <header>
    <div class="header-left">
      <a href="index.php"><div class="station-name">⛅ <?= htmlspecialchars($config['station_name']) ?></div></a>
      <div class="station-meta">📊 Storico Climatico · <?= htmlspecialchars($config['station_city']) ?></div>
    </div>
    <div class="header-right">
      <a class="btn-back" href="index.php">← Meteo Live</a>
      <button class="btn-theme" id="themeToggle">☀ Light</button>
    </div>
  </header>

  <?php if (!empty($ym)): ?>
  <div class="navigator">
    <span class="nav-label">📅 Periodo</span>
    <div class="nav-selects">
      <select id="selYear" onchange="updateUrl()">
        <?php foreach ($ym as $y => $months): ?>
          <option value="<?= $y ?>" <?= $y === $currentYear ? 'selected' : '' ?>><?= $y ?></option>
        <?php endforeach; ?>
      </select>
      <select id="selMonth" onchange="updateUrl()">
        <?php foreach ($ym[$currentYear] ?? [] as $m): ?>
          <option value="<?= $m ?>" <?= $m === $currentMonth ? 'selected' : '' ?>><?= $monthNames[$m] ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="month-period">
      <?= $monthNames[$currentMonth] ?> <span><?= $currentYear ?></span>
    </div>
  </div>

  <?php
    $maxT = !empty($sel['maxTemp']) ? max(array_column($sel['maxTemp'], 1)) : null;
    $minT = !empty($sel['minTemp']) ? min(array_column($sel['minTemp'], 1)) : null;
    $avgT = !empty($sel['avgTemp']) ? array_sum(array_column($sel['avgTemp'],1))/count($sel['avgTemp']) : null;
    $maxG = !empty($sel['maxGust']) ? max(array_column($sel['maxGust'], 1)) : null;
    $maxH = !empty($sel['maxHum'])  ? max(array_column($sel['maxHum'],  1)) : null;
    $minH = !empty($sel['minHum'])  ? min(array_column($sel['minHum'],  1)) : null;
    $maxP = !empty($sel['maxBaro']) ? max(array_column($sel['maxBaro'], 1)) : null;
    $minP = !empty($sel['minBaro']) ? min(array_column($sel['minBaro'], 1)) : null;
  ?>
  <div class="chart-card">
    <h3>Riepilogo <?= $monthNames[$currentMonth] ?> <?= $currentYear ?></h3>
    <div class="stats-row">
      <?php if ($maxT !== null): ?><div class="stat-item"><span class="stat-lbl">Temp max</span><span class="stat-val hot"><?= number_format($maxT,1) ?>°C</span></div><?php endif; ?>
      <?php if ($minT !== null): ?><div class="stat-item"><span class="stat-lbl">Temp min</span><span class="stat-val cold"><?= number_format($minT,1) ?>°C</span></div><?php endif; ?>
      <?php if ($avgT !== null): ?><div class="stat-item"><span class="stat-lbl">Temp media</span><span class="stat-val"><?= number_format($avgT,1) ?>°C</span></div><?php endif; ?>
      <div class="stat-item"><span class="stat-lbl">Pioggia totale</span><span class="stat-val rain"><?= number_format($rainTotal,1) ?> mm</span></div>
      <?php if ($maxG !== null): ?><div class="stat-item"><span class="stat-lbl">Raffica max</span><span class="stat-val wind"><?= number_format($maxG,0) ?> km/h</span></div><?php endif; ?>
      <?php if ($maxH !== null): ?><div class="stat-item"><span class="stat-lbl">Umidità max</span><span class="stat-val"><?= number_format($maxH,0) ?>%</span></div><?php endif; ?>
      <?php if ($minH !== null): ?><div class="stat-item"><span class="stat-lbl">Umidità min</span><span class="stat-val"><?= number_format($minH,0) ?>%</span></div><?php endif; ?>
      <?php if ($maxP !== null): ?><div class="stat-item"><span class="stat-lbl">Pressione max</span><span class="stat-val"><?= number_format($maxP,1) ?> hPa</span></div><?php endif; ?>
      <?php if ($minP !== null): ?><div class="stat-item"><span class="stat-lbl">Pressione min</span><span class="stat-val"><?= number_format($minP,1) ?> hPa</span></div><?php endif; ?>
    </div>
  </div>

  <div class="section-title">🌡 Temperatura</div>
  <div class="chart-card">
    <h3>Temperatura giornaliera</h3>
    <div class="chart-subtitle">Min / Media / Max °C</div>
    <div class="chart-wrap-tall"><canvas id="chartTemp"></canvas></div>
  </div>

  <div class="charts-2col">
    <div class="chart-card">
      <h3>Punto di rugiada</h3>
      <div class="chart-subtitle">Max / Min °C</div>
      <div class="chart-wrap"><canvas id="chartDew"></canvas></div>
    </div>
    <div class="chart-card">
      <h3>Temperatura percepita</h3>
      <div class="chart-subtitle">Max / Min °C</div>
      <div class="chart-wrap"><canvas id="chartFeels"></canvas></div>
    </div>
  </div>

  <div class="section-title">🌧 Precipitazioni</div>
  <div class="chart-card">
    <h3>Pioggia giornaliera</h3>
    <div class="chart-subtitle">mm · Totale mese: <strong><?= number_format($rainTotal, 1) ?> mm</strong></div>
    <div class="chart-wrap"><canvas id="chartRain"></canvas></div>
  </div>
  <div class="chart-card">
    <h3>Intensità massima pioggia</h3>
    <div class="chart-subtitle">mm/h</div>
    <div class="chart-wrap"><canvas id="chartRainRate"></canvas></div>
  </div>

  <div class="section-title">📊 Pressione atmosferica</div>
  <div class="chart-card">
    <h3>Pressione giornaliera</h3>
    <div class="chart-subtitle">Min / Max hPa</div>
    <div class="chart-wrap"><canvas id="chartPress"></canvas></div>
  </div>

  <div class="charts-2col">
    <div class="chart-card">
      <h3>Umidità relativa</h3>
      <div class="chart-subtitle">Min / Max %</div>
      <div class="chart-wrap"><canvas id="chartHum"></canvas></div>
    </div>
    <div class="chart-card">
      <h3>Vento</h3>
      <div class="chart-subtitle">Raffica max / Vento max km/h</div>
      <div class="chart-wrap"><canvas id="chartWind"></canvas></div>
    </div>
  </div>

  <?php if (!empty($rainByYear)): ?>
  <div class="section-title">📅 Pioggia totale per anno</div>
  <div class="chart-card">
    <h3>Precipitazioni annuali</h3>
    <div class="chart-subtitle">Totale mm per anno solare</div>
    <div class="chart-wrap"><canvas id="chartAnnualRain"></canvas></div>
    <table class="annual-table" style="margin-top:1rem;">
      <thead><tr><th>Anno</th><th>Totale</th><th class="bar-cell">Distribuzione</th></tr></thead>
      <tbody>
        <?php
          $maxRainYear = max($rainByYear);
          foreach ($rainByYear as $y => $tot):
        ?>
          <tr>
            <td class="year-cell"><?= $y ?></td>
            <td><?= number_format($tot, 1) ?> mm</td>
            <td class="bar-cell">
              <div class="bar-bg"><div class="bar-fill" style="width:<?= $maxRainYear > 0 ? round($tot/$maxRainYear*100) : 0 ?>%"></div></div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <?php else: ?>
  <div class="chart-card">
    <h3>Nessun dato storico trovato</h3>
    <p style="color:var(--muted);font-size:0.85rem;margin-top:0.5rem;">
      Verifica che i file <code>alldaily*.json</code> di CumulusMX siano presenti nella cartella configurata.
    </p>
  </div>
  <?php endif; ?>

  <footer>
    <span>© <?= date('Y') ?> <?= htmlspecialchars($config['station_name']) ?> · <?= htmlspecialchars($config['station_city']) ?> · <?= htmlspecialchars($config['version'] ?? 'v1.0.0') ?></span>
    <span><a href="index.php">🌡 Meteo Live</a></span>
  </footer>

</div>

<script>
(function(){
  const html = document.documentElement;
  const btn  = document.getElementById('themeToggle');
  const saved = localStorage.getItem('theme') || 'light';
  html.setAttribute('data-theme', saved);
  btn.textContent = saved === 'dark' ? '☀ Light' : '🌙 Dark';
  btn.addEventListener('click', function(){
    const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
    btn.textContent = next === 'dark' ? '☀ Light' : '🌙 Dark';
    updateAllChartThemes();
  });
})();

const ymData = <?= json_encode($ym, JSON_NUMERIC_CHECK) ?>;

function updateUrl() {
  const y = document.getElementById('selYear').value;
  const m = document.getElementById('selMonth').value;
  window.location.href = '?year=' + y + '&month=' + m;
}

const selYearElem = document.getElementById('selYear');
if (selYearElem) {
  selYearElem.addEventListener('change', function(){
    const y = parseInt(this.value);
    const months = ymData[y] || [];
    const selM = document.getElementById('selMonth');
    selM.innerHTML = '';
    const mNames = ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno',
                    'Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
    months.forEach(m => {
      const opt = document.createElement('option');
      opt.value = m; opt.textContent = mNames[m];
      selM.appendChild(opt);
    });
    selM.value = months[months.length - 1];
  });
}

const sel = {
  maxTemp:     <?= jsArr($sel['maxTemp'])     ?>,
  minTemp:     <?= jsArr($sel['minTemp'])     ?>,
  avgTemp:     <?= jsArr($sel['avgTemp'])     ?>,
  maxDew:      <?= jsArr($sel['maxDew'])      ?>,
  minDew:      <?= jsArr($sel['minDew'])      ?>,
  maxFeels:    <?= jsArr($sel['maxFeels'])    ?>,
  minFeels:    <?= jsArr($sel['minFeels'])    ?>,
  rain:        <?= jsArr($sel['rain'])        ?>,
  maxRainRate: <?= jsArr($sel['maxRainRate']) ?>,
  maxBaro:     <?= jsArr($sel['maxBaro'])     ?>,
  minBaro:     <?= jsArr($sel['minBaro'])     ?>,
  minHum:      <?= jsArr($sel['minHum'])      ?>,
  maxHum:      <?= jsArr($sel['maxHum'])      ?>,
  maxGust:     <?= jsArr($sel['maxGust'])     ?>,
  maxWind:     <?= jsArr($sel['maxWind'])     ?>,
};

const annualRain = <?= json_encode(array_map(fn($y,$v)=>[$y,$v], array_keys($rainByYear), array_values($rainByYear)), JSON_NUMERIC_CHECK) ?>;

function toXY(arr) {
  return arr.map(([ts, v]) => ({ x: new Date(ts), y: v }));
}

function getColors() {
  const dark = document.documentElement.getAttribute('data-theme') === 'dark';
  return {
    grid:   dark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)',
    ticks:  dark ? '#8492a6' : '#64748b',
    legend: dark ? '#f0f4ff' : '#0f172a',
  };
}

const allCharts = [];

function baseOptions(extra = {}) {
  const c = getColors();
  return {
    responsive: true, maintainAspectRatio: false,
    interaction: { mode: 'nearest', intersect: false },
    plugins: {
      legend: { labels: { usePointStyle: true, color: c.legend, boxWidth: 10, padding: 12, font: { size: 11 } } },
      tooltip: {
        callbacks: {
          title: items => new Date(items[0].parsed.x).toLocaleDateString('it-IT', { weekday:'short', day:'2-digit', month:'2-digit' })
        }
      }
    },
    scales: {
      x: {
        type: 'time',
        time: { unit:'day', displayFormats: { day: 'dd/MM' } },
        ticks: { color: c.ticks, font: { size: 10 }, maxTicksLimit: 16 },
        grid: { color: c.grid }
      },
      y: {
        ticks: { color: c.ticks, font: { size: 11 } },
        grid: { color: c.grid },
        ...extra
      }
    }
  };
}

function makeChart(id, cfg) {
  const ctx = document.getElementById(id)?.getContext('2d');
  if (!ctx) return null;
  const ch = new Chart(ctx, cfg);
  allCharts.push(ch);
  return ch;
}

<?php if (!empty($ym)): ?>
makeChart('chartTemp', {
  type: 'line',
  data: {
    datasets: [
      { label: 'Max °C', data: toXY(sel.maxTemp), borderColor: '#f87171', backgroundColor: 'rgba(248,113,113,0.1)', borderWidth: 2, pointRadius: 2, tension: 0.3, fill: false },
      { label: 'Media °C', data: toXY(sel.avgTemp), borderColor: '#fbbf24', borderWidth: 2, pointRadius: 1, tension: 0.3, fill: false, borderDash: [4,3] },
      { label: 'Min °C', data: toXY(sel.minTemp), borderColor: '#38bdf8', backgroundColor: 'rgba(56,189,248,0.1)', borderWidth: 2, pointRadius: 2, tension: 0.3, fill: false },
    ]
  },
  options: baseOptions({ title: { display: true, text: '°C', color: getColors().ticks, font: { size: 10 } } })
});

makeChart('chartDew', {
  type: 'line',
  data: {
    datasets: [
      { label: 'Dew max', data: toXY(sel.maxDew), borderColor: '#818cf8', borderWidth: 2, pointRadius: 1, tension: 0.3, fill: false },
      { label: 'Dew min', data: toXY(sel.minDew), borderColor: '#c4b5fd', borderWidth: 1.5, pointRadius: 1, tension: 0.3, fill: false, borderDash: [3,3] },
    ]
  },
  options: baseOptions()
});

makeChart('chartFeels', {
  type: 'line',
  data: {
    datasets: [
      { label: 'Percepita max', data: toXY(sel.maxFeels), borderColor: '#fb923c', borderWidth: 2, pointRadius: 1, tension: 0.3, fill: false },
      { label: 'Percepita min', data: toXY(sel.minFeels), borderColor: '#93c5fd', borderWidth: 1.5, pointRadius: 1, tension: 0.3, fill: false, borderDash: [3,3] },
    ]
  },
  options: baseOptions()
});

makeChart('chartRain', {
  type: 'bar',
  data: {
    datasets: [{ label: 'Pioggia mm', data: toXY(sel.rain), backgroundColor: 'rgba(96,165,250,0.7)', borderColor: '#60a5fa', borderWidth: 1, borderRadius: 3 }]
  },
  options: baseOptions({ beginAtZero: true })
});

makeChart('chartRainRate', {
  type: 'bar',
  data: {
    datasets: [{ label: 'Intensità mm/h', data: toXY(sel.maxRainRate), backgroundColor: 'rgba(14,165,233,0.6)', borderColor: '#0ea5e9', borderWidth: 1, borderRadius: 3 }]
  },
  options: baseOptions({ beginAtZero: true })
});

makeChart('chartPress', {
  type: 'line',
  data: {
    datasets: [
      { label: 'Max hPa', data: toXY(sel.maxBaro), borderColor: '#34d399', borderWidth: 2, pointRadius: 1, tension: 0.3, fill: '+1', backgroundColor: 'rgba(52,211,153,0.07)' },
      { label: 'Min hPa', data: toXY(sel.minBaro), borderColor: '#a3e635', borderWidth: 1.5, pointRadius: 1, tension: 0.3, fill: false, borderDash: [3,3] },
    ]
  },
  options: baseOptions()
});

makeChart('chartHum', {
  type: 'line',
  data: {
    datasets: [
      { label: 'Max %', data: toXY(sel.maxHum), borderColor: '#818cf8', borderWidth: 2, pointRadius: 1, tension: 0.3, fill: '+1', backgroundColor: 'rgba(129,140,248,0.07)' },
      { label: 'Min %', data: toXY(sel.minHum), borderColor: '#c4b5fd', borderWidth: 1.5, pointRadius: 1, tension: 0.3, fill: false, borderDash: [3,3] },
    ]
  },
  options: baseOptions({ min: 0, max: 100 })
});

makeChart('chartWind', {
  type: 'line',
  data: {
    datasets: [
      { label: 'Raffica max km/h', data: toXY(sel.maxGust), borderColor: '#fbbf24', backgroundColor: 'rgba(251,191,36,0.1)', borderWidth: 2, pointRadius: 1, tension: 0.3, fill: true },
      { label: 'Vento max km/h',   data: toXY(sel.maxWind), borderColor: '#f59e0b', borderWidth: 1.5, pointRadius: 1, tension: 0.3, fill: false, borderDash: [4,3] },
    ]
  },
  options: baseOptions({ beginAtZero: true })
});

makeChart('chartAnnualRain', {
  type: 'bar',
  data: {
    labels: annualRain.map(([y]) => y),
    datasets: [{
      label: 'mm/anno',
      data: annualRain.map(([,v]) => v),
      backgroundColor: annualRain.map((_, i) => i === annualRain.length-1 ? 'rgba(96,165,250,0.45)' : 'rgba(96,165,250,0.75)'),
      borderColor: '#60a5fa', borderWidth: 1.5, borderRadius: 6
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: { callbacks: { label: item => ` ${item.parsed.y.toFixed(1)} mm` } }
    },
    scales: {
      x: { ticks: { color: getColors().ticks }, grid: { display: false } },
      y: { beginAtZero: true, ticks: { color: getColors().ticks }, grid: { color: getColors().grid } }
    }
  }
});
<?php endif; ?>

function updateAllChartThemes() {
  const c = getColors();
  allCharts.forEach(ch => {
    if (!ch) return;
    if (ch.options.plugins?.legend?.labels) ch.options.plugins.legend.labels.color = c.legend;
    ['x','y'].forEach(ax => {
      if (ch.options.scales?.[ax]) {
        ch.options.scales[ax].ticks && (ch.options.scales[ax].ticks.color = c.ticks);
        ch.options.scales[ax].grid  && (ch.options.scales[ax].grid.color  = c.grid);
      }
    });
    ch.update('none');
  });
}
</script>
</body>
</html>