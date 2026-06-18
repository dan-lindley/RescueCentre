<?php
// Safe lookup for disposition types
require_once __DIR__ . '/../operations/modules_registry.php';

$adoptionsModuleActive = modules_is_active($pdo, 'adoptions', isset($centre_id) ? (int)$centre_id : null);
$dispStmt = $pdo->prepare("
    SELECT disposition
    FROM rescue_dispositions
    WHERE (:adoptions_active = 1 OR disposition NOT IN ('For Adoption', 'Adopted'))
    ORDER BY disposition ASC
");
$dispStmt->execute([':adoptions_active' => $adoptionsModuleActive ? 1 : 0]);
$dispositions = $dispStmt->fetchAll(PDO::FETCH_ASSOC);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


?>

<form action="controllers/form_handler.php" method="post" class="xform" id="dispositionform">

    <div class="xform-grid">

        <!-- DISPOSITION -->
        <div class="xform-field">
            <label class="xform-label" for="disposition"><?= htmlspecialchars($lang['DISPOSITION']) ?></label>
            <select name="disposition" id="disposition" class="xform-input" required>
                <option value="" disabled selected><?= htmlspecialchars($lang['SELECT'] . ' ' . strtolower($lang['PATIENT']) . ' ' . strtolower($lang['DISPOSITION'])) ?></option>
                <?php foreach ($dispositions as $d): ?>
                    <option value="<?= htmlspecialchars($d['disposition']) ?>">
                        <?= htmlspecialchars($d['disposition']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- DATE/TIME -->
        <div class="xform-field">
            <label class="xform-label" for="disposition_date"><?= htmlspecialchars($lang['DISPOSITION'] . ' ' . $lang['DATE'] . ' & ' . $lang['TIME']) ?></label>
            <input type="datetime-local" name="disposition_date" id="disposition_date"
                   class="xform-input" required>
        </div>

        <!-- EUTHANASIA METHOD -->
        <div class="xform-field">
            <label class="xform-label" for="euthanasia_method"><?= htmlspecialchars($lang['EUTHANASIA'] . ' ' . $lang['METHOD']) ?></label>
            <select name="euthanasia_method" id="euthanasia_method" class="xform-input">
                <option value="Not Applicable" selected><?= htmlspecialchars($lang['NOT_APPLICABLE']) ?></option>
                <option value="Pharmacological - Vet"><?= htmlspecialchars($lang['ADM_PHARMACOLOGICAL_VET']) ?></option>
                <option value="Pharmacological - Centre"><?= htmlspecialchars($lang['ADM_PHARMACOLOGICAL_CENTRE']) ?></option>
                <option value="Manual"><?= htmlspecialchars($lang['MANUAL']) ?></option>
                <option value="Captive Bolt"><?= htmlspecialchars($lang['ADM_CAPTIVE_BOLT']) ?></option>
                <option value="Shot"><?= htmlspecialchars($lang['SHOT']) ?></option>
                <option value="Other"><?= htmlspecialchars($lang['OTHER']) ?></option>
            </select>
        </div>

        <!-- COMMENT (FULL WIDTH) -->
        <div class="xform-field" style="grid-column: span 4;">
            <label class="xform-label" for="disposition_comment"><?= htmlspecialchars($lang['COMMENTS']) ?></label>
            <textarea name="disposition_comment" id="disposition_comment"
                      class="xform-input" rows="3"></textarea>
        </div>

    </div>

    <!-- HIDDEN FIELDS -->
    <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
    <input type="hidden" name="theadmissionid" value="<?= $admission_id ?>">
    <input type="hidden" name="centre_id" value="<?= $centre_id ?>">
    <input type="hidden" name="disposition_user" value="<?= $user_id ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

    
    <input type="hidden" name="audit_action" value="Updated disposition for CRN-<?= $patient_id ?>">

    <br><button type="submit" name="formdisp" class="btn red">
        <?= htmlspecialchars($lang['DISCHARGE'] . ' ' . $lang['PATIENT']) ?>
    </button>

</form>
