<?php
// controllers/admissions/section8.php
// Discharge / disposition editor for editpatient.php

require_once __DIR__ . '/../../operations/permissions.php';
require_once __DIR__ . '/../../operations/modules_registry.php';

registerPermission('patients.discharge', 'Discharge patient', 'action');
$can_discharge = can('patients.discharge');

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

$storedDisposition = trim((string)($admission['disposition'] ?? ''));
$uiDisposition = $storedDisposition === 'Transferred Out' ? 'Transferred to another rescue' : $storedDisposition;
$dispositionDateValue = '';

if (!empty($admission['disposition_date'])) {
    try {
        $dispositionDateValue = (new DateTime((string)$admission['disposition_date']))->format('Y-m-d\TH:i');
    } catch (Throwable $e) {
        $dispositionDateValue = '';
    }
}

$euthanasiaMethod = trim((string)($admission['euthanasia_method'] ?? 'Not Applicable'));
$dispositionComment = (string)($admission['disposition_comment'] ?? '');
?>

<div class="rc-card rc-card-muted">
    <h3><?= htmlspecialchars(($lang['DISCHARGE'] ?? 'Discharge') . ' / ' . ($lang['DISPOSITION'] ?? 'Disposition')) ?></h3>

    <?php if (!$can_discharge): ?>
        <div class="rc-alert amber"><?= htmlspecialchars($lang['ADM_DISPOSITION_NO_PERMISSION'] ?? 'You can view disposition details, but you do not have permission to update them.') ?></div>
    <?php endif; ?>

    <form action="controllers/form_handler.php" method="post" class="xform" id="dispositionform">
        <div class="xform-grid">
            <div class="xform-field">
                <label class="xform-label" for="disposition"><?= htmlspecialchars($lang['DISPOSITION'] ?? 'Disposition') ?></label>
                <select name="disposition" id="disposition" class="xform-input" required <?= $can_discharge ? '' : 'disabled' ?>>
                    <option value="" disabled <?= $uiDisposition === '' ? 'selected' : '' ?>><?= htmlspecialchars(($lang['SELECT'] ?? 'Select') . ' ' . ($lang['DISPOSITION'] ?? 'Disposition')) ?></option>
                    <?php foreach ($dispositions as $d): ?>
                        <?php $option = (string)$d['disposition']; ?>
                        <option value="<?= htmlspecialchars($option) ?>" <?= $option === $uiDisposition ? 'selected' : '' ?>>
                            <?= htmlspecialchars($option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="xform-field">
                <label class="xform-label" for="disposition_date"><?= htmlspecialchars(($lang['DISPOSITION'] ?? 'Disposition') . ' ' . ($lang['DATE'] ?? 'Date') . ' / ' . ($lang['TIME'] ?? 'Time')) ?></label>
                <input type="datetime-local"
                       name="disposition_date"
                       id="disposition_date"
                       class="xform-input"
                       value="<?= htmlspecialchars($dispositionDateValue) ?>"
                       required
                       <?= $can_discharge ? '' : 'disabled' ?>>
            </div>

            <div class="xform-field">
                <label class="xform-label" for="euthanasia_method"><?= htmlspecialchars(($lang['EUTHANASIA'] ?? 'Euthanasia') . ' ' . ($lang['METHOD'] ?? 'Method')) ?></label>
                <select name="euthanasia_method" id="euthanasia_method" class="xform-input" <?= $can_discharge ? '' : 'disabled' ?>>
                    <?php
                    $euthanasiaMethods = [
                        'Not Applicable' => $lang['NOT_APPLICABLE'] ?? 'Not Applicable',
                        'Pharmacological - Vet' => $lang['ADM_PHARMACOLOGICAL_VET'] ?? 'Pharmacological - Vet',
                        'Pharmacological - Centre' => $lang['ADM_PHARMACOLOGICAL_CENTRE'] ?? 'Pharmacological - Centre',
                        'Manual' => $lang['MANUAL'] ?? 'Manual',
                        'Captive Bolt' => $lang['ADM_CAPTIVE_BOLT'] ?? 'Captive Bolt',
                        'Shot' => $lang['SHOT'] ?? 'Shot',
                        'Other' => $lang['OTHER'] ?? 'Other',
                    ];
                    ?>
                    <?php foreach ($euthanasiaMethods as $method => $methodLabel): ?>
                        <option value="<?= htmlspecialchars($method) ?>" <?= $method === $euthanasiaMethod ? 'selected' : '' ?>>
                            <?= htmlspecialchars($methodLabel) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="xform-field span-4">
                <label class="xform-label" for="disposition_comment"><?= htmlspecialchars($lang['COMMENTS'] ?? 'Comments') ?></label>
                <textarea name="disposition_comment"
                          id="disposition_comment"
                          class="xform-input"
                          rows="3"
                          <?= $can_discharge ? '' : 'disabled' ?>><?= htmlspecialchars($dispositionComment) ?></textarea>
            </div>
        </div>

        <input type="hidden" name="patient_id" value="<?= (int)$pid ?>">
        <input type="hidden" name="theadmissionid" value="<?= (int)$aid ?>">
        <input type="hidden" name="centre_id" value="<?= (int)$centre_id ?>">
        <input type="hidden" name="disposition_user" value="<?= (int)$user_id ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="audit_action" value="Updated disposition for CRN-<?= (int)$pid ?>">

        <?php if ($can_discharge): ?>
            <div class="xform-actions">
                <button type="submit" name="formdisp" class="btn red">
                    <?= htmlspecialchars(($lang['SAVE'] ?? 'Save') . ' ' . ($lang['DISPOSITION'] ?? 'Disposition')) ?>
                </button>
            </div>
        <?php endif; ?>
    </form>
</div>
