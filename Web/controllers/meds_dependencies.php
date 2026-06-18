<?php
/* ------------------ MEDICATION STOCK LOOKUP (GLOBAL) ------------------ */

$all_stock_items = [];

$stockStmt = $pdo->prepare("
    SELECT 
        t.med_trans_id,
        t.batch_number,
        t.expiry,
        t.est_volume,

        m.common_name,
        m.medication_name,

        sm.concentration_dose,
        sm.concentration_volume

    FROM rescue_medication_trans t

    /* Correct: trans.med_profile_id = stock_medication.medication_profile_id */
    INNER JOIN rescue_stock_medication sm
        ON t.med_profile_id = sm.medication_profile_id

    /* Correct: stock_medication.medication = medications.medication_id */
    INNER JOIN rescue_medications m
        ON sm.medication = m.medication_id

    WHERE t.centre_id = :centre_id
    ORDER BY m.common_name ASC, t.batch_number ASC
");


$stockStmt->execute([':centre_id' => $centre_id]);
$all_stock_items = $stockStmt->fetchAll(PDO::FETCH_ASSOC);

/* ------------------ MASTER MEDICATION LIST LOOKUP (GLOBAL) ------------------ */

$all_medications = [];

$medListStmt = $pdo->prepare("
    SELECT 
        medication_id,
        medication_name,
        class,
        common_name
    FROM rescue_medications
    ORDER BY class ASC, medication_name ASC
");

$medListStmt->execute();
$all_medications = $medListStmt->fetchAll(PDO::FETCH_ASSOC); 

// Stock profiles / batches (for stock mode)
$stock_items = [];
$stockStmt = $pdo->prepare("
    SELECT 
        t.med_trans_id,
        t.batch_number,
        t.expiry,
        sm.concentration_dose,
        sm.concentration_volume,
        rm.medication_name,
        rm.common_name
    FROM rescue_medication_trans t
    JOIN rescue_stock_medication sm ON t.med_profile_id = sm.medication_profile_id
    JOIN rescue_medications rm ON sm.medication = rm.medication_id
    WHERE t.centre_id = :cid
    ORDER BY rm.medication_name ASC, t.expiry ASC
");
$stockStmt->execute([':cid' => $GLOBALS['centre_id'] ?? 0]);
$stock_items = $stockStmt->fetchAll(PDO::FETCH_ASSOC);
?>