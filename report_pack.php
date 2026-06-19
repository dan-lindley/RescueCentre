<?php
define('APP_LOADED', true);

include 'main.php';
include 'getcentreinfo.php';
include 'getuserinfo.php';
check_loggedin($pdo, 'index.php');
require_once __DIR__ . '/operations/permissions.php';

registerPermission(
    "page_centre_reports",
    "Access to Rescue Reports Page",
    "page"
);
requirePermission("page_centre_reports");

$printMode = (isset($_GET['print']) && (string)$_GET['print'] === '1')
    || strtolower((string)($_GET['view'] ?? ($_GET['mode'] ?? 'web'))) === 'print';
require_once __DIR__ . '/views/reports/report_helpers.php';
require_once __DIR__ . '/views/reports/' . ($printMode ? 'chart_render_print.php' : 'chart_render_web.php');

$centre_id = (int)($centre_id ?? $_SESSION['centre_id'] ?? 0);
$rescueName = trim((string)($rescue_name ?? $_SESSION['rescue_name'] ?? 'Rescue Centre'));

$stmt = $pdo->prepare("
    SELECT reporting_from, reporting_to
    FROM rescue_centre_meta
    WHERE centre_id = :centre_id
    LIMIT 1
");
$stmt->execute([':centre_id' => $centre_id]);
$meta = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$from_date = (string)($meta['reporting_from'] ?? date('Y-m-d', strtotime('-30 days')));
$to_date = (string)($meta['reporting_to'] ?? date('Y-m-d'));

$requested = $_POST['module_codes'] ?? $_GET['module_codes'] ?? [];
if (is_string($requested)) {
    $requested = array_filter(array_map('trim', explode(',', $requested)));
}
if (!is_array($requested)) {
    $requested = [];
}
$requested = array_values(array_unique(array_map('strval', $requested)));

$stmt = $pdo->prepare("
    SELECT code, name, description, query_path
    FROM rescue_reports_modules
    WHERE is_active = 1
    ORDER BY sort_order ASC, name ASC
");
$stmt->execute();
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$modulesByCode = [];
foreach ($modules as $module) {
    $modulesByCode[(string)$module['code']] = $module;
}

if (empty($requested)) {
    $requested = array_keys($modulesByCode);
}

$selectedModules = [];
foreach ($requested as $code) {
    if (!empty($modulesByCode[$code])) {
        $selectedModules[] = $modulesByCode[$code];
    }
}

$printUrl = 'report_pack_pdf.php?' . http_build_query([
    'module_codes' => implode(',', array_map(static function (array $module): string {
        return (string)$module['code'];
    }, $selectedModules)),
]);

function report_pack_supporting_table(array $rows, int $limit = 50): void {
    if (empty($rows)) {
        return;
    }

    $rows = array_slice($rows, 0, $limit);
    echo '<details class="report-detail">';
    echo '<summary>Supporting records (' . count($rows) . ' shown)</summary>';
    echo '<div class="rc-table-scroll">';
    echo '<table class="rc-table row-hover">';
    echo '<thead><tr>';
    foreach (array_keys($rows[0]) as $column) {
        echo '<th>' . report_h($column) . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($row as $value) {
            echo '<td>' . report_h($value) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
    echo '</details>';
}

function report_pack_module_hooks(string $code): array {
    $templates = [
        'CASE_INDEX' => [
            'file' => 'case_index_report.php',
            'main' => 'report_case_index_main',
            'appendix' => 'report_case_index_appendix',
        ],
        'MEDICATION_LOG' => [
            'file' => 'medication_log_report.php',
            'main' => 'report_medication_log_main',
            'appendix' => 'report_medication_log_appendix',
        ],
        'OUTCOMES_DISPOSITION_LOG' => [
            'file' => 'outcomes_disposition_report.php',
            'main' => 'report_outcomes_disposition_main',
            'appendix' => 'report_outcomes_disposition_appendix',
        ],
        'SPECIES_OUTCOME_SUMMARY' => [
            'file' => 'species_outcome_report.php',
            'main' => 'report_species_outcome_main',
            'appendix' => 'report_species_outcome_appendix',
        ],
        'TREATMENT_CARE_LOG' => [
            'file' => 'treatment_care_report.php',
            'main' => 'report_treatment_care_main',
            'appendix' => 'report_treatment_care_appendix',
        ],
        'NOTIFIABLE_INCIDENTS' => [
            'file' => 'notifiable_incidents_report.php',
            'main' => 'report_notifiable_incidents_main',
            'appendix' => 'report_notifiable_incidents_appendix',
        ],
    ];

    $code = strtoupper($code);
    if (empty($templates[$code])) {
        return [];
    }

    $template = $templates[$code];
    $file = __DIR__ . '/views/reports/' . $template['file'];
    if (is_file($file)) {
        require_once $file;
    }

    return $template;
}

function report_pack_render_module_main(array $module, array $rows, array $context): bool {
    $hooks = report_pack_module_hooks(report_module_key($module));
    if (empty($hooks['main']) || !function_exists($hooks['main'])) {
        return false;
    }

    $hooks['main']($module, $rows, $context);
    return true;
}

function report_pack_render_module_appendix(array $module, array $rows, array $context): bool {
    $hooks = report_pack_module_hooks(report_module_key($module));
    if (empty($hooks['appendix']) || !function_exists($hooks['appendix'])) {
        return false;
    }

    $hooks['appendix']($module, $rows, $context);
    return true;
}

function report_pack_render_footer(array $context): void {
    echo '<footer class="report-footer">';
    echo '<span>' . report_h($context['rescue_name'] ?? 'Rescue Centre') . '</span>';
    echo '<span>Generated ' . report_h($context['generated_at'] ?? date('d M Y H:i')) . '</span>';
    echo '</footer>';
}

function report_pack_run_cohort_summary(PDO $pdo, int $centre_id, string $from_date, string $to_date): array {
    $sqlFile = __DIR__ . '/models/reporting/REPORT_COHORT_SUMMARY.sql';
    if (!is_file($sqlFile)) {
        return [];
    }

    $sql = file_get_contents($sqlFile);
    if ($sql === false || trim($sql) === '') {
        return [];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':centre_id' => $centre_id,
        ':from_date' => $from_date,
        ':to_date' => $to_date,
    ]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

function report_pack_render_cohort_header(array $summary): void {
    if (empty($summary)) {
        return;
    }

    echo '<div class="rc-card report-block">';
    echo '<strong>Report cohort:</strong> A patient journey is a complete admission from start to final disposition within the selected report dates. Records that overlap the report boundary are shown below as context, but are excluded from journey-based comparisons.';
    echo '</div>';

    report_render_insights([
        [
            'label' => 'Total patient journeys',
            'value' => (int)($summary['Complete Patient Journeys'] ?? 0),
            'note' => 'Admission and final disposition inside the report period',
        ],
        [
            'label' => 'Active before report start',
            'value' => (int)($summary['Active Before Report Start'] ?? 0),
            'note' => 'Already in care when the report period began',
        ],
        [
            'label' => 'Disposition after report end',
            'value' => (int)($summary['Disposition After Report End'] ?? 0),
            'note' => 'Admitted in period, final outcome after the report period',
        ],
    ]);
}

$reportCohortSummary = report_pack_run_cohort_summary($pdo, $centre_id, $from_date, $to_date);

$reportContext = [
    'centre_id' => $centre_id,
    'rescue_name' => $rescueName,
    'from_date' => $from_date,
    'to_date' => $to_date,
    'print_mode' => $printMode,
    'cohort_summary' => $reportCohortSummary,
    'generated_at' => date('d M Y H:i'),
];
$reportAppendices = [];
?><!doctype html>


<html<?= !empty($_SESSION['dark_mode']) ? ' data-theme="dark"' : '' ?>>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= report_h($rescueName) ?> Report Pack</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="core/css/core.css">
    <link rel="stylesheet" href="core/css/report_web.css">
    <link rel="stylesheet" href="core/css/report_print.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>
    <main class="report-document<?= $printMode ? ' report-print-view' : '' ?>">
        <?php
        ob_start();
        report_render_print_tools();
        $reportTools = ob_get_clean();
        echo preg_replace('/<div class="report-toolbar">.*?<\/div>/s', '', $reportTools ?? '');
        ?>
        <style>
            .report-summary-grid.report-grid-four,
            .report-insight-grid.report-grid-four {
                grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            }

            @media (max-width: 1000px) {
                .report-summary-grid.report-grid-four,
                .report-insight-grid.report-grid-four {
                    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                }
            }

            @media (max-width: 640px) {
                .report-summary-grid.report-grid-four,
                .report-insight-grid.report-grid-four {
                    grid-template-columns: 1fr !important;
                }
            }

            @media print {
                .report-summary-grid.report-grid-four,
                .report-insight-grid.report-grid-four {
                    grid-template-columns: repeat(4, 1fr) !important;
                }
            }
        </style>
        <?php if ($printMode): ?>
            <style>
                html[data-theme="dark"] .report-print-view,
                .report-print-view {
                    background: #ffffff !important;
                    color: #111827 !important;
                }

                html[data-theme="dark"] .report-print-view .report-cover,
                html[data-theme="dark"] .report-print-view .report-section,
                html[data-theme="dark"] .report-print-view .report-appendices,
                html[data-theme="dark"] .report-print-view .report-appendix,
                html[data-theme="dark"] .report-print-view .report-detail,
                html[data-theme="dark"] .report-print-view .report-detail summary,
                html[data-theme="dark"] .report-print-view .report-detail .rc-table-scroll,
                html[data-theme="dark"] .report-print-view .report-detail table,
                html[data-theme="dark"] .report-print-view .report-detail th,
                html[data-theme="dark"] .report-print-view .report-detail td,
                html[data-theme="dark"] .report-print-view .report-footer,
                .report-print-view .report-cover,
                .report-print-view .report-section,
                .report-print-view .report-appendices,
                .report-print-view .report-appendix,
                .report-print-view .report-detail,
                .report-print-view .report-detail summary,
                .report-print-view .report-detail .rc-table-scroll,
                .report-print-view .report-detail table,
                .report-print-view .report-detail th,
                .report-print-view .report-detail td,
                .report-print-view .report-footer {
                    background: #ffffff !important;
                    color: #111827 !important;
                    border-color: #cbd5e1 !important;
                    box-shadow: none !important;
                }

                html[data-theme="dark"] .report-print-view .report-cover *,
                html[data-theme="dark"] .report-print-view .report-section > h2,
                html[data-theme="dark"] .report-print-view .report-section > p,
                html[data-theme="dark"] .report-print-view .report-appendices > h2,
                html[data-theme="dark"] .report-print-view .report-appendices > p,
                html[data-theme="dark"] .report-print-view .report-appendix h3,
                html[data-theme="dark"] .report-print-view .report-appendix p,
                html[data-theme="dark"] .report-print-view .report-detail *,
                html[data-theme="dark"] .report-print-view .report-footer *,
                .report-print-view .report-cover *,
                .report-print-view .report-section > h2,
                .report-print-view .report-section > p,
                .report-print-view .report-appendices > h2,
                .report-print-view .report-appendices > p,
                .report-print-view .report-appendix h3,
                .report-print-view .report-appendix p,
                .report-print-view .report-detail *,
                .report-print-view .report-footer * {
                    color: #111827 !important;
                    text-shadow: none !important;
                }

                html[data-theme="dark"] .report-print-view .report-detail thead th,
                .report-print-view .report-detail thead th {
                    background: #e5e7eb !important;
                    color: #111827 !important;
                }
            </style>
        <?php endif; ?>
        <div class="report-actions no-print">
            <?php if ($printMode): ?>
                <button type="button" class="btn blue" onclick="window.print(); return false;">Print / Save PDF</button>
            <?php else: ?>
                <a class="btn blue" href="<?= report_h($printUrl) ?>" target="_blank" rel="noopener">Print View</a>
            <?php endif; ?>
        </div>

        <section class="report-cover">
            <span class="rc-chip blue">Report pack</span>
            <h1><?= report_h($rescueName) ?></h1>
            <p>Operational report for <?= report_h($from_date) ?> to <?= report_h($to_date) ?>.</p>
            <div class="report-meta">
                <span class="rc-chip"><?= count($selectedModules) ?> section<?= count($selectedModules) === 1 ? '' : 's' ?></span>
                <span class="rc-chip">Generated <?= report_h($reportContext['generated_at']) ?></span>
            </div>
            <?php report_pack_render_cohort_header($reportCohortSummary); ?>
        </section>

        <?php if (empty($selectedModules)): ?>
            <div class="rc-alert amber">No active report sections were selected.</div>
        <?php endif; ?>

        <?php foreach ($selectedModules as $module): ?>
            <section class="rc-panel report-section">
                <h2><?= report_h($module['name']) ?></h2>
                <?php if (!empty($module['description'])): ?>
                    <p class="rc-muted"><?= report_h($module['description']) ?></p>
                <?php endif; ?>

                <?php
                try {
                    $rows = report_run_module($pdo, $module, $centre_id, $from_date, $to_date);
                    $moduleCode = report_module_key($module);
                    if (empty($rows)) {
                        echo '<div class="rc-alert blue">No data found for this section.</div>';
                    } elseif (report_pack_render_module_main($module, $rows, $reportContext)) {
                        $reportAppendices[] = ['module' => $module, 'rows' => $rows];
                    } else {
                        report_render_meaningful_summary($module, $rows, $from_date, $to_date);
                        report_render_module_charts($moduleCode, $rows);
                        report_pack_supporting_table($rows);
                    }
                } catch (Throwable $e) {
                    echo '<div class="rc-alert red">Could not generate this report section.</div>';
                    echo '<details class="rc-alert amber report-debug" open>';
                    echo '<summary>Report debug</summary>';
                    echo '<p><strong>Module:</strong> ' . report_h($module['code'] ?? 'unknown') . '</p>';
                    echo '<p><strong>Query path:</strong> ' . report_h($module['query_path'] ?? 'unknown') . '</p>';
                    echo '<p><strong>Error:</strong> ' . report_h($e->getMessage()) . '</p>';
                    echo '<p><strong>File:</strong> ' . report_h($e->getFile()) . ':' . report_h($e->getLine()) . '</p>';
                    echo '</details>';
                    error_log('REPORT PACK ERROR [' . ($module['code'] ?? '') . ']: ' . $e->getMessage());
                }
                ?>
            </section>
            <hr class="report-section-break">
        <?php endforeach; ?>

        <?php if (!empty($reportAppendices)): ?>
            <section class="report-appendices">
                <h2>Appendices</h2>
                <p class="rc-muted">Supporting records and audit detail for report sections that use the module template hooks.</p>
                <?php foreach ($reportAppendices as $index => $appendix): ?>
                    <?php report_pack_render_module_appendix($appendix['module'], $appendix['rows'], $reportContext); ?>
                    <?php if ($index < count($reportAppendices) - 1): ?>
                        <hr class="report-section-break">
                    <?php endif; ?>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <?php report_pack_render_footer($reportContext); ?>
    </main>
</body>
</html>
