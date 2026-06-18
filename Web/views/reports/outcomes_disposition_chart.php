<?php
if (!defined('APP_LOADED')) exit;

report_render_chart_grid([
    [
        'title' => 'Outcome codes',
        'counts' => report_count_by($rows, ['Universal Shortcode']),
        'type' => 'doughnut',
    ],
    [
        'title' => 'Outcomes by species',
        'counts' => report_count_by($rows, ['Animal Species']),
        'type' => 'bar',
    ],
    [
        'title' => 'Disposition text',
        'counts' => report_count_by($rows, ['Disposition (Text)']),
        'type' => 'bar',
        'limit' => 6,
    ],
]);
