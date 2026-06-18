<?php
// controllers/admissions/section6.php
// ----------------------------------------------------
// SECTION 6 – WEATHER DATA (OPTIONAL) (FIELD PERMISSIONS)
// ----------------------------------------------------

if (!isset($pdo)) {
    die('PDO not available in Section 6');
}

require_once __DIR__ . '/../../operations/permissions.php';

/*
|--------------------------------------------------------------------------
| SECTION 6 FIELD CONFIG (SINGLE SOURCE OF TRUTH)
| true  = required to mark complete
| false = optional
|--------------------------------------------------------------------------
| Weather section is optional by default.
*/
$SECTION6_FIELDS = [
    'w_temp'     => false,
    'w_wind'     => false,
    'w_humidity' => false,
    'w_freetext' => false,
];

// Register permissions
registerPermission('admission.weather.w_temp.edit',      'Edit temperature (weather)', 'field');
registerPermission('admission.weather.w_wind.edit',      'Edit wind speed (weather)', 'field');
registerPermission('admission.weather.w_humidity.edit',  'Edit humidity (weather)', 'field');
registerPermission('admission.weather.w_freetext.edit',  'Edit weather free text', 'field');

// Optional action: allow clicking the fetch button (fills values)
registerPermission('admission.weather.fetch',            'Fetch weather data automatically', 'action');

// Strict edit flags
$can_temp    = can('admission.weather.w_temp.edit');
$can_wind    = can('admission.weather.w_wind.edit');
$can_hum     = can('admission.weather.w_humidity.edit');
$can_text    = can('admission.weather.w_freetext.edit');
$can_fetch   = can('admission.weather.fetch');

// Values from database if editing
$w_temp     = $admission['w_temp']     ?? '';
$w_wind     = $admission['w_wind']     ?? '';
$w_humidity = $admission['w_humidity'] ?? '';
$w_freetext = $admission['w_freetext'] ?? '';

// Coordinates and date/time from the admission record
$location_lat   = $admission['location_lat']   ?? '';
$location_long  = $admission['location_long']  ?? '';
$admission_date = $admission['admission_date'] ?? '';

// Can user meaningfully use fetch? (must be allowed to fetch and edit at least one field)
$can_use_fetch = $can_fetch && ($can_temp || $can_wind || $can_hum || $can_text);
?>

