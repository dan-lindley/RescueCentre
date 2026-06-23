<?php
// ============================================================================
// SECTION 3 – Collection Information (FIELD PERMISSIONS ENABLED)
// ============================================================================

if (!isset($pdo)) {
    die('PDO not available in Section 3');
}

require_once __DIR__ . '/../../operations/permissions.php';

/*
|--------------------------------------------------------------------------
| SECTION 3 FIELD DEFINITIONS
| true  = required to mark complete
| false = optional (tracked but skippable)
|--------------------------------------------------------------------------
*/
$SECTION3_FIELDS = [
    'collection_location' => true,
    'location_lat'        => true,
    'location_long'       => true,
    'finder_name'         => false,
    'finder_tel'          => false,
    'consent_to_update'   => false,
    'passphrase'          => true,
];

// Register field permissions (auto-creates if missing)
registerPermission('admission.collection.collection_location.edit', 'Edit collection location', 'field');
registerPermission('admission.collection.location_lat.edit',        'Edit collection latitude', 'field');
registerPermission('admission.collection.location_long.edit',       'Edit collection longitude', 'field');

registerPermission('admission.finder.finder_name.edit',             'Edit finder name', 'field');
registerPermission('admission.finder.finder_tel.edit',              'Edit finder telephone', 'field');
registerPermission('admission.finder.consent_to_update.edit',       'Edit SMS consent', 'field');
registerPermission('admission.finder.passphrase.edit',              'Edit passphrase', 'field');

// Optional action permission for creating a new finder
registerPermission('admission.finder.add',                          'Add new finder', 'action');

// Strict field control
$can_collection_location = can('admission.collection.collection_location.edit');
$can_lat                = can('admission.collection.location_lat.edit');
$can_long               = can('admission.collection.location_long.edit');

$can_finder_name        = can('admission.finder.finder_name.edit');
$can_finder_tel         = can('admission.finder.finder_tel.edit');
$can_consent            = can('admission.finder.consent_to_update.edit');
$can_passphrase         = can('admission.finder.passphrase.edit');

$can_add_finder         = can('admission.finder.add');

// Finder “search + select” editable only if name OR tel is editable
$can_edit_finder_block  = ($can_finder_name || $can_finder_tel);

// Load existing admission values if editing
$collection_location = $admission['collection_location'] ?? '';
$location_lat        = $admission['location_lat']        ?? '';
$location_long       = $admission['location_long']       ?? '';

$finder_id   = $admission['finder_id']   ?? '';
$finder_name = $admission['finder_name'] ?? '';
$finder_tel  = $admission['finder_tel']  ?? '';

// backend expects consent_to_update
$consent_to_update = $admission['consent_to_update'] ?? ($admission['sms_consent'] ?? 0);

$passphrase  = trim((string)($admission['passphrase']  ?? ''));
$has_stored_passphrase = ($passphrase !== '');

// Fetch 3 passphrases
$words = $pdo->query("SELECT word_1, word_2, word_3 FROM rescue_words ORDER BY RAND() LIMIT 1")
             ->fetch(PDO::FETCH_ASSOC);

$pp1 = $words['word_1'] ?? 'alpha';
$pp2 = $words['word_2'] ?? 'beta';
$pp3 = $words['word_3'] ?? 'gamma';
?>

<div class="rc-card rc-card-muted">
<h3><?= htmlspecialchars(($lang['SECTION'] ?? 'Section') . ' 3 - ' . ($lang['COLLECTION'] ?? 'Collection') . ' & ' . ($lang['FINDER'] ?? 'Finder')) ?></h3>

