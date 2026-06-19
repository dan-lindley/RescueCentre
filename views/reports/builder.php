<?php
if (!defined('APP_LOADED')) exit;

require_once __DIR__ . '/report_helpers.php';

$centre_id = (int)($REPORTING_CONTEXT['centre_id'] ?? 0);
$from_date = (string)($REPORTING_CONTEXT['from_date'] ?? '');
$to_date = (string)($REPORTING_CONTEXT['to_date'] ?? '');

if ($centre_id <= 0 || $from_date === '' || $to_date === '') {
    echo '<div class="rc-alert red">Reporting context is not available.</div>';
    return;
}

$stmt = $pdo->prepare("
    SELECT code, name, description, query_path
    FROM rescue_reports_modules
    WHERE is_active = 1
    ORDER BY sort_order ASC, name ASC
");
$stmt->execute();
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$selected = $_POST['module_codes'] ?? [];
if (!is_array($selected)) {
    $selected = [];
}
$selected = array_values(array_unique(array_map('strval', $selected)));
$shouldBuild = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'build_report');
?>

<div class="rc-panel no-print">
    <h3>Report Builder</h3>
    <p class="rc-muted">Select several report sections to generate a combined operational report for the current date range.</p>

    <form method="post" action="report_pack.php" target="_blank" class="xform">

        <div class="rc-card-grid">
            <?php foreach ($modules as $module): ?>
                <?php $code = (string)$module['code']; ?>
                <?php $inputId = 'report_module_' . preg_replace('/[^a-z0-9_]+/i', '_', strtolower($code)); ?>
                <label class="rc-card rc-select-card" for="<?= report_h($inputId) ?>">
                    <input class="rc-select-card-input" id="<?= report_h($inputId) ?>" type="checkbox" name="module_codes[]" value="<?= report_h($code) ?>" <?= in_array($code, $selected, true) ? 'checked' : '' ?>>
                    <div class="rc-inline-list">
                        <span class="rc-badge blue"><?= report_h($code) ?></span>
                        <span class="rc-item-main">
                            <strong><?= report_h($module['name']) ?></strong>
                        </span>
                    </div>
                    <?php if (!empty($module['description'])): ?>
                        <small class="rc-muted"><?= report_h($module['description']) ?></small>
                    <?php endif; ?>
                    <span class="rc-select-card-check" aria-hidden="true">&#10003;</span>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="xform-actions">
            <button type="submit" class="btn green">Generate Report Pack</button>
        </div>
    </form>
</div>

<?php if ($shouldBuild): ?>
    <?php if (empty($selected)): ?>
        <div class="rc-alert amber">Choose at least one report section.</div>
    <?php else: ?>
        <div class="report-pack-header no-print" aria-hidden="true"></div>

        <?php
        $modulesByCode = [];
        foreach ($modules as $module) {
            $modulesByCode[(string)$module['code']] = $module;
        }
        ?>

        <?php foreach ($selected as $code): ?>
            <?php if (empty($modulesByCode[$code])) continue; ?>
            <?php $module = $modulesByCode[$code]; ?>

            <section class="report-document">
                <h2 class="report-section-title"><?= report_h($module['name']) ?></h2>

                <?php
                try {
                    $rows = report_run_module($pdo, $module, $centre_id, $from_date, $to_date);
                    if (empty($rows)) {
                        echo '<div class="rc-alert blue">No data found for this section.</div>';
                    } else {
                        report_render_meaningful_summary($module, $rows, $from_date, $to_date);
                    }
                } catch (Throwable $e) {
                    echo '<div class="rc-alert red">Could not generate this section.</div>';
                    error_log('REPORT BUILDER ERROR [' . $code . ']: ' . $e->getMessage());
                }
                ?>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endif; ?>
