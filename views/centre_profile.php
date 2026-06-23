<?php

if (!defined('APP_LOADED')) exit;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// CSRF token setup
if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

echo '<div class="content-title">
    <div class="title">
        <div class="txt">
            <h2>' . htmlspecialchars($lang['SETTINGS_CENTRE_DETAILS_TITLE']) . '</h2>
            <p>' . htmlspecialchars($lang['SETTINGS_EDIT_CENTRE_DETAILS']) . '</p>
        </div>
    </div>
</div>';

$centre_id = isset($GLOBALS['centre_id']) ? (int)$GLOBALS['centre_id'] : 0;

if (!$centre_id) {
    die(htmlspecialchars($lang['SETTINGS_NO_CENTRE_SELECTED']));
}

// Load centre
$stmt = $pdo->prepare("SELECT * FROM rescue_centres WHERE rescue_id = :cid LIMIT 1");
$stmt->execute([':cid' => $centre_id]);
$centre = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$centre) {
    die(htmlspecialchars($lang['SETTINGS_CENTRE_NOT_FOUND']));
}

// Load animal orders and species
$oStmt = $pdo->query("SELECT animal_order FROM rescue_animal_orders ORDER BY animal_order ASC");
$orders = $oStmt->fetchAll(PDO::FETCH_COLUMN);

$tStmt = $pdo->query("SELECT type_name, animal_order FROM rescue_animal_types ORDER BY type_name ASC");
$types = $tStmt->fetchAll(PDO::FETCH_ASSOC);

$speciesAccepted = $centre['species_accepted'] ?? "";
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($lang['SETTINGS_CENTRE_PROFILE']) ?></title>

<!-- Expose CSRF token for any AJAX scripts -->
<meta name="csrf-token" content="<?= htmlspecialchars($csrf_token, ENT_QUOTES) ?>">

<style>
    .centre-id-box {
        padding: 8px 12px;
        display: inline-block;
        font-weight: bold;
        margin-bottom: 20px;
    }

    /* Species selector UI */
    .species-wrapper { position: relative; }
    .species-suggestions {
        list-style: none;
        margin: 0;
        padding: 0;
        display: none;
        border: 1px solid #ccc;
        background: #fff;
        max-height: 200px;
        overflow-y: auto;
        position: absolute;
        width: 100%;
        z-index: 100;
    }
    .species-suggestions li {
        padding: 6px;
        cursor: pointer;
    }
    .species-suggestions li:hover {
        background: #eee;
    }
    .species-tags { margin-top: 6px; }
    .species-tag {
        display: inline-block;
        padding: 3px 6px;
        margin: 2px;
        border: 1px solid #888;
        border-radius: 4px;
        font-size: 0.85em;
    }
    .species-tag button {
        background: none;
        border: none;
        cursor: pointer;
        margin-left: 6px;
    }

    /* Address autocomplete UI (copied from Section 3) */
    .addr-wrapper { position: relative; }
    #addr_results {
        background:white;
        border:1px solid #ccc;
        max-height:200px;
        overflow-y:auto;
        position:absolute;
        width:100%;
        display:none;
        z-index:9999;
    }
    #addr_results div {
        padding:6px;
        cursor:pointer;
    }
    #addr_results div:hover {
        background:#eee;
    }
</style>
</head>

<body>
<div class="rc-alert amber centre-id-box">
    <?= htmlspecialchars($lang['SETTINGS_CENTRE_NUMBER']) ?>: #<?= htmlspecialchars($centre['rescue_id']) ?>
</div>

