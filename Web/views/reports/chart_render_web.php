<?php
if (!defined('APP_LOADED')) exit;

/* Web chart renderer. Include BEFORE report_helpers.php. */

if (!function_exists('report_render_print_tools')) {
    function report_render_print_tools(): void {
        static $rendered = false;
        if ($rendered) return;
        $rendered = true;
        ?>
        <style>
            .report-toolbar{display:flex;justify-content:flex-end;gap:8px;margin-bottom:12px;}
            .report-chart-grid{align-items:stretch;}
            .report-chart-card{min-width:0;}
            .report-chart-wrap{position:relative;min-height:280px;height:280px;}
            .report-chart{width:100% !important;height:100% !important;}
        </style>
        <script>
        (function(){
            function parseJson(value, fallback){try{return JSON.parse(value || '');}catch(e){return fallback;}}
            function isDarkMode(){return document.documentElement.getAttribute('data-theme') === 'dark';}
            function chartTheme(){
                var dark = isDarkMode();
                return {
                    text: dark ? '#e5e7eb' : '#111827',
                    grid: dark ? 'rgba(229,231,235,.18)' : 'rgba(17,24,39,.14)'
                };
            }
            function applyDefaults(t){
                if (typeof Chart === 'undefined' || !Chart.defaults) return;
                Chart.defaults.color = t.text;
                Chart.defaults.borderColor = t.grid;
                if (Chart.defaults.global) {
                    Chart.defaults.global.defaultFontColor = t.text;
                    Chart.defaults.global.defaultFontFamily = "'Inter', Arial, sans-serif";
                }
                if (Chart.defaults.plugins && Chart.defaults.plugins.legend) {
                    Chart.defaults.plugins.legend.labels = Chart.defaults.plugins.legend.labels || {};
                    Chart.defaults.plugins.legend.labels.color = t.text;
                }
            }
            function makeOptions(type, t){
                var isDoughnut = type === 'doughnut' || type === 'pie';
                var options = {
                    responsive:true,
                    maintainAspectRatio:false,
                    color:t.text,
                    plugins:{
                        legend:{
                            display:isDoughnut,
                            position:'bottom',
                            labels:{color:t.text,font:{weight:'700',size:12}}
                        },
                        tooltip:{mode:'index',intersect:false}
                    }
                };
                if (!isDoughnut) {
                    options.scales = {
                        x:{ticks:{color:t.text,font:{weight:'700'}},grid:{color:t.grid},border:{color:t.grid}},
                        y:{beginAtZero:true,ticks:{color:t.text,precision:0,font:{weight:'700'}},grid:{color:t.grid},border:{color:t.grid}},
                        xAxes:[{ticks:{fontColor:t.text,fontStyle:'bold'},gridLines:{color:t.grid,zeroLineColor:t.grid}}],
                        yAxes:[{ticks:{beginAtZero:true,precision:0,fontColor:t.text,fontStyle:'bold'},gridLines:{color:t.grid,zeroLineColor:t.grid}}]
                    };
                }
                return options;
            }
            function renderCharts(){
                if (typeof Chart === 'undefined') return;
                var t = chartTheme();
                var palette = ['#2563eb','#16a34a','#d97706','#dc2626','#7c3aed','#0891b2','#be185d','#4f46e5','#65a30d','#ea580c'];
                applyDefaults(t);
                document.querySelectorAll('canvas.report-chart').forEach(function(canvas){
                    try {
                        if (canvas.dataset.chartReady === '1') return;
                        var labels = parseJson(canvas.dataset.labels, []);
                        var values = parseJson(canvas.dataset.values, []).map(Number);
                        var type = canvas.dataset.chartType || 'bar';
                        var isDoughnut = type === 'doughnut' || type === 'pie';
                        canvas.dataset.chartReady = '1';
                        new Chart(canvas, {
                            type:type,
                            data:{labels:labels,datasets:[{
                                data:values,
                                backgroundColor:isDoughnut ? palette : palette[0],
                                borderColor:isDoughnut ? (isDarkMode() ? '#162527' : '#ffffff') : palette[0],
                                borderWidth:isDoughnut ? 2 : 1,
                                borderRadius:type === 'bar' ? 8 : 0
                            }]},
                            options:makeOptions(type, t)
                        });
                    } catch(e) {
                        canvas.dataset.chartReady = '';
                        if (window.console) console.error('Report chart failed', canvas.id, e);
                    }
                });
            }
            document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', renderCharts) : renderCharts();
        }());
        </script>
        <?php
    }
}

if (!function_exists('report_render_chart')) {
    function report_render_chart(string $title, array $counts, string $type = 'bar', int $limit = 8): void {
        if (empty($counts)) return;
        arsort($counts);
        $counts = array_slice($counts, 0, $limit, true);
        $chartId = report_chart_id('report_chart');
        echo '<div class="rc-card report-chart-card">';
        echo '<h4>' . report_h($title) . '</h4>';
        echo '<div class="report-chart-wrap">';
        echo '<canvas id="' . report_h($chartId) . '" class="report-chart" data-chart-type="' . report_h($type) . '" data-labels="' . report_h(json_encode(array_keys($counts))) . '" data-values="' . report_h(json_encode(array_values($counts))) . '"></canvas>';
        echo '</div></div>';
    }
}

if (!function_exists('report_render_chart_grid')) {
    function report_render_chart_grid(array $charts): void {
        echo '<div class="rc-card-grid report-chart-grid">';
        foreach ($charts as $chart) {
            report_render_chart((string)($chart['title'] ?? ''), (array)($chart['counts'] ?? []), (string)($chart['type'] ?? 'bar'), (int)($chart['limit'] ?? 8));
        }
        echo '</div>';
    }
}
