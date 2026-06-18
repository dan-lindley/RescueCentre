<?php
header('Content-Type: application/json; charset=utf-8');
$limit  = isset($_GET['limit'])  ? (int)$_GET['limit']  : 10;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

if ($limit < 1)  $limit = 10;
if ($limit > 50) $limit = 50;
if ($offset < 0) $offset = 0;

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if (mb_strlen($q) < 2) { echo json_encode([]); exit; }

function curl_json($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'User-Agent: rescue-app/1.0'
        ]
    ]);
    $raw  = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!$raw || $http < 200 || $http >= 300) return null;
    $json = json_decode($raw, true);
    return is_array($json) ? $json : null;
}

function canonicalize_scientific($canonicalName, $scientificName) {
    $c = trim((string)$canonicalName);
    if ($c !== '') return $c;

    // Fallback: strip authorship from scientificName (remove trailing " (Author, year)" or " Author, year")
    $s = trim((string)$scientificName);
    if ($s === '') return '';

    // Remove trailing parenthetical authorship
    $s = preg_replace('/\s*\([^)]*\)\s*$/', '', $s);
    // Remove trailing authorship without parentheses (best-effort)
    $s = preg_replace('/\s+[A-Z][A-Za-z\.\-]+.*$/', '', $s);

    return trim($s);
}

function best_common_name_for_query($key, $qLower) {
    $v = curl_json('https://api.gbif.org/v1/species/' . urlencode((string)$key) . '/vernacularNames?limit=100');
    if (!is_array($v) || !isset($v['results']) || !is_array($v['results'])) return '';

    $bestContainsEn = '';
    $bestContainsAny = '';
    $bestEnPreferred = '';
    $bestEn = '';

    foreach ($v['results'] as $row) {
        $name = trim((string)($row['vernacularName'] ?? ''));
        if ($name === '') continue;

        $lang = strtolower((string)($row['language'] ?? ''));
        $preferred = !empty($row['preferred']);
        $nameLower = strtolower($name);

        if ($lang === 'en' && $preferred && $bestEnPreferred === '') $bestEnPreferred = $name;
        if ($lang === 'en' && $bestEn === '') $bestEn = $name;

        if (strpos($nameLower, $qLower) !== false) {
            if ($lang === 'en' && $bestContainsEn === '') $bestContainsEn = $name;
            if ($bestContainsAny === '') $bestContainsAny = $name;
        }
    }

    if ($bestContainsEn !== '') return $bestContainsEn;
    if ($bestContainsAny !== '') return $bestContainsAny;
    if ($bestEnPreferred !== '') return $bestEnPreferred;
    return $bestEn;
}

$qLower = strtolower($q);

$backboneDatasetKey = 'd7dddbf4-2cf0-4f39-9b2a-bb099caae36c';

$searchUrl = 'https://api.gbif.org/v1/species/search'
    . '?datasetKey=' . urlencode($backboneDatasetKey)
    . '&qField=VERNACULAR'
    . '&q=' . urlencode($q)
    . '&rank=SPECIES'
    . '&status=ACCEPTED'
    . '&higherTaxonKey=359'
    . '&limit=' . $limit
    . '&offset=' . $offset;


$j = curl_json($searchUrl);
if (!is_array($j) || !isset($j['results']) || !is_array($j['results'])) { echo json_encode([]); exit; }

$candidates = [];
foreach ($j['results'] as $r) {
    $key = $r['key'] ?? null;
    if (!$key) continue;

    $canon = canonicalize_scientific($r['canonicalName'] ?? '', $r['scientificName'] ?? '');
    if ($canon === '') continue;

    $common = best_common_name_for_query($key, $qLower);
    if ($common === '') continue;

    $candidates[] = [
        'gbif_id' => (string)$key,
        'common' => $common,
        'scientific' => $canon
    ];

    if (count($candidates) >= $limit) break;
}

$score = function($common) use ($qLower) {
    $c = strtolower($common);
    if ($c === $qLower) return 0;
    if (strpos($c, $qLower . ' ') === 0) return 1;
    if (strpos($c, $qLower) === 0) return 2;
    if (strpos($c, ' ' . $qLower) !== false) return 3;
    if (strpos($c, $qLower) !== false) return 4;
    return 5;
};

usort($candidates, function($a, $b) use ($score) {
    $sa = $score($a['common']);
    $sb = $score($b['common']);
    if ($sa !== $sb) return $sa <=> $sb;
    return strcmp($a['common'], $b['common']);
});

$out = [];
foreach ($candidates as $c) {
    $out[] = [
        'gbif_id' => $c['gbif_id'],
        'display' => $c['common'] . ' (' . $c['scientific'] . ')'
    ];
    if (count($out) >= $limit) break;
}

echo json_encode($out);
