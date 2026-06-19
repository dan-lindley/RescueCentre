<?php
// models/all_dataModel.php

/* ------------------------------------------------------------
   DEFAULTS
   ------------------------------------------------------------ */
$disptotal = 0;
$dispcaptive = 0;
$dispreleased = 0;
$disptrans = 0;
$dispeuth = 0;
$dispin48 = 0;
$dispafter48 = 0;
$dispdoa = 0;
$dispdiedtotal = 0;
$clinefficiency = 0;

$ytddisptotal = 0;
$ytddispcaptive = 0;
$ytddispreleased = 0;
$ytddisptrans = 0;
$ytddispeuth = 0;
$ytddispin48 = 0;
$ytddispafter48 = 0;
$ytddispdoa = 0;
$ytddispdiedtotal = 0;
$ytdclinefficiency = 0;
$ytdyear = date('Y');

$lastYear = (int)date('Y') - 1;

$lastyearytddisptotal = 0;
$lastyearytddispreleased = 0;
$lastyearytddispdiedtotal = 0;
$lastyearytdclinefficiency = 0;

$allTimeReleaseRate = 0;

$totalCapacity = 0;
$occupiedSpaces = 0;
$capacityPercent = 0;

$admissionComparisons = [
    '1d'  => ['current' => 0, 'previous' => 0, 'pct' => 0],
    '7d'  => ['current' => 0, 'previous' => 0, 'pct' => 0],
    '31d' => ['current' => 0, 'previous' => 0, 'pct' => 0],
    'ytd' => ['current' => 0, 'previous' => 0, 'pct' => 0],
];

$releaseComparisons = [
    '1d'  => ['current' => 0, 'previous' => 0, 'pct' => 0],
    '7d'  => ['current' => 0, 'previous' => 0, 'pct' => 0],
    '31d' => ['current' => 0, 'previous' => 0, 'pct' => 0],
    'ytd' => ['current' => 0, 'previous' => 0, 'pct' => 0],
];

$deathComparisons = [
    '1d'  => ['current' => 0, 'previous' => 0, 'pct' => 0],
    '7d'  => ['current' => 0, 'previous' => 0, 'pct' => 0],
    '31d' => ['current' => 0, 'previous' => 0, 'pct' => 0],
    'ytd' => ['current' => 0, 'previous' => 0, 'pct' => 0],
];

if (!function_exists('clinical_efficiency_for_period')) {
function clinical_efficiency_for_period(PDO $pdo, int $centre_id, ?string $date_start = null, ?string $date_end = null): array {
    $dateFilter = '';
    $params = ['centre_id' => $centre_id];

    if ($date_start !== null && $date_end !== null) {
        $dateFilter = ' AND admission_date BETWEEN :date_start AND :date_end';
        $params['date_start'] = $date_start;
        $params['date_end'] = $date_end;
    }

    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) AS total_admissions,
            SUM(CASE WHEN LOWER(TRIM(disposition)) = 'released' THEN 1 ELSE 0 END) AS released,
            SUM(CASE
                WHEN LOWER(TRIM(disposition)) IN ('died - after 48 hours', 'died after 48 hours') THEN 1
                ELSE 0
            END) AS died_after_48,
            SUM(CASE
                WHEN LOWER(TRIM(disposition)) IN ('died - within 48 hours', 'died within 48 hours') THEN 1
                ELSE 0
            END) AS died_within_48,
            SUM(CASE
                WHEN LOWER(TRIM(disposition)) IN ('died - on admission', 'died on admission') THEN 1
                ELSE 0
            END) AS died_on_admission,
            SUM(CASE
                WHEN LOWER(TRIM(disposition)) IN ('died - euthanised', 'died euthanised') THEN 1
                ELSE 0
            END) AS euthanised,
            SUM(CASE
                WHEN LOWER(TRIM(disposition)) IN ('transferred out', 'transferred to another rescue') THEN 1
                ELSE 0
            END) AS transferred,
            SUM(CASE
                WHEN LOWER(TRIM(disposition)) IN ('long-term captive', 'long term captive') THEN 1
                ELSE 0
            END) AS long_term_captive
        FROM rescue_admissions
        WHERE centre_id = :centre_id
          $dateFilter
    ");
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $totalAdmissions = (int)($row['total_admissions'] ?? 0);
    $released = (int)($row['released'] ?? 0);
    $diedAfter48 = (int)($row['died_after_48'] ?? 0);
    $diedWithin48 = (int)($row['died_within_48'] ?? 0);
    $diedOnAdmission = (int)($row['died_on_admission'] ?? 0);
    $euthanised = (int)($row['euthanised'] ?? 0);
    $transferred = (int)($row['transferred'] ?? 0);
    $longTermCaptive = (int)($row['long_term_captive'] ?? 0);
    $eligible = max(0, $totalAdmissions - ($transferred + $longTermCaptive));
    $diedAfter48Percent = $eligible > 0 ? ($diedAfter48 / $eligible) * 100 : 0;

    return [
        'total_admissions' => $totalAdmissions,
        'released' => $released,
        'died_after_48' => $diedAfter48,
        'died_within_48' => $diedWithin48,
        'died_on_admission' => $diedOnAdmission,
        'euthanised' => $euthanised,
        'transferred' => $transferred,
        'long_term_captive' => $longTermCaptive,
        'eligible' => $eligible,
        'died_after_48_percent' => $diedAfter48Percent,
        'efficiency' => $eligible > 0 ? max(0, 100 - $diedAfter48Percent) : 0,
    ];
}
}


