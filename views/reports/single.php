<?php
/**
 * views/reports/single.php
 *
 * Clean Single Reports tab:
 * - Lists active modules
 * - Runs a single selected module (Pattern A) inside the reports.php wrapper
 *
 * Requires:
 * - $pdo
 * - $REPORTING_CONTEXT = ['centre_id' => ..., 'from_date' => ..., 'to_date' => ...]
 * - $tab (from reports.php wrapper)
 */

require_once __DIR__ . '/report_helpers.php';

// ---------------------------
// ✅ CONTEXT
// ---------------------------
$centre_id = $REPORTING_CONTEXT['centre_id'] ?? null;
$from_date = $REPORTING_CONTEXT['from_date'] ?? null;
$to_date   = $REPORTING_CONTEXT['to_date'] ?? null;

if (!function_exists('report_h')) {
    function report_h($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('report_value')) {
    function report_value(array $row, array $keys, $default = '') {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
                return $row[$key];
            }
        }
        return $default;
    }
}

if (!function_exists('report_count_by')) {
    function report_count_by(array $rows, array $keys, string $fallback = 'Not recorded'): array {
        $counts = [];
        foreach ($rows as $row) {
            $label = trim((string)report_value($row, $keys, $fallback));
            if ($label === '') {
                $label = $fallback;
            }
            $counts[$label] = ($counts[$label] ?? 0) + 1;
        }
        arsort($counts);
        return $counts;
    }
}

if (!function_exists('report_sum_column')) {
    function report_sum_column(array $rows, string $key): int {
        $sum = 0;
        foreach ($rows as $row) {
            $sum += (int)($row[$key] ?? 0);
        }
        return $sum;
    }
}

if (!function_exists('report_average_column')) {
    function report_average_column(array $rows, string $key): ?float {
        $total = 0;
        $count = 0;
        foreach ($rows as $row) {
            if (isset($row[$key]) && is_numeric($row[$key])) {
                $total += (float)$row[$key];
                $count++;
            }
        }
        return $count > 0 ? round($total / $count, 1) : null;
    }
}

if (!function_exists('report_render_stat_grid')) {
    function report_render_stat_grid(array $stats): void {
        echo '<div class="rc-stat-grid">';
        foreach ($stats as $stat) {
            echo '<div class="rc-stat">';
            echo '<small>' . report_h($stat['label'] ?? '') . '</small>';
            echo '<strong>' . report_h($stat['value'] ?? '') . '</strong>';
            if (!empty($stat['note'])) {
                echo '<span>' . report_h($stat['note']) . '</span>';
            }
            echo '</div>';
        }
        echo '</div>';
    }
}

if (!function_exists('report_render_breakdown')) {
    function report_render_breakdown(string $title, array $counts, int $limit = 8): void {
        echo '<div class="rc-card">';
        echo '<h4>' . report_h($title) . '</h4>';
        if (empty($counts)) {
            echo '<p class="rc-muted">No data available.</p>';
            echo '</div>';
            return;
        }
        echo '<div class="rc-list">';
        $shown = 0;
        foreach ($counts as $label => $count) {
            if ($shown >= $limit) {
                break;
            }
            echo '<div class="rc-item">';
            echo '<div class="rc-item-main"><strong>' . report_h($label) . '</strong></div>';
            echo '<span class="rc-badge">' . (int)$count . '</span>';
            echo '</div>';
            $shown++;
        }
        echo '</div>';
        echo '</div>';
    }
}

