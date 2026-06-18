<?php

include __DIR__ . '/../controllers/meds_dependencies.php';

// ----------------------------------------------
// MEDICATIONS DUE TODAY - GROUPED BY AREA & TIME
// ----------------------------------------------

// Build a single query to fetch all active prescriptions
// for this centre, with area + time info.
$prescriptionSoftDeleteFilter = '';
try {
    $colStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'rescue_prescriptions'
          AND COLUMN_NAME = 'is_deleted'
    ");
    $colStmt->execute();
    if ((int)$colStmt->fetchColumn() > 0) {
        $prescriptionSoftDeleteFilter = " AND COALESCE(rp.is_deleted, 0) = 0";
    }
} catch (Throwable $e) {
    $prescriptionSoftDeleteFilter = '';
}

$medStmt = $pdo->prepare("
    SELECT 
        rp.prescription_id,
        rp.patient_id,
        rp.medication,
        rp.dose,
        rp.dose_type,
        rp.duration,
        rp.frequency,
        rp.route,
        rp.date AS prescription_date,

        rp.admission_id,
        p.name AS patient_name,
        a.current_location,
        l.location_area,
        ft.time AS freq_time
    FROM rescue_prescriptions rp
    JOIN rescue_patients p 
        ON p.patient_id = rp.patient_id
    JOIN rescue_admissions a 
        ON a.admission_id = rp.admission_id
    LEFT JOIN rescue_locations l 
        ON a.current_location = l.location_name
       AND l.centre_id = :centre_id
    LEFT JOIN rescue_frequency_times ft
        ON ft.frequency_name = rp.frequency
    WHERE 
        CURDATE() <= DATE_ADD(rp.date, INTERVAL rp.duration DAY)
        AND p.centre_id = :centre_id2
        {$prescriptionSoftDeleteFilter}
    ORDER BY 
        COALESCE(l.location_area, 'Unassigned') ASC,
        ft.time ASC,
        p.name ASC
");
$medStmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
$medStmt->bindParam(':centre_id2', $centre_id, PDO::PARAM_INT);
$medStmt->execute();

$rows = $medStmt->fetchAll(PDO::FETCH_ASSOC);

// Group by Area -> Time
$medBoard = [];

foreach ($rows as $row) {
    $area = $row['location_area'] ?: ($lang['PAT_UNASSIGNED'] ?? 'Unassigned');

    // time can be NULL; handle gracefully
    $timeKey = $row['freq_time'] ?: ($lang['MEDS_UNSCHEDULED'] ?? 'Unscheduled');

    if (!isset($medBoard[$area])) {
        $medBoard[$area] = [];
    }
    if (!isset($medBoard[$area][$timeKey])) {
        $medBoard[$area][$timeKey] = [];
    }
    $medBoard[$area][$timeKey][] = $row;
}

function roundLabel($time) {
    global $lang;

    if (!$time) return ($lang['MEDS_UNSCHEDULED'] ?? 'Unscheduled');

    $t = (int) str_replace(":", "", substr($time,0,5)); // “08:00” → 800

    return match(true) {
        $t >= 700 && $t < 1000  => ($lang['MEDS_ROUND_MORNING'] ?? 'Morning Round'),
        $t >= 1000 && $t < 1200 => ($lang['MEDS_ROUND_LATE_MORNING'] ?? 'Late Morning Round'),
        $t >= 1200 && $t < 1400 => ($lang['MEDS_ROUND_LUNCHTIME'] ?? 'Lunchtime Round'),
        $t >= 1400 && $t < 1600 => ($lang['MEDS_ROUND_EARLY_AFTERNOON'] ?? 'Early Afternoon Round'),
        $t >= 1600 && $t < 1800 => ($lang['MEDS_ROUND_TEATIME'] ?? 'Teatime Round'),
        $t >= 1800 && $t < 2359 => ($lang['MEDS_ROUND_NIGHT'] ?? 'Night Time Round'),
        default                 => ($lang['MEDS_UNSCHEDULED'] ?? 'Unscheduled')
    };
}

function safeKey($s) {
    $s = strtolower((string)$s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim($s, '-');
    return $s !== '' ? $s : 'na';
}

?>

<!-- MAIN CARD: ALL MEDICATIONS DUE TODAY -->

<?php if (empty($medBoard)): ?>

    <div class="rc-alert blue"><?= $lang['MEDS_NONE_TODAY'] ?? 'There are no medications scheduled for today.' ?></div>

<?php else: ?>

    <?php foreach ($medBoard as $areaName => $times): ?>
        <?php $areaKeySafe = safeKey($areaName); ?>

        <!-- AREA CARD -->
        <div class="rc-panel rc-stack">
            <h3>
                <?= $lang['MEDS_AREA_LABEL'] ?? 'Area:' ?> <?php echo htmlspecialchars($areaName); ?>
            </h3>
            <div class="rc-stack">

                <?php foreach ($times as $timeValue => $prescriptions): ?>

                    <?php
                        // Build a friendly time label
                        if ($timeValue === ($lang['MEDS_UNSCHEDULED'] ?? 'Unscheduled')) {
                            $timeLabel = ($lang['MEDS_UNSCHEDULED'] ?? 'Unscheduled');
                            $timeKeySafe = 'unscheduled';
                        } else {
                            // If time is stored as 'HH:MM:SS' or 'HH:MM'
                            $timeLabel = substr($timeValue, 0, 5); // '08:00'
                            $timeKeySafe = safeKey($timeLabel);
                        }

                        $roundLabelText = roundLabel($timeValue);
                        $roundKeySafe   = safeKey($roundLabelText);
                    ?>

                    <!-- TIME CARD INSIDE AREA -->
                    <div class="rc-card rc-stack">
                        <h4>
                            <?php echo htmlspecialchars($timeLabel); ?> —
                            <?php echo htmlspecialchars($roundLabelText); ?>
                        </h4>

                        <div>

                            <div class="rc-table-scroll">
                                <table class="rc-table row-hover" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th><?= $lang['MEDS_COL_TIME'] ?? ($lang['TIME'] ?? 'Time') ?></th>
                                            <th><?= $lang['PATIENT'] ?? 'Patient' ?></th>
                                            <th><?= $lang['LOCATION'] ?? 'Location' ?></th>
                                            <th><?= $lang['LM_MEDICATION'] ?? 'Medication' ?></th>
                                            <th><?= $lang['MEDS_COL_ROUTE'] ?? 'Route' ?></th>
                                            <th><?= $lang['MEDS_COL_DOSE'] ?? 'Dose' ?></th>
                                            <th><?= $lang['MEDS_COL_FREQUENCY'] ?? 'Frequency' ?></th>
                                            <th><?= $lang['ACTIONS'] ?? 'Actions' ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                    <?php foreach ($prescriptions as $p): ?>
                                        <?php
                                            $patient_id   = $p['patient_id'];
                                            $patient_name = $p['patient_name'];
                                            $medication   = $p['medication'];
                                            $dose         = $p['dose'];
                                            $dose_type    = $p['dose_type'];
                                            $route        = $p['route'];
                                            $frequency    = $p['frequency'];
                                            $location     = $p['current_location'];
                                            $time_raw     = $p['freq_time'];
                                            $presc_id     = $p['prescription_id'];

                                            $time_display = $time_raw ? substr($time_raw, 0, 5) : '';

                                            /**
                                             * IMPORTANT:
                                             * A single prescription can appear multiple times (e.g. BID => 08:00 and 20:00)
                                             * so IDs MUST include AREA + ROUND + TIME + PATIENT + PRESCRIPTION to stay unique.
                                             */
                                            $formRowId = "med-admin-{$areaKeySafe}-{$roundKeySafe}-{$timeKeySafe}-{$patient_id}-{$presc_id}";
                                        ?>

                                        <!-- MAIN PRESCRIPTION ROW -->
                                        <tr>
                                            <td><?php echo htmlspecialchars($time_display); ?></td>

                                            <td>
                                                <a href="https://rescuecentre.org.uk/view-patient/?patient_id=<?php echo (int)$patient_id; ?>">
                                                    <?php echo htmlspecialchars($patient_name); ?>
                                                </a>
                                            </td>

                                            <td><?php echo htmlspecialchars($location); ?></td>
                                            <td><?php echo htmlspecialchars($medication); ?></td>
                                            <td><?php echo ($lang['MEDS_BY'] ?? 'by') . ' ' . htmlspecialchars($route); ?></td>
                                            <td><?php echo htmlspecialchars($dose . ' ' . $dose_type); ?></td>
                                            <td><?php echo htmlspecialchars($frequency); ?></td>

                                            <td>
                                                <div class="rc-actions">
                                                    <!-- Care Plan -->
                                                    <a href="https://rescuecentre.org.uk/view-patient/?patient_id=<?php echo (int)$patient_id; ?>"
                                                       class="btn green"
                                                       title="<?= htmlspecialchars($lang['TIP_VIEW_CARE_PLAN'] ?? 'View Care Plan') ?>">
                                                        <?= $lang['BTN_CARE_PLAN'] ?? 'Care Plan' ?>
                                                    </a> &nbsp;

                                                    <!-- Administer Medication -->
                                                    <button type="button"
                                                            class="btn blue toggle-med-form"
                                                            data-target-id="<?php echo htmlspecialchars($formRowId); ?>"
                                                            title="<?= htmlspecialchars($lang['TIP_ADMINISTER_A_MEDICATION'] ?? 'Administer a Medication') ?>">
                                                        <!-- Syringe SVG icon -->
                                                        <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg"
                                                             viewBox="0 0 640 640" class="icon">
                                                            <path d="M529.5 47C520.1 37.6 504.9 37.6 495.6 47C486.3 56.4 486.2 71.6 495.6 80.9L510.6 95.9L464.5 142L401.5 79C392.1 69.6 376.9 69.6 367.6 79C358.3 88.4 358.2 103.6 367.6 112.9L374.6 119.9L296.5 198L337.5 239C346.9 248.4 346.9 263.6 337.5 272.9C328.1 282.2 312.9 282.3 303.6 272.9L262.6 231.9L216.5 278L257.5 319C266.9 328.4 266.9 343.6 257.5 352.9C248.1 362.2 232.9 362.3 223.6 352.9L182.6 311.9L144.9 349.6C134.4 360.1 128.5 374.3 128.5 389.2L128.5 478L71.5 535C62.1 544.4 62.1 559.6 71.5 568.9C80.9 578.2 96.1 578.3 105.4 568.9L162.4 511.9L251.2 511.9C266.1 511.9 280.3 506 290.8 495.5L520.5 265.8L527.5 272.8C536.9 282.2 552.1 282.2 561.4 272.8C570.7 263.4 570.8 248.2 561.4 238.9L498.4 175.9L544.5 129.8L559.5 144.8C568.9 154.2 584.1 154.2 593.4 144.8C602.7 135.4 602.8 120.2 593.4 110.9L529.4 46.9z"/>
                                                        </svg>
                                                        <?= $lang['MEDS_BTN_ADMINISTER'] ?? 'Administer' ?>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- INLINE ADMIN FORM ROW -->
                                        <tr id="<?php echo htmlspecialchars($formRowId); ?>" class="med-admin-form-row" style="display:none;">
                                            <td colspan="8">
                                                <div class="rc-card rc-card-muted">
                                                <?php
                                                    // Ensure $patient_id is available inside form
                                                    $inline_patient_id = $patient_id;
                                                    $patient_id = $inline_patient_id;

                                                    include __DIR__ . '/../controllers/add_medsadmin.php';
                                                ?>
                                                </div>
                                            </td>
                                        </tr>

                                    <?php endforeach; // prescriptions ?>

                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>

                <?php endforeach; // times ?>

            </div>
        </div>
    <?php endforeach; // areas ?>

<?php endif; ?>

<?php include __DIR__ . '/../controllers/medicationlist.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {

  const STORAGE_KEY = 'openMedAdminTarget';

  function setUrlTarget(targetId) {
    const url = new URL(window.location.href);
    url.searchParams.set('open', 'medication');
    url.searchParams.set('target', targetId);
    window.history.replaceState({}, '', url.toString());
  }

  function clearUrlTarget() {
    const url = new URL(window.location.href);
    url.searchParams.delete('open');
    url.searchParams.delete('target');
    window.history.replaceState({}, '', url.toString());
  }

  function closeAllRowsExcept(targetId) {
    document.querySelectorAll('.med-admin-form-row').forEach(function (r) {
      if (r.id !== targetId) r.style.display = 'none';
    });
  }

  function openRow(targetId) {
    const row = document.getElementById(targetId);
    if (!row) return false;

    closeAllRowsExcept(targetId);
    row.style.display = 'table-row';

    sessionStorage.setItem(STORAGE_KEY, targetId);
    setUrlTarget(targetId);

    if (typeof attachAutocomplete === 'function') attachAutocomplete();
    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
    return true;
  }

  function restoreOpenTarget() {
    const params = new URLSearchParams(window.location.search);
    const urlTarget = (params.get('open') === 'medication' && params.get('target')) ? params.get('target') : null;
    const targetId = urlTarget || sessionStorage.getItem(STORAGE_KEY);
    if (!targetId) return;

    let tries = 0;
    const timer = setInterval(function () {
      tries++;
      if (openRow(targetId)) {
        clearInterval(timer);
      } else if (tries >= 25) {
        clearInterval(timer);
      }
    }, 150);
  }

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.toggle-med-form');
    if (!btn) return;

    e.preventDefault();

    const targetId = btn.getAttribute('data-target-id');
    if (!targetId) return;

    const row = document.getElementById(targetId);
    if (!row) return;

    const isOpen = (row.style.display === 'table-row');

    if (isOpen) {
      row.style.display = 'none';
      sessionStorage.removeItem(STORAGE_KEY);
      clearUrlTarget();
    } else {
      openRow(targetId);
    }
  });

  restoreOpenTarget();

});
</script>
