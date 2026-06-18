<?php
include 'dashmain.php';
include 'models/patient_data.php';
require_once __DIR__ . '/core/icons.php';

require_once __DIR__ . '/operations/permissions.php';
require_once __DIR__ . '/operations/patient_view_tabs.php';
registerPermission('patient.view', 'View patient care Plan', 'page');
requirePermission('patient.view');

registerPermission('patients.movements.add', 'View patient movements', 'tab');


/**
 * NOTE:
 * We are NOT calling registerPermission() for these tab permissions
 * because you explicitly said: "no need to create a new entry to the database".
 * Therefore we ONLY use can($key).
 */

// -----------------------------------------
// TAB PERMISSIONS (EXACT EXISTING KEYS)
// -----------------------------------------
$tabPerm = [
    // Tabs you already have in this file
    'observations' => 'patients.observation.add',
    'carenotes'    => 'patients.carenote.add',
    'treatments'   => 'patients.treatment.add',
    'labs'         => 'patients.labs.add',

    'feeding'      => 'patients.feeding.add',
    'movements'    => 'patients.movements.add',


    // Grouped tabs
    'weightsmeasures_any' => ['patients.weight.add', 'patients.measurement.add'],
    'prescriptions_any'   => ['patients.prescription.add', 'patients.medication.administer'],
];

function can_any(array $keys): bool
{
    foreach ($keys as $k) {
        if (can($k)) return true;
    }
    return false;
}

// Build tab list in the exact order you render them
$tabs = [];

// Always-visible (for now)
$tabs[] = ['id' => 'triage',   'label' => 'Triage',   'view' => 'views/triages.php',  'allowed' => true];
$tabs[] = ['id' => 'diagrams', 'label' => 'Diagrams', 'view' => 'views/diagrams.php', 'allowed' => true];

// Permission-gated
$tabs[] = ['id' => 'observations', 'label' => 'Observations', 'view' => 'views/observations.php', 'allowed' => can($tabPerm['observations'])];
$tabs[] = ['id' => 'carenotes',    'label' => 'Notes',        'view' => 'views/carenotes.php',    'allowed' => can($tabPerm['carenotes'])];
$tabs[] = ['id' => 'treatments',   'label' => 'Treatments',   'view' => 'views/treatments.php',   'allowed' => can($tabPerm['treatments'])];
$tabs[] = ['id' => 'feeding',      'label'   => 'Feeding',    'view' => 'views/feeding.php',       'allowed' => can($tabPerm['feeding'])];

// Grouped permissions
$tabs[] = [
    'id'      => 'weightsmeasures',
    'label'   => "Weights &<BR>Measurements",
    'view'    => 'views/weightsmeasures.php',
    'allowed' => can_any($tabPerm['weightsmeasures_any'])
];

$tabs[] = [
    'id'      => 'prescriptions',
    'label'   => "Medication",
    'view'    => 'views/prescriptions.php',
    'allowed' => can_any($tabPerm['prescriptions_any'])
];

$tabs[] = ['id' => 'labs',        'label' => 'Labs',         'view' => 'views/labs.php',        'allowed' => can($tabPerm['labs'])];
foreach (patient_view_tabs_load_tabs($pdo, (int)$centre_id) as $moduleTab) {
    $tabs[] = $moduleTab;
}
$tabs[] = ['id' => 'movements',   'label' => 'Movements',    'view' => 'views/movements.php',   'allowed' => can($tabPerm['movements'])];

// Choose active tab:
// - supports ?tab=xxx (optional)
// - otherwise defaults to first allowed tab
$requestedTab = $_GET['tab'] ?? null;

$activeTab = null;
if ($requestedTab) {
    foreach ($tabs as $t) {
        if ($t['allowed'] && $t['id'] === $requestedTab) {
            $activeTab = $requestedTab;
            break;
        }
    }
}

if (!$activeTab) {
    foreach ($tabs as $t) {
        if ($t['allowed']) {
            $activeTab = $t['id'];
            break;
        }
    }
}

// Safety fallback (should never happen unless all tabs are denied)
if (!$activeTab) {
    $activeTab = 'triage';
}

