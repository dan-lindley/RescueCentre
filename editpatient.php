<?php
// editpatient.php
// ------------------------------------------------------------
// Edit existing Patient + Admission (controlled re-entry)
// Reuses admission section files + save_section.php
// ------------------------------------------------------------

include 'dashmain.php';
include 'models/patient_data.php';

require_once __DIR__ . '/operations/permissions.php';
registerPermission('patient.edit', 'Edit patient and admission details', 'page');
requirePermission('patient.edit');

// ------------------------------------------------------------
// HARD REQUIREMENTS
// ------------------------------------------------------------
if (empty($patient_id)) {
    die('Patient ID missing');
}

if (empty($admission_id)) {
    die('No active admission found for this patient');
}

/* ============================================================
   FIX: LOAD FULL ROW ARRAYS FOR SHARED SECTION FILES
   - admission wizard loads $patient and $admission arrays
   - editpatient.php must do the same so sections prefill
============================================================ */

// Wizard-compatible IDs
$pid = (int)$patient_id;
$aid = (int)$admission_id;

// Load patient row into $patient array
$patient = null;
$pStmt = $pdo->prepare("SELECT * FROM rescue_patients WHERE patient_id = :pid LIMIT 1");
$pStmt->execute([':pid' => $pid]);
$patient = $pStmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die('Patient not found');
}

// Load admission row into $admission array
$admission = null;
$aStmt = $pdo->prepare("SELECT * FROM rescue_admissions WHERE admission_id = :aid LIMIT 1");
$aStmt->execute([':aid' => $aid]);
$admission = $aStmt->fetch(PDO::FETCH_ASSOC);

if (!$admission) {
    die('Admission not found');
}

// Normalise for section files that expect these variable names
$admission_id = $aid; // keep existing var name too

// ------------------------------------------------------------
// EDIT MODE FLAG (used by section files)
// ------------------------------------------------------------
define('ADMISSION_EDIT_MODE', true);

// ------------------------------------------------------------
// Which section to show
// ------------------------------------------------------------
$sid = isset($_GET['sid']) ? (int)$_GET['sid'] : 2;
$allowedSections = [1,2,3,4,5,6,8];

if (!in_array($sid, $allowedSections, true)) {
    $sid = 2;
}

// ------------------------------------------------------------
// Page header
// ------------------------------------------------------------
echo template_admin_header(
    'CRN: ' . $patient_id . ' - ' . $patient_name . ' - ' . ($lang['EDIT'] ?? 'Edit') . ' ' . ($lang['PATIENT'] ?? 'Patient') . ' & ' . ($lang['ADMISSION'] ?? 'Admission'),
    'patients',
    'viewpatient'
);

// ------------------------------------------------------------
// Flash messages
// ------------------------------------------------------------
if (!empty($_GET['msg'])) {
    echo '<div class="rc-alert green">'
        . htmlspecialchars($_GET['msg']) .
        '</div>';
}

if (!empty($_GET['error'])) {
    echo '<div class="rc-alert red">'
        . htmlspecialchars($_GET['error']) .
        '</div>';
}
?>



<div class="content-title">
    <div class="title">
        <div class="icon">
            <!-- same icon as viewpatient -->
            <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                <path d="M298.5 156.9C312.8 199.8 298.2 243.1 265.9 253.7C233.6 264.3 195.8 238.1 181.5 195.2C167.2 152.3 181.8 109 214.1 98.4C246.4 87.8 284.2 114 298.5 156.9zM133.2 465.2C185.6 323.9 278.7 288 320 288C361.3 288 454.4 323.9 506.8 465.2C510.4 474.9 512 485.3 512 495.7L512 497.3C512 523.1 491.1 544 465.3 544C453.8 544 442.4 542.6 431.3 539.8L343.3 517.8C328 514 312 514 296.7 517.8L208.7 539.8C197.6 542.6 186.2 544 174.7 544C148.9 544 128 523.1 128 497.3L128 495.7C128 485.3 129.6 474.9 133.2 465.2z"/>
            </svg>
        </div>
        <div class="txt">
            <h2 class="pagehead"><?= htmlspecialchars(($lang['EDIT'] ?? 'Edit') . ' ' . ($lang['PATIENT'] ?? 'Patient') . ' & ' . ($lang['ADMISSION'] ?? 'Admission')) ?></h2>
            <b>CRN: <?= htmlspecialchars($patient_id) ?> – <?= htmlspecialchars($patient_name) ?></b>
        </div>
    </div>

    <div class="btns">
        <a id="edit-patient-return"
           href="viewpatient.php?<?= http_build_query(array_filter([
               'patient_id' => (int)$patient_id,
               'msg' => trim((string)($_GET['msg'] ?? '')),
               'error' => trim((string)($_GET['error'] ?? '')),
           ], static fn($value) => $value !== '')) ?>"
           class="btn grey">
            <?= htmlspecialchars($lang['CANCEL'] ?? 'Cancel') ?>
        </a>
    </div>
