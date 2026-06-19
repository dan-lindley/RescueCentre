<?php
// views/my_patients.php

// --------------------------------------------------
// DEBUG (TEMP) — uncomment if needed
// --------------------------------------------------
 ini_set('display_errors', '1');
 ini_set('display_startup_errors', '1');
 error_reporting(E_ALL);

// --------------------------------------------------
// PERMISSIONS (your engine)
// --------------------------------------------------
require_once __DIR__ . '/../operations/permissions.php';
require_once __DIR__ . '/../core/icons.php';

/**
 * Register + check an action permission.
 * - Ensures the permission exists in DB (auto-create)
 * - Returns true only if the current user is allowed
 */
function can_action(string $key, string $description = ''): bool
{
    registerPermission($key, $description, 'action');
    return can($key);
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
    'move'         => ['key' => 'patients.movements.add',           'desc' => 'Move or transfer patient'],
    'discharge'    => ['key' => 'patients.discharge',               'desc' => 'Discharge patient'],
];

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

$cohortLocationPanelHtml = '';
try {
    require_once __DIR__ . '/../operations/modules_registry.php';
    $cohortsModule = modules_find($pdo, 'cohorts');
    if ($cohortsModule && !empty($cohortsModule['installed']) && !empty($cohortsModule['enabled'])) {
        require_once __DIR__ . '/../modules/cohorts/controllers/my_patients_hook.php';
        $cohortLocationPanelHtml = cohorts_module_render_my_patients_location_panel($centre_id_int);
    }
} catch (Throwable $e) {
    $cohortLocationPanelHtml = '';
}

try {
    require_once __DIR__ . '/../operations/patient_row_actions.php';
    $patientRowActionProviders = patient_row_actions_load_providers($pdo, $centre_id_int);
} catch (Throwable $e) {
    $patientRowActionProviders = [];
}

$patientRowActionContext = [
    'lang' => $lang ?? [],
    'centre_id' => $centre_id_int,
    'providers' => $patientRowActionProviders,
];

// --------------------------------------------------
// ZONE SWITCHER
// --------------------------------------------------
$zone_id = isset($_GET['zone_id']) ? (int)$_GET['zone_id'] : 0;

