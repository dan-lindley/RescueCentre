<?php
// controllers/admissions/section7.php
// ----------------------------------------------------
// SECTION 7 – DECLARATION & E-SIGNATURE
// DB model (matches save_section.php):
//  - rescue_signatures.signature_data = base64 PNG (data URL) OR ''
//  - rescue_signatures.refused = 1 indicates refusal/no signature
// ----------------------------------------------------

// $pid, $aid, $pdo, $centre_id come from wrapper

require_once __DIR__ . '/../../operations/permissions.php';

// Permission to complete declaration (one-time action)
registerPermission('admission.declaration.complete', 'Complete declaration/signature', 'action');
$can_complete = can('admission.declaration.complete');

// ------------------------------------------------------------
// 1. Load declaration text (centre meta → fallback default)
// ------------------------------------------------------------
$defaultDeclaration =
    ($lang['ADM_DEFAULT_DECLARATION_1'] ?? 'By handing over this animal to the rescue centre, the finder confirms that they transfer ongoing responsibility for the care and welfare of the animal to the rescue.')
    . "\n\n"
    . ($lang['ADM_DEFAULT_DECLARATION_2'] ?? 'The finder understands that their personal details (where provided and consented) may be stored and used for the purposes of providing updates on the animal’s progress and for audit/legal purposes in line with GDPR and the centre’s privacy policy.');

$declarationText = $defaultDeclaration;

