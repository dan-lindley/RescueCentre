<?php
if (!defined('APP_LOADED')) exit;

/* Print/PDF chart renderer. Include BEFORE report_helpers.php in print mode. */

if (!function_exists('report_render_print_tools')) {
    function report_render_print_tools(): void {
        static $rendered = false;
        if ($rendered) return;
        $rendered = true;
        ?>
        <link rel="stylesheet" href="core/css/report_print.css">
        <style>
            html,body{background:#fff !important;color:#111827 !important;}
            .report-toolbar{display:flex;justify-content:flex-end;gap:8px;margin-bottom:12px;}
            .report-chart-grid{align-items:stretch;}
            .report-chart-card{background:#fff !important;color:#111827 !important;border:1px solid #9ca3af !important;box-shadow:none !important;min-width:0;break-inside:avoid;page-break-inside:avoid;}
            .report-chart-card h4{color:#111827 !important;}
            .report-chart-wrap{position:relative;min-height:240px;height:240px;background:#fff !important;}
            .report-chart{width:100% !important;height:100% !important;background:#fff !important;}
            @media print{
                .header,header.app-header,aside,nav,.sidebar,.report-toolbar,.rc-tabs,.xform-actions,button,.btn{display:none !important;}
                body,main,.content,.rc-panel,.rc-card{background:#fff !important;color:#111827 !important;box-shadow:none !important;}
                .rc-muted,small,.text-muted,p,li,td,th,span,label{color:#111827 !important;}
                .report-chart-grid{grid-template-columns:repeat(2,minmax(0,1fr)) !important;gap:6mm !important;align-items:start !important;}
                .report-chart-card{padding:7mm !important;border-radius:6px !important;}
                .report-chart-card h4{margin:0 0 2mm !important;font-size:10pt !important;line-height:1.15 !important;}
                .report-chart-card p{margin:0 0 3mm !important;font-size:7.5pt !important;line-height:1.2 !important;}
                .report-chart-wrap{min-height:50mm !important;height:50mm !important;}
            }
        </style>
        <div class="report-toolbar">
            <button type="button" class="btn grey" onclick="window.print()">Print / Save PDF</button>
        </div>
        <script>
        (function(){
            function parseJson(value, fallback){try{return JSON.parse(value || '');}catch(e){return fallback;}}
            var PRINT_TEXT = '#000000';
            var PRINT_GRID = 'rgba(0,0,0,.35)';
            var PRINT_PAPER = '#ffffff';
            var printPaperPlugin = {
                id:'reportPrintPaperPlugin',
                beforeDraw:function(chart){
                    var ctx = chart.ctx;
                    ctx.save();
                    ctx.globalCompositeOperation = 'destination-over';
                    ctx.fillStyle = PRINT_PAPER;
                    ctx.fillRect(0, 0, chart.width, chart.height);
                    ctx.restore();
                }
            };
            function applyDefaults(){
                if (typeof Chart === 'undefined' || !Chart.defaults) return;
                Chart.defaults.color = PRINT_TEXT;
                Chart.defaults.borderColor = PRINT_GRID;
                if (Chart.defaults.global) {
                    Chart.defaults.global.defaultFontColor = PRINT_TEXT;
                    Chart.defaults.global.defaultFontFamily = "'Inter', Arial, sans-serif";
                }
                if (Chart.defaults.plugins && Chart.defaults.plugins.legend) {
                    Chart.defaults.plugins.legend.labels = Chart.defaults.plugins.legend.labels || {};
                    Chart.defaults.plugins.legend.labels.color = PRINT_TEXT;
                }
            }
            function makeOptions(type){
                var isDoughnut = type === 'doughnut' || type === 'pie';
                var options = {
                    responsive:true,
                    maintainAspectRatio:false,
                    animation:false,
                    color:PRINT_TEXT,
                    backgroundColor:PRINT_PAPER,
                    plugins:{
                        legend:{
                            display:isDoughnut,
                            position:'bottom',
                            labels:{color:PRINT_TEXT,font:{weight:'800',size:13}}
                        },
                        tooltip:{enabled:false}
                    }
                };
                if (!isDoughnut) {
                    options.scales = {
                        x:{ticks:{color:PRINT_TEXT,font:{weight:'800',size:12}},title:{color:PRINT_TEXT},grid:{color:PRINT_GRID},border:{color:PRINT_TEXT}},
                        y:{beginAtZero:true,ticks:{color:PRINT_TEXT,precision:0,font:{weight:'800',size:12}},title:{color:PRINT_TEXT},grid:{color:PRINT_GRID},border:{color:PRINT_TEXT}},
                        xAxes:[{ticks:{fontColor:PRINT_TEXT,fontStyle:'bold'},gridLines:{color:PRINT_GRID,zeroLineColor:PRINT_GRID}}],
                        yAxes:[{ticks:{beginAtZero:true,precision:0,fontColor:PRINT_TEXT,fontStyle:'bold'},gridLines:{color:PRINT_GRID,zeroLineColor:PRINT_GRID}}]
                    };
                }
                return options;
            }
            function renderCharts(){
                if (typeof Chart === 'undefined') return;
                var palette = ['#1d4ed8','#15803d','#b45309','#b91c1c','#6d28d9','#0e7490','#be185d','#3730a3','#4d7c0f','#c2410c'];
                applyDefaults();
                if (Chart.register) { try { Chart.register(printPaperPlugin); } catch(e) {} }
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
                                borderColor:isDoughnut ? '#ffffff' : palette[0],
                                borderWidth:isDoughnut ? 2 : 1,
                                borderRadius:type === 'bar' ? 8 : 0
                            }]},
                            options:makeOptions(type),
                            plugins:[printPaperPlugin]
                        });
                    } catch(e) {
                        canvas.dataset.chartReady = '';
                        if (window.console) console.error('Print report chart failed', canvas.id, e);
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