<div class="rc-card rc-card-muted">
    <h3><?= htmlspecialchars(($lang['SECTION'] ?? 'Section') . ' 6 - ' . ($lang['WEATHER'] ?? 'Weather') . ' ' . ($lang['DATA'] ?? 'Data') . ' (' . strtolower($lang['OPTIONAL'] ?? 'optional') . ')') ?></h3>

    <form id="section6-form" class="xform"
          data-required-fields="<?= htmlspecialchars(json_encode($SECTION6_FIELDS), ENT_QUOTES, 'UTF-8') ?>"
          onsubmit="event.preventDefault(); document.getElementById('section6-mark-complete').value='0'; saveSection(6, 'section6-form');">

        <input type="hidden" name="patient_id" value="<?= htmlspecialchars($pid ?? '') ?>">
        <input type="hidden" name="admission_id" value="<?= htmlspecialchars($aid ?? '') ?>">
        <input type="hidden" name="mark_complete" id="section6-mark-complete" value="0">

        <div class="xform-grid">

            <!-- Temperature -->
            <div class="xform-field">
                <label class="xform-label"><?= htmlspecialchars(($lang['TEMPERATURE'] ?? 'Temperature') . ' (°C)') ?></label>

                <?php if (!$can_temp): ?>
                    <input type="hidden" name="w_temp" value="<?= htmlspecialchars($w_temp) ?>">
                <?php endif; ?>

                <input type="text"
                       name="w_temp"
                       id="w_temp"
                       class="xform-input <?= $can_temp ? '' : 'is-readonly' ?>"
                       value="<?= htmlspecialchars($w_temp) ?>"
                       placeholder="e.g. 13.5"
                       <?= $can_temp ? '' : 'readonly' ?>>
            </div>

            <!-- Wind Speed -->
            <div class="xform-field">
                <label class="xform-label"><?= htmlspecialchars(($lang['WIND'] ?? 'Wind') . ' ' . ($lang['SPEED'] ?? 'Speed') . ' (mph)') ?></label>

                <?php if (!$can_wind): ?>
                    <input type="hidden" name="w_wind" value="<?= htmlspecialchars($w_wind) ?>">
                <?php endif; ?>

                <input type="text"
                       name="w_wind"
                       id="w_wind"
                       class="xform-input <?= $can_wind ? '' : 'is-readonly' ?>"
                       value="<?= htmlspecialchars($w_wind) ?>"
                       placeholder="e.g. 8"
                       <?= $can_wind ? '' : 'readonly' ?>>
            </div>

            <!-- Humidity -->
            <div class="xform-field">
                <label class="xform-label"><?= htmlspecialchars(($lang['HUMIDITY'] ?? 'Humidity') . ' (%)') ?></label>

                <?php if (!$can_hum): ?>
                    <input type="hidden" name="w_humidity" value="<?= htmlspecialchars($w_humidity) ?>">
                <?php endif; ?>

                <input type="text"
                       name="w_humidity"
                       id="w_humidity"
                       class="xform-input <?= $can_hum ? '' : 'is-readonly' ?>"
                       value="<?= htmlspecialchars($w_humidity) ?>"
                       placeholder="e.g. 72"
                       <?= $can_hum ? '' : 'readonly' ?>>
            </div>

            <!-- Free Text -->
            <div class="xform-field span-4">
                <label class="xform-label"><?= htmlspecialchars($lang['NOTES'] ?? 'Notes') ?></label>

                <?php if (!$can_text): ?>
                    <input type="hidden" name="w_freetext" value="<?= htmlspecialchars($w_freetext) ?>">
                <?php endif; ?>

                <input type="text"
                       name="w_freetext"
                       id="w_freetext"
                       class="xform-input <?= $can_text ? '' : 'is-readonly' ?>"
                       value="<?= htmlspecialchars($w_freetext) ?>"
                       placeholder="<?= htmlspecialchars($lang['ADM_WEATHER_NOTES_PLACEHOLDER'] ?? 'Describe weather or abnormalities') ?>"
                       <?= $can_text ? '' : 'readonly' ?>>
            </div>

            <!-- Weather Button -->
            <div class="xform-field span-4">
                <button type="button"
                        id="getWeatherBtn"
                        class="btn green"
                        <?= $can_use_fetch ? '' : 'disabled' ?>>
                    <?= htmlspecialchars(($lang['FETCH'] ?? 'Fetch') . ' ' . ($lang['WEATHER'] ?? 'Weather')) ?>
                </button>

                <?php if (!$can_use_fetch): ?>
                    <p class="rc-note is-danger">
                        <?= htmlspecialchars($lang['ADM_WEATHER_FETCH_DISABLED'] ?? 'Weather fetch is disabled (no permission to fetch and/or edit weather fields).') ?>
                    </p>
                <?php else: ?>
                    <p class="rc-note">
                        <?= htmlspecialchars($lang['ADM_WEATHER_SOURCE_HELP'] ?? 'Uses the collection location (Section 3) and admission date/time (Section 2).') ?>
                    </p>
                <?php endif; ?>
            </div>

        </div><!-- /xform-grid -->

        <div class="xform-actions">
            <br>
            <button type="submit" class="btn green"><?= htmlspecialchars(($lang['SAVE'] ?? 'Save') . ' ' . ($lang['SECTION'] ?? 'Section') . ' 6') ?></button>
            <button type="button" class="btn" id="markSection6Complete">
                <?= htmlspecialchars(($lang['MARK'] ?? 'Mark') . ' ' . ($lang['SECTION'] ?? 'Section') . ' ' . strtolower($lang['COMPLETE'] ?? 'complete')) ?>
            </button>
        </div>

    </form>
</div>

<script>
/* ===================== SECTION 6 CLIENT VALIDATION ===================== */
const SECTION6_FIELDS = <?= json_encode($SECTION6_FIELDS) ?>;

function missingRequiredSection6() {
    const missing = [];
    for (const f in SECTION6_FIELDS) {
        if (!SECTION6_FIELDS[f]) continue;
        const el = document.querySelector('[name="'+f+'"]');
        const val = el ? (el.value || '').trim() : '';
        if (val === '') missing.push(f);
    }
    return missing;
}

document.getElementById('markSection6Complete').onclick = () => {
    const missing = missingRequiredSection6();
    if (missing.length) {
        alert(<?= json_encode($lang['COMPLETE_REQUIRED_FIRST'] ?? 'Please complete required fields first.') ?>);
        return;
    }
    const flag = document.getElementById('section6-mark-complete');
    flag.value = '1';
    saveSection(6, 'section6-form');
    flag.value = '0';
};