/* ------------------------------------------------------------
   ALL-TIME TOTALS
   ------------------------------------------------------------ */
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN disposition = 'Released' THEN 1 ELSE 0 END) AS Released,
        SUM(CASE WHEN disposition = 'Transferred Out' THEN 1 ELSE 0 END) AS Transferred,
        SUM(CASE WHEN disposition = 'Died - After 48 hours' THEN 1 ELSE 0 END) AS Diedafter48,
        SUM(CASE WHEN disposition = 'Died - Euthanised' THEN 1 ELSE 0 END) AS DiedEuth,
        SUM(CASE WHEN disposition = 'Died - On Admission' THEN 1 ELSE 0 END) AS Diedadmit,
        SUM(CASE WHEN disposition = 'Died - Within 48 hours' THEN 1 ELSE 0 END) AS Diedin48
    FROM rescue_admissions
    WHERE centre_id = :centre_id
");
$stmt->bindValue(':centre_id', $centre_id, PDO::PARAM_INT);
$stmt->execute();

if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $disptotal    = (int)$row['total'];
    $dispreleased = (int)$row['Released'];
    $disptrans    = (int)$row['Transferred'];
    $dispeuth     = (int)$row['DiedEuth'];
    $dispin48     = (int)$row['Diedin48'];
    $dispafter48  = (int)$row['Diedafter48'];
    $dispdoa      = (int)$row['Diedadmit'];

    $dispdiedtotal = $dispeuth + $dispin48 + $dispafter48 + $dispdoa;
}

/* ------------------------------------------------------------
   ACTIVE ANIMALS (MATCHES NAV QUERY)
   ------------------------------------------------------------ */
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM rescue_admissions ra
    INNER JOIN rescue_patients rp
        ON ra.patient_id = rp.patient_id
    WHERE ra.disposition = 'Held in captivity'
      AND rp.state = 'Admitted'
      AND rp.centre_id = :centre_id
");
$stmt->bindValue(':centre_id', $centre_id, PDO::PARAM_INT);
$stmt->execute();

$dispcaptive = (int)$stmt->fetchColumn();

/* ------------------------------------------------------------
   CLINICAL EFFICIENCY
   ------------------------------------------------------------ */
$clinicalAllTime = clinical_efficiency_for_period($pdo, (int)$centre_id);
$clinefficiency = (float)$clinicalAllTime['efficiency'];
$allTimeReleaseRate = $clinefficiency;

/* ------------------------------------------------------------
   DATE BOUNDARIES
   ------------------------------------------------------------ */
