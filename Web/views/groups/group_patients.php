<?php
// views/groups/group_patients.php

// --------------------------------------------------
// DEBUG (TEMP) — uncomment if needed
// --------------------------------------------------
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// --------------------------------------------------
// PERMISSIONS (your engine)
// --------------------------------------------------
require_once __DIR__ . '/../../operations/permissions.php';

/**
 * Register + check an action permission.
 * Forces external-share role (rescue_role = 8) for this page only.
 */
$external_share_role = 8;

function can_action(string $key, string $description = ''): bool
{
    global $external_share_role;

    registerPermission($key, $description, 'action');

    $prevSessionRole = $_SESSION['rescue_role'] ?? null;
    $hadSessionRole  = array_key_exists('rescue_role', $_SESSION ?? []);
    $prevGlobalRole  = $GLOBALS['rescue_role'] ?? null;
    $hadGlobalRole   = array_key_exists('rescue_role', $GLOBALS);

    $_SESSION['rescue_role'] = $external_share_role;
    $GLOBALS['rescue_role']  = $external_share_role;

    try {
        return can($key);
    } finally {
        if ($hadSessionRole) {
            $_SESSION['rescue_role'] = $prevSessionRole;
        } else {
            unset($_SESSION['rescue_role']);
        }

        if ($hadGlobalRole) {
            $GLOBALS['rescue_role'] = $prevGlobalRole;
        } else {
            unset($GLOBALS['rescue_role']);
        }
    }
}

// Permission map for buttons/forms
$perm = [
    'careplan'     => ['key' => 'patients.careplan.view',           'desc' => 'View patient care plan'],
    'carenote'     => ['key' => 'patients.carenote.add',            'desc' => 'Add care note'],
    'observation'  => ['key' => 'patients.observation.add',         'desc' => 'Add observation'],
    'prescription' => ['key' => 'patients.prescription.add',        'desc' => 'Add prescription'],
    'medication'   => ['key' => 'patients.medication.administer',   'desc' => 'Administer medication'],
    'treatment'    => ['key' => 'patients.treatment.add',           'desc' => 'Add treatment'],
    'feeding'      => ['key' => 'patients.feeding.add',             'desc' => 'Add feeding'],
    'labs'         => ['key' => 'patients.labs.add',                'desc' => 'Add lab results'],
    'weight'       => ['key' => 'patients.weight.add',              'desc' => 'Add weight entry'],
    'measurement'  => ['key' => 'patients.measurement.add',         'desc' => 'Add measurement entry'],
    'tasks'        => ['key' => 'patients.tasks.add',               'desc' => 'Add quick task'],
    'discharge'    => ['key' => 'patients.discharge',               'desc' => 'Discharge patient'],
];

// centre_id must already be available from the template
$centre_id_int = isset($centre_id) ? (int)$centre_id : 0;

// --------------------------------------------------
// FETCH SHARED PATIENTS
// - Shows active group shares for the currently viewed network
// - Organises filter options by owner centre / rescue name
// --------------------------------------------------
$group_id = isset($_GET['network_id']) ? (int)$_GET['network_id'] : 0;
$current_account_id = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
$is_network_admin = !empty($network_is_admin);

$sql = "
    SELECT
        s.share_id,
        s.share_type,
        s.group_id,
        s.target_centre_id,
        s.target_account_id,
        s.owner_centre_id,
        s.created_at AS share_created_at,

        ownerc.rescue_name AS owner_rescue_name,

        a.admission_id,
        a.admission_date,
        a.presenting_complaint,
        a.current_location,
        a.current_location_id,
        a.disposition,

        p.patient_id,
        p.name,
        p.sex,
        p.animal_species,
        p.animal_type,
        p.state,
        DATEDIFF(NOW(), a.admission_date) AS daysincare,

        (a.bc_score + a.age_score + a.severity_score) AS wra,

        o.obs_bcs_score,
        o.obs_age_score,
        o.obs_severity_score,

        (
            COALESCE(o.obs_bcs_score, 99) +
            COALESCE(o.obs_age_score, 0) +
            COALESCE(o.obs_severity_score, 0)
        ) AS newwra

    FROM rescue_patient_shares s
    INNER JOIN rescue_patients p
        ON p.patient_id = s.patient_id
    LEFT JOIN rescue_admissions a
        ON a.patient_id = p.patient_id
    LEFT JOIN rescue_centres ownerc
        ON ownerc.rescue_id = s.owner_centre_id
    LEFT JOIN rescue_observations o
        ON o.patient_id = p.patient_id
        AND NOT EXISTS (
            SELECT 1
            FROM rescue_observations o2
            WHERE o2.patient_id = o.patient_id
              AND o2.obs_date > o.obs_date
        )
    WHERE
        s.status = 'active'
        AND s.share_type = 'group'
        AND s.group_id = ?
    ORDER BY
        COALESCE(ownerc.rescue_name, CONCAT('Centre #', s.owner_centre_id)) ASC,
        a.admission_date DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$group_id]);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --------------------------------------------------
