<!-- ADD OBSERVATION FORM -->
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<form action="controllers/form_handler.php" method="post" class="xform" id="addobservationform">

    <div class="xform-grid">

        <!-- AGE CATEGORY -->
        <div class="xform-field">
            <label class="xform-label" for="obs_age_text"><?= htmlspecialchars($lang['CURRENT_AGE']) ?></label>
            <select name="obs_age_text" id="obs_age_text" class="xform-input" required>
                <optgroup label="<?= htmlspecialchars($lang['MAMMALS']) ?>">
                    <option value="Newborn"><?= htmlspecialchars($lang['NEWBORN']) ?></option>
                    <option value="Dependent Juvenile"><?= htmlspecialchars($lang['DEPENDENT_JUVENILE']) ?></option>
                    <option value="Independent Juvenile"><?= htmlspecialchars($lang['INDEPENDENT_JUVENILE']) ?></option>
                    <option value="Adult"><?= htmlspecialchars($lang['ADULT']) ?></option>
                </optgroup>

                <optgroup label="<?= htmlspecialchars($lang['BIRDS']) ?>">
                    <option value="Hatchling"><?= htmlspecialchars($lang['HATCHLING']) ?></option>
                    <option value="Fledgling"><?= htmlspecialchars($lang['FLEDGLING']) ?></option>
                    <option value="Adult"><?= htmlspecialchars($lang['ADULT']) ?></option>
                </optgroup>
            </select>
        </div>

        <!-- SEVERITY SCORE -->
        <div class="xform-field">
            <label class="xform-label" for="obs_sev_text"><?= htmlspecialchars($lang['CURRENT_SEVERITY']) ?></label>
            <select name="obs_sev_text" id="obs_sev_text" class="xform-input" required style="width: 300px;">
                <option value="" disabled selected><?= htmlspecialchars($lang['SELECT'] . ' ' . $lang['SEVERITY']) ?></option>

                <?php
                $sevStmt = $pdo->prepare("SELECT * FROM rescue_severity_score ORDER BY ss_id ASC");
                $sevStmt->execute();
                while ($row = $sevStmt->fetch(PDO::FETCH_ASSOC)):
                ?>
                    <option value="<?= $row['ss_category'] ?>">
                        <?= $row['ss_incare_desc'] ?> (<?= $row['ss_category'] ?>)
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- BCS -->
        <div class="xform-field">
            <label class="xform-label" for="obs_bcs_text"><?= htmlspecialchars($lang['BODY'] . ' ' . $lang['CONDITION'] . ' ' . $lang['SCORE']) ?></label>
            <select name="obs_bcs_text" id="obs_bcs_text" class="xform-input" required>
                <option value="BCS 1 Skeletal">1 – Emaciated/Skeletal</option>
                <option value="BCS 2 Underweight">2 – Underweight</option>
                <option value="BCS 3 Slightly Underweight">3 – Slightly Underweight</option>
                <option value="BCS 4 Healthy">4 – Healthy</option>
                <option value="BCS 5 Overweight">5 – Overweight</option>
            </select>
        </div>

        <!-- NOTES (FULL WIDTH) -->
        <div class="xform-field" style="grid-column: span 4;">
            <label class="xform-label" for="obs_notes"><?= htmlspecialchars($lang['NOTES']) ?></label>
            <textarea name="obs_notes" id="obs_notes" class="xform-input" rows="4"></textarea>
        </div>

    </div>

    <!-- HIDDEN FIELDS -->
    <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
    <input type="hidden" name="admission_id" value="<?= $admission_id ?>">
    <input type="hidden" name="obs_user_id" value="<?= $user_id ?>"> <!-- or user ID -->
    <input type="hidden" name="audit_action" value="New Observation added for CRN-<?= $patient_id ?>">
    <input type="hidden" name="csrf_token"value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

    <br>

    <!-- SUBMIT -->
    <button type="submit" name="observationform" class="btn blue">
        <?= htmlspecialchars($lang['ADD'] . ' ' . $lang['OBSERVATION'] . ' ' . $lang['FOR'] . ' ' . $patient_name) ?>
    </button>

</form>

<!-- END OBSERVATION FORM -->
