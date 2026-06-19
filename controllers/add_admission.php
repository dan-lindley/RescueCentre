<?php
// controllers/add_admission.php
// --------------------------------------
// MASTER WRAPPER for multi-step admission
// Controls sections S1–S6 via ?sid=1..6
// --------------------------------------

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../getuserinfo.php';
require_once __DIR__ . '/../getcentreinfo.php';

// ------------------------------------------------------------
// READ ROUTING PARAMS
// ------------------------------------------------------------
$sid = isset($_GET['sid']) ? (int)$_GET['sid'] : 1;
if ($sid < 1 || $sid > 7) $sid = 1;

$pid = isset($_GET['pid']) ? (int)$_GET['pid'] : null;
$aid = isset($_GET['aid']) ? (int)$_GET['aid'] : null;

// ------------------------------------------------------------
// LOAD PATIENT
// ------------------------------------------------------------
$patient = null;
if ($pid) {
    $stmt = $pdo->prepare("SELECT * FROM rescue_patients WHERE patient_id = :pid");
    $stmt->execute([':pid' => $pid]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ------------------------------------------------------------
// LOAD ADMISSION
// ------------------------------------------------------------
$admission = null;
if ($aid) {
    $stmt = $pdo->prepare("SELECT * FROM rescue_admissions WHERE admission_id = :aid");
    $stmt->execute([':aid' => $aid]);
    $admission = $stmt->fetch(PDO::FETCH_ASSOC);
}

$incompleteMeta = [];
$markedComplete = (isset($incompleteMeta['marked_complete']) && is_array($incompleteMeta['marked_complete']))
    ? $incompleteMeta['marked_complete']
    : [];



// ------------------------------------------------------------
// STAGE DEFINITIONS
// ------------------------------------------------------------
$stages = [
    1 => ['label' => ($lang['ANIMAL'] ?? 'Animal') . ' ' . ($lang['DETAILS'] ?? 'Details'), 'short' => 'S1'],
    2 => ['label' => $lang['ADMISSION'] ?? 'Admission', 'short' => 'S2'],
    3 => ['label' => ($lang['COLLECTION'] ?? 'Collection') . ' ' . ($lang['INFORMATION'] ?? 'Information'), 'short' => 'S3'],
    4 => ['label' => $lang['BIOMETRICS'] ?? 'Biometrics', 'short' => 'S4'],
    5 => ['label' => ($lang['TRIAGE'] ?? 'Triage') . ' & ' . ($lang['ASSESSMENT'] ?? 'Assessment'), 'short' => 'S5'],
    6 => ['label' => ($lang['WEATHER'] ?? 'Weather') . ' ' . ($lang['DATA'] ?? 'Data'), 'short' => 'S6'],
    7 => ['label' => $lang['DECLARATION'] ?? 'Declaration', 'short' => 'S7'],
];

// ------------------------------------------------------------
// COMPLETENESS CALC FOR ALL SECTIONS
// ------------------------------------------------------------
$stageCompletion = [];
foreach ($stages as $k => $meta) {
    $stageCompletion[$k] = [
        'completed' => 0,
        'total'     => 0,
    ];
}

/* -----------------------------
   SECTION 1 — animal details
   Name, Sex, Approx DOB, Species
   + ring/microchip numbers if Yes
----------------------------- */
if ($patient) {
    $completed = 0;
    $total     = 0;

    // Always count these four fields in the total
    $fields = ['name', 'sex', 'approx_dob', 'animal_species'];
    $total  = count($fields);

    // Name: treat "Not completed" as not done
    if (!empty($patient['name']) && $patient['name'] !== 'Not completed') {
        $completed++;
    }

    // Sex
    if (!empty($patient['sex'])) {
        $completed++;
    }

    // Approx DOB
    if (!empty($patient['approx_dob'])) {
        $completed++;
    }

    // Species
    if (!empty($patient['animal_species'])) {
        $completed++;
    }

    // Ring number only if ringed = Yes
    if (isset($patient['ringed']) && $patient['ringed'] === 'Yes') {
        $total++;
        if (!empty($patient['ring_number'])) {
            $completed++;
        }
    }

    // Microchip number only if microchipped = Yes
    if (isset($patient['microchipped']) && $patient['microchipped'] === 'Yes') {
        $total++;
        if (!empty($patient['microchip_number'])) {
            $completed++;
        }
    }

    $stageCompletion[1] = [
        'completed' => $completed,
        'total'     => $total,
    ];
}

/* =================================================================
   ADMISSION-BASED SECTIONS (2–6) – only if we have an admission row
   ================================================================= */
if ($admission) {

    /* -----------------------------
       SECTION 2 — admission basics
       Fields (4 x 25%):
       - admission_date
       - time_to_admission
       - current_location
       - disposition
       (status is derived and excluded)
    ----------------------------- */
$req = ['admission_date','time_to_admission','current_location','disposition'];
$done = 0;
$total = count($req);

foreach ($req as $f) {
    if (!empty($admission[$f])) {
        $done++;
    }
}

// MARK COMPLETE OVERRIDE (SECTION 2)
// If section was explicitly marked complete, force 100%
if (!empty($admission['incomplete_fields'])) {
    $meta = json_decode($admission['incomplete_fields'], true);

    if (is_array($meta) && !empty($meta['marked_complete'][2])) {
        $done = $total;
    }
}

$stageCompletion[2] = [
    'completed' => $done,
    'total'     => $total
];

/* -----------------------------
   SECTION 3 — collection data
----------------------------- */
$req = [
    'collection_location',
    'location_lat',
    'location_long',
    'finder_name',
    'finder_tel',
    'consent_to_update',
    'passphrase'
];

$done  = 0;
$total = count($req);

foreach ($req as $f) {
    if ($f === 'consent_to_update') {
        if (isset($admission[$f])) $done++;
    } else {
        if (!empty($admission[$f])) $done++;
    }
}

// MARK COMPLETE OVERRIDE (SECTION 3)
if (!empty($admission['incomplete_fields'])) {
    $meta = json_decode($admission['incomplete_fields'], true);
    if (is_array($meta) && !empty($meta['marked_complete'][3])) {
        $done = $total;
    }
}

$stageCompletion[3] = [
    'completed' => $done,
    'total'     => $total
];

/* -----------------------------
   SECTION 4 — biometrics
----------------------------- */
$req = [
    'age_on_admission',
    'dehydrated',
    'starved',
    'weight',
    'weight_unit',
    'measurement',
    'measurement_unit'
];

$done  = 0;
$total = count($req);

foreach ($req as $f) {
    if (isset($admission[$f]) && $admission[$f] !== '') {
        $done++;
    }
}

// MARK COMPLETE OVERRIDE (SECTION 4)
if (!empty($admission['incomplete_fields'])) {
    $meta = json_decode($admission['incomplete_fields'], true);
    if (is_array($meta) && !empty($meta['marked_complete'][4])) {
        $done = $total;
    }
}

$stageCompletion[4] = [
    'completed' => $done,
    'total'     => $total
];
/* -----------------------------
   SECTION 5 — triage
----------------------------- */

$req = ['ss_text','bcs_text','presenting_complaint','hpc','on_examination'];

$done  = 0;
$total = count($req);

foreach ($req as $f) {
    if (!empty($admission[$f])) $done++;
}

// MARK COMPLETE OVERRIDE (SECTION 5) — SAME PATTERN AS SECTION 3
if (!empty($admission['incomplete_fields'])) {
    $meta = json_decode($admission['incomplete_fields'], true);
    if (is_array($meta) && !empty($meta['marked_complete'][5])) {
        $done = $total;
    }
}

$stageCompletion[5] = [
    'completed' => $done,
    'total'     => $total
];

/*
|--------------------------------------------------
| CRITICAL: MARK COMPLETE OVERRIDE (MATCHES S2–S4)
|--------------------------------------------------
*/
if (!empty($incompleteMeta['marked_complete'][5])) {
    $done = $total;
}

$stageCompletion[5] = [
    'completed' => $done,
    'total'     => $total
];


/* -----------------------------
   SECTION 6 — optional weather
----------------------------- */
$req = ['w_temp','w_wind','w_humidity','w_freetext'];

$done  = 0;
$total = count($req);

foreach ($req as $f) {
    if (!empty($admission[$f])) $done++;
}

// MARK COMPLETE OVERRIDE (SECTION 6) — SAME PATTERN AS SECTION 3 & 5
if (!empty($admission['incomplete_fields'])) {
    $meta = json_decode($admission['incomplete_fields'], true);
    if (is_array($meta) && !empty($meta['marked_complete'][6])) {
        $done = $total;
    }
}

$stageCompletion[6] = [
    'completed' => $done,
    'total'     => $total
];


   // -----------------------------
// SECTION 7 — declaration
// -----------------------------
if ($aid && $pid) {
    $stageCompletion[7] = ['completed' => 0, 'total' => 1];

    try {
        $stmt = $pdo->prepare("
            SELECT signature_data, refused
            FROM rescue_signatures
            WHERE admission_id = :aid
              AND patient_id   = :pid
            LIMIT 1
        ");
        $stmt->execute([
            ':aid' => $aid,
            ':pid' => $pid
        ]);
        $sigRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($sigRow) {
            $hasSig   = !empty($sigRow['signature_data']);
            $hasRef   = !empty($sigRow['refused']) && (int)$sigRow['refused'] === 1;

            // Either a stored signature OR a recorded refusal counts as complete
            if ($hasSig || $hasRef) {
                $stageCompletion[7]['completed'] = 1;
            }
        }
    } catch (Exception $e) {
        // if query fails, leave section 7 as 0/1
    }
}


}
?>

<div class="content admission-page">
    <div class="rc-stage-shell">
        <aside class="content-block rc-stage-nav admission-stage-nav">
                    <ul class="rc-stage-list">

                    <?php foreach ($stages as $id => $meta):

                        $comp  = $stageCompletion[$id];
                        $total = $comp['total'];
                        $done  = $comp['completed'];

                        $percent = ($total > 0) ? round($done / $total * 100) : 0;

// HARD OVERRIDE FOR MARKED COMPLETE (force the underlying numbers too)
if (!empty($markedComplete[$id]) && $total > 0) {
    $done    = $total;
    $percent = 100;
}



                        if ($sid === $id) {
                            $class = 'is-active';
                            $icon  = '●';
                        } elseif ($total > 0 && $done >= $total) {
                            $class = 'is-done';
                            $icon  = '✓';
                        } else {
                            $class = '';
                            $icon  = '○';
                        }

                        $url = 'admission.php?sid=' . $id;
                        if ($pid) $url .= '&pid=' . $pid;
                        if ($aid) $url .= '&aid=' . $aid;

                    ?>
                        <li>
                            <a class="rc-stage-link <?= $class ?>" href="<?= htmlspecialchars($url) ?>">
                                <span class="rc-stage-index"><?= htmlspecialchars($meta['short']) ?></span>
                                <span class="rc-stage-label"><?= htmlspecialchars($meta['label']) ?></span>
                                <span class="rc-stage-meta"><?= $percent ?>%</span>
                            </a>
                        </li>
                    <?php endforeach; ?>

                    </ul>

                    <?php
    // Check if all sections are complete
  $allComplete = true;
foreach ($stageCompletion as $sec => $meta) {
    if ($meta['total'] <= 0) continue;

    $isMarked = !empty($markedComplete[$sec]);
    if (!$isMarked && $meta['completed'] < $meta['total']) {
        $allComplete = false;
        break;
    }
}


    // Only show button if we have patient + admission + all 100%
    if ($pid && $aid && $allComplete):
?>
    <button class="btn green" id="admitPatientBtn">
        <?= htmlspecialchars(($lang['ADMIT'] ?? 'Admit') . ' ' . ($lang['PATIENT'] ?? 'Patient')) ?>
    </button>

    <script>
    document.getElementById('admitPatientBtn').addEventListener('click', function() {

        if (!confirm(<?= json_encode($lang['ADM_CONFIRM_ADMIT'] ?? 'Are you sure you want to admit this patient?') ?>)) return;

        const fd = new FormData();
        fd.append('sid', 99); // special action
        fd.append('patient_id', "<?= $pid ?>");
        fd.append('admission_id', "<?= $aid ?>");

        fetch('controllers/admissions/save_section.php', {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(<?= json_encode($lang['ADM_PATIENT_ADMITTED'] ?? 'Patient has been admitted.') ?>);
               window.location.href = "patients.php";
            } else {
                alert(<?= json_encode(($lang['ERROR'] ?? 'Error') . ':') ?> + ' ' + (data.message || <?= json_encode($lang['ADM_UNABLE_TO_ADMIT'] ?? 'Unable to admit patient.') ?>));
            }
        })
        .catch(() => alert(<?= json_encode(($lang['NETWORK_ERROR'] ?? 'Network error') . '.') ?>));
    });
    </script>

<?php endif; ?>

        </aside>

        <div class="rc-main-panel">
            <div id="save-status" class="alert-box alert-green"></div>
            <section class="content-block">

                <?php
                $sectionFile = __DIR__ . '/admissions/section' . $sid . '.php';
                if (file_exists($sectionFile)) {
                    include $sectionFile;
                } else {
                    echo '<p>' . htmlspecialchars(sprintf($lang['ADM_SECTION_NOT_IMPLEMENTED'] ?? 'Section %s not yet implemented.', $sid)) . '</p>';
                }
                ?>
            </section>
        </div>
    </div>
    </div>

<script src="core/js/admission-required.js?v=<?= filemtime(__DIR__ . '/../core/js/admission-required.js') ?>"></script>
<script>
function showSaveStatus(msg, isError = false) {
    const el = document.getElementById('save-status');
    if (!el) return;
    el.textContent = msg;
    el.className = isError ? 'alert-box alert-red' : 'alert-box alert-green';
    el.style.display = 'block';
    setTimeout(() => el.style.display = 'none', 4000);
}

// ===============================================
// GLOBAL saveSection() USED BY ALL SECTIONS
// ===============================================
function saveSection(sectionId, formId) {

    const form = document.getElementById(formId);
    if (!form) {
        showSaveStatus(<?= json_encode(($lang['FORM_NOT_FOUND'] ?? 'Form not found') . ':') ?> + ' ' + formId, true);
        return;
    }

    const fd = new FormData(form);
    fd.set('sid', sectionId);

    const pid = form.querySelector('[name="patient_id"]')?.value || '';
    const aid = form.querySelector('[name="admission_id"]')?.value || '';

    function submitSave(formData) {
        return fetch('controllers/admissions/save_section.php', {
            method: 'POST',
            body: formData
        }).then(r => r.json());
    }

    submitSave(fd)
    .then(data => {

        if (data?.duplicate_partial === true) {
            const dup = data.duplicate || {};
            const details = [
                dup.name ? <?= json_encode($lang['NAME'] ?? 'Name:') ?> + ' ' + dup.name : '',
                dup.animal_species ? <?= json_encode($lang['SPECIES'] ?? 'Species:') ?> + ' ' + dup.animal_species : '',
                dup.patient_id ? <?= json_encode(($lang['PAT_CRN'] ?? 'CRN') . ':') ?> + ' ' + dup.patient_id : ''
            ].filter(Boolean).join('\n');

            const useExisting = confirm(
                <?= json_encode($lang['ADM_DUPLICATE_PROMPT'] ?? "A partial admission already exists for this patient.\n\n%s\n\nOK = continue the existing partial admission.\nCancel = create a new separate admission.") ?>.replace('%s', details)
            );

            if (useExisting) {
                let url = 'admission.php?sid=' + (dup.admission_id ? '2' : '1');
                if (dup.patient_id) url += '&pid=' + encodeURIComponent(dup.patient_id);
                if (dup.admission_id) url += '&aid=' + encodeURIComponent(dup.admission_id);
                window.location.href = url;
                return;
            }

            fd.set('force_new', '1');
            showSaveStatus(<?= json_encode($lang['ADM_CREATING_SEPARATE'] ?? 'Creating a new separate admission...') ?>, false);

            submitSave(fd)
                .then(handleSaveResponse)
                .catch(err => {
                    console.error(err);
                    showSaveStatus(<?= json_encode($lang['ADM_NETWORK_SAVE_ERROR'] ?? 'Network/JS error while saving.') ?>, true);
                });
            return;
        }

        handleSaveResponse(data);
    })
    .catch(err => {
        console.error(err);
        showSaveStatus(<?= json_encode($lang['ADM_NETWORK_SAVE_ERROR'] ?? 'Network/JS error while saving.') ?>, true);
    });

    function handleSaveResponse(data) {
        if (!data || data.success !== true) {
            showSaveStatus(
                data?.message || <?= json_encode($lang['ADM_ERROR_SAVING_SECTION'] ?? 'Error saving this section.') ?>,
                true
            );
            console.error("Save error:", data);
            return;
        }

        let newPid = data.patient_id   || pid || '';
        let newAid = data.admission_id || aid || '';

        let url = 'admission.php?sid=' + sectionId;
        if (newPid) url += '&pid=' + encodeURIComponent(newPid);
        if (newAid) url += '&aid' + '=' + encodeURIComponent(newAid);

        showSaveStatus(<?= json_encode(($lang['SECTION'] ?? 'Section') . ' ' . strtolower($lang['SAVED'] ?? 'saved') . '...') ?>, false);

        setTimeout(() => {
            window.location.href = url;
        }, 300);
    }

    /*
    fetch('controllers/admissions/save_section.php', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(data => {

        if (!data || data.success !== true) {
            showSaveStatus(
                data?.message || "Error saving this section.",
                true
            );
            console.error("Save error:", data);
            return;
        }

        let newPid = data.patient_id   || pid || '';
        let newAid = data.admission_id || aid || '';

        let url = 'admission.php?sid=' + sectionId;
        if (newPid) url += '&pid=' + encodeURIComponent(newPid);
        if (newAid) url += '&aid' + '=' + encodeURIComponent(newAid);

        showSaveStatus("Section saved…", false);

        setTimeout(() => {
            window.location.href = url;
        }, 300);
    })
    .catch(err => {
        console.error(err);
        showSaveStatus("Network/JS error while saving.", true);
    });
    */
}
</script>

