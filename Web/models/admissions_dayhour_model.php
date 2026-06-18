<?php
// models/admissions_dayhour_model.php

$admissionsHeatmapDays = [
    $lang['DAY_MON'] ?? 'Mon', $lang['DAY_TUE'] ?? 'Tue', $lang['DAY_WED'] ?? 'Wed',
    $lang['DAY_THU'] ?? 'Thu', $lang['DAY_FRI'] ?? 'Fri', $lang['DAY_SAT'] ?? 'Sat',
    $lang['DAY_SUN'] ?? 'Sun',
];
$admissionsHeatmapHours = [];

for ($h = 0; $h < 24; $h++) {
    $admissionsHeatmapHours[] = str_pad((string) $h, 2, '0', STR_PAD_LEFT);
}

$endYear   = (int) date('Y');
$startYear = $endYear - 4;

// Build empty 7 x 24 matrix
$admissionsHeatmapData = [];
for ($d = 0; $d < 7; $d++) {
    $admissionsHeatmapData[$d] = array_fill(0, 24, 0);
}

$sql = "
    SELECT
        WEEKDAY(admission_date) AS wd,
        HOUR(admission_date)    AS hr,
        COUNT(*)                AS cnt
    FROM rescue_admissions
    WHERE centre_id = :centre_id
      AND YEAR(admission_date) BETWEEN :startYear AND :endYear
    GROUP BY WEEKDAY(admission_date), HOUR(admission_date)
    ORDER BY WEEKDAY(admission_date), HOUR(admission_date)
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':centre_id', $centre_id, PDO::PARAM_INT);
$stmt->bindValue(':startYear', $startYear, PDO::PARAM_INT);
$stmt->bindValue(':endYear', $endYear, PDO::PARAM_INT);
$stmt->execute();

$admissionsHeatmapMax = 0;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $wd  = (int) $row['wd'];
    $hr  = (int) $row['hr'];
    $cnt = (int) $row['cnt'];

    if ($wd >= 0 && $wd <= 6 && $hr >= 0 && $hr <= 23) {
        $admissionsHeatmapData[$wd][$hr] = $cnt;

        if ($cnt > $admissionsHeatmapMax) {
            $admissionsHeatmapMax = $cnt;
        }
    }
}
