<?php
if (!defined('APP_LOADED')) exit;

$speciesTotals = [];
foreach ($rows as $row) {
    $speciesTotals[report_value($row, ['Animal Species'], 'Unknown species')] = (int)($row['Admitted Total'] ?? 0);
}
arsort($speciesTotals);

report_render_chart_grid([
    [
        'title' => 'Largest species groups',
        'counts' => $speciesTotals,
        'type' => 'bar',
    ],
    [
        'title' => 'Outcome totals',
        'counts' => [
            'Released' => report_sum_column($rows, 'Released (R)'),
            'Transferred' => report_sum_column($rows, 'Transferred (T)'),
            'Euthanised' => report_sum_column($rows, 'Euthanised (E)'),
            'Died after intake' => report_sum_column($rows, 'Died After Intake (D)'),
            'Dead on admission' => report_sum_column($rows, 'Dead On Admission (DOA)'),
            'Held in captivity' => report_sum_column($rows, 'Held in Captivity (IC)'),
        ],
        'type' => 'doughnut',
    ],
]);
