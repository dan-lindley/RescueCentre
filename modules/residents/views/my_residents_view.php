<?php    
// views/my_residents_view.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// centre_id must already be available from the template
$centre_id_int = isset($centre_id) ? (int)$centre_id : 0;
$allowed_patient_page_sizes = [10, 20, 25, 50, 100, 9999];
$patient_page_size = (int)($_SESSION['my_patients_per_page'] ?? 0);

if ((int)($_SESSION['account_id'] ?? 0) > 0) {
    try {
        $pageSizeStmt = $pdo->prepare('SELECT my_patients_per_page FROM accounts WHERE id = ? LIMIT 1');
        $pageSizeStmt->execute([(int)$_SESSION['account_id']]);
        $patient_page_size = (int)$pageSizeStmt->fetchColumn();
    } catch (Throwable $e) {
        $patient_page_size = 10;
    }
}

if (!in_array($patient_page_size, $allowed_patient_page_sizes, true)) {
    $patient_page_size = 10;
}

$_SESSION['my_patients_per_page'] = $patient_page_size;

// --------------------------------------------------
// FETCH PATIENTS (residents)
// --------------------------------------------------
$stmt = $pdo->prepare("

    SELECT 
        a.admission_id,
        a.admission_date,
        a.presenting_complaint,
        a.current_location,
        p.patient_id,
        p.name,
        p.sex,
        p.animal_species,
        p.animal_type,
        DATEDIFF(NOW(), a.admission_date) AS daysincare,

        -- Admission WRA
        (a.bc_score + a.age_score + a.severity_score) AS wra,

        -- Latest observation values
        o.obs_bcs_score,
        o.obs_age_score,
        o.obs_severity_score,

        -- New WRA computed at SQL level
        (
            COALESCE(o.obs_bcs_score, 99) +
            COALESCE(o.obs_age_score, 0) +
            COALESCE(o.obs_severity_score, 0)
        ) AS newwra,

        -- Location area from rescue_locations
        rl.location_area

    FROM rescue_admissions a
    LEFT JOIN rescue_patients p 
        ON a.patient_id = p.patient_id

    -- Get the latest observation using a NOT EXISTS anti-join
    LEFT JOIN rescue_observations o
        ON o.patient_id = a.patient_id
        AND NOT EXISTS (
            SELECT 1 
            FROM rescue_observations o2
            WHERE o2.patient_id = o.patient_id
              AND o2.obs_date > o.obs_date
        )

    -- Join to locations to get area
    LEFT JOIN rescue_locations rl
        ON rl.location_name = a.current_location
       AND rl.centre_id    = :cid

    WHERE 
        p.centre_id = :cid
        AND a.disposition = 'Long-term Captive' 
        AND p.state = 'Admitted'

    ORDER BY a.admission_date DESC

");

$stmt->execute([':cid' => $centre_id_int]);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --------------------------------------------------
// MEDICATION DEPENDENCIES (for add_medsadmin.php)
// --------------------------------------------------
$residentsControllerPath = dirname(__DIR__, 3) . '/controllers';

@include $residentsControllerPath . '/meds_dependencies.php';

// Sections map (used for buttons AND form containers) - labels not shown, leave as-is
$sections = [
    'carenote'     => 'Care Note',
    'observation'  => 'Observation',
    'prescription' => 'Prescription',
    'medication'   => 'Medication',
    'treatment'    => 'Treatment',
    'labs'         => 'Labs',
    'weight'       => 'Weight',
    'measurement'  => 'Measurement',
    'tasks'        => 'Tasks',
    'discharge'    => 'Discharge'
];

// Map section key -> controller file
$form_map = [
    'carenote'     => 'care_notes_form.php',
    'observation'  => 'add_observation.php',
    'prescription' => 'add_prescription.php',
    'medication'   => 'add_medsadmin.php',
    'treatment'    => 'add_treatment.php',
    'labs'         => 'add_labs.php',
    'weight'       => 'add_weight.php',
    'measurement'  => 'add_measurement.php',
    'tasks'        => 'add_tasks.php',
    'discharge'    => 'add_disposition.php'
];
?>

<h2><?= $lang['LM_RESIDENTS'] ?? 'My Residents' ?></h2>

<!-- PAGE SIZE + SEARCH CONTROLS -->
<div class="xform-grid">
    <div class="xform-field" style="grid-column: span 2;">
        <input type="text"
               id="tableSearch"
               class="xform-input"
               placeholder="<?= htmlspecialchars($lang['TABLE_SEARCH_PLACEHOLDER_PATIENTS'] ?? 'Search by CRN, name or species') ?>">
    </div>

    <div class="xform-field" style="grid-column: span 1;">
        <label style="font-weight:normal;">
            <?= $lang['TABLE_SHOW'] ?? 'Show' ?>
            <select id="pageSize" class="xform-input" style="width:auto; display:inline-block;">
                <?php foreach ($allowed_patient_page_sizes as $size): ?>
                    <option value="<?= (int)$size ?>" <?= $patient_page_size === $size ? 'selected' : '' ?>>
                        <?= $size === 9999 ? htmlspecialchars($lang['TABLE_ALL'] ?? 'All') : (int)$size ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= $lang['TABLE_ENTRIES'] ?? 'entries' ?>
        </label>
    </div>
</div>

<?php if (empty($patients)): ?>
    <p><?= $lang['NO_PATIENTS_FOUND'] ?? 'No patients found.' ?></p>
<?php else: ?>

<?php
/* ----------------------------------------------------------
   BUILD LIST OF LOCATION AREAS FOR TABS
---------------------------------------------------------- */
$areaStmt = $pdo->prepare("
    SELECT DISTINCT location_area
    FROM rescue_locations
    WHERE centre_id = :centre_id_areas
      AND location_area IS NOT NULL
      AND location_area <> ''
    ORDER BY location_area ASC
");
$areaStmt->bindValue(':centre_id_areas', $centre_id_int, PDO::PARAM_INT);
$areaStmt->execute();
$location_areas = $areaStmt->fetchAll(PDO::FETCH_COLUMN);

include $residentsControllerPath . '/meds_dependencies.php'; 
?>

<!-- AREA TABS -->
<div class="tabs" id="locationTabs">
    <a href="#" class="loc-tab active" data-area="all"><?= $lang['PAT_TAB_ALL'] ?? 'All' ?></a>
    <?php foreach ($location_areas as $area): ?>
        <a href="#" class="loc-tab" data-area="<?php echo htmlspecialchars($area); ?>">
            <?php echo htmlspecialchars($area); ?>
        </a>
    <?php endforeach; ?>
</div>
<br>

<table class="table table-bordered table-sm table-hover table-modern patient-table" width="100%" cellspacing="0">
    <thead class="thead-dark">
        <tr>
            <th><?= $lang['PAT_TABLE_CRN_PATIENT'] ?? 'CRN / Patient' ?></th>
            <th><?= $lang['PAT_TABLE_ADMISSION_DATE'] ?? 'Admission<br>Date' ?></th>
            <th><?= $lang['PAT_TABLE_DAYS_IN_CARE'] ?? 'Days in<br>Care' ?></th>
            <th><?= $lang['LOCATION'] ?? 'Location' ?></th>
            <th><?= $lang['PAT_TABLE_PRESENTING_COMPLAINT'] ?? 'Presenting<br>Complaint' ?></th>
            <th><?= $lang['PAT_TABLE_WRA_ADMISSION'] ?? 'WRA Score<br>(admission)' ?></th>
            <th><?= $lang['PAT_TABLE_WRA_CURRENT'] ?? 'WRA Score<br>(current)' ?></th>
        </tr>
    </thead>
    <tbody>

<?php foreach ($patients as $p): 
    $pid   = $p['patient_id'];
    $pname = $p['name'];

    // Normalise area label
    $location_area = trim($p['location_area'] ?? '');
    if ($location_area === '') {
        $location_area = $lang['PAT_UNASSIGNED'] ?? 'Unassigned';
    }
?>

<?php
$wra = (int)$p['wra'];
$newwra = (int)$p['newwra'];

// Admission WRA colour
if ($wra > 90) {
    $wra_class   = '';
    $wra_display = $lang['PAT_WRA_NA'] ?? 'N/A';
} elseif ($wra >= 6) {
    $wra_class   = 'alert-red';
    $wra_display = $wra;
} elseif ($wra >= 3) {
    $wra_class   = 'alert-amber';
    $wra_display = $wra;
} else {
    $wra_class   = 'alert-green';
    $wra_display = $wra;
}

// Latest WRA colour
if ($newwra > 90) {
    $newwra_class   = '';
    $newwra_display = $lang['PAT_WRA_NA'] ?? 'N/A';
} elseif ($newwra >= 6) {
    $newwra_class   = 'alert-red';
    $newwra_display = $newwra;
} elseif ($newwra >= 3) {
    $newwra_class   = 'alert-amber';
    $newwra_display = $newwra;
} else {
    $newwra_class   = 'alert-green';
    $newwra_display = $newwra;
}

/* ---- TASK ICON QUERY FOR THIS PATIENT ---- */
$taskStmt = $pdo->prepare("
    SELECT 
        t.task,
        t.svg,
        tp.status,
        tp.task_pt_id,
        tp.completed_date_time,
        tp.completed_by,
        a.first_name,
        a.last_name
    FROM rescue_tasks_patients tp
    JOIN rescue_tasks t 
        ON t.task_id = tp.task_id
    LEFT JOIN accounts a 
        ON a.id = tp.completed_by
    WHERE tp.patient_id = :pid
");
$taskStmt->execute([':pid' => $pid]);
$taskIcons = $taskStmt->fetchAll(PDO::FETCH_ASSOC);

// PHP 8 match:
$daysclass = match (true) {
  $p['daysincare'] > 120 => 'alert-grey',
  $p['daysincare'] > 90  => 'alert-red',
  $p['daysincare'] > 60  => 'alert-amber',
  $p['daysincare'] > 31  => 'alert-blue',
  $p['daysincare'] <= 31 => 'alert-green',
};             
?>

<tbody class="patient-block" style="border:1px solid #ccc; border-radius:4px;">

<tr class="patient-row patient-block-row"
    data-main="1"
    data-patient-id="<?= $pid ?>"
    data-area="<?= htmlspecialchars($location_area) ?>">

    <td class="align-middle">
        <?= ($lang['PAT_CRN'] ?? 'CRN') ?>: <?= $pid ?> – <b><?= htmlspecialchars($pname) ?></b> (<?= $p['sex'] ?>)
        <br><?= $p['animal_species'] ?> (<?= $p['animal_type'] ?>)
    </td>

    <td class="align-middle">
    <?php
        $dt = new DateTime($p['admission_date']);
        $day = $dt->format('j');
        $suffix = date('S', $dt->getTimestamp());
        echo $day . $suffix . " " . $dt->format('F Y') . "<br>" . $dt->format('H:i');
    ?>
    </td>

    <td class="align-middle <?php echo $daysclass; ?>">
        <center><h5><?= (int)$p['daysincare'] ?></h5></center>
    </td>

    <td class="align-middle">
        <b><?= htmlspecialchars($p['current_location']) ?></b><br>
        (<?= htmlspecialchars($location_area) ?>)
    </td>

    <td class="align-middle">
        <?= htmlspecialchars($p['presenting_complaint']) ?>
    </td>

    <td class="align-middle <?= $wra_class ?>">
        <center><strong><h5><?= $wra_display ?></h5></strong></center>
    </td>

    <td class="align-middle <?= $newwra_class ?>">
        <center><strong><h5><?= $newwra_display ?></h5></strong></center>
    </td>
</tr>

<tr data-main="0" data-patient-id="<?= $pid ?>">
    <td colspan="4" class="align-middle">
        <div class="btn-group">

            <a href="viewpatient.php?patient_id=<?= $pid ?>"
               class="btn green"
               alt="<?= htmlspecialchars($lang['TIP_VIEW_CARE_PLAN'] ?? 'View Care Plan') ?>">
                <svg svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><path d="M439.4 96L448 96C483.3 96 512 124.7 512 160L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 160C128 124.7 156.7 96 192 96L200.6 96C211.6 76.9 232.3 64 256 64L384 64C407.7 64 428.4 76.9 439.4 96zM376 176C389.3 176 400 165.3 400 152C400 138.7 389.3 128 376 128L264 128C250.7 128 240 138.7 240 152C240 165.3 250.7 176 264 176L376 176zM320 408C350.9 408 376 382.9 376 352C376 321.1 350.9 296 320 296C289.1 296 264 321.1 264 352C264 382.9 289.1 408 320 408zM226.3 477C213.4 492.6 228.5 512 248.7 512L391.2 512C411.4 512 426.5 492.6 413.6 477C398.9 459.3 376.7 448 351.9 448L287.9 448C263.1 448 240.9 459.3 226.2 477z"/></svg>
                &nbsp; <?= $lang['BTN_CARE_PLAN'] ?? 'Care Plan' ?>
            </a>

            <button title="<?= htmlspecialchars($lang['TIP_ADD_A_CARE_NOTE'] ?? 'Add a Care Note') ?>" class="btn blue open-section" data-section="carenote" data-pid="<?= $pid ?>">
                <svg svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><path d="M160 416C160 451.3 188.7 480 224 480L405.5 480C422.5 480 438.8 473.3 450.8 461.3L557.3 354.7C569.3 342.7 576 326.4 576 309.4L576 128C576 92.7 547.3 64 512 64L224 64C188.7 64 160 92.7 160 128L160 416zM352 176L384 176C392.8 176 400 183.2 400 192L400 240L448 240C456.8 240 464 247.2 464 256L464 288C464 296.8 456.8 304 448 304L400 304L400 352C400 360.8 392.8 368 384 368L352 368C343.2 368 336 360.8 336 352L336 304L288 304C279.2 304 272 296.8 272 288L272 256C272 247.2 279.2 240 288 240L336 240L336 192C336 183.2 343.2 176 352 176zM112 184C112 170.7 101.3 160 88 160C74.7 160 64 170.7 64 184L64 512C64 547.3 92.7 576 128 576L392 576C405.3 576 416 565.3 416 552C416 538.7 405.3 528 392 528L128 528C119.2 528 112 520.8 112 512L112 184z"/></svg>
            </button>

            <button title="<?= htmlspecialchars($lang['TIP_ADD_AN_OBSERVATION'] ?? 'Add an Observation') ?>" class="btn blue open-section" data-section="observation" data-pid="<?= $pid ?>">
                <svg svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><path d="M128 128C128 110.3 113.7 96 96 96C78.3 96 64 110.3 64 128L64 464C64 508.2 99.8 544 144 544L544 544C561.7 544 576 529.7 576 512C576 494.3 561.7 480 544 480L144 480C135.2 480 128 472.8 128 464L128 128zM534.6 214.6C547.1 202.1 547.1 181.8 534.6 169.3C522.1 156.8 501.8 156.8 489.3 169.3L384 274.7L326.6 217.4C314.1 204.9 293.8 204.9 281.3 217.4L185.3 313.4C172.8 325.9 172.8 346.2 185.3 358.7C197.8 371.2 218.1 371.2 230.6 358.7L304 285.3L361.4 342.7C373.9 355.2 394.2 355.2 406.7 342.7L534.7 214.7z"/></svg>
            </button>

            <button title="<?= htmlspecialchars($lang['TIP_ADD_A_PRESCRIPTION'] ?? 'Add a Prescription') ?>" class="btn blue open-section" data-section="prescription" data-pid="<?= $pid ?>">
                <svg svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><path d="M128 64C110.3 64 96 78.3 96 96L96 352C96 369.7 110.3 384 128 384C145.7 384 160 369.7 160 352L160 288L210.7 288L338.7 416L233.3 521.4C220.8 533.9 220.8 554.2 233.3 566.7C245.8 579.2 266.1 579.2 278.6 566.7L384 461.3L489.4 566.6C501.9 579.1 522.2 579.1 534.7 566.6C547.2 554.1 547.2 533.8 534.7 521.3L429.3 416L534.6 310.6C547.1 298.1 547.1 277.8 534.6 265.3C522.1 252.8 501.8 252.8 489.3 265.3L384 370.7L298.2 284.9C347.4 273.1 384 228.8 384 176C384 114.1 333.9 64 272 64L128 64zM272 224L160 224L160 128L272 128C298.5 128 320 149.5 320 176C320 202.5 298.5 224 272 224z"/></svg>
            </button>

            <button title="<?= htmlspecialchars($lang['TIP_ADMINISTER_A_MEDICATION'] ?? 'Administer a Medication') ?>" class="btn blue open-section" data-section="medication" data-pid="<?= $pid ?>">
                <svg svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><path d="M128 176C128 149.5 149.5 128 176 128C202.5 128 224 149.5 224 176L224 288L128 288L128 176zM240 432C240 383.3 258.1 338.8 288 305L288 176C288 114.1 237.9 64 176 64C114.1 64 64 114.1 64 176L64 464C64 525.9 114.1 576 176 576C213.3 576 246.3 557.8 266.7 529.7C249.7 501.1 240 467.7 240 432zM304.7 499.4C309.3 508.1 321 509.1 328 502.1L502.1 328C509.1 321 508.1 309.3 499.4 304.7C479.3 294 456.4 288 432 288C352.5 288 288 352.5 288 432C288 456.3 294 479.3 304.7 499.4zM361.9 536C354.9 543 355.9 554.7 364.6 559.3C384.7 570 407.6 576 432 576C511.5 576 576 511.5 576 432C576 407.7 570 384.7 559.3 364.6C554.7 355.9 543 354.9 536 361.9L361.9 536z"/></svg>
            </button>

            <button title="<?= htmlspecialchars($lang['TIP_ADD_A_TREATMENT'] ?? 'Add a Treatment') ?>" class="btn blue open-section" data-section="treatment" data-pid="<?= $pid ?>">
                <svg svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><path d="M160 141.3C160 134 165.9 128 173.3 128C176.8 128 180.2 129.4 182.7 131.9L197.6 146.8C194 155.9 192.1 165.7 192.1 176C192.1 195.9 199.3 214 211.3 228C206 237.2 207.3 249.1 215.1 257C224.5 266.4 239.7 266.4 249 257L353 153C362.4 143.6 362.4 128.4 353 119.1C345.2 111.2 333.2 110 324 115.3C310 103.3 291.9 96.1 272 96.1C261.7 96.1 251.8 98.1 242.8 101.6L227.9 86.6C213.4 72.1 193.7 64 173.3 64C130.6 64 96 98.6 96 141.3L96 320C78.3 320 64 334.3 64 352C64 369.7 78.3 384 96 384L96 432C96 460.4 108.4 486 128 503.6L128 544C128 561.7 142.3 576 160 576C177.7 576 192 561.7 192 544L192 528L448 528L448 544C448 561.7 462.3 576 480 576C497.7 576 512 561.7 512 544L512 503.6C531.6 486 544 460.5 544 432L544 384C561.7 384 576 369.7 576 352C576 334.3 561.7 320 544 320L160 320L160 141.3z"/></svg>
            </button>

            <button title="<?= htmlspecialchars($lang['TIP_ADD_LAB_RESULTS'] ?? 'Add Lab Results') ?>" class="btn blue open-section" data-section="labs" data-pid="<?= $pid ?>">
                <svg svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><path d="M184.6 475.5C181.5 482.8 179.2 490.4 177.8 498.1C163.3 506.9 146.3 512 128 512C75 512 32 469 32 416L32 128C14.3 128 0 113.7 0 96C0 78.3 14.3 64 32 64L224 64C241.7 64 256 78.3 256 96C256 113.7 241.7 128 224 128L224 383.6L184.6 475.5zM96 128L96 256L160 256L160 128L96 128zM352 64L512 64C529.7 64 544 78.3 544 96C544 113.7 529.7 128 512 128L512 281.4L603.3 494.4C605.6 499.8 607.1 505.5 607.7 511.4L608 512L607.7 512C607.9 513.8 608 515.6 608 517.4C608 549.7 581.8 576 549.4 576L282.5 576C250.2 576 223.9 549.8 223.9 517.4C223.9 515.6 224 513.8 224.2 512L223.9 512L224.2 511.4C224.8 505.6 226.3 499.8 228.6 494.4L320 281.4L320 128C302.3 128 288 113.7 288 96C288 78.3 302.3 64 320 64L352 64zM453.2 306.6C449.8 298.6 448 290.1 448 281.4L448 128L384 128L384 281.4C384 290.1 382.2 298.6 378.8 306.6L345.6 384L486.3 384L453.1 306.6z"/></svg>
            </button>

            <button title="<?= htmlspecialchars($lang['TIP_ADD_A_WEIGHT'] ?? 'Add a Weight') ?>" class="btn grey open-section" data-section="weight" data-pid="<?= $pid ?>">
                <svg svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><path d="M212.6 256C209.6 245.9 208 235.1 208 224C208 162.1 258.1 112 320 112C381.9 112 432 162.1 432 224C432 235.1 430.4 245.9 427.4 256L356.4 256L381 211.7C387.4 200.1 383.3 185.5 371.7 179.1C360.1 172.7 345.5 176.8 339.1 188.4L301.5 256.1L212.7 256.1zM224 96L160 96C124.7 96 96 124.7 96 160L96 480C96 515.3 124.7 544 160 544L480 544C515.3 544 544 515.3 544 480L544 160C544 124.7 515.3 96 480 96L416 96C389.3 75.9 356 64 320 64C284 64 250.7 75.9 224 96z"/></svg>
            </button>

            <button title="<?= htmlspecialchars($lang['TIP_ADD_A_MEASUREMENT'] ?? 'Add a Measurement') ?>" class="btn grey open-section" data-section="measurement" data-pid="<?= $pid ?>">
                <svg svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><path d="M80 448C53.5 448 32 426.5 32 400L32 240C32 213.5 53.5 192 80 192L104 192L104 296C104 309.3 114.7 320 128 320C141.3 320 152 309.3 152 296L152 192L200 192L200 264C200 277.3 210.7 288 224 288C237.3 288 248 277.3 248 264L248 192L296 192L296 296C296 309.3 306.7 320 320 320C333.3 320 344 309.3 344 296L344 192L392 192L392 264C392 277.3 402.7 288 416 288C429.3 288 440 277.3 440 264L440 192L488 192L488 296C488 309.3 498.7 320 512 320C525.3 320 536 309.3 536 296L536 192L560 192C586.5 192 608 213.5 608 240L608 400C608 426.5 586.5 448 560 448L80 448z"/></svg>
            </button>

            <button title="<?= htmlspecialchars($lang['TIP_ADD_A_QUICK_TASK'] ?? 'Add a Quick Task') ?>" class="btn purple open-section" data-section="tasks" data-pid="<?= $pid ?>">
                <svg svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><path d="M197.8 100.3C208.7 107.9 211.3 122.9 203.7 133.7L147.7 213.7C143.6 219.5 137.2 223.2 130.1 223.8C123 224.4 116 222 111 217L71 177C61.7 167.6 61.7 152.4 71 143C80.3 133.6 95.6 133.7 105 143L124.8 162.8L164.4 106.2C172 95.3 187 92.7 197.8 100.3zM197.8 260.3C208.7 267.9 211.3 282.9 203.7 293.7L147.7 373.7C143.6 379.5 137.2 383.2 130.1 383.8C123 384.4 116 382 111 377L71 337C61.6 327.6 61.6 312.4 71 303.1C80.4 293.8 95.6 293.7 104.9 303.1L124.7 322.9L164.3 266.3C171.9 255.4 186.9 252.8 197.7 260.4zM288 160C288 142.3 302.3 128 320 128L544 128C561.7 128 576 142.3 576 160C576 177.7 561.7 192 544 192L320 192C302.3 192 288 177.7 288 160zM288 320C288 302.3 302.3 288 320 288L544 288C561.7 288 576 302.3 576 320C576 337.7 561.7 352 544 352L320 352C302.3 352 288 337.7 288 320zM224 480C224 462.3 238.3 448 256 448L544 448C561.7 448 576 462.3 576 480C576 497.7 561.7 512 544 512L256 512C238.3 512 224 497.7 224 480zM128 440C150.1 440 168 457.9 168 480C168 502.1 150.1 520 128 520C105.9 520 88 502.1 88 480C88 457.9 105.9 440 128 440z"/></svg>
            </button>

            <button title="<?= htmlspecialchars($lang['TIP_DISCHARGE_THIS_PATIENT'] ?? 'Discharge this Patient') ?>" class="btn red open-section" data-section="discharge" data-pid="<?= $pid ?>">
                <svg svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><path d="M569 337C578.4 327.6 578.4 312.4 569 303.1L425 159C418.1 152.1 407.8 150.1 398.8 153.8C389.8 157.5 384 166.3 384 176L384 256L272 256C245.5 256 224 277.5 224 304L224 336C224 362.5 245.5 384 272 384L384 384L384 464C384 473.7 389.8 482.5 398.8 486.2C407.8 489.9 418.1 487.9 425 481L569 337zM224 160C241.7 160 256 145.7 256 128C256 110.3 241.7 96 224 96L160 96C107 96 64 139 64 192L64 448C64 501 107 544 160 544L224 544C241.7 544 256 529.7 256 512C256 494.3 241.7 480 224 480L160 480C142.3 480 128 465.7 128 448L128 192C128 174.3 142.3 160 160 160L224 160z"/></svg>
                &nbsp; <?= $lang['DISCHARGE'] ?? 'Discharge' ?>
            </button>

        </div>
    </td>

    <td colspan="3" class="align-middle">
        <?php if (!empty($taskIcons)): ?>
        <div style="display:flex; gap:6px; justify-content:center; align-items:center;">
            <?php foreach ($taskIcons as $icon): ?>
                <?php
                    $isCompleted = ($icon['status'] === 'Completed');
                    $color = $isCompleted ? '#2ecc71' : '#bdc3c7';

                    if ($isCompleted && !empty($icon['completed_date_time'])) {
                        $by  = trim(($icon['first_name'] ?? '') . ' ' . ($icon['last_name'] ?? ''));
                        if ($by === '') $by = ($lang['TASK_UNKNOWN_USER'] ?? 'Unknown user');
                        $dt  = date('d-m-Y H:i', strtotime($icon['completed_date_time']));
                        $tooltip_tpl = ($lang['TASK_TOOLTIP_COMPLETED'] ?? 'Patient had {task} - Completed by {by} on {dt}');
                        $tooltip = strtr($tooltip_tpl, ['{task}' => $icon['task'], '{by}' => $by, '{dt}' => $dt]);
                    } else {
                        $tooltip_tpl = ($lang['TASK_TOOLTIP_REQUIRES'] ?? 'Requires {task} (mark to complete)');
                        $tooltip = strtr($tooltip_tpl, ['{task}' => $icon['task']]);
                    }

                    $svg = $icon['svg'];
                    $svgColored = preg_replace('/fill="[^"]*"/i', 'fill="'.$color.'"', $svg);

                    if (!preg_match('/fill="/i', $svgColored)) {
                        $svgColored = preg_replace('/<path/i', '<path fill="'.$color.'"', $svgColored, 1);
                    }

                    $svgColored = preg_replace(
                        '/<svg([^>]*)>/i',
                        '<svg$1 width="30" height="30" style="width:30px;height:30px;">',
                        $svgColored,
                        1
                    );
                ?>
                <span class="task-icon"
                      data-task-pt-id="<?php echo $icon['task_pt_id']; ?>"
                      data-is-complete="<?php echo $isCompleted ? '1' : '0'; ?>"
                      title="<?php echo htmlspecialchars($tooltip); ?>"
                      style="
                          display:inline-block;
                          width:30px;
                          height:30px;
                          cursor:<?php echo $isCompleted ? 'default' : 'pointer'; ?>;
                      ">
                    <?php echo $svgColored; ?>
                </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </td>
</tr>

<tr id="panel-<?= $pid ?>" class="patient-panel" style="display:none;" data-patient-id="<?= $pid ?>">
    <td colspan="7">

        <?php foreach ($sections as $key => $label): ?>
            <div id="form-<?= $key ?>-<?= $pid ?>" class="form-container" style="display:none;">
                <?php
                    $patient_id   = $pid;
                    $patient_name = $pname;
                    include $residentsControllerPath . '/' . $form_map[$key];
                ?>
            </div>
        <?php endforeach; ?>
        <hr style="border:0; border-top:3px solid #00065cff; margin:2px 0;">

    </td>
</tr>

</tbody>

<?php endforeach; ?>

    </tbody>
</table>

<?php endif; ?>

<?php
@include $residentsControllerPath . '/medicationlist.php';
?>

<script>
// Button row: open a specific section for a patient
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll(".open-section").forEach(function (btn) {
        btn.addEventListener("click", function () {

            const pid     = btn.getAttribute("data-pid");
            const section = btn.getAttribute("data-section");
            if (!pid || !section) return;

            const panel = document.getElementById("panel-" + pid);
            if (!panel) return;

            document.querySelectorAll(".patient-panel").forEach(function (p) {
                if (p !== panel) p.style.display = "none";
            });

            panel.style.display = "table-row";

            panel.querySelectorAll(".form-container").forEach(function (c) {
                c.style.display = "none";
            });

            const container = document.getElementById("form-" + section + "-" + pid);
            if (container) container.style.display = "block";
        });
    });

});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const pageSizeSelect      = document.getElementById('pageSize');
    const searchInput         = document.getElementById('tableSearch');
    const paginationContainer = document.getElementById('pagination');
    const tabLinks            = document.querySelectorAll('#locationTabs .loc-tab');

    const mainRows = Array.from(document.querySelectorAll(
        'tr.patient-row[data-main="1"]'
    ));

    const patientBlocks = mainRows.map(row => {
        const pid  = row.dataset.patientId;
        const area = row.dataset.area || 'Unassigned';

        const rows = Array.from(document.querySelectorAll(
            'tr[data-patient-id="' + pid + '"]'
        ));

        return { pid, area, rows };
    });

    let currentArea  = 'all';
    let currentPage  = 1;
    let pageSize     = pageSizeSelect ? parseInt(pageSizeSelect.value,10) : 10;
    let searchTerm   = '';

    function renderPagination(activePage, totalPages) {
        if (!paginationContainer) return;
        paginationContainer.innerHTML = '';

        if (!totalPages || totalPages <= 1) return;

        const ul = document.createElement('ul');
        ul.className = 'pagination pagination-sm'; 

        function add(label, page, disabled, active) {
            const li = document.createElement('li');
            li.className = 'page-item';
            if (disabled) li.classList.add('disabled');
            if (active)   li.classList.add('active');

            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = label;

            if (!disabled) {
                a.addEventListener('click', function (e) {
                    e.preventDefault();
                    currentPage = page;
                    applyFilters();
                });
            }

            li.appendChild(a);
            ul.appendChild(li);
        }

        add('«', activePage - 1, activePage === 1, false);

        for (let p = 1; p <= totalPages; p++) {
            add(p, p, false, p === activePage);
        }

        add('»', activePage + 1, activePage === totalPages, false);

        paginationContainer.appendChild(ul);
    }

    function applyFilters() {

        const term = searchTerm.trim().toLowerCase();

        const filtered = patientBlocks.filter(block => {

            const matchesArea = (currentArea === 'all' || block.area === currentArea);
            if (!matchesArea) return false;

            if (!term) return true;

            const mainRow = document.querySelector(
                'tr.patient-row[data-main="1"][data-patient-id="' + block.pid + '"]'
            );
            if (!mainRow) return false;

            const text = mainRow.textContent.toLowerCase();
            return text.includes(term);
        });

        const totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));
        if (currentPage > totalPages) currentPage = totalPages;

        patientBlocks.forEach(block => {
            block.rows.forEach(r => r.style.display = 'none');
        });

        if (filtered.length === 0) {
            renderPagination(0, 0);
            return;
        }

        const start = (currentPage - 1) * pageSize;
        const end   = start + pageSize;
        const pageItems = filtered.slice(start, end);

        pageItems.forEach(block => {
            block.rows.forEach(r => r.style.display = '');
        });

        renderPagination(currentPage, totalPages);
    }

    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', function () {
            pageSize   = parseInt(this.value, 10);
            currentPage = 1;
            savePageSizePreference(pageSize);
            applyFilters();
        });
    }

    function savePageSizePreference(size) {
        const allowed = [10, 20, 25, 50, 100, 9999];
        if (!allowed.includes(size)) return;

        const body = new URLSearchParams();
        body.set('per_page', String(size));

        fetch('ajax/save_my_patients_per_page.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: body.toString()
        }).catch(() => {});
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            searchTerm  = this.value;
            currentPage = 1;
            applyFilters();
        });
    }

    tabLinks.forEach(tab => {
        tab.addEventListener('click', function (e) {
            e.preventDefault();
            tabLinks.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            currentArea = this.dataset.area || 'all';
            currentPage = 1;
            applyFilters();
        });
    });

    applyFilters();

});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.task-icon').forEach(function (icon) {

        icon.addEventListener('click', function () {

            if (icon.dataset.isComplete === '1') return;

            const taskPtId = icon.dataset.taskPtId;
            if (!taskPtId) return;

            fetch('/controllers/form_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    complete_task: 1,
                    task_pt_id: taskPtId
                })
            })
            .then(res => res.text())
            .then(response => {
                if (response.trim() === 'OK') {
                    icon.dataset.isComplete = '1';
                    icon.style.cursor = 'default';
                    const svg = icon.querySelector('svg');
                    if (svg) {
                        svg.querySelectorAll('path').forEach(p => {
                            p.setAttribute('fill', '#2ecc71');
                        });
                    }
                } else {
                    alert('<?= addslashes($lang['TASK_COULD_NOT_BE_COMPLETED'] ?? 'Task could not be completed.') ?>');
                }
            })
            .catch(() => {
                alert('<?= addslashes($lang['NETWORK_ERROR_COMPLETING_TASK'] ?? 'Network error completing task.') ?>');
            });
        });

    });

});
</script>
