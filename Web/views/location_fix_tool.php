<?php
// views/location_fix_tool.php
// Assumes $pdo and $centre_id exist (same as dashboard context).

if (!isset($pdo) || !isset($centre_id)) {
    echo '<div class="rc-alert red"><strong>' . htmlspecialchars(($lang['LOCATION'] ?? 'Location') . ' ' . ($lang['LOC_FIX'] ?? 'Fix'), ENT_QUOTES, 'UTF-8') . '</strong><br>' . htmlspecialchars($lang['DATA_CONTEXT_MISSING'] ?? 'Missing context.', ENT_QUOTES, 'UTF-8') . '</div>';
    return;
}

$sql = "
    SELECT 
        ra.patient_id,
        ra.admission_date,
        ra.location_lat,
        ra.location_long,
        ra.collection_location,
        rp.name,
        rp.animal_species
    FROM rescue_admissions ra
    INNER JOIN rescue_patients rp ON ra.patient_id = rp.patient_id
    WHERE 
        ra.centre_id = :centre_id
        AND ra.location_lat IS NOT NULL
        AND ra.location_long IS NOT NULL
        AND ra.location_lat <> ''
        AND ra.location_long <> ''
        AND ra.admission_date >= DATE_SUB(CURDATE(), INTERVAL 3 YEAR)
    ORDER BY ra.admission_date DESC
";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
$stmt->execute();
$admissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$admissions_json = json_encode($admissions, JSON_UNESCAPED_UNICODE);
?>

<script>
  const BASE_URL = "<?= base_url ?>";
  window.admissionsData = <?php echo $admissions_json; ?>;
</script>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css" />
<link rel="stylesheet" href="<?php echo base_url; ?>operations/map/map.css" />

<style>
  /* Keep it self-contained so we don’t affect other pages */
  #admissionsMap {
    width: 100%;
    height: 420px; /* reduced height (full width, not full height) */
    border-radius: 10px;
    overflow: hidden;
  }
  .fix-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px 12px;
  }
  .fix-grid .full { grid-column: 1 / -1; }
  .fix-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
    margin-top: 10px;
  }
  .fix-hint { font-size: 12px; color: var(--rc-muted); margin-top: 6px; }
  .fix-msg  { font-size: 12px; color: var(--rc-muted); }
  .fix-title {
    display:flex; justify-content:space-between; align-items:center; gap:12px;
    margin-bottom: 10px;
  }
  .fix-title h3 { margin:0; font-size: 16px; }
  .muted { color: var(--rc-muted); font-size: 12px; }
</style>

<div class="rc-panel">
  <div class="fix-title">
    <h3><?= htmlspecialchars(($lang['LOCATION'] ?? 'Location') . ' ' . ($lang['LOC_FIX'] ?? 'Fix')) ?></h3>
    <div class="muted"><?= htmlspecialchars($lang['DATA_CLICK_PIN_HELP'] ?? 'Click a pin to load its details below') ?></div>
  </div>

  <!-- Optional small filter row (species only) -->
  <div style="margin-bottom:10px; display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
    <div class="fix-field" style="min-width:220px;">
      <label class="xform-label" for="filterSpecies"><?= htmlspecialchars($lang['SPECIES'] ?? 'Species') ?></label>
      <select id="filterSpecies" class="xform-input">
        <option value="all" selected><?= htmlspecialchars($lang['MAP_ALL_SPECIES'] ?? 'All Species') ?></option>
      </select>
    </div>

    <div class="rc-chip" id="selectedSummary"><?= htmlspecialchars($lang['DATA_NO_PIN_SELECTED'] ?? 'No pin selected') ?></div>
  </div>

  <div id="admissionsMap"></div>
</div>

