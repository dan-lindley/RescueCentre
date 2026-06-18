<?php
if (!defined('APP_LOADED')) exit;

report_render_chart_grid([
    [
        'title' => 'Admissions by species',
        'counts' => report_count_by($rows, ['animal_species', 'Animal Species']),
        'type' => 'bar',
    ],
    [
        'title' => 'Disposition mix',
        'counts' => report_count_by($rows, ['disposition', 'Disposition (Text)', 'Disposition']),
        'type' => 'doughnut',
    ],
    [
        'title' => 'Presenting complaint themes',
        'counts' => report_count_by($rows, ['presenting_complaint', 'Presenting Complaint']),
        'type' => 'bar',
        'limit' => 6,
    ],
]);