if (!function_exists('report_render_meaningful_summary')) {
    function report_render_meaningful_summary(array $module, array $rows, string $from_date, string $to_date): void {
        $code = strtoupper((string)($module['code'] ?? ''));
        $totalRows = count($rows);

        report_render_print_tools();
        echo '<div class="rc-stack">';

        switch ($code) {
            case 'CASE_INDEX':
                $patients = [];
                $open = 0;
                $incomplete = 0;
                foreach ($rows as $row) {
                    $pid = report_value($row, ['patient_id', 'Patient ID']);
                    if ($pid !== '') {
                        $patients[(string)$pid] = true;
                    }
                    if (trim((string)report_value($row, ['disposition', 'Disposition (Text)', 'Disposition'], '')) === '') {
                        $open++;
                    }
                    if (trim((string)report_value($row, ['incomplete_fields'], '')) !== '') {
                        $incomplete++;
                    }
                }
                report_render_stat_grid([
                    ['label' => 'Admissions', 'value' => $totalRows, 'note' => $from_date . ' to ' . $to_date],
                    ['label' => 'Unique patients', 'value' => count($patients)],
                    ['label' => 'Open / pending', 'value' => $open],
                    ['label' => 'Incomplete records', 'value' => $incomplete],
                ]);
                echo '<div class="rc-card-grid">';
                report_render_breakdown('Admissions by species', report_count_by($rows, ['animal_species', 'Animal Species']));
                report_render_breakdown('Admissions by disposition', report_count_by($rows, ['disposition', 'Disposition (Text)', 'Disposition']));
                report_render_breakdown('Presenting complaints', report_count_by($rows, ['presenting_complaint', 'Presenting Complaint']), 6);
                echo '</div>';
                break;

            case 'SPECIES_OUTCOME_SUMMARY':
                $admitted = report_sum_column($rows, 'Admitted Total');
                $released = report_sum_column($rows, 'Released (R)');
                $pending = report_sum_column($rows, 'Pending / Open');
                $unmapped = report_sum_column($rows, 'Unmapped Disposition');
                $releaseRate = $admitted > 0 ? round(($released / $admitted) * 100, 1) . '%' : '0%';
                report_render_stat_grid([
                    ['label' => 'Admitted total', 'value' => $admitted],
                    ['label' => 'Species groups', 'value' => $totalRows],
                    ['label' => 'Released', 'value' => $released, 'note' => $releaseRate . ' release rate'],
                    ['label' => 'Pending / unmapped', 'value' => $pending + $unmapped],
                ]);
                $speciesTotals = [];
                foreach ($rows as $row) {
                    $label = report_value($row, ['Animal Species'], 'Unknown species');
                    $speciesTotals[$label] = (int)($row['Admitted Total'] ?? 0);
                }
                arsort($speciesTotals);
                echo '<div class="rc-card-grid">';
                report_render_breakdown('Largest species groups', $speciesTotals);
                report_render_breakdown('Outcome totals', [
                    'Released' => $released,
                    'Transferred' => report_sum_column($rows, 'Transferred (T)'),
                    'Euthanised' => report_sum_column($rows, 'Euthanised (E)'),
                    'Died after intake' => report_sum_column($rows, 'Died After Intake (D)'),
                    'Dead on admission' => report_sum_column($rows, 'Dead On Admission (DOA)'),
                    'Held in captivity' => report_sum_column($rows, 'Held in Captivity (IC)'),
                ]);
                echo '</div>';
                break;

            case 'OUTCOMES_DISPOSITION_LOG':
                $avgDays = report_average_column($rows, 'Days in Care');
                report_render_stat_grid([
                    ['label' => 'Outcome records', 'value' => $totalRows],
                    ['label' => 'Average days in care', 'value' => $avgDays !== null ? $avgDays : 'n/a'],
                    ['label' => 'Released', 'value' => (report_count_by($rows, ['Universal Shortcode'])['R'] ?? 0)],
                    ['label' => 'Still open / unmapped', 'value' => (report_count_by($rows, ['Disposition (Text)'])['Not recorded'] ?? 0)],
                ]);
                echo '<div class="rc-card-grid">';
                report_render_breakdown('Outcomes by universal code', report_count_by($rows, ['Universal Shortcode']));
                report_render_breakdown('Outcomes by species', report_count_by($rows, ['Animal Species']));
                echo '</div>';
                break;

            case 'MEDICATION_LOG':
                $patients = [];
                foreach ($rows as $row) {
                    $pid = report_value($row, ['Patient ID']);
                    if ($pid !== '') {
                        $patients[(string)$pid] = true;
                    }
                }
                report_render_stat_grid([
                    ['label' => 'Administrations', 'value' => $totalRows],
                    ['label' => 'Patients treated', 'value' => count($patients)],
                    ['label' => 'Medicines used', 'value' => count(report_count_by($rows, ['Medication']))],
                    ['label' => 'Staff recorded', 'value' => count(report_count_by($rows, ['Given By']))],
                ]);
                echo '<div class="rc-card-grid">';
                report_render_breakdown('Most used medicines', report_count_by($rows, ['Medication']));
                report_render_breakdown('Administrations by species', report_count_by($rows, ['Animal Species']));
                report_render_breakdown('Given by', report_count_by($rows, ['Given By']));
                echo '</div>';
                break;

            case 'TREATMENT_CARE_LOG':
                $patients = [];
                foreach ($rows as $row) {
                    $pid = report_value($row, ['Patient ID']);
                    if ($pid !== '') {
                        $patients[(string)$pid] = true;
                    }
                }
                $events = report_count_by($rows, ['Event Type']);
                report_render_stat_grid([
                    ['label' => 'Care events', 'value' => $totalRows],
                    ['label' => 'Patients covered', 'value' => count($patients)],
                    ['label' => 'Treatments', 'value' => $events['Treatment'] ?? 0],
                    ['label' => 'Care notes', 'value' => $events['Care Note'] ?? 0],
                ]);
                echo '<div class="rc-card-grid">';
                report_render_breakdown('Event types', $events);
                report_render_breakdown('Care by species', report_count_by($rows, ['Animal Species']));
                report_render_breakdown('Recorded by', report_count_by($rows, ['Recorded By']));
                echo '</div>';
                break;

            case 'NOTIFIABLE_INCIDENTS':
                report_render_stat_grid([
                    ['label' => 'Potential incidents', 'value' => $totalRows],
                    ['label' => 'Categories', 'value' => count(report_count_by($rows, ['Notifiable Category (Derived)']))],
                    ['label' => 'Species involved', 'value' => count(report_count_by($rows, ['Animal Species']))],
                    ['label' => 'Needs review', 'value' => $totalRows, 'note' => 'Derived from presenting complaint text'],
                ]);
                echo '<div class="rc-card-grid">';
                report_render_breakdown('Incident categories', report_count_by($rows, ['Notifiable Category (Derived)']));
                report_render_breakdown('Species involved', report_count_by($rows, ['Animal Species']));
                echo '</div>';
                echo '<div class="rc-alert amber">These categories are derived automatically from presenting complaint keywords and should be reviewed before external submission.</div>';
                break;

            default:
                report_render_stat_grid([
                    ['label' => 'Rows returned', 'value' => $totalRows],
                    ['label' => 'Reporting period', 'value' => $from_date . ' to ' . $to_date],
                ]);
                break;
        }

        echo '</div>';
    }
}

