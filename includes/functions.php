<?php
// includes/functions.php

function fetchRealtime(string $path): array {
    if (!file_exists($path) || !is_readable($path)) return [];
    $txt = @file_get_contents($path);
    if ($txt === false) return [];
    $line = trim(strtok($txt, "\r\n"));
    $parts = preg_split('/\s+/', $line);
    return is_array($parts) ? $parts : [];
}

function rt(array $p, int $i, $default = null) {
    return isset($p[$i]) && $p[$i] !== '' ? $p[$i] : $default;
}

// Calcolo Wet Bulb (Stull 2011)
function calculateWetBulb(?float $T, ?float $RH): ?float {
    if ($T === null || $RH === null || $RH <= 0) return null;
    return round(
        $T * atan(0.151977 * sqrt($RH + 8.313659))
        + atan($T + $RH)
        - atan($RH - 1.676331)
        + 0.00391838 * pow($RH, 1.5) * atan(0.023101 * $RH)
        - 4.686035,
    1);
}

function pressTendLabel($v): string {
    if ($v === null) return '--';
    $f = (float)$v;
    if ($f >  0.5) return '↑ In salita';
    if ($f < -0.5) return '↓ In discesa';
    return '→ Stabile';
}

function pressTendIcon($v): string {
    if ($v === null) return '→';
    $f = (float)$v;
    if ($f >  0.5) return '↑';
    if ($f < -0.5) return '↓';
    return '→';
}

function bearingToName(?int $deg): string {
    if ($deg === null) return '--';
    $dirs = ['N','NNE','NE','ENE','E','ESE','SE','SSE','S','SSO','SO','OSO','O','ONO','NO','NNO'];
    return $dirs[round($deg / 22.5) % 16];
}

function tempColor(?float $t): string {
    if ($t === null) return '#e5e7eb';
    if ($t <= 0)  return '#60a5fa';
    if ($t <= 10) return '#34d399';
    if ($t <= 20) return '#fbbf24';
    if ($t <= 30) return '#f97316';
    return '#ef4444';
}

function loadJsonFile(string $path): array {
    if (!file_exists($path)) return [];
    $raw = @file_get_contents($path);
    return $raw ? (json_decode($raw, true) ?: []) : [];
}

function filterSeries(array $s, $lo, $hi): array {
    if ($lo === null && $hi === null) return $s;
    $out = [];
    foreach ($s as $p) {
        if ($p[1] >= $lo && $p[1] <= $hi) $out[] = $p;
    }
    return array_values($out);
}

function filterByYearMonth(array $s, int $y, int $m): array {
    $tz = new DateTimeZone('Europe/Rome');
    $out = [];
    foreach ($s as $p) {
        $dt = new DateTime('@'.intval($p[0]/1000));
        $dt->setTimezone($tz);
        if ((int)$dt->format('Y') === $y && (int)$dt->format('n') === $m) $out[] = $p;
    }
    return array_values($out);
}

function toChartJson(array $series): string {
    $out = [];
    foreach ($series as $p) $out[] = ['x' => $p[0], 'y' => $p[1]];
    return json_encode($out, JSON_NUMERIC_CHECK);
}

function aggregateByMonth(array $series, int $year, string $agg = 'avg'): array {
    $tz = new DateTimeZone('Europe/Rome');
    $byMonth = [];
    foreach ($series as $p) {
        $ts = $p[0]; $v = $p[1];
        $dt = new DateTime('@'.intval($ts/1000));
        $dt->setTimezone($tz);
        if ((int)$dt->format('Y') !== $year) continue;
        $m = (int)$dt->format('n');
        $byMonth[$m][] = $v;
    }
    $out = [];
    for ($m = 1; $m <= 12; $m++) {
        if (!isset($byMonth[$m])) continue;
        $vals = $byMonth[$m];
        if ($agg === 'max')      $val = max($vals);
        elseif ($agg === 'min')  $val = min($vals);
        elseif ($agg === 'sum')  $val = array_sum($vals);
        else                     $val = array_sum($vals) / count($vals);
        $out[] = ['x' => $m, 'y' => round($val, 1)];
    }
    return $out;
}