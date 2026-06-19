<!-- ADD MEASUREMENT FORM -->


<form action="controllers/form_handler.php" method="post" class="xform" id="addmeasurementForm">

    <div class="xform-grid">

        <!-- DATE -->
        <div class="xform-field">
            <label class="xform-label" for="date"><?= htmlspecialchars($lang['DATE'] . ' & ' . $lang['TIME']) ?></label>
            <input type="datetime-local" name="date" id="date" class="xform-input" required>
        </div>

        <!-- MEASUREMENT -->
        <div class="xform-field">
            <label class="xform-label" for="measurement"><?= htmlspecialchars($lang['MEASUREMENT']) ?></label>
            <input type="text" name="measurement" id="measurement" class="xform-input"
                   placeholder="<?= htmlspecialchars($lang['ANIMAL'] . ' ' . strtolower($lang['MEASUREMENT'])) ?>" required>
        </div>

        <!-- UNIT -->
        <div class="xform-field">
            <label class="xform-label" for="measurement_unit"><?= htmlspecialchars($lang['DIET_TH_UNIT']) ?></label>
            <select id="measurement_unit" name="measurement_unit" class="xform-input">
                <option value="mm"><?= htmlspecialchars($lang['MILLIMETERS']) ?></option>
                <option value="cm"><?= htmlspecialchars($lang['CENTIMETERS']) ?></option>
                <option value="m"><?= htmlspecialchars($lang['METERS']) ?></option>
                <option value="in"><?= htmlspecialchars($lang['INCHES']) ?></option>
                <option value="ft"><?= htmlspecialchars($lang['FEET']) ?></option>
            </select>
        </div>

        <!-- EMPTY 4th COL (keeps alignment) -->
        <div class="xform-field"></div>

    </div>
<br>
    <!-- BUTTON BELOW GRID -->
    <input type="hidden" name="measurement_thepatientid" value="<?php echo $patient_id; ?>">
     <input type="hidden" name="audit_action" value="New measurement added for CRN-<?= $patient_id ?>">
    <button type="submit" name="addmeasurementForm" class="btn grey">
        <?= htmlspecialchars($lang['ADD'] . ' ' . $lang['MEASUREMENT'] . ' ' . $lang['FOR'] . ' ' . $patient_name) ?>
    </button>

</form>

<!-- END ADD MEASUREMENT FORM -->