$stmt = $pdo->prepare("
  SELECT bg_id, name, file_path
  FROM rescue_backgrounds
  WHERE active = 1
  ORDER BY name
");
$stmt->execute();
$backgrounds = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT diag_id, background_used, diagram_png, label_data, created_at
    FROM rescue_diagrams
    WHERE patient_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$patient_id]);
$saved_diagrams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Locations for lookup (grouped by area)
$locStmt = $pdo->prepare("
    SELECT location_id, location_area, location_name
    FROM rescue_locations
    WHERE deleted = 0
      AND centre_id = :centre_id
    ORDER BY location_area, location_name
");
$locStmt->execute([ ':centre_id' => $centre_id ]);

$locations_by_area = [];
while ($row = $locStmt->fetch(PDO::FETCH_ASSOC)) {
    $area = $row['location_area'] ?: 'Other';
    $locations_by_area[$area][] = [
        'location_id'   => (int)$row['location_id'],
        'location_name' => $row['location_name'],
    ];
}
// Centre to centre sharing
$shareCentresSql = "
    SELECT
        rc.rescue_id,
        rc.rescue_name
    FROM rescue_centre_friends f
    INNER JOIN rescue_centres rc
        ON rc.rescue_id = CASE
            WHEN f.centre_a_id = ? THEN f.centre_b_id
            ELSE f.centre_a_id
        END
    WHERE
        f.status = 'approved'
        AND (? IN (f.centre_a_id, f.centre_b_id))
    ORDER BY rc.rescue_name ASC
";

$stmt = $pdo->prepare($shareCentresSql);
$stmt->execute([$centre_id, $centre_id]);
$share_centres = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Networks for sharing
$shareNetworksSql = "
    SELECT
        gm.group_id,
        g.name
    FROM rescue_group_members gm
    INNER JOIN rescue_groups g
        ON g.group_id = gm.group_id
    WHERE
        gm.centre_id = ?
        AND gm.status = 'active'
    ORDER BY g.name ASC
";

$stmt = $pdo->prepare($shareNetworksSql);
$stmt->execute([$centre_id]);
$share_networks = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Vets for sharing (only needed if you want to show vet options in the share form, otherwise can be removed)
$shareVetsSql = "
    SELECT
        v.practice_id,
        v.practice_name
    FROM rescue_vet_centres rvc
    INNER JOIN rescue_vets v
        ON v.practice_id = rvc.practice_id
    WHERE
        rvc.centre_id = ?
        AND rvc.status = 'approved'
    ORDER BY v.practice_name ASC
";

$stmt = $pdo->prepare($shareVetsSql);
$stmt->execute([$centre_id]);
$share_vets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?=template_admin_header('CRN: ' . $patient_id .  ' - ' . $patient_name . ' - View individual Patient', 'patients', 'mypatients')?>
<?php

if (!empty($_GET['msg'])) {
    echo '<div class="rc-alert green">'
        . htmlspecialchars($_GET['msg']) .
        '</div>';
}

if (!empty($_GET['error'])) {
    echo '<div class="rc-alert red">'
        . htmlspecialchars($_GET['error']) .
        '</div>';
}

$auto_type = $_GET['type'] ?? null;
$auto_pid  = $_GET['pid']  ?? null;

?>
<div class="content-title">
    <div class="title">
        <div class="icon">
            <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M298.5 156.9C312.8 199.8 298.2 243.1 265.9 253.7C233.6 264.3 195.8 238.1 181.5 195.2C167.2 152.3 181.8 109 214.1 98.4C246.4 87.8 284.2 114 298.5 156.9zM164.4 262.6C183.3 295 178.7 332.7 154.2 346.7C129.7 360.7 94.5 345.8 75.7 313.4C56.9 281 61.4 243.3 85.9 229.3C110.4 215.3 145.6 230.2 164.4 262.6zM133.2 465.2C185.6 323.9 278.7 288 320 288C361.3 288 454.4 323.9 506.8 465.2C510.4 474.9 512 485.3 512 495.7L512 497.3C512 523.1 491.1 544 465.3 544C453.8 544 442.4 542.6 431.3 539.8L343.3 517.8C328 514 312 514 296.7 517.8L208.7 539.8C197.6 542.6 186.2 544 174.7 544C148.9 544 128 523.1 128 497.3L128 495.7C128 485.3 129.6 474.9 133.2 465.2zM485.8 346.7C461.3 332.7 456.7 295 475.6 262.6C494.5 230.2 529.6 215.3 554.1 229.3C578.6 243.3 583.2 281 564.3 313.4C545.4 345.8 510.3 360.7 485.8 346.7zM374.1 253.7C341.8 243.1 327.2 199.8 341.5 156.9C355.8 114 393.6 87.8 425.9 98.4C458.2 109 472.8 152.3 458.5 195.2C444.2 238.1 406.4 264.3 374.1 253.7z"/></svg>
        </div>
        <div class="txt">
            <h2 class="pagehead">View Patient</h2><b>CRN: <?php echo $patient_id; ?> - <?php echo $patient_name; ?></b>          
        </div>   
    </div>
        <div class="btns rc-actions rc-actions-compact">

        <a href="qr.php?patient_id=<?= (int)$patient_id ?>" target="_blank" class="btn grey" title="Patient QR code" aria-label="Patient QR code"><?= rc_icon('qr-code', 20, 'icon', 'aria-hidden="true"') ?></a>
        <button id="shareBtn" type="button" class="btn green" title="Share" aria-label="Share" onclick="toggleShareForm()"><?= rc_icon('share', 20, 'icon', 'aria-hidden="true"') ?></button>

            <a href="editpatient.php?patient_id=<?php echo $patient_id; ?>" type="button" class="btn orange" data-placement="top" title="Edit Admission" aria-label="Edit Admission"><?= rc_icon('edit', 20, 'icon', 'aria-hidden="true"') ?></a>  
            <a href="docspatient.php?patient_id=<?= (int)$patient_id ?>" class="btn red" title="Patient Documents" aria-label="Patient Documents"><?= rc_icon('document', 20, 'icon', 'fill="currentColor" aria-hidden="true"') ?></a>
            <button id="wraBtn" type="button" class="btn purple" data-placement="top" title="Move / transfer this patient" aria-label="Move / transfer this patient" onclick="toggleLocationForm()"><?= rc_icon('move', 20, 'icon', 'aria-hidden="true"') ?></button>
<a href="/views/print/patient_record_print.php?patient_id=<?= (int)$patient_id ?>"
   target="_blank"
   class="btn blue"
   data-placement="top"
   title="Print full patient record"
   aria-label="Print full patient record">
    <?= rc_icon('print', 20, 'icon', 'fill="currentColor" aria-hidden="true"') ?>
</a>

        </div>
</div>
<div id="share-form" class="rc-card rc-card-muted" style="display:none;">

    <form method="post"
          action="controllers/form_handler.php"
          class="xform">

        <input type="hidden" name="share_patient_form" value="1">
        <input type="hidden" name="patient_id" value="<?= (int)$patient_id ?>">
        <input type="hidden" name="owner_centre_id" value="<?= (int)$centre_id ?>">

        <div class="xform-grid">
            <div class="xform-field span-3">
                <label class="xform-label" for="share_target">Share target</label>
                <select name="share_target"
                        id="share_target"
                        class="xform-input"
                        required>
                    <option value="">â€” Select share target â€”</option>

                    <?php if (!empty($share_centres)): ?>
                        <optgroup label="Centres">
                            <?php foreach ($share_centres as $c): ?>
                                <option value="centre:<?= (int)$c['rescue_id'] ?>">
                                    <?= htmlspecialchars($c['rescue_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>

                    <?php if (!empty($share_networks)): ?>
                        <optgroup label="Networks">
                            <?php foreach ($share_networks as $g): ?>
                                <option value="group:<?= (int)$g['group_id'] ?>">
                                    <?= htmlspecialchars($g['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>

                    <?php if (!empty($share_vets)): ?>
                        <optgroup label="Vet Practices">
                            <?php foreach ($share_vets as $v): ?>
                                <option value="vet:<?= (int)$v['practice_id'] ?>">
                                    <?= htmlspecialchars($v['practice_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                </select>
            </div>

            <div class="xform-field">
                <label class="xform-label">&nbsp;</label>
                <button type="submit" class="btn green">Share</button>
            </div>
        </div>
    </form>
</div>
<div id="change-location-form" class="rc-card rc-card-muted" style="display:none;">

    <form method="post"
          action="controllers/form_handler.php"
          class="xform">

        <input type="hidden" name="changelocationform" value="1">
        <input type="hidden" name="patient_id" value="<?= (int)$patient_id ?>">
        <input type="hidden" name="admission_id" value="<?= (int)$admission_id ?>">
        <div class="xform-grid">
            <div class="xform-field span-3">
                <label class="xform-label" for="new_location_id">New location</label>
                
      <select name="new_location_id"
        id="new_location_id"
        class="xform-input"
        required>

    <option value="">â€” Select location â€”</option>

    <?php foreach ($locations_by_area as $area => $locations): ?>
        <optgroup label="<?= htmlspecialchars($area) ?>">
            <?php foreach ($locations as $loc): ?>
                <option value="<?= (int)$loc['location_id'] ?>"
                    <?= ((int)$loc['location_id'] === (int)$current_location_id ? 'selected' : '') ?>>
                    <?= htmlspecialchars($loc['location_name']) ?>
                </option>
            <?php endforeach; ?>
        </optgroup>
    <?php endforeach; ?>

</select>


            </div>
            <div class="xform-field">
                <label class="xform-label">&nbsp;</label>
                <button type="submit" class="btn green" name="changeLocationForm">
                    Save
                </button>
            </div>
        </div>
    </form>
</div>





<?php
$maskedFinderTel = strlen((string)$finder_tel) > 4
    ? substr((string)$finder_tel, 0, 2) . str_repeat('*', strlen((string)$finder_tel) - 4) . substr((string)$finder_tel, -2)
    : (string)$finder_tel;
?>
<div class="rc-card rc-card-muted">
    <div class="rc-stat-grid">
        <div class="rc-stat rc-alert red">
            <span>Species</span>
            <strong><?= htmlspecialchars($patient_animal_species ?: 'Not recorded') ?></strong>
            <p class="rc-note">
                Sex: <?= htmlspecialchars($patient_sex ?: 'Not recorded') ?><br>
                <?= htmlspecialchars(implode(' / ', array_filter([$patient_animal_type, $patient_animal_order])) ?: 'Not recorded') ?>
            </p>
        </div>

        <div class="rc-stat rc-alert green">
            <span>Current location</span>
            <strong><?= htmlspecialchars($current_location ?: 'Not recorded') ?></strong>
            <p class="rc-note">
                Microchip: <?= htmlspecialchars($patient_microchipped === 'Yes' ? ($patient_microchip_number ?: 'Number not recorded') : 'None') ?><br>
                Ring: <?= htmlspecialchars($patient_ringed === 'Yes' ? ($patient_ring_number ?: 'Number not recorded') : 'None') ?>
            </p>
        </div>

        <div class="rc-stat rc-alert blue">
            <span>Presenting complaint</span>
            <strong><?= htmlspecialchars($presenting_complaint ?: 'Not recorded') ?></strong>
        </div>

        <div class="rc-stat rc-alert amber">
            <span>Finder</span>
            <strong><?= htmlspecialchars($finder_name ?: 'Not recorded') ?></strong>
            <p class="rc-note">
                Tel: <i id="finder-tel-value" data-full="<?= htmlspecialchars($finder_tel) ?>" data-masked="<?= htmlspecialchars($maskedFinderTel) ?>"><?= htmlspecialchars($maskedFinderTel ?: 'Not recorded') ?></i> <a href="javascript:void(0)" class="link3" onclick="togglePrivateValue('finder-tel-value', this)">(view)</a><br>
                Passphrase: <?= htmlspecialchars($passphrase ?: 'Not recorded') ?>
            </p>
        </div>
    </div>
</div>

<?php if (false): ?>
<div class="rc-card rc-card-muted">

    <div class="xform-grid">

            <!-- LEFT COLUMN -->
            <div class="xform-field span-2">

                <p class="rc-muted">
                    <?= htmlspecialchars($patient_sex) ?>
                    â€“
                    <strong><?= htmlspecialchars($patient_animal_species) ?></strong>
                    â€“
                    (<?= htmlspecialchars($patient_animal_type) ?> â€“ <?= htmlspecialchars($patient_animal_order) ?>)
                </p>

                <p>
                    <strong>Presenting complaint:</strong>
                    <?= nl2br(htmlspecialchars($presenting_complaint)) ?>
                </p>

                <p>
                    <?php if ($patient_ringed): ?>
                        <strong>Ringed:</strong> <?= htmlspecialchars($patient_ring_number) ?>
                    <?php endif; ?>

                    <?php if ($patient_microchipped): ?>
                        <br>
                        <strong>Microchipped:</strong> <?= htmlspecialchars($patient_microchip_number) ?>
                    <?php endif; ?>
                </p>

            </div>

            <!-- RIGHT COLUMN -->
            <div class="xform-field span-2">

                <p>
                    <strong>Finder name:</strong>
                    <?= htmlspecialchars($finder_name) ?>
                <br>
                    <strong>Finder tel:</strong>
                    <span
                        id="finder-tel"
                        data-full="<?= htmlspecialchars($finder_tel) ?>"
                        data-masked="<?= htmlspecialchars(
                        strlen($finder_tel) > 4
                        ? substr($finder_tel, 0, 2) . str_repeat('*', strlen($finder_tel) - 4) . substr($finder_tel, -2)
                        : $finder_tel
                        ) ?>" >
                    <?= htmlspecialchars(
                        strlen($finder_tel) > 4
                         ? substr($finder_tel, 0, 2) . str_repeat('*', strlen($finder_tel) - 4) . substr($finder_tel, -2)
                        : $finder_tel ) ?>
                    </span>

                <a href="javascript:void(0)" class="link3" onclick="toggleFinderTel()">View</a>
                </p>
                <p>
                    <strong>Passphrase:</strong>
                    <?= htmlspecialchars($passphrase) ?>
                </p>

                <p>
                    <strong>Current location:</strong>
                    <?= htmlspecialchars($current_location) ?>
                </p>

            </div>
    </div>

</div>
<?php endif; ?>


<!-- ========================= -->
<!-- TABS (permission gated) -->
<!-- ========================= -->
<div class="rc-tabs rc-tabs-pill">
    <?php foreach ($tabs as $t): ?>
        <?php if (!$t['allowed']) continue; ?>
        <a href="?patient_id=<?= (int)$patient_id ?>&tab=<?= htmlspecialchars($t['id']) ?>"
           class="rc-tab <?= ($t['id'] === $activeTab ? 'is-active' : '') ?>">
            <?= $t['label'] ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- ========================= -->
<!-- TAB CONTENT (permission gated) -->
<!-- ========================= -->
<?php foreach ($tabs as $t): ?>
    <?php if (!$t['allowed']) continue; ?>
    <div class="tab-content rc-tab-panel rc-panel <?= ($t['id'] === $activeTab ? 'active is-active' : '') ?>">
        <?php include $t['view']; ?>
    </div>
<?php endforeach; ?>


<script>
function togglePrivateValue(elementId, link) {
    const value = document.getElementById(elementId);
    if (!value || !link) return;

    const isMasked = value.textContent === value.dataset.masked;
    value.textContent = isMasked ? (value.dataset.full || 'Not recorded') : value.dataset.masked;
    link.textContent = isMasked ? 'Hide' : 'View';
}

function toggleFinderTel() {
    const tel = document.getElementById('finder-tel');
    if (!tel) return;

    const link = event.target;
    const isMasked = tel.textContent === tel.dataset.masked;

    tel.textContent = isMasked ? tel.dataset.full : tel.dataset.masked;
    link.textContent = isMasked ? 'Hide' : 'View';
}
</script>

<script>
function toggleLocationForm() {
    const form = document.getElementById('change-location-form');
    if (!form) return;

    form.style.display =
        (form.style.display === 'none' || form.style.display === '')
        ? 'block'
        : 'none';
}
</script>
<script>
</script>
<script>
function toggleShareForm() {
    const form = document.getElementById('share-form');
    if (!form) return;
    form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'block' : 'none';
}
</script>
<?=template_admin_footer()?>