// MEDICATION DEPENDENCIES (for add_medsadmin.php)
// --------------------------------------------------
@include __DIR__ . '/../../controllers/meds_dependencies.php';

// Sections map (used for buttons AND form containers)
$sections = [
    'carenote'     => 'Care Note',
    'observation'  => 'Observation',
    'prescription' => 'Prescription',
    'medication'   => 'Medication',
    'treatment'    => 'Treatment',
    'feeding'      => 'Feeding',
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
    'feeding'      => 'add_feed.php',
    'labs'         => 'add_labs.php',
    'weight'       => 'add_weight.php',
    'measurement'  => 'add_measurement.php',
    'tasks'        => 'add_tasks.php',
    'discharge'    => 'add_disposition.php'
];

// --------------------------------------------------
// Build centre filter options from owner centre / rescue name
// --------------------------------------------------
$centreOptions = [];
foreach ($patients as $row) {
    $ownerCentreId = isset($row['owner_centre_id']) ? (int)$row['owner_centre_id'] : 0;
    if ($ownerCentreId <= 0) {
        continue;
    }

    $ownerName = trim((string)($row['owner_rescue_name'] ?? ''));
    $centreOptions[$ownerCentreId] = ($ownerName !== '')
        ? $ownerName
        : ('Centre #' . $ownerCentreId);
}
asort($centreOptions, SORT_NATURAL | SORT_FLAG_CASE);
?>
<!-- GROUP PATIENTS.php -->
<!-- SEARCH + FILTER + PAGE SIZE -->
<div class="rc-panel rc-stack">
<div class="xform-grid">

    <div class="xform-field span-2">
        <input type="text"
               id="tableSearch"
               class="xform-input"
               placeholder="<?php echo htmlspecialchars($lang['TABLE_SEARCH_PLACEHOLDER_PATIENTS']); ?>">
    </div>

    <div class="xform-field">
        <label for="centreFilter" class="xform-label">
            Shared by
        </label>
        <select id="centreFilter" class="xform-input">
            <option value="all">All</option>
            <?php foreach ($centreOptions as $ownerCentreId => $label): ?>
                <option value="<?php echo (int)$ownerCentreId; ?>"><?php echo htmlspecialchars($label); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="xform-field">
        <label class="xform-label">
            <?php echo $lang['TABLE_SHOW']; ?>
            <select id="pageSize"
                    class="xform-input">
                <option value="10">10</option>
                <option value="20">20</option>
                <option value="50">50</option>
                <option value="9999"><?php echo $lang['PAT_TAB_ALL']; ?></option>
            </select>
            <?php echo $lang['TABLE_ENTRIES']; ?>
        </label>
    </div>

</div>
<?php if (empty($patients)): ?>
    <div class="rc-alert amber"><?php echo $lang['NO_PATIENTS_FOUND']; ?></div>
<?php else: ?>

<table class="table table-bordered table-sm table-hover table-modern patient-table" width="100%" cellspacing="0">
    <thead class="thead-dark">
        <tr>
            <th><?php echo $lang['PAT_TABLE_CRN_PATIENT']; ?></th>
            <th><?php echo $lang['PAT_TABLE_ADMISSION_DATE']; ?></th>
            <th><?php echo $lang['PAT_TABLE_DAYS_IN_CARE']; ?></th>
            <th><?php echo $lang['PAT_TABLE_PRESENTING_COMPLAINT']; ?></th>
            <th><?php echo $lang['PAT_TABLE_WRA_ADMISSION']; ?></th>
            <th><?php echo $lang['PAT_TABLE_WRA_CURRENT']; ?></th>
        </tr>
    </thead>
    <tbody>
