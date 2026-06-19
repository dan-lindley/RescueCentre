<?php
// views/groups/dashboard_data.php
// Master data loader for the network/group dashboard partials.
//
// Expected before include:
// - APP_LOADED defined
// - $pdo available
// - $network_id available
//
// Provides:
// - $gid
// - $range, $rangeDays, $rangeLabel, $rangeSql, $rangeParams
// - $loadErrors
// - $centreIds, $centresCount, $memberCentres
// - $networkStats
// - $centreStats
// - $speciesRows
// - $mapRows
// - helper functions used by dashboard partials

if (!defined('APP_LOADED')) {
    echo '<div class="rc-alert red"><strong>Error</strong><br>APP_LOADED not defined.</div>';
    return;
}

if (!isset($pdo)) {
    echo '<div class="rc-alert red"><strong>Error</strong><br>Database connection not available.</div>';
    return;
}

if (!isset($network_id) || (int)$network_id <= 0) {
    echo '<div class="rc-alert red"><strong>Error</strong><br>Network context missing.</div>';
    return;
}

$gid = (int)$network_id;
$loadErrors = [];

/**
 * ---------------------------
 * Helpers
 * ---------------------------
 */
if (!function_exists('group_dash_in_placeholders')) {
    function group_dash_in_placeholders(array $ids, string $prefix = ':id'): array
    {
        $ph = [];
        $params = [];

        foreach (array_values($ids) as $i => $id) {
            $key = $prefix . $i;
            $ph[] = $key;
            $params[$key] = (int)$id;
        }

        return [$ph, $params];
    }
}

if (!function_exists('group_dash_num')) {
    function group_dash_num($value, int $decimals = 0): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        return number_format((float)$value, $decimals);
    }
}

if (!function_exists('group_dash_pct')) {
    function group_dash_pct($num, $den, int $decimals = 1): float
    {
        $num = (float)$num;
        $den = (float)$den;
        if ($den <= 0) {
            return 0.0;
        }
        return round(($num / $den) * 100, $decimals);
    }
}

if (!function_exists('group_dash_capacity_state')) {
    function group_dash_capacity_state($pct): array
    {
        $pct = (float)$pct;

        if ($pct >= 100) {
            return ['class' => 'is-high', 'label' => 'Full / over capacity'];
        }
        if ($pct >= 85) {
            return ['class' => 'is-high', 'label' => 'High occupancy'];
        }
        if ($pct >= 60) {
            return ['class' => 'is-medium', 'label' => 'Moderate occupancy'];
        }
        return ['class' => 'is-good', 'label' => 'Comfortable capacity'];
    }
}

if (!function_exists('group_dash_colour_for_index')) {
    function group_dash_colour_for_index(int $index): string
    {
        $palette = [
            '#2563eb', // blue
            '#16a34a', // green
            '#ea580c', // orange
            '#7c3aed', // purple
            '#dc2626', // red
            '#0891b2', // cyan
            '#ca8a04', // mustard
            '#db2777', // pink
            '#4f46e5', // indigo
            '#0f766e', // teal
            '#65a30d', // lime
            '#c2410c', // burnt orange
            '#9333ea', // violet
            '#0ea5e9', // sky
            '#be123c', // rose
            '#4338ca', // deep indigo
            '#15803d', // forest green
            '#a16207', // ochre
            '#1d4ed8', // royal blue
            '#b91c1c', // dark red
            '#0d9488', // turquoise
            '#7e22ce', // strong purple
            '#e11d48', // magenta rose
            '#0369a1', // steel blue
        ];

        return $palette[$index % count($palette)];
    }
}

/**
 * ---------------------------
 * Range / filter state
 * ---------------------------
 */
$range = $_GET['range'] ?? 'all';

$allowedRanges = [
    'all' => null,
    '30'  => 30,
    '90'  => 90,
    '180' => 180,
    '365' => 365,
    '730' => 730,
];

if (!array_key_exists($range, $allowedRanges)) {
    $range = 'all';
}

$rangeDays = $allowedRanges[$range];

