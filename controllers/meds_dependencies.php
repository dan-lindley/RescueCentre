<?php
/* ------------------ MEDICATION STOCK LOOKUP (GLOBAL) ------------------ */

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

$all_stock_items = [];

if ($hasStockCatalogue) {
    $stockStmt = $pdo->prepare("
        SELECT 
            t.med_trans_id,
            t.batch_number,
            t.expiry,
            t.est_volume,

            COALESCE(m.common_name, '') AS common_name,
            m.medication_name,

            sm.concentration_dose,
            sm.concentration_volume

        FROM rescue_medication_trans t
        INNER JOIN rescue_stock_medication sm
            ON t.med_profile_id = sm.medication_profile_id
        INNER JOIN rescue_medications m
            ON sm.medication = m.medication_id
        WHERE t.centre_id = :centre_id
        ORDER BY COALESCE(NULLIF(m.common_name, ''), m.medication_name) ASC, t.batch_number ASC
    ");

    $stockStmt->execute([':centre_id' => $centre_id]);
    $all_stock_items = $stockStmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ------------------ MASTER MEDICATION LIST LOOKUP (GLOBAL) ------------------ */

$all_medications = [];

if ($hasMedicationCatalogue) {
    $medListStmt = $pdo->prepare("
        SELECT 
            medication_id,
            medication_name,
            COALESCE(class, '') AS class,
            COALESCE(common_name, '') AS common_name
        FROM rescue_medications
        ORDER BY class ASC, medication_name ASC
    ");
} else {
    $medListStmt = $pdo->prepare("
        SELECT
            med_profile_id AS medication_id,
            medication AS medication_name,
            '' AS class,
            '' AS common_name
        FROM rescue_medications
        ORDER BY medication ASC
    ");
}

$medListStmt->execute();
$all_medications = $medListStmt->fetchAll(PDO::FETCH_ASSOC); 

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
