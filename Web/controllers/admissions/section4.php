<?php
// controllers/admissions/section4.php
// --------------------------------------
// SECTION 4 – Biometrics
// --------------------------------------

if (!isset($pdo)) {
    die('PDO not available in Section 4');
}

require_once __DIR__ . '/../../operations/permissions.php';

/*
|--------------------------------------------------------------------------
| SECTION 4 FIELD CONFIG (SINGLE SOURCE OF TRUTH)
| true  = required to mark complete
| false = optional
|--------------------------------------------------------------------------
*/
$SECTION4_FIELDS = [
    'age_on_admission' => true,
    'dehydrated'       => false,
    'starved'          => false,
    'weight'           => false,
    'weight_unit'      => false,
    'measurement'      => false,
    'measurement_unit' => false,
];

// Register permissions
registerPermission('admission.biometrics.age_on_admission.edit',  'Edit age on admission', 'field');
registerPermission('admission.biometrics.dehydrated.edit',       'Edit dehydrated flag', 'field');
registerPermission('admission.biometrics.starved.edit',          'Edit starved flag', 'field');
registerPermission('admission.biometrics.weight.edit',           'Edit weight', 'field');
registerPermission('admission.biometrics.weight_unit.edit',      'Edit weight unit', 'field');
registerPermission('admission.biometrics.measurement.edit',      'Edit measurement', 'field');
registerPermission('admission.biometrics.measurementment_unit.edit', 'Edit measurement unit', 'field');

// Permissions
$can_age        = can('admission.biometrics.age_on_admission.edit');
$can_dehydrated = can('admission.biometrics.dehydrated.edit');
$can_starved    = can('admission.biometrics.starved.edit');
$can_weight     = can('admission.biometrics.weight.edit');
$can_weight_u   = can('admission.biometrics.weight_unit.edit');
$can_meas       = can('admission.biometrics.measurement.edit');
$can_meas_u     = can('admission.biometrics.measurementment_unit.edit');

// Existing values
$age_on_admission = $admission['age_on_admission'] ?? '';
$dehydrated       = $admission['dehydrated']       ?? 'No';
$starved          = $admission['starved']          ?? 'No';
$weight           = $admission['weight']           ?? '';
$weight_unit      = $admission['weight_unit']      ?? 'g';
$measurement      = $admission['measurement']      ?? '';
$measurement_unit = $admission['measurement_unit'] ?? 'mm';

function sel($current, $value) {
    return ($current === $value) ? 'selected' : '';
}
?>

<div class="rc-card rc-card-muted">
<h3><?= htmlspecialchars(($lang['SECTION'] ?? 'Section') . ' 4 - ' . ($lang['PATIENT'] ?? 'Patient') . ' ' . ($lang['BIOMETRICS'] ?? 'Biometrics')) ?></h3>

<form id="section4-form" class="xform"
      data-required-fields="<?= htmlspecialchars(json_encode($SECTION4_FIELDS), ENT_QUOTES, 'UTF-8') ?>"
      onsubmit="event.preventDefault(); document.getElementById('section4-mark-complete').value='0'; saveSection(4,'section4-form');">

<input type="hidden" name="patient_id" value="<?= htmlspecialchars($pid ?? '') ?>">
<input type="hidden" name="admission_id" value="<?= htmlspecialchars($aid ?? '') ?>">
<input type="hidden" name="mark_complete" id="section4-mark-complete" value="0">