</div>

<!-- ========================================================= -->
<!-- SECTION NAVIGATION (EDIT MODE)                             -->
<!-- ========================================================= -->
<div class="rc-tabs rc-tabs-pill editpatient-tabs">
    <a href="editpatient.php?patient_id=<?= $patient_id ?>&sid=1" class="rc-tab <?= $sid===1?'is-active':'' ?>"><?= htmlspecialchars($lang['PATIENT'] ?? 'Patient') ?></a>
    <a href="editpatient.php?patient_id=<?= $patient_id ?>&sid=2" class="rc-tab <?= $sid===2?'is-active':'' ?>"><?= htmlspecialchars($lang['ADMISSION'] ?? 'Admission') ?></a>
    <a href="editpatient.php?patient_id=<?= $patient_id ?>&sid=3" class="rc-tab <?= $sid===3?'is-active':'' ?>"><?= htmlspecialchars(($lang['FINDER'] ?? 'Finder') . ' / ' . ($lang['COLLECTION'] ?? 'Collection')) ?></a>
    <a href="editpatient.php?patient_id=<?= $patient_id ?>&sid=4" class="rc-tab <?= $sid===4?'is-active':'' ?>"><?= htmlspecialchars($lang['BIOMETRICS'] ?? 'Biometrics') ?></a>
    <a href="editpatient.php?patient_id=<?= $patient_id ?>&sid=5" class="rc-tab <?= $sid===5?'is-active':'' ?>"><?= htmlspecialchars($lang['TRIAGE'] ?? 'Triage') ?></a>
    <a href="editpatient.php?patient_id=<?= $patient_id ?>&sid=6" class="rc-tab <?= $sid===6?'is-active':'' ?>"><?= htmlspecialchars($lang['WEATHER'] ?? 'Weather') ?></a>
    <a href="editpatient.php?patient_id=<?= $patient_id ?>&sid=8" class="rc-tab <?= $sid===8?'is-active':'' ?>"><?= htmlspecialchars($lang['DISCHARGE'] ?? 'Discharge') ?></a>
</div>

<div id="edit-save-feedback-wrap"></div>


<!-- ========================================================= -->
<!-- MAIN EDIT CONTENT                                         -->
<!-- ========================================================= -->
<div class="rc-panel">

    <div class="xform rc-stack">

        <?php
        // --------------------------------------------------------
        // LOAD SECTION FILE (REUSED)
        // --------------------------------------------------------
        $sectionFile = __DIR__ . '/controllers/admissions/section' . $sid . '.php';

        if (file_exists($sectionFile)) {
            include $sectionFile;
        } else {
            echo '<div class="rc-alert amber">' . htmlspecialchars(($lang['SECTION'] ?? 'Section') . ' ' . strtolower($lang['UNAVAILABLE'] ?? 'unavailable') . '.') . '</div>';
        }
        ?>

    </div>

</div>

<!-- ========================================================= -->
<!-- SHARED SAVE STATUS + saveSection()                         -->
<!-- ========================================================= -->
<script src="core/js/admission-required.js?v=<?= filemtime(__DIR__ . '/core/js/admission-required.js') ?>"></script>
<script>
function updatePatientReturnFeedback(msg, isError = false) {
    try {
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.delete(isError ? 'msg' : 'error');
        currentUrl.searchParams.set(isError ? 'error' : 'msg', msg);
        window.history.replaceState({}, '', currentUrl.toString());

        const link = document.getElementById('edit-patient-return');
        if (link) {
            const returnUrl = new URL(link.href, window.location.href);
            returnUrl.searchParams.delete(isError ? 'msg' : 'error');
            returnUrl.searchParams.set(isError ? 'error' : 'msg', msg);
            link.href = returnUrl.toString();
        }

        document.querySelectorAll('.editpatient-tabs a').forEach(tab => {
            const tabUrl = new URL(tab.href, window.location.href);
            tabUrl.searchParams.delete(isError ? 'msg' : 'error');
            tabUrl.searchParams.set(isError ? 'error' : 'msg', msg);
            tab.href = tabUrl.toString();
        });
    } catch (error) {
        console.error('Could not preserve edit feedback.', error);
    }
}