<?php foreach ($patients as $p):
    $pid   = (int)$p['patient_id'];
    $pname = (string)($p['name'] ?? '');

    $owner_centre_id = isset($p['owner_centre_id']) ? (int)$p['owner_centre_id'] : 0;
    $owner_rescue_name = trim((string)($p['owner_rescue_name'] ?? ''));

    $share_label = ($owner_rescue_name !== '')
        ? $owner_rescue_name
        : (($owner_centre_id > 0) ? ('Centre #' . $owner_centre_id) : ($lang['PAT_UNASSIGNED'] ?? 'Unassigned'));

    $wra = (int)($p['wra'] ?? 99);
    $newwra = (int)($p['newwra'] ?? 99);

    if ($wra > 90) {
        $wra_class   = 'na';
        $wra_display = $lang['PAT_WRA_NA'];
    } elseif ($wra >= 6) {
        $wra_class   = 'bad';
        $wra_display = $wra;
    } elseif ($wra >= 3) {
        $wra_class   = 'warn';
        $wra_display = $wra;
    } else {
        $wra_class   = 'ok';
        $wra_display = $wra;
    }

    if ($newwra > 90) {
        $newwra_class   = 'na';
        $newwra_display = $lang['PAT_WRA_NA'];
    } elseif ($newwra >= 6) {
        $newwra_class   = 'bad';
        $newwra_display = $newwra;
    } elseif ($newwra >= 3) {
        $newwra_class   = 'warn';
        $newwra_display = $newwra;
    } else {
        $newwra_class   = 'ok';
        $newwra_display = $newwra;
    }

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
        WHERE tp.patient_id = ?
    ");
    $taskStmt->execute([$pid]);
    $taskIcons = $taskStmt->fetchAll(PDO::FETCH_ASSOC);

    $daysincare = (int)($p['daysincare'] ?? 0);
    $daysclass = match (true) {
        $daysincare > 120 => 'dark',
        $daysincare > 90  => 'bad',
        $daysincare > 60  => 'warn',
        $daysincare > 31  => 'mid',
        default           => 'ok',
    };

    $can_unshare = $is_network_admin || ($owner_centre_id > 0 && $owner_centre_id === $centre_id_int);
?>
<tbody class="patient-block" style="border:1px solid #ccc; border-radius:4px;">

<tr class="patient-row patient-block-row"
    data-main="1"
    data-patient-id="<?= $pid ?>"
    data-owner-centre-id="<?= (int)$owner_centre_id ?>">

    <td class="align-middle">
        <?php echo $lang['PAT_CRN']; ?>: <?= $pid ?> – <b><?= htmlspecialchars($pname) ?></b> (<?= htmlspecialchars((string)($p['sex'] ?? '')) ?>)
        <br><?= htmlspecialchars((string)($p['animal_species'] ?? '')) ?> (<?= htmlspecialchars((string)($p['animal_type'] ?? '')) ?>)
        <br><small>Shared by: <?= htmlspecialchars($share_label) ?></small>
    </td>

    <td class="align-middle">
        <?php if (!empty($p['admission_date'])): ?>
            <?php
                $dt = new DateTime((string)$p['admission_date']);
                $day = $dt->format('j');
                $suffix = date('S', $dt->getTimestamp());
                echo $day . $suffix . ' ' . $dt->format('F Y') . '<br>' . $dt->format('H:i');
            ?>
        <?php else: ?>
            <?= htmlspecialchars($lang['PAT_WRA_NA'] ?? 'N/A') ?>
        <?php endif; ?>
    </td>

    <td class="align-middle">
        <span class="rc-badge <?php echo htmlspecialchars($daysclass); ?>"><?= $daysincare ?></span>
    </td>

    <td class="align-middle">
        <?= htmlspecialchars((string)($p['presenting_complaint'] ?? '')) ?>
    </td>

    <td class="align-middle">
        <span class="rc-badge <?= htmlspecialchars($wra_class) ?>"><?= htmlspecialchars((string)$wra_display) ?></span>
    </td>

    <td class="align-middle">
        <span class="rc-badge <?= htmlspecialchars($newwra_class) ?>"><?= htmlspecialchars((string)$newwra_display) ?></span>
    </td>