$currentYtdStart = date('Y-01-01 00:00:00');
$currentNow      = date('Y-m-d 23:59:59');

$lastYearYtdStart = date('Y-01-01 00:00:00', strtotime('-1 year'));
$lastYearSamePoint = date('Y-m-d 23:59:59', strtotime('-1 year'));


// CAPACITY

/* total capacity */
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(max_occupancy), 0) AS total_capacity
    FROM rescue_locations
    WHERE centre_id = :centre_id
      AND (deleted = 0 OR deleted IS NULL)
");
$stmt->bindValue(':centre_id', $centre_id, PDO::PARAM_INT);
$stmt->execute();
$totalCapacity = (int)$stmt->fetchColumn();

/* occupied spaces = EXACT SAME LOGIC AS NAV COUNT */
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM rescue_admissions ra
    INNER JOIN rescue_patients rp
        ON ra.patient_id = rp.patient_id
    WHERE ra.disposition = 'Held in captivity'
      AND rp.state = 'Admitted'
      AND rp.centre_id = :centre_id
");
$stmt->bindValue(':centre_id', $centre_id, PDO::PARAM_INT);
$stmt->execute();
$occupiedSpaces = (int)$stmt->fetchColumn();

$capacityPercent = 0;
if ($totalCapacity > 0) {
    $capacityPercent = ($occupiedSpaces / $totalCapacity) * 100;
}


/* ------------------------------------------------------------
   REMAINDER OF YOUR FILE
   (ROLLING WINDOWS, SEASONAL PREDICTOR, CLINICAL HISTORY,
   COMPARISON CALCULATIONS, ETC.)
   REMAINS EXACTLY THE SAME AS YOUR ORIGINAL FILE
------------------------------------------------------------ */


/* ------------------------------------------------------------
   YTD ADMISSIONS + DEATHS (CURRENT YEAR)
   ------------------------------------------------------------ */
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN disposition = 'Held in captivity' THEN 1 ELSE 0 END) AS Captive,
        SUM(CASE WHEN disposition = 'Transferred Out' THEN 1 ELSE 0 END) AS Transferred,
        SUM(CASE WHEN disposition = 'Died - After 48 hours' THEN 1 ELSE 0 END) AS Diedafter48,
        SUM(CASE WHEN disposition = 'Died - Euthanised' THEN 1 ELSE 0 END) AS DiedEuth,
        SUM(CASE WHEN disposition = 'Died - On Admission' THEN 1 ELSE 0 END) AS Diedadmit,
        SUM(CASE WHEN disposition = 'Died - Within 48 hours' THEN 1 ELSE 0 END) AS Diedin48
    FROM rescue_admissions
    WHERE centre_id = :centre_id
      AND admission_date BETWEEN :date_start AND :date_end
");
$stmt->bindValue(':centre_id', $centre_id, PDO::PARAM_INT);
$stmt->bindValue(':date_start', $currentYtdStart, PDO::PARAM_STR);
$stmt->bindValue(':date_end', $currentNow, PDO::PARAM_STR);
$stmt->execute();

if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $ytddisptotal   = (int)$row['total'];
    $ytddispcaptive = (int)$row['Captive'];
    $ytddisptrans   = (int)$row['Transferred'];
    $ytddispeuth    = (int)$row['DiedEuth'];
    $ytddispin48    = (int)$row['Diedin48'];
    $ytddispafter48 = (int)$row['Diedafter48'];
    $ytddispdoa     = (int)$row['Diedadmit'];

    $ytddispdiedtotal = $ytddispeuth + $ytddispin48 + $ytddispafter48 + $ytddispdoa;
}


/* ------------------------------------------------------------
   YTD RELEASES (CURRENT YEAR) - BY DISPOSITION DATE
   ------------------------------------------------------------ */
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM rescue_admissions
    WHERE centre_id = :centre_id
      AND disposition = 'Released'
      AND disposition_date IS NOT NULL
      AND disposition_date BETWEEN :date_start AND :date_end