<form id="section3-form" class="xform"
      data-required-fields="<?= htmlspecialchars(json_encode($SECTION3_FIELDS), ENT_QUOTES, 'UTF-8') ?>"
      onsubmit="event.preventDefault(); document.getElementById('section3-mark-complete').value='0'; mergeAdmissionHouseNumber(); saveSection(3, 'section3-form');">

    <input type="hidden" name="patient_id" value="<?= htmlspecialchars($pid ?? '') ?>">
    <input type="hidden" name="admission_id" value="<?= htmlspecialchars($aid ?? '') ?>">

    <!-- Mark complete flag -->
    <input type="hidden" name="mark_complete" id="section3-mark-complete" value="0">

    <div class="xform-grid">

        <!-- COLLECTION LOCATION -->
        <div class="xform-field span-2">
            <label class="xform-label"><?= htmlspecialchars(($lang['COLLECTION'] ?? 'Collection') . ' ' . ($lang['LOCATION'] ?? 'Location')) ?> *</label>

            <div class="rc-lookup-wrap rc-location-lookup-row">
                <input type="text"
                       id="collection_house_number"
                       class="xform-input <?= $can_collection_location ? '' : 'is-readonly' ?>"
                       placeholder="<?= htmlspecialchars(($lang['HOUSE'] ?? 'House') . ' / ' . ($lang['NUMBER_ABBR'] ?? 'No')) ?>"
                       autocomplete="off"
                       <?= $can_collection_location ? '' : 'readonly' ?>>

                <div class="rc-location-address-field">
                    <input type="text"
                           id="collection_location"
                           name="collection_location"
                           class="xform-input <?= $can_collection_location ? '' : 'is-readonly' ?>"
                           placeholder="<?= htmlspecialchars($lang['ADDRESS_PLACEHOLDER'] ?? 'Start typing an address...') ?>"
                           autocomplete="off"
                           value="<?= htmlspecialchars($collection_location) ?>"
                           <?= $can_collection_location ? '' : 'readonly' ?>>

            <span id="useMyLocationIcon" style="display:none;"
                  class="rc-note"
                  title="<?= htmlspecialchars(($lang['USE'] ?? 'Use') . ' ' . strtolower($lang['CURRENT'] ?? 'current') . ' ' . strtolower($lang['LOCATION'] ?? 'location')) ?>"
                  aria-label="<?= htmlspecialchars(($lang['USE'] ?? 'Use') . ' ' . strtolower($lang['CURRENT'] ?? 'current') . ' ' . strtolower($lang['LOCATION'] ?? 'location')) ?>">📍</span>

                    <div id="location_results" class="rc-autocomplete-results">
                    </div>
                </div>
            </div>
        </div>

        <!-- LAT -->
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['LATITUDE'] ?? 'Latitude') ?> *</label>
            <input type="text"
                   id="location_lat"
                   name="location_lat"
                   class="xform-input <?= $can_lat ? '' : 'is-readonly' ?>"
                   value="<?= htmlspecialchars($location_lat) ?>"
                   <?= $can_lat ? '' : 'readonly' ?>>
        </div>

        <!-- LONG -->
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['LONGITUDE'] ?? 'Longitude') ?> *</label>
            <input type="text"
                   id="location_long"
                   name="location_long"
                   class="xform-input <?= $can_long ? '' : 'is-readonly' ?>"
                   value="<?= htmlspecialchars($location_long) ?>"
                   <?= $can_long ? '' : 'readonly' ?>>
        </div>

    </div>

    <div class="xform-grid" style="margin-top: 8px;">
        <div class="xform-field">
            <button type="button"
                    id="useCurrentLocationBtn"
                    class="btn"
                    title="<?= htmlspecialchars(($lang['USE'] ?? 'Use') . ' ' . strtolower($lang['CURRENT'] ?? 'current') . ' ' . strtolower($lang['LOCATION'] ?? 'location')) ?>"
                    aria-label="<?= htmlspecialchars(($lang['USE'] ?? 'Use') . ' ' . strtolower($lang['CURRENT'] ?? 'current') . ' ' . strtolower($lang['LOCATION'] ?? 'location')) ?>">
                <?= htmlspecialchars(($lang['USE'] ?? 'Use') . ' ' . strtolower($lang['CURRENT'] ?? 'current') . ' ' . strtolower($lang['LOCATION'] ?? 'location')) ?>
            </button>
        </div>
    </div>

    <div class="xform-grid">

        <!-- FINDER SEARCH -->
        <div class="xform-field span-2">
            <label class="xform-label"><?= htmlspecialchars(($lang['FINDER'] ?? 'Finder') . ' ' . ($lang['NAME'] ?? 'Name')) ?></label>

            <input type="text"
                   id="finder_search"
                   class="xform-input <?= $can_edit_finder_block ? '' : 'is-readonly' ?>"
                   placeholder="<?= htmlspecialchars($lang['ADM_FINDER_SEARCH_PLACEHOLDER'] ?? 'Type to search finder...') ?>"
                   autocomplete="off"
                   value="<?= htmlspecialchars($finder_name) ?>"
                   <?= $can_edit_finder_block ? '' : 'readonly' ?>>

            <div id="finder_results" class="rc-autocomplete-results">
            </div>
        </div>

        <!-- FINDER TEL -->
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars(($lang['FINDER'] ?? 'Finder') . ' ' . ($lang['TELEPHONE'] ?? 'Telephone')) ?></label>
            <input type="text"
                   id="finder_tel"
                   name="finder_tel"
                   class="xform-input <?= $can_finder_tel ? '' : 'is-readonly' ?>"
                   autocomplete="off"
                   value="<?= htmlspecialchars($finder_tel) ?>"
                   <?= $can_finder_tel ? '' : 'readonly' ?>>
        </div>

        <div class="xform-field">
            <label class="xform-label">&nbsp;</label>
            <?php if ($can_add_finder && $can_edit_finder_block): ?>
                <button type="button" class="btn" id="showAddFinderBtn">
                    + <?= htmlspecialchars(($lang['ADD'] ?? 'Add') . ' ' . ($lang['NEW'] ?? 'New') . ' ' . ($lang['FINDER'] ?? 'Finder')) ?>
                </button>
            <?php else: ?>
                <button type="button" class="btn is-readonly" disabled>
                    + <?= htmlspecialchars(($lang['ADD'] ?? 'Add') . ' ' . ($lang['NEW'] ?? 'New') . ' ' . ($lang['FINDER'] ?? 'Finder')) ?>
                </button>
            <?php endif; ?>
        </div>

        <input type="hidden" id="finder_id"   name="finder_id"   value="<?= htmlspecialchars($finder_id) ?>">
        <input type="hidden" id="finder_name" name="finder_name" value="<?= htmlspecialchars($finder_name) ?>">

    </div>

    <div class="xform-grid">
        <div class="xform-field span-4">
            <div id="addFinderWrapper" class="rc-panel rc-card-muted">

                <h3><?= htmlspecialchars(($lang['ADD'] ?? 'Add') . ' ' . ($lang['NEW'] ?? 'New') . ' ' . ($lang['FINDER'] ?? 'Finder')) ?></h3>

                <div class="xform-grid">
                    <div class="xform-field">
                        <label class="xform-label"><?= htmlspecialchars(($lang['FINDER'] ?? 'Finder') . ' ' . ($lang['NAME'] ?? 'Name')) ?> *</label>
                        <input type="text" id="newFinderName" class="xform-input">
                    </div>

                    <div class="xform-field">
                        <label class="xform-label"><?= htmlspecialchars(($lang['FINDER'] ?? 'Finder') . ' ' . ($lang['TELEPHONE'] ?? 'Telephone')) ?> *</label>
                        <input type="text" id="newFinderTel" class="xform-input">
                    </div>
                </div>

                <div class="xform-actions">
                    <button type="button" class="btn green" id="saveNewFinderBtn"><?= htmlspecialchars(($lang['SAVE'] ?? 'Save') . ' ' . ($lang['FINDER'] ?? 'Finder')) ?></button>
                    <button type="button" class="btn" id="cancelNewFinderBtn"><?= htmlspecialchars($lang['CANCEL'] ?? 'Cancel') ?></button>
                </div>

                <div id="addFinderStatus" class="rc-note"></div>
            </div>
        </div>
    </div>

    <div class="xform-grid">

        <!-- SMS Consent -->
        <div class="xform-field span-2">
            <label class="xform-label"><?= htmlspecialchars($lang['SMS_CONSENT'] ?? 'SMS Consent') ?></label>

            <?php if (!$can_consent): ?>
                <input type="hidden" name="consent_to_update" value="<?= htmlspecialchars((string)$consent_to_update) ?>">
            <?php endif; ?>

            <select id="consent_to_update"
                    name="consent_to_update"
                    class="xform-input <?= $can_consent ? '' : 'is-readonly' ?>"
                    <?= $can_consent ? '' : 'disabled' ?>>
                <option value="0" <?= ((int)$consent_to_update === 0) ? 'selected' : '' ?>><?= htmlspecialchars($lang['NO'] ?? 'No') ?></option>
                <option value="1" <?= ((int)$consent_to_update === 1) ? 'selected' : '' ?>><?= htmlspecialchars($lang['YES'] ?? 'Yes') ?></option>
            </select>
        </div>

        <!-- Passphrase -->
        <div class="xform-field span-2">
            <label class="xform-label"><?= htmlspecialchars($lang['PASSPHRASE'] ?? 'Passphrase') ?> *</label>
            <div class="rc-inline-options">

            <?php if ($has_stored_passphrase): ?>
                <input type="hidden" name="passphrase" value="<?= htmlspecialchars($passphrase) ?>">
                <input type="text"
                       class="xform-input is-readonly"
                       value="<?= htmlspecialchars($passphrase) ?>"
                       readonly>
                <div class="rc-note"><?= htmlspecialchars($lang['ADM_PASSPHRASE_LOCKED'] ?? 'Stored passphrase. This cannot be changed after first save.') ?></div>
            <?php elseif (!$can_passphrase): ?>
                <input type="hidden" name="passphrase" value="<?= htmlspecialchars($passphrase) ?>">
                <input type="text"
                       class="xform-input is-readonly"
                       value="<?= htmlspecialchars($passphrase) ?>"
                       readonly>
            <?php else: ?>
                <?php
                $passphrase = $_POST['passphrase'] ?? $pp1;
                ?>

                <?php foreach ([$pp1, $pp2, $pp3] as $p): ?>
                    <label>
                        <input type="radio"
                               name="passphrase"
                               value="<?= htmlspecialchars($p) ?>"
                               <?= ($passphrase === $p ? 'checked' : '') ?>>
                        <?= htmlspecialchars($p) ?>
                    </label>
                <?php endforeach; ?>
            <?php endif; ?>
            </div>
        </div>

    </div>

    <div class="xform-actions">
        <br>
        <button type="submit" class="btn green"><?= htmlspecialchars(($lang['SAVE'] ?? 'Save') . ' ' . ($lang['SECTION'] ?? 'Section') . ' 3') ?></button>

        <button type="button" class="btn"
                id="markSection3Complete">
            <?= htmlspecialchars(($lang['MARK'] ?? 'Mark') . ' ' . ($lang['SECTION'] ?? 'Section') . ' ' . strtolower($lang['COMPLETE'] ?? 'complete')) ?>
        </button>
    </div>

