<?php if (!isset($treatment_dropdown_cache_loaded)) {

    // Treatment options (static for now)
    $treatment_options = [
        'Heating Pad' => $lang['TRT_HEATING_PAD'],
        'Food' => $lang['TRT_FOOD'],
        'Water' => $lang['TRT_WATER'],
        'IV' => $lang['TRT_IV'],
        'Subcutaneous Fluids' => $lang['TRT_SUBCUTANEOUS_FLUIDS'],
        'Pain Relief' => $lang['TRT_PAIN_RELIEF'],
        'Parasite Removal' => $lang['TRT_PARASITE_REMOVAL'],
        'Tick Removal' => $lang['TRT_TICK_REMOVAL'],
        'Bath' => $lang['TRT_BATH'],
        'Incubator' => $lang['TRT_INCUBATOR'],
        'Maggot Removal' => $lang['TRT_MAGGOT_REMOVAL'],
        'Flystrike (eggs) Removal' => $lang['TRT_FLYSTRIKE_REMOVAL'],
        'Topical Treatment' => $lang['TRT_TOPICAL'],
        'Over-counter Medication' => $lang['TRT_OVER_COUNTER_MEDICATION'],
        'Natural Remedy' => $lang['TRT_NATURAL_REMEDY'],
        'Other (use notes to describe)' => $lang['TRT_OTHER_NOTES'],
    ];

    $treatment_dropdown_cache_loaded = true;

}
// CSRF TOKEN GENERATION
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

 ?>

<!-- ADD TREATMENT FORM -->

<form action="controllers/form_handler.php" method="post" class="xform" id="addtreatmentform">

    <div class="xform-grid">

        <!-- TREATMENT TYPE -->
        <div class="xform-field">
            <label class="xform-label" for="treatment"><?= htmlspecialchars($lang['TREATMENT']) ?></label>
            <select name="treatment" id="treatment" class="xform-input" required>
                <option value="" disabled selected><?= htmlspecialchars($lang['SELECT'] . ' ' . $lang['TREATMENT']) ?></option>

                <?php foreach ($treatment_options as $value => $label): ?>
                    <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- DONE BY -->
        <div class="xform-field">
            <label class="xform-label" for="done_by"><?= htmlspecialchars($lang['DONE_BY']) ?></label>
            <input type="text"
                   name="done_by"
                   id="done_by"
                   class="xform-input"
                   value="<?php echo $record_name; ?>"
                   readonly>
        </div>

        <!-- TREATMENT NOTES (SPAN ALL 4 COLUMNS) -->
        <div class="xform-field" style="grid-column: span 4;">
            <label class="xform-label" for="treatment_free_text"><?= htmlspecialchars($lang['NOTES']) ?></label>
            <textarea name="treatment_free_text"
                      id="treatment_free_text"
                      class="xform-input"
                      rows="4"></textarea>
        </div>

    </div>

    <br>

    <!-- HIDDEN FIELDS -->
    <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
    <input type="hidden" name="audit_action" value="Treatment added for CRN-<?= $patient_id ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

    <!-- SUBMIT -->
    <button type="submit" name="treatmentform" class="btn blue">
        <?= htmlspecialchars($lang['ADD'] . ' ' . $lang['TREATMENT'] . ' ' . $lang['FOR'] . ' ' . $patient_name) ?>
    </button>

</form>
<!-- END TREATMENT FORM -->
