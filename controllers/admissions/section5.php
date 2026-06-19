<?php
// controllers/admissions/section5.php
// ----------------------------------------------------
// SECTION 5 – TRIAGE & ASSESSMENT (CONSISTENT WITH S2/3/4)
// ----------------------------------------------------

if (!isset($pdo)) {
    die('PDO not available in Section 5');
}

require_once __DIR__ . '/../../operations/permissions.php';

/*
|--------------------------------------------------------------------------
| SECTION 5 FIELD CONFIG (SINGLE SOURCE OF TRUTH)
| true  = required to mark complete
| false = optional
|--------------------------------------------------------------------------
*/
$SECTION5_FIELDS = [
    'ss_text'               => false,
    'bcs_text'              => false,
    'presenting_complaint'  => false,
    'hpc'                   => false,
    'on_examination'        => false,
];

// Register field permissions (auto-create)
registerPermission('admission.triage.ss_text.edit',                 'Edit severity score (text)', 'field');
registerPermission('admission.triage.bcs_text.edit',                'Edit body condition score (text)', 'field');
registerPermission('admission.triage.presenting_complaint.edit',    'Edit presenting complaint', 'field');
registerPermission('admission.triage.hpc.edit',                     'Edit history of presenting complaint', 'field');
registerPermission('admission.triage.on_examination.edit',          'Edit on examination notes', 'field');

// Strict edit flags
$can_ss          = can('admission.triage.ss_text.edit');
$can_bcs         = can('admission.triage.bcs_text.edit');
$can_presenting  = can('admission.triage.presenting_complaint.edit');
$can_hpc         = can('admission.triage.hpc.edit');
$can_exam        = can('admission.triage.on_examination.edit');

// Load existing values
$ss_text             = $admission['ss_text']              ?? '';
$bcs_text            = $admission['bcs_text']             ?? '';
$presenting_current  = $admission['presenting_complaint'] ?? '';
$hpc                 = $admission['hpc']                  ?? '';
$on_examination      = $admission['on_examination']       ?? '';