<div class="rc-panel">
  <div class="fix-title">
    <h3><?= htmlspecialchars($lang['DATA_SELECTED_RECORD'] ?? 'Selected record') ?></h3>
    <div class="muted" id="selectedMeta"><?= htmlspecialchars($lang['DATA_SELECT_PIN_BEGIN'] ?? 'Select a pin to begin') ?></div>
  </div>

  <form id="locationFixForm" onsubmit="return false;">
    <input type="hidden" id="fix_patient_id" value="">

    <div class="fix-grid">
      <div class="fix-field">
        <label class="xform-label"><?= htmlspecialchars($lang['ANIMAL'] ?? 'Animal') ?></label>
        <input id="fix_animal" class="xform-input" type="text" value="" readonly>
      </div>

      <div class="fix-field">
        <label class="xform-label"><?= htmlspecialchars(($lang['ADMISSION'] ?? 'Admission') . ' ' . strtolower($lang['DATE'] ?? 'date')) ?></label>
        <input id="fix_admission_date" class="xform-input" type="text" value="" readonly>
      </div>

      <div class="fix-field">
        <label class="xform-label"><?= htmlspecialchars($lang['LATITUDE'] ?? 'Latitude') ?></label>
        <input id="fix_lat" class="xform-input" type="text" value="" placeholder="e.g. 51.503364">
      </div>

      <div class="fix-field">
        <label class="xform-label"><?= htmlspecialchars($lang['LONGITUDE'] ?? 'Longitude') ?></label>
        <input id="fix_lng" class="xform-input" type="text" value="" placeholder="e.g. -0.127625">
      </div>

      <div class="fix-field full">
  <label class="xform-label"><?= htmlspecialchars($lang['DATA_STORED_COLLECTION_LOCATION'] ?? 'Stored collection location (original)') ?></label>
  <input id="fix_stored_location"
         class="xform-input"
         type="text"
         value=""
         readonly
         style="background:#f7f7f7; color:#555;">
  <div class="fix-hint">
    <?= htmlspecialchars($lang['DATA_STORED_COLLECTION_LOCATION_HELP'] ?? 'This is the original stored value used for geocoding.') ?>
  </div>
</div>

<div class="fix-field full" style="position:relative;">
  <label class="xform-label"><?= htmlspecialchars($lang['DATA_SEARCH_CORRECTED_ADDRESS'] ?? 'Search / corrected address') ?></label>

  <input id="fix_address"
         class="xform-input"
         type="text"
         value=""
         placeholder="<?= htmlspecialchars($lang['DATA_CORRECTED_ADDRESS_PLACEHOLDER'] ?? 'Start typing a corrected address...') ?>"
         autocomplete="off">

  <div id="fix_address_suggestions"
       class="rc-autocomplete-results"
       style="display:none; position:absolute; left:0; right:0; top:100%; z-index:9999;
              margin-top:6px; max-height:220px;">
  </div>

  <div class="fix-hint">
    <?= htmlspecialchars($lang['DATA_CORRECTED_ADDRESS_HELP'] ?? 'Use autocomplete to refine or correct the original location.') ?>
  </div>
</div>


    <div class="fix-actions">
      <label style="display:flex; gap:8px; align-items:center; margin:0;">
        <input type="checkbox" id="fix_click_mode">
        <span style="font-size:12px;"><?= htmlspecialchars($lang['DATA_CLICK_MAP_COORDS'] ?? 'Click map to set coordinates') ?></span>
      </label>

      <button type="button" class="btn green" id="btnSaveCoords" disabled><?= htmlspecialchars($lang['DATA_SAVE_COORDS'] ?? 'Save coords') ?></button>
      <button type="button" class="btn blue" id="btnRerunLookup" disabled><?= htmlspecialchars($lang['DATA_RERUN_LOOKUP'] ?? 'Re-run lookup') ?></button>

      <span class="fix-msg" id="fix_msg"></span>
    </div>

    <div class="fix-hint" style="margin-top:10px;">
      <?= htmlspecialchars($lang['DATA_LOCATION_FIX_TIP'] ?? 'Tip: After selecting a pin, you can adjust lat/long manually or enable click map to set coordinates.') ?>
    </div>

    <div class="fix-hint" style="margin-top:10px; display:none;">
      Tip: After selecting a pin, you can adjust lat/long manually or enable “Click map to set coordinates”.
    </div>
  </form>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>

<script src="<?php echo base_url; ?>operations/map/location_fix_map.js?ver=2"></script>