if (!$centre_id) {
    echo '<div class="rc-alert red">❌ Centre context not loaded.</div>';
    return;
}
if (!$from_date || !$to_date) {
    echo '<div class="rc-alert red">❌ Reporting date range not set. Please set From/To dates above.</div>';
    return;
}

// ---------------------------
// ✅ LOAD MODULE LIST
// ---------------------------
$stmt = $pdo->prepare("
    SELECT code, name, description
    FROM rescue_reports_modules
    WHERE is_active = 1
    ORDER BY sort_order ASC, name ASC
");
$stmt->execute();
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$modules) {
    echo '<div class="rc-alert blue">No report modules are currently available.</div>';
    return;
}

// ---------------------------
// ✅ OPTIONAL: RUN SELECTED MODULE
// ---------------------------
$generatedModule = null;
$generatedRows   = null;
$localError      = null;

$shouldRun = ($tab === 'single' && ($_GET['run'] ?? null) && $_SERVER['REQUEST_METHOD'] === 'POST');

if ($shouldRun) {
    $module_code = trim($_POST['module_code'] ?? '');

    if ($module_code === '') {
        $localError = "Missing module code.";
    } else {
        // Load module with query path
        $modStmt = $pdo->prepare("
            SELECT code, name, description, query_path
            FROM rescue_reports_modules
            WHERE code = :code AND is_active = 1
            LIMIT 1
        ");
        $modStmt->execute([':code' => $module_code]);
        $generatedModule = $modStmt->fetch(PDO::FETCH_ASSOC);

        if (!$generatedModule) {
            $localError = "Unknown or inactive module: " . htmlspecialchars($module_code);
        } else {
            $query_path = $generatedModule['query_path'] ?? '';

            // Safety: block traversal
            if ($query_path === '' || strpos($query_path, '..') !== false) {
                $localError = "Invalid query path for module.";
            } else {
                // Expect query_path like "reporting/CASE_INDEX.sql"
                // This file is in views/reports/, so go up to project root:
                $sqlFile = __DIR__ . '/../../models/' . $query_path;

                if (!is_file($sqlFile)) {
                    $localError = "SQL file not found: " . htmlspecialchars($query_path);
                } else {
                    $sql = file_get_contents($sqlFile);

                    if ($sql === false || trim($sql) === '') {
                        $localError = "SQL file is empty: " . htmlspecialchars($query_path);
                    } else {
                        try {
                            $q = $pdo->prepare($sql);
                            $q->execute([
                                ':centre_id' => $centre_id,
                                ':from_date' => $from_date,
                                ':to_date'   => $to_date
                            ]);
                            $generatedRows = $q->fetchAll(PDO::FETCH_ASSOC);
                        } catch (Throwable $e) {
                            error_log("REPORT MODULE ERROR [{$module_code}]: " . $e->getMessage());
                            $localError = "Failed to generate module: " . $e->getMessage();

                        }
                    }
                }
            }
        }
    }
}

// ---------------------------
// ✅ DISPLAY ERRORS (LOCAL TO THIS TAB)
// ---------------------------
if ($localError) {
    echo '<div class="rc-alert red">❌ ' . htmlspecialchars($localError) . '</div>';
}
?>

<div class="rc-panel">
    <h3>Single Reports</h3>
    <p>
        Reporting window:
        <strong><?= htmlspecialchars($from_date) ?></strong>
        to
        <strong><?= htmlspecialchars($to_date) ?></strong>
    </p>
</div>

<div class="rc-panel">
    <div class="rc-table-scroll">
    <table class="rc-table row-hover">
        <thead>
            <tr>
                <th style="width:30%;">Module</th>
                <th style="width:20%;">Shortcode</th>
                <th>Description</th>
                <th style="width:160px; text-align:right;"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($modules as $m): ?>
            <tr>
                <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
                <td><code><?= htmlspecialchars($m['code']) ?></code></td>
                <td><?= nl2br(htmlspecialchars($m['description'] ?? '')) ?></td>
                <td style="text-align:right;">
                    <form method="post" action="report_pack.php" target="_blank" style="display:inline;">
                        <input type="hidden" name="module_codes[]" value="<?= htmlspecialchars($m['code']) ?>">
                        <button type="submit" class="btn green">Open Report</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<?php if (!empty($generatedModule)): ?>
    <div class="report-generated-header no-print" aria-hidden="true"></div>
    <section class="report-document">
        <?php if (empty($generatedRows)): ?>
            <div class="rc-alert blue">No data found for this period.</div>
        <?php else: ?>
            <?php report_render_meaningful_summary($generatedModule, $generatedRows, $from_date, $to_date); ?>

            <hr>
            <h4>Supporting Detail</h4>
            <p class="rc-muted">The table below is included for checking individual records behind the summary.</p>

            <div class="rc-table-scroll">
                <table class="rc-table row-hover">
                    <thead>
                        <tr>
                            <?php foreach (array_keys($generatedRows[0]) as $col): ?>
                                <th><?= htmlspecialchars($col) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($generatedRows as $r): ?>
                            <tr>
                                <?php foreach ($r as $val): ?>
                                    <td><?= htmlspecialchars((string)$val) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