</form>
</div>

<script>
/* =========================== MARK COMPLETE ============================== */
(function(){
    const btn = document.getElementById('markSection3Complete');
    const flag = document.getElementById('section3-mark-complete');
    if (!btn || !flag) return;

    btn.addEventListener('click', () => {
        flag.value = '1';
        mergeAdmissionHouseNumber();
        saveSection(3, 'section3-form');
        flag.value = '0';
    });
})();

/* =========================== FINDER SEARCH ============================== */
(function() {
    const canEditFinder = <?= $can_edit_finder_block ? 'true' : 'false' ?>;
    if (!canEditFinder) return;

    const fs  = document.getElementById('finder_search');
    const box = document.getElementById('finder_results');
    let timer = null;

    if (!fs || !box) return;

    fs.addEventListener('input', () => {
        const q = fs.value.trim();
        box.innerHTML = '';
        box.style.display = 'none';
        if (q.length < 2) return;

        if (timer) clearTimeout(timer);

        timer = setTimeout(() => {
            fetch('controllers/admissions/search_finder.php?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(rows => {
                    if (!rows.length) return;

                    rows.forEach(r => {
                        const d = document.createElement('div');
                        d.textContent = r.finder_name + " (" + r.finder_tel + ")";
                        d.style.padding = "6px";
                        d.style.cursor = "pointer";

                        d.onclick = () => {
                            document.getElementById('finder_id').value   = r.finder_id;
                            document.getElementById('finder_name').value = r.finder_name;
                            document.getElementById('finder_tel').value  = r.finder_tel;

                            fs.value = r.finder_name;
                            box.style.display = 'none';
                        };

                        box.appendChild(d);
                    });

                    box.style.display = 'block';
                });
        }, 300);
    });

    document.addEventListener('click', e => {
        if (!box.contains(e.target) && e.target !== fs) box.style.display = 'none';
    });
})();

