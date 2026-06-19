<?php
// ------------------------------------------------------------
// get_packs.php
// Returns all packs belonging to a specific medication batch
// (med_trans_id). Output is always valid JSON.
// ------------------------------------------------------------

require_once __DIR__ . '/../../connection.php';

// Force JSON header
header('Content-Type: application/json');

// ------------------------------------------------------------
// 1. Validate input
// ------------------------------------------------------------
$med_trans_id = $_GET['med_trans_id'] ?? null;

if (!$med_trans_id || !is_numeric($med_trans_id)) {
    echo json_encode([
        "error" => "Missing or invalid med_trans_id",
        "packs" => []
    ]);
    exit;
}

// ------------------------------------------------------------
// 2. Query packs for this med_trans_id
// ------------------------------------------------------------

$sql = "
    SELECT 
        p.pack_id,
        p.amount_remaining,
        p.status,
        t.batch_number,
        t.expiry,
        m.medication_name,
        sf.value_unit AS unit,

        sm.concentration_dose,
        sm.concentration_dose_type,
        sm.concentration_volume,
        sm.concentration_volume_type

    FROM rescue_medication_packs p
    INNER JOIN rescue_medication_trans t
        ON p.med_trans_id = t.med_trans_id

    INNER JOIN rescue_stock_medication sm
        ON t.med_profile_id = sm.medication_profile_id

    INNER JOIN rescue_medications m
        ON sm.medication = m.medication_id

    INNER JOIN rescue_stock_forms sf
        ON sm.stock_form_id = sf.stock_form_id

    WHERE p.med_trans_id = :mid
      AND p.status NOT IN ('finished', 'destroyed')
    ORDER BY p.status DESC, p.pack_id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':mid' => $med_trans_id]);

$packs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ------------------------------------------------------------
// 3. Ensure valid JSON output
// ------------------------------------------------------------
echo json_encode([
    "med_trans_id" => (int)$med_trans_id,
    "count"        => count($packs),
    "packs"        => $packs
]);
exit;
