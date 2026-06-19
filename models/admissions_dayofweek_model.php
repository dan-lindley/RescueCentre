<?php
// models/admissions_dayofweek_model.php

$admissionsDayLabels = [
    $lang['DAY_MON'] ?? 'Mon', $lang['DAY_TUE'] ?? 'Tue', $lang['DAY_WED'] ?? 'Wed',
    $lang['DAY_THU'] ?? 'Thu', $lang['DAY_FRI'] ?? 'Fri', $lang['DAY_SAT'] ?? 'Sat',
    $lang['DAY_SUN'] ?? 'Sun',
];

$endYear   = (int) date('Y');
$startYear = $endYear - 4;

$admissionsDayPalette = ["#3cba9f", "#e7e42bff", "#14d814ff", "#ff7f50", "#7b68ee"];

// Build empty structure: data[year][weekday] = 0
// WEEKDAY() in MariaDB/MySQL = Mon(0) ... Sun(6)
$admissionsDayDataByYear = [];
for ($y = $startYear; $y <= $endYear; $y++) {
    $admissionsDayDataByYear[$y] = array_fill(0, 7, 0);
}

$sql = "
    SELECT
        YEAR(admission_date)    AS yr,
        WEEKDAY(admission_date) AS wd,
        COUNT(*)                AS cnt
    FROM rescue_admissions
    WHERE centre_id = :centre_id
      AND YEAR(admission_date) BETWEEN :startYear AND :endYear
    GROUP BY YEAR(admission_date), WEEKDAY(admission_date)
    ORDER BY YEAR(admission_date), WEEKDAY(admission_date)
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':centre_id', $centre_id, PDO::PARAM_INT);
$stmt->bindValue(':startYear', $startYear, PDO::PARAM_INT);
$stmt->bindValue(':endYear', $endYear, PDO::PARAM_INT);
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $yr  = (int) $row['yr'];
    $wd  = (int) $row['wd'];
    $cnt = (int) $row['cnt'];

    if (isset($admissionsDayDataByYear[$yr]) && $wd >= 0 && $wd <= 6) {
        $admissionsDayDataByYear[$yr][$wd] = $cnt;
    }
}

$admissionsDayDatasets = [];
$colourIdx = 0;

for ($y = $startYear; $y <= $endYear; $y++) {
    $colour = $admissionsDayPalette[$colourIdx % count($admissionsDayPalette)];

    $admissionsDayDatasets[] = [
        'label' => (string) $y,
        'data' => array_values($admissionsDayDataByYear[$y]),
        'borderColor' => $colour,
        'backgroundColor' => $colour,
        'fill' => false,
        'tension' => 0.3,
        'pointRadius' => 3,
        'pointHoverRadius' => 5,
        'borderWidth' => 2
    ];

    $colourIdx++;
}
