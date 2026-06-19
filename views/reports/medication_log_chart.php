<?php
if (!defined('APP_LOADED')) exit;

report_render_chart_grid([
    [
        'title' => 'Most used medicines',
        'counts' => report_count_by($rows, ['Medication']),
        'type' => 'bar',
    ],
    [
        'title' => 'Medication by species',
        'counts' => report_count_by($rows, ['Animal Species']),
        'type' => 'doughnut',
    ],
    [
        'title' => 'Administrations by staff member',
        'counts' => report_count_by($rows, ['Given By']),
        'type' => 'bar',
    ],
]);