/* =========================== ADD NEW FINDER ============================= */
(function(){
    const canAddFinder = <?= ($can_add_finder && $can_edit_finder_block) ? 'true' : 'false' ?>;
    if (!canAddFinder) return;

    const showBtn = document.getElementById('showAddFinderBtn');
    const wrap = document.getElementById('addFinderWrapper');
    const cancelBtn = document.getElementById('cancelNewFinderBtn');
    const saveBtn = document.getElementById('saveNewFinderBtn');

    if (!showBtn || !wrap || !cancelBtn || !saveBtn) return;

    showBtn.onclick = () => wrap.style.display = 'block';

    cancelBtn.onclick = () => {
        wrap.style.display = 'none';
        const s = document.getElementById('addFinderStatus');
        if (s) s.style.display = 'none';
    };

    saveBtn.onclick = () => {
        const name = document.getElementById('newFinderName').value.trim();
        const tel  = document.getElementById('newFinderTel').value.trim();
        const status = document.getElementById('addFinderStatus');

        if (!name || !tel) {
            status.textContent = <?= json_encode($lang['ADM_FINDER_REQUIRED'] ?? 'Name and Telephone required.') ?>;
            status.style.color = "red";
            status.style.display = "block";
            return;
        }

        const fd = new FormData();
        fd.append('sid', 33);
        fd.append('finder_name', name);
        fd.append('finder_tel',  tel);
        fd.append('patient_id', '<?= (int)$pid ?>');

        fetch('controllers/admissions/save_section.php', { method:'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    status.textContent = data.message;
                    status.style.color = "red";
                    status.style.display = "block";
                    return;
                }

                document.getElementById('finder_id').value   = data.finder_id;
                document.getElementById('finder_name').value = name;
                document.getElementById('finder_tel').value  = tel;
                document.getElementById('finder_search').value = name;

                status.textContent = <?= json_encode($lang['ADM_FINDER_ADDED'] ?? 'Finder added successfully.') ?>;
                status.style.color = "#2f6b2f";
                status.style.display = "block";

                setTimeout(() => {
                    wrap.style.display = 'none';
                    status.style.display = 'none';
                }, 1200);
            });
    };
})();