$rangeLabelMap = [
    'all' => 'All time',
    '30'  => 'Last 30 days',
    '90'  => 'Last 3 months',
    '180' => 'Last 6 months',
    '365' => 'Last 1 year',
    '730' => 'Last 2 years',
];

$rangeLabel = $rangeLabelMap[$range] ?? 'All time';

$rangeSql = '';
$rangeParams = [];
if ($rangeDays !== null) {
    $rangeSql = " AND a.admission_date >= DATE_SUB(CURDATE(), INTERVAL :range_days DAY) ";
    $rangeParams[':range_days'] = (int)$rangeDays;
}

/**
 * ---------------------------
 * Load member centres
 * ---------------------------
 */
$centreIds = [];
$memberCentres = [];   // keyed by centre_id
$centresCount = 0;

try {
    $stmt = $pdo->prepare("
        SELECT gm.centre_id
        FROM rescue_group_members gm
        WHERE gm.group_id = :gid
          AND gm.status = 'active'
        ORDER BY gm.centre_id
    ");
    $stmt->execute([':gid' => $gid]);

    $centreIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    $centresCount = count($centreIds);

    foreach ($centreIds as $idx => $centreId) {
        $memberCentres[$centreId] = [
            'centre_id'   => (int)$centreId,
            'centre_name' => 'Centre #' . (int)$centreId,
            'colour'      => group_dash_colour_for_index($idx),
        ];
    }
} catch (Throwable $e) {
    $loadErrors[] = 'Failed to load network members: ' . $e->getMessage();
}

/**
 * Centre name enrichment (primary source: rescue_centres)
 */
if (!empty($centreIds)) {
    try {
        [$centrePh, $centreParams] = group_dash_in_placeholders($centreIds, ':centre_lookup_');

        $sql = "
            SELECT rc.rescue_id, rc.rescue_name
            FROM rescue_centres rc
            WHERE rc.rescue_id IN (" . implode(',', $centrePh) . ")
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($centreParams);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cid = (int)($row['rescue_id'] ?? 0);

            if ($cid > 0 && isset($memberCentres[$cid])) {
                $name = trim((string)($row['rescue_name'] ?? ''));

                if ($name !== '') {
                    $memberCentres[$cid]['centre_name'] = $name;
                }
            }
        }
    } catch (Throwable $e) {
        $loadErrors[] = 'Centre name lookup failed: ' . $e->getMessage();
    }
}

/**
 * ---------------------------
 * Default output structures
 * ---------------------------
 */

// Radius selector for rescue centre boundaries on the map
$selectedRadiusMiles = isset($_GET['radius']) ? (int)$_GET['radius'] : 0;
$allowedRadiusMiles = [0, 5, 10, 15, 20, 30, 50];

if (!in_array($selectedRadiusMiles, $allowedRadiusMiles, true)) {
    $selectedRadiusMiles = 0;
}

$selectedRadiusMeters = $selectedRadiusMiles * 1609.344;

$networkStats = [
    'centres_count'               => $centresCount,
    'active_admissions'           => 0,
    'total_capacity'              => 0,
    'occupied_spaces'             => 0,   // first pass uses active admissions as occupied proxy
    'occupancy_percent'           => 0.0,
    'capacity_state'              => ['class' => 'is-good', 'label' => 'Comfortable capacity'],
    'admissions_in_range'         => 0,
    'admissions_all_time'         => 0,
    'map_admissions_count'        => 0,
    'map_centres_contributing'    => 0,
    'centres_high_occupancy'      => 0,
    'centres_full_or_over'        => 0,
    'top_centre_name'             => null,
    'top_centre_active'           => 0,
    'average_centre_occupancy'    => 0.0,
];

$centreStats = [];        // ordered rows for occupancy/member centre section
$speciesRows = [];
$mapRows = [];
$centreLocationRows = []; // fixed rescue centre base markers for map

if ($centresCount === 0) {
    return;
}

[$ph, $params] = group_dash_in_placeholders($centreIds, ':c');

/**
 * ---------------------------
 * Network totals
 * ---------------------------
 */

