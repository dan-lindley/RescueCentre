<?php
if (!defined('APP_LOADED')) exit;


if (!function_exists('report_is_print_mode')) {
    function report_is_print_mode(): bool {
        if (isset($_GET['print']) && (string)$_GET['print'] === '1') {
            return true;
        }
        $mode = strtolower((string)($_GET['view'] ?? ($_GET['mode'] ?? 'web')));
        return in_array($mode, ['print', 'pdf'], true);
    }
}

if (!function_exists('report_h')) {
    function report_h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('report_value')) {
    function report_value(array $row, array $keys, $default = '') {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') return $row[$key];
        }
        return $default;
    }
}

if (!function_exists('report_run_module')) {
    function report_run_module(PDO $pdo, array $module, int $centre_id, string $from_date, string $to_date): array {
        $query_path = (string)($module['query_path'] ?? '');
        if ($query_path === '' || strpos($query_path, '..') !== false || strpos($query_path, 'reporting/') !== 0) {
            throw new RuntimeException('Invalid query path.');
        }
        $sqlFile = __DIR__ . '/../../models/' . $query_path;
        if (!is_file($sqlFile)) throw new RuntimeException('SQL file not found: ' . $query_path);
        $sql = file_get_contents($sqlFile);
        if ($sql === false || trim($sql) === '') throw new RuntimeException('SQL file is empty: ' . $query_path);
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':centre_id' => $centre_id, ':from_date' => $from_date, ':to_date' => $to_date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('report_count_by')) {
    function report_count_by(array $rows, array $keys, string $fallback = 'Not recorded'): array {
        $counts = [];
        foreach ($rows as $row) {
            $label = trim((string)report_value($row, $keys, $fallback));
            if ($label === '') $label = $fallback;
            $counts[$label] = ($counts[$label] ?? 0) + 1;
        }
        arsort($counts);
        return $counts;
    }
}

if (!function_exists('report_sum_column')) {
    function report_sum_column(array $rows, string $key): int {
        $sum = 0; foreach ($rows as $row) $sum += (int)($row[$key] ?? 0); return $sum;
    }
}

if (!function_exists('report_average_column')) {
    function report_average_column(array $rows, string $key): ?float {
        $total = 0; $count = 0;
        foreach ($rows as $row) if (isset($row[$key]) && is_numeric($row[$key])) { $total += (float)$row[$key]; $count++; }
        return $count ? round($total / $count, 1) : null;
    }
}

if (!function_exists('report_chart_id')) {
    function report_chart_id(string $prefix = 'report_chart'): string {
        static $i = 0; $i++; return preg_replace('/[^a-z0-9_]+/i', '_', $prefix) . '_' . $i;
    }
}

if (!function_exists('report_pct')) {
    function report_pct($part, $total, int $dp = 1): float { return $total > 0 ? round(((float)$part / (float)$total) * 100, $dp) : 0.0; }
}

if (!function_exists('report_date_key')) {
    function report_date_key($value, string $bucket = 'week'): ?string {
        if (!$value) return null;
        try { $dt = new DateTime((string)$value); } catch (Throwable $e) { return null; }
        if ($bucket === 'month') return $dt->format('Y-m');
        if ($bucket === 'day') return $dt->format('Y-m-d');
        $dt->modify('monday this week');
        return $dt->format('Y-m-d');
    }
}

if (!function_exists('report_time_series')) {
    function report_time_series(array $rows, array $dateKeys, string $bucket = 'week'): array {
        $series = [];
        foreach ($rows as $row) {
            $date = report_value($row, $dateKeys, null);
            $key = report_date_key($date, $bucket);
            if ($key) $series[$key] = ($series[$key] ?? 0) + 1;
        }
        ksort($series);
        return $series;
    }
}

if (!function_exists('report_top_map')) {
    function report_top_map(array $map, int $limit = 10): array { arsort($map); return array_slice($map, 0, $limit, true); }
}

if (!function_exists('report_patient_intensity')) {
    function report_patient_intensity(array $rows, string $labelKey = 'Patient Name'): array {
        $out = [];
        foreach ($rows as $row) {
            $pid = (string)report_value($row, ['Patient ID', 'patient_id'], '');
            $name = trim((string)report_value($row, [$labelKey, 'patient_name', 'patient_name'], ''));
            $species = trim((string)report_value($row, ['Animal Species', 'animal_species'], ''));
            $label = trim(($name !== '' ? $name : 'Patient ' . $pid) . ($species !== '' ? ' · ' . $species : ''));
            if ($pid === '' && $label === '') continue;
            $out[$label] = ($out[$label] ?? 0) + 1;
        }
        return report_top_map($out, 12);
    }
}

if (!function_exists('report_age_buckets')) {
    function report_age_buckets(array $rows): array {
        $buckets = ['0–7 days' => 0, '8–14 days' => 0, '15–30 days' => 0, '31+ days' => 0];
        foreach ($rows as $row) {
            $start = report_value($row, ['admission_date', 'Admission Date', 'Admission Date (Start)'], null);
            $end = report_value($row, ['disposition_date', 'Disposition Date', 'Disposition Date (End)'], null) ?: date('Y-m-d');
            if (!$start) continue;
            try { $days = (new DateTime((string)$start))->diff(new DateTime((string)$end))->days; } catch (Throwable $e) { continue; }
            if ($days <= 7) $buckets['0–7 days']++; elseif ($days <= 14) $buckets['8–14 days']++; elseif ($days <= 30) $buckets['15–30 days']++; else $buckets['31+ days']++;
        }
        return $buckets;
    }
}

if (!function_exists('report_outcome_family')) {
    function report_outcome_family($code, $disposition = ''): string {
        $code = strtoupper(trim((string)$code));
        if ($code === 'R') return 'Released';
        if ($code === 'T') return 'Transferred';
        if ($code === 'E') return 'Euthanised';
        if ($code === 'D') return 'Died';
        if ($code === 'DOA') return 'Dead on arrival';
        if ($code === 'IC' || $code === 'PC') return 'Captive care';
        if (trim((string)$disposition) === '') return 'Open / pending';
        return 'Unmapped';
    }
}

if (!function_exists('report_render_print_tools')) {
    function report_render_print_tools(): void {
        static $done = false;
        if ($done) return;
        $done = true;

        $is_print = function_exists('report_is_print_mode') && report_is_print_mode();

        $print_url = '#';
        try {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $base = ($host !== '') ? ($scheme . '://' . $host . $uri) : $uri;
            $parts = parse_url($base);
            $query = [];
            if (!empty($parts['query'])) parse_str($parts['query'], $query);
            $query['view'] = 'print';
            unset($query['mode']);
            $path = ($parts['scheme'] ?? '') !== ''
                ? (($parts['scheme'] ?? $scheme) . '://' . ($parts['host'] ?? $host) . ($parts['path'] ?? ''))
                : ($parts['path'] ?? $uri);
            $print_url = $path . '?' . http_build_query($query);
        } catch (Throwable $e) {
            $print_url = '?view=print';
        }
        ?>
        <?php if ($is_print): ?>
            <link rel="stylesheet" href="core/css/report_print.css">
        <?php endif; ?>
        <style>
            .report-toolbar{
                display:flex;
                justify-content:flex-end;
                gap:8px;
                margin:0 0 14px;
            }

            .report-summary-grid{
                display:grid;
                grid-template-columns:repeat(3,minmax(0,1fr));
                gap:12px;
                margin:0 0 16px;
            }

            .report-summary-card{
                display:flex;
                flex-direction:column;
                gap:6px;
                min-width:0;
                padding:14px 15px;
                border:1px solid var(--rc-border, #d1d5db);
                border-radius:14px;
                background:var(--rc-surface, #ffffff);
                color:var(--rc-text, #111827);
                box-shadow:var(--rc-shadow, 0 2px 10px rgba(15,23,42,.04));
            }

            .report-summary-card::before{
                content:"";
                position:absolute;
                top:0;
                left:0;
                right:0;
                display:block;
                width:100%;
                height:8px;
                border-radius:0;
                background:#2563eb;
                margin:0;
            }

            .report-summary-label{
                color:var(--rc-muted, #4b5563);
                font-size:.75rem;
                font-weight:900;
                line-height:1.2;
                letter-spacing:.05em;
                text-transform:uppercase;
            }

            .report-summary-value{
                display:block;
                color:var(--rc-text, #111827);
                font-size:1.55rem;
                line-height:1.05;
                font-weight:900;
                overflow-wrap:anywhere;
            }

            .report-summary-note{
                display:block;
                color:var(--rc-muted, #4b5563);
                font-size:.86rem;
                line-height:1.35;
                font-weight:650;
            }

            .report-insight-grid{align-items:stretch;}
            .report-chart-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;align-items:stretch;}
            .report-chart-wide{grid-column:1 / -1;}
            .report-chart-card{min-width:0;break-inside:avoid;page-break-inside:avoid;}
            .report-chart-wrap{position:relative;min-height:300px;height:300px;}
            .report-chart{width:100% !important;height:100% !important;}
            .report-exception-list{display:flex;flex-direction:column;gap:8px;margin-top:10px;}
            .report-exception{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;border:1px solid var(--rc-border,#d1d5db);border-radius:10px;background:var(--rc-surface-muted,#f9fafb);}
            .report-exception small{display:block;color:var(--rc-muted,#4b5563);margin-top:2px;}

            html[data-theme="dark"] .report-summary-card{
                background:var(--rc-surface, #162527);
                color:var(--rc-text, #eaf6f4);
                border-color:var(--rc-border, rgba(148,163,184,.18));
            }
            html[data-theme="dark"] .report-summary-label,
            html[data-theme="dark"] .report-summary-note{
                color:var(--rc-muted, #9db0b5);
            }
            html[data-theme="dark"] .report-summary-value{
                color:var(--rc-text, #eaf6f4);
            }

            <?php if ($is_print): ?>
            html,body{background:#fff !important;color:#111827 !important;}
            .report-summary-grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;}
            .report-summary-card{
                background:#fff !important;
                color:#111827 !important;
                border:1px solid #6b7280 !important;
                box-shadow:none !important;
                break-inside:avoid;
                page-break-inside:avoid;
            }
            .report-summary-label,.report-summary-note,.report-summary-value{color:#111827 !important;}
            .report-chart-card{background:#fff !important;color:#111827 !important;border:1px solid #6b7280 !important;box-shadow:none !important;break-inside:avoid;page-break-inside:avoid;}
            .report-chart-card h4,.report-chart-card p{color:#111827 !important;}
            .report-chart-wrap,.report-chart{background:#fff !important;}
            @media print{
                .header,header.app-header,aside,nav,.sidebar,.report-toolbar,.rc-tabs,.xform-actions,button,.btn{display:none !important;}
                body,main,.content,.rc-panel,.rc-card{background:#fff !important;color:#111827 !important;box-shadow:none !important;}
                .rc-muted,small,.text-muted,p,li,td,th,span,label{color:#111827 !important;}
            }
            <?php endif; ?>

            @media (max-width:900px){
                .report-summary-grid,.report-chart-grid{grid-template-columns:1fr;}
            }
        </style>

        <div class="report-toolbar">
            <?php if ($is_print): ?>
                <button type="button" class="btn grey" onclick="window.print(); return false;">Print / Save PDF</button>
            <?php else: ?>
                <a class="btn grey" target="_blank" rel="noopener" href="<?php echo report_h($print_url); ?>">View print</a>
            <?php endif; ?>
        </div>

        <script>
        (function(){
            var REPORT_PRINT_MODE = <?php echo $is_print ? 'true' : 'false'; ?>;
            var palette = ['#2563eb','#16a34a','#d97706','#dc2626','#7c3aed','#0891b2','#be185d','#4f46e5','#65a30d','#ea580c'];

            function parse(value, fallback){ try { return JSON.parse(value || ''); } catch(e) { return fallback; } }
            function isDarkMode(){ return document.documentElement.getAttribute('data-theme') === 'dark'; }
            function theme(){
                if (REPORT_PRINT_MODE) {
                    return { text:'#000000', grid:'rgba(0,0,0,.35)', paper:'#ffffff', doughnutBorder:'#ffffff' };
                }
                var dark = isDarkMode();
                return {
                    text: dark ? '#e5e7eb' : '#111827',
                    grid: dark ? 'rgba(229,231,235,.18)' : 'rgba(17,24,39,.14)',
                    paper: 'transparent',
                    doughnutBorder: dark ? '#162527' : '#ffffff'
                };
            }
            function chartMajor(){
                var v = (Chart && Chart.version) ? String(Chart.version).split('.')[0] : '2';
                var n = parseInt(v, 10);
                return isNaN(n) ? 2 : n;
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

            var printPaperPlugin = {
                id: 'reportPrintPaperPlugin',
                beforeDraw: function(chart) {
                    if (!REPORT_PRINT_MODE) return;
                    var ctx = chart.ctx;
                    ctx.save();
                    ctx.globalCompositeOperation = 'destination-over';
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, chart.width, chart.height);
                    ctx.restore();
                }
            };

            var reportValueLabelsPlugin = {
                id: 'reportValueLabels',
                afterDatasetsDraw: function(chart, args, pluginOptions) {
                    var opts = pluginOptions || {};
                    if (!opts.display) return;

                    var ctx = chart.ctx;
                    ctx.save();
                    ctx.font = '800 10px Arial, sans-serif';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';

                    (chart.data.datasets || []).forEach(function(ds, datasetIndex) {
                        var meta = chart.getDatasetMeta(datasetIndex);
                        if (!meta || meta.hidden) return;

                        (meta.data || []).forEach(function(bar, index) {
                            var value = Number((ds.data || [])[index] || 0);
                            if (!value) return;

                            var point = bar.tooltipPosition ? bar.tooltipPosition() : null;
                            if (!point) return;

                            var text = String(value);
                            var label = String(ds.label || '');
                            var isBaseline = label === 'No medication';

                            ctx.lineWidth = isBaseline ? 3 : 2;
                            ctx.strokeStyle = isBaseline ? 'rgba(255,255,255,.82)' : 'rgba(15,23,42,.62)';
                            ctx.fillStyle = isBaseline ? '#111827' : '#ffffff';
                            ctx.strokeText(text, point.x, point.y);
                            ctx.fillText(text, point.x, point.y);
                        });
                    });

                    ctx.restore();
                }
            };

            function makeOptions(cfg, t){
                var type = cfg.type || 'bar';
                var isDoughnut = type === 'doughnut' || type === 'pie';
                var multiDataset = cfg.data && cfg.data.datasets && cfg.data.datasets.length > 1;
                var isV3 = chartMajor() >= 3;

                var options = {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: REPORT_PRINT_MODE ? false : undefined,
                    indexAxis: cfg.indexAxis || 'x',
                    color: t.text,
                    backgroundColor: t.paper,
                    legend: {
                        display: isDoughnut || multiDataset,
                        position: 'bottom',
                        labels: { fontColor: t.text, fontStyle: 'bold' }
                    },
                    plugins: {
                        legend: {
                            display: isDoughnut || multiDataset,
                            position: 'bottom',
                            labels: { color: t.text, font: { weight: '800', size: 13 } }
                        },
                        tooltip: { enabled: !REPORT_PRINT_MODE, mode: 'index', intersect: false },
                        reportValueLabels: {
                            display: !!cfg.showValues
                        }
                    }
                };

                if (!isDoughnut) {
                    if (isV3) {
                        options.scales = {
                            x: { stacked: !!cfg.stacked, ticks: { color: t.text, font: { weight: '800', size: 12 } }, title: { color: t.text }, grid: { color: t.grid }, border: { color: t.text } },
                            y: { stacked: !!cfg.stacked, beginAtZero: true, ticks: { color: t.text, precision: 0, font: { weight: '800', size: 12 } }, title: { color: t.text }, grid: { color: t.grid }, border: { color: t.text } }
                        };
                    } else {
                        options.scales = {
                            xAxes: [{ stacked: !!cfg.stacked, ticks: { fontColor: t.text, fontStyle: 'bold' }, gridLines: { color: t.grid, zeroLineColor: t.grid } }],
                            yAxes: [{ stacked: !!cfg.stacked, ticks: { beginAtZero: true, precision: 0, fontColor: t.text, fontStyle: 'bold' }, gridLines: { color: t.grid, zeroLineColor: t.grid } }]
                        };
                    }
                }

                return options;
            }

            function normaliseDatasets(cfg){
                (cfg.data.datasets || []).forEach(function(ds, i){
                    if (!ds.backgroundColor) {
                        ds.backgroundColor = cfg.type === 'line'
                            ? 'rgba(37,99,235,.18)'
                            : (cfg.stacked ? palette[i % palette.length] : palette);
                    }
                    if (!ds.borderColor) ds.borderColor = palette[i % palette.length];
                    if (cfg.type === 'bar') ds.borderRadius = 8;
                    if (cfg.type === 'line') { ds.tension = .3; ds.fill = true; ds.pointRadius = 3; }
                    if (REPORT_PRINT_MODE && (cfg.type === 'doughnut' || cfg.type === 'pie')) ds.borderColor = '#ffffff';
                });
            }

            function renderCharts(){
                if (typeof Chart === 'undefined') return;
                var t = theme();
                applyDefaults(t);
                if (Chart.register) { try { Chart.register(printPaperPlugin); } catch(e) {} }

                document.querySelectorAll('canvas.report-chart').forEach(function(canvas){
                    try {
                        if (canvas.dataset.chartReady === '1') return;

                        var cfg = parse(canvas.dataset.chartConfig, null);

                        if (!cfg) {
                            var labels = parse(canvas.dataset.labels, []);
                            var values = parse(canvas.dataset.values, []).map(Number);
                            var type = canvas.dataset.chartType || 'bar';
                            cfg = { type: type, data: { labels: labels, datasets: [{ label: canvas.getAttribute('aria-label') || '', data: values }] } };
                        }

                        if (!cfg || !cfg.data) return;
                        normaliseDatasets(cfg);
                        canvas.dataset.chartReady = '1';

                        var chartPlugins = [reportValueLabelsPlugin];
                        if (REPORT_PRINT_MODE) chartPlugins.unshift(printPaperPlugin);

                        new Chart(canvas, {
                            type: cfg.type || 'bar',
                            data: cfg.data,
                            options: makeOptions(cfg, t),
                            plugins: chartPlugins
                        });
                    } catch(e) {
                        canvas.dataset.chartReady = '';
                        if (window.console) console.error('Report chart failed', canvas.id, e);
                    }
                });
            }

            document.readyState === 'loading'
                ? document.addEventListener('DOMContentLoaded', renderCharts)
                : renderCharts();
        }());
        </script>
        <?php
    }
}

if (!function_exists('report_render_chart_config')) {
    function report_render_chart_config(string $title, array $config, string $note = ''): void {
        if (isset($GLOBALS['report_chart_config_collector']) && is_callable($GLOBALS['report_chart_config_collector'])) {
            $GLOBALS['report_chart_config_collector']($title, $config, $note);
            return;
        }
        $id = report_chart_id('report_chart');
        echo '<div class="rc-card report-chart-card"><h4>' . report_h($title) . '</h4>';
        if ($note !== '') echo '<p class="rc-muted">' . report_h($note) . '</p>';
        echo '<div class="report-chart-wrap"><canvas id="' . report_h($id) . '" class="report-chart" data-chart-config="' . report_h(json_encode($config)) . '"></canvas></div></div>';
    }
}

if (!function_exists('report_chart_from_map')) {
    function report_chart_from_map(string $title, array $map, string $type = 'bar', string $note = '', int $limit = 10, string $indexAxis = 'x'): void {
        $map = report_top_map($map, $limit);
        if (!$map) return;
        report_render_chart_config($title, ['type'=>$type,'indexAxis'=>$indexAxis,'data'=>['labels'=>array_keys($map),'datasets'=>[['label'=>$title,'data'=>array_values($map)]]]], $note);
    }
}

if (!function_exists('report_render_insights')) {
    function report_render_insights(array $items): void {
        echo '<div class="report-summary-grid report-insight-grid">';
        foreach ($items as $it) {
            echo '<div class="report-summary-card report-insight">';
            echo '<span class="report-summary-label">' . report_h($it['label'] ?? '') . '</span>';
            echo '<strong class="report-summary-value">' . report_h($it['value'] ?? '') . '</strong>';
            if (!empty($it['note'])) echo '<span class="report-summary-note">' . report_h($it['note']) . '</span>';
            echo '</div>';
        }
        echo '</div>';
    }
}

if (!function_exists('report_render_exception_list')) {
    function report_render_exception_list(string $title, array $items, int $limit = 12): void {
        echo '<div class="rc-card"><h4>' . report_h($title) . '</h4>';
        if (!$items) { echo '<p class="rc-muted">No exceptions found.</p></div>'; return; }
        echo '<div class="report-exception-list">'; $i=0;
        foreach ($items as $item) { if ($i++ >= $limit) break;
            echo '<div class="report-exception"><div><strong>' . report_h($item['title'] ?? '') . '</strong><small>' . report_h($item['meta'] ?? '') . '</small></div><span class="rc-badge">' . report_h($item['tag'] ?? 'Review') . '</span></div>';
        }
        echo '</div></div>';
    }
}

if (!function_exists('report_render_module_charts')) {
    function report_render_module_charts(string $code, array $rows): void {
        $chartFiles = [
            'CASE_INDEX' => 'case_index_chart.php',
            'SPECIES_OUTCOME_SUMMARY' => 'species_outcome_chart.php',
            'OUTCOMES_DISPOSITION_LOG' => 'outcomes_disposition_chart.php',
            'MEDICATION_LOG' => 'medication_log_chart.php',
            'TREATMENT_CARE_LOG' => 'treatment_care_chart.php',
            'NOTIFIABLE_INCIDENTS' => 'notifiable_incidents_chart.php',
        ];

        $code = strtoupper($code);
        if (empty($chartFiles[$code])) {
            return;
        }

        $chartFile = __DIR__ . '/' . $chartFiles[$code];
        if (is_file($chartFile)) {
            include $chartFile;
        }
    }
}


if (!function_exists('report_module_key')) {
    function report_module_key(array $module): string {
        $raw = strtoupper(trim((string)($module['code'] ?? '')));
        $raw = preg_replace('/[^A-Z0-9]+/', '_', $raw);
        $raw = trim($raw, '_');

        $query = strtoupper((string)($module['query_path'] ?? ''));
        $file = strtoupper(pathinfo($query, PATHINFO_FILENAME));
        $file = preg_replace('/[^A-Z0-9]+/', '_', $file);
        $file = trim($file, '_');

        $name = strtoupper((string)($module['name'] ?? ''));
        $name = preg_replace('/[^A-Z0-9]+/', '_', $name);
        $name = trim($name, '_');

        $candidates = array_filter([$raw, $file, $name]);
        foreach ($candidates as $candidate) {
            if ($candidate === 'CASE_INDEX' || str_contains($candidate, 'CASE_INDEX')) return 'CASE_INDEX';
            if ($candidate === 'SPECIES_OUTCOME_SUMMARY' || (str_contains($candidate, 'SPECIES') && str_contains($candidate, 'OUTCOME'))) return 'SPECIES_OUTCOME_SUMMARY';
            if ($candidate === 'OUTCOMES_DISPOSITION_LOG' || $candidate === 'OUTCOME_DISPOSITION_LOG' || (str_contains($candidate, 'OUTCOME') && str_contains($candidate, 'DISPOSITION'))) return 'OUTCOMES_DISPOSITION_LOG';
            if ($candidate === 'MEDICATION_LOG' || str_contains($candidate, 'MEDICATION')) return 'MEDICATION_LOG';
            if ($candidate === 'TREATMENT_CARE_LOG' || (str_contains($candidate, 'TREATMENT') && str_contains($candidate, 'CARE'))) return 'TREATMENT_CARE_LOG';
            if ($candidate === 'NOTIFIABLE_INCIDENTS' || str_contains($candidate, 'NOTIFIABLE')) return 'NOTIFIABLE_INCIDENTS';
        }
        return $raw ?: $file;
    }
}

if (!function_exists('report_render_meaningful_summary')) {
    function report_render_meaningful_summary(array $module, array $rows, string $from_date, string $to_date): void {
        $code = report_module_key($module);
        report_render_print_tools();
        echo '<div class="rc-stack">';

        switch ($code) {
            case 'CASE_INDEX': {
                $open = $incomplete = 0; $speciesOpen = [];
                foreach ($rows as $r) {
                    $species = (string)report_value($r, ['animal_species','Animal Species'], 'Unknown species');
                    $disp = trim((string)report_value($r, ['disposition','Disposition (Text)','Disposition'], ''));
                    $fields = trim((string)report_value($r, ['incomplete_fields'], ''));
                    if ($disp === '') { $open++; $speciesOpen[$species] = ($speciesOpen[$species] ?? 0) + 1; }
                    if ($fields !== '') { $incomplete++; $exceptions[] = ['title'=>report_value($r,['patient_name'],'Patient').' · '.$species, 'meta'=>'Missing: '.$fields, 'tag'=>'Incomplete']; }
                }
                report_render_insights([
                    ['label'=>'Open case pressure','value'=>$open,'note'=>'Admissions in this period without final disposition'],
                    ['label'=>'Record quality risk','value'=>$incomplete,'note'=>'Records marked with incomplete fields'],
                    ['label'=>'Period','value'=>$from_date.' → '.$to_date,'note'=>'Admission-date based'],
                ]);
                echo '<div class="report-chart-grid">';
                report_chart_from_map('Admission trend', report_time_series($rows, ['admission_date','Admission Date'], 'week'), 'line', 'Shows pressure over time, not just total intake.');
                report_chart_from_map('Open cases by species', $speciesOpen, 'bar', 'Where follow-up work is concentrated.', 10, 'y');
                report_chart_from_map('Case age profile', report_age_buckets($rows), 'bar', 'Older open/closed cases highlight care duration pressure.');
                echo '</div>';
                break;
            }

            case 'SPECIES_OUTCOME_SUMMARY': {
                $labels=[]; $datasets=['Released'=>[],'Transferred'=>[],'Euthanised'=>[],'Died'=>[],'DOA'=>[],'Captive/Open/Unmapped'=>[]]; $releaseRate=[]; $riskRate=[];
                foreach (array_slice($rows,0,12) as $r) {
                    $sp=(string)report_value($r,['Animal Species'],'Unknown species'); $labels[]=$sp;
                    $ad=(int)($r['Admitted Total']??0); $rel=(int)($r['Released (R)']??0); $bad=(int)($r['Euthanised (E)']??0)+(int)($r['Died After Intake (D)']??0)+(int)($r['Dead On Admission (DOA)']??0);
                    $datasets['Released'][]=$rel; $datasets['Transferred'][]=(int)($r['Transferred (T)']??0); $datasets['Euthanised'][]=(int)($r['Euthanised (E)']??0); $datasets['Died'][]=(int)($r['Died After Intake (D)']??0); $datasets['DOA'][]=(int)($r['Dead On Admission (DOA)']??0); $datasets['Captive/Open/Unmapped'][]=(int)($r['Held in Captivity (IC)']??0)+(int)($r['Long-Term Captive (PC)']??0)+(int)($r['Pending / Open']??0)+(int)($r['Unmapped Disposition']??0);
                    $releaseRate[$sp]=report_pct($rel,$ad); $riskRate[$sp]=report_pct($bad,$ad);
                }
                $chartDatasets=[]; foreach($datasets as $name=>$data) $chartDatasets[]=['label'=>$name,'data'=>$data];
                report_render_insights([
                    ['label'=>'Best release signal','value'=>array_key_first(report_top_map($releaseRate,1)) ?: 'n/a','note'=>'Highest release percentage among shown species'],
                    ['label'=>'Highest poor-outcome signal','value'=>array_key_first(report_top_map($riskRate,1)) ?: 'n/a','note'=>'Euthanised/died/DOA percentage among shown species'],
                    ['label'=>'Outcome review','value'=>report_sum_column($rows,'Pending / Open') + report_sum_column($rows,'Unmapped Disposition'),'note'=>'Open or unmapped outcomes'],
                ]);
                echo '<div class="report-chart-grid">';
                report_render_chart_config('Species outcome mix', ['type'=>'bar','stacked'=>true,'data'=>['labels'=>$labels,'datasets'=>$chartDatasets]], 'Stacked outcomes show workload quality, not species counts.');
                report_chart_from_map('Release rate by species (%)', $releaseRate, 'bar', 'Compares success rate, not admission volume.', 12, 'y');
                report_chart_from_map('Poor outcome rate by species (%)', $riskRate, 'bar', 'Flags species groups needing closer review.', 12, 'y');
                echo '</div>';
                break;
            }

            case 'OUTCOMES_DISPOSITION_LOG': {
                $families=[]; $daysByFamily=[]; $long=[];
                foreach ($rows as $r) {
                    $fam=report_outcome_family(report_value($r,['Universal Shortcode'],''), report_value($r,['Disposition (Text)'],''));
                    $families[$fam]=($families[$fam]??0)+1;
                    $days=(int)($r['Days in Care']??0); if($days>0){ $daysByFamily[$fam]['sum']=($daysByFamily[$fam]['sum']??0)+$days; $daysByFamily[$fam]['n']=($daysByFamily[$fam]['n']??0)+1; }
                    if($days>=90) $long[]=['title'=>report_value($r,['Patient Name'],'Patient').' · '.report_value($r,['Animal Species'],''),'meta'=>$days.' days in care · '.report_value($r,['Disposition (Text)'],'No disposition'),'tag'=>'Long stay'];
                }
                $avg=[]; foreach($daysByFamily as $k=>$v) $avg[$k]=round($v['sum']/$v['n'],1);
                report_render_insights([
                    ['label'=>'Average care duration','value'=>report_average_column($rows,'Days in Care') ?? 'n/a','note'=>'Only where disposition date exists'],
                    ['label'=>'Long-stay records','value'=>count($long),'note'=>'90+ days in care'],
                    ['label'=>'Open/unmapped','value'=>($families['Open / pending']??0)+($families['Unmapped']??0),'note'=>'Needs review before final reporting'],
                ]);
                echo '<div class="report-chart-grid">';
                report_chart_from_map('Outcome balance', $families, 'doughnut', 'Shows overall outcome shape.');
                report_chart_from_map('Average days in care by outcome', $avg, 'bar', 'Highlights outcomes associated with longer stays.', 10, 'y');
                report_chart_from_map('Disposition timing profile', report_age_buckets($rows), 'bar', 'Care-duration distribution for admissions in period.');
                echo '</div>';
                report_render_exception_list('Long-stay cases', $long);
                break;
            }

            case 'MEDICATION_LOG': {
                $patients=[]; $medicineBySpecies=[]; $expiry=[];
                foreach($rows as $r){
                    $med=(string)report_value($r,['Medication'],'Unknown medication'); $sp=(string)report_value($r,['Animal Species'],'Unknown species');
                    $medicineBySpecies[$med.' · '.$sp]=($medicineBySpecies[$med.' · '.$sp]??0)+1;
                    $exp=report_value($r,['Expiry Date'],''); if($exp && strtotime((string)$exp) && strtotime((string)$exp) < time()) $expiry[]=['title'=>$med.' · '.report_value($r,['Patient Name'],'Patient'),'meta'=>'Batch '.report_value($r,['Batch'],'n/a').' · expired '.$exp,'tag'=>'Expiry'];
                }
                report_render_insights([
                    ['label'=>'Medication burden','value'=>round(count($rows)/max(1,count(report_count_by($rows,['Patient ID']))),1),'note'=>'Administrations per treated patient'],
                    ['label'=>'Expired batch flags','value'=>count($expiry),'note'=>'Based on recorded expiry date'],
                    ['label'=>'Treated patients','value'=>count(report_count_by($rows,['Patient ID'])),'note'=>'Context only, not the main measure'],
                ]);
                echo '<div class="report-chart-grid">';
                report_chart_from_map('Medication activity over time', report_time_series($rows,['Date Given'],'week'), 'line', 'Shows pressure and stock demand by week.');
                report_chart_from_map('Patients with highest medication burden', report_patient_intensity($rows), 'bar', 'Highlights cases needing the most medication input.', 12, 'y');
                report_chart_from_map('Medication/species combinations', $medicineBySpecies, 'bar', 'More useful than medicine counts alone: shows what is being used on whom.', 12, 'y');
                echo '</div>';
                report_render_exception_list('Medication records needing review', $expiry);
                break;
            }

            case 'TREATMENT_CARE_LOG': {
                $eventByWeek = report_time_series($rows,['Event Date'],'week'); $byPatient = report_patient_intensity($rows); $staff=[];
                foreach($rows as $r){ $s=(string)report_value($r,['Recorded By'],'Not recorded'); $staff[$s]=($staff[$s]??0)+1; }
                report_render_insights([
                    ['label'=>'Care intensity','value'=>round(count($rows)/max(1,count(report_count_by($rows,['Patient ID']))),1),'note'=>'Events per patient with care activity'],
                    ['label'=>'Most intensive case','value'=>array_key_first($byPatient) ?: 'n/a','note'=>'Highest treatment/care-note volume'],
                    ['label'=>'Recording spread','value'=>count($staff),'note'=>'People recording care activity'],
                ]);
                echo '<div class="report-chart-grid">';
                report_chart_from_map('Care activity over time', $eventByWeek, 'line', 'Shows treatment/care pressure through the period.');
                report_chart_from_map('Highest care-load patients', $byPatient, 'bar', 'Identifies cases consuming the most care time.', 12, 'y');
                report_chart_from_map('Recording workload by person', $staff, 'bar', 'Shows recording/care distribution across the team.', 12, 'y');
                echo '</div>';
                break;
            }

            case 'NOTIFIABLE_INCIDENTS': {
                $priority=['High priority'=>0,'Review'=>0,'Low confidence'=>0]; $catSpecies=[]; $queue=[];
                foreach($rows as $r){
                    $cat=(string)report_value($r,['Notifiable Category (Derived)'],'Other / Review Required'); $sp=(string)report_value($r,['Animal Species'],'Unknown species');
                    $isOther = stripos($cat,'Other')!==false; $tag=$isOther?'Low confidence':(stripos($cat,'Poison')!==false||stripos($cat,'Shooting')!==false||stripos($cat,'Cruelty')!==false?'High priority':'Review');
                    $priority[$tag]++; $catSpecies[$cat.' · '.$sp]=($catSpecies[$cat.' · '.$sp]??0)+1;
                    $queue[]=['title'=>report_value($r,['Patient Name'],'Patient').' · '.$sp,'meta'=>$cat.' · '.report_value($r,['Presenting Complaint'],''),'tag'=>$tag];
                }
                report_render_insights([
                    ['label'=>'High-priority review','value'=>$priority['High priority'],'note'=>'Poisoning/shooting/cruelty style matches'],
                    ['label'=>'Low-confidence matches','value'=>$priority['Low confidence'],'note'=>'Other/review required categories'],
                    ['label'=>'Manual confirmation','value'=>'Required','note'=>'Keyword-derived queue only'],
                ]);
                echo '<div class="report-chart-grid">';
                report_chart_from_map('Review priority', $priority, 'doughnut', 'Designed as a triage queue, not an incident count.');
                report_chart_from_map('Incident theme by species', $catSpecies, 'bar', 'Combines suspected issue with animal context.', 12, 'y');
                report_chart_from_map('Incident intake trend', report_time_series($rows,['Admission Date'],'week'), 'line', 'Shows whether reportable themes are clustering over time.');
                echo '</div>';
                report_render_exception_list('Manual review queue', $queue);
                echo '<div class="rc-alert amber">Derived from presenting complaint text only. Treat this as a review queue before external reporting.</div>';
                break;
            }

            default:
                report_render_insights([['label'=>'Report available','value'=>(string)count($rows),'note'=>'No contextual definition has been added for this module yet.']]);
                break;
        }
        echo '</div>';
    }
}