try {
    $stmt = $pdo->prepare("
        SELECT handover_declaration_text
        FROM rescue_centre_meta
        WHERE centre_id = :cid
        LIMIT 1
    ");
    $stmt->execute([':cid' => $centre_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && trim($row['handover_declaration_text']) !== '') {
        $declarationText = $row['handover_declaration_text'];
    }
} catch (Exception $e) {
    // silently fall back
}

// ------------------------------------------------------------
// 2. Check if we already have a signature/record (LATEST)
// ------------------------------------------------------------
$hasSignatureRecord  = false;
$signatureWasRefused = false;
$storedSignatureData = null;
$lastSignedAt        = null;

try {
    $stmt = $pdo->prepare("
        SELECT signature_data, refused, signed_at
        FROM rescue_signatures
        WHERE admission_id = :aid
          AND patient_id   = :pid
        ORDER BY signed_at DESC
        LIMIT 1
    ");
    $stmt->execute([
        ':aid' => $aid,
        ':pid' => $pid
    ]);
    $sigRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($sigRow) {
        $hasSignatureRecord = true;
        $lastSignedAt       = $sigRow['signed_at'] ?? null;

        $signatureWasRefused = !empty($sigRow['refused']) && (int)$sigRow['refused'] === 1;

        if (!$signatureWasRefused && !empty($sigRow['signature_data'])) {
            $storedSignatureData = $sigRow['signature_data']; // data URL
        }
    }
} catch (Exception $e) {
    // table may not exist yet
}
?>

<div class="rc-card rc-card-muted">
    <h3><?= htmlspecialchars(($lang['SECTION'] ?? 'Section') . ' 7 - ' . ($lang['DECLARATION'] ?? 'Declaration')) ?></h3>

    <?php if ($hasSignatureRecord): ?>
        <p class="rc-alert green">
            <?= htmlspecialchars($lang['ADM_DECLARATION_EXISTS'] ?? 'A declaration record already exists for this admission') ?>
            <?php if ($lastSignedAt): ?>
                (<?= htmlspecialchars($lang['ADM_RECORDED_ON'] ?? 'recorded on') ?> <?= htmlspecialchars($lastSignedAt) ?>)
            <?php endif; ?>.
            <?= htmlspecialchars($lang['ADM_DECLARATION_LOCKED'] ?? 'The declaration can only be completed once and cannot be edited.') ?>
        </p>
    <?php elseif (!$can_complete): ?>
        <p class="rc-alert amber">
            <?= htmlspecialchars($lang['ADM_DECLARATION_NO_PERMISSION'] ?? 'You can view this declaration, but you do not have permission to complete it.') ?>
        </p>
    <?php endif; ?>

    <form id="section7-form" class="xform" onsubmit="event.preventDefault();">
        <input type="hidden" name="sid" value="7">
        <input type="hidden" name="patient_id" value="<?= htmlspecialchars($pid ?? '') ?>">
        <input type="hidden" name="admission_id" value="<?= htmlspecialchars($aid ?? '') ?>">

        <input type="hidden" name="signature_data" id="signature_data" value="">

        <div class="xform-grid">

            <!-- Declaration text -->
            <div class="xform-field span-4">
                <label class="xform-label"><?= htmlspecialchars($lang['DECLARATION'] ?? 'Declaration') ?></label>
                <div class="rc-panel rc-card-muted">
                    <?= nl2br(htmlspecialchars($declarationText)) ?>
                </div>
            </div>

            <?php if ($hasSignatureRecord && $storedSignatureData): ?>

                <div class="xform-field span-4">
                    <label class="xform-label"><?= htmlspecialchars($lang['SIGNATURE'] ?? 'Signature') ?></label>
                    <img src="<?= htmlspecialchars($storedSignatureData) ?>"
                         alt="<?= htmlspecialchars($lang['SIGNATURE'] ?? 'Signature') ?>"
                         class="rc-stored-signature">
                    <p class="rc-note">
                        <?= htmlspecialchars($lang['SIGNATURE_COMPLETE_LOCKED'] ?? 'Declaration completed with an electronic signature. Changes are not allowed.') ?>
                    </p>
                </div>

            <?php elseif ($hasSignatureRecord && $signatureWasRefused): ?>

                <div class="xform-field span-4">
                    <label class="xform-label"><?= htmlspecialchars($lang['SIGNATURE'] ?? 'Signature') ?></label>
                    <p class="rc-alert amber">
                        <?= htmlspecialchars($lang['SIGNATURE_REFUSED_LOCKED'] ?? 'No signature was provided / signature refused. This has been recorded and cannot be changed.') ?>
                    </p>
                </div>

            <?php else: ?>

                <div class="xform-field span-4">
                    <label class="xform-label"><?= htmlspecialchars(($lang['SIGNATURE'] ?? 'Signature') . ' (' . strtolower($lang['FINDER'] ?? 'finder') . ')') ?></label>
                    <p class="rc-note"><?= htmlspecialchars($lang['ADM_SIGN_BELOW'] ?? 'Ask the finder to sign in the box below.') ?></p>

                    <div class="rc-signature-frame">
                        <canvas id="signature-canvas"
                                width="480"
                                height="180"
                                class="rc-signature-pad"></canvas>
                    </div>

                    <div>
                        <button type="button" class="btn" id="clearSignatureBtn" <?= $can_complete ? '' : 'disabled' ?>>
                            <?= htmlspecialchars(($lang['CLEAR'] ?? 'Clear') . ' ' . strtolower($lang['SIGNATURE'] ?? 'signature')) ?>
                        </button>
                    </div>

                    <small class="form-text text-muted">
                        <?= htmlspecialchars($lang['SIGNATURE_INSTRUCTIONS'] ?? 'Use mouse or touch to sign. If no signature is provided, tick the box below.') ?>
                    </small>
                </div>

                <div class="xform-field span-4">
                    <label class="xform-label"><?= htmlspecialchars($lang['ADM_NO_SIGNATURE_PROMPT'] ?? 'Tick this box if a signature was not obtained:') ?></label>
                    <label>
                        <input type="checkbox" id="no_signature" name="no_signature" value="1" <?= $can_complete ? '' : 'disabled' ?>>
                        (<?= htmlspecialchars(($lang['NO'] ?? 'No') . ' ' . strtolower($lang['SIGNATURE'] ?? 'signature')) ?>)
                    </label>
                    <p class="rc-note">
                        <?= htmlspecialchars($lang['SIGNATURE_COMPLETION_RULE'] ?? 'This section is only considered complete if either a signature is captured or this box is checked to record that a signature was refused, not possible or not collected.') ?>
                    </p>
                </div>

            <?php endif; ?>

        </div>

        <?php if (!$hasSignatureRecord): ?>
            <div class="xform-actions">
                <button type="button"
                        class="btn green"
                        id="section7-save-btn"
                        <?= $can_complete ? '' : 'disabled' ?>>
                    <?= htmlspecialchars(($lang['SAVE'] ?? 'Save') . ' ' . ($lang['SECTION'] ?? 'Section') . ' 7') ?>
                </button>
            </div>
        <?php endif; ?>
    </form>
</div>

<?php if (!$hasSignatureRecord): ?>
<script>
(function() {
    const canComplete = <?= $can_complete ? 'true' : 'false' ?>;
    const canvas = document.getElementById('signature-canvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    ctx.lineWidth = 2;
    ctx.lineJoin = 'round';
    ctx.lineCap = 'round';
    ctx.strokeStyle = '#000';

    let drawing = false;
    let lastX = 0, lastY = 0;

    function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        const evt  = e.touches ? e.touches[0] : e;
        return { x: evt.clientX - rect.left, y: evt.clientY - rect.top };
    }

    function startDraw(e) {
        if (!canComplete) return;
        e.preventDefault();
        const p = getPos(e);
        drawing = true;
        lastX = p.x; lastY = p.y;
    }

    function draw(e) {
        if (!canComplete || !drawing) return;
        e.preventDefault();
        const p = getPos(e);
        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
        lastX = p.x; lastY = p.y;
    }

    function endDraw(e) {
        if (!drawing) return;
        e.preventDefault();
        drawing = false;
    }

    canvas.addEventListener('mousedown', startDraw);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', endDraw);
    canvas.addEventListener('mouseleave', endDraw);

    canvas.addEventListener('touchstart', startDraw, { passive: false });
    canvas.addEventListener('touchmove',  draw,      { passive: false });
    canvas.addEventListener('touchend',   endDraw);
    canvas.addEventListener('touchcancel',endDraw);

    const clearBtn = document.getElementById('clearSignatureBtn');
    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            if (!canComplete) return;
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        });
    }

    function isCanvasBlank(c) {
        const blank = document.createElement('canvas');
        blank.width = c.width; blank.height = c.height;
        return c.toDataURL() === blank.toDataURL();
    }

    const saveBtn = document.getElementById('section7-save-btn');
    if (saveBtn) {
        saveBtn.addEventListener('click', () => {
            if (!canComplete) return;

            const sigField = document.getElementById('signature_data');
            const refused  = document.getElementById('no_signature')?.checked;

            if (refused) {
                sigField.value = '';
            } else {
                sigField.value = (!isCanvasBlank(canvas)) ? canvas.toDataURL('image/png') : '';
            }

            if (typeof saveSection === 'function') {
                saveSection(7, 'section7-form');
            } else {
                alert(<?= json_encode($lang['SIGNATURE_HELPER_UNAVAILABLE'] ?? 'The section save helper is not available.') ?>);
            }
        });
    }
})();
</script>
<?php endif; ?>
