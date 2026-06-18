<?php
// ----------------------------------------------------------------------
// medication_handler.php
// URL-driven medication admin handler (weight-handler pattern)
// ----------------------------------------------------------------------

require_once __DIR__ . '/../../connection.php';

function medication_column_exists(PDO $pdo, string $table, string $column): bool {
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

/* -----------------------------------------
   redirect_with (copied verbatim from weight)
----------------------------------------- */
function redirect_with($params = []) {

    $ref = $_SERVER['HTTP_REFERER'] ?? '/patients.php';
    $url = parse_url($ref);

    $base = basename($url['path']);
    $script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $app_base = preg_replace('#/controllers/medication$#', '', $script_dir);
    $app_base = rtrim($app_base ?: '', '/');

    if ($base === 'patients.php') {
        $base = $app_base . '/patients.php';
    }

    if ($base === 'viewpatient.php') {
        $base = $app_base . '/viewpatient.php';
    }

    if ($base === 'medication.php') {
        $base = $app_base . '/medication.php';
    }

    if ($base === 'medicationstock.php') {
        $base = $app_base . '/medicationstock.php';
    }

    if (!empty($url['query'])) {
        parse_str($url['query'], $qs);
        if (isset($qs['patient_id'])) {
            $params['patient_id'] = $qs['patient_id'];
        }
        if (basename($url['path'] ?? '') === 'patients.php') {
            foreach (['area', 'location', 'zone', 'zone_id'] as $key) {
                if (isset($qs[$key]) && !isset($params[$key])) {
                    $params[$key] = $qs[$key];
                }
            }
        }
    }

    $query = http_build_query($params);
    $redirect = $base . ($query ? "?$query" : "");

    header("Location: $redirect");
    exit;
}

// Must be POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with(['error' => 'Invalid request.']);
}