");
$stmt->bindValue(':centre_id', $centre_id, PDO::PARAM_INT);
$stmt->bindValue(':date_start', $currentYtdStart, PDO::PARAM_STR);
$stmt->bindValue(':date_end', $currentNow, PDO::PARAM_STR);
$stmt->execute();
$ytddispreleased = (int)$stmt->fetchColumn();


/* ------------------------------------------------------------
   YTD ADMISSIONS + DEATHS (LAST YEAR SAME POINT)
   ------------------------------------------------------------ */
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN disposition = 'Held in captivity' THEN 1 ELSE 0 END) AS Captive,
        SUM(CASE WHEN disposition = 'Transferred Out' THEN 1 ELSE 0 END) AS Transferred,
        SUM(CASE WHEN disposition = 'Died - After 48 hours' THEN 1 ELSE 0 END) AS Diedafter48,
        SUM(CASE WHEN disposition = 'Died - Euthanised' THEN 1 ELSE 0 END) AS DiedEuth,
        SUM(CASE WHEN disposition = 'Died - On Admission' THEN 1 ELSE 0 END) AS Diedadmit,
        SUM(CASE WHEN disposition = 'Died - Within 48 hours' THEN 1 ELSE 0 END) AS Diedin48
    FROM rescue_admissions
    WHERE centre_id = :centre_id
      AND admission_date BETWEEN :date_start AND :date_end
");
$stmt->bindValue(':centre_id', $centre_id, PDO::PARAM_INT);
$stmt->bindValue(':date_start', $lastYearYtdStart, PDO::PARAM_STR);
$stmt->bindValue(':date_end', $lastYearSamePoint, PDO::PARAM_STR);
$stmt->execute();

$lastyearytdcaptive = 0;
$lastyearytdtrans = 0;
$lastyearytdeuth = 0;
$lastyearytdin48 = 0;
$lastyearytdafter48 = 0;
$lastyearytddoa = 0;

if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $lastyearytddisptotal = (int)$row['total'];
    $lastyearytdcaptive   = (int)$row['Captive'];
    $lastyearytdtrans     = (int)$row['Transferred'];
    $lastyearytdeuth      = (int)$row['DiedEuth'];
    $lastyearytdin48      = (int)$row['Diedin48'];
    $lastyearytdafter48   = (int)$row['Diedafter48'];
    $lastyearytddoa       = (int)$row['Diedadmit'];

    $lastyearytddispdiedtotal =
        $lastyearytdeuth +
        $lastyearytdin48 +
        $lastyearytdafter48 +
        $lastyearytddoa;
}


/* ------------------------------------------------------------
   YTD RELEASES (LAST YEAR SAME POINT) - BY DISPOSITION DATE
   ------------------------------------------------------------ */
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM rescue_admissions
    WHERE centre_id = :centre_id
      AND disposition = 'Released'
      AND disposition_date IS NOT NULL
      AND disposition_date BETWEEN :date_start AND :date_end
");
$stmt->bindValue(':centre_id', $centre_id, PDO::PARAM_INT);
$stmt->bindValue(':date_start', $lastYearYtdStart, PDO::PARAM_STR);
$stmt->bindValue(':date_end', $lastYearSamePoint, PDO::PARAM_STR);
$stmt->execute();
$lastyearytddispreleased = (int)$stmt->fetchColumn();

/* ------------------------------------------------------------
   CLINICAL EFFICIENCY HISTORY
   same YTD point for the last 5 years
   ------------------------------------------------------------ */
$clinicalHistory = [];
$clinicalEfficiencyChangePct = 0;

$currentYear = (int)date('Y');
$historyStartYear = $currentYear - 2;

for ($yr = $historyStartYear; $yr <= $currentYear; $yr++) {
    $rangeStart = $yr . '-01-01 00:00:00';
    $rangeEnd   = date('Y-m-d 23:59:59', strtotime(($yr - $currentYear) . ' year'));

    $histClinical = clinical_efficiency_for_period($pdo, (int)$centre_id, $rangeStart, $rangeEnd);
    $histEfficiency = (float)$histClinical['efficiency'];

    $clinicalHistory[$yr] = [
        'efficiency' => $histEfficiency,
        'change_pct' => 0
    ];
}

