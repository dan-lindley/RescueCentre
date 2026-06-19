<?php
if (!defined('APP_LOADED')) exit;

report_render_chart_grid([
    [
        'title' => 'Care event types',
        'counts' => report_count_by($rows, ['Event Type']),
        'type' => 'doughnut',
    ],
    [
        'title' => 'Care activity by species',
        'counts' => report_count_by($rows, ['Animal Species']),
        'type' => 'bar',
    ],
    [
        'title' => 'Recorded by',
        'counts' => report_count_by($rows, ['Recorded By']),
        'type' => 'bar',
    ],
]);
