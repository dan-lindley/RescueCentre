<?php
// models/admissions_hourly_model.php

$admissionsHourLabels = [];
for ($h = 0; $h < 24; $h++) {
    $admissionsHourLabels[] = str_pad((string) $h, 2, '0', STR_PAD_LEFT) . ':00';
}

$endYear   = (int) date('Y');
$startYear = $endYear - 4;

$admissionsHourPalette = ["#3cba9f", "#e7e42bff", "#14d814ff", "#ff7f50", "#7b68ee"];

// Build empty structure: data[year][hour] = 0
$admissionsHourDataByYear = [];
for ($y = $startYear; $y <= $endYear; $y++) {
    $admissionsHourDataByYear[$y] = array_fill(0, 24, 0);
}

$sql = "
    SELECT
        YEAR(admission_date) AS yr,
        HOUR(admission_date) AS hr,
        COUNT(*)             AS cnt
    FROM rescue_admissions
    WHERE centre_id = :centre_id
      AND YEAR(admission_date) BETWEEN :startYear AND :endYear
    GROUP BY YEAR(admission_date), HOUR(admission_date)
    ORDER BY YEAR(admission_date), HOUR(admission_date)
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':centre_id', $centre_id, PDO::PARAM_INT);
$stmt->bindValue(':startYear', $startYear, PDO::PARAM_INT);
$stmt->bindValue(':endYear', $endYear, PDO::PARAM_INT);
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $yr  = (int) $row['yr'];
    $hr  = (int) $row['hr'];
    $cnt = (int) $row['cnt'];

    if (isset($admissionsHourDataByYear[$yr]) && $hr >= 0 && $hr <= 23) {
        $admissionsHourDataByYear[$yr][$hr] = $cnt;
    }
}

$admissionsHourDatasets = [];
$colourIdx = 0;

for ($y = $startYear; $y <= $endYear; $y++) {
    $colour = $admissionsHourPalette[$colourIdx % count($admissionsHourPalette)];

    $admissionsHourDatasets[] = [
        'label' => (string) $y,
        'data' => array_values($admissionsHourDataByYear[$y]),
        'borderColor' => $colour,
        'backgroundColor' => $colour,
        'fill' => false,
        'tension' => 0.3,
        'pointRadius' => 2,
        'pointHoverRadius' => 4,
        'borderWidth' => 2
    ];

    $colourIdx++;
}