<?php
// controllers/admissions/section2.php
// Section 2 – This Admission (STRICT FIELD PERMISSIONS)

if (!isset($pdo)) {
    die('PDO not available in Section 2');
}

require_once __DIR__ . '/../../operations/permissions.php';
require_once __DIR__ . '/../../operations/modules_registry.php';

/*
|--------------------------------------------------------------------------
| SECTION 2 FIELD DEFINITION (SINGLE SOURCE OF TRUTH)
| true  = required to MARK COMPLETE
| false = optional (counts toward %, never blocks completion)
|--------------------------------------------------------------------------
*/
$SECTION2_FIELDS = [
    'admission_date'     => true,
    'time_to_admission'  => false,
    'current_location'   => true,
    'disposition'        => true,
];

// Register field permissions (auto-creates in DB if missing)
registerPermission('admission.core.admission_date.edit',     'Edit admission date',      'field');
registerPermission('admission.core.time_to_admission.edit', 'Edit time to admission',   'field');
registerPermission('admission.core.current_location.edit',  'Edit current location',    'field');
registerPermission('admission.core.disposition.edit',       'Edit disposition',         'field');

// STRICT: fields editable ONLY if field permission is allowed
$can_adm_date    = can('admission.core.admission_date.edit');
$can_time_to_adm = can('admission.core.time_to_admission.edit');
$can_location    = can('admission.core.current_location.edit');
$can_disposition = can('admission.core.disposition.edit');

// ----------------------------------------
// Prefill from $admission if available
// ----------------------------------------
$admission_id = $admission['admission_id'] ?? '';
$patient_id   = $pid ?? ($admission['patient_id'] ?? '');

$current_location     = $admission['current_location']  ?? '';
$disposition          = $admission['disposition']       ?? 'Held in captivity';
$time_to_admission    = $admission['time_to_admission'] ?? '';
$current_location_id = (int)($admission['current_location_id'] ?? 0);

$admission_date_value = '';

// admission_date in DB is "Y-m-d H:i:s" → convert to "Y-m-d\TH:i"
if (!empty($admission['admission_date'])) {
    try {
        $dt = new DateTime($admission['admission_date']);
        $admission_date_value = $dt->format('Y-m-d\TH:i');
    } catch (Exception $e) {
        $admission_date_value = '';
    }
}

// Status derived from disposition
$status_value = ($disposition === 'Held in captivity') ? 'Active' : 'Closed';

// ----------------------------------------
// Load locations for this centre (ID + name)
// ----------------------------------------
$locations_by_area = [];

