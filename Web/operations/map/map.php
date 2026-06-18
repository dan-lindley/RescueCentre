
<script>
    const BASE_URL = "<?= base_url ?>";
</script>

<?php
// operations/map/map.php
// Assumes $conn and $centre_id already exist in dashboard

$sql = "
    SELECT 
        ra.patient_id,
        ra.admission_date,
        ra.location_lat,
        ra.location_long,
        ra.presenting_complaint,
        ra.age_on_admission,
        ra.disposition,
        ra.w_temp,
        ra.w_wind,
        ra.w_rainfall,
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
    ORDER BY ra.admission_date ASC
";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
$stmt->execute();
$admissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$admissions_json = json_encode($admissions, JSON_UNESCAPED_UNICODE);
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css" />
<link rel="stylesheet" href="<?php echo base_url; ?>operations/map/map.css?ver=<?php echo filemtime(__DIR__ . '/map.css'); ?>" />

<div id="mapWrapper">

    <!-- Controls Panel -->
    <div id="mapControls">
        <div class="panelHeader">
            <h3><?php echo $lang['MAP_CONTROLS']; ?></h3>
            <span class="toggleBtn" id="toggleControls">▼</span>
        </div>

        <div id="controlsContent">

            <div class="control-group">
                <label for="filterYear"><?php echo $lang['YEAR']; ?></label>
                <select id="filterYear">
                    <option value="all" selected><?php echo $lang['MAP_ALL_YEARS']; ?></option>
                </select>
            </div>

            <div class="control-group">
                <label for="filterSpecies"><?php echo $lang['SPECIES']; ?></label>
                <select id="filterSpecies">
                    <option value="all" selected><?php echo $lang['MAP_ALL_SPECIES']; ?></option>
                </select>
            </div>

            <div class="control-group">
                <p><strong><?php echo ($lang['WEATHER'] ?? 'Weather') . ' ' . ($lang['LAYERS'] ?? 'Layers'); ?></strong></p>
                <label><input type="checkbox" id="toggleTemp" checked> <?php echo $lang['MAP_TEMPERATURE']; ?></label>
                <label><input type="checkbox" id="toggleWind" checked> <?php echo $lang['MAP_WIND']; ?></label>
                <label><input type="checkbox" id="toggleRain" checked> <?php echo $lang['MAP_RAINFALL']; ?></label>
            </div>

        </div>
    </div>

    <div id="admissionsMap"></div>

</div> <!-- end mapWrapper -->


<!-- HORIZONTAL LEGEND BELOW MAP -->
<div id="mapLegend">

    <div class="legendHeader">
        <h4><?php echo $lang['MAP_LEGEND']; ?></h4>
        <span class="toggleBtn" id="toggleLegend">▼</span>
    </div>

    <div id="legendContent">

        <div class="legend-group">
            <h5><?php echo $lang['MAP_PIN_COLOURS']; ?></h5>
            <div class="legend-item"><span class="legend-box" style="background: green;"></span> <?php echo $lang['CURRENT']; ?></div>
            <div class="legend-item"><span class="legend-box" style="background: yellow;"></span> <?php echo $lang['PREVIOUS']; ?></div>
            <div class="legend-item"><span class="legend-box" style="background: blue;"></span> <?php echo $lang['MAP_TWO_YEARS_AGO']; ?></div>
        </div>

        <div class="legend-group">
            <h5><?php echo $lang['MAP_TEMPERATURE']; ?> (°C)</h5>
            <div class="legend-item"><span class="legend-box" style="background:#0000ff;"></span> <10</div>
            <div class="legend-item"><span class="legend-box" style="background:#00ffff;"></span> 10–15</div>
            <div class="legend-item"><span class="legend-box" style="background:#00ff00;"></span> 15–20</div>
            <div class="legend-item"><span class="legend-box" style="background:#ffff00;"></span> 20–25</div>
            <div class="legend-item"><span class="legend-box" style="background:#ff9900;"></span> 25–30</div>
            <div class="legend-item"><span class="legend-box" style="background:#ff0000;"></span> >30</div>
        </div>

        <div class="legend-group">
            <h5><?php echo $lang['MAP_WIND']; ?> (mph)</h5>
            <div class="legend-item"><span class="legend-box" style="background:#00ff00;"></span> 0–10</div>
            <div class="legend-item"><span class="legend-box" style="background:#ffff00;"></span> 10–20</div>
            <div class="legend-item"><span class="legend-box" style="background:#ff9900;"></span> 20–30</div>
            <div class="legend-item"><span class="legend-box" style="background:#ff0000;"></span> >30</div>
        </div>

        <div class="legend-group">
            <h5><?php echo $lang['MAP_RAINFALL']; ?> (mm)</h5>
            <div class="legend-item"><span class="legend-box" style="background:#a0c4ff;"></span> 0–1</div>
            <div class="legend-item"><span class="legend-box" style="background:#4361ee;"></span> 1–5</div>
            <div class="legend-item"><span class="legend-box" style="background:#7209b7;"></span> 5–10</div>
            <div class="legend-item"><span class="legend-box" style="background:#3a0ca3;"></span> >10</div>
        </div>

    </div>

</div>


<script>
window.admissionsData = <?php echo $admissions_json; ?>;
window.admissionsMapLang = <?php echo json_encode([
    'weather' => $lang['WEATHER'],
    'temp' => $lang['TEMPERATURE_ABBR'],
    'wind' => $lang['MAP_WIND'],
    'rain' => $lang['RAIN'],
    'complaint' => $lang['COMPLAINT'],
    'age' => $lang['AGE'],
    'disposition' => $lang['DISPOSITION'],
    'view_patient' => ($lang['VIEW'] ?? 'View') . ' ' . ($lang['PATIENT'] ?? 'Patient') . ' ' . ($lang['RECORD'] ?? 'Record'),
    'na' => 'N/A',
], JSON_UNESCAPED_UNICODE); ?>;
</script>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>

<script src="<?php echo base_url; ?>operations/map/admissions_map.js?ver=12"></script>
