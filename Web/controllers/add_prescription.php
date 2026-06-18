<?php 
// CSRF TOKEN GENERATION
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>
<!-- ADD PRESCRIPTION FORM -->
<p><?= htmlspecialchars($lang['RX_REPEAT_HELP']) ?></p>

<form action="controllers/form_handler.php" method="post" class="xform" id="addprescriptionform">

    <div class="xform-grid">

        <!-- DATE STARTED -->
        <div class="xform-field">
            <label class="xform-label" for="date"><?= htmlspecialchars($lang['DATE_STARTED']) ?></label>
            <input type="date" name="date" id="date" class="xform-input" required>
        </div>

        <!-- MEDICATION (AUTOCOMPLETE TEXTBOX) -->
        <div class="xform-field" style="position:relative;">
            <label class="xform-label"><?= htmlspecialchars($lang['LM_MEDICATION']) ?></label>

            <input type="text"
                   class="xform-input medication_input"
                   autocomplete="off"
                   placeholder="<?= htmlspecialchars($lang['MED_PROFILES_START_TYPING']) ?>"
                   required>

            <input type="hidden"
                   name="medication"
                   class="medication_hidden">
        </div>

        <!-- ROUTE -->
        <div class="xform-field">
            <label class="xform-label" for="route"><?= htmlspecialchars($lang['MEDS_COL_ROUTE']) ?></label>
            <select id="route" name="route" class="xform-input">
                <option value="Subcut"><?= htmlspecialchars($lang['SUBCUTANEOUS_INJECTION']) ?></option>
                <option value="IV"><?= htmlspecialchars($lang['INTRAVENOUS_INJECTION']) ?></option>
                <option value="Oral"><?= htmlspecialchars($lang['ORAL']) ?></option>
                <option value="Topical"><?= htmlspecialchars($lang['TOPICAL']) ?></option>
            </select>
        </div>

        <!-- DOSE -->
        <div class="xform-field">
            <label class="xform-label" for="dose"><?= htmlspecialchars($lang['MEDS_COL_DOSE']) ?></label>
            <input type="text" id="dose" name="dose" class="xform-input">
        </div>

        <!-- DOSE TYPE -->
        <div class="xform-field">
            <label class="xform-label" for="dose_type"><?= htmlspecialchars($lang['DOSE_TYPE']) ?></label>
            <select id="dose_type" name="dose_type" class="xform-input">
                <option>mcg</option>
                <option>mg</option>
                <option>g</option>
                <option>ml</option>
                <option>l</option>
                <option>prn</option>
                <option>spray</option>
            </select>
        </div>

        <!-- DURATION -->
        <div class="xform-field">
            <label class="xform-label" for="duration"><?= htmlspecialchars($lang['DURATION'] . ' (' . $lang['DAYS'] . ')') ?></label>
            <input type="text" id="duration" name="duration" class="xform-input">
        </div>

        <!-- FREQUENCY -->
        <div class="xform-field">
            <label class="xform-label" for="frequency"><?= htmlspecialchars($lang['MEDS_COL_FREQUENCY']) ?></label>
            <select name="frequency" id="frequency" class="xform-input" required>
                <option value="" disabled selected><?= htmlspecialchars($lang['SELECT'] . ' ' . $lang['MEDS_COL_FREQUENCY']) ?></option>
                <?php
                $freqStmt = $pdo->prepare("SELECT * FROM rescue_frequencies ORDER BY frequency ASC");
                $freqStmt->execute();
                while ($row = $freqStmt->fetch(PDO::FETCH_ASSOC)) {
                    echo '<option value="' . $row["frequency"] . '">' . $row["frequency"] . '</option>';
                }
                ?>
            </select>
        </div>
        <!-- BY WEIGHT -->
        <div class="xform-field">
            <label class="xform-label">
                        <?= htmlspecialchars($lang['DOSE_BY_WEIGHT']) ?>
            </label>
            <br><input type="checkbox"
                        name="dose_by_weight"
                        value="1">
        </div>


    </div> <!-- end xform-grid -->

    <!-- HIDDEN FIELDS -->
    <input type="hidden" name="user_id" value="<?= $user_id ?>">
    <input type="hidden" name="admission_id" value="<?= $admission_id ?>">
    <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
    <input type="hidden" name="centre_id" value="<?= $centre_id ?>">
    <input type="hidden" name="audit_action" value="Prescription added for CRN-<?= $patient_id ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
    <br>

    <!-- SUBMIT -->
    <button type="submit" name="prescriptionform" class="btn blue">
        <?= htmlspecialchars($lang['ADD'] . ' ' . $lang['PRESCRIPTION'] . ' ' . $lang['FOR'] . ' ' . $patient_name) ?>
    </button>

</form>
