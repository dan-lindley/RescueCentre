<?php
// ------------------------------------------------------------
// meds_simple.php
// Simple (non-stock) medication administration form
// ------------------------------------------------------------

// Expected variables from wrapper:
// $patient_id, $patient_name, $medications
?>

<form action="controllers/medication/medication_handler.php"
      method="post"
      class="xform medication-form">

    <div class="xform-grid">

        <!-- Medication -->
        <div class="xform-field span-2">
            <label class="xform-label"><?= htmlspecialchars($lang['LM_MEDICATION']) ?></label>

            <div style="position:relative;">
                <input type="text"
                       class="xform-input medication_input"
                       placeholder="<?= htmlspecialchars($lang['MED_PROFILES_START_TYPING']) ?>">

                <!-- TEXT value stored (not ID) -->
                <input type="hidden"
                       name="medication_given"
                       class="medication_hidden">
            </div>
        </div>

        <!-- Dose + Volume (side by side) -->
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['MEDS_COL_DOSE']) ?></label>
            <input type="number"
                   step="0.001"
                   name="dose"
                   class="xform-input"
                   required>
        </div>

        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['VOLUME']) ?></label>
            <input type="number"
                   step="0.001"
                   name="volume_used"
                   class="xform-input"
                   required>
        </div>

        <!-- Dose type -->
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['DOSE_TYPE']) ?></label>
            <select name="dose_type" class="xform-input">
                <option value="mcg">mcg</option>
                <option value="mg" selected>mg</option>
                <option value="g">g</option>
                <option value="ml">ml</option>
                <option value="l">l</option>
                <option value="prn">prn</option>
            </select>
        </div>

        <!-- Date given -->
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['GIVEN_ON']) ?></label>
            <input type="datetime-local"
                   name="date_given"
                   class="xform-input"
                   required>
        </div>

    </div>

    <!-- Hidden context -->
    <input type="hidden" name="patient_id" value="<?= (int)$patient_id ?>">
    <input type="hidden" name="centre_id" value="<?= (int)$GLOBALS['centre_id'] ?>">
    <input type="hidden" name="given_by" value="<?= htmlspecialchars($GLOBALS['record_name']) ?>">
    <input type="hidden" name="given_by_id" value="<?= (int)$GLOBALS['user_id'] ?>">

    <div class="xform-actions">
        <button type="submit"
                name="medicationform_simple"
                class="btn blue">
            <?= htmlspecialchars($lang['ADD'] . ' ' . $lang['LM_MEDICATION'] . ' ' . $lang['FOR'] . ' ' . $patient_name) ?>
        </button>
    </div>

</form>

<script>
// ------------------------------------------------------------
// Simple medication autocomplete (TEXT only)
// ------------------------------------------------------------
(function () {

    const meds = <?= json_encode(
        array_map(function ($m) {
            return $m['common_name'] ?: $m['medication_name'];
        }, $medications),
        JSON_UNESCAPED_SLASHES
    ) ?>;

    document.querySelectorAll('.medication_input').forEach(input => {
        const hidden = input.parentElement.querySelector('.medication_hidden');

        input.addEventListener('blur', () => {
            hidden.value = input.value.trim();
        });

        input.addEventListener('change', () => {
            hidden.value = input.value.trim();
        });
    });

})();
</script>