function fetchWeatherManual() {

    const canTemp  = <?= $can_temp ? 'true' : 'false' ?>;
    const canWind  = <?= $can_wind ? 'true' : 'false' ?>;
    const canHum   = <?= $can_hum ? 'true' : 'false' ?>;
    const canText  = <?= $can_text ? 'true' : 'false' ?>;
    const canFetch = <?= $can_use_fetch ? 'true' : 'false' ?>;

    if (!canFetch) return;

    const lat      = "<?= htmlspecialchars((string)$location_lat, ENT_QUOTES) ?>";
    const lon      = "<?= htmlspecialchars((string)$location_long, ENT_QUOTES) ?>";
    const dateTime = "<?= htmlspecialchars((string)$admission_date, ENT_QUOTES) ?>";

    if (!lat || !lon) {
        alert(<?= json_encode($lang['ADM_SAVE_COLLECTION_FIRST'] ?? 'Please set and save the collection location in Section 3 first.') ?>);
        return;
    }

    if (!dateTime) {
        alert(<?= json_encode($lang['ADM_SAVE_ADMISSION_DATE_FIRST'] ?? 'Please set and save the admission date/time in Section 2 first.') ?>);
        return;
    }

    let datePart, hourPart;
    if (dateTime.includes("T")) {
        const parts = dateTime.split("T");
        datePart = parts[0];
        hourPart = parts[1].substring(0, 2);
    } else {
        const parts = dateTime.split(" ");
        datePart = parts[0];
        hourPart = (parts[1] || "00:00:00").substring(0, 2);
    }

    const btn = document.getElementById("getWeatherBtn");
    btn.innerText = <?= json_encode($lang['FETCHING'] ?? 'Fetching...') ?>;
    btn.disabled = true;

    const url =
        `https://archive-api.open-meteo.com/v1/archive?latitude=${lat}` +
        `&longitude=${lon}` +
        `&start_date=${datePart}&end_date=${datePart}` +
        `&hourly=temperature_2m,relative_humidity_2m,windspeed_10m`;

    fetch(url)
        .then(r => r.json())
        .then(data => {

            if (!data.hourly || !data.hourly.time) {
                alert(<?= json_encode($lang['ADM_WEATHER_DATE_UNAVAILABLE'] ?? 'Weather data not available for this date.') ?>);
                btn.innerText = <?= json_encode(($lang['FETCH'] ?? 'Fetch') . ' ' . ($lang['WEATHER'] ?? 'Weather')) ?>;
                btn.disabled = false;
                return;
            }

            const idx = data.hourly.time.findIndex(t => t.includes(`${datePart}T${hourPart}`));
            if (idx === -1) {
                alert(<?= json_encode($lang['ADM_WEATHER_HOUR_UNAVAILABLE'] ?? 'No weather data available for that hour.') ?>);
                btn.innerText = <?= json_encode(($lang['FETCH'] ?? 'Fetch') . ' ' . ($lang['WEATHER'] ?? 'Weather')) ?>;
                btn.disabled = false;
                return;
            }

            const temp = data.hourly.temperature_2m[idx];
            const hum  = data.hourly.relative_humidity_2m[idx];
            const wind = data.hourly.windspeed_10m[idx];

            if (canTemp) document.getElementById("w_temp").value     = temp ?? "";
            if (canHum)  document.getElementById("w_humidity").value = hum ?? "";
            if (canWind) document.getElementById("w_wind").value     = wind ?? "";

            if (canText) {
                document.getElementById("w_freetext").value = <?= json_encode($lang['ADM_WEATHER_APPROX'] ?? 'Approx: %s°C, %s%% humidity, %s mph wind.') ?>
                    .replace('%s', temp)
                    .replace('%s', hum)
                    .replace('%%', '%')
                    .replace('%s', wind);
            }

            btn.innerText = <?= json_encode(($lang['WEATHER'] ?? 'Weather') . ' ' . ($lang['LOADED'] ?? 'Loaded') . ' ✔') ?>;
            btn.disabled = false;
            setTimeout(() => btn.innerText = <?= json_encode(($lang['FETCH'] ?? 'Fetch') . ' ' . ($lang['WEATHER'] ?? 'Weather')) ?>, 2000);
        })
        .catch(err => {
            console.error("Weather error:", err);
            alert(<?= json_encode($lang['ADM_WEATHER_FETCH_ERROR'] ?? 'Error fetching weather.') ?>);
            btn.innerText = <?= json_encode(($lang['FETCH'] ?? 'Fetch') . ' ' . ($lang['WEATHER'] ?? 'Weather')) ?>;
            btn.disabled = false;
        });
}

const wBtn = document.getElementById("getWeatherBtn");
if (wBtn) wBtn.addEventListener("click", fetchWeatherManual);
</script>