/* ============================================================
   SOFT DELETE PRESCRIPTION / MEDICATION ADMINISTRATION
============================================================ */
if (isset($_POST['action']) && $_POST['action'] === 'soft_delete_prescription') {
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $prescription_id = (int)($_POST['prescription_id'] ?? 0);

    if ($patient_id <= 0 || $prescription_id <= 0) {
        redirect_with(['error' => 'Invalid prescription delete request.', 'open' => 'medication', 'pid' => $patient_id]);
    }

    if (!medication_column_exists($pdo, 'rescue_prescriptions', 'is_deleted')) {
        redirect_with(['error' => 'Prescription soft delete column is missing.', 'open' => 'medication', 'pid' => $patient_id]);
    }

    $stmt = $pdo->prepare("
        UPDATE rescue_prescriptions
        SET is_deleted = 1
        WHERE prescription_id = :id
          AND patient_id = :pid
        LIMIT 1
    ");
    $stmt->execute([':id' => $prescription_id, ':pid' => $patient_id]);

    redirect_with(['msg' => 'Prescription deleted.', 'open' => 'medication', 'pid' => $patient_id]);
}

if (isset($_POST['action']) && $_POST['action'] === 'soft_delete_medication_given') {
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $med_adm_id = (int)($_POST['med_adm_id'] ?? 0);

    if ($patient_id <= 0 || $med_adm_id <= 0) {
        redirect_with(['error' => 'Invalid medication delete request.', 'open' => 'medication', 'pid' => $patient_id]);
    }

    if (!medication_column_exists($pdo, 'rescue_medications_given', 'is_deleted')) {
        redirect_with(['error' => 'Medication soft delete column is missing.', 'open' => 'medication', 'pid' => $patient_id]);
    }

    $stmt = $pdo->prepare("
        UPDATE rescue_medications_given
        SET is_deleted = 1
        WHERE med_adm_id = :id
          AND patient_id = :pid
        LIMIT 1
    ");
    $stmt->execute([':id' => $med_adm_id, ':pid' => $patient_id]);

    redirect_with(['msg' => 'Medication administration deleted.', 'open' => 'medication', 'pid' => $patient_id]);
}

/* ============================================================
   SIMPLE MEDICATION MODE
   (no stock, no packs, audit only)
============================================================ */
if (isset($_POST['medicationform_simple'])) {

    $patient_id  = (int)($_POST['patient_id'] ?? 0);
    $med_text    = trim($_POST['medication_given'] ?? '');
    $dose        = trim($_POST['dose'] ?? '');
    $dose_type   = trim($_POST['dose_type'] ?? '');
    $vol_given   = (float)($_POST['volume_used'] ?? 0);
    $date_given  = $_POST['date_given'] ?? date('Y-m-d H:i:s');

    $centre_id   = (int)($_POST['centre_id'] ?? 0);
    $given_by    = $_POST['given_by'] ?? '';
    $given_by_id = (int)($_POST['given_by_id'] ?? 0);

    // ---- validation (simple only) ----
    if (
        !$patient_id ||
        $med_text === '' ||
        $dose === '' ||
        !$dose_type ||
        $vol_given <= 0
    ) {
        redirect_with([
            'error' => 'Missing required medication information.',
            'open'  => 'medication',
            'pid'   => $patient_id
        ]);
    }

    try {

        $stmt = $pdo->prepare("
            INSERT INTO rescue_medications_given (
                patient_id,
                medication_given,
                dose,
                dose_type,
                vol_given,
                date,
                centre_id,
                given_by,
                given_by_id
            ) VALUES (
                :pid, :med, :dose, :dtype, :vol,
                :dt, :cid, :by, :byid
            )
        ");

        $stmt->execute([
            ':pid'   => $patient_id,
            ':med'   => $med_text,
            ':dose'  => $dose,
            ':dtype' => $dose_type,
            ':vol'   => $vol_given,
            ':dt'    => $date_given,
            ':cid'   => $centre_id,
            ':by'    => $given_by,
            ':byid'  => $given_by_id
        ]);

    } catch (Exception $e) {

        redirect_with([
            'error' => 'Medication error: ' . $e->getMessage(),
            'open'  => 'medication',
            'pid'   => $patient_id
        ]);
    }

    redirect_with([
        'msg'  => 'Medication added successfully.',
        'open' => 'medication',
        'pid'  => $patient_id
    ]);
}

/* ============================================================
   ADD MEDICATION PROFILE (centre-level)
   Form posts: action=add_med_profile
   Table: rescue_stock_medication (per your schema)
============================================================ */
if (isset($_POST['action']) && $_POST['action'] === 'add_med_profile') {

    // expected globals already exist in your app
$centre_id = (int)($_POST['centre_id'] ?? 0);
$user_id   = (int)($_POST['user_id'] ?? 0);


    // form fields
    $medication_id              = (int)($_POST['medication_id'] ?? 0);
    $stock_form_id              = (int)($_POST['stock_form_id'] ?? 0);
    $concentration_dose         = (float)($_POST['concentration_dose'] ?? 0);
    $concentration_dose_type    = trim((string)($_POST['concentration_dose_type'] ?? 'mg'));
    $concentration_volume       = (float)($_POST['concentration_volume'] ?? 0);
    $concentration_volume_type  = trim((string)($_POST['concentration_volume_type'] ?? 'ml'));
    $pack_quantity              = (float)($_POST['pack_quantity'] ?? 0);
    $reorder_level              = (float)($_POST['reorder_level'] ?? 0);
    $use_within                 = (int)($_POST['use_within'] ?? 0);


    // If medication_id wasn't set by JS, try resolve from the typed text
if ($medication_id <= 0) {
    $lookup = trim((string)($_POST['medication_lookup'] ?? ''));
    if ($lookup !== '') {
        $m = $pdo->prepare("
            SELECT medication_id
            FROM rescue_medications
            WHERE common_name = :q OR medication_name = :q
            LIMIT 1
        ");
        $m->execute([':q' => $lookup]);
        $medication_id = (int)$m->fetchColumn();
    }
}

    // BEFORE redirecting with err=missing, show exactly what is missing
$missing = [];
if ($centre_id <= 0)               $missing[] = 'centre_id (global)';
if ($user_id <= 0)                 $missing[] = 'user_id (global)';
if ($medication_id <= 0)           $missing[] = 'medication_id (POST)';
if ($stock_form_id <= 0)           $missing[] = 'stock_form_id (POST)';
if ($concentration_dose <= 0)      $missing[] = 'concentration_dose (POST)';
if ($concentration_volume <= 0)    $missing[] = 'concentration_volume (POST)';
if ($pack_quantity <= 0)           $missing[] = 'pack_quantity (POST)';

if ($missing) {
    http_response_code(400);
    echo "ADD PROFILE FAILED — missing/invalid:\n";
    echo implode("\n", $missing);
    echo "\n\nPOST DUMP:\n";
    print_r($_POST);
    exit;
}
 
    // minimal required check (matches schema requirements)
    if ($centre_id <= 0 || $user_id <= 0 || $medication_id <= 0 || $stock_form_id <= 0 || $concentration_dose <= 0 || $concentration_volume <= 0 || $pack_quantity <= 0) {
        header('Location: ../../medicationstock.php?sub=profiles&err=missing');
        exit;
    }

    // server-side mg/ml calc (mg per ml)
    $dose_mg = $concentration_dose;
    if ($concentration_dose_type === 'g')   $dose_mg = $concentration_dose * 1000;
    if ($concentration_dose_type === 'mcg') $dose_mg = $concentration_dose / 1000;

    $mgml = null;
    if ($concentration_volume_type === 'ml' && $concentration_volume > 0) {
        $mgml = $dose_mg / $concentration_volume;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO rescue_stock_medication
            (
                medication,
                stock_form_id,
                concentration_dose,
                concentration_dose_type,
                concentration_volume,
                concentration_volume_type,
                pack_quantity,
                reorder_level,
                use_within,
                mgml,
                centre_id,
                user_id
            )
            VALUES
            (
                :medication,
                :stock_form_id,
                :conc_dose,
                :dose_type,
                :conc_vol,
                :vol_type,
                :pack_qty,
                :reorder,
                :use_within,
                :mgml,
                :centre_id,
                :user_id
            )
        ");

        $stmt->execute([
            ':medication'    => $medication_id,
            ':stock_form_id' => $stock_form_id,
            ':conc_dose'     => $concentration_dose,
            ':dose_type'     => $concentration_dose_type,
            ':conc_vol'      => $concentration_volume,
            ':vol_type'      => $concentration_volume_type,
            ':pack_qty'      => $pack_quantity,
            ':reorder'       => $reorder_level,
            ':use_within'    => $use_within,
            ':mgml'          => $mgml,
            ':centre_id'     => $centre_id,
            ':user_id'       => $user_id
        ]);

        // optional audit if your handler already includes audit.php + functions
        if (function_exists('audit_write')) {
            audit_write($pdo, 'create', 'medication_profile', (int)$pdo->lastInsertId(), [
                'centre_id' => $centre_id,
                'medication_id' => $medication_id,
                'stock_form_id' => $stock_form_id
            ]);
        }

        header('Location: ../../medicationstock.php?sub=profiles&success=1');
        exit;

    } catch (Throwable $e) {
        header('Location: ../../medicationstock.php?sub=profiles&err=save');
        exit;
    }
}


/* ============================================================
   STOCK MEDICATION MODE (SINGLE SOURCE OF TRUTH)
   - shortfall detection + finish pack + continue
   - sealed -> opened on first real use
   - finished when remaining hits 0
   - server-side enforcement
============================================================ */
if (isset($_POST['medicationform_stock'])) {

    /* -----------------------------------------
       COLLECT FIELDS
    ----------------------------------------- */
    $patient_id   = (int)($_POST['patient_id'] ?? 0);
    $med_trans_id = (int)($_POST['stock_item_used'] ?? 0);
    $pack_id      = (int)($_POST['pack_id'] ?? 0);
    $vol_given    = (float)($_POST['volume_used'] ?? 0);

    $date_given   = $_POST['date_given'] ?? date('Y-m-d H:i:s');
    $dose         = trim($_POST['dose'] ?? '');
    $dose_type    = trim($_POST['dose_type'] ?? 'mg');

    $given_by     = $_POST['given_by'] ?? '';
    $given_by_id  = (int)($_POST['given_by_id'] ?? 0);
    $centre_id    = (int)($_POST['centre_id'] ?? 0);

    $batch_given  = $_POST['bn_given'] ?? '';
    $exp_given    = $_POST['exp_given'] ?? '';

    $finish_pack        = (isset($_POST['finish_pack']) && $_POST['finish_pack'] === '1');
    $finish_pack_id     = (int)($_POST['finish_pack_id'] ?? 0);
    $finish_pack_amount = (float)($_POST['finish_pack_amount'] ?? 0);
    $remaining_dose     = (float)($_POST['remaining_dose'] ?? 0);

    /* -----------------------------------------
       BASIC VALIDATION
    ----------------------------------------- */
    if (
        !$patient_id ||
        !$med_trans_id ||
        !$pack_id ||
        $dose === '' ||
        !$dose_type ||
        $vol_given <= 0
    ) {
        redirect_with([
            'error' => 'Missing or invalid medication data.',
            'open'  => 'medication',
            'pid'   => $patient_id
        ]);
    }

    /* -----------------------------------------
       LOAD PACK
    ----------------------------------------- */
    $stmt = $pdo->prepare("
        SELECT amount_remaining, status
        FROM rescue_medication_packs
        WHERE pack_id = :pid
    ");
    $stmt->execute([':pid' => $pack_id]);
    $pack = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pack) {
        redirect_with([
            'error' => 'Invalid medication pack selected.',
            'open'  => 'medication',
            'pid'   => $patient_id
        ]);
    }

    $remaining = (float)$pack['amount_remaining'];
    $status    = strtolower(trim($pack['status'] ?? ''));

    /* -----------------------------------------
       HARD BLOCK: only if marked finished
       (as requested — NOT based on remaining ml)
    ----------------------------------------- */
    if ($status === 'finished') {
        redirect_with([
            'error' => 'This pack is marked finished and cannot be used.',
            'open'  => 'medication',
            'pid'   => $patient_id
        ]);
    }

    /* -----------------------------------------
       PHASE 2: FINISH PACK & CONTINUE
       - also sets date_opened if it was never opened
    ----------------------------------------- */
    if ($finish_pack && $finish_pack_id > 0 && $finish_pack_amount > 0) {

        $pdo->prepare("
            UPDATE rescue_medication_packs
            SET amount_remaining = 0,
                status = 'finished',
                date_finished = :dt,
                date_opened = COALESCE(date_opened, :dt)
            WHERE pack_id = :pid
        ")->execute([
            ':dt'  => $date_given,
            ':pid' => $finish_pack_id
        ]);

        // Recalc batch volume
        $sum = $pdo->prepare("
            SELECT SUM(amount_remaining)
            FROM rescue_medication_packs
            WHERE med_trans_id = :mid
        ");
        $sum->execute([':mid' => $med_trans_id]);
        $total_remaining = (float)$sum->fetchColumn();

        $pdo->prepare("
            UPDATE rescue_medication_trans
            SET est_volume = :vol
            WHERE med_trans_id = :mid
        ")->execute([
            ':vol' => $total_remaining,
            ':mid' => $med_trans_id
        ]);

        redirect_with([
            'med_continue' => 1,
            'remaining'    => $remaining_dose,
            'open'         => 'medication',
            'pid'          => $patient_id
        ]);
    }

    /* -----------------------------------------
       PHASE 1: SHORTFALL DETECTION
       (do NOT open pack here; no deduction happened yet)
    ----------------------------------------- */
    if ($vol_given > $remaining) {

        redirect_with([
            'med_shortfall' => 1,
            'available'     => $remaining,
            'needed'        => $vol_given,
            'still_needed'  => ($vol_given - $remaining),
            'pack_id'       => $pack_id,
            'med_trans_id'  => $med_trans_id,
            'open'          => 'medication',
            'pid'           => $patient_id
        ]);
    }

    /* -----------------------------------------
       NORMAL DEDUCTION
       - sealed -> opened on first use (and date_opened set)
       - finished when hits 0 (and date_finished set)
    ----------------------------------------- */
    $pdo->prepare("
        UPDATE rescue_medication_packs
        SET
            amount_remaining = amount_remaining - :used,
            status = CASE
                WHEN (amount_remaining - :used) <= 0 THEN 'finished'
                WHEN status = 'sealed' THEN 'opened'
                ELSE status
            END,
            date_opened = CASE
                WHEN status = 'sealed' THEN COALESCE(date_opened, :dt)
                ELSE date_opened
            END,
            date_finished = CASE
                WHEN (amount_remaining - :used) <= 0 THEN :dt
                ELSE date_finished
            END
        WHERE pack_id = :pid
    ")->execute([
        ':used' => $vol_given,
        ':dt'   => $date_given,
        ':pid'  => $pack_id
    ]);

    // Recalc batch
    $sum = $pdo->prepare("
        SELECT SUM(amount_remaining)
        FROM rescue_medication_packs
        WHERE med_trans_id = :mid
    ");
    $sum->execute([':mid' => $med_trans_id]);
    $total_remaining = (float)$sum->fetchColumn();

    $pdo->prepare("
        UPDATE rescue_medication_trans
        SET est_volume = :vol
        WHERE med_trans_id = :mid
    ")->execute([
        ':vol' => $total_remaining,
        ':mid' => $med_trans_id
    ]);

    /* -----------------------------------------
       INSERT MEDICATION GIVEN (AUDIT)
    ----------------------------------------- */

    // Resolve medication name (TEXT) from profile
    $nameStmt = $pdo->prepare("
        SELECT rm.medication_name
        FROM rescue_medication_trans t
        JOIN rescue_stock_medication sm ON t.med_profile_id = sm.medication_profile_id
        JOIN rescue_medications rm ON sm.medication = rm.medication_id
        WHERE t.med_trans_id = :mid
    ");
    $nameStmt->execute([':mid' => $med_trans_id]);
    $medication_name = $nameStmt->fetchColumn();

    $pdo->prepare("
        INSERT INTO rescue_medications_given (
            patient_id,
            medication_given,
            dose,
            dose_type,
            date,
            centre_id,
            given_by,
            given_by_id,
            vol_given,
            batch_given,
            exp_given,
            stock_item_used,
            pack_used
        ) VALUES (
            :pid, :med, :dose, :dtype, :dt,
            :cid, :by, :byid, :vol, :batch, :exp, :trans, :pack
        )
    ")->execute([
        ':pid'   => $patient_id,
        ':med'   => $medication_name,
        ':dose'  => $dose,
        ':dtype' => $dose_type,
        ':dt'    => $date_given,
        ':cid'   => $centre_id,
        ':by'    => $given_by,
        ':byid'  => $given_by_id,
        ':vol'   => $vol_given,
        ':batch' => $batch_given,
        ':exp'   => $exp_given,
        ':trans' => $med_trans_id,
        ':pack'  => $pack_id
    ]);

    redirect_with([
        'msg'  => 'Medication administered successfully.',
        'open' => 'medication',
        'pid'  => $patient_id
    ]);
}

/* If we got here, the POST didn't match either known form */
redirect_with([
    'error' => 'Invalid medication request.',
    'open'  => 'medication'
]);