/* percentage shift vs previous year */
for ($yr = $historyStartYear + 1; $yr <= $currentYear; $yr++) {
    $prev = (float)$clinicalHistory[$yr - 1]['efficiency'];
    $curr = (float)$clinicalHistory[$yr]['efficiency'];

    if ($prev > 0) {
        $clinicalHistory[$yr]['change_pct'] = (($curr - $prev) / $prev) * 100;
    } elseif ($curr > 0) {
        $clinicalHistory[$yr]['change_pct'] = 100;
    } else {
        $clinicalHistory[$yr]['change_pct'] = 0;
    }
}

/* current card comparator */
$ytdclinefficiency = isset($clinicalHistory[$currentYear])
    ? (float)$clinicalHistory[$currentYear]['efficiency']
    : 0;

$lastyearytdclinefficiency = isset($clinicalHistory[$currentYear - 1])
    ? (float)$clinicalHistory[$currentYear - 1]['efficiency']
    : 0;

if ($lastyearytdclinefficiency > 0) {
    $clinicalEfficiencyChangePct = (($ytdclinefficiency - $lastyearytdclinefficiency) / $lastyearytdclinefficiency) * 100;
} elseif ($ytdclinefficiency > 0) {
    $clinicalEfficiencyChangePct = 100;
} else {
    $clinicalEfficiencyChangePct = 0;
}

/* ------------------------------------------------------------
   SEASONAL PRESSURE PREDICTOR
   current 7-day admissions vs average same 7-day window
   across previous 5 years
   ------------------------------------------------------------ */
$current7DayAdmissions = 0;
$seasonalAverageAdmissions = 0;
$seasonalDifferencePct = 0;
$seasonalPressureLabel = 'Normal';
$seasonalPressureClass = 'season-normal';
$seasonalPressureText = 'Close to seasonal average';
$seasonalHistoryYears = 0;

$currentWindowStart = date('Y-m-d 00:00:00', strtotime('-7 days'));
$currentWindowEnd   = date('Y-m-d 23:59:59');

$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM rescue_admissions
    WHERE centre_id = :centre_id
      AND admission_date BETWEEN :date_start AND :date_end
");
$stmt->bindValue(':centre_id', $centre_id, PDO::PARAM_INT);
$stmt->bindValue(':date_start', $currentWindowStart, PDO::PARAM_STR);
$stmt->bindValue(':date_end', $currentWindowEnd, PDO::PARAM_STR);
$stmt->execute();
$current7DayAdmissions = (int)$stmt->fetchColumn();

/* build historical same-window comparison across previous 5 years */
$seasonalTotalAdmissions = 0;
$currentYear = (int)date('Y');

for ($i = 1; $i <= 5; $i++) {
    $compareStart = date('Y-m-d 00:00:00', strtotime('-7 days -' . $i . ' year'));
    $compareEnd   = date('Y-m-d 23:59:59', strtotime('-' . $i . ' year'));

    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM rescue_admissions
        WHERE centre_id = :centre_id
          AND admission_date BETWEEN :date_start AND :date_end
    ");
    $stmt->bindValue(':centre_id', $centre_id, PDO::PARAM_INT);
    $stmt->bindValue(':date_start', $compareStart, PDO::PARAM_STR);
    $stmt->bindValue(':date_end', $compareEnd, PDO::PARAM_STR);
    $stmt->execute();

    $historicalCount = (int)$stmt->fetchColumn();
    $seasonalTotalAdmissions += $historicalCount;
    $seasonalHistoryYears++;
}

if ($seasonalHistoryYears > 0) {
    $seasonalAverageAdmissions = $seasonalTotalAdmissions / $seasonalHistoryYears;
}

if ($seasonalAverageAdmissions > 0) {
    $seasonalDifferencePct = (($current7DayAdmissions - $seasonalAverageAdmissions) / $seasonalAverageAdmissions) * 100;
} elseif ($current7DayAdmissions > 0) {
    $seasonalDifferencePct = 100;
} else {
    $seasonalDifferencePct = 0;
}

