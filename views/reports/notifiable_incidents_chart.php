<?php
if (!defined('APP_LOADED')) exit;

report_render_chart_grid([
    [
        'title' => 'Incident categories',
        'counts' => report_count_by($rows, ['Notifiable Category (Derived)']),
        'type' => 'bar',
    ],
    [
        'title' => 'Species involved',
        'counts' => report_count_by($rows, ['Animal Species']),
        'type' => 'doughnut',
    ],
    [
        'title' => 'Disposition of flagged records',
        'counts' => report_count_by($rows, ['Disposition']),
        'type' => 'bar',
        'limit' => 6,
    ],
]);