<form action="controllers/centre_config_handler.php" method="post" class="xform">
    <!-- CSRF -->
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES) ?>">

    <!-- ========================= -->
    <!-- SECTION: CENTRE DETAILS   -->
    <!-- ========================= -->
    <h3><?= htmlspecialchars($lang['SETTINGS_CENTRE_DETAILS']) ?></h3>
    <div class="xform-grid">

        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['SETTINGS_CENTRE_NAME']) ?></label>
            <input type="text" name="rescue_name" class="xform-input"
                   value="<?= htmlspecialchars($centre['rescue_name'] ?? '') ?>">
        </div>

        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['SETTINGS_EMAIL']) ?></label>
            <input type="email" name="email" class="xform-input"
                   value="<?= htmlspecialchars($centre['email'] ?? '') ?>">
        </div>

        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['SETTINGS_OFFICE_TEL']) ?></label>
            <input type="text" name="office_tel" class="xform-input"
                   value="<?= htmlspecialchars($centre['office_tel'] ?? '') ?>">
        </div>

        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['SETTINGS_MOBILE']) ?></label>
            <input type="text" name="mobile" class="xform-input"
                   value="<?= htmlspecialchars($centre['mobile'] ?? '') ?>">
        </div>

        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['SETTINGS_24H_TEL']) ?></label>
            <input type="text" name="24_hour" class="xform-input"
                   value="<?= htmlspecialchars($centre['24_hour'] ?? '') ?>">
        </div>
    </div>


    <!-- ========================= -->
    <!-- SECTION: ADDRESS LOOKUP   -->
    <!-- ========================= -->
    <br><h3><?= htmlspecialchars($lang['ADDRESS']) ?></h3>
    <div class="xform-grid">

        <!-- LIVE SEARCH BAR -->
        <div class="xform-field" style="grid-column: span 2;">
            <label class="xform-label"><?= htmlspecialchars($lang['SETTINGS_ADDRESS_SEARCH']) ?></label>
            <div class="addr-wrapper">
                <input type="text" id="address_search" class="xform-input"
                       placeholder="<?= htmlspecialchars($lang['SETTINGS_ADDRESS_SEARCH_PLACEHOLDER']) ?>"
                       autocomplete="off">

                <div id="addr_results"></div>
            </div>
        </div>

        <!-- Address Line 1 -->
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['SETTINGS_ADDRESS_LINE_1']) ?></label>
            <input type="text" id="address_line_one" name="address_line_one"
                   class="xform-input"
                   value="<?= htmlspecialchars($centre['address_line_one'] ?? '') ?>">
        </div>

        <!-- Address Line 2 -->
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['SETTINGS_ADDRESS_LINE_2']) ?></label>
            <input type="text" id="address_line_two" name="address_line_two"
                   class="xform-input"
                   value="<?= htmlspecialchars($centre['address_line_two'] ?? '') ?>">
        </div>

        <!-- City -->
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['CITY']) ?></label>
            <input type="text" id="city" name="city" class="xform-input"
                   value="<?= htmlspecialchars($centre['city'] ?? '') ?>">
        </div>

        <!-- County / State -->
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['SETTINGS_COUNTY'] ?? 'County') ?></label>
            <input type="text" id="county" name="county" class="xform-input"
                   value="<?= htmlspecialchars($centre['county'] ?? '') ?>">
        </div>

        <!-- Postcode -->
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['POSTCODE']) ?></label>
            <input type="text" id="postcode" name="postcode" class="xform-input"
                   value="<?= htmlspecialchars($centre['postcode'] ?? '') ?>">
        </div>

        <!-- LAT -->
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['LATITUDE']) ?></label>
            <input type="text" id="centre_lat" name="centre_lat"
                   class="xform-input" readonly
                   value="<?= htmlspecialchars($centre['centre_lat'] ?? '') ?>">
        </div>

        <!-- LONG -->
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['LONGITUDE']) ?></label>
            <input type="text" id="centre_long" name="centre_long"
                   class="xform-input" readonly
                   value="<?= htmlspecialchars($centre['centre_long'] ?? '') ?>">
        </div>

        <!-- Combined hidden coordinates -->
        <input type="hidden" id="coordinates" name="coordinates"
               value="<?= htmlspecialchars($centre['coordinates'] ?? '') ?>">

        <!-- Country Code -->
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['SETTINGS_COUNTRY_CODE'] ?? 'Country Code') ?></label>
            <input type="text" id="country_code" name="country_code" class="xform-input"
                   maxlength="2"
                   value="<?= htmlspecialchars($centre['country_code'] ?? '') ?>">
        </div>
    </div>


    <!-- ========================= -->
    <!-- SECTION: ADMISSIONS       -->
    <!-- ========================= -->
    <br><h3><?= htmlspecialchars($lang['ADMISSIONS']) ?></h3>
    <div class="xform-grid">

        <!-- SPECIES SELECTOR -->
        <div class="xform-field" style="grid-column: span 2;">
            <label class="xform-label"><?= htmlspecialchars($lang['SETTINGS_SPECIES_ACCEPTED']) ?></label>

            <div class="species-wrapper">
                <input type="text" id="species_input" class="xform-input"
                       placeholder="<?= htmlspecialchars($lang['SETTINGS_SPECIES_PLACEHOLDER']) ?>">
                <ul id="species_suggestions" class="species-suggestions"></ul>
            </div>

            <div id="species_selected" class="species-tags"></div>

            <input type="hidden" id="species_accepted" name="species_accepted"
                   value="<?= htmlspecialchars($speciesAccepted ?? '') ?>">
        </div>

        <!-- Opening Hours -->
        <div class="xform-field" style="grid-column: span 2;">
            <label class="xform-label"><?= htmlspecialchars($lang['SETTINGS_OPENING_HOURS']) ?></label>
            <textarea name="opening_hours" class="xform-input" rows="3"><?= htmlspecialchars($centre['opening_hours'] ?? '') ?></textarea>
        </div>

        <!-- Accepting Admissions -->
        <div class="xform-field" style="grid-column: span 2;">
            <label class="xform-label"><?= htmlspecialchars($lang['SETTINGS_ACCEPTING_ADMISSIONS']) ?></label>
            <input type="text" name="accepting_admissions" class="xform-input"
                   value="<?= htmlspecialchars($centre['accepting_admissions'] ?? '') ?>">
        </div>

        <!-- Closed Message -->
        <div class="xform-field" style="grid-column: span 2;">
            <label class="xform-label"><?= htmlspecialchars($lang['SETTINGS_CLOSED_MESSAGE']) ?></label>
            <textarea name="closed_message" class="xform-input" rows="3"><?= htmlspecialchars($centre['closed_message'] ?? '') ?></textarea>
        </div>

    </div>

    <button type="submit" class="btn blue"><?= htmlspecialchars($lang['SETTINGS_SAVE_CHANGES']) ?></button>