/* pressure banding */
if ($seasonalDifferencePct >= 30) {
    $seasonalPressureLabel = 'Peak';
    $seasonalPressureClass = 'season-peak';
    $seasonalPressureText = 'Well above seasonal average';
} elseif ($seasonalDifferencePct >= 15) {
    $seasonalPressureLabel = 'High';
    $seasonalPressureClass = 'season-high';
    $seasonalPressureText = 'Above seasonal average';
} elseif ($seasonalDifferencePct <= -15) {
    $seasonalPressureLabel = 'Low';
    $seasonalPressureClass = 'season-low';
    $seasonalPressureText = 'Below seasonal average';
} else {
    $seasonalPressureLabel = 'Normal';
    $seasonalPressureClass = 'season-normal';
    $seasonalPressureText = 'Close to seasonal average';
}

/* ------------------------------------------------------------
   ROLLING WINDOWS
   ------------------------------------------------------------ */
$windows = [
    '1d'  => 1,
    '7d'  => 7,
    '31d' => 31,
];

/* admissions */
$admissionStmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM rescue_admissions
    WHERE centre_id = :centre_id
      AND admission_date BETWEEN :date_start AND :date_end
");

/* releases */
$releaseStmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM rescue_admissions
    WHERE centre_id = :centre_id
      AND disposition = 'Released'
      AND disposition_date IS NOT NULL
      AND disposition_date BETWEEN :date_start AND :date_end
");

/* deaths */
$deathStmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM rescue_admissions
    WHERE centre_id = :centre_id
      AND disposition IN (
          'Died - After 48 hours',
          'Died - Euthanised',
          'Died - On Admission',
          'Died - Within 48 hours'
      )
      AND admission_date BETWEEN :date_start AND :date_end
");

foreach ($windows as $key => $days) {
    $currentStart = date('Y-m-d 00:00:00', strtotime('-' . $days . ' days'));
    $currentEnd   = date('Y-m-d 23:59:59');

    $previousStart = date('Y-m-d 00:00:00', strtotime('-' . $days . ' days -1 year'));
    $previousEnd   = date('Y-m-d 23:59:59', strtotime('-1 year'));

    /* admissions */
    $admissionStmt->bindValue(':centre_id', $centre_id, PDO::PARAM_INT);
    $admissionStmt->bindValue(':date_start', $currentStart, PDO::PARAM_STR);
    $admissionStmt->bindValue(':date_end', $currentEnd, PDO::PARAM_STR);
    $admissionStmt->execute();
    $admissionComparisons[$key]['current'] = (int)$admissionStmt->fetchColumn();

    $admissionStmt->bindValue(':centre_id', $centre_id, PDO::PARAM_INT);
    $admissionStmt->bindValue(':date_start', $previousStart, PDO::PARAM_STR);
    $admissionStmt->bindValue(':date_end', $previousEnd, PDO::PARAM_STR);
    $admissionStmt->execute();
    $admissionComparisons[$key]['previous'] = (int)$admissionStmt->fetchColumn();

    if ($admissionComparisons[$key]['previous'] > 0) {
        $admissionComparisons[$key]['pct'] =
            (($admissionComparisons[$key]['current'] - $admissionComparisons[$key]['previous']) / $admissionComparisons[$key]['previous']) * 100;
    } elseif ($admissionComparisons[$key]['current'] > 0) {
        $admissionComparisons[$key]['pct'] = 100;
    }

    /* releases */
    $releaseStmt->bindValue(':centre_id', $centre_id, PDO::PARAM_INT);
    $releaseStmt->bindValue(':date_start', $currentStart, PDO::PARAM_STR);
    $releaseStmt->bindValue(':date_end', $currentEnd, PDO::PARAM_STR);
    $releaseStmt->execute();
    $releaseComparisons[$key]['current'] = (int)$releaseStmt->fetchColumn();

    $releaseStmt->bindValue(':centre_id', $centre_id, PDO::PARAM_INT);
    $releaseStmt->bindValue(':date_start', $previousStart, PDO::PARAM_STR);
    $releaseStmt->bindValue(':date_end', $previousEnd, PDO::PARAM_STR);
    $releaseStmt->execute();
    $releaseComparisons[$key]['previous'] = (int)$releaseStmt->fetchColumn();

    if ($releaseComparisons[$key]['previous'] > 0) {
        $releaseComparisons[$key]['pct'] =
            (($releaseComparisons[$key]['current'] - $releaseComparisons[$key]['previous']) / $releaseComparisons[$key]['previous']) * 100;
    } elseif ($releaseComparisons[$key]['current'] > 0) {
        $releaseComparisons[$key]['pct'] = 100;
    }

    /* deaths */
    $deathStmt->bindValue(':centre_id', $centre_id, PDO::PARAM_INT);
    $deathStmt->bindValue(':date_start', $currentStart, PDO::PARAM_STR);
    $deathStmt->bindValue(':date_end', $currentEnd, PDO::PARAM_STR);
    $deathStmt->execute();
    $deathComparisons[$key]['current'] = (int)$deathStmt->fetchColumn();

    $deathStmt->bindValue(':centre_id', $centre_id, PDO::PARAM_INT);
    $deathStmt->bindValue(':date_start', $previousStart, PDO::PARAM_STR);
    $deathStmt->bindValue(':date_end', $previousEnd, PDO::PARAM_STR);
    $deathStmt->execute();
    $deathComparisons[$key]['previous'] = (int)$deathStmt->fetchColumn();

    if ($deathComparisons[$key]['previous'] > 0) {
        $deathComparisons[$key]['pct'] =
            (($deathComparisons[$key]['current'] - $deathComparisons[$key]['previous']) / $deathComparisons[$key]['previous']) * 100;
    } elseif ($deathComparisons[$key]['current'] > 0) {
        $deathComparisons[$key]['pct'] = 100;
    }
}


