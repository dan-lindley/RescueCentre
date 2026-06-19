<?php
include 'dashmain.php';
include 'getcentreinfo.php';
require_once __DIR__ . '/operations/modules_registry.php';
check_loggedin($pdo);

$centre_id = (int)($_SESSION['centre_id'] ?? 0);

$locations_by_area = [];
$locationOptions = [];
$defaultLocationId = 'none';
$timeOptions = [];
$presentingComplaints = [];
$passphraseWords = ['alpha', 'beta', 'gamma'];
$declarationText = "By handing over these animals to the rescue centre, the finder confirms that they transfer ongoing responsibility for the care and welfare of the animals to the rescue.\n\nThe finder understands that their personal details, where provided and consented, may be stored and used for the purposes of providing updates and for audit/legal purposes in line with GDPR and the centre's privacy policy.";

if ($centre_id > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT location_id, location_name, location_area
            FROM rescue_locations
            WHERE centre_id = :cid
              AND (deleted IS NULL OR deleted = 0)
            ORDER BY location_area ASC, location_name ASC
        ");
        $stmt->execute([':cid' => $centre_id]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $area = $row['location_area'] ?: 'Other';
            $locations_by_area[$area][] = $row;
            $locationOptions[] = [
                'location_id' => (int)$row['location_id'],
                'location_name' => (string)$row['location_name'],
                'location_area' => (string)$area,
            ];
            if (strcasecmp((string)$row['location_name'], 'None') === 0) {
                $defaultLocationId = (string)(int)$row['location_id'];
            }
        }
    } catch (Exception $e) {
        $locations_by_area = [];
    }

    try {
        $timeOptions = $pdo->query("
            SELECT time_to_admission
            FROM rescue_time_admission
            ORDER BY time_id ASC
        ")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $timeOptions = [];
    }

    try {
        $stmt = $pdo->query("
            SELECT prsenting_complaint
            FROM rescue_presenting_complaints
            ORDER BY prsenting_complaint ASC
        ");
        $presentingComplaints = array_values(array_filter(array_map(
            static fn($row) => trim((string)($row['prsenting_complaint'] ?? '')),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        )));
    } catch (Exception $e) {
        $presentingComplaints = [];
    }

    try {
        $words = $pdo->query("SELECT word_1, word_2, word_3 FROM rescue_words ORDER BY RAND() LIMIT 1")
            ->fetch(PDO::FETCH_ASSOC);
        if ($words) {
            $passphraseWords = [
                $words['word_1'] ?? 'alpha',
                $words['word_2'] ?? 'beta',
                $words['word_3'] ?? 'gamma',
            ];
        }
    } catch (Exception $e) {
        $passphraseWords = ['alpha', 'beta', 'gamma'];
    }

    try {
        $stmt = $pdo->prepare("
            SELECT handover_declaration_text
            FROM rescue_centre_meta
            WHERE centre_id = :cid
            LIMIT 1
        ");
        $stmt->execute([':cid' => $centre_id]);
        $customDeclaration = trim((string)$stmt->fetchColumn());
        if ($customDeclaration !== '') {
            $declarationText = $customDeclaration;
        }
    } catch (Exception $e) {
        // Keep fallback text.
    }
}

$dispositionOptions = [
    'Held in captivity',
    'Released',
    'Transferred out',
    'Died - Euthanised',
    'Died - after 48 hours',
    'Died - within 48 hours',
    'Died - on admission',
];

if (modules_is_active($pdo, 'adoptions', $centre_id > 0 ? $centre_id : null)) {
    $dispositionOptions[] = 'For Adoption';
    $dispositionOptions[] = 'Adopted';
}

$sexOptions = [
    'Male',
    'Female',
    'Female (lactating)',
    'Female (pregnant)',
    'Undetermined',
];

$ageOptions = [
    'Newborn',
    'Dependent Juvenile',
    'Independent Juvenile',
    'Hatchling',
    'Fledgling',
    'Adult',
];

$severityOptions = [
    'Apparently Healthy',
    'Mildly unwell',
    'Obvious Injuries',
    'Severe Injuries',
    'Near Death',
];

$bodyConditionOptions = [
    'BCS 1 Skeletal',
    'BCS 2 Underweight',
    'BCS 3 Slightly Underweight',
    'BCS 4 Healthy',
    'BCS 5 Overweight',
];
?>

<?php $page_css_files = ['core/css/group-admission.css']; ?>
<?= template_admin_header('Group Admission - ' . $rescue_name . ' - Rescue Centre - Rescue Management System', 'patients', 'admission') ?>

<div class="content-title">
    <div class="title">
        <div class="icon">
            <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M96 160a96 96 0 1 1 192 0A96 96 0 1 1 96 160zM0 528c0-97.2 78.8-176 176-176s176 78.8 176 176c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48zM384 224a80 80 0 1 1 160 0 80 80 0 1 1-160 0zM352 576c19.9-25.1 32-56.7 32-91.2 0-38.8-15.2-74-39.9-100.1C374.5 364 413 352 454.4 352 556.9 352 640 435.1 640 537.6c0 21.2-17.2 38.4-38.4 38.4H352z"/></svg>
        </div>
        <div class="txt">
            <h2 class="pagehead">Group Admission</h2>
            <p>Admit multiple animals from one shared collection event.</p>
        </div>
    </div>
    <div class="btns">
        <a href="admission.php" class="btn grey">Single Admission</a>
    </div>
</div>



<div class="content">
    <div id="group-status" class="group-status"></div>

    <form id="group-admission-form" class="xform">
        <input type="hidden" name="animal_species" id="animal_species">
        <input type="hidden" name="animal_type" id="animal_type">
        <input type="hidden" name="animal_order" id="animal_order">
        <input type="hidden" name="signature_data" id="signature_data">

        <div class="rc-stage-shell">
            <aside class="content-block rc-stage-nav admission-stage-nav">
                <ul class="rc-stage-list">
                    <li><button type="button" class="group-stage rc-stage-link is-active" data-stage="1"><span class="rc-stage-index">S1</span><span class="rc-stage-label">Animal + Quantity</span><span class="rc-stage-meta">0%</span></button></li>
                    <li><button type="button" class="group-stage rc-stage-link" data-stage="2"><span class="rc-stage-index">S2</span><span class="rc-stage-label">Individual Patients</span><span class="rc-stage-meta">0%</span></button></li>
                    <li><button type="button" class="group-stage rc-stage-link" data-stage="3"><span class="rc-stage-index">S3</span><span class="rc-stage-label">Collection / Finder</span><span class="rc-stage-meta">0%</span></button></li>
                    <li><button type="button" class="group-stage rc-stage-link" data-stage="4"><span class="rc-stage-index">S4</span><span class="rc-stage-label">Assessments</span><span class="rc-stage-meta">0%</span></button></li>
                    <li><button type="button" class="group-stage rc-stage-link" data-stage="5"><span class="rc-stage-index">S5</span><span class="rc-stage-label">Release / Sign</span><span class="rc-stage-meta">0%</span></button></li>
                    <li><button type="button" class="group-stage rc-stage-link" data-stage="6"><span class="rc-stage-index">S6</span><span class="rc-stage-label">Confirmation</span><span class="rc-stage-meta">0%</span></button></li>
                </ul>
            </aside>

            <div>
                <section class="content-block group-panel active" data-panel="1">
                    <h3>Stage 1 - Animal + Quantity</h3>
                    <p>Select the shared species and enter how many individual patient rows to create.</p>
                    <div class="xform-grid">
                        <div class="xform-field span-3">
                            <label class="xform-label">Animal Species <span class="req">*</span></label>
                            <input type="text" id="species_search" class="xform-input" autocomplete="off" placeholder="Start typing common or scientific name..." required>
                            <div id="species_results" class="rc-autocomplete-results"></div>
                        </div>
                        <div class="xform-field">
                            <label class="xform-label">Number of animals <span class="req">*</span></label>
                            <input type="number" name="quantity" id="quantity" class="xform-input" min="1" max="200" value="2">
                        </div>
                    </div>
                </section>

                <section class="content-block group-panel" data-panel="2">
                    <h3>Stage 2 - Individual Patients</h3>
                    <p>These details stay individual to each generated patient and admission.</p>
                    <div id="patient-rows-wrap"></div>
                </section>

                <section class="content-block group-panel" data-panel="3">
                    <h3>Stage 3 - Collection / Finder Information</h3>
                    <div class="xform-grid">
                        <div class="xform-field span-2">
                            <label class="xform-label">Collection Location <span class="req">*</span></label>
                            <div class="rc-lookup-wrap">
                            <div class="xform-grid" style="grid-template-columns: 120px minmax(0, 1fr); gap: 8px;">
                                <div class="xform-field">
                                    <label class="xform-label" for="collection_house_number">House no</label>
                                    <input type="text" id="collection_house_number" class="xform-input" autocomplete="off">
                                </div>
                                <div class="xform-field">
                                    <label class="xform-label" for="collection_location">Address</label>
                                    <input type="text" name="collection_location" id="collection_location" class="xform-input" placeholder="Start typing an address or postcode..." autocomplete="off">
                                </div>
                            </div>
                            <span id="useMyLocationIcon"
                                  class="rc-note"
                                  title="Use my current location">Pin</span>
                            <div id="location_results" class="rc-autocomplete-results"></div>
                            </div>
                        </div>
                        <div class="xform-field">
                            <label class="xform-label">Postcode</label>
                            <input type="text" name="incident_location_postcode" id="incident_location_postcode" class="xform-input">
                        </div>
                        <div class="xform-field">
                            <label class="xform-label">Latitude</label>
                            <input type="text" name="location_lat" id="location_lat" class="xform-input">
                        </div>
                        <div class="xform-field">
                            <label class="xform-label">Longitude</label>
                            <input type="text" name="location_long" id="location_long" class="xform-input">
                        </div>
                    </div>

                    <div class="xform-grid">
                        <div class="xform-field span-2">
                            <label class="xform-label">Finder Name</label>
                            <input type="text" id="finder_search" class="xform-input" placeholder="Type to search finder..." autocomplete="off">
                            <div id="finder_results"
                                 class="rc-autocomplete-results"></div>
                        </div>
                        <div class="xform-field">
                            <label class="xform-label">Finder Telephone</label>
                            <input type="text" name="finder_tel" id="finder_tel" class="xform-input" autocomplete="off">
                        </div>
                        <input type="hidden" name="finder_id" id="finder_id" value="0">
                        <input type="hidden" name="finder_name" id="finder_name" value="">
                    </div>

                    <div class="xform-grid">
                        <div class="xform-field span-3">
                            <br>
                            <button type="button" class="btn" id="showAddFinderBtn">
                                + Add New Finder
                            </button>

                            <div id="addFinderWrapper" class="rc-panel rc-card-muted">
                                <h3>Add New Finder</h3>
                                <div class="xform-grid">
                                    <div class="xform-field">
                                        <label class="xform-label">Finder Name *</label>
                                        <input type="text" id="newFinderName" class="xform-input">
                                    </div>
                                    <div class="xform-field">
                                        <label class="xform-label">Finder Tel *</label>
                                        <input type="text" id="newFinderTel" class="xform-input">
                                    </div>
                                </div>
                                <div class="xform-actions">
                                    <button type="button" class="btn primary" id="saveNewFinderBtn">Save Finder</button>
                                    <button type="button" class="btn" id="cancelNewFinderBtn">Cancel</button>
                                </div>
                                <div id="addFinderStatus" class="rc-note"></div>
                            </div>
                        </div>
                    </div>

                    <div class="xform-grid">
                        <div class="xform-field">
                            <label class="xform-label">Incident/admission date <span class="req">*</span></label>
                            <input type="datetime-local" name="admission_date" id="admission_date" class="xform-input" value="<?= htmlspecialchars(date('Y-m-d\TH:i')) ?>">
                        </div>
                        <div class="xform-field">
                            <label class="xform-label">Time to admission</label>
                            <select name="time_to_admission" class="xform-input">
                                <option value="">Select...</option>
                                <?php foreach ($timeOptions as $opt): ?>
                                    <option value="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars($opt) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="xform-field">
                            <label class="xform-label">Consent to updates</label>
                            <select name="consent_to_update" class="xform-input">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                        <div class="xform-field span-3">
                            <label class="xform-label">Passphrase *</label>
                            <p>Used for finder verification on the public page.</p>
                            <?php foreach ($passphraseWords as $idx => $word): ?>
                                <label>
                                    <input type="radio"
                                           name="passphrase"
                                           value="<?= htmlspecialchars($word) ?>"
                                           <?= $idx === 0 ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($word) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="xform-grid">
                        <div class="xform-field">
                            <label class="xform-label">Weather temp</label>
                            <input type="text" name="w_temp" id="w_temp" class="xform-input">
                        </div>
                        <div class="xform-field">
                            <label class="xform-label">Wind</label>
                            <input type="text" name="w_wind" id="w_wind" class="xform-input">
                        </div>
                        <div class="xform-field">
                            <label class="xform-label">Humidity</label>
                            <input type="text" name="w_humidity" id="w_humidity" class="xform-input">
                        </div>
                        <div class="xform-field">
                            <label class="xform-label">Rainfall</label>
                            <input type="text" name="w_rainfall" id="w_rainfall" class="xform-input">
                        </div>
                        <div class="xform-field span-4">
                            <label class="xform-label">Weather notes</label>
                            <textarea name="w_freetext" id="w_freetext" class="xform-input" rows="3"></textarea>
                        </div>
                        <div class="xform-field span-4">
                            <button type="button" class="btn primary" id="getWeatherBtn">Fetch Weather Data</button>
                            <span id="weatherFetchStatus" class="rc-note"></span>
                            <p class="rc-note">
                                Uses the collection latitude/longitude and incident/admission date/time.
                            </p>
                        </div>
                    </div>
                </section>

                <section class="content-block group-panel" data-panel="4">
                    <h3>Stage 4 - Per Patient Assessment</h3>
                    <div class="xform-grid">
                        <input type="hidden" name="disposition" value="Held in captivity">
                        <div class="xform-field span-4">
                            <p>Complete the assessment fields for each animal in the group.</p>
                            <div id="assessment-rows-wrap"></div>
                        </div>
                    </div>
                </section>

                <section class="content-block group-panel" data-panel="5">
                    <h3>Stage 5 - Release / Sign</h3>
                    <div class="xform-field">
                        <label class="xform-label">Declaration text</label>
                        <div class="rc-panel rc-card-muted"><?= nl2br(htmlspecialchars($declarationText)) ?></div>
                    </div>
                    <div class="xform-field">
                        <label class="xform-label">Finder Signature</label>
                        <canvas id="signature-canvas" width="520" height="180" class="rc-signature-pad"></canvas>
                        <div>
                            <button type="button" class="btn grey" id="clearSignatureBtn">Clear signature</button>
                            <label><input type="checkbox" name="no_signature" id="no_signature" value="1"> No signature obtained</label>
                        </div>
                    </div>
                </section>

                <section class="content-block group-panel" data-panel="6">
                    <h3>Stage 6 - Confirmation</h3>
                    <div id="review-output"></div>
                    <div>
                        <button type="submit" class="btn green" id="submitGroupBtn">Create Group Admission</button>
                    </div>
                </section>

                <div class="group-actions">
                    <button type="button" class="btn grey" id="prevStageBtn">Previous</button>
                    <button type="button" class="btn primary" id="nextStageBtn">Next</button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
(function () {
    const sexOptions = <?= json_encode($sexOptions) ?>;
    const ageOptions = <?= json_encode($ageOptions) ?>;
    const severityOptions = <?= json_encode($severityOptions) ?>;
    const bodyConditionOptions = <?= json_encode($bodyConditionOptions) ?>;
    const presentingComplaintOptions = <?= json_encode($presentingComplaints) ?>;
    const locationOptions = <?= json_encode($locationOptions) ?>;
    const defaultLocationId = <?= json_encode($defaultLocationId) ?>;
    let currentStage = 1;
    let lastQuantity = 0;

    const form = document.getElementById('group-admission-form');
    const statusBox = document.getElementById('group-status');

    function showStatus(message, isError) {
        statusBox.textContent = message;
        statusBox.className = 'group-status ' + (isError ? 'err' : 'ok');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function esc(value) {
        return String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
    }

    function optionsHtml(options, selected, emptyLabel) {
        let html = emptyLabel ? '<option value="">' + esc(emptyLabel) + '</option>' : '';
        options.forEach(opt => {
            const value = typeof opt === 'object' ? opt.value : opt;
            const label = typeof opt === 'object' ? opt.label : opt;
            html += '<option value="' + esc(value) + '"' + (String(selected || '') === String(value) ? ' selected' : '') + '>' + esc(label) + '</option>';
        });
        return html;
    }

    const ageScoreMap = {
        'Newborn': 3,
        'Dependent Juvenile': 2,
        'Independent Juvenile': 1,
        'Hatchling': 3,
        'Fledgling': 2,
        'Adult': 0
    };

    const severityScoreMap = {
        'Apparently Healthy': 0,
        'Mildly unwell': 0,
        'Obvious Injuries': 1,
        'Severe Injuries': 2,
        'Near Death': 3
    };

    const bodyConditionScoreMap = {
        'BCS 1 Skeletal': 3,
        'BCS 2 Underweight': 2,
        'BCS 3 Slightly Underweight': 1,
        'BCS 4 Healthy': 0,
        'BCS 5 Overweight': 0
    };

    function scoreValue(map, label) {
        return Object.prototype.hasOwnProperty.call(map, label) ? map[label] : '';
    }

    function locationOptionsHtml(selected) {
        let html = '<option value="none"' + (String(selected || defaultLocationId) === 'none' ? ' selected' : '') + '>None</option>';
        html += optionsHtml(locationOptions.map(loc => ({
            value: loc.location_id,
            label: (loc.location_area ? loc.location_area + ' - ' : '') + loc.location_name
        })), selected, '');
        return html;
    }

    function generateRows(force) {
        const qty = Math.max(1, Math.min(200, parseInt(document.getElementById('quantity').value || '1', 10)));
        if (!force && qty === lastQuantity) return;

        const existing = [];
        document.querySelectorAll('#patient-rows-wrap .patient-row-card').forEach((tr) => {
            existing.push({
                name: tr.querySelector('[name="animal_name[]"]')?.value || '',
                sex: tr.querySelector('[name="sex[]"]')?.value || '',
                weight: tr.querySelector('[name="weight[]"]')?.value || '0',
                weight_unit: tr.querySelector('[name="weight_unit[]"]')?.value || 'g',
                measurement: tr.querySelector('[name="measurement[]"]')?.value || '0',
                measurement_unit: tr.querySelector('[name="measurement_unit[]"]')?.value || 'mm',
                current_location_id: tr.querySelector('[name="current_location_id[]"]')?.value || defaultLocationId,
                age_on_admission: tr.querySelector('[name="age_on_admission[]"]')?.value || '',
                dehydrated: tr.querySelector('[name="dehydrated[]"]')?.value || 'No',
                starved: tr.querySelector('[name="starved[]"]')?.value || 'No',
                ringed: tr.querySelector('[name="ringed[]"]')?.value || 'No',
                ring_number: tr.querySelector('[name="ring_number[]"]')?.value || '',
                microchipped: tr.querySelector('[name="microchipped[]"]')?.value || 'No',
                microchip_number: tr.querySelector('[name="microchip_number[]"]')?.value || ''
            });
        });

        let html = '';
        for (let i = 0; i < qty; i++) {
            const row = existing[i] || {};
            html += '<div class="patient-row-card">';
            html += '<h4>Animal ' + (i + 1) + '</h4>';
            html += '<div class="patient-row-grid">';
            html += '<div class="xform-field"><label class="xform-label">Name / identifier</label><input type="text" name="animal_name[]" class="xform-input" value="' + esc(row.name || 'Animal ' + (i + 1)) + '"></div>';
            html += '<div class="xform-field"><label class="xform-label">Sex</label><select name="sex[]" class="xform-input"><option value="">Select sex...</option>' + sexOptions.map(opt => '<option value="' + esc(opt) + '"' + (row.sex === opt ? ' selected' : '') + '>' + esc(opt) + '</option>').join('') + '</select></div>';
            html += '<div class="xform-field"><label class="xform-label">Age</label><select name="age_on_admission[]" class="xform-input score-age">' + optionsHtml(ageOptions, row.age_on_admission, 'Select...') + '</select><input type="hidden" name="age_score[]" value="' + esc(scoreValue(ageScoreMap, row.age_on_admission)) + '"></div>';
            html += '<div class="xform-field"><label class="xform-label">Current location</label><select name="current_location_id[]" class="xform-input">' + locationOptionsHtml(row.current_location_id || defaultLocationId) + '</select></div>';
            html += '</div>';
            html += '<div class="patient-row-grid secondary">';
            html += '<div class="xform-field"><label class="xform-label">Dehydrated</label><select name="dehydrated[]" class="xform-input"><option value="No"' + ((row.dehydrated || 'No') === 'No' ? ' selected' : '') + '>No</option><option value="Yes"' + (row.dehydrated === 'Yes' ? ' selected' : '') + '>Yes</option></select></div>';
            html += '<div class="xform-field"><label class="xform-label">Starved</label><select name="starved[]" class="xform-input"><option value="No"' + ((row.starved || 'No') === 'No' ? ' selected' : '') + '>No</option><option value="Yes"' + (row.starved === 'Yes' ? ' selected' : '') + '>Yes</option></select></div>';
            html += '<div class="xform-field"><label class="xform-label">Weight</label><input type="number" step="0.01" name="weight[]" class="xform-input" value="' + esc(row.weight ?? '0') + '"></div>';
            html += '<div class="xform-field"><label class="xform-label">Unit</label><select name="weight_unit[]" class="xform-input"><option value="g"' + ((row.weight_unit || 'g') === 'g' ? ' selected' : '') + '>g</option><option value="kg"' + (row.weight_unit === 'kg' ? ' selected' : '') + '>kg</option></select></div>';
            html += '<div class="xform-field"><label class="xform-label">Meas</label><input type="number" step="0.01" name="measurement[]" class="xform-input" value="' + esc(row.measurement ?? '0') + '"></div>';
            html += '<div class="xform-field"><label class="xform-label">Unit</label><select name="measurement_unit[]" class="xform-input"><option value="mm"' + ((row.measurement_unit || 'mm') === 'mm' ? ' selected' : '') + '>mm</option><option value="cm"' + (row.measurement_unit === 'cm' ? ' selected' : '') + '>cm</option></select></div>';
            html += '</div>';
            html += '<div class="patient-row-grid identifiers">';
            html += '<div class="xform-field"><label class="xform-label">Ringed</label><select name="ringed[]" class="xform-input"><option value="No"' + ((row.ringed || 'No') === 'No' ? ' selected' : '') + '>No</option><option value="Yes"' + (row.ringed === 'Yes' ? ' selected' : '') + '>Yes</option></select></div>';
            html += '<div class="xform-field"><label class="xform-label">Ring number</label><input type="text" name="ring_number[]" class="xform-input" value="' + esc(row.ring_number) + '"></div>';
            html += '<div class="xform-field"><label class="xform-label">Microchipped</label><select name="microchipped[]" class="xform-input"><option value="No"' + ((row.microchipped || 'No') === 'No' ? ' selected' : '') + '>No</option><option value="Yes"' + (row.microchipped === 'Yes' ? ' selected' : '') + '>Yes</option></select></div>';
            html += '<div class="xform-field"><label class="xform-label">Microchip number</label><input type="text" name="microchip_number[]" class="xform-input" value="' + esc(row.microchip_number) + '"></div>';
            html += '</div>';
            html += '</div>';
        }
        document.getElementById('patient-rows-wrap').innerHTML = html;
        lastQuantity = qty;
        generateAssessmentRows(false);
    }

    function generateAssessmentRows(force) {
        const qty = Math.max(1, Math.min(200, parseInt(document.getElementById('quantity').value || '1', 10)));
        const wrap = document.getElementById('assessment-rows-wrap');
        if (!wrap) return;
        if (!force && wrap.querySelectorAll('tbody tr').length === qty) return;

        const existing = [];
        wrap.querySelectorAll('tbody tr').forEach((tr) => {
            existing.push({
                ss_text: tr.querySelector('[name="ss_text[]"]')?.value || '',
                bcs_text: tr.querySelector('[name="bcs_text[]"]')?.value || '',
                presenting_complaint: tr.querySelector('[name="presenting_complaint[]"]')?.value || '',
                hpc: tr.querySelector('[name="hpc[]"]')?.value || '',
                on_examination: tr.querySelector('[name="on_examination[]"]')?.value || ''
            });
        });

        const patientNames = Array.from(document.querySelectorAll('[name="animal_name[]"]')).map((el, i) => el.value || 'Animal ' + (i + 1));
        let html = '<table class="group-patient-table"><thead><tr><th>#</th><th>Patient</th><th>Injury severity</th><th>Body condition</th><th>Presenting complaint</th><th>Triage / history notes</th><th>On examination</th></tr></thead><tbody>';
        for (let i = 0; i < qty; i++) {
            const row = existing[i] || {};
            html += '<tr>';
            html += '<td>' + (i + 1) + '</td>';
            html += '<td>' + esc(patientNames[i]) + '</td>';
            html += '<td><select name="ss_text[]" class="xform-input score-severity">' + optionsHtml(severityOptions, row.ss_text, 'Select...') + '</select><input type="hidden" name="severity_score[]" value="' + esc(scoreValue(severityScoreMap, row.ss_text)) + '"></td>';
            html += '<td><select name="bcs_text[]" class="xform-input score-bcs">' + optionsHtml(bodyConditionOptions, row.bcs_text, 'Select...') + '</select><input type="hidden" name="bc_score[]" value="' + esc(scoreValue(bodyConditionScoreMap, row.bcs_text)) + '"></td>';
            html += '<td><select name="presenting_complaint[]" class="xform-input">' + optionsHtml(presentingComplaintOptions, row.presenting_complaint, 'Select complaint...') + '</select></td>';
            html += '<td><textarea name="hpc[]" class="xform-input" rows="3">' + esc(row.hpc) + '</textarea></td>';
            html += '<td><textarea name="on_examination[]" class="xform-input" rows="3">' + esc(row.on_examination) + '</textarea></td>';
            html += '</tr>';
        }
        html += '</tbody></table>';
        wrap.innerHTML = html;
    }

    function validateStage(stage) {
        if (stage === 1) {
            if (!document.getElementById('animal_species').value.trim()) {
                showStatus('Select an animal species from the search results before continuing.', true);
                return false;
            }
            if (parseInt(document.getElementById('quantity').value || '0', 10) < 1) {
                showStatus('Enter at least one animal.', true);
                return false;
            }
            generateRows(false);
        }
        if (stage === 2) {
            let ok = true;
            document.querySelectorAll('[name="sex[]"]').forEach(el => { if (!el.value) ok = false; });
            if (!ok) {
                showStatus('Select sex for every animal row.', true);
                return false;
            }
            document.querySelectorAll('[name="current_location_id[]"]').forEach(el => { if (!el.value) ok = false; });
            if (!ok) {
                showStatus('Select current location for every animal row.', true);
                return false;
            }
            generateAssessmentRows(false);
        }
        if (stage === 3 && !form.elements.collection_location.value.trim()) {
            showStatus('Collection location is required.', true);
            return false;
        }
        if (stage === 4) generateAssessmentRows(false);
        if (stage === 5) {
            captureSignature();
            if (!document.getElementById('signature_data').value && !document.getElementById('no_signature').checked) {
                showStatus('Record a signature or tick no signature obtained.', true);
                return false;
            }
        }
        return true;
    }

    function setStage(stage) {
        if (stage > currentStage && !validateStage(currentStage)) return;
        currentStage = Math.max(1, Math.min(6, stage));
        document.querySelectorAll('.group-panel').forEach(p => p.classList.toggle('active', parseInt(p.dataset.panel, 10) === currentStage));
        document.querySelectorAll('.group-stage').forEach(btn => {
            const id = parseInt(btn.dataset.stage, 10);
            btn.classList.toggle('is-active', id === currentStage);
            btn.classList.toggle('is-done', id < currentStage);
            const meta = btn.querySelector('.rc-stage-meta');
            if (meta) meta.textContent = id < currentStage ? '100%' : (id === currentStage ? '0%' : '0%');
        });
        document.getElementById('prevStageBtn').style.visibility = currentStage === 1 ? 'hidden' : 'visible';
        document.getElementById('nextStageBtn').style.display = currentStage === 6 ? 'none' : 'inline-block';
        if (currentStage === 6) renderReview();
    }

    function renderReview() {
        captureSignature();
        const names = Array.from(document.querySelectorAll('[name="animal_name[]"]')).map((el, i) => ({
            index: i + 1,
            name: el.value,
            sex: document.querySelectorAll('[name="sex[]"]')[i]?.value || '',
            weight: document.querySelectorAll('[name="weight[]"]')[i]?.value || '0',
            weight_unit: document.querySelectorAll('[name="weight_unit[]"]')[i]?.value || 'g',
            measurement: document.querySelectorAll('[name="measurement[]"]')[i]?.value || '0',
            measurement_unit: document.querySelectorAll('[name="measurement_unit[]"]')[i]?.value || 'mm',
            ringed: document.querySelectorAll('[name="ringed[]"]')[i]?.value || 'No',
            ring_number: document.querySelectorAll('[name="ring_number[]"]')[i]?.value || '',
            microchipped: document.querySelectorAll('[name="microchipped[]"]')[i]?.value || 'No',
            microchip_number: document.querySelectorAll('[name="microchip_number[]"]')[i]?.value || '',
            age_on_admission: document.querySelectorAll('[name="age_on_admission[]"]')[i]?.value || '',
            dehydrated: document.querySelectorAll('[name="dehydrated[]"]')[i]?.value || 'No',
            starved: document.querySelectorAll('[name="starved[]"]')[i]?.value || 'No',
            ss_text: document.querySelectorAll('[name="ss_text[]"]')[i]?.value || '',
            bcs_text: document.querySelectorAll('[name="bcs_text[]"]')[i]?.value || '',
            presenting_complaint: document.querySelectorAll('[name="presenting_complaint[]"]')[i]?.value || ''
        }));
        let html = '<div class="group-summary">';
        html += '<div class="summary-box"><strong>Total animals</strong><br>' + names.length + '</div>';
        html += '<div class="summary-box"><strong>Species</strong><br>' + esc(document.getElementById('animal_species').value) + '</div>';
        html += '<div class="summary-box"><strong>Collection</strong><br>' + esc(collectionLocationWithHouseNumber()) + '</div>';
        html += '</div>';
        html += '<h4>Individual rows</h4><table class="group-patient-table"><thead><tr><th>#</th><th>Name</th><th>Sex</th><th>Age</th><th>Identifiers</th><th>State</th><th>Weight</th><th>Measurement</th><th>Assessment</th></tr></thead><tbody>';
        names.forEach(row => {
            html += '<tr><td>' + row.index + '</td><td>' + esc(row.name) + '</td><td>' + esc(row.sex) + '</td><td>' + esc(row.age_on_admission) + '</td><td>Ring: ' + esc(row.ringed === 'Yes' ? row.ring_number : 'No') + '<br>Chip: ' + esc(row.microchipped === 'Yes' ? row.microchip_number : 'No') + '</td><td>' + esc(row.dehydrated) + ' dehydrated / ' + esc(row.starved) + ' starved</td><td>' + esc(row.weight) + ' ' + esc(row.weight_unit) + '</td><td>' + esc(row.measurement) + ' ' + esc(row.measurement_unit) + '</td><td>' + esc(row.ss_text) + '<br>' + esc(row.bcs_text) + '<br>' + esc(row.presenting_complaint) + '</td></tr>';
        });
        html += '</tbody></table>';
        document.getElementById('review-output').innerHTML = html;
    }

    document.getElementById('nextStageBtn').addEventListener('click', () => setStage(currentStage + 1));
    document.getElementById('prevStageBtn').addEventListener('click', () => setStage(currentStage - 1));
    document.querySelectorAll('.group-stage').forEach(btn => btn.addEventListener('click', () => setStage(parseInt(btn.dataset.stage, 10))));
    document.getElementById('quantity').addEventListener('change', () => {
        generateRows(true);
        generateAssessmentRows(true);
    });
    document.getElementById('patient-rows-wrap').addEventListener('input', () => generateAssessmentRows(true));
    document.getElementById('patient-rows-wrap').addEventListener('change', (e) => {
        if (e.target.matches('.score-age')) {
            const hidden = e.target.closest('.xform-field')?.querySelector('[name="age_score[]"]');
            if (hidden) hidden.value = scoreValue(ageScoreMap, e.target.value);
        }
        generateAssessmentRows(false);
    });
    document.getElementById('assessment-rows-wrap').addEventListener('change', (e) => {
        if (e.target.matches('.score-severity')) {
            const hidden = e.target.parentElement.querySelector('[name="severity_score[]"]');
            if (hidden) hidden.value = scoreValue(severityScoreMap, e.target.value);
        }
        if (e.target.matches('.score-bcs')) {
            const hidden = e.target.parentElement.querySelector('[name="bc_score[]"]');
            if (hidden) hidden.value = scoreValue(bodyConditionScoreMap, e.target.value);
        }
    });

    const speciesInput = document.getElementById('species_search');
    const speciesResults = document.getElementById('species_results');
    let speciesTimer = null;
    speciesInput.addEventListener('input', function () {
        document.getElementById('animal_species').value = '';
        document.getElementById('animal_type').value = '';
        document.getElementById('animal_order').value = '';
        speciesInput.setCustomValidity('Click a species from the search results.');
        speciesResults.innerHTML = '';
        speciesResults.style.display = 'none';
        const q = speciesInput.value.trim();
        if (q.length < 2) return;
        clearTimeout(speciesTimer);
        speciesTimer = setTimeout(() => {
            fetch('controllers/search_species.php?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    if (!Array.isArray(data) || !data.length) return;
                    const ul = document.createElement('ul');
                    data.forEach(item => {
                        const li = document.createElement('li');
                        li.textContent = item.species_display;
                        li.addEventListener('click', () => {
                            speciesInput.value = item.species_name;
                            document.getElementById('animal_species').value = item.species_name;
                            document.getElementById('animal_type').value = item.type_name || '';
                            document.getElementById('animal_order').value = item.order_name || '';
                            speciesInput.setCustomValidity('');
                            speciesResults.style.display = 'none';
                        });
                        ul.appendChild(li);
                    });
                    speciesResults.innerHTML = '';
                    speciesResults.appendChild(ul);
                    speciesResults.style.display = 'block';
                });
        }, 250);
    });

    function postcodeFromAddress(address) {
        return address?.postcode || address?.postal_code || '';
    }

    function collectionLocationWithHouseNumber() {
        const house = document.getElementById('collection_house_number')?.value.trim() || '';
        const address = document.getElementById('collection_location')?.value.trim() || '';
        if (!house || !address) return address;

        const escaped = house.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        if (new RegExp('^' + escaped + '(\\b|\\s|,)', 'i').test(address)) {
            return address;
        }

        return house + ' ' + address;
    }

    function mergeCollectionHouseNumber() {
        const input = document.getElementById('collection_location');
        if (!input) return;
        input.value = collectionLocationWithHouseNumber();
    }

    function setLocationFromNominatim(item) {
        document.getElementById('collection_location').value = item.display_name || '';
        document.getElementById('location_lat').value = item.lat || '';
        document.getElementById('location_long').value = item.lon || '';
        document.getElementById('incident_location_postcode').value = postcodeFromAddress(item.address || {});
    }

    const collectionInput = document.getElementById('collection_location');
    const locationResults = document.getElementById('location_results');
    let locationTimer = null;

    if (collectionInput && locationResults) {
        collectionInput.addEventListener('input', () => {
            const q = collectionInput.value.trim();
            const house = document.getElementById('collection_house_number')?.value.trim() || '';
            const lookupQuery = [house, q].filter(Boolean).join(' ');
            clearTimeout(locationTimer);
            locationResults.innerHTML = '';
            locationResults.style.display = 'none';

            if (q.length < 3) return;

            locationTimer = setTimeout(() => {
                fetch('ajax/nominatim.php?q=' + encodeURIComponent(lookupQuery))
                    .then(r => r.json())
                    .then(data => {
                        locationResults.innerHTML = '';
                        if (!Array.isArray(data) || !data.length) return;

                        data.forEach(item => {
                            const d = document.createElement('div');
                            d.textContent = item.display_name || '';
                            d.style.padding = '6px';
                            d.style.cursor = 'pointer';
                            d.addEventListener('click', () => {
                                setLocationFromNominatim(item);
                                locationResults.style.display = 'none';
                            });
                            locationResults.appendChild(d);
                        });

                        locationResults.style.display = 'block';
                    });
            }, 400);
        });

        document.addEventListener('click', e => {
            if (!locationResults.contains(e.target) && e.target !== collectionInput) {
                locationResults.style.display = 'none';
            }
        });
    }

    const useMyLocationIcon = document.getElementById('useMyLocationIcon');
    if (useMyLocationIcon) {
        useMyLocationIcon.addEventListener('click', () => {
            if (!navigator.geolocation) {
                alert('Geolocation not supported.');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                pos => {
                    const lat = pos.coords.latitude;
                    const lon = pos.coords.longitude;

                    document.getElementById('location_lat').value = lat;
                    document.getElementById('location_long').value = lon;

                    fetch('ajax/nominatim.php?lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lon))
                        .then(r => r.json())
                        .then(data => {
                            if (data && data.display_name) {
                                setLocationFromNominatim(data);
                            }
                        });
                },
                err => alert('Unable to get location: ' + err.message)
            );
        });
    }

    const finderSearch = document.getElementById('finder_search');
    const finderResults = document.getElementById('finder_results');
    let finderTimer = null;

    if (finderSearch && finderResults) {
        finderSearch.addEventListener('input', () => {
            const q = finderSearch.value.trim();
            document.getElementById('finder_name').value = finderSearch.value;
            document.getElementById('finder_id').value = '0';
            finderResults.innerHTML = '';
            finderResults.style.display = 'none';

            if (q.length < 2) return;
            clearTimeout(finderTimer);

            finderTimer = setTimeout(() => {
                fetch('controllers/admissions/search_finder.php?q=' + encodeURIComponent(q))
                    .then(r => r.json())
                    .then(rows => {
                        if (!Array.isArray(rows) || !rows.length) return;

                        rows.forEach(row => {
                            const d = document.createElement('div');
                            d.textContent = row.finder_name + ' (' + row.finder_tel + ')';
                            d.style.padding = '6px';
                            d.style.cursor = 'pointer';
                            d.addEventListener('click', () => {
                                document.getElementById('finder_id').value = row.finder_id;
                                document.getElementById('finder_name').value = row.finder_name;
                                document.getElementById('finder_tel').value = row.finder_tel;
                                finderSearch.value = row.finder_name;
                                finderResults.style.display = 'none';
                            });
                            finderResults.appendChild(d);
                        });

                        finderResults.style.display = 'block';
                    });
            }, 300);
        });

        document.addEventListener('click', e => {
            if (!finderResults.contains(e.target) && e.target !== finderSearch) {
                finderResults.style.display = 'none';
            }
        });
    }

    const showAddFinderBtn = document.getElementById('showAddFinderBtn');
    const addFinderWrapper = document.getElementById('addFinderWrapper');
    const cancelNewFinderBtn = document.getElementById('cancelNewFinderBtn');
    const saveNewFinderBtn = document.getElementById('saveNewFinderBtn');
    const addFinderStatus = document.getElementById('addFinderStatus');

    if (showAddFinderBtn && addFinderWrapper && cancelNewFinderBtn && saveNewFinderBtn) {
        showAddFinderBtn.addEventListener('click', () => {
            addFinderWrapper.style.display = 'block';
        });

        cancelNewFinderBtn.addEventListener('click', () => {
            addFinderWrapper.style.display = 'none';
            addFinderStatus.style.display = 'none';
        });

        saveNewFinderBtn.addEventListener('click', () => {
            const name = document.getElementById('newFinderName').value.trim();
            const tel = document.getElementById('newFinderTel').value.trim();

            if (!name || !tel) {
                addFinderStatus.textContent = 'Name and Telephone required.';
                addFinderStatus.style.color = 'red';
                addFinderStatus.style.display = 'block';
                return;
            }

            const fd = new FormData();
            fd.append('finder_name', name);
            fd.append('finder_tel', tel);

            fetch('controllers/admissions/group_add_finder.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (!data || data.success !== true) {
                        addFinderStatus.textContent = data?.message || 'Unable to add finder.';
                        addFinderStatus.style.color = 'red';
                        addFinderStatus.style.display = 'block';
                        return;
                    }

                    document.getElementById('finder_id').value = data.finder_id;
                    document.getElementById('finder_name').value = name;
                    document.getElementById('finder_tel').value = tel;
                    finderSearch.value = name;

                    addFinderStatus.textContent = 'Finder added successfully.';
                    addFinderStatus.style.color = '#2f6b2f';
                    addFinderStatus.style.display = 'block';

                    setTimeout(() => {
                        addFinderWrapper.style.display = 'none';
                        addFinderStatus.style.display = 'none';
                    }, 1200);
                });
        });
    }

    function weatherDateParts(dateTime) {
        if (!dateTime) return null;
        if (dateTime.includes('T')) {
            const parts = dateTime.split('T');
            return {
                date: parts[0],
                hour: (parts[1] || '00:00').substring(0, 2)
            };
        }

        const parts = dateTime.split(' ');
        return {
            date: parts[0],
            hour: (parts[1] || '00:00:00').substring(0, 2)
        };
    }

    function setWeatherStatus(message, isError) {
        const el = document.getElementById('weatherFetchStatus');
        if (!el) return;
        el.textContent = message;
        el.style.color = isError ? '#a33' : '#2f6b2f';
    }

    function fillWeather(data, idx) {
        const hourly = data.hourly || {};
        const temp = hourly.temperature_2m?.[idx] ?? '';
        const hum = hourly.relative_humidity_2m?.[idx] ?? '';
        const wind = (hourly.wind_speed_10m?.[idx] ?? hourly.windspeed_10m?.[idx] ?? '');
        const rain = hourly.precipitation?.[idx] ?? '';

        document.getElementById('w_temp').value = temp;
        document.getElementById('w_humidity').value = hum;
        document.getElementById('w_wind').value = wind;
        document.getElementById('w_rainfall').value = rain;

        const notes = [];
        if (temp !== '') notes.push(temp + ' C');
        if (hum !== '') notes.push(hum + '% humidity');
        if (wind !== '') notes.push(wind + ' wind');
        if (rain !== '') notes.push(rain + ' mm rainfall');
        document.getElementById('w_freetext').value = notes.length ? 'Approx: ' + notes.join(', ') + '.' : '';
    }

    function fetchWeatherForGroup() {
        const lat = document.getElementById('location_lat').value.trim();
        const lon = document.getElementById('location_long').value.trim();
        const dateTime = document.getElementById('admission_date').value.trim();
        const parts = weatherDateParts(dateTime);
        const btn = document.getElementById('getWeatherBtn');

        if (!lat || !lon) {
            setWeatherStatus('Set collection latitude and longitude first.', true);
            return;
        }

        if (!parts || !parts.date) {
            setWeatherStatus('Set admission date/time first.', true);
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Fetching...';
        setWeatherStatus('', false);

        const requested = 'temperature_2m,relative_humidity_2m,wind_speed_10m,precipitation';
        const archiveUrl =
            'https://archive-api.open-meteo.com/v1/archive' +
            '?latitude=' + encodeURIComponent(lat) +
            '&longitude=' + encodeURIComponent(lon) +
            '&start_date=' + encodeURIComponent(parts.date) +
            '&end_date=' + encodeURIComponent(parts.date) +
            '&hourly=' + encodeURIComponent(requested);

        const forecastUrl =
            'https://api.open-meteo.com/v1/forecast' +
            '?latitude=' + encodeURIComponent(lat) +
            '&longitude=' + encodeURIComponent(lon) +
            '&start_date=' + encodeURIComponent(parts.date) +
            '&end_date=' + encodeURIComponent(parts.date) +
            '&hourly=' + encodeURIComponent(requested);

        function fetchWeatherUrl(url) {
            return fetch(url).then(r => r.json());
        }

        fetchWeatherUrl(archiveUrl)
            .then(data => {
                if (data?.hourly?.time?.length) return data;
                return fetchWeatherUrl(forecastUrl);
            })
            .then(data => {
                const times = data?.hourly?.time || [];
                if (!times.length) {
                    setWeatherStatus('No weather data returned for that date/location.', true);
                    return;
                }

                const target = parts.date + 'T' + parts.hour + ':00';
                let idx = times.indexOf(target);
                if (idx === -1) {
                    idx = times.findIndex(t => t.includes(parts.date + 'T' + parts.hour));
                }
                if (idx === -1) {
                    setWeatherStatus('No weather data available for that hour.', true);
                    return;
                }

                fillWeather(data, idx);
                setWeatherStatus('Weather loaded.', false);
            })
            .catch(() => {
                setWeatherStatus('Error fetching weather data.', true);
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = 'Fetch Weather Data';
            });
    }

    const getWeatherBtn = document.getElementById('getWeatherBtn');
    if (getWeatherBtn) {
        getWeatherBtn.addEventListener('click', fetchWeatherForGroup);
    }

    const canvas = document.getElementById('signature-canvas');
    const ctx = canvas.getContext('2d');
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.strokeStyle = '#000';
    let drawing = false;
    let lastX = 0;
    let lastY = 0;
    function pos(e) {
        const rect = canvas.getBoundingClientRect();
        const evt = e.touches ? e.touches[0] : e;
        return { x: evt.clientX - rect.left, y: evt.clientY - rect.top };
    }
    function start(e) { e.preventDefault(); const p = pos(e); drawing = true; lastX = p.x; lastY = p.y; }
    function move(e) { if (!drawing) return; e.preventDefault(); const p = pos(e); ctx.beginPath(); ctx.moveTo(lastX, lastY); ctx.lineTo(p.x, p.y); ctx.stroke(); lastX = p.x; lastY = p.y; }
    function end(e) { if (!drawing) return; e.preventDefault(); drawing = false; }
    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', move);
    canvas.addEventListener('mouseup', end);
    canvas.addEventListener('mouseleave', end);
    canvas.addEventListener('touchstart', start, { passive: false });
    canvas.addEventListener('touchmove', move, { passive: false });
    canvas.addEventListener('touchend', end);
    document.getElementById('clearSignatureBtn').addEventListener('click', () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        document.getElementById('signature_data').value = '';
    });
    function captureSignature() {
        const blank = document.createElement('canvas');
        blank.width = canvas.width;
        blank.height = canvas.height;
        document.getElementById('signature_data').value = canvas.toDataURL() === blank.toDataURL() ? '' : canvas.toDataURL('image/png');
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        for (let i = 1; i <= 5; i++) {
            if (!validateStage(i)) {
                setStage(i);
                return;
            }
        }
        mergeCollectionHouseNumber();
        const btn = document.getElementById('submitGroupBtn');
        btn.disabled = true;
        btn.textContent = 'Creating...';
        fetch('controllers/admissions/group_submit.php', { method: 'POST', body: new FormData(form) })
            .then(r => r.json())
            .then(data => {
                if (!data || data.success !== true) {
                    const detail = data?.details ? ' ' + data.details : '';
                    showStatus((data?.message || 'Unable to create group admission.') + detail, true);
                    console.error('Group admission failed:', data);
                    btn.disabled = false;
                    btn.textContent = 'Create Group Admission';
                    return;
                }
                showStatus('Group admission created: ' + data.created + ' animals admitted.', false);
                setTimeout(() => { window.location.href = 'patients.php'; }, 900);
            })
            .catch(() => {
                showStatus('Network/JS error while creating group admission.', true);
                btn.disabled = false;
                btn.textContent = 'Create Group Admission';
            });
    });

    generateRows(true);
    generateAssessmentRows(true);
    setStage(1);
})();
</script>

<?= template_admin_footer() ?>