$zonesStmt = $pdo->prepare("
    SELECT zone_id, zone_name
    FROM rescue_zones
    WHERE centre_id = ?
      AND is_active = 1
    ORDER BY sort_order ASC, zone_name ASC
");
$zonesStmt->execute([$centre_id_int]);
$zones = $zonesStmt->fetchAll(PDO::FETCH_ASSOC);

if (count($zones) === 1) {
    $zone_id = (int)$zones[0]['zone_id'];
}
if ($zone_id <= 0 && !empty($zones)) {
    $zone_id = (int)$zones[0]['zone_id'];
}

// Locations for patient move/transfer forms, grouped by area.
$locStmt = $pdo->prepare("
    SELECT location_id, location_area, location_name
    FROM rescue_locations
    WHERE deleted = 0
      AND centre_id = :centre_id
    ORDER BY location_area, location_name
");
$locStmt->execute([':centre_id' => $centre_id_int]);

$locations_by_area = [];
while ($row = $locStmt->fetch(PDO::FETCH_ASSOC)) {
    $area = trim((string)($row['location_area'] ?? ''));
    if ($area === '') {
        $area = 'Other';
    }
    $locations_by_area[$area][] = [
        'location_id' => (int)$row['location_id'],
        'location_name' => (string)$row['location_name'],
    ];
}

// --------------------------------------------------
// FETCH PATIENTS
// - Resolve locations by ID only (current_location_id)
// - If current_location_id is NULL/0 or invalid, patient appears under the Unknown tab
// - Resolve areas/zones by ID only (rescue_locations.area_id -> rescue_areas.area_id)
// - rescue_locations uses `deleted`; patient/admission soft deletes use `is_deleted`
// - Filter by zone only when a valid zone is resolved
// --------------------------------------------------
$myPatientsSoftDeleteFilter = '';
try {
    $softDeleteCols = $pdo->prepare("
        SELECT TABLE_NAME, COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME IN ('rescue_admissions', 'rescue_patients')
          AND COLUMN_NAME = 'is_deleted'
    ");
    $softDeleteCols->execute();
    $softDeleteMap = [];
    foreach ($softDeleteCols->fetchAll(PDO::FETCH_ASSOC) ?: [] as $softDeleteCol) {
        $softDeleteMap[(string)$softDeleteCol['TABLE_NAME']] = true;
    }
    if (!empty($softDeleteMap['rescue_admissions'])) {
        $myPatientsSoftDeleteFilter .= "\n        AND (COALESCE(a.is_deleted, 0) = 0)";
    }
    if (!empty($softDeleteMap['rescue_patients'])) {
        $myPatientsSoftDeleteFilter .= "\n        AND (COALESCE(p.is_deleted, 0) = 0)";
    }
} catch (Throwable $e) {
    $myPatientsSoftDeleteFilter = '';
}

$sql = "
    SELECT
        a.admission_id,
        a.admission_date,
        a.presenting_complaint,
        a.current_location,
        a.current_location_id,

        p.patient_id,
        p.name,
        p.sex,
        p.animal_species,
        p.animal_type,
        DATEDIFF(NOW(), a.admission_date) AS daysincare,

        (a.bc_score + a.age_score + a.severity_score) AS wra,

        o.obs_bcs_score,
        o.obs_age_score,
        o.obs_severity_score,

        (
            COALESCE(o.obs_bcs_score, 99) +
            COALESCE(o.obs_age_score, 0) +
            COALESCE(o.obs_severity_score, 0)
        ) AS newwra,

        -- resolved location (ID only)
        NULLIF(a.current_location_id, 0) AS resolved_location_id,

        rl.location_id,
        rl.location_name,
        rl.location_area,
        rl.area_id AS location_area_id,

        -- area via ID
        ra_id.area_id   AS area_id_by_id,
        ra_id.area_name AS area_name_by_id,
        ra_id.zone_id   AS zone_id_by_id,

        -- resolved area/zone
        ra_id.area_id       AS resolved_area_id,
        ra_id.area_name     AS resolved_area_name,
        ra_id.zone_id       AS resolved_zone_id,

        rz.zone_name AS resolved_zone_name

    FROM rescue_admissions a
    LEFT JOIN rescue_patients p
        ON a.patient_id = p.patient_id

    LEFT JOIN rescue_observations o
        ON o.patient_id = a.patient_id
        AND NOT EXISTS (
            SELECT 1
            FROM rescue_observations o2
            WHERE o2.patient_id = o.patient_id
              AND o2.obs_date > o.obs_date
        )

    -- Join resolved location
    LEFT JOIN rescue_locations rl
        ON rl.location_id = NULLIF(a.current_location_id, 0)
       AND rl.centre_id   = ?
       AND rl.deleted     = 0

    -- Area via ID
    LEFT JOIN rescue_areas ra_id
        ON ra_id.area_id   = rl.area_id
       AND ra_id.centre_id = ?

    -- Zone name from resolved zone_id
    LEFT JOIN rescue_zones rz
        ON rz.zone_id   = ra_id.zone_id
       AND rz.centre_id = ?
       AND rz.is_active = 1

    WHERE
        p.centre_id = ?
        AND a.disposition = 'Held in captivity'
        AND p.state = 'Admitted'
        {$myPatientsSoftDeleteFilter}
        AND (
            ? = 0
            OR ra_id.zone_id = ?
            OR ra_id.zone_id IS NULL
        )

    ORDER BY
        CASE WHEN ra_id.area_name IS NULL THEN 1 ELSE 0 END ASC,
        ra_id.area_name ASC,
        CASE WHEN rl.location_name IS NULL THEN 1 ELSE 0 END ASC,
        rl.location_name ASC,
        a.admission_date DESC
";

$params = [
    $centre_id_int, // rl.centre_id
    $centre_id_int, // ra_id.centre_id
    $centre_id_int, // rz.centre_id
    $centre_id_int, // p.centre_id
    $zone_id,       // zone filter
    $zone_id,       // zone filter
];

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --------------------------------------------------
// MEDICATION DEPENDENCIES (for add_medsadmin.php)
// --------------------------------------------------
@include __DIR__ . '/../controllers/meds_dependencies.php';

// Sections map (used for buttons AND form containers)
$sections = [
    'carenote'     => $lang['CARE_NOTE'],
    'observation'  => $lang['OBSERVATION'],
    'prescription' => $lang['PRESCRIPTION'],
    'medication'   => $lang['LM_MEDICATION'],
    'treatment'    => $lang['TREATMENT'],
    'feeding'      => $lang['FEEDING'],
    'labs'         => $lang['LABS'],
    'weight'       => $lang['WEIGHT'],
    'measurement'  => $lang['MEASUREMENT'],
    'move'         => $lang['MOVE_PATIENT'],
    'discharge'    => $lang['DISCHARGE']
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
    'move'         => 'move_patient.php',
    'discharge'    => 'add_disposition.php'
];

// --------------------------------------------------
// Build AREA tabs from resolved data (stable key)
// - If resolved_area_id exists: key "id:123"
// - If no valid ID link: "unassigned" / Unknown
// --------------------------------------------------
$areaTabs = [];
foreach ($patients as $row) {
    $label = '';
    $key = '';

    $rid = isset($row['resolved_area_id']) ? (int)$row['resolved_area_id'] : 0;
    $rname = trim((string)($row['resolved_area_name'] ?? ''));

    if ($rid > 0) {
        $key = 'id:' . $rid;
        $label = $rname !== '' ? $rname : ('Area #' . $rid);
    } else {
        $key = 'unassigned';
        $label = $lang['PAT_UNKNOWN'] ?? 'Unknown';
    }

    $areaTabs[$key] = $label;
}
asort($areaTabs, SORT_NATURAL | SORT_FLAG_CASE);
?>
<!--MY PATIENTS.php -->
<!-- SEARCH + PAGE SIZE + ZONE SWITCHER (single unified row) -->
<div class="xform-grid" style="align-items:end;">

    <!-- SEARCH BOX (wider) -->
    <div class="xform-field" style="grid-column: span 2;">
        <input type="text"
               id="tableSearch"
               class="xform-input"
               placeholder="<?php echo htmlspecialchars($lang['TABLE_SEARCH_PLACEHOLDER_PATIENTS']); ?>">
    </div>

    <!-- PAGE SIZE SELECT -->
    <div class="xform-field" style="grid-column: span 1;">
        <label style="font-weight:normal;">
            <?php echo $lang['TABLE_SHOW']; ?>
            <select id="pageSize"
                    class="xform-input"
                    style="width:auto; display:inline-block;">
                <?php foreach ($allowed_patient_page_sizes as $size): ?>
                    <option value="<?= (int)$size ?>" <?= $patient_page_size === $size ? 'selected' : '' ?>>
                        <?= $size === 9999 ? htmlspecialchars($lang['PAT_TAB_ALL']) : (int)$size ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php echo $lang['TABLE_ENTRIES']; ?>
        </label>
    </div>

    <!-- ZONE SWITCHER (only shown if >1 zones) -->
    <?php if (count($zones) > 1): ?>
        <div class="xform-field" style="grid-column: span 1;">
            <label class="xform-label">
                <?php echo htmlspecialchars($lang['ZONE'] ?? 'Zone'); ?>
            </label>
            <select class="xform-input"
                    id="zoneSwitcher"
                    style="min-width:150px;">
                <?php foreach ($zones as $z): ?>
                    <option value="<?php echo (int)$z['zone_id']; ?>"
                        <?php echo ((int)$z['zone_id'] === (int)$zone_id) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($z['zone_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sel = document.getElementById('zoneSwitcher');
            if (!sel) return;

            sel.addEventListener('change', function () {
                const params = new URLSearchParams(window.location.search);
                params.set('zone_id', this.value);
                window.location.search = params.toString();
            });
        });
        </script>
    <?php endif; ?>

</div>


<?php if (empty($patients)): ?>
    <p><?php echo $lang['NO_PATIENTS_FOUND']; ?></p>
<?php else: ?>

<!-- AREA TABS (stable key: id:* or unassigned) -->
<div class="tabs" id="locationTabs">
    <a href="#" class="loc-tab active" data-area-key="all"><?php echo $lang['PAT_TAB_ALL']; ?></a>
    <?php foreach ($areaTabs as $k => $label): ?>
        <a href="#" class="loc-tab" data-area-key="<?php echo htmlspecialchars($k); ?>">
            <?php echo htmlspecialchars($label); ?>
        </a>
    <?php endforeach; ?>
</div>
<br>

<?= $cohortLocationPanelHtml ?>

<table class="table table-bordered table-sm table-hover table-modern patient-table" width="100%" cellspacing="0">
    <thead class="thead-dark">
        <tr>
            <th><?php echo $lang['PAT_TABLE_CRN_PATIENT']; ?></th>
            <th><?php echo $lang['PAT_TABLE_ADMISSION_DATE']; ?></th>
            <th><?php echo $lang['PAT_TABLE_DAYS_IN_CARE']; ?></th>
            <th><?php echo $lang['LOCATION']; ?></th>
            <th><?php echo $lang['PAT_TABLE_PRESENTING_COMPLAINT']; ?></th>
            <th><?php echo $lang['PAT_TABLE_WRA_ADMISSION']; ?></th>
            <th><?php echo $lang['PAT_TABLE_WRA_CURRENT']; ?></th>
        </tr>
    </thead>
    <tbody>
<?php foreach ($patients as $p):
    $pid   = (int)$p['patient_id'];
    $pname = (string)($p['name'] ?? '');

    // Stable area key + label (same logic as tabs)
    $rid = isset($p['resolved_area_id']) ? (int)$p['resolved_area_id'] : 0;
    $rname = trim((string)($p['resolved_area_name'] ?? ''));

    if ($rid > 0) {
        $area_key = 'id:' . $rid;
        $area_label = $rname !== '' ? $rname : ('Area #' . $rid);
    } else {
        $area_key = 'unassigned';
        $area_label = $lang['PAT_UNKNOWN'] ?? 'Unknown';
    }

    // Location display: prefer joined location_name, fallback to admissions current_location text
    $loc_name = trim((string)($p['location_name'] ?? ''));
    if ($loc_name === '') {
        $loc_name = trim((string)($p['current_location'] ?? ''));
        if ($loc_name === '') $loc_name = ($lang['PAT_UNASSIGNED'] ?? 'Unassigned');
    }

    $resolved_location_id = isset($p['resolved_location_id']) ? (int)$p['resolved_location_id'] : 0;
    $resolved_zone_name = trim((string)($p['resolved_zone_name'] ?? ''));
    $resolved_zone_id = isset($p['resolved_zone_id']) ? (int)$p['resolved_zone_id'] : 0;

    // -----------------------------------------------------
    // Compute WRA classes (OLD admission WRA & NEW WRA)
    // -----------------------------------------------------
    $wra = (int)($p['wra'] ?? 99);
    $newwra = (int)($p['newwra'] ?? 99);

    // Admission WRA colour
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

    // Latest WRA colour
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

    // PHP 8 match:
    $daysincare = (int)($p['daysincare'] ?? 0);
    $daysclass = match (true) {
        $daysincare > 120 => 'dark',
        $daysincare > 90  => 'bad',
        $daysincare > 60  => 'warn',
        $daysincare > 31  => 'mid',
        default           => 'ok',
    };
?>
<tbody class="patient-block" style="border:1px solid #ccc; border-radius:4px;">

<tr class="patient-row patient-block-row"
    data-main="1"
    data-patient-id="<?= $pid ?>"
    data-area-key="<?= htmlspecialchars($area_key) ?>"
    data-area-label="<?= htmlspecialchars($area_label) ?>"
    data-location-id="<?= $resolved_location_id ?>"
    data-location-label="<?= htmlspecialchars($loc_name) ?>"
    data-zone-id="<?= $resolved_zone_id ?>"
    data-zone-label="<?= htmlspecialchars($resolved_zone_name) ?>"
    data-patient-name="<?= htmlspecialchars($pname) ?>"
    data-patient-species="<?= htmlspecialchars((string)($p['animal_species'] ?? '')) ?>">

    <td class="align-middle">
        <?php echo $lang['PAT_CRN']; ?>: <?= $pid ?> – <b><?= htmlspecialchars($pname) ?></b> (<?= htmlspecialchars((string)($p['sex'] ?? '')) ?>)
        <br><?= htmlspecialchars((string)($p['animal_species'] ?? '')) ?> (<?= htmlspecialchars((string)($p['animal_type'] ?? '')) ?>)
    </td>

    <td class="align-middle">
        <?php
            $dt = new DateTime((string)$p['admission_date']);
            $day = $dt->format('j');
            $suffix = date('S', $dt->getTimestamp());
            echo $day . $suffix . " " . $dt->format('F Y') . "<br>" . $dt->format('H:i');
        ?>
    </td>

    <td class="align-middle">
        <span class="rc-badge <?php echo htmlspecialchars($daysclass); ?>"><?= $daysincare ?></span>
    </td>

    <td class="align-middle">
        <b><?= htmlspecialchars($loc_name) ?></b><br>
        (<?= htmlspecialchars($area_label) ?>)
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

<!-- ========================= -->
<!-- BUTTON ROW (permission gated) -->
<!-- ========================= -->
<tr data-main="0" data-patient-id="<?= $pid ?>">
    <td colspan="5" class="align-middle">

        <div class="btn-group">

            <?php if (can_action($perm['careplan']['key'], $perm['careplan']['desc'])): ?>
                <a href="viewpatient.php?patient_id=<?= $pid ?>" class="btn green" alt="<?php echo htmlspecialchars($lang['TIP_VIEW_CARE_PLAN']); ?>">
                    <?= rc_icon('care-plan') ?>
                    &nbsp; <?php echo $lang['BTN_CARE_PLAN']; ?>
                </a>
            <?php endif; ?>

            <?php if (can_action($perm['carenote']['key'], $perm['carenote']['desc'])): ?>
            <button title="<?php echo htmlspecialchars($lang['TIP_ADD_A_CARE_NOTE']); ?>" class="btn blue open-section" data-section="carenote" data-pid="<?= $pid ?>">
                <?= rc_icon('care-note') ?>
            </button>
            <?php endif; ?>

            <?php if (can_action($perm['observation']['key'], $perm['observation']['desc'])): ?>
            <button title="<?php echo htmlspecialchars($lang['TIP_ADD_AN_OBSERVATION']); ?>" class="btn blue open-section" data-section="observation" data-pid="<?= $pid ?>">
                <?= rc_icon('observation') ?>
            </button>
            <?php endif; ?>

            <?php if (can_action($perm['prescription']['key'], $perm['prescription']['desc'])): ?>
            <button title="<?php echo htmlspecialchars($lang['TIP_ADD_A_PRESCRIPTION']); ?>" class="btn blue open-section" data-section="prescription" data-pid="<?= $pid ?>">
                <?= rc_icon('prescription') ?>
            </button>
            <?php endif; ?>

            <?php if (can_action($perm['medication']['key'], $perm['medication']['desc'])): ?>
            <button title="<?php echo htmlspecialchars($lang['TIP_ADMINISTER_A_MEDICATION']); ?>" class="btn blue open-section" data-section="medication" data-pid="<?= $pid ?>">
                <?= rc_icon('medication') ?>
            </button>
            <?php endif; ?>

            <?php if (can_action($perm['treatment']['key'], $perm['treatment']['desc'])): ?>
            <button title="<?php echo htmlspecialchars($lang['TIP_ADD_A_TREATMENT']); ?>" class="btn blue open-section" data-section="treatment" data-pid="<?= $pid ?>">
                <?= rc_icon('treatment') ?>
            </button>
            <?php endif; ?>

            <?php if (can_action($perm['feeding']['key'], $perm['feeding']['desc'])): ?>
            <button title="<?php echo htmlspecialchars($lang['TIP_ADD_A_FEEDING']); ?>" class="btn blue open-section" data-section="feeding" data-pid="<?= $pid ?>">
                <?= rc_icon('feeding') ?>
            </button>
            <?php endif; ?>

            <?php if (can_action($perm['labs']['key'], $perm['labs']['desc'])): ?>
            <button title="<?php echo htmlspecialchars($lang['TIP_ADD_LAB_RESULTS']); ?>" class="btn blue open-section" data-section="labs" data-pid="<?= $pid ?>">
                <?= rc_icon('labs') ?>
            </button>
            <?php endif; ?>

            <?php if (can_action($perm['weight']['key'], $perm['weight']['desc'])): ?>
            <button title="<?php echo htmlspecialchars($lang['TIP_ADD_A_WEIGHT']); ?>" class="btn grey open-section" data-section="weight" data-pid="<?= $pid ?>">
                <?= rc_icon('weight') ?>
            </button>
            <?php endif; ?>

            <?php if (can_action($perm['measurement']['key'], $perm['measurement']['desc'])): ?>
            <button title="<?php echo htmlspecialchars($lang['TIP_ADD_A_MEASUREMENT']); ?>" class="btn grey open-section" data-section="measurement" data-pid="<?= $pid ?>">
                <?= rc_icon('measurement') ?>
            </button>
            <?php endif; ?>

            <?= patient_row_actions_render_buttons($pdo, $p, $patientRowActionContext) ?>

            <?php if (can_action($perm['move']['key'], $perm['move']['desc'])): ?>
            <button title="<?= htmlspecialchars($lang['MOVE_PATIENT']) ?>" aria-label="<?= htmlspecialchars($lang['MOVE_PATIENT']) ?>" class="btn purple open-section" data-section="move" data-pid="<?= $pid ?>">
                <?= rc_icon('move', 20, 'icon', 'aria-hidden="true"') ?>
            </button>
            <?php endif; ?>

            <?php if (can_action($perm['discharge']['key'], $perm['discharge']['desc'])): ?>
            <button title="<?php echo htmlspecialchars($lang['TIP_DISCHARGE_THIS_PATIENT']); ?>" class="btn red open-section" data-section="discharge" data-pid="<?= $pid ?>">
                <?= rc_icon('discharge') ?>
                &nbsp; <?php echo $lang['DISCHARGE']; ?>
            </button>
            <?php endif; ?>

        </div>
    </td>

    <!-- Module icon hooks -->
    <td colspan="2" class="align-middle">
        <?= patient_row_actions_render_icons($pdo, $p, $patientRowActionContext) ?>
    </td>
</tr>

<!-- ========================= -->
<!-- HIDDEN PANEL CONTAINING FORMS (permission gated) -->
<!-- ========================= -->
<tr id="panel-<?= $pid ?>" class="patient-panel" style="display:none;" data-patient-id="<?= $pid ?>">
    <td colspan="7">

        <?php foreach ($sections as $key => $label): ?>
            <?php
                if (!isset($perm[$key])) continue;

                $pkey  = $perm[$key]['key'];
                $pdesc = $perm[$key]['desc'];

                if (!can_action($pkey, $pdesc)) continue;
            ?>
            <div id="form-<?= $key ?>-<?= $pid ?>" class="form-container" style="display:none;">
                <div class="rc-card rc-card-muted">
                    <?php
                        $patient_id   = $pid;
                        $patient_name = $pname;
                        $admission_id = (int)$p['admission_id'];
                        $current_location_id = (int)($p['resolved_location_id'] ?? $p['current_location_id'] ?? 0);
                        $current_location_name = $loc_name ?? trim((string)($p['current_location'] ?? ''));
                        include __DIR__ . '/../controllers/' . $form_map[$key];
                    ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?= patient_row_actions_render_forms($pdo, $p, $patientRowActionContext) ?>
        <hr style="border:0; border-top:3px solid #00065cff; margin:2px 0;">

    </td>
</tr>

</tbody>

<?php endforeach; ?>

    </tbody>
</table>

<?php endif; ?>

<?php
@include __DIR__ . '/../controllers/medicationlist.php';
?>

<script>
// Open/close panel + show form
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll(".clickable-row").forEach(function (row) {
        row.addEventListener("click", function () {
            const href = row.getAttribute("data-href");
            if (href) window.location = href;
        });
    });

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
    const cohortPanel         = document.getElementById('cohortLocationPanel');
    const cohortLocationKey   = document.getElementById('cohortLocationKey');
    const cohortLocationLabel = document.getElementById('cohortLocationLabel');
    const cohortNameInput     = document.getElementById('cohortNameInput');
    const cohortSummary       = document.getElementById('cohortLocationSummary');
    const cohortChoices       = document.getElementById('cohortPatientChoices');
    const areaLabels          = <?php echo json_encode($areaTabs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

    const mainRows = Array.from(document.querySelectorAll(
        'tr.patient-row[data-main="1"]'
    ));

    const patientBlocks = mainRows.map(row => {
        const pid     = row.dataset.patientId;
        const areaKey = row.dataset.areaKey || 'unassigned';
        const areaLabel = row.dataset.areaLabel || '';
        const locationId = row.dataset.locationId || '';
        const locationLabel = row.dataset.locationLabel || '';
        const zoneId = row.dataset.zoneId || '';
        const zoneLabel = row.dataset.zoneLabel || '';
        const name    = row.dataset.patientName || '';
        const species = row.dataset.patientSpecies || '';

        const rows = Array.from(document.querySelectorAll(
            'tr[data-patient-id="' + pid + '"]'
        ));

        return {
            pid,
            areaKey,
            areaLabel,
            locationId,
            locationLabel,
            zoneId,
            zoneLabel,
            name,
            species,
            rows
        };
    });

    let currentAreaKey = 'all';
    let currentLocationFilter = '';
    let currentPage    = 1;
    let pageSize       = pageSizeSelect ? parseInt(pageSizeSelect.value,10) : 10;
    let searchTerm     = '';

    function activateAreaTab(areaKey) {
        const requestedTab = Array.from(tabLinks).find(
            tab => (tab.dataset.areaKey || 'all') === areaKey
        );
        if (!requestedTab) return false;

        tabLinks.forEach(t => t.classList.remove('active'));
        requestedTab.classList.add('active');
        currentAreaKey = areaKey;
        return true;
    }

    function normaliseLocationValue(value) {
        return String(value || '')
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    function areaMatchesBlock(block, value) {
        const wanted = normaliseLocationValue(value);
        if (!wanted || wanted === 'all') return true;

        const candidates = [
            block.areaKey,
            block.areaLabel
        ];

        return candidates.some(candidate => normaliseLocationValue(candidate) === wanted);
    }

    function locationMatchesBlock(block, value) {
        const wanted = normaliseLocationValue(value);
        if (!wanted || wanted === 'all') return true;

        const candidates = [
            block.locationId ? 'location-' + block.locationId : '',
            block.locationId,
            block.locationLabel
        ];

        return candidates.some(candidate => normaliseLocationValue(candidate) === wanted);
    }

    function zoneMatchesBlock(block, value) {
        const wanted = normaliseLocationValue(value);
        if (!wanted || wanted === 'all') return true;

        const candidates = [
            block.zoneId ? 'zone-' + block.zoneId : '',
            block.zoneId,
            block.zoneLabel
        ];

        return candidates.some(candidate => normaliseLocationValue(candidate) === wanted);
    }

    function findAreaKeyFromAreaParam(value) {
        const wanted = normaliseLocationValue(value);
        if (!wanted || wanted === 'all') return 'all';

        for (const [key, label] of Object.entries(areaLabels)) {
            const bareAreaId = String(key).startsWith('id:') ? String(key).substring(3) : '';
            if (
                normaliseLocationValue(key) === wanted ||
                normaliseLocationValue(bareAreaId) === wanted ||
                normaliseLocationValue(label) === wanted
            ) {
                return key;
            }
        }

        return 'all';
    }

    function setLocationParam(areaKey) {
        const params = new URLSearchParams(window.location.search);
        params.delete('location');
        params.delete('zone');

        if (areaKey === 'all') {
            params.delete('area');
        } else {
            params.set('area', normaliseLocationValue(areaLabels[areaKey] || areaKey));
        }

        const query = params.toString();
        const nextUrl = window.location.pathname + (query ? '?' + query : '') + window.location.hash;
        window.history.replaceState({}, '', nextUrl);
    }

    function updateCohortPanel() {
        if (!cohortPanel || !cohortChoices) return;

        if (currentAreaKey === 'all') {
            cohortPanel.style.display = 'none';
            cohortChoices.innerHTML = '';
            return;
        }

        const label = currentLocationFilter ? currentLocationFilter.value : (areaLabels[currentAreaKey] || 'Selected location');
        const matches = patientBlocks.filter(block => {
            if (currentLocationFilter) {
                return currentLocationFilter.type === 'location'
                    ? locationMatchesBlock(block, currentLocationFilter.value)
                    : zoneMatchesBlock(block, currentLocationFilter.value);
            }
            return block.areaKey === currentAreaKey;
        });

        cohortPanel.style.display = '';
        if (cohortLocationKey) cohortLocationKey.value = currentLocationFilter ? currentLocationFilter.type + ':' + currentLocationFilter.value : currentAreaKey;
        if (cohortLocationLabel) cohortLocationLabel.value = String(label);
        if (cohortNameInput) cohortNameInput.value = label + ' cohort';
        if (cohortSummary) {
            cohortSummary.textContent = matches.length + ' patient' + (matches.length === 1 ? '' : 's') + ' currently shown in ' + label + '.';
        }

        cohortChoices.innerHTML = '';
        matches.forEach(block => {
            const wrap = document.createElement('label');
            wrap.className = 'alert-box alert-white';
            wrap.style.margin = '0';
            wrap.style.padding = '8px 10px';
            wrap.style.display = 'flex';
            wrap.style.gap = '8px';
            wrap.style.alignItems = 'flex-start';

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = 'patient_ids[]';
            checkbox.value = block.pid;
            checkbox.checked = true;

            const text = document.createElement('span');
            const title = document.createElement('strong');
            title.textContent = 'CRN ' + block.pid;
            text.appendChild(title);
            text.appendChild(document.createTextNode(' - ' + (block.name || 'Unnamed')));
            if (block.species) {
                text.appendChild(document.createElement('br'));
                const small = document.createElement('small');
                small.textContent = block.species;
                text.appendChild(small);
            }

            wrap.appendChild(checkbox);
            wrap.appendChild(text);
            cohortChoices.appendChild(wrap);
        });
    }

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

        add('<?php echo addslashes($lang['PAG_PREV']); ?>', activePage - 1, activePage === 1, false);

        for (let p = 1; p <= totalPages; p++) {
            add(p, p, false, p === activePage);
        }

        add('<?php echo addslashes($lang['PAG_NEXT']); ?>', activePage + 1, activePage === totalPages, false);

        paginationContainer.appendChild(ul);
    }

    function applyFilters() {

        const term = searchTerm.trim().toLowerCase();

        const filtered = patientBlocks.filter(block => {

            const matchesLocation = currentLocationFilter
                ? (
                    currentLocationFilter.type === 'location'
                        ? locationMatchesBlock(block, currentLocationFilter.value)
                        : zoneMatchesBlock(block, currentLocationFilter.value)
                )
                : (currentAreaKey === 'all' || areaMatchesBlock(block, currentAreaKey));

            if (!matchesLocation) return false;

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
            pageSize    = parseInt(this.value, 10);
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

            currentAreaKey = this.dataset.areaKey || 'all';
            currentLocationFilter = '';
            currentPage    = 1;
            setLocationParam(currentAreaKey);
            updateCohortPanel();
            applyFilters();
        });
    });

    const initialParams = new URLSearchParams(window.location.search);
    const requestedArea = initialParams.get('area');
    const requestedLocation = initialParams.get('location');
    const requestedZone = initialParams.get('zone');

    if (requestedArea) {
        const requestedAreaKey = findAreaKeyFromAreaParam(requestedArea);
        activateAreaTab(requestedAreaKey);
    } else if (requestedLocation) {
        currentLocationFilter = { type: 'location', value: requestedLocation };
        const matchedBlock = patientBlocks.find(block => locationMatchesBlock(block, requestedLocation));
        if (matchedBlock) {
            activateAreaTab(matchedBlock.areaKey);
        }
    } else if (requestedZone) {
        currentLocationFilter = { type: 'zone', value: requestedZone };
        activateAreaTab('all');
    }

    updateCohortPanel();
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

            fetch(icon.dataset.completeUrl || '/controllers/form_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'complete_task',
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

});

// ---------------------------------------------
// AUTO-OPEN PANEL + FORM FROM URL (on page load)
// ---------------------------------------------
(function () {

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

})();
</script>