/* ------------------------------------------------------------
   YTD COMPARISONS FOR MICRO LINE
   ------------------------------------------------------------ */
$admissionComparisons['ytd']['current'] = (int)$ytddisptotal;
$admissionComparisons['ytd']['previous'] = (int)$lastyearytddisptotal;
if ($lastyearytddisptotal > 0) {
    $admissionComparisons['ytd']['pct'] = (($ytddisptotal - $lastyearytddisptotal) / $lastyearytddisptotal) * 100;
} elseif ($ytddisptotal > 0) {
    $admissionComparisons['ytd']['pct'] = 100;
}

$releaseComparisons['ytd']['current'] = (int)$ytddispreleased;
$releaseComparisons['ytd']['previous'] = (int)$lastyearytddispreleased;
if ($lastyearytddispreleased > 0) {
    $releaseComparisons['ytd']['pct'] = (($ytddispreleased - $lastyearytddispreleased) / $lastyearytddispreleased) * 100;
} elseif ($ytddispreleased > 0) {
    $releaseComparisons['ytd']['pct'] = 100;
}

$deathComparisons['ytd']['current'] = (int)$ytddispdiedtotal;
$deathComparisons['ytd']['previous'] = (int)$lastyearytddispdiedtotal;
if ($lastyearytddispdiedtotal > 0) {
    $deathComparisons['ytd']['pct'] = (($ytddispdiedtotal - $lastyearytddispdiedtotal) / $lastyearytddispdiedtotal) * 100;
} elseif ($ytddispdiedtotal > 0) {
    $deathComparisons['ytd']['pct'] = 100;
}


/* ------------------------------------------------------------
   SUPPORT LINE DELTAS
   ------------------------------------------------------------ */
$admissionsYtdDiff = $ytddisptotal - $lastyearytddisptotal;
$releasesYtdDiff   = $ytddispreleased - $lastyearytddispreleased;
$deathsYtdDiff     = $ytddispdiedtotal - $lastyearytddispdiedtotal;

$clinicalEfficiencyPointDiff = $ytdclinefficiency - $lastyearytdclinefficiency;
?>