</form>


<!-- ======================================================= -->
<!-- JAVASCRIPT — SPECIES SELECTOR (RESTORED BASELINE)       -->
<!-- ======================================================= -->
<script>
const orders = <?= json_encode($orders) ?>;
const types  = <?= json_encode($types) ?>;

// Build suggestion list
const speciesList = [];
const allLabel = <?= json_encode($lang['TABLE_ALL'] ?? 'All') ?>;
orders.forEach(o => speciesList.push({label: allLabel + " " + o, value:o + ":All"}));
types.forEach(t => speciesList.push({
    label: t.type_name + (t.animal_order ? " (" + t.animal_order + ")" : ""),
    value: t.type_name
}));

const sInput = document.getElementById("species_input");
const sBox   = document.getElementById("species_suggestions");
const sTags  = document.getElementById("species_selected");
const sField = document.getElementById("species_accepted");

let selected = [];

// Init from DB
(function() {
    const raw = sField.value || "";
    raw.split(",").forEach(v => {
        v = v.trim();
        if (v) selected.push(v);
    });
    renderTags();
})();

function renderTags() {
    sTags.innerHTML = "";
    selected.forEach(v => {
        const tag = document.createElement("span");
        tag.className = "species-tag";
        tag.textContent = v;

        const btn = document.createElement("button");
        btn.textContent = "×";
        btn.onclick = () => {
            selected = selected.filter(x => x !== v);
            renderTags();
        };

        tag.appendChild(btn);
        sTags.appendChild(tag);
    });

    sField.value = selected.join(", ");
}

