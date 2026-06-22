<?php
// ------------------------------------------------------------
// add_medsadmin.php
// Wrapper for medication administration
// ------------------------------------------------------------

// Expected vars from parent (patients.php):
// $patient_id, $patient_name, $pdo

// ------------------------------------------------------------
// Determine active tab (URL-driven, default = simple)
// ------------------------------------------------------------
$active_tab = $_GET['tab'] ?? 'simple';
if (!in_array($active_tab, ['simple', 'stock'], true)) {
    $active_tab = 'simple';
}

// ------------------------------------------------------------
// Shared lookups (used by both sub-forms)
// ------------------------------------------------------------
if (!function_exists('lite_column_exists')) {
    function lite_column_exists(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table
              AND COLUMN_NAME = :column
        ");
        $stmt->execute([':table' => $table, ':column' => $column]);
        return (int)$stmt->fetchColumn() > 0;
    }
}

$hasMedicationCatalogue = lite_column_exists($pdo, 'rescue_medications', 'medication_id')
    && lite_column_exists($pdo, 'rescue_medications', 'medication_name');
$hasStockCatalogue = $hasMedicationCatalogue
    && lite_column_exists($pdo, 'rescue_stock_medication', 'medication_profile_id')
    && lite_column_exists($pdo, 'rescue_medication_trans', 'med_profile_id');

// Master medication list (for simple mode)
$medications = [];
if ($hasMedicationCatalogue) {
    $medStmt = $pdo->query("
        SELECT medication_id, medication_name, COALESCE(common_name, '') AS common_name
        FROM rescue_medications
        ORDER BY medication_name ASC
    ");
} else {
    $medStmt = $pdo->query("
        SELECT med_profile_id AS medication_id, medication AS medication_name, '' AS common_name
        FROM rescue_medications
        ORDER BY medication ASC
    ");
}
$medications = $medStmt->fetchAll(PDO::FETCH_ASSOC);

// Stock profiles / batches (for stock mode)
$stock_items = [];
if ($hasStockCatalogue) {
    $stockStmt = $pdo->prepare("
        SELECT 
            t.med_trans_id,
            t.batch_number,
            t.expiry,
            sm.concentration_dose,
            sm.concentration_volume,
            rm.medication_name,
            COALESCE(rm.common_name, '') AS common_name
        FROM rescue_medication_trans t
        JOIN rescue_stock_medication sm ON t.med_profile_id = sm.medication_profile_id
        JOIN rescue_medications rm ON sm.medication = rm.medication_id
        WHERE t.centre_id = :cid
        ORDER BY rm.medication_name ASC, t.expiry ASC
    ");
    $stockStmt->execute([':cid' => $GLOBALS['centre_id'] ?? 0]);
    $stock_items = $stockStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!-- ========================= -->
<!-- MEDICATION ADMIN WRAPPER -->
<!-- ========================= -->

<h3>
    <?= htmlspecialchars($lang['LM_MEDICATION'] . ' ' . $lang['FOR'] . ' ' . $patient_name) ?>
</h3>

<!-- Tabs -->
<div class="rc-tabs">
    <a href="?open=medication&pid=<?= $patient_id ?>&tab=simple"
       class="rc-tab <?= $active_tab === 'simple' ? 'is-active' : '' ?>">
        <?= htmlspecialchars($lang['SIMPLE']) ?>
    </a>

    <a href="?open=medication&pid=<?= $patient_id ?>&tab=stock"
       class="rc-tab <?= $active_tab === 'stock' ? 'is-active' : '' ?>">
        <?= htmlspecialchars($lang['USE'] . ' ' . $lang['LM_STOCK_MANAGEMENT']) ?>
    </a>
</div>

<!-- Active tab content -->
<div class="rc-tab-panel is-active">

    <?php if ($active_tab === 'simple'): ?>

        <?php
        // SIMPLE MODE FORM
        // ------------------------------------------------
        include __DIR__ . '/medication/meds_simple.php';
        ?>

    <?php else: ?>

        <?php
        // STOCK MODE FORM
        // ------------------------------------------------
        include __DIR__ . '/medication/meds_stock.php';
        ?>

    <?php endif; ?>

</div>