// Active admissions across member centres
try {
    $sql = "
        SELECT COUNT(*)
        FROM rescue_admissions a
        WHERE a.centre_id IN (" . implode(',', $ph) . ")
          AND LOWER(a.status) = 'active'
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $networkStats['active_admissions'] = (int)$stmt->fetchColumn();
    $networkStats['occupied_spaces'] = (int)$networkStats['active_admissions'];
} catch (Throwable $e) {
    $loadErrors[] = 'Active admissions unavailable: ' . $e->getMessage();
}

// Admissions in selected range
try {
    $sql = "
        SELECT COUNT(*)
        FROM rescue_admissions a
        WHERE a.centre_id IN (" . implode(',', $ph) . ")
          $rangeSql
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params + $rangeParams);
    $networkStats['admissions_in_range'] = (int)$stmt->fetchColumn();
} catch (Throwable $e) {
    $loadErrors[] = 'Admissions for selected range unavailable: ' . $e->getMessage();
}

// Admissions all time
try {
    $sql = "
        SELECT COUNT(*)
        FROM rescue_admissions a
        WHERE a.centre_id IN (" . implode(',', $ph) . ")
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $networkStats['admissions_all_time'] = (int)$stmt->fetchColumn();
} catch (Throwable $e) {
    $loadErrors[] = 'Total admissions unavailable: ' . $e->getMessage();
}

// Total capacity
try {
    $sql = "
        SELECT COALESCE(SUM(COALESCE(l.max_occupancy, 0)), 0) AS total_capacity
        FROM rescue_locations l
        WHERE l.centre_id IN (" . implode(',', $ph) . ")
          AND COALESCE(l.deleted, 0) = 0
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $networkStats['total_capacity'] = (int)$stmt->fetchColumn();
} catch (Throwable $e) {
    $loadErrors[] = 'Capacity unavailable: ' . $e->getMessage();
}

$networkStats['occupancy_percent'] = group_dash_pct(
    $networkStats['occupied_spaces'],
    $networkStats['total_capacity'],
    1
);

$networkStats['capacity_state'] = group_dash_capacity_state($networkStats['occupancy_percent']);

/**
 * ---------------------------
 * Species breakdown
 * ---------------------------
 */
try {
    $sql = "
        SELECT
            COALESCE(NULLIF(TRIM(p.animal_species), ''), 'Unknown') AS species,
            COUNT(*) AS total
        FROM rescue_admissions a
        JOIN rescue_patients p
          ON p.patient_id = a.patient_id
        WHERE a.centre_id IN (" . implode(',', $ph) . ")
          AND LOWER(a.status) = 'active'
        GROUP BY COALESCE(NULLIF(TRIM(p.animal_species), ''), 'Unknown')
        ORDER BY total DESC, species ASC
        LIMIT 12
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $speciesRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $loadErrors[] = 'Species breakdown unavailable: ' . $e->getMessage();
}

/**
 * ---------------------------
 * Map dataset
 * ---------------------------
 */
try {
    $sql = "
        SELECT
            a.admission_id,
            a.patient_id,
            a.centre_id,
            a.admission_date,
            a.location_lat,
            a.location_long,
            COALESCE(NULLIF(TRIM(p.animal_species), ''), 'Unknown') AS species
        FROM rescue_admissions a
        LEFT JOIN rescue_patients p
          ON p.patient_id = a.patient_id
        WHERE a.centre_id IN (" . implode(',', $ph) . ")
          AND a.location_lat IS NOT NULL
          AND a.location_lat <> ''
          AND a.location_long IS NOT NULL
          AND a.location_long <> ''
          $rangeSql
        ORDER BY a.admission_date DESC
        LIMIT 2000
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params + $rangeParams);
    $rawMapRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($rawMapRows as $row) {
        $cid = (int)($row['centre_id'] ?? 0);
        $centreMeta = $memberCentres[$cid] ?? [
            'centre_id'   => $cid,
            'centre_name' => 'Centre #' . $cid,
            'colour'      => '#2563eb',
        ];

        $mapRows[] = [
            'admission_id'   => (int)($row['admission_id'] ?? 0),
            'patient_id'     => (int)($row['patient_id'] ?? 0),
            'centre_id'      => $cid,
            'centre_name'    => $centreMeta['centre_name'],
            'centre_colour'  => $centreMeta['colour'],
            'species'        => (string)($row['species'] ?? 'Unknown'),
            'admission_date' => (string)($row['admission_date'] ?? ''),
            'lat'            => (float)$row['location_lat'],
            'lng'            => (float)$row['location_long'],
        ];
    }

    $networkStats['map_admissions_count'] = count($mapRows);
    $networkStats['map_centres_contributing'] = count(array_unique(array_column($mapRows, 'centre_id')));
} catch (Throwable $e) {
    $loadErrors[] = 'Map dataset unavailable: ' . $e->getMessage();
}

