<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$centre_id_int = isset($centre_id) ? (int)$centre_id : 0;
if ($centre_id_int <= 0) {
    echo '<div class="rc-alert red">' . htmlspecialchars($lang['LOC_CENTRE_ID_MISSING']) . '</div>';
    return;
}

/* -----------------------------
   Filter flag
   Default: only where current_location_id NOT stored
------------------------------ */
$show_all = isset($_GET['show_all_locations']) && $_GET['show_all_locations'] === '1';

/* -----------------------------
   Load locations
------------------------------ */
$locStmt = $pdo->prepare("
    SELECT location_id, location_area, location_name
    FROM rescue_locations
    WHERE centre_id = :cid
    ORDER BY
        CASE WHEN location_area IS NULL OR location_area='' THEN 1 ELSE 0 END,
        location_area ASC,
        location_name ASC
");
$locStmt->execute([':cid' => $centre_id_int]);
$locations = $locStmt->fetchAll(PDO::FETCH_ASSOC);

$nameCounts = [];
foreach ($locations as $l) {
    $n = trim((string)$l['location_name']);
    if ($n === '') continue;
    $k = mb_strtolower($n);
    $nameCounts[$k] = ($nameCounts[$k] ?? 0) + 1;
}
$uniqueNameToId = [];
foreach ($locations as $l) {
    $n = trim((string)$l['location_name']);
    if ($n === '') continue;
    $k = mb_strtolower($n);
    if (($nameCounts[$k] ?? 0) === 1) {
        $uniqueNameToId[$k] = (int)$l['location_id'];
    }
}

$groupedLocations = [];
foreach ($locations as $loc) {
    $area = trim((string)($loc['location_area'] ?? ''));
    if ($area === '') $area = $lang['PAT_UNASSIGNED'] ?? 'Unassigned';
    $groupedLocations[$area][] = $loc;
}

/* -----------------------------
   POST update
------------------------------ */
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rc_loc_batch_update'])) {

    $apply  = $_POST['apply'] ?? [];
    $chosen = $_POST['location_id'] ?? [];

    $validLocIds = [];
    foreach ($locations as $l) {
        $validLocIds[(int)$l['location_id']] = true;
    }

    $updated = 0;
    $skipped = 0;

    $upd = $pdo->prepare("
        UPDATE rescue_admissions
        SET current_location_id = :loc_id
        WHERE admission_id = :admission_id
        LIMIT 1
    ");

    $belongsStmt = $pdo->prepare("
        SELECT 1
        FROM rescue_admissions ra
        JOIN rescue_patients rp ON rp.patient_id = ra.patient_id
        WHERE ra.admission_id = :aid
          AND rp.centre_id = :cid
        LIMIT 1
    ");

    foreach ($apply as $admissionIdStr => $on) {
        $admission_id = (int)$admissionIdStr;
        $loc_id = isset($chosen[$admissionIdStr]) ? (int)$chosen[$admissionIdStr] : 0;

        if ($admission_id <= 0 || $loc_id <= 0) { $skipped++; continue; }
        if (!isset($validLocIds[$loc_id])) { $skipped++; continue; }

        $belongsStmt->execute([':aid' => $admission_id, ':cid' => $centre_id_int]);
        if (!$belongsStmt->fetchColumn()) { $skipped++; continue; }

        $upd->execute([':loc_id' => $loc_id, ':admission_id' => $admission_id]);
        $updated += ($upd->rowCount() > 0) ? 1 : 0;
    }

    $flash = [
        'type' => 'success',
        'msg'  => sprintf($lang['LOC_UPDATED_SKIPPED'], $updated, $skipped)
    ];
}

/* -----------------------------
   Load current in-patients
------------------------------ */
$whereExtra = $show_all ? "" : " AND (ra.current_location_id IS NULL OR ra.current_location_id = 0) ";

$sql = "
    SELECT
        ra.admission_id,
        ra.patient_id AS crn,
        ra.presenting_complaint,
        ra.current_location,
        ra.collection_location,
        ra.current_location_id,
        rp.name AS animal_name,
        rp.animal_species
    FROM rescue_admissions ra
    JOIN rescue_patients rp ON rp.patient_id = ra.patient_id
    WHERE rp.centre_id = :cid
      AND rp.state = 'Admitted'
      AND ra.disposition = 'Held in captivity'
      {$whereExtra}
    ORDER BY ra.admission_date DESC, ra.patient_id DESC
";

$patStmt = $pdo->prepare($sql);
$patStmt->execute([':cid' => $centre_id_int]);
$rows = $patStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as &$r) {
    $default = (int)($r['current_location_id'] ?? 0);

    if ($default <= 0) {
        $txt = trim((string)($r['current_location'] ?? ''));
        if ($txt !== '') {
            $k = mb_strtolower($txt);
            if (isset($uniqueNameToId[$k])) $default = (int)$uniqueNameToId[$k];
        }
    }
    $r['_default_location_id'] = $default;
}
unset($r);
?>

<style>
#rcLocBatchTool { max-width: 100%; }
#rcLocBatchTool .rc-contained { overflow-x: auto; width: 100%; }
#rcLocBatchTool table { width: 100%; table-layout: fixed; }
#rcLocBatchTool td, #rcLocBatchTool th { vertical-align: middle; padding: .35rem .5rem; }
#rcLocBatchTool .rc-checkcell { width: 58px; text-align: center; }
#rcLocBatchTool .rc-patientcell { width: 260px; }
#rcLocBatchTool .rc-assigncell { width: 320px; }
#rcLocBatchTool .rc-strong { font-weight: 700; }
#rcLocBatchTool .rc-muted { font-size: 12px; }
#rcLocBatchTool .rc-line { font-size: 13px; line-height: 1.25; margin-top: 2px; }
#rcLocBatchTool .rc-k { color:#6b7280; font-weight: 600; margin-right: 6px; }
#rcLocBatchTool .rc-clip {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
#rcLocBatchTool tbody tr { border-bottom: 1px solid var(--rc-border); }
#rcLocBatchTool tbody tr:last-child { border-bottom: none; }
#rcLocBatchTool .rc-chip { margin-left: 8px; }
#rcLocBatchTool select.xform-input {
  height: calc(1.5em + .5rem + 2px);
  padding: .25rem .5rem;
}
</style>