sInput.addEventListener("input", () => {
    const q = sInput.value.toLowerCase().trim();
    sBox.innerHTML = "";

    if (!q) {
        sBox.style.display = "none";
        return;
    }

    speciesList
        .filter(s => s.label.toLowerCase().includes(q))
        .slice(0, 20)
        .forEach(item => {
            const li = document.createElement("li");
            li.textContent = item.label;
            li.onclick = () => {
                if (!selected.includes(item.value)) {
                    selected.push(item.value);
                    renderTags();
                }
                sInput.value = "";
                sBox.style.display = "none";
            };
            sBox.appendChild(li);
        });

    sBox.style.display = sBox.children.length ? "block" : "none";
});

// Add custom values via Enter or comma
sInput.addEventListener("keydown", e => {
    if (e.key === "Enter" || e.key === ",") {
        e.preventDefault();
        const v = sInput.value.trim();
        if (v && !selected.includes(v)) {
            selected.push(v);
            renderTags();
        }
        sInput.value = "";
        sBox.style.display = "none";
    }
});

document.addEventListener("click", e => {
    if (!sBox.contains(e.target) && e.target !== sInput) {
        sBox.style.display = "none";
    }
});
</script>


<!-- ======================================================= -->
<!-- JAVASCRIPT — ADDRESS LOOKUP (SECTION 3 LOGIC REUSED)    -->
<!-- ======================================================= -->
<script>
(function() {
    const input = document.getElementById('address_search');
    const box   = document.getElementById('addr_results');

    const line1 = document.getElementById('address_line_one');
    const line2 = document.getElementById('address_line_two');
    const city  = document.getElementById('city');
    const county = document.getElementById('county');
    const pc    = document.getElementById('postcode');

    const latEl = document.getElementById('centre_lat');
    const lonEl = document.getElementById('centre_long');
    const coord = document.getElementById('coordinates');
    const countryCode = document.getElementById('country_code');

    let timer = null;

    [line1, line2, city, county, pc].forEach(field => {
        if (!field) return;
        field.addEventListener('input', () => {
            countryCode.value = '';
        });
    });

    input.addEventListener('input', () => {
        const q = input.value.trim();

        box.style.display = 'none';
        box.innerHTML = '';

        if (timer) clearTimeout(timer);
        if (q.length < 3) return;

        timer = setTimeout(() => {
            fetch("/ajax/nominatim.php?q=" + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    box.innerHTML = "";
                    if (!Array.isArray(data) || !data.length) return;

                    data.forEach(item => {
                        const d = document.createElement("div");
                        d.textContent = item.display_name;
                        d.onclick = () => {
                            applyAddress(item);
                            box.style.display = "none";
                        };
                        box.appendChild(d);
                    });

                    box.style.display = "block";
                });
        }, 320);
    });

    function applyAddress(item) {
        const a = item.address || {};

        // Build Line 1
        const house = a.house_number ? a.house_number + " " : "";
        const road  = a.road || "";
        line1.value = (house + road).trim();

        // Line 2
        line2.value = a.suburb || a.neighbourhood || "";

        // City
        city.value =
            a.city ||
            a.town ||
            a.village ||
            a.hamlet ||
            "";

        if (county) {
            county.value =
                a.county ||
                a.state_district ||
                a.state ||
                a.province ||
                a.region ||
                "";
        }

        // Postcode
        pc.value = a.postcode || "";

        // Lat/Long
        latEl.value = item.lat || "";
        lonEl.value = item.lon || "";
        coord.value = latEl.value && lonEl.value ? latEl.value + ", " + lonEl.value : "";
        countryCode.value = a.country_code ? String(a.country_code).toUpperCase() : "";

        input.value = item.display_name;
    }

    document.addEventListener('click', e => {
        if (!box.contains(e.target) && e.target !== input) {
            box.style.display = "none";
        }
    });
})();
</script>

</body>
</html>