// Load presenting complaints dynamically
$presenting = [];
try {
    $stmt = $pdo->query("
        SELECT pc_id, prsenting_complaint
        FROM rescue_presenting_complaints
        ORDER BY prsenting_complaint ASC
    ");
    $presenting = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $presenting = [];
}

// Helper for selected attr
function sel($a, $b) { return ($a === $b ? 'selected' : ''); }
?>

<div class="rc-card rc-card-muted">
    <h3><?= htmlspecialchars(($lang['SECTION'] ?? 'Section') . ' 5 - ' . ($lang['TRIAGE'] ?? 'Triage') . ' & ' . ($lang['ASSESSMENT'] ?? 'Assessment')) ?></h3>

    <form id="section5-form" class="xform"
          data-required-fields="<?= htmlspecialchars(json_encode($SECTION5_FIELDS), ENT_QUOTES, 'UTF-8') ?>"
          onsubmit="event.preventDefault(); document.getElementById('section5-mark-complete').value='0'; saveSection(5, 'section5-form');">

        <input type="hidden" name="patient_id" value="<?= htmlspecialchars($pid ?? '') ?>">
        <input type="hidden" name="admission_id" value="<?= htmlspecialchars($aid ?? '') ?>">
        <input type="hidden" name="mark_complete" id="section5-mark-complete" value="0">

        <div class="xform-grid">

            <!-- Severity Score -->
            <div class="xform-field span-2">
                <label class="xform-label"><?= htmlspecialchars(($lang['SEVERITY'] ?? 'Severity') . ' ' . ($lang['SCORE'] ?? 'Score')) ?></label>

                <?php if (!$can_ss): ?>
                    <input type="hidden" name="ss_text" value="<?= htmlspecialchars($ss_text) ?>">
                <?php endif; ?>

                <select name="ss_text" id="ss_text"
                        class="xform-input <?= $can_ss ? '' : 'is-readonly' ?>"
                        <?= $can_ss ? '' : 'disabled' ?>>
                    <option value=""><?= htmlspecialchars(($lang['SELECT'] ?? 'Select') . ' ' . ($lang['SEVERITY'] ?? 'Severity') . '...') ?></option>
                    <option value="Apparently Healthy" <?= sel($ss_text,'Apparently Healthy') ?>><?= htmlspecialchars($lang['APPARENTLY_HEALTHY'] ?? 'Apparently Healthy') ?></option>
                    <option value="Mildly unwell"      <?= sel($ss_text,'Mildly unwell') ?>><?= htmlspecialchars($lang['MILDLY_UNWELL'] ?? 'Mildly unwell') ?></option>
                    <option value="Obvious Injuries"   <?= sel($ss_text,'Obvious Injuries') ?>><?= htmlspecialchars($lang['OBVIOUS_INJURIES'] ?? 'Obvious Injuries') ?></option>
                    <option value="Severe Injuries"    <?= sel($ss_text,'Severe Injuries') ?>><?= htmlspecialchars($lang['SEVERE_INJURIES'] ?? 'Severe Injuries') ?></option>
                    <option value="Near Death"         <?= sel($ss_text,'Near Death') ?>><?= htmlspecialchars($lang['NEAR_DEATH'] ?? 'Near Death') ?></option>
                </select>
            </div>

            <!-- Body Condition Score -->
            <div class="xform-field span-2">
                <label class="xform-label"><?= htmlspecialchars(($lang['BODY'] ?? 'Body') . ' ' . ($lang['CONDITION'] ?? 'Condition') . ' ' . ($lang['SCORE'] ?? 'Score')) ?></label>

                <?php if (!$can_bcs): ?>
                    <input type="hidden" name="bcs_text" value="<?= htmlspecialchars($bcs_text) ?>">
                <?php endif; ?>

                <select name="bcs_text" id="bcs_text"
                        class="xform-input <?= $can_bcs ? '' : 'is-readonly' ?>"
                        <?= $can_bcs ? '' : 'disabled' ?>>
                    <option value=""><?= htmlspecialchars(($lang['SELECT'] ?? 'Select') . ' ' . ($lang['CONDITION'] ?? 'Condition') . '...') ?></option>
                    <option value="BCS 1 Skeletal"             <?= sel($bcs_text,'BCS 1 Skeletal') ?>><?= htmlspecialchars($lang['ADM_BCS_1_LABEL'] ?? '1 - Emaciated/Skeletal') ?></option>
                    <option value="BCS 2 Underweight"          <?= sel($bcs_text,'BCS 2 Underweight') ?>><?= htmlspecialchars($lang['ADM_BCS_2_LABEL'] ?? '2 - Underweight') ?></option>
                    <option value="BCS 3 Slightly Underweight" <?= sel($bcs_text,'BCS 3 Slightly Underweight') ?>><?= htmlspecialchars($lang['ADM_BCS_3_LABEL'] ?? '3 - Slightly Underweight') ?></option>
                    <option value="BCS 4 Healthy"              <?= sel($bcs_text,'BCS 4 Healthy') ?>><?= htmlspecialchars($lang['ADM_BCS_4_LABEL'] ?? '4 - Healthy') ?></option>
                    <option value="BCS 5 Overweight"           <?= sel($bcs_text,'BCS 5 Overweight') ?>><?= htmlspecialchars($lang['ADM_BCS_5_LABEL'] ?? '5 - Overweight') ?></option>
                </select>
            </div>

            <!-- Presenting Complaint -->
            <div class="xform-field span-4">
                <label class="xform-label"><?= htmlspecialchars($lang['PRESENTING_COMPLAINT'] ?? 'Presenting Complaint') ?></label>

                <?php if (!$can_presenting): ?>
                    <input type="hidden" name="presenting_complaint" value="<?= htmlspecialchars($presenting_current) ?>">
                <?php endif; ?>

                <select name="presenting_complaint" id="presenting_complaint"
                        class="xform-input <?= $can_presenting ? '' : 'is-readonly' ?>"
                        <?= $can_presenting ? '' : 'disabled' ?>>
                    <option value=""><?= htmlspecialchars(($lang['SELECT'] ?? 'Select') . ' ' . ($lang['PRESENTING_COMPLAINT'] ?? 'Presenting Complaint') . '...') ?></option>
                    <?php foreach ($presenting as $pc): ?>
                        <?php $val = $pc['prsenting_complaint']; ?>
                        <option value="<?= htmlspecialchars($val) ?>" <?= sel($presenting_current, $val) ?>>
                            <?= htmlspecialchars($val) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- HPC -->
            <div class="xform-field span-4">
                <label class="xform-label"><?= htmlspecialchars(($lang['HISTORY'] ?? 'History') . ': ' . ($lang['PRESENTING_COMPLAINT'] ?? 'Presenting Complaint')) ?></label>

                <?php if (!$can_hpc): ?>
                    <input type="hidden" name="hpc" value="<?= htmlspecialchars($hpc) ?>">
                <?php endif; ?>

                <textarea name="hpc" id="hpc" rows="2"
                          class="xform-input <?= $can_hpc ? '' : 'is-readonly' ?>"
                          <?= $can_hpc ? '' : 'readonly' ?>><?= htmlspecialchars($hpc) ?></textarea>
            </div>

            <!-- On Examination -->
            <div class="xform-field span-4">
                <label class="xform-label"><?= htmlspecialchars($lang['ON_EXAMINATION'] ?? 'On Examination') ?></label>

                <?php if (!$can_exam): ?>
                    <input type="hidden" name="on_examination" value="<?= htmlspecialchars($on_examination) ?>">
                <?php endif; ?>

                <textarea name="on_examination" id="on_examination" rows="6"
                          class="xform-input <?= $can_exam ? '' : 'is-readonly' ?>"
                          <?= $can_exam ? '' : 'readonly' ?>><?= htmlspecialchars($on_examination) ?></textarea>
            </div>

        </div><!-- /xform-grid -->

        <div class="xform-actions">
            <br>
            <button type="submit" class="btn green"><?= htmlspecialchars(($lang['SAVE'] ?? 'Save') . ' ' . ($lang['SECTION'] ?? 'Section') . ' 5') ?></button>
            <button type="button" class="btn" id="markSection5Complete">
                <?= htmlspecialchars(($lang['MARK'] ?? 'Mark') . ' ' . ($lang['SECTION'] ?? 'Section') . ' ' . strtolower($lang['COMPLETE'] ?? 'complete')) ?>
            </button>
        </div>

    </form>
</div>

<script>
/* ===================== SECTION 5 CLIENT VALIDATION ===================== */
const SECTION5_FIELDS = <?= json_encode($SECTION5_FIELDS) ?>;

function missingRequiredSection5() {
    const missing = [];
    for (const f in SECTION5_FIELDS) {
        if (!SECTION5_FIELDS[f]) continue;
        const el = document.querySelector('[name="'+f+'"]');
        const val = el ? (el.value || '').trim() : '';
        if (val === '') missing.push(f);
    }
    return missing;
}

document.getElementById('markSection5Complete').onclick = () => {
    const missing = missingRequiredSection5();
    if (missing.length) {
        alert(<?= json_encode($lang['COMPLETE_REQUIRED_FIRST'] ?? 'Please complete required fields first.') ?>);
        return;
    }
    const flag = document.getElementById('section5-mark-complete');
    flag.value = '1';
    saveSection(5, 'section5-form');
    flag.value = '0';
};
</script>