<div class="rc-panel" id="rcLocBatchTool">
  <div class="rc-split-head">
    <div>
      <h6 class="m-0 font-weight-bold text-primary"><?= htmlspecialchars($lang['LOC_BATCH_ASSIGN_TITLE']) ?></h6>
      <div class="rc-muted" style="margin-top:6px;"><?= htmlspecialchars($lang['LOC_BATCH_ASSIGN_HELP']) ?></div>
    </div>
    <?php if ($flash): ?>
      <div class="rc-alert green" style="padding:6px 10px;"><?php echo htmlspecialchars($flash['msg']); ?></div>
    <?php endif; ?>
  </div>

  <div class="rc-stack">
    <div class="rc-split-head">
      <div class="xform-field" style="margin:0;">
        <input id="rcLocSearch" class="xform-input" placeholder="<?= htmlspecialchars($lang['LOC_SEARCH_BATCH_PLACEHOLDER']) ?>">
      </div>

      <div class="rc-actions" style="gap:14px;">
        <!-- Filter checkbox -->
        <label style="margin:0; display:flex; gap:8px; align-items:center; font-weight:normal;">
          <input type="checkbox" id="rcShowAll"
                 <?php echo $show_all ? 'checked' : ''; ?>
                 style="transform: translateY(1px);">
          <?= htmlspecialchars($lang['LOC_SHOW_ALL_ADMITTED']) ?>
        </label>

        <button type="button" class="btn btn-sm btn-secondary" id="rcSelectAll"><?= htmlspecialchars($lang['LOC_SELECT_ALL_VISIBLE']) ?></button>
        <button type="button" class="btn btn-sm btn-light" id="rcSelectNone"><?= htmlspecialchars($lang['LOC_SELECT_NONE_VISIBLE']) ?></button>
      </div>
    </div>

    <form method="post">
      <input type="hidden" name="rc_loc_batch_update" value="1">

      <div class="rc-contained">
        <table class="rc-table row-hover" id="rcLocBatchTable" cellspacing="0">
          <thead class="thead-light">
            <tr>
              <th class="rc-checkcell"><?= htmlspecialchars($lang['LOC_APPLY']) ?></th>
              <th class="rc-patientcell"><?= htmlspecialchars($lang['PATIENT']) ?></th>
              <th><?= htmlspecialchars($lang['DETAILS']) ?></th>
              <th class="rc-assigncell"><?= htmlspecialchars($lang['LOC_ASSIGN_LOCATION']) ?></th>
            </tr>
          </thead>

          <tbody>
          <?php foreach ($rows as $r): ?>
            <?php
              $admission_id = (int)$r['admission_id'];
              $crn = (int)$r['crn'];

              $animal_name = trim((string)($r['animal_name'] ?? ''));
              if ($animal_name === '') $animal_name = $lang['LOC_UNNAMED'];

              $species = trim((string)($r['animal_species'] ?? ''));
              if ($species === '') $species = $lang['LOC_UNKNOWN_SPECIES'];

              $presenting = trim((string)($r['presenting_complaint'] ?? ''));
              if ($presenting === '') $presenting = $lang['LOC_BLANK'];

              $collection_loc = trim((string)($r['collection_location'] ?? ''));
              if ($collection_loc === '') $collection_loc = $lang['LOC_BLANK'];

              $recorded = trim((string)($r['current_location'] ?? ''));
              if ($recorded === '') $recorded = $lang['LOC_BLANK'];

              $defaultLocId = (int)$r['_default_location_id'];

              $pill = 'warn'; $pillText = $lang['LOC_NO_MATCH'];
              if (!empty($r['current_location_id'])) { $pill='good'; $pillText=$lang['LOC_ID_SET']; }
              elseif ($defaultLocId > 0) { $pill='blue'; $pillText=$lang['LOC_TEXT_MATCH']; }
            ?>
            <tr class="rc-loc-row">
              <td class="rc-checkcell">
                <input type="checkbox" name="apply[<?php echo $admission_id; ?>]" value="1">
              </td>

              <td class="rc-patientcell">
                <div class="rc-strong">CRN: <?php echo $crn; ?></div>
                <div class="rc-line rc-clip"><?php echo htmlspecialchars($animal_name); ?></div>
                <div class="rc-muted rc-clip"><?php echo htmlspecialchars($species); ?> · Adm #<?php echo $admission_id; ?></div>
              </td>

              <td>
                <div class="rc-line rc-clip"><span class="rc-k"><?= htmlspecialchars($lang['LOC_COLLECTED_FROM']) ?>:</span><?php echo htmlspecialchars($collection_loc); ?></div>
                <div class="rc-line rc-clip"><span class="rc-k"><?= htmlspecialchars($lang['LOC_PRESENTING']) ?>:</span><?php echo htmlspecialchars($presenting); ?></div>
                <div class="rc-line rc-clip">
                  <span class="rc-k"><?= htmlspecialchars($lang['LOC_RECORDED']) ?>:</span><?php echo htmlspecialchars($recorded); ?>
                  <span class="rc-chip <?php echo $pill; ?>"><?php echo htmlspecialchars($pillText); ?></span>
                </div>
              </td>

              <td class="rc-assigncell">
                <select class="xform-input"
                        name="location_id[<?php echo $admission_id; ?>]">
                  <option value="0"><?= htmlspecialchars($lang['LOC_SELECT_DASH']) ?></option>
                  <?php foreach ($groupedLocations as $area => $locs): ?>
                    <optgroup label="<?php echo htmlspecialchars($area); ?>">
                      <?php foreach ($locs as $loc):
                        $locId = (int)$loc['location_id'];
                        $name = (string)$loc['location_name'];
                        $sel = ($defaultLocId === $locId) ? ' selected' : '';
                      ?>
                        <option value="<?php echo $locId; ?>"<?php echo $sel; ?>>
                          <?php echo htmlspecialchars($name); ?>
                        </option>
                      <?php endforeach; ?>
                    </optgroup>
                  <?php endforeach; ?>
                </select>
              </td>
            </tr>
          <?php endforeach; ?>

          <?php if (empty($rows)): ?>
            <tr><td colspan="4" class="text-center" style="color:#666;"><?= htmlspecialchars($lang['LOC_NO_ADMITTED_PATIENTS']) ?></td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div style="display:flex; justify-content:flex-end; margin-top:10px;">
        <button type="submit" class="btn btn-primary"><?= htmlspecialchars($lang['LOC_UPDATE_SELECTED']) ?></button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const search = document.getElementById('rcLocSearch');
  const table = document.getElementById('rcLocBatchTable');
  const selectAllBtn = document.getElementById('rcSelectAll');
  const selectNoneBtn = document.getElementById('rcSelectNone');
  const showAll = document.getElementById('rcShowAll');

  function getVisibleRows() {
    return Array.from(table.querySelectorAll('tbody tr.rc-loc-row'))
      .filter(tr => tr.style.display !== 'none');
  }

  search?.addEventListener('input', function () {
    const term = (this.value || '').trim().toLowerCase();
    table.querySelectorAll('tbody tr.rc-loc-row').forEach(tr => {
      tr.style.display = (!term || tr.textContent.toLowerCase().includes(term)) ? '' : 'none';
    });
  });

  selectAllBtn?.addEventListener('click', function () {
    getVisibleRows().forEach(tr => {
      const cb = tr.querySelector('input[type="checkbox"]');
      if (cb) cb.checked = true;
    });
  });

  selectNoneBtn?.addEventListener('click', function () {
    getVisibleRows().forEach(tr => {
      const cb = tr.querySelector('input[type="checkbox"]');
      if (cb) cb.checked = false;
    });
  });

  // Filter toggle: reload page with GET param
  showAll?.addEventListener('change', function () {
    const url = new URL(window.location.href);
    if (this.checked) {
      url.searchParams.set('show_all_locations', '1');
    } else {
      url.searchParams.delete('show_all_locations');
    }
    window.location.href = url.toString();
  });
});
</script>