<div class="xform-grid">

    <!-- AGE -->
    <div class="xform-field span-2">
        <label class="xform-label"><?= htmlspecialchars($lang['AGE'] ?? 'Age') ?> *</label>

        <?php if (!$can_age): ?>
            <input type="hidden" name="age_on_admission" value="<?= htmlspecialchars($age_on_admission) ?>">
        <?php endif; ?>

        <select name="age_on_admission"
                class="xform-input <?= $can_age ? '' : 'is-readonly' ?>"
                <?= $can_age ? '' : 'disabled' ?>>
            <option value=""><?= htmlspecialchars(($lang['SELECT'] ?? 'Select') . ' ' . ($lang['AGE'] ?? 'Age') . '...') ?></option>
            <optgroup label="<?= htmlspecialchars($lang['MAMMALS'] ?? 'Mammals') ?>">
                <option value="Newborn" <?= sel($age_on_admission,'Newborn') ?>><?= htmlspecialchars($lang['NEWBORN'] ?? 'Newborn') ?></option>
                <option value="Dependent Juvenile" <?= sel($age_on_admission,'Dependent Juvenile') ?>><?= htmlspecialchars($lang['DEPENDENT_JUVENILE'] ?? 'Dependent Juvenile') ?></option>
                <option value="Independent Juvenile" <?= sel($age_on_admission,'Independent Juvenile') ?>><?= htmlspecialchars($lang['INDEPENDENT_JUVENILE'] ?? 'Independent Juvenile') ?></option>
                <option value="Adult" <?= sel($age_on_admission,'Adult') ?>><?= htmlspecialchars($lang['ADULT'] ?? 'Adult') ?></option>
            </optgroup>
            <optgroup label="<?= htmlspecialchars($lang['BIRDS'] ?? 'Birds') ?>">
                <option value="Hatchling" <?= sel($age_on_admission,'Hatchling') ?>><?= htmlspecialchars($lang['HATCHLING'] ?? 'Hatchling') ?></option>
                <option value="Fledgling" <?= sel($age_on_admission,'Fledgling') ?>><?= htmlspecialchars($lang['FLEDGLING'] ?? 'Fledgling') ?></option>
                <option value="Adult" <?= sel($age_on_admission,'Adult') ?>><?= htmlspecialchars($lang['ADULT'] ?? 'Adult') ?></option>
            </optgroup>
        </select>
    </div>

    <!-- DEHYDRATED -->
    <div class="xform-field">
        <label class="xform-label"><?= htmlspecialchars($lang['DEHYDRATED'] ?? 'Dehydrated?') ?></label>
        <select name="dehydrated"
                class="xform-input <?= $can_dehydrated ? '' : 'is-readonly' ?>"
                <?= $can_dehydrated ? '' : 'disabled' ?>>
            <option value="Yes" <?= sel($dehydrated,'Yes') ?>><?= htmlspecialchars($lang['YES'] ?? 'Yes') ?></option>
            <option value="No"  <?= sel($dehydrated,'No') ?>><?= htmlspecialchars($lang['NO'] ?? 'No') ?></option>
        </select>
    </div>

    <!-- STARVED -->
    <div class="xform-field">
        <label class="xform-label"><?= htmlspecialchars($lang['STARVED'] ?? 'Starved?') ?></label>
        <select name="starved"
                class="xform-input <?= $can_starved ? '' : 'is-readonly' ?>"
                <?= $can_starved ? '' : 'disabled' ?>>
            <option value="Yes" <?= sel($starved,'Yes') ?>><?= htmlspecialchars($lang['YES'] ?? 'Yes') ?></option>
            <option value="No"  <?= sel($starved,'No') ?>><?= htmlspecialchars($lang['NO'] ?? 'No') ?></option>
        </select>
    </div>

    <!-- WEIGHT -->
    <div class="xform-field">
        <label class="xform-label"><?= htmlspecialchars($lang['WEIGHT'] ?? 'Weight') ?></label>
        <input name="weight"
               class="xform-input <?= $can_weight ? '' : 'is-readonly' ?>"
               value="<?= htmlspecialchars($weight) ?>"
               <?= $can_weight ? '' : 'readonly' ?>>
    </div>

    <div class="xform-field">
        <label class="xform-label"><?= htmlspecialchars($lang['WEIGHT_UNIT'] ?? 'Weight unit') ?></label>
        <select name="weight_unit"
                class="xform-input <?= $can_weight_u ? '' : 'is-readonly' ?>"
                <?= $can_weight_u ? '' : 'disabled' ?>>
            <option value="g" <?= sel($weight_unit,'g') ?>>g</option>
            <option value="kg" <?= sel($weight_unit,'kg') ?>>kg</option>
            <option value="lbs" <?= sel($weight_unit,'lbs') ?>>lbs</option>
        </select>
    </div>

    <!-- MEASUREMENT -->
    <div class="xform-field">
        <label class="xform-label"><?= htmlspecialchars($lang['MEASUREMENT'] ?? 'Measurement') ?></label>
        <input name="measurement"
               class="xform-input <?= $can_meas ? '' : 'is-readonly' ?>"
               value="<?= htmlspecialchars($measurement) ?>"
               <?= $can_meas ? '' : 'readonly' ?>>
    </div>

    <div class="xform-field">
        <label class="xform-label"><?= htmlspecialchars($lang['MEASUREMENT_UNIT'] ?? 'Measurement unit') ?></label>
        <select name="measurement_unit"
                class="xform-input <?= $can_meas_u ? '' : 'is-readonly' ?>"
                <?= $can_meas_u ? '' : 'disabled' ?>>
            <option value="mm" <?= sel($measurement_unit,'mm') ?>>mm</option>
            <option value="cm" <?= sel($measurement_unit,'cm') ?>>cm</option>
            <option value="m" <?= sel($measurement_unit,'m') ?>>m</option>
            <option value="in" <?= sel($measurement_unit,'in') ?>>in</option>
            <option value="ft" <?= sel($measurement_unit,'ft') ?>>ft</option>
        </select>
    </div>

</div>

<div class="xform-actions">
    <button type="submit" class="btn green"><?= htmlspecialchars(($lang['SAVE'] ?? 'Save') . ' ' . ($lang['SECTION'] ?? 'Section') . ' 4') ?></button>
    <button type="button" class="btn" id="markSection4Complete"><?= htmlspecialchars(($lang['MARK'] ?? 'Mark') . ' ' . ($lang['SECTION'] ?? 'Section') . ' ' . strtolower($lang['COMPLETE'] ?? 'complete')) ?></button>
</div>

</form>
</div>

<script>
/* ===================== SECTION 4 CLIENT VALIDATION ===================== */
const SECTION4_FIELDS = <?= json_encode($SECTION4_FIELDS) ?>;

function missingRequiredSection4() {
    const missing = [];
    for (const f in SECTION4_FIELDS) {
        if (!SECTION4_FIELDS[f]) continue;
        const el = document.querySelector('[name="'+f+'"]');
        if (!el || !el.value) missing.push(f);
    }
    return missing;
}

document.getElementById('markSection4Complete').onclick = () => {
    const missing = missingRequiredSection4();
    if (missing.length) {
        alert(<?= json_encode($lang['COMPLETE_REQUIRED_FIRST'] ?? 'Please complete required fields first.') ?>);
        return;
    }
    const flag = document.getElementById('section4-mark-complete');
    flag.value = '1';
    saveSection(4,'section4-form');
    flag.value = '0';
};
</script>