</tr>

<tr data-main="0" data-patient-id="<?= $pid ?>">
    <td colspan="6" class="align-middle">

        <div class="btn-group">

            <?php if (can_action($perm['careplan']['key'], $perm['careplan']['desc'])): ?>
                <a href="viewpatient.php?patient_id=<?= $pid ?>" class="btn green" alt="<?php echo htmlspecialchars($lang['TIP_VIEW_CARE_PLAN']); ?>">
                    <svg svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><path d="M439.4 96L448 96C483.3 96 512 124.7 512 160L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 160C128 124.7 156.7 96 192 96L200.6 96C211.6 76.9 232.3 64 256 64L384 64C407.7 64 428.4 76.9 439.4 96zM376 176C389.3 176 400 165.3 400 152C400 138.7 389.3 128 376 128L264 128C250.7 128 240 138.7 240 152C240 165.3 250.7 176 264 176L376 176zM320 408C350.9 408 376 382.9 376 352C376 321.1 350.9 296 320 296C289.1 296 264 321.1 264 352C264 382.9 289.1 408 320 408zM226.3 477C213.4 492.6 228.5 512 248.7 512L391.2 512C411.4 512 426.5 492.6 413.6 477C398.9 459.3 376.7 448 351.9 448L287.9 448C263.1 448 240.9 459.3 226.2 477z"/></svg>
                    &nbsp; <?php echo $lang['BTN_CARE_PLAN']; ?>
                </a>
            <?php endif; ?>

            <?php if (can_action($perm['carenote']['key'], $perm['carenote']['desc'])): ?>
            <button title="<?php echo htmlspecialchars($lang['TIP_ADD_A_CARE_NOTE']); ?>" class="btn blue open-section" data-section="carenote" data-pid="<?= $pid ?>">
                <svg svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><path d="M160 416C160 451.3 188.7 480 224 480L405.5 480C422.5 480 438.8 473.3 450.8 461.3L557.3 354.7C569.3 342.7 576 326.4 576 309.4L576 128C576 92.7 547.3 64 512 64L224 64C188.7 64 160 92.7 160 128L160 416zM352 176L384 176C392.8 176 400 183.2 400 192L400 240L448 240C456.8 240 464 247.2 464 256L464 288C464 296.8 456.8 304 448 304L400 304L400 352C400 360.8 392.8 368 384 368L352 368C343.2 368 336 360.8 336 352L336 304L288 304C279.2 304 272 296.8 272 288L272 256C272 247.2 279.2 240 288 240L336 240L336 192C336 183.2 343.2 176 352 176zM112 184C112 170.7 101.3 160 88 160C74.7 160 64 170.7 64 184L64 512C64 547.3 92.7 576 128 576L392 576C405.3 576 416 565.3 416 552C416 538.7 405.3 528 392 528L128 528C119.2 528 112 520.8 112 512L112 184z"/></svg>
            </button>
            <?php endif; ?>

            <?php if (can_action($perm['observation']['key'], $perm['observation']['desc'])): ?>
            <button title="<?php echo htmlspecialchars($lang['TIP_ADD_AN_OBSERVATION']); ?>" class="btn blue open-section" data-section="observation" data-pid="<?= $pid ?>">
                <svg svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><path d="M128 128C128 110.3 113.7 96 96 96C78.3 96 64 110.3 64 128L64 464C64 508.2 99.8 544 144 544L544 544C561.7 544 576 529.7 576 512C576 494.3 561.7 480 544 480L144 480C135.2 480 128 472.8 128 464L128 128zM534.6 214.6C547.1 202.1 547.1 181.8 534.6 169.3C522.1 156.8 501.8 156.8 489.3 169.3L384 274.7L326.6 217.4C314.1 204.9 293.8 204.9 281.3 217.4L185.3 313.4C172.8 325.9 172.8 346.2 185.3 358.7C197.8 371.2 218.1 371.2 230.6 358.7L304 285.3L361.4 342.7C373.9 355.2 394.2 355.2 406.7 342.7L534.7 214.7z"/></svg>
            </button>
            <?php endif; ?>

            <?php if (can_action($perm['prescription']['key'], $perm['prescription']['desc'])): ?>
            <button title="<?php echo htmlspecialchars($lang['TIP_ADD_A_PRESCRIPTION']); ?>" class="btn blue open-section" data-section="prescription" data-pid="<?= $pid ?>">
                <svg svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><path d="M128 64C110.3 64 96 78.3 96 96L96 352C96 369.7 110.3 384 128 384C145.7 384 160 369.7 160 352L160 288L210.7 288L338.7 416L233.3 521.4C220.8 533.9 220.8 554.2 233.3 566.7C245.8 579.2 266.1 579.2 278.6 566.7L384 461.3L489.4 566.6C501.9 579.1 522.2 579.1 534.7 566.6C547.2 554.1 547.2 533.8 534.7 521.3L429.3 416L534.6 310.6C547.1 298.1 547.1 277.8 534.6 265.3C522.1 252.8 501.8 252.8 489.3 265.3L384 370.7L298.2 284.9C347.4 273.1 384 228.8 384 176C384 114.1 333.9 64 272 64L128 64zM272 224L160 224L160 128L272 128C298.5 128 320 149.5 320 176C320 202.5 298.5 224 272 224z"/></svg>
            </button>
            <?php endif; ?>

            <?php if (can_action($perm['medication']['key'], $perm['medication']['desc'])): ?>
            <button title="<?php echo htmlspecialchars($lang['TIP_ADMINISTER_A_MEDICATION']); ?>" class="btn blue open-section" data-section="medication" data-pid="<?= $pid ?>">
                <svg svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><path d="M128 176C128 149.5 149.5 128 176 128C202.5 128 224 149.5 224 176L224 288L128 288L128 176zM240 432C240 383.3 258.1 338.8 288 305L288 176C288 114.1 237.9 64 176 64C114.1 64 64 114.1 64 176L64 464C64 525.9 114.1 576 176 576C213.3 576 246.3 557.8 266.7 529.7C249.7 501.1 240 467.7 240 432zM304.7 499.4C309.3 508.1 321 509.1 328 502.1L502.1 328C509.1 321 508.1 309.3 499.4 304.7C479.3 294 456.4 288 432 288C352.5 288 288 352.5 288 432C288 456.3 294 479.3 304.7 499.4zM361.9 536C354.9 543 355.9 554.7 364.6 559.3C384.7 570 407.6 576 432 576C511.5 576 576 511.5 576 432C576 407.7 570 384.7 559.3 364.6C554.7 355.9 543 354.9 536 361.9L361.9 536z"/></svg>
            </button>
            <?php endif; ?>

            <?php if (can_action($perm['treatment']['key'], $perm['treatment']['desc'])): ?>
            <button title="<?php echo htmlspecialchars($lang['TIP_ADD_A_TREATMENT']); ?>" class="btn blue open-section" data-section="treatment" data-pid="<?= $pid ?>">
                <svg svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><path d="M160 141.3C160 134 165.9 128 173.3 128C176.8 128 180.2 129.4 182.7 131.9L197.6 146.8C194 155.9 192.1 165.7 192.1 176C192.1 195.9 199.3 214 211.3 228C206 237.2 207.3 249.1 215.1 257C224.5 266.4 239.7 266.4 249 257L353 153C362.4 143.6 362.4 128.4 353 119.1C345.2 111.2 333.2 110 324 115.3C310 103.3 291.9 96.1 272 96.1C261.7 96.1 251.8 98.1 242.8 101.6L227.9 86.6C213.4 72.1 193.7 64 173.3 64C130.6 64 96 98.6 96 141.3L96 320C78.3 320 64 334.3 64 352C64 369.7 78.3 384 96 384L96 432C96 460.4 108.4 486 128 503.6L128 544C128 561.7 142.3 576 160 576C177.7 576 192 561.7 192 544L192 528L448 528L448 544C448 561.7 462.3 576 480 576C497.7 576 512 561.7 512 544L512 503.6C531.6 486 544 460.5 544 432L544 384C561.7 384 576 369.7 576 352C576 334.3 561.7 320 544 320L160 320L160 141.3z"/></svg>
            </button>
            <?php endif; ?>

            <?php if (can_action($perm['feeding']['key'], $perm['feeding']['desc'])): ?>
            <button title="<?php echo htmlspecialchars($lang['TIP_ADD_A_FEEDING']); ?>" class="btn blue open-section" data-section="feeding" data-pid="<?= $pid ?>">
                <svg svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><path d="M127.9 78.4C127.1 70.2 120.2 64 112 64C103.8 64 96.9 70.2 96 78.3L81.9 213.7C80.6 219.7 80 225.8 80 231.9C80 277.8 115.1 315.5 160 319.6L160 544C160 561.7 174.3 576 192 576C209.7 576 224 561.7 224 544L224 319.6C268.9 315.5 304 277.8 304 231.9C304 225.8 303.4 219.7 302.1 213.7L287.9 78.3C287.1 70.2 280.2 64 272 64C263.8 64 256.9 70.2 256.1 78.4L242.5 213.9C241.9 219.6 237.1 224 231.4 224C225.6 224 220.8 219.6 220.2 213.8L207.9 78.6C207.2 70.3 200.3 64 192 64C183.7 64 176.8 70.3 176.1 78.6L163.8 213.8C163.3 219.6 158.4 224 152.6 224C146.8 224 142 219.6 141.5 213.9L127.9 78.4zM512 64C496 64 384 96 384 240L384 352C384 387.3 412.7 416 448 416L480 416L480 544C480 561.7 494.3 576 512 576C529.7 576 544 561.7 544 544L544 96C544 78.3 529.7 64 512 64z"/></svg>
            </button>
            <?php endif; ?>

            <?php if (can_action($perm['labs']['key'], $perm['labs']['desc'])): ?>
            <button title="<?php echo htmlspecialchars($lang['TIP_ADD_LAB_RESULTS']); ?>" class="btn blue open-section" data-section="labs" data-pid="<?= $pid ?>">
                <svg svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><path d="M184.6 475.5C181.5 482.8 179.2 490.4 177.8 498.1C163.3 506.9 146.3 512 128 512C75 512 32 469 32 416L32 128C14.3 128 0 113.7 0 96C0 78.3 14.3 64 32 64L224 64C241.7 64 256 78.3 256 96C256 113.7 241.7 128 224 128L224 383.6L184.6 475.5zM96 128L96 256L160 256L160 128L96 128zM352 64L512 64C529.7 64 544 78.3 544 96C544 113.7 529.7 128 512 128L512 281.4L603.3 494.4C605.6 499.8 607.1 505.5 607.7 511.4L608 512L607.7 512L608 512C607.9 513.8 608 515.6 608 517.4C608 549.7 581.8 576 549.4 576L282.5 576C250.2 576 223.9 549.8 223.9 517.4C223.9 515.6 224 513.8 224.2 512L223.9 512L224.2 511.4C224.8 505.6 226.3 499.8 228.6 494.4L320 281.4L320 128C302.3 128 288 113.7 288 96C288 78.3 302.3 64 320 64L352 64zM453.2 306.6C449.8 298.6 448 290.1 448 281.4L448 128L384 128L384 281.4C384 290.1 382.2 298.6 378.8 306.6L345.6 384L486.3 384L453.1 306.6z"/></svg>
            </button>
            <?php endif; ?>

            <?php if (can_action($perm['weight']['key'], $perm['weight']['desc'])): ?>
            <button title="<?php echo htmlspecialchars($lang['TIP_ADD_A_WEIGHT']); ?>" class="btn grey open-section" data-section="weight" data-pid="<?= $pid ?>">
                <svg svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><path d="M212.6 256C209.6 245.9 208 235.1 208 224C208 162.1 258.1 112 320 112C381.9 112 432 162.1 432 224C432 235.1 430.4 245.9 427.4 256L356.4 256L381 211.7C387.4 200.1 383.3 185.5 371.7 179.1C360.1 172.7 345.5 176.8 339.1 188.4L301.5 256.1L212.7 256.1zM224 96L160 96C124.7 96 96 124.7 96 160L96 480C96 515.3 124.7 544 160 544L480 544C515.3 544 544 515.3 544 480L544 160C544 124.7 515.3 96 480 96L416 96C389.3 75.9 356 64 320 64C284 64 250.7 75.9 224 96z"/></svg>
            </button>
            <?php endif; ?>

            <?php if (can_action($perm['measurement']['key'], $perm['measurement']['desc'])): ?>
            <button title="<?php echo htmlspecialchars($lang['TIP_ADD_A_MEASUREMENT']); ?>" class="btn grey open-section" data-section="measurement" data-pid="<?= $pid ?>">
                <svg svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><path d="M80 448C53.5 448 32 426.5 32 400L32 240C32 213.5 53.5 192 80 192L104 192L104 296C104 309.3 114.7 320 128 320C141.3 320 152 309.3 152 296L152 192L200 192L200 264C200 277.3 210.7 288 224 288C237.3 288 248 277.3 248 264L248 192L296 192L296 296C296 309.3 306.7 320 320 320C333.3 320 344 309.3 344 296L344 192L392 192L392 264C392 277.3 402.7 288 416 288C429.3 288 440 277.3 440 264L440 192L488 192L488 296C488 309.3 498.7 320 512 320C525.3 320 536.7 309.3 536 296L536 192L560 192C586.5 192 608 213.5 608 240L608 400C608 426.5 586.5 448 560 448L80 448z"/></svg>
            </button>
            <?php endif; ?>

            <?php if ($can_unshare): ?>
                <form method="post"
                      action="controllers/groups_handler.php"
                      style="margin:0; display:inline-flex; align-items:center; vertical-align:top; line-height:0;"
                      onsubmit="return confirm('Remove this patient from the shared patients list?');">
                    <input type="hidden" name="action" value="unshare_patient">
                    <input type="hidden" name="group_id" value="<?= (int)$group_id ?>">
                    <input type="hidden" name="share_id" value="<?= (int)($p['share_id'] ?? 0) ?>">
                    <button type="submit" class="btn red">Unshare</button>
                </form>
            <?php endif; ?>

        </div>
    </td>