try {
    $locStmt = $pdo->prepare("
        SELECT location_id, location_name, location_area
        FROM rescue_locations
        WHERE centre_id = :cid
          AND (deleted IS NULL OR deleted = 0)
        ORDER BY location_area ASC, location_name ASC
    ");
    $locStmt->execute([':cid' => $centre_id]);
    $locRows = $locStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($locRows as $row) {
        $area = $row['location_area'] ?: 'Other';
        $locations_by_area[$area][] = [
            'location_id'   => (int)$row['location_id'],
            'location_name' => $row['location_name'],
        ];
    }
} catch (PDOException $e) {
    $locations_by_area = [];
}

// ----------------------------------------
// Load time_to_admission options
// ----------------------------------------
$timeOptions = [];
try {
    $tStmt = $pdo->query("
        SELECT time_to_admission
        FROM rescue_time_admission
        ORDER BY time_id ASC
    ");
    $timeOptions = $tStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $timeOptions = [];
}

// Disposition options (fixed list)
$dispositionOptions = [
    'Held in captivity' => $lang['ADM_HELD_IN_CAPTIVITY'] ?? 'Held in captivity',
    'Released' => $lang['RELEASED'] ?? 'Released',
    'Transferred out' => $lang['ADM_TRANSFERRED_OUT'] ?? 'Transferred out',
    'Died - Euthanised' => $lang['ADM_DIED_EUTHANISED'] ?? 'Died - Euthanised',
    'Died - after 48 hours' => $lang['ADM_DIED_AFTER_48_HOURS'] ?? 'Died - after 48 hours',
    'Died - within 48 hours' => $lang['ADM_DIED_WITHIN_48_HOURS'] ?? 'Died - within 48 hours',
    'Died - on admission' => $lang['ADM_DIED_ON_ADMISSION'] ?? 'Died - on admission',
];

$section2CentreId = (int)($centre_id ?? ($_SESSION['centre_id'] ?? ($_SESSION['rescue_id'] ?? 0)));
if (modules_is_active($pdo, 'adoptions', $section2CentreId > 0 ? $section2CentreId : null)) {
    $dispositionOptions['For Adoption'] = $lang['ADM_FOR_ADOPTION'] ?? 'For Adoption';
    $dispositionOptions['Adopted'] = $lang['ADM_ADOPTED'] ?? 'Adopted';
}
?>

<div class="rc-card rc-card-muted">
    <h3><?= htmlspecialchars(($lang['SECTION'] ?? 'Section') . ' 2 - ' . ($lang['ADMISSION'] ?? 'Admission')) ?></h3>

    <form id="section2-form" class="xform"
          data-required-fields="<?= htmlspecialchars(json_encode($SECTION2_FIELDS), ENT_QUOTES, 'UTF-8') ?>"
          onsubmit="event.preventDefault(); saveSection(2, 'section2-form');">

        <input type="hidden" name="patient_id" value="<?= htmlspecialchars($patient_id) ?>">
        <input type="hidden" name="admission_id" value="<?= htmlspecialchars($admission_id) ?>">

        <!-- status is derived server-side, but posted for consistency -->
        <input type="hidden" name="status" id="status-hidden" value="<?= htmlspecialchars($status_value) ?>">

        <!-- used ONLY when clicking 'Mark section complete' -->
        <input type="hidden" name="mark_complete" id="mark-complete-flag" value="0">

        <div class="xform-grid">

            <!-- Admission date/time -->
            <div class="xform-field span-2">
                <label class="xform-label">
                    <?= htmlspecialchars(($lang['ADMISSION'] ?? 'Admission') . ' ' . ($lang['DATE'] ?? 'Date') . ' / ' . ($lang['TIME'] ?? 'Time')) ?> <span class="req">*</span>
                </label>

                <?php if (!$can_adm_date): ?>
                    <input type="hidden" name="admission_date" value="<?= htmlspecialchars($admission_date_value) ?>">
                <?php endif; ?>

                <input type="datetime-local"
                       name="admission_date"
                       value="<?= htmlspecialchars($admission_date_value) ?>"
                       <?= $can_adm_date ? '' : 'readonly' ?>
                       class="xform-input <?= $can_adm_date ? '' : 'is-readonly' ?>">
            </div>

            <!-- Time to admission (optional) -->
            <div class="xform-field span-2">
                <label class="xform-label"><?= htmlspecialchars(($lang['TIME'] ?? 'Time') . ' ' . ($lang['TO'] ?? 'to') . ' ' . ($lang['ADMISSION'] ?? 'Admission')) ?></label>

                <?php if (!$can_time_to_adm): ?>
                    <input type="hidden" name="time_to_admission" value="<?= htmlspecialchars($time_to_admission) ?>">
                <?php endif; ?>

                <select name="time_to_admission"
                        class="xform-input <?= $can_time_to_adm ? '' : 'is-readonly' ?>"
                        <?= $can_time_to_adm ? '' : 'disabled' ?>>
                    <option value=""><?= htmlspecialchars(($lang['SELECT'] ?? 'Select') . '...') ?></option>
                    <?php foreach ($timeOptions as $opt): ?>
                        <option value="<?= htmlspecialchars($opt) ?>"
                            <?= ($opt === $time_to_admission ? 'selected' : '') ?>>
                            <?= htmlspecialchars($opt) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Current location -->
<div class="xform-field span-2">
    <label class="xform-label">
        <?= htmlspecialchars($lang['LOCATION'] ?? 'Location') ?> <span class="req">*</span>
    </label>

    <?php if (!$can_location): ?>
        <input type="hidden" name="current_location_id" value="<?= (int)$current_location_id ?>">
    <?php endif; ?>

    <select name="current_location_id"
            data-required-field="current_location"
            class="xform-input <?= $can_location ? '' : 'is-readonly' ?>"
            <?= $can_location ? '' : 'disabled' ?>>
        <option value=""><?= htmlspecialchars(($lang['SELECT'] ?? 'Select') . ' ' . ($lang['LOCATION'] ?? 'Location') . '...') ?></option>

        <?php foreach ($locations_by_area as $area => $locs): ?>
            <optgroup label="<?= htmlspecialchars($area) ?>">
                <?php foreach ($locs as $loc): ?>
                    <option value="<?= (int)$loc['location_id'] ?>"
                        <?= ((int)$loc['location_id'] === (int)$current_location_id ? 'selected' : '') ?>>
                        <?= htmlspecialchars($loc['location_name']) ?>
                    </option>
                <?php endforeach; ?>
            </optgroup>
        <?php endforeach; ?>
    </select>
</div>


            <!-- Disposition -->
            <div class="xform-field span-2">
                <label class="xform-label">
                    <?= htmlspecialchars($lang['DISPOSITION'] ?? 'Disposition') ?> <span class="req">*</span>
                </label>

                <?php if (!$can_disposition): ?>
                    <input type="hidden" name="disposition" value="<?= htmlspecialchars($disposition) ?>">
                <?php endif; ?>

                <select name="disposition"
                        id="disposition"
                        class="xform-input <?= $can_disposition ? '' : 'is-readonly' ?>"
                        <?= $can_disposition ? '' : 'disabled' ?>>
                    <?php foreach ($dispositionOptions as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>"
                            <?= ($value === $disposition ? 'selected' : '') ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status display -->
            <div class="xform-field span-2">
                <label class="xform-label"><?= htmlspecialchars($lang['STATUS'] ?? 'Status') ?></label>
                <input type="text"
                       class="xform-input"
                       value="<?= htmlspecialchars($status_value === 'Active' ? ($lang['ACTIVE'] ?? 'Active') : ($lang['CLOSED'] ?? 'Closed')) ?>"
                       readonly>
                <small><?= htmlspecialchars($lang['ADM_STATUS_FROM_DISPOSITION'] ?? 'Set automatically from disposition.') ?></small>
            </div>

        </div>

        <div class="xform-actions">
            <br>
            <button type="submit" class="btn green">
                <?= htmlspecialchars(($lang['SAVE'] ?? 'Save') . ' ' . ($lang['SECTION'] ?? 'Section') . ' 2') ?>
            </button>

            <button type="button"
                    class="btn green"
                    id="mark-complete-btn">
                <?= htmlspecialchars(($lang['MARK'] ?? 'Mark') . ' ' . ($lang['SECTION'] ?? 'Section') . ' ' . strtolower($lang['COMPLETE'] ?? 'complete')) ?>
            </button>
        </div>

    </form>
</div>

<script>
(function () {
    const dispositionEl = document.getElementById('disposition');
    const statusHidden  = document.getElementById('status-hidden');
    const markBtn       = document.getElementById('mark-complete-btn');
    const markFlag      = document.getElementById('mark-complete-flag');
    const form          = document.getElementById('section2-form');

    function syncStatus() {
        if (!dispositionEl) return;
        const v = dispositionEl.value;
        statusHidden.value = (v === 'Held in captivity') ? 'Active' : (v ? 'Closed' : '');
    }

    if (dispositionEl) {
        dispositionEl.addEventListener('change', syncStatus);
        syncStatus();
    }

    if (markBtn && form && markFlag) {
        markBtn.addEventListener('click', function () {
            markFlag.value = '1';
            saveSection(2, 'section2-form');
        });
    }
})();
</script>
