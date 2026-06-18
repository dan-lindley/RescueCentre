<!-- START ADD WEIGHT FORM -->

<?php
// ensure patient_id arrives correctly when loaded via AJAX
$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : $patient_id;
?>

<form action="controllers/form_handler.php" method="post">


    <div class="xform-grid">

        <!-- DATE -->
        <div class="xform-field">
            <label class="xform-label" for="date"><?= htmlspecialchars($lang['DATE'] . ' & ' . $lang['TIME']) ?></label>
            <input type="datetime-local" name="date" id="date" class="xform-input" required>
        </div>

        <!-- WEIGHT -->
        <div class="xform-field">
            <label class="xform-label" for="weight"><?= htmlspecialchars($lang['WEIGHT']) ?></label>
            <input type="text" name="weight" id="weight" class="xform-input" placeholder="<?= htmlspecialchars($lang['ANIMAL'] . ' ' . $lang['WEIGHT']) ?>" required>
        </div>

        <!-- UNIT -->
        <div class="xform-field">
            <label class="xform-label" for="weight_unit"><?= htmlspecialchars($lang['DIET_TH_UNIT']) ?></label>
            <select id="weight_unit" name="weight_unit" class="xform-input">
                <option value="g"><?= htmlspecialchars($lang['GRAMS']) ?></option>
                <option value="kg"><?= htmlspecialchars($lang['KILOGRAMS']) ?></option>
                <option value="lbs"><?= htmlspecialchars($lang['POUNDS']) ?></option>
            </select>
        </div>

        <!-- EMPTY COLUMN -->
        <div class="xform-field"></div>

    </div>

    <br>

    <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
 <input type="hidden" name="audit_action" value="New Weight addedfor CRN-<?= $patient_id ?>">
    <button type="submit" name="addweightForm" class="btn grey">
        <?= htmlspecialchars($lang['ADD'] . ' ' . $lang['WEIGHT'] . ' ' . $lang['FOR'] . ' ' . ($patient_name ?? '')) ?>
    </button>

</form>

<!-- END ADD WEIGHT FORM -->