/* =========================== ADDRESS AUTOCOMPLETE ======================= */
(function() {
    const canEditLocation = <?= $can_collection_location ? 'true' : 'false' ?>;
    if (!canEditLocation) return;

    const input = document.getElementById('collection_location');
    const houseNumber = document.getElementById('collection_house_number');
    const results = document.getElementById('location_results');
    let timer = null;

    if (!input || !results) return;

    input.addEventListener('input', () => {
        const q = input.value.trim();
        const lookupQuery = [houseNumber ? houseNumber.value.trim() : '', q].filter(Boolean).join(' ');
        if (timer) clearTimeout(timer);
        if (q.length < 3) {
            results.style.display = 'none';
            return;
        }

        timer = setTimeout(() => {
            fetch("ajax/nominatim.php?q=" + encodeURIComponent(lookupQuery))
                .then(r => r.json())
                .then(data => {
                    results.innerHTML = "";
                    if (!Array.isArray(data) || !data.length) {
                        results.style.display = "none";
                        return;
                    }

                    data.forEach(item => {
                        const d = document.createElement("div");
                        d.textContent = item.display_name;
                        d.style.padding = "6px";
                        d.style.cursor = "pointer";

                        d.onclick = () => {
                            input.value = item.display_name;

                            const latEl = document.getElementById("location_lat");
                            const lonEl = document.getElementById("location_long");
                            if (latEl) latEl.value = item.lat ?? '';
                            if (lonEl) lonEl.value = item.lon ?? '';

                            results.style.display = "none";
                        };

                        results.appendChild(d);
                    });

                    results.style.display = "block";
                });
        }, 400);
    });

    document.addEventListener("click", e => {
        if (!results.contains(e.target) && e.target !== input) results.style.display = "none";
    });
})();

function mergeAdmissionHouseNumber() {
    const house = document.getElementById('collection_house_number');
    const input = document.getElementById('collection_location');
    if (!house || !input) return;

    const number = house.value.trim();
    const address = input.value.trim();
    if (!number || !address) return;

    const escaped = number.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const alreadyPrefixed = new RegExp('^' + escaped + '(\\b|\\s|,)', 'i').test(address);
    if (!alreadyPrefixed) {
        input.value = number + ' ' + address;
    }
}

/* =========================== USE MY LOCATION ============================ */
(function(){
    const icon = document.getElementById("useCurrentLocationBtn") || document.getElementById("useMyLocationIcon");
    if (!icon) return;

    const canEditLocation = <?= $can_collection_location ? 'true' : 'false' ?>;
    if (!canEditLocation) {
        icon.style.opacity = "0.2";
        icon.style.cursor = "not-allowed";
        return;
    }

    icon.onclick = () => {
        if (!navigator.geolocation) {
            alert(<?= json_encode($lang['ADM_GEOLOCATION_UNSUPPORTED'] ?? 'Geolocation not supported.') ?>);
            return;
        }

        navigator.geolocation.getCurrentPosition(
            pos => {
                const lat = pos.coords.latitude;
                const lon = pos.coords.longitude;

                const latEl = document.getElementById("location_lat");
                const lonEl = document.getElementById("location_long");
                if (latEl) latEl.value = lat;
                if (lonEl) lonEl.value = lon;

                fetch("ajax/nominatim.php?lat=" + lat + "&lon=" + lon)
                    .then(r => r.json())
                    .then(data => {
                        if (data.display_name) {
                            document.getElementById("collection_location").value = data.display_name;
                        }
                    });
            },
            err => alert(<?= json_encode($lang['ADM_LOCATION_UNAVAILABLE'] ?? 'Unable to get location:') ?> + ' ' + err.message)
        );
    };
})();
</script>