/**
 * ---------------------------
 * Per-centre occupancy / member stats
 * ---------------------------
 */
try {
    $sql = "
        SELECT
            x.centre_id,
            COALESCE(ac.active_admissions, 0) AS active_admissions,
            COALESCE(cap.total_capacity, 0) AS total_capacity
        FROM (
            SELECT gm.centre_id
            FROM rescue_group_members gm
            WHERE gm.group_id = :gid
              AND gm.status = 'active'
        ) x
        LEFT JOIN (
            SELECT a.centre_id, COUNT(*) AS active_admissions
            FROM rescue_admissions a
            WHERE a.centre_id IN (" . implode(',', $ph) . ")
              AND LOWER(a.status) = 'active'
            GROUP BY a.centre_id
        ) ac ON ac.centre_id = x.centre_id
        LEFT JOIN (
            SELECT l.centre_id, COALESCE(SUM(COALESCE(l.max_occupancy, 0)), 0) AS total_capacity
            FROM rescue_locations l
            WHERE l.centre_id IN (" . implode(',', $ph) . ")
              AND COALESCE(l.deleted, 0) = 0
            GROUP BY l.centre_id
        ) cap ON cap.centre_id = x.centre_id
        ORDER BY COALESCE(ac.active_admissions, 0) DESC, x.centre_id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':gid' => $gid] + $params);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cid = (int)($row['centre_id'] ?? 0);
        $active = (int)($row['active_admissions'] ?? 0);
        $capacity = (int)($row['total_capacity'] ?? 0);
        $occupancy = group_dash_pct($active, $capacity, 1);
        $state = group_dash_capacity_state($occupancy);

        $centreStats[] = [
            'centre_id'          => $cid,
            'centre_name'        => $memberCentres[$cid]['centre_name'] ?? ('Centre #' . $cid),
            'colour'             => $memberCentres[$cid]['colour'] ?? '#2563eb',
            'active_admissions'  => $active,
            'occupied_spaces'    => $active,
            'total_capacity'     => $capacity,
            'occupancy_percent'  => $occupancy,
            'capacity_state'     => $state,
        ];
    }
} catch (Throwable $e) {
    $loadErrors[] = 'Centre occupancy stats unavailable: ' . $e->getMessage();
}

/**
 * ---------------------------
 * Derived centre-level summary metrics
 * ---------------------------
 */
if (!empty($centreStats)) {
    $sumOccupancy = 0.0;
    $countWithCapacity = 0;

    foreach ($centreStats as $row) {
        if ($row['total_capacity'] > 0) {
            $sumOccupancy += (float)$row['occupancy_percent'];
            $countWithCapacity++;
        }

        if ($row['occupancy_percent'] >= 85) {
            $networkStats['centres_high_occupancy']++;
        }
        if ($row['occupancy_percent'] >= 100) {
            $networkStats['centres_full_or_over']++;
        }
    }

    $networkStats['average_centre_occupancy'] = $countWithCapacity > 0
        ? round($sumOccupancy / $countWithCapacity, 1)
        : 0.0;

    $topCentre = $centreStats[0] ?? null;
    if ($topCentre) {
        $networkStats['top_centre_name'] = $topCentre['centre_name'];
        $networkStats['top_centre_active'] = (int)$topCentre['active_admissions'];
    }
}
/**
 * ---------------------------
 * Admissions trend (chart)
 * ---------------------------
 * Builds $admissionsChartRows for Chart.js
 */

