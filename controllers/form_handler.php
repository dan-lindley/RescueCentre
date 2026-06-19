<?php
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../getuserinfo.php';
include_once __DIR__ . '/../operations/audit.php';
include_once __DIR__ . '/../operations/transfers_log.php';
transfers_auto($pdo);
audit_auto($pdo);

function redirect_with($params = []) {

    // Determine referrer, fallback to patients.php
    $ref = $_SERVER['HTTP_REFERER'] ?? '/patients.php';
    $url = parse_url($ref);

    // ALWAYS remove directories — keep only filename
    $base = basename($url['path']);

    // If base resolved to something inside /controllers/, FIX IT
    // because the file actually lives in the web root.
    if ($base === 'patients.php') {
        $base = '/patients.php';
    }

    if ($base === 'viewpatient.php') {
        $base = '/viewpatient.php';
    }

    if ($base === 'editpatient.php') {
        $base = '/editpatient.php';
    }

    if ($base === 'module.php') {
        $base = '/module.php';
    }

    // Preserve patient_id when coming from viewpatient.php
    if (!empty($url['query'])) {
        parse_str($url['query'], $qs);
        if (isset($qs['patient_id'])) {
            $params['patient_id'] = $qs['patient_id'];
        }
        if ($base === 'patients.php' || $base === '/patients.php') {
            foreach (['area', 'location', 'zone', 'zone_id'] as $key) {
                if (isset($qs[$key]) && !isset($params[$key])) {
                    $params[$key] = $qs[$key];
                }
            }
        }
        if (($base === 'editpatient.php' || $base === '/editpatient.php') && ($_POST['formdisp'] ?? null) !== null) {
            $params['sid'] = 8;
        }
        if ($base === 'module.php' || $base === '/module.php') {
            if (isset($qs['module'])) {
                $params['module'] = $qs['module'];
            }
            if (isset($qs['view'])) {
                $params['view'] = $qs['view'];
            }
        }
    }

    // Build final URL
    $query = http_build_query($params);
    $redirect = $base . ($query ? "?$query" : "");

    header("Location: $redirect");
    exit;
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ------------------------------------------------------------
    // CSRF rollout switchboard (per-form enforcement)
    // ------------------------------------------------------------
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // 1) Define which form keys require CSRF today
    //    Add entries as you migrate forms.
    $CSRF_REQUIRED = [
        'care_note_form' => true,    // Care note form
        'formdisp' => true,          // Discharge form
        'addlabsform' => true,      // Add labs form 
        'observationform' => true,  // Add observation form
        'prescriptionform' => true, // Add prescription form
        'treatmentform' => true,   // Add treatment form
    ];

    // 2) Identify which form was submitted (first matching key)
    $submitted_key = null;
    foreach ($CSRF_REQUIRED as $key => $required) {
        if (isset($_POST[$key])) {
            $submitted_key = $key;
            break;
        }
    }

    // 3) If the submitted form is flagged true, enforce CSRF
    if ($submitted_key !== null && !empty($CSRF_REQUIRED[$submitted_key])) {

        $posted_token  = $_POST['csrf_token'] ?? '';
        $session_token = $_SESSION['csrf_token'] ?? '';

        if (!$posted_token || !$session_token || !hash_equals($session_token, $posted_token)) {
            redirect_with([
                'error' => 'Security check failed. Please refresh and try again.',
                // optional: preserve these if present
                'open'  => $_POST['open'] ?? null,
                'pid'   => $_POST['patient_id'] ?? null
            ]);
        }
    }

    /* -----------------------------------------
       ADD MEASUREMENT
    ------------------------------------------ */
    if (isset($_POST['addmeasurementForm'])) {

        $patient_id            = $_POST["measurement_thepatientid"];
        $add_measurement       = $_POST["measurement"];
        $add_measurement_unit  = $_POST["measurement_unit"];
        $add_date              = $_POST["date"];

        if (!$patient_id || $add_measurement === '' || $add_measurement === null) {
            redirect_with([
                'error' => 'Missing required measurement information.',
                'open'  => 'measurement',
                'pid'   => $patient_id
            ]);
        }

        try {
            $statement = $pdo->prepare(
                'INSERT INTO rescue_measurements
                 (patient_id, measurement, measurement_unit, date)
                 VALUES (:patient_id, :measurement, :measurement_unit, :date)'
            );

            $statement->execute([
                'patient_id'        => $patient_id,
                'measurement'       => $add_measurement,
                'measurement_unit'  => $add_measurement_unit,
                'date'              => $add_date
            ]);

        } catch (Exception $e) {
            redirect_with([
                'error' => 'Error adding measurement: ' . $e->getMessage(),
                'open'  => 'measurement',
                'pid'   => $patient_id
            ]);
        }

        redirect_with([
            'msg'  => 'Measurement added successfully.',
            'open' => 'measurement',
            'pid'  => $patient_id
        ]);
    }

    /* -----------------------------------------
       ADD WEIGHT (URL-driven version)
    ----------------------------------------- */
    if (isset($_POST['addweightForm'])) {

        $pid   = (int)($_POST["patient_id"] ?? 0);
        $weight = $_POST["weight"] ?? '';
        $unit   = $_POST["weight_unit"] ?? 'g';
        $date   = $_POST["date"] ?? date('Y-m-d H:i:s');

        if (!$pid || !$weight) {
            redirect_with([
                'error' => 'Missing required weight information.',
                'open'  => 'weight',
                'pid'   => $pid
            ]);
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO rescue_weights (patient_id, weight, weight_unit, date)
                VALUES (:pid, :weight, :unit, :date)
            ");

            $stmt->execute([
                ':pid'   => $pid,
                ':weight'=> $weight,
                ':unit'  => $unit,
                ':date'  => $date
            ]);

        } catch (Exception $e) {

            redirect_with([
                'error' => 'DB error: ' . $e->getMessage(),
                'open'  => 'weight',
                'pid'   => $pid
            ]);
        }

        // SUCCESS → return to the origin page
        // If origin was patients.php → auto-opens weight for this patient
        redirect_with([
            'msg'  => 'Weight added successfully.',
            'open' => 'weight',
            'pid'  => $pid
        ]);
    }


/* -----------------------------------------
   CHANGE PATIENT LOCATION
------------------------------------------ */
if (isset($_POST['changelocationform'])) {

    $patient_id      = (int)($_POST['patient_id'] ?? 0);
    $admission_id    = (int)($_POST['admission_id'] ?? 0);
    $new_location_id = (int)($_POST['new_location_id'] ?? 0);

    if (!$patient_id || !$admission_id || !$new_location_id) {
        redirect_with([
            'error' => 'Missing required location information.',
            'pid'   => $patient_id
        ]);
    }

    try {

        // Get old location id (for logging)
        $oldStmt = $pdo->prepare("
            SELECT current_location_id
            FROM rescue_admissions
            WHERE admission_id = :admission_id
              AND patient_id   = :patient_id
            LIMIT 1
        ");
        $oldStmt->execute([
            ':admission_id' => $admission_id,
            ':patient_id'   => $patient_id
        ]);
        $old_location_id = (int)($oldStmt->fetchColumn() ?? 0);

        // Look up the location name by ID (scoped to centre)
        $locStmt = $pdo->prepare("
            SELECT location_name
            FROM rescue_locations
            WHERE location_id = :location_id
              AND centre_id   = :centre_id
              AND deleted     = 0
            LIMIT 1
        ");
        $locStmt->execute([
            ':location_id' => $new_location_id,
            ':centre_id'   => $centre_id
        ]);

        $new_location_name = $locStmt->fetchColumn();

        if (!$new_location_name) {
            redirect_with([
                'error' => 'Invalid location selected.',
                'pid'   => $patient_id
            ]);
        }

        // Update admission with BOTH id + name
        $statement = $pdo->prepare(
            'UPDATE rescue_admissions
             SET current_location_id = :location_id,
                 current_location    = :location_name
             WHERE admission_id = :admission_id
               AND patient_id   = :patient_id
             LIMIT 1'
        );

        $statement->execute([
            'location_id'   => $new_location_id,
            'location_name' => $new_location_name,
            'admission_id'  => $admission_id,
            'patient_id'    => $patient_id
        ]);

        // Log internal move (only after successful update)
        transfers_log($pdo, 'internal_move', [
            'patient_id'       => $patient_id,
            'admission_id'     => $admission_id,
            'from_location_id' => $old_location_id ?: null,
            'to_location_id'   => $new_location_id
        ]);

    } catch (Exception $e) {
        redirect_with([
            'error' => 'Error updating patient location: ' . $e->getMessage(),
            'pid'   => $patient_id
        ]);
    }

    redirect_with([
        'msg' => 'Patient location updated successfully.',
        'pid' => $patient_id
    ]);
}


    /* -----------------------------------------
       ADD LABS
    ------------------------------------------ */
if (isset($_POST['addlabsform'])) {

    $add_lab_date         = $_POST["lab_date"];
    $add_sample_type      = $_POST["sample_type"];
    $add_lab_result       = $_POST["lab_result"];
    $add_reported_by      = $_POST["reported_by"];
    $add_lab_test         = $_POST["lab_test"];
    $add_lab_centre_id    = $_POST["centre_id"];
    $add_lab_patient_id   = $_POST["patient_id"];
    $add_lab_admission_id = $_POST["admission_id"];

    // NEW: is_positive (default 0, only allow 0/1)
    $add_is_positive = isset($_POST['is_positive']) && (int)$_POST['is_positive'] === 1 ? 1 : 0;

    if (!$add_lab_patient_id || !$add_lab_centre_id || !$add_lab_admission_id) {
        redirect_with([
            'error' => 'Missing required lab information.',
            'open'  => 'labs',
            'pid'   => $add_lab_patient_id
        ]);
    }

    try {
        $statement = $pdo->prepare(
            'INSERT INTO rescue_labs
             (patient_id, centre_id, admission_id, lab_date, sample_type, lab_result, reported_by, lab_test, is_positive)
             VALUES (:patient_id, :centre_id, :admission_id, :lab_date, :sample_type, :lab_result, :reported_by, :lab_test, :is_positive)'
        );

        $statement->execute([
            'patient_id'   => $add_lab_patient_id,
            'centre_id'    => $add_lab_centre_id,
            'admission_id' => $add_lab_admission_id,
            'lab_date'     => $add_lab_date,
            'sample_type'  => $add_sample_type,
            'lab_result'   => $add_lab_result,
            'reported_by'  => $add_reported_by,
            'lab_test'     => $add_lab_test,
            'is_positive'  => $add_is_positive
        ]);

    } catch (Exception $e) {
        redirect_with([
            'error' => 'Error adding lab result: ' . $e->getMessage(),
            'open'  => 'labs',
            'pid'   => $add_lab_patient_id
        ]);
    }

    redirect_with([
        'msg'  => 'Lab test added successfully.',
        'open' => 'labs',
        'pid'  => $add_lab_patient_id
    ]);

}

/*----------------------------------------------
| ADD TREATMENT
----------------------------------------------*/
if (isset($_POST['treatmentform'])) {

    $patient_id   = $_POST['patient_id'];
    $treatment    = $_POST['treatment'];
    $done_by      = $_POST['done_by'];
    $notes        = $_POST['treatment_free_text'];
    $date         = date('Y-m-d H:i:s');

    if (!$patient_id || !$treatment) {
        redirect_with([
            'error' => 'Missing required treatment information.',
            'open'  => 'treatment',
            'pid'   => $patient_id
        ]);
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO rescue_treatments
                (patient_id, treatment, treatment_free_text, done_by, date)
            VALUES
                (:patient_id, :treatment, :notes, :done_by, :date)
        ");

        $stmt->execute([
            ':patient_id' => $patient_id,
            ':treatment'  => $treatment,
            ':notes'      => $notes,
            ':done_by'    => $done_by,
            ':date'       => $date
        ]);

        redirect_with([
            'msg'  => 'Treatment added successfully.',
            'open' => 'treatment',
            'pid'  => $patient_id
        ]);
    }
    catch (Exception $e) {
        redirect_with([
            'error' => 'Error adding treatment: ' . $e->getMessage(),
            'open'  => 'treatment',
            'pid'   => $patient_id
        ]);
    }
}

/* -----------------------------------------------------
   OBSERVATIONS FORM HANDLER
----------------------------------------------------- */

if (isset($_POST['observationform'])) {

    $patient_id   = $_POST['patient_id'];
    $admission_id = $_POST['admission_id'];
    $obs_user_id  = $_POST['obs_user_id'];

    $obs_sev_text = $_POST['obs_sev_text'];
    $obs_bcs_text = $_POST['obs_bcs_text'];
    $obs_age_text = $_POST['obs_age_text'];
    $obs_notes    = $_POST['obs_notes'];

    $date = date('Y-m-d H:i:s');

    /* AGE SCORE */
    $age_map = [
        'Newborn'              => 3,
        'Dependent Juvenile'   => 2,
        'Independent Juvenile' => 1,
        'Hatchling'            => 3,
        'Fledgling'            => 2,
        'Adult'                => 0
    ];
    $obs_age_sc = $age_map[$obs_age_text] ?? 0;

    /* SEVERITY SCORE */
    $sev_map = [
        'Apparently Healthy' => 0,
        'Mildly unwell'      => 0,
        'Obvious Injuries'   => 1,
        'Severe Injuries'    => 2,
        'Near Death'         => 3
    ];
    $obs_sev_sc = $sev_map[$obs_sev_text] ?? 0;

    /* BCS SCORE */
    $bcs_map = [
        'BCS 1 Skeletal'           => 3,
        'BCS 2 Underweight'        => 2,
        'BCS 3 Slightly Underweight' => 1,
        'BCS 4 Healthy'            => 0,
        'BCS 5 Overweight'         => 0
    ];
    $obs_bcs_sc = $bcs_map[$obs_bcs_text] ?? 0;

    /* Insert the record */
    $stmt = $pdo->prepare("
        INSERT INTO rescue_observations
        (patient_id, admission_id, user_id,
        obs_severity_score, obs_severity_text,
        obs_bcs_score, obs_bcs_text,
        obs_age_score, obs_age_text,
        obs_notes, obs_date)
        VALUES
        (:patient_id, :admission_id, :user_id,
        :sev_sc, :sev_text,
        :bcs_sc, :bcs_text,
        :age_sc, :age_text,
        :notes, :date)
    ");

    $stmt->execute([
        'patient_id' => $patient_id,
        'admission_id' => $admission_id,
        'user_id' => $obs_user_id,
        'sev_sc' => $obs_sev_sc,
        'sev_text' => $obs_sev_text,
        'bcs_sc' => $obs_bcs_sc,
        'bcs_text' => $obs_bcs_text,
        'age_sc' => $obs_age_sc,
        'age_text' => $obs_age_text,
        'notes' => $obs_notes,
        'date' => $date
    ]);

        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
}

/*--------------------------- FORM PROCESSING: PRESCRIPTION -----------------------*/
if (isset($_POST['prescriptionform'])) {

    $patient_id   = $_POST["patient_id"];
    $centre_id    = $_POST["centre_id"];
    $admission_id = $_POST["admission_id"];
    $user_id      = $_POST["user_id"];

    // Form fields from xform
    $medication   = $_POST["medication"]; // (hidden autocomplete value)
    $dose         = $_POST["dose"];
    $dose_type    = $_POST["dose_type"];
    $duration     = $_POST["duration"];
    $frequency    = $_POST["frequency"];
    $route        = $_POST["route"];
    $date         = $_POST["date"];
    $dose_by_weight = $_POST['dose_by_weight'] ? 1 : 0;


    if (!$patient_id || !$centre_id || !$admission_id || !$user_id) {
        redirect_with([
            'error' => 'Missing required prescription information.',
            'open'  => 'prescription',
            'pid'   => $patient_id
        ]);
    }

    try {

        /* INSERT INTO rescue_prescriptions */
        $stmt = $pdo->prepare("
            INSERT INTO rescue_prescriptions
            (
                patient_id,
                centre_id,
                admission_id,
                user_id,
                medication,
                dose,
                dose_type,
                duration,
                frequency,
                route,
                by_weight,
                date
            )
            VALUES
            (
                :patient_id,
                :centre_id,
                :admission_id,
                :user_id,
                :medication,
                :dose,
                :dose_type,
                :duration,
                :frequency,
                :route,
                :dose_by_weight,
                :date
            )
        ");

        $stmt->execute([
            ':patient_id'   => $patient_id,
            ':centre_id'    => $centre_id,
            ':admission_id' => $admission_id,
            ':user_id'      => $user_id,
            ':medication'   => $medication,
            ':dose'         => $dose,
            ':dose_type'    => $dose_type,
            ':duration'     => $duration,
            ':frequency'    => $frequency,
            ':route'        => $route,
            ':dose_by_weight' => $dose_by_weight,
            ':date'         => $date

        ]);

    } catch (PDOException $e) {
        redirect_with([
            'error' => 'Database Error: The prescription could not be added. ' . $e->getMessage(),
            'open'  => 'prescription',
            'pid'   => $patient_id
        ]);
    } catch (Exception $e) {
        redirect_with([
            'error' => 'General Error: The prescription could not be added. ' . $e->getMessage(),
            'open'  => 'prescription',
            'pid'   => $patient_id
        ]);
    }

    redirect_with([
        'msg'  => 'Prescription added successfully.',
        'open' => 'prescription',
        'pid'  => $patient_id
    ]);
}
/*--------------------------- END PRESCRIPTION PROCESSING -----------------------*/

/*--------------------------- ASSIGN TASK ---------------------------*/
if (isset($_POST['taskassignform'])) {

    $task_id    = $_POST['task_id'];
    $patient_id = $_POST['patient_id'];
    $user_id    = $_SESSION['account_id'];  // logged-in user
    $now        = date("Y-m-d H:i:s");

    if (!$task_id || !$patient_id) {
        redirect_with([
            'error' => 'Missing required task assignment information.',
            'open'  => 'tasks',
            'pid'   => $patient_id
        ]);
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO rescue_tasks_patients
                (task_id, patient_id, status, set_date_time, set_by)
            VALUES
                (:task_id, :patient_id, 'Waiting', :dt, :uid)
        ");

        $stmt->execute([
            ':task_id'    => $task_id,
            ':patient_id' => $patient_id,
            ':dt'         => $now,
            ':uid'        => $user_id
        ]);

    } catch (Exception $e) {
        redirect_with([
            'error' => 'Error assigning task: ' . $e->getMessage(),
            'open'  => 'tasks',
            'pid'   => $patient_id
        ]);
    }

    redirect_with([
        'msg'  => 'Task assigned successfully.',
        'open' => 'tasks',
        'pid'  => $patient_id
    ]);
}

/*--------------------- COMPLETE TASK (AJAX) ---------------------*/
if (isset($_POST['complete_task'], $_POST['task_pt_id'])) {

    $task_pt_id = (int)$_POST['task_pt_id'];
    $user_id    = $_SESSION['account_id'];
    $now        = date("Y-m-d H:i:s");

    try {
        $stmt = $pdo->prepare("
            UPDATE rescue_tasks_patients
            SET 
                status = 'Completed',
                completed_by = :uid,
                completed_date_time = :dt
            WHERE task_pt_id = :id
              AND status <> 'Completed'
            LIMIT 1
        ");

        $stmt->execute([
            ':uid' => $user_id,
            ':dt'  => $now,
            ':id'  => $task_pt_id
        ]);

    } catch (Exception $e) {
        echo "Error";
        exit();
    }

    echo "OK";
    exit();
}

/*----------------------- DISCHARGE PATIENT -----------------------*/
if (isset($_POST['formdisp'])) {

    // Clean inputs (same sanitisation logic you used)
    function clean($v) {
        return htmlspecialchars(stripslashes(trim($v)));
    }

    function clean_datetime_local($v): string {
        $value = trim((string)$v);
        if ($value === '') {
            return '';
        }

        $value = str_replace('T', ' ', $value);
        $formats = ['Y-m-d H:i:s', 'Y-m-d H:i'];

        foreach ($formats as $format) {
            $dt = DateTime::createFromFormat($format, $value);
            if ($dt instanceof DateTime) {
                return $dt->format('Y-m-d H:i:s');
            }
        }

        return '';
    }

    // Admissions table fields
    $admission_id   = clean($_POST["theadmissionid"]);
    $centre_id      = clean($_POST["centre_id"]);
    $user_id        = clean($_POST["disposition_user"]);
    $comment        = clean($_POST["disposition_comment"]);
    $disposition_ui = clean($_POST["disposition"]); // UI value from select
    $euth_method    = clean($_POST["euthanasia_method"]);
    $disp_date      = clean_datetime_local($_POST["disposition_date"] ?? '');

    // Patient table field
    $patient_id     = clean($_POST["patient_id"]);

    if (!$admission_id || !$centre_id || !$user_id || !$patient_id || $disp_date === '') {
        redirect_with([
            'error' => 'Missing required discharge information.',
            'open'  => 'discharge',
            'pid'   => $patient_id
        ]);
    }

    /* ---------------- DISPOSITION → WORKFLOW LOGIC ----------------
       UI values (from select):
         - Held in captivity
         - Long-term captive
         - Released
         - Transferred to another rescue
         - Died - Euthanised
         - Died - within 48 hours
         - Died - after 48 hours
         - Died - on admission

       DB translations required:
         - "Transferred to another rescue" => "Transferred Out"
         - "Long-term captive"             => "Long-term Captive"
    */

    // Defaults = Held in captivity workflow
    $pat_status     = 'Captive';
    $pat_state      = 'Admitted';
    $adm_status     = 'Active';
    $adm_survived   = 1;
    $disposition_db = 'Held in captivity';

    // Died set (exact UI strings)
    $died_dispositions = [
        'Died - Euthanised',
        'Died - within 48 hours',
        'Died - after 48 hours',
        'Died - on admission'
    ];

    if ($disposition_ui === 'Released') {

        $pat_status     = 'Released';
        $pat_state      = 'Closed';
        $adm_status     = 'Closed';
        $adm_survived   = 1;
        $disposition_db = 'Released';

    } elseif ($disposition_ui === 'Transferred to another rescue') {

        $pat_status     = 'Transferred';
        $pat_state      = 'Closed';
        $adm_status     = 'Closed';
        $adm_survived   = 1;
        $disposition_db = 'Transferred Out'; // required dependency value

    } elseif ($disposition_ui === 'Long-term captive') {

        $pat_status     = 'Captive';
        $pat_state      = 'Admitted';
        $adm_status     = 'Active';
        $adm_survived   = 1;
        $disposition_db = 'Long-term Captive'; // required stored value

    } elseif (in_array($disposition_ui, $died_dispositions, true)) {

        $pat_status     = 'Deceased';
        $pat_state      = 'Deceased';
        $adm_status     = 'Closed';
        $adm_survived   = 0;
        $disposition_db = $disposition_ui; // store exact died variant

    } elseif ($disposition_ui === 'Held in captivity') {

        // Keep defaults, but make it explicit (safer than relying on fall-through)
        $pat_status     = 'Captive';
        $pat_state      = 'Admitted';
        $adm_status     = 'Active';
        $adm_survived   = 1;
        $disposition_db = 'Held in captivity';

    } elseif ($disposition_ui === 'Review') {

        // Administrative review queue. This removes the record from active patient lists
        // without deleting anything until a manager/owner reviews it.
        $pat_status     = 'Review';
        $pat_state      = 'Review';
        $adm_status     = 'Review';
        $adm_survived   = 1;
        $disposition_db = 'Review';

    } else {

        // Unknown disposition – don't silently treat as captivity
        redirect_with([
            'error' => 'Unknown disposition selected.',
            'open'  => 'discharge',
            'pid'   => $patient_id
        ]);
    }

    try {

        /* ---------------- UPDATE rescue_admissions ---------------- */
        $stmt1 = $pdo->prepare("
            UPDATE rescue_admissions
            SET
                euthanasia_method    = :euthanasia_method,
                disposition_user     = :disp_user,
                disposition_centre   = :disp_centre,
                disposition          = :disposition,
                disposition_date     = :disp_date,
                status               = :adm_status,
                survived             = :survived,
                disposition_comment  = :disp_comment
            WHERE admission_id = :admission_id
              AND patient_id = :patient_id
              AND centre_id = :centre_id
            LIMIT 1
        ");

        $stmt1->execute([
            ':euthanasia_method' => $euth_method,
            ':disp_user'         => $user_id,
            ':disp_centre'       => $centre_id,
            ':disposition'       => $disposition_db,
            ':disp_date'         => $disp_date,
            ':adm_status'        => $adm_status,
            ':survived'          => $adm_survived,
            ':disp_comment'      => $comment,
            ':admission_id'      => $admission_id,
            ':patient_id'        => $patient_id,
            ':centre_id'         => $centre_id
        ]);

        /* ---------------- UPDATE rescue_patients ---------------- */
        $stmt2 = $pdo->prepare("
            UPDATE rescue_patients
            SET
                status = :pat_status,
                state  = :pat_state
            WHERE patient_id = :patient_id
            LIMIT 1
        ");

        $stmt2->execute([
            ':pat_status' => $pat_status,
            ':pat_state'  => $pat_state,
            ':patient_id' => $patient_id
        ]);

    //entry for transfer log
    // Log final outcome once (only when admission is being closed)
if ($adm_status === 'Closed') {

    // Resolve disposition_id from rescue_dispositions (text matches UI values)
    $dispIdStmt = $pdo->prepare("
        SELECT disposition_id
        FROM rescue_dispositions
        WHERE disposition = :disp
        LIMIT 1
    ");
    $dispIdStmt->execute([':disp' => $disposition_ui]);
    $disposition_id = (int)($dispIdStmt->fetchColumn() ?? 0);

    // Canonical event type mapping
    $event_type = '';
    if ($disposition_ui === 'Released') {
        $event_type = 'released';
    } elseif ($disposition_ui === 'Transferred to another rescue') {
        $event_type = 'transfer_out';
    } elseif ($disposition_ui === 'Died - Euthanised') {
        $event_type = 'euthanised';
    } elseif (in_array($disposition_ui, $died_dispositions, true)) {
        $event_type = 'died';
    }

    if ($event_type !== '' && $disposition_id > 0) {

        // Prevent double-final logging for this admission
        $chk = $pdo->prepare("
            SELECT 1
            FROM rescue_transfers_log
            WHERE admission_id = :aid
              AND disposition_id IS NOT NULL
            LIMIT 1
        ");
        $chk->execute([':aid' => $admission_id]);

        if (!$chk->fetchColumn()) {

            // Get current location id (for from_location_id)
            $locStmt = $pdo->prepare("
                SELECT current_location_id
                FROM rescue_admissions
                WHERE admission_id = :admission_id
                  AND patient_id   = :patient_id
                  AND centre_id    = :centre_id
                LIMIT 1
            ");
            $locStmt->execute([
                ':admission_id' => $admission_id,
                ':patient_id'   => $patient_id,
                ':centre_id'    => $centre_id
            ]);
            $from_location_id = (int)($locStmt->fetchColumn() ?? 0);

            transfers_log($pdo, $event_type, [
                'patient_id'       => (int)$patient_id,
                'admission_id'     => (int)$admission_id,
                'event_at'         => $disp_date,
                'from_location_id' => ($from_location_id > 0 ? $from_location_id : null),
                'disposition_id'   => $disposition_id
            ]);
        }
    }
}


    } catch (Exception $e) {
        redirect_with([
            'error' => 'Error updating discharge: ' . $e->getMessage(),
            'open'  => 'discharge',
            'pid'   => $patient_id
        ]);
    }

    redirect_with([
        'msg'  => 'Discharge updated successfully.',
        'open' => 'discharge',
        'pid'  => $patient_id
    ]);
}

/*--------------------------- ADD CARE NOTE ---------------------------*/
if (isset($_POST['care_note_form'])) {

    // ---- Config ----
    $LEGACY_BASE = 'https://legacy.rescuecentre.org.uk/wp-content/themes/brikk-child/'; // not used here, but kept for consistency
    $NEW_BASE    = 'https://myrescuecentre.com/'; // not used here, but kept for consistency

    // Filesystem base (you said: public_html/reception/user_images)
    // This should be the directory that corresponds to https://myrescuecentre.com/user_images/
    $USER_IMAGES_FS_BASE = rtrim($_SERVER['DOCUMENT_ROOT'], '/')
        . '/user_images';

    $MAX_UPLOAD_BYTES = 3 * 1024 * 1024; // 3MB hard cap

    // Where we store new patient images (relative under /user_images)
    $PATIENT_IMAGES_REL_BASE = 'patient_images';

    function clean_text($v) {
        return trim((string)$v);
    }

    function slug_filename($name) {
        $name = trim((string)$name);
        $name = str_replace(["\0", "\r", "\n"], '', $name);
        // Keep letters/numbers/dot/dash/underscore, convert spaces to dashes
        $name = preg_replace('/\s+/', '-', $name);
        $name = preg_replace('/[^A-Za-z0-9\.\-\_]/', '', $name);
        $name = preg_replace('/\-+/', '-', $name);
        $name = trim($name, '-');
        if ($name === '') $name = 'image';
        return $name;
    }

    function ensure_dir($path) {
        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true) && !is_dir($path)) {
                throw new Exception("Failed to create directory: " . $path);
            }
        }
        if (!is_writable($path)) {
            throw new Exception("Upload directory not writable: " . $path);
        }
    }

    function image_from_upload($tmp, $mime) {
        if ($mime === 'image/jpeg') return imagecreatefromjpeg($tmp);
        if ($mime === 'image/png')  return imagecreatefrompng($tmp);
        if ($mime === 'image/webp') return imagecreatefromwebp($tmp);
        return false;
    }

    function save_as_jpeg_with_resize($srcImg, $srcW, $srcH, $destPath, $maxDim = 2000, $targetMaxBytes = 2000000) {
        // Compute scaled size preserving aspect ratio
        $scale = 1.0;
        $maxSide = max($srcW, $srcH);
        if ($maxSide > $maxDim) {
            $scale = $maxDim / $maxSide;
        }
        $newW = max(1, (int)round($srcW * $scale));
        $newH = max(1, (int)round($srcH * $scale));

        $dstImg = imagecreatetruecolor($newW, $newH);
        imageinterlace($dstImg, true);

        // White background (in case source has transparency)
        $white = imagecolorallocate($dstImg, 255, 255, 255);
        imagefilledrectangle($dstImg, 0, 0, $newW, $newH, $white);

        imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

        // Try a few quality steps to get under target size
        $qualities = [85, 80, 75, 70, 65, 60, 55];
        $ok = false;

        foreach ($qualities as $q) {
            imagejpeg($dstImg, $destPath, $q);
            clearstatcache(true, $destPath);
            $size = @filesize($destPath);
            if ($size !== false && $size <= $targetMaxBytes) {
                $ok = true;
                break;
            }
        }

        // If still big, keep last written file; it’s still capped by dimension.
        imagedestroy($dstImg);
        return $ok;
    }

    // ---- Inputs ----
    $patient_id = isset($_POST["patient_id"]) ? (int)$_POST["patient_id"] : 0;
    $message    = clean_text($_POST["new_note"] ?? '');
    $author     = clean_text($_POST["note_author"] ?? '');
    $public     = isset($_POST["public"]) ? 1 : 0;

    // Existing image selection (optional)
    $image_id = isset($_POST["image_id"]) ? (int)$_POST["image_id"] : 0;

    $date = date("Y-m-d H:i:s");

    if (!$patient_id || $message === '') {
        redirect_with([
            'error' => 'Missing required care note information.',
            'open'  => 'carenote',
            'pid'   => $patient_id
        ]);
    }

    // ---- Determine centre_id for path + DB insert ----
    // Prefer session centre_id if your app already sets it.
    $centre_id = isset($_SESSION['centre_id']) ? (int)$_SESSION['centre_id'] : 0;

    if ($centre_id <= 0) {
        // Fallback: derive from patient (safest, avoids trusting hidden fields)
        try {
            $cStmt = $pdo->prepare("SELECT centre_id FROM rescue_patients WHERE patient_id = :pid LIMIT 1");
            $cStmt->execute([':pid' => $patient_id]);
            $centre_id = (int)$cStmt->fetchColumn();
        } catch (Exception $e) {
            // keep 0, handled below
        }
    }

    if ($centre_id <= 0) {
        redirect_with([
            'error' => 'Unable to determine centre for this patient.',
            'open'  => 'carenote',
            'pid'   => $patient_id
        ]);
    }

    // ---- Optional upload handling ----
    $uploaded_image_id = 0;

    if (isset($_FILES['care_note_image']) && is_array($_FILES['care_note_image']) && ($_FILES['care_note_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {

        $f = $_FILES['care_note_image'];

        if (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            redirect_with([
                'error' => 'Image upload failed (error code ' . (int)$f['error'] . ').',
                'open'  => 'carenote',
                'pid'   => $patient_id
            ]);
        }

        if (($f['size'] ?? 0) > $MAX_UPLOAD_BYTES) {
            redirect_with([
                'error' => 'Image too large. Max upload is 3MB.',
                'open'  => 'carenote',
                'pid'   => $patient_id
            ]);
        }

        $tmp = $f['tmp_name'] ?? '';
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            redirect_with([
                'error' => 'Invalid upload detected.',
                'open'  => 'carenote',
                'pid'   => $patient_id
            ]);
        }

        $mime = @mime_content_type($tmp);
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];

        if (!$mime || !in_array($mime, $allowed, true)) {
            redirect_with([
                'error' => 'Unsupported image type. Please upload a JPG, PNG, or WebP.',
                'open'  => 'carenote',
                'pid'   => $patient_id
            ]);
        }

        $srcImg = image_from_upload($tmp, $mime);
        if (!$srcImg) {
            redirect_with([
                'error' => 'Could not read the uploaded image.',
                'open'  => 'carenote',
                'pid'   => $patient_id
            ]);
        }

        $srcW = imagesx($srcImg);
        $srcH = imagesy($srcImg);

        // Destination folder & filename (new mission-critical structure)
        $centreFolder  = 'centre_id_' . $centre_id;
        $patientFolder = 'patient_id_' . $patient_id;

        $destRelDir = 'user_images/' . $PATIENT_IMAGES_REL_BASE . '/' . $centreFolder . '/' . $patientFolder;
        $destFsDir  = rtrim($USER_IMAGES_FS_BASE, '/') . '/' . $PATIENT_IMAGES_REL_BASE . '/' . $centreFolder . '/' . $patientFolder;

        try {
            ensure_dir($destFsDir);
        } catch (Exception $e) {
            imagedestroy($srcImg);
            redirect_with([
                'error' => 'Upload directory error: ' . $e->getMessage(),
                'open'  => 'carenote',
                'pid'   => $patient_id
            ]);
        }

        $origName = $f['name'] ?? 'image.jpg';
        $baseName = pathinfo($origName, PATHINFO_FILENAME);
        $safeBase = slug_filename($baseName);

        // Always save as JPG for predictable size (transparent PNGs get white background)
        $finalName = $safeBase . '-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(4)), 0, 8) . '.jpg';

        $destFsPath  = rtrim($destFsDir, '/') . '/' . $finalName;
        $destRelPath = $destRelDir . '/' . $finalName; // stored in DB

        try {
            // Resize + compress aiming under ~2MB
            save_as_jpeg_with_resize($srcImg, $srcW, $srcH, $destFsPath, 2000, 2000000);

            // Insert rescue_images row (is_legacy = 0)
            $iStmt = $pdo->prepare("
                INSERT INTO rescue_images (centre_id, patient_id, image_url, file_name, is_legacy)
                VALUES (:centre_id, :patient_id, :image_url, :file_name, 0)
            ");
            $iStmt->execute([
                ':centre_id'  => $centre_id,
                ':patient_id' => $patient_id,
                ':image_url'  => $destRelPath,
                ':file_name'  => $finalName
            ]);

            $uploaded_image_id = (int)$pdo->lastInsertId();

        } catch (Exception $e) {
            // If DB insert fails, attempt cleanup of file
            @unlink($destFsPath);
            imagedestroy($srcImg);

            redirect_with([
                'error' => 'Error saving uploaded image: ' . $e->getMessage(),
                'open'  => 'carenote',
                'pid'   => $patient_id
            ]);
        }

        imagedestroy($srcImg);
    }

    // If upload happened, it wins (attach the new one).
    if ($uploaded_image_id > 0) {
        $image_id = $uploaded_image_id;
    } else {
        // If user selected "0" or nothing, store NULL rather than 0 (cleaner)
        if ($image_id <= 0) {
            $image_id = null;
        }
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO rescue_notes_patients
                (patient_id, message, author, public, image_id, date)
            VALUES
                (:patient_id, :message, :author, :public, :image_id, :date)
        ");

        $stmt->execute([
            ':patient_id' => $patient_id,
            ':message'    => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
            ':author'     => htmlspecialchars($author, ENT_QUOTES, 'UTF-8'),
            ':public'     => $public,
            ':image_id'   => $image_id,
            ':date'       => $date
        ]);

    } catch (Exception $e) {
        redirect_with([
            'error' => 'Error adding care note: ' . $e->getMessage(),
            'open'  => 'carenote',
            'pid'   => $patient_id
        ]);
    }

    redirect_with([
        'msg'  => 'Care note added successfully.',
        'open' => 'carenote',
        'pid'  => $patient_id
    ]);
}

/*--------------------------- MEDICATION GIVEN ---------------------------*/
if (isset($_POST['medicationform'])) {

    function m_clean($v) {
        return htmlspecialchars(trim((string)$v));
    }

    $patient_id        = m_clean($_POST["patient_id"]);
    $medication_given  = m_clean($_POST["medication_given"]);
    $dose              = m_clean($_POST["dose"]);
    $dose_type         = m_clean($_POST["dose_type"]);
    $given_by          = m_clean($_POST["given_by"]);
    $stock_item_used   = isset($_POST["stock_item_used"]) ? m_clean($_POST["stock_item_used"]) : '';
    $med_centre_id     = m_clean($_POST["centre_id"]);
    $bn_given          = m_clean($_POST["bn_given"]);
    $exp_given         = m_clean($_POST["exp_given"]);
    $given_by_id       = m_clean($_POST["given_by_id"]);
    $given_vol_raw     = isset($_POST["volume_used"]) ? trim($_POST["volume_used"]) : '';
    $date_given        = m_clean($_POST["date_given"]);

    if (!$patient_id || !$med_centre_id) {
        redirect_with([
            'error' => 'Missing required medication information.',
            'open'  => 'medication',
            'pid'   => $patient_id
        ]);
    }

    // Normalise volume as float or null
    $given_vol = ($given_vol_raw === '') ? null : (float)$given_vol_raw;

    try {
        /* ---------- INSERT INTO rescue_medications_given ---------- */
        $stmt = $pdo->prepare("
            INSERT INTO rescue_medications_given
                (patient_id,
                 centre_id,
                 given_by_id,
                 medication_given,
                 given_by,
                 dose,
                 dose_type,
                 stock_item_used,
                 batch_given,
                 exp_given,
                 vol_given,
                 date)
            VALUES
                (:patient_id,
                 :centre_id,
                 :given_by_id,
                 :medication_given,
                 :given_by,
                 :dose,
                 :dose_type,
                 :stock_item_used,
                 :batch_given,
                 :exp_given,
                 :vol_given,
                 :date_given)
        ");

        $stmt->execute([
            ':patient_id'       => $patient_id,
            ':centre_id'        => $med_centre_id,
            ':given_by_id'      => $given_by_id,
            ':medication_given' => $medication_given,
            ':given_by'         => $given_by,
            ':dose'             => $dose,
            ':dose_type'        => $dose_type,
            ':stock_item_used'  => $stock_item_used ?: null,
            ':batch_given'      => $bn_given,
            ':exp_given'        => $exp_given,
            ':vol_given'        => $given_vol,
            ':date_given'       => $date_given
        ]);

        /* ---------- UPDATE STOCK (ONLY IF USING STOCK) ---------- */
        if ($stock_item_used !== '' && $given_vol !== null && $given_vol > 0) {
            $stmt2 = $pdo->prepare("
                UPDATE rescue_medication_trans
                SET est_volume = est_volume - :given_vol
                WHERE med_trans_id = :stock_item_used
                LIMIT 1
            ");

            $stmt2->execute([
                ':given_vol'      => $given_vol,
                ':stock_item_used'=> $stock_item_used
            ]);
        }

    } catch (Exception $e) {
        redirect_with([
            'error' => 'Error adding medication record: ' . $e->getMessage(),
            'open'  => 'medication',
            'pid'   => $patient_id
        ]);
    }

    redirect_with([
        'msg'  => 'Medication record added successfully.',
        'open' => 'medication',
        'pid'  => $patient_id
    ]);
}

/*----------------------- ADD FEED -----------------------*/
if (isset($_POST['add_feed_form'])) {

    // Small helper
    function clean_text($v) {
        return htmlspecialchars(trim((string)$v), ENT_QUOTES, 'UTF-8');
    }

    $patient_id   = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
    $admission_id = isset($_POST['admission_id']) ? (int)$_POST['admission_id'] : 0;
    $centre_id    = isset($_POST['centre_id']) ? (int)$_POST['centre_id'] : 0;

    $centre_diet_item_id = isset($_POST['centre_diet_item_id']) ? (int)$_POST['centre_diet_item_id'] : 0;

    $feed_at_raw   = $_POST['feed_at'] ?? '';
    $offered_value = isset($_POST['offered_value']) ? (float)$_POST['offered_value'] : 0.0;
    $remaining_val = (isset($_POST['remaining_value']) && $_POST['remaining_value'] !== '') ? (float)$_POST['remaining_value'] : 0.0;

    $is_estimated  = isset($_POST['is_estimated']) ? 1 : 0;
    $notes         = clean_text($_POST['notes'] ?? '');

    $is_refused = isset($_POST['feed_refused']) && (string)$_POST['feed_refused'] === '1';
    $is_skipped = isset($_POST['feed_skipped']) && (string)$_POST['feed_skipped'] === '1';

    // Basic context checks
    if ($patient_id <= 0 || $centre_id <= 0) {
        header("Location: ../viewpatient.php?patient_id={$patient_id}&tab=feeding&error=" . urlencode("Missing patient/centre context."));
        exit;
    }

    // Feed time: datetime-local "YYYY-MM-DDTHH:MM"
    $feed_at = date('Y-m-d H:i:s');
    if (!empty($feed_at_raw)) {
        $feed_at = str_replace('T', ' ', $feed_at_raw) . ':00';
    }

    // Determine created_by (align with your existing session/global usage)
    $created_by =
        (int)($_SESSION['user_id'] ?? $GLOBALS['user_id'] ?? 0);

    if ($created_by <= 0) {
        header("Location: ../viewpatient.php?patient_id={$patient_id}&tab=feeding&error=" . urlencode("User context not available."));
        exit;
    }

    // If not skipped, diet item is required
    if (!$is_skipped && $centre_diet_item_id <= 0) {
        header("Location: ../viewpatient.php?patient_id={$patient_id}&tab=feeding&error=" . urlencode("Please select a diet item."));
        exit;
    }

    // Lock diet -> feed_type + units from DB
    $diet_item_id = null;
    $feed_type    = null; // 'solid' or 'liquid'
    $unit         = null; // 'ml' or 'g' or 'unit'

    if ($centre_diet_item_id > 0) {
        $stmt = $pdo->prepare("
            SELECT
                di.diet_item_id,
                di.type,
                di.default_unit
            FROM rescue_centre_diet_items cdi
            JOIN rescue_diet_items di ON di.diet_item_id = cdi.diet_item_id
            WHERE cdi.centre_diet_item_id = ?
              AND cdi.centre_id = ?
              AND cdi.is_enabled = 1
            LIMIT 1
        ");
        $stmt->execute([$centre_diet_item_id, $centre_id]);
        $dietRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dietRow && !$is_skipped) {
            header("Location: ../viewpatient.php?patient_id={$patient_id}&tab=feeding&error=" . urlencode("Diet item is not available for this centre."));
            exit;
        }

        if ($dietRow) {
            $diet_item_id = (int)$dietRow['diet_item_id'];
            $feed_type    = (string)$dietRow['type'];
            $unit         = (string)$dietRow['default_unit'];
        }
    }

    // For skipped events, allow diet to be null; for non-skipped, it should be set
    if (!$is_skipped && (!$diet_item_id || !$feed_type || !$unit)) {
        header("Location: ../viewpatient.php?patient_id={$patient_id}&tab=feeding&error=" . urlencode("Diet item lookup failed."));
        exit;
    }

    // Estimated only permitted for solids
    if ($feed_type !== 'solid') {
        $is_estimated = 0;
    }

    // Normalise numeric values
    if ($offered_value < 0) $offered_value = 0.0;
    if ($remaining_val < 0) $remaining_val = 0.0;

    // Compute status + remaining/consumed
    $status           = 'normal';
    $offered_store    = null;
    $remaining_store  = null;
    $remaining_percent = null; // reserved for slider later
    $consumed_store   = 0.0;
    $offered_unit     = null;
    $consumed_unit    = null;

    if ($is_skipped) {
        $status = 'skipped';
        $consumed_store = 0.0;
    } else {
        $offered_store = $offered_value;

        // Remaining must not exceed offered (for absolute remaining)
        if ($remaining_val > $offered_store) $remaining_val = $offered_store;

        if ($is_refused) {
            $status = 'refused';
            $remaining_store = $offered_store;
            $consumed_store  = 0.0;
        } else {
            $status = 'normal';
            $remaining_store = $remaining_val;
            $consumed_store  = $offered_store - $remaining_store;
            if ($consumed_store < 0) $consumed_store = 0.0;
        }

        $offered_unit  = $unit;
        $consumed_unit = $unit;
    }

    try {
        $sql = "
            INSERT INTO rescue_feeding_events
                (patient_id, admission_id, centre_id, diet_item_id, feed_at, feed_type, status,
                 offered_value, offered_unit, is_estimated,
                 remaining_value, remaining_percent,
                 consumed_value, consumed_unit,
                 notes, created_by, created_at)
            VALUES
                (:patient_id, :admission_id, :centre_id, :diet_item_id, :feed_at, :feed_type, :status,
                 :offered_value, :offered_unit, :is_estimated,
                 :remaining_value, :remaining_percent,
                 :consumed_value, :consumed_unit,
                 :notes, :created_by, NOW())
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':patient_id'       => $patient_id,
            ':admission_id'     => ($admission_id > 0 ? $admission_id : null),
            ':centre_id'        => $centre_id,
            ':diet_item_id'     => ($diet_item_id ?: null),
            ':feed_at'          => $feed_at,
            ':feed_type'        => ($feed_type ?: null),
            ':status'           => $status,

            ':offered_value'    => $offered_store,
            ':offered_unit'     => $offered_unit,
            ':is_estimated'     => $is_estimated,

            ':remaining_value'  => $remaining_store,
            ':remaining_percent'=> $remaining_percent,

            ':consumed_value'   => $consumed_store,
            ':consumed_unit'    => $consumed_unit,

            ':notes'            => $notes,
            ':created_by'       => $created_by
        ]);

        header("Location: ../viewpatient.php?patient_id={$patient_id}&tab=feeding&msg=" . urlencode("Feed saved."));
        exit;

    } catch (PDOException $e) {
        header("Location: ../viewpatient.php?patient_id={$patient_id}&tab=feeding&error=" . urlencode("Failed to save feed."));
        exit;
    }
}
// --------------------------------------------------
// SHARE PATIENT
// Expects POST:
// - share_patient_form = 1
// - patient_id
// - owner_centre_id
// - share_target   (format: centre:123 OR group:456)
// --------------------------------------------------
if (isset($_POST['share_patient_form'])) {

    $patient_id            = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
    $owner_centre_id       = isset($_POST['owner_centre_id']) ? (int)$_POST['owner_centre_id'] : 0;
    $shared_by_account_id  = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
    $share_target_raw      = isset($_POST['share_target']) ? trim((string)$_POST['share_target']) : '';

    if ($patient_id <= 0 || $owner_centre_id <= 0 || $share_target_raw === '') {
        die('Invalid share request.');
    }

    $parts = explode(':', $share_target_raw, 2);
    $share_type = isset($parts[0]) ? trim((string)$parts[0]) : '';
    $target_id  = isset($parts[1]) ? (int)$parts[1] : 0;

    if ($target_id <= 0 || !in_array($share_type, ['centre', 'group'], true)) {
        die('Invalid share target.');
    }

    $group_id = null;
    $target_centre_id = null;
    $target_account_id = null;

    try {
        $pdo->beginTransaction();

        // ------------------------------------------
        // 1) Optional ownership sanity check
        //    Confirms the patient belongs to the posting centre
        // ------------------------------------------
        $ownershipStmt = $pdo->prepare("
            SELECT 1
            FROM rescue_patients p
            INNER JOIN rescue_admissions a
                ON a.patient_id = p.patient_id
            WHERE p.patient_id = ?
              AND a.disposition = 'Held in captivity'
            LIMIT 1
        ");
        $ownershipStmt->execute([$patient_id]);

        if (!$ownershipStmt->fetchColumn()) {
            $pdo->rollBack();
            die('Patient is not eligible to be shared.');
        }

        // ------------------------------------------
        // 2) Validate target is allowed
        // ------------------------------------------
        if ($share_type === 'centre') {
            $target_centre_id = $target_id;

            if ($target_centre_id === $owner_centre_id) {
                $pdo->rollBack();
                die('Cannot share to the same centre.');
            }

            $allowedStmt = $pdo->prepare("
                SELECT 1
                FROM rescue_centre_friends f
                WHERE f.status = 'approved'
                  AND (
                        (f.centre_a_id = ? AND f.centre_b_id = ?)
                     OR (f.centre_a_id = ? AND f.centre_b_id = ?)
                  )
                LIMIT 1
            ");
            $allowedStmt->execute([
                $owner_centre_id, $target_centre_id,
                $target_centre_id, $owner_centre_id
            ]);

            if (!$allowedStmt->fetchColumn()) {
                $pdo->rollBack();
                die('That centre is not an approved connection.');
            }

        } elseif ($share_type === 'group') {
            $group_id = $target_id;

            $allowedStmt = $pdo->prepare("
                SELECT 1
                FROM rescue_group_members gm
                WHERE gm.group_id = ?
                  AND gm.centre_id = ?
                  AND gm.status = 'active'
                LIMIT 1
            ");
            $allowedStmt->execute([$group_id, $owner_centre_id]);

            if (!$allowedStmt->fetchColumn()) {
                $pdo->rollBack();
                die('That network is not active for this centre.');
            }
        } elseif ($share_type === 'vet') {
            $target_vet_id = $target_id;

        $allowedStmt = $pdo->prepare("
            SELECT 1
            FROM rescue_vet_centres
            WHERE centre_id = ?
             AND practice_id = ?
             AND status = 'approved'
            LIMIT 1
        ");
        $allowedStmt->execute([$owner_centre_id, $target_vet_id]);

        if (!$allowedStmt->fetchColumn()) {
            $pdo->rollBack();
            die('That vet practice is not connected to this centre.');
        }
    }

        // ------------------------------------------
        // 3) Guardrail: if an ACTIVE matching share already exists, do nothing
        // ------------------------------------------
        $existingActiveStmt = $pdo->prepare("
            SELECT share_id
            FROM rescue_patient_shares
            WHERE patient_id = :patient_id
              AND owner_centre_id = :owner_centre_id
              AND share_type = :share_type
              AND status = 'active'
              AND (
                    (:group_id IS NOT NULL AND group_id = :group_id)
                 OR (:target_centre_id IS NOT NULL AND target_centre_id = :target_centre_id)
              )
            LIMIT 1
        ");
        $existingActiveStmt->execute([
            ':patient_id'        => $patient_id,
            ':owner_centre_id'   => $owner_centre_id,
            ':share_type'        => $share_type,
            ':group_id'          => $group_id,
            ':target_centre_id'  => $target_centre_id,
        ]);

        $existing_active_share_id = (int)$existingActiveStmt->fetchColumn();

        if ($existing_active_share_id > 0) {
            $pdo->commit();

            $redirect = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/';
            header('Location: ' . $redirect);
            exit;
        }

        // ------------------------------------------
        // 4) If a matching REVOKED share exists, reactivate it instead of inserting a new row
        // ------------------------------------------
        $existingRevokedStmt = $pdo->prepare("
            SELECT share_id
            FROM rescue_patient_shares
            WHERE patient_id = :patient_id
              AND owner_centre_id = :owner_centre_id
              AND share_type = :share_type
              AND status = 'revoked'
              AND (
                    (:group_id IS NOT NULL AND group_id = :group_id)
                 OR (:target_centre_id IS NOT NULL AND target_centre_id = :target_centre_id)
              )
            ORDER BY share_id DESC
            LIMIT 1
        ");
        $existingRevokedStmt->execute([
            ':patient_id'        => $patient_id,
            ':owner_centre_id'   => $owner_centre_id,
            ':share_type'        => $share_type,
            ':group_id'          => $group_id,
            ':target_centre_id'  => $target_centre_id,
        ]);

        $existing_revoked_share_id = (int)$existingRevokedStmt->fetchColumn();

        if ($existing_revoked_share_id > 0) {
            $reactivateStmt = $pdo->prepare("
                UPDATE rescue_patient_shares
                SET
                    status = 'active',
                    revoked_at = NULL,
                    revoked_by_account_id = NULL,
                    shared_by_account_id = :shared_by_account_id,
                    created_at = NOW()
                WHERE share_id = :share_id
                LIMIT 1
            ");
            $reactivateStmt->execute([
                ':shared_by_account_id' => $shared_by_account_id,
                ':share_id'             => $existing_revoked_share_id,
            ]);

            $pdo->commit();

            $redirect = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/';
            header('Location: ' . $redirect);
            exit;
        }

        // ------------------------------------------
        // 5) Insert new share
        // ------------------------------------------
        $insertStmt = $pdo->prepare("
            INSERT INTO rescue_patient_shares (
                patient_id,
                owner_centre_id,
                shared_by_account_id,
                share_type,
                group_id,
                target_centre_id,
                target_account_id,
                status,
                created_at
            ) VALUES (
                :patient_id,
                :owner_centre_id,
                :shared_by_account_id,
                :share_type,
                :group_id,
                :target_centre_id,
                :target_account_id,
                'active',
                NOW()
            )
        ");
        $insertStmt->execute([
            ':patient_id'            => $patient_id,
            ':owner_centre_id'       => $owner_centre_id,
            ':shared_by_account_id'  => $shared_by_account_id,
            ':share_type'            => $share_type,
            ':group_id'              => $group_id,
            ':target_centre_id'      => $target_centre_id,
            ':target_account_id'     => $target_account_id,
        ]);

        $pdo->commit();

        $redirect = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/';
        header('Location: ' . $redirect);
        exit;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        die('Unable to create share: ' . $e->getMessage());
    }
}
/*----------------------- DELETE FEED EVENT -----------------------*/
if (isset($_POST['feed_delete'])) {

    $patient_id = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
    $feed_id    = isset($_POST['feed_id']) ? (int)$_POST['feed_id'] : 0;

    if ($patient_id <= 0 || $feed_id <= 0) {
        header("Location: ../viewpatient.php?patient_id={$patient_id}&tab=feeding&error=" . urlencode("Missing feed delete data."));
        exit;
    }

    // Enforce permission server-side
    require_once __DIR__ . '/../operations/permissions.php';
    if (!can('patients.feeding.delete')) {
        header("Location: ../viewpatient.php?patient_id={$patient_id}&tab=feeding&error=" . urlencode("You do not have permission to delete feeding events."));
        exit;
    }

    try {
        // Ensure feed belongs to patient (prevents tampering)
        $chk = $pdo->prepare("
            SELECT feed_id
            FROM rescue_feeding_events
            WHERE feed_id = :feed_id
              AND patient_id = :patient_id
            LIMIT 1
        ");
        $chk->execute([
            ':feed_id'    => $feed_id,
            ':patient_id' => $patient_id
        ]);

        if (!$chk->fetchColumn()) {
            header("Location: ../viewpatient.php?patient_id={$patient_id}&tab=feeding&error=" . urlencode("Feed entry not found."));
            exit;
        }

        $del = $pdo->prepare("
            DELETE FROM rescue_feeding_events
            WHERE feed_id = :feed_id
              AND patient_id = :patient_id
            LIMIT 1
        ");
        $del->execute([
            ':feed_id'    => $feed_id,
            ':patient_id' => $patient_id
        ]);

        header("Location: ../viewpatient.php?patient_id={$patient_id}&tab=feeding&msg=" . urlencode("Feed entry deleted."));
        exit;

    } catch (PDOException $e) {
        header("Location: ../viewpatient.php?patient_id={$patient_id}&tab=feeding&error=" . urlencode("Failed to delete feed entry."));
        exit;
    }
}


}
?>