function showSaveStatus(msg, isError = false) {
    const wrap = document.getElementById('edit-save-feedback-wrap');
    if (!wrap) return;

    let el = document.getElementById('edit-save-feedback');
    if (!el) {
        el = document.createElement('div');
        el.id = 'edit-save-feedback';
        wrap.appendChild(el);
    }

    el.className = 'rc-alert ' + (isError ? 'red' : 'green');
    el.textContent = msg;
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    updatePatientReturnFeedback(msg, isError);
}

// ------------------------------------------------------------
// Reuse existing save mechanism
// ------------------------------------------------------------
function saveSection(sectionId, formId) {

    const form = document.getElementById(formId);
    if (!form) {
        showSaveStatus(<?= json_encode(($lang['FORM_NOT_FOUND'] ?? 'Form not found') . '.') ?>, true);
        return;
    }

    const fd = new FormData(form);
    fd.set('sid', sectionId);

    const statusWrap = document.getElementById('edit-save-feedback-wrap');
    if (statusWrap) {
        let status = document.getElementById('edit-save-feedback');
        if (!status) {
            status = document.createElement('div');
            status.id = 'edit-save-feedback';
            statusWrap.appendChild(status);
        }
        status.className = 'rc-alert blue';
        status.textContent = <?= json_encode(($lang['SAVING'] ?? 'Saving') . ' ' . strtolower($lang['CHANGES'] ?? 'changes') . '...') ?>;
    }

    fetch('controllers/admissions/save_section.php', {
        method: 'POST',
        body: fd
    })
    .then(async response => {
        const text = await response.text();
        let data;

        try {
            data = JSON.parse(text);
        } catch (error) {
            throw new Error(<?= json_encode($lang['ADM_INVALID_SERVER_RESPONSE'] ?? 'The server returned an invalid response.') ?>);
        }

        if (!response.ok && (!data || !data.message)) {
            throw new Error(<?= json_encode($lang['ADM_SAVE_FAILED_STATUS'] ?? 'Save failed with status %s.') ?>.replace('%s', response.status));
        }

        return data;
    })
    .then(data => {

        if (!data || data.success !== true) {
            showSaveStatus((data && data.message) ? data.message : <?= json_encode($lang['ADM_SAVE_FAILED'] ?? 'Save failed.') ?>, true);
            return;
        }

        // Use the server message if present (e.g. "Section 2 saved")
        showSaveStatus(data.message ? data.message : <?= json_encode(($lang['CHANGES'] ?? 'Changes') . ' ' . strtolower($lang['SAVED'] ?? 'saved') . '.') ?>, false);

        // If a new admission_id gets created, keep URL + hidden fields in sync
        if (data.admission_id) {
            const hiddenAid = form.querySelector('input[name="admission_id"]');
            if (hiddenAid) hiddenAid.value = data.admission_id;

            const url = new URL(window.location.href);
            url.searchParams.set('aid', data.admission_id);
            window.history.replaceState({}, '', url.toString());
        }

        if (data.patient_id) {
            const url = new URL(window.location.href);
            url.searchParams.set('patient_id', data.patient_id);
            window.history.replaceState({}, '', url.toString());
        }
    })
    .catch(error => {
        showSaveStatus(error && error.message ? error.message : <?= json_encode($lang['ADM_NETWORK_SAVE_FAILED'] ?? 'Network error while saving.') ?>, true);
    });
}

(function preserveExistingEditFeedback() {
    const link = document.getElementById('edit-patient-return');
    if (!link) return;

    const url = new URL(link.href, window.location.href);
    const error = url.searchParams.get('error');
    const msg = url.searchParams.get('msg');

    if (error) {
        updatePatientReturnFeedback(error, true);
    } else if (msg) {
        updatePatientReturnFeedback(msg, false);
    }
})();
</script>
<script>
(function () {
    const tabs = document.querySelectorAll('.editpatient-tabs a');
    if (!tabs.length) return;

    tabs.forEach(a => {
        // capture phase so we run before any global tab handler
        a.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (e.stopImmediatePropagation) e.stopImmediatePropagation();
            window.location.href = this.href;
        }, true);
    });
})();
</script>


<?= template_admin_footer() ?>