</tr>

<tr id="panel-<?= $pid ?>" class="patient-panel" style="display:none;" data-patient-id="<?= $pid ?>">
    <td colspan="6">

        <?php foreach ($sections as $key => $label): ?>
            <?php
                if (!isset($perm[$key])) continue;

                $pkey  = $perm[$key]['key'];
                $pdesc = $perm[$key]['desc'];

                if (!can_action($pkey, $pdesc)) continue;
            ?>
            <div id="form-<?= $key ?>-<?= $pid ?>" class="form-container rc-card rc-card-muted" style="display:none;">
                <?php
                    $patient_id   = $pid;
                    $patient_name = $pname;
                    $admission_id = (int)($p['admission_id'] ?? 0);
                    include __DIR__ . '/../../controllers/' . $form_map[$key];
                ?>
            </div>
        <?php endforeach; ?>

    </td>
</tr>
</tbody>
<?php endforeach; ?>
    </tbody>
</table>

<div id="pagination"></div>

<?php endif; ?>
</div>

<?php
@include __DIR__ . '/../../controllers/medicationlist.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.clickable-row').forEach(function (row) {
        row.addEventListener('click', function () {
            const href = row.getAttribute('data-href');
            if (href) window.location = href;
        });
    });

    document.querySelectorAll('.open-section').forEach(function (btn) {
        btn.addEventListener('click', function () {

            const pid     = btn.getAttribute('data-pid');
            const section = btn.getAttribute('data-section');
            if (!pid || !section) return;

            const panel = document.getElementById('panel-' + pid);
            if (!panel) return;

            document.querySelectorAll('.patient-panel').forEach(function (p) {
                if (p !== panel) p.style.display = 'none';
            });

            panel.style.display = 'table-row';

            panel.querySelectorAll('.form-container').forEach(function (c) {
                c.style.display = 'none';
            });

            const container = document.getElementById('form-' + section + '-' + pid);
            if (container) container.style.display = 'block';
        });
    });

});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const pageSizeSelect      = document.getElementById('pageSize');
    const searchInput         = document.getElementById('tableSearch');
    const centreFilter        = document.getElementById('centreFilter');
    const paginationContainer = document.getElementById('pagination');

    const mainRows = Array.from(document.querySelectorAll(
        'tr[data-main="1"][data-patient-id]'
    ));

    const patientBlocks = mainRows.map(function (row) {
        const pid = String(row.dataset.patientId || '').trim();
        const ownerCentreId = String(row.dataset.ownerCentreId || '0').trim();

        const rows = Array.from(document.querySelectorAll(
            'tr[data-patient-id="' + pid + '"]'
        ));

        return { pid, ownerCentreId, rows };
    });

    let currentOwnerCentreId = centreFilter ? String(centreFilter.value || 'all').trim() : 'all';
    let currentPage = 1;
    let pageSize = pageSizeSelect ? parseInt(pageSizeSelect.value, 10) : 10;
    let searchTerm = '';

    function renderPagination(activePage, totalPages) {
        if (!paginationContainer) return;
        paginationContainer.innerHTML = '';

        if (!totalPages || totalPages <= 1) return;

        const nav = document.createElement('div');
        nav.className = 'rc-pager';

        function add(label, page, disabled, active) {
            const a = document.createElement('a');
            a.className = 'rc-pager-btn';
            if (disabled) a.classList.add('disabled');
            if (active) a.classList.add('active');
            a.href = '#';
            a.textContent = label;

            if (!disabled) {
                a.addEventListener('click', function (e) {
                    e.preventDefault();
                    currentPage = page;
                    applyFilters();
                });
            }

            nav.appendChild(a);
        }

        add('<?php echo addslashes($lang['PAG_PREV']); ?>', activePage - 1, activePage === 1, false);

        for (let p = 1; p <= totalPages; p++) {
            add(p, p, false, p === activePage);
        }

        add('<?php echo addslashes($lang['PAG_NEXT']); ?>', activePage + 1, activePage === totalPages, false);

        paginationContainer.appendChild(nav);
    }

    function applyFilters() {
        const term = searchTerm.trim().toLowerCase();

        const filtered = patientBlocks.filter(function (block) {
            const matchesCentre = (
                currentOwnerCentreId === 'all' ||
                block.ownerCentreId === currentOwnerCentreId
            );
            if (!matchesCentre) return false;

            if (!term) return true;

            const mainRow = document.querySelector(
                'tr[data-main="1"][data-patient-id="' + block.pid + '"]'
            );
            if (!mainRow) return false;

            return mainRow.textContent.toLowerCase().includes(term);
        });

        const totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));
        if (currentPage > totalPages) currentPage = totalPages;

        patientBlocks.forEach(function (block) {
            block.rows.forEach(function (r) {
                r.style.display = 'none';
            });
        });

        if (filtered.length === 0) {
            renderPagination(0, 0);
            return;
        }

        const start = (currentPage - 1) * pageSize;
        const end   = start + pageSize;
        const pageItems = filtered.slice(start, end);

        pageItems.forEach(function (block) {
            block.rows.forEach(function (r) {
                r.style.display = '';
            });
        });

        renderPagination(currentPage, totalPages);
    }

    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', function () {
            pageSize = parseInt(this.value, 10);
            currentPage = 1;
            applyFilters();
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            searchTerm = this.value;
            currentPage = 1;
            applyFilters();
        });
    }

    if (centreFilter) {
        centreFilter.addEventListener('change', function () {
            currentOwnerCentreId = String(this.value || 'all').trim();
            currentPage = 1;
            applyFilters();
        });
    }

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
                    alert('<?php echo addslashes($lang['TASK_COULD_NOT_BE_COMPLETED']); ?>');
                }

            })
            .catch(() => {
                alert('<?php echo addslashes($lang['NETWORK_ERROR_COMPLETING_TASK']); ?>');
            });
        });

    });

    const params = new URLSearchParams(window.location.search);
    const pid    = params.get('pid');
    const open   = params.get('open');

    if (!pid || !open) return;

    const panel = document.getElementById('panel-' + pid);
    if (!panel) return;

    document.querySelectorAll('.patient-panel').forEach(function (p) {
        p.style.display = 'none';
    });

    panel.style.display = 'table-row';

    panel.querySelectorAll('.form-container').forEach(function (c) {
        c.style.display = 'none';
    });

    const container = document.getElementById('form-' + open + '-' + pid);
    if (container) container.style.display = 'block';
});
</script>