$admissionsChartRows = [];

if ($centresCount > 0) {
    try {
        // Reuse existing placeholders
        [$ph, $params] = group_dash_in_placeholders($centreIds, ':chart_c');

        // Date filter (same logic as map)
        $dateSql = '';
        $dateParams = [];

        if ($rangeDays !== null) {
            $dateSql = " AND a.admission_date >= DATE_SUB(NOW(), INTERVAL :chart_days DAY) ";
            $dateParams[':chart_days'] = (int)$rangeDays;
        }

        // Grouping logic:
        // - shorter ranges = daily
        // - longer ranges = monthly (keeps chart readable)
        if ($rangeDays !== null && $rangeDays <= 90) {
            $dateSelect = "DATE(a.admission_date)";
            $labelFormat = "%Y-%m-%d";
        } else {
            $dateSelect = "DATE_FORMAT(a.admission_date, '%Y-%m-01')";
            $labelFormat = "%Y-%m";
        }

        $sql = "
            SELECT
                $dateSelect AS d,
                COUNT(*) AS total
            FROM rescue_admissions a
            WHERE a.centre_id IN (" . implode(',', $ph) . ")
            $dateSql
            GROUP BY d
            ORDER BY d ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params + $dateParams);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rawDate = (string)($row['d'] ?? '');
            $total   = (int)($row['total'] ?? 0);

            if ($rawDate === '') {
                continue;
            }

            // Format label nicely for chart
            if ($rangeDays !== null && $rangeDays <= 90) {
                // Daily → nicer short date
                $label = date('j M', strtotime($rawDate));
            } else {
                // Monthly
                $label = date('M Y', strtotime($rawDate));
            }

            $admissionsChartRows[] = [
                'label' => $label,
                'total' => $total,
            ];
        }
    } catch (Throwable $e) {
        $loadErrors[] = 'Admissions chart unavailable: ' . $e->getMessage();
    }
}
/**
 * ---------------------------
 * Rescue centre base locations
 * ---------------------------
 * Uses rescue_centres.centre_lat / centre_long for fixed map pins
 * and supports optional radius boundaries in map.php
 */
try {
    $centreStatsById = [];
    foreach ($centreStats as $row) {
        $centreStatsById[(int)$row['centre_id']] = $row;
    }

    $sql = "
        SELECT
            rc.rescue_id,
            rc.rescue_name,
            rc.centre_lat,
            rc.centre_long
        FROM rescue_centres rc
        WHERE rc.rescue_id IN (" . implode(',', $ph) . ")
          AND rc.centre_lat IS NOT NULL
          AND rc.centre_lat <> ''
          AND rc.centre_long IS NOT NULL
          AND rc.centre_long <> ''
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cid = (int)($row['rescue_id'] ?? 0);
        if ($cid <= 0) {
            continue;
        }

        $meta = $memberCentres[$cid] ?? [
            'centre_id'   => $cid,
            'centre_name' => 'Centre #' . $cid,
            'colour'      => '#2563eb',
        ];

        $stat = $centreStatsById[$cid] ?? [];

        $rescueName = trim((string)($row['rescue_name'] ?? ''));
        if ($rescueName === '') {
            $rescueName = (string)$meta['centre_name'];
        }

        $centreLocationRows[] = [
            'centre_id'          => $cid,
            'centre_name'        => $rescueName,
            'colour'             => (string)$meta['colour'],
            'lat'                => (float)$row['centre_lat'],
            'lng'                => (float)$row['centre_long'],
            'active_admissions'  => (int)($stat['active_admissions'] ?? 0),
            'total_capacity'     => (int)($stat['total_capacity'] ?? 0),
            'occupancy_percent'  => (float)($stat['occupancy_percent'] ?? 0),
        ];
    }
} catch (Throwable $e) {
    $loadErrors[] = 'Centre map locations unavailable: ' . $e->getMessage();
}
