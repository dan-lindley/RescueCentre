<?php
// views/medication/meds_profiles.php
// NO permissions / NO CSRF yet — per request

if (!isset($pdo)) {
    echo '<div class="rc-alert red">' . ($lang['DATABASE_CONNECTION_MISSING'] ?? 'Database connection missing ($pdo not set).') . '</div>';
    return;
}

// Centre context
$cid = 0;
if (isset($centre_id) && (int)$centre_id > 0) {
    $cid = (int)$centre_id;
} elseif (!empty($GLOBALS['centre_id'])) {
    $cid = (int)$GLOBALS['centre_id'];
}
if ($cid <= 0) {
    echo '<div class="rc-alert red">' . ($lang['CENTRE_CONTEXT_MISSING'] ?? 'Centre context missing (centre_id not set).') . '</div>';
    return;
}

try {
    // Existing profiles
    $profilesStmt = $pdo->prepare("
        SELECT 
            sm.medication_profile_id,
            sm.medication AS medication_id,
            rm.medication_name,
            rm.common_name,
            sm.stock_form_id,
            sf.form_code,
            sm.concentration_dose,
            sm.concentration_dose_type,
            sm.concentration_volume,
            sm.concentration_volume_type,
            sm.pack_quantity,
            sm.reorder_level,
            sm.use_within,
            sm.mgml
        FROM rescue_stock_medication sm
        INNER JOIN rescue_medications rm
            ON sm.medication = rm.medication_id
        INNER JOIN rescue_stock_forms sf
            ON sm.stock_form_id = sf.stock_form_id
        WHERE sm.centre_id = :cid
        ORDER BY rm.medication_name ASC, sm.medication_profile_id DESC
    ");
    $profilesStmt->execute([':cid' => $cid]);
    $profiles = $profilesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Master meds
    $meds = $pdo->query("
        SELECT medication_id, medication_name, common_name
        FROM rescue_medications
        ORDER BY medication_name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Stock forms
    $forms = $pdo->query("
        SELECT stock_form_id, form_code
        FROM rescue_stock_forms
        ORDER BY form_code ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    echo '<div class="rc-alert red"><strong>'
        . ($lang['MED_PROFILES_FAILED_TO_LOAD'] ?? 'Medication Profiles failed to load:')
        . '</strong><br>'
        . htmlspecialchars($e->getMessage()) .
        '</div>';
    return;
}

// Build autocomplete list (label + id)
$med_lookup = array_map(function ($m) {
    $label = trim((string)($m['common_name'] ?? ''));
    $name  = trim((string)($m['medication_name'] ?? ''));
    if ($label !== '' && $name !== '' && strcasecmp($label, $name) !== 0) {
        $label = $label . ' — ' . $name;
    } elseif ($label === '') {
        $label = $name;
    }
    return [
        'id'    => (int)$m['medication_id'],
        'label' => $label
    ];
}, $meds);
?>

<div class="content-title">
    <div class="title">
        <div class="txt">
            <h2><?= $lang['MED_STOCK_TAB_PROFILES'] ?? 'Medication Profiles' ?></h2>
            <p><?= $lang['MED_PROFILES_INTRO'] ?? 'Set up the medicines your centre routinely keeps so stock can be added and managed consistently.' ?></p>
        </div>
    </div>
</div>

<div class="content-block">
    <p class="rc-muted" style="margin-bottom:10px;">
        <?= $lang['MED_PROFILES_DESC_1'] ?? 'Medication profiles define the medicines your centre keeps, and how they are supplied (strength, reference volume, pack size and form).' ?>
    </p>
    <p class="rc-muted" style="margin-bottom:0;">
        <?= $lang['MED_PROFILES_DESC_2'] ?? 'Once a profile is set up, you can add stock and administer medication without re-entering the same details each time.' ?>
        <span class="rc-chip blue"><?= $lang['MED_PROFILES_KICKER'] ?? 'Create profile once → add stock → administer safely.' ?></span>
    </p>
</div>

<div class="content-block">
    <h3><?= $lang['MED_PROFILES_CURRENT'] ?? 'Current Profiles' ?></h3>

    <div class="rc-table-scroll">
    <table class="rc-table row-hover">
        <thead>
            <tr>
                <th><?= $lang['MEDICATION'] ?? 'Medication' ?></th>
                <th><?= $lang['FORM'] ?? 'Form' ?></th>
                <th><?= $lang['CONCENTRATION'] ?? 'Concentration' ?></th>
                <th class="rc-table-actions"><?= $lang['PACK_SIZE'] ?? 'Pack Size' ?></th>
                <th class="rc-table-actions"><?= $lang['USE_WITHIN'] ?? 'Use Within' ?></th>
                <th class="rc-table-actions"><?= $lang['REORDER'] ?? 'Reorder' ?></th>
                <th class="rc-table-actions"><?= $lang['MG_ML'] ?? 'mg/ml' ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($profiles)): ?>
                <tr><td colspan="7" class="rc-muted"><?= $lang['MED_PROFILES_NONE'] ?? 'No medication profiles set up yet.' ?></td></tr>
            <?php else: ?>
                <?php foreach ($profiles as $p): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($p['common_name'] ?: $p['medication_name']) ?></strong>
                            <?php if (!empty($p['common_name']) && !empty($p['medication_name']) && strcasecmp($p['common_name'], $p['medication_name']) !== 0): ?>
                                <span class="rc-muted"><?= htmlspecialchars($p['medication_name']) ?></span>
                            <?php endif; ?>
                        </td>

                        <td><?= htmlspecialchars($p['form_code']) ?></td>

                        <td>
                            <?= htmlspecialchars($p['concentration_dose']) ?> <?= htmlspecialchars($p['concentration_dose_type']) ?>
                            <span class="rc-muted"><?= $lang['IN'] ?? 'in' ?> <?= htmlspecialchars($p['concentration_volume']) ?> <?= htmlspecialchars($p['concentration_volume_type']) ?></span>
                        </td>

                        <td class="rc-table-actions"><?= htmlspecialchars($p['pack_quantity']) ?></td>
                        <td class="rc-table-actions"><?= htmlspecialchars($p['use_within']) ?><?= $lang['DAYS_ABBR'] ?? 'd' ?></td>
                        <td class="rc-table-actions"><?= htmlspecialchars($p['reorder_level']) ?></td>
                        <td class="rc-table-actions"><?= htmlspecialchars($p['mgml']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<div class="content-block">
    <h3><?= $lang['ADD_MEDICATION_PROFILE'] ?? 'Add Medication Profile' ?></h3>
    <p class="rc-muted" style="margin-top:0;">
        <?= $lang['MED_PROFILES_ADD_HELP'] ?? 'Start typing to select a medication from the master list, then set the centre-specific profile values.' ?>
    </p>

    <form method="post" action="controllers/medication/medication_handler.php" class="xform" autocomplete="off">
        <input type="hidden" name="action" value="add_med_profile">

        <div class="xform-grid">

            <!-- Medication autocomplete -->
            <div class="xform-field span-2">
                <label class="xform-label"><?= $lang['MEDICATION'] ?? 'Medication' ?></label>

                <div style="position:relative;">
                    <input type="text"
                           name="medication_lookup"
                           class="xform-input js-med-master"
                           placeholder="<?= $lang['MED_PROFILES_START_TYPING'] ?? 'Start typing medication…' ?>"
                           autocomplete="off"
                           required>

                    <input type="hidden"
                           name="medication_id"
                           class="js-med-master-id">
                </div>

                <small class="rc-muted"><?= $lang['MED_PROFILES_MASTER_LIST_HELP'] ?? 'Select from the master list (common name / medication name).' ?></small>
            </div>

            <div class="xform-field span-2">
                <label class="xform-label"><?= $lang['STOCK_FORM'] ?? 'Stock Form' ?></label>
                <select name="stock_form_id" class="xform-input" required>
                    <option value=""><?= $lang['SELECT_FORM'] ?? 'Select form…' ?></option>
                    <?php foreach ($forms as $f): ?>
                        <option value="<?= (int)$f['stock_form_id'] ?>">
                            <?= htmlspecialchars($f['form_code']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="xform-field">
                <label class="xform-label"><?= $lang['CONCENTRATION_DOSE'] ?? 'Concentration Dose' ?></label>
                <input type="number" step="0.001" name="concentration_dose" id="prof_conc_dose" class="xform-input" required>
            </div>

            <div class="xform-field">
                <label class="xform-label"><?= $lang['DOSE_TYPE'] ?? 'Dose Type' ?></label>
                <select name="concentration_dose_type" id="prof_conc_dose_type" class="xform-input" required>
                    <option value="mg">mg</option>
                    <option value="g">g</option>
                    <option value="mcg">mcg</option>
                </select>
            </div>

            <div class="xform-field">
                <label class="xform-label"><?= $lang['CONCENTRATION_VOLUME'] ?? 'Concentration Volume' ?></label>
                <input type="number" step="0.001" name="concentration_volume" id="prof_conc_vol" class="xform-input" required>
            </div>

            <div class="xform-field">
                <label class="xform-label"><?= $lang['VOLUME_TYPE'] ?? 'Volume Type' ?></label>
                <select name="concentration_volume_type" id="prof_conc_vol_type" class="xform-input" required>
                    <option value="ml">ml</option>
                    <option value="g">g</option>
                </select>
            </div>

            <div class="xform-field">
                <label class="xform-label">
                    <?= $lang['PACK_SIZE'] ?? 'Pack Size' ?>
                    <span class="rc-muted">(<?= $lang['PACK_SIZE_HINT'] ?? 'e.g. bottle size / tablets per pack' ?>)</span>
                </label>
                <input type="number" step="0.001" name="pack_quantity" class="xform-input" required>
                <small class="rc-muted"><?= $lang['PACK_SIZE_EXAMPLES'] ?? 'Examples: 100ml bottle, 28 tablets, 50g tube.' ?></small>
            </div>

            <div class="xform-field">
                <label class="xform-label"><?= $lang['REORDER_LEVEL'] ?? 'Reorder Level' ?></label>
                <input type="number" step="0.001" name="reorder_level" class="xform-input">
            </div>

            <div class="xform-field">
                <label class="xform-label"><?= $lang['USE_WITHIN_DAYS'] ?? 'Use Within (days)' ?></label>
                <input type="number" step="1" name="use_within" class="xform-input" value="0">
            </div>

            <div class="xform-field">
                <label class="xform-label"><?= $lang['MG_ML_AUTO'] ?? 'mg/ml (auto)' ?></label>
                <input type="text" name="mgml" id="prof_mgml" class="xform-input" readonly>
            </div>

        </div>

        <input type="hidden" name="centre_id" value="<?= (int)$cid ?>">
        <input type="hidden" name="user_id" value="<?= (int)($GLOBALS['user_id'] ?? 0) ?>">

        <div class="xform-actions">
            <button type="submit" class="btn blue"><?= $lang['ADD_MEDICATION_PROFILE'] ?? 'Add Medication Profile' ?></button>
        </div>
    </form>
</div>

<script>
/* Master medication autocomplete (ID + LABEL) */
(function () {
    const items = <?= json_encode($med_lookup, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const map = new Map();
    items.forEach(i => { if (i.label) map.set(i.label.toLowerCase(), i.id); });

    function attachAutocomplete(input, hidden) {
        let box;

        function closeBox() { if (box) box.remove(); box = null; }

        function render(matches) {
            closeBox();
            box = document.createElement('div');
            box.className = 'rc-autocomplete-results';
            box.style.display = 'block';

            matches.slice(0, 15).forEach(m => {
                const row = document.createElement('div');
                row.className = 'rc-autocomplete-option';
                row.textContent = m.label;
                row.style.cursor = 'pointer';
                row.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    input.value = m.label;
                    hidden.value = m.id;
                    closeBox();
                });
                box.appendChild(row);
            });

            input.parentElement.appendChild(box);
        }

        function syncHiddenFromExactMatch() {
            const val = (input.value || '').trim().toLowerCase();
            hidden.value = map.get(val) || '';
        }

        input.addEventListener('input', () => {
            const q = (input.value || '').trim().toLowerCase();
            hidden.value = '';
            if (!q) { closeBox(); return; }
            const matches = items.filter(i => i.label.toLowerCase().includes(q));
            if (!matches.length) { closeBox(); return; }
            render(matches);
        });

        input.addEventListener('blur', () => {
            setTimeout(() => { syncHiddenFromExactMatch(); closeBox(); }, 150);
        });

        input.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeBox(); });
    }

    document.querySelectorAll('.js-med-master').forEach(input => {
        const hidden = input.parentElement.querySelector('.js-med-master-id');
        if (hidden) attachAutocomplete(input, hidden);
    });
})();

/* mg/ml calculator (profile form) */
(function(){
    const doseEl = document.getElementById('prof_conc_dose');
    const doseTypeEl = document.getElementById('prof_conc_dose_type');
    const volEl = document.getElementById('prof_conc_vol');
    const volTypeEl = document.getElementById('prof_conc_vol_type');
    const outEl = document.getElementById('prof_mgml');

    function toMg(val, unit){
        const n = parseFloat(val);
        if (!isFinite(n)) return 0;
        if (unit === 'g') return n * 1000;
        if (unit === 'mcg') return n / 1000;
        return n;
    }

    function calc(){
        const doseMg = toMg(doseEl.value, doseTypeEl.value);
        const vol = parseFloat(volEl.value);
        const volType = volTypeEl.value;

        if (!doseMg || !vol || !isFinite(vol) || vol <= 0) { outEl.value = ''; return; }
        if (volType !== 'ml') { outEl.value = ''; return; }

        outEl.value = (doseMg / vol).toFixed(6);
    }

    ['input','change'].forEach(ev => {
        doseEl.addEventListener(ev, calc);
        doseTypeEl.addEventListener(ev, calc);
        volEl.addEventListener(ev, calc);
        volTypeEl.addEventListener(ev, calc);
    });
})();
</script>
