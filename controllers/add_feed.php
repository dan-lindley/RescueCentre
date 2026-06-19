<?php
// controllers/add_feed.php
// Reusable Add Feed form (4-column layout + slider + live consumed)
// Supports solids measured in grams OR units (e.g. 34 mealworms)
// Assumes $patient_id, $admission_id, $centre_id, $pdo are available from viewpatient wrapper.

if (!isset($patient_id, $centre_id, $pdo)) {
    echo '<div class="alert-box alert-red" style="margin-bottom:12px;"><strong>'
        . htmlspecialchars($lang['FEEDING'])
        . '</strong><br>'
        . htmlspecialchars($lang['PATIENT_CONTEXT_MISSING'])
        . '</div>';
    return;
}

// Fetch centre-enabled diet items
$stmt = $pdo->prepare("
    SELECT
        cdi.centre_diet_item_id,
        di.name,
        di.type,
        di.default_unit
    FROM rescue_centre_diet_items cdi
    JOIN rescue_diet_items di
        ON di.diet_item_id = cdi.diet_item_id
    WHERE cdi.centre_id = ?
      AND cdi.is_enabled = 1
    ORDER BY di.name
");
$stmt->execute([(int)$centre_id]);
$dietItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<form method="post"
      action="controllers/form_handler.php"
      class="xform">

    <input type="hidden" name="add_feed_form" value="1">
    <input type="hidden" name="audit_action" value="Feed event added">

    <input type="hidden" name="patient_id" value="<?= (int)$patient_id ?>">
    <input type="hidden" name="admission_id" value="<?= (int)$admission_id ?>">
    <input type="hidden" name="centre_id" value="<?= (int)$centre_id ?>">

    <!-- Locked feed type -->
    <input type="hidden" name="feed_type" id="feed_type">

    <!-- Slider posts remaining_percent; numeric posts remaining_value -->
    <input type="hidden" name="remaining_percent" id="remaining_percent" value="">

    <div class="xform-grid" style="grid-template-columns: repeat(4, 1fr); align-items:end;">

        <!-- ROW 1: date/time | diet item (span 2) | feed type -->
        <div class="xform-field" style="grid-column: span 1;">
            <label><?= htmlspecialchars($lang['DATE'] . ' / ' . $lang['TIME']) ?></label>
            <input type="datetime-local"
                   name="feed_at"
                   class="xform-input"
                   value="<?= date('Y-m-d\TH:i') ?>"
                   required>
        </div>

        <div class="xform-field" style="grid-column: span 2;">
            <label><?= htmlspecialchars($lang['DIET_ITEM']) ?></label>
            <select name="centre_diet_item_id"
                    id="centre_diet_item_id"
                    class="xform-input"
                    required>
                <option value="">— <?= htmlspecialchars($lang['SELECT'] . ' ' . strtolower($lang['DIET_ITEM'])) ?> —</option>
                <?php foreach ($dietItems as $item): ?>
                    <option value="<?= (int)$item['centre_diet_item_id'] ?>"
                            data-type="<?= htmlspecialchars($item['type']) ?>"
                            data-unit="<?= htmlspecialchars($item['default_unit']) ?>">
                        <?= htmlspecialchars($item['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="xform-field" style="grid-column: span 1;">
            <label><?= htmlspecialchars($lang['FEED_TYPE']) ?></label>
            <input type="text"
                   id="feed_type_display"
                   class="xform-input"
                   readonly
                   placeholder="<?= htmlspecialchars($lang['SELECT'] . ' ' . strtolower($lang['DIET_ITEM'])) ?>">
        </div>

        <!-- ROW 2: offered | estimated | remaining (span 2) -->
        <div class="xform-field" style="grid-column: span 1;">
            <label><?= htmlspecialchars($lang['AMOUNT_OFFERED']) ?> <span id="unit_label_offered" style="opacity:0.8;"></span></label>
            <input type="number"
                   step="0.01"
                   name="offered_value"
                   id="offered_value"
                   class="xform-input"
                   required>
        </div>

        <div class="xform-field" id="estimated_wrap" style="grid-column: span 1;">
            <label style="display:block;">&nbsp;</label>
            <label style="margin:0;">
                <input type="checkbox"
                       name="is_estimated"
                       id="is_estimated"
                       value="1">
                <?= htmlspecialchars($lang['ESTIMATED']) ?>
            </label>
        </div>

        <div class="xform-field" style="grid-column: span 2;">
            <!-- Numeric remaining -->
            <div id="remaining_value_wrap">
                <label><?= htmlspecialchars(ucfirst($lang['MED_STOCK_REMAINING'])) ?> <span id="unit_label_remaining" style="opacity:0.8;"></span></label>
                <input type="number"
                       step="0.01"
                       name="remaining_value"
                       id="remaining_value"
                       class="xform-input"
                       value="">
            </div>

            <!-- Slider remaining (estimated solids only) -->
            <div id="remaining_slider_wrap" style="display:none; margin-top:6px;">
                <label><?= htmlspecialchars(ucfirst($lang['MED_STOCK_REMAINING']) . ' (' . strtolower($lang['ESTIMATED']) . ')') ?></label>

                <div style="display:flex; align-items:center; gap:10px;">
                    <span style="font-size:0.75rem; opacity:0.85; white-space:nowrap;"><?= htmlspecialchars($lang['EMPTY']) ?></span>

                    <input type="range"
                           id="remaining_slider"
                           min="0"
                           max="100"
                           step="5"
                           value="0"
                           style="width:100%;">

                    <span style="font-size:0.75rem; opacity:0.85; white-space:nowrap;"><?= htmlspecialchars($lang['FULL']) ?></span>
                </div>

                <div style="margin-top:6px; font-size:0.75rem; opacity:0.9;">
                    <?= htmlspecialchars(ucfirst($lang['MED_STOCK_REMAINING'])) ?>: <strong><span id="remaining_slider_text">0</span>%</strong>
                </div>
            </div>
        </div>

        <!-- ROW 3: consumed (span 4) -->
        <div class="xform-field" style="grid-column: span 4;">
            <div class="alert-box alert-grey" style="padding:8px 12px; margin:0;">
                <strong><?= htmlspecialchars($lang['CONSUMED']) ?>:</strong>
                <span id="consumed_preview">—</span>
            </div>
        </div>

        <!-- ROW 4: notes (span 4) -->
        <div class="xform-field" style="grid-column: span 4;">
            <label><?= htmlspecialchars($lang['NOTES_OPTIONAL']) ?></label>
            <input type="text"
                   name="notes"
                   class="xform-input"
                   placeholder="<?= htmlspecialchars($lang['FEED_NOTES_PLACEHOLDER']) ?>">
        </div>

        <!-- ROW 5: buttons -->
        <div class="xform-field" style="grid-column: span 1;">
            <button type="submit"
                    class="btn green"
                    id="btn_save"
                    style="width:100%;">
                <?= htmlspecialchars($lang['SAVE'] . ' ' . $lang['FEEDING']) ?>
            </button>
        </div>

        <div class="xform-field" style="grid-column: span 1;">
            <button type="submit"
                    name="feed_refused"
                    value="1"
                    class="btn orange"
                    id="btn_refused"
                    style="width:100%;">
                <?= htmlspecialchars($lang['REFUSED']) ?>
            </button>
        </div>

        <div class="xform-field" style="grid-column: span 1;">
            <button type="submit"
                    name="feed_skipped"
                    value="1"
                    class="btn red"
                    id="btn_skipped"
                    style="width:100%;">
                <?= htmlspecialchars($lang['SKIPPED']) ?>
            </button>
        </div>

        <div class="xform-field" style="grid-column: span 1;">
            <!-- spacer -->
        </div>

    </div>

</form>

<script>
(function () {
    const dietSelect   = document.getElementById('centre_diet_item_id');

    const typeHidden   = document.getElementById('feed_type');
    const typeDisplay  = document.getElementById('feed_type_display');

    const offeredInput = document.getElementById('offered_value');

    const estWrap      = document.getElementById('estimated_wrap');
    const estCheckbox  = document.getElementById('is_estimated');

    const remValWrap   = document.getElementById('remaining_value_wrap');
    const remValInput  = document.getElementById('remaining_value');

    const remSliderWrap = document.getElementById('remaining_slider_wrap');
    const remSlider     = document.getElementById('remaining_slider');
    const remSliderText = document.getElementById('remaining_slider_text');
    const remPercentHidden = document.getElementById('remaining_percent');

    const unitLabelOffered  = document.getElementById('unit_label_offered');
    const unitLabelRemaining = document.getElementById('unit_label_remaining');

    const consumedPreview = document.getElementById('consumed_preview');

    const btnRefused = document.getElementById('btn_refused');
    const btnSkipped = document.getElementById('btn_skipped');

    let currentType = '';
    let currentUnit = '';

    function unitDisplay(unit) {
        if (!unit) return '';
        if (unit === 'unit') return 'units';
        return unit; // g, ml
    }

    function setUnits(unit) {
        currentUnit = unit || '';
        const u = unitDisplay(currentUnit);
        const suffix = u ? `(${u})` : '';
        unitLabelOffered.textContent = suffix ? ' ' + suffix : '';
        unitLabelRemaining.textContent = suffix ? ' ' + suffix : '';
    }

    function setType(type) {
        currentType = type || '';
        typeHidden.value = currentType;

        if (!currentType) {
            typeDisplay.value = '';
            typeDisplay.placeholder = <?= json_encode($lang['SELECT'] . ' ' . strtolower($lang['DIET_ITEM'])) ?>;
        } else {
            typeDisplay.value = currentType.charAt(0).toUpperCase() + currentType.slice(1);
        }

        // Estimated only for solids (grams or units)
        if (currentType === 'solid') {
            estCheckbox.disabled = false;
            estWrap.style.opacity = 1;
        } else if (currentType === 'liquid') {
            estCheckbox.checked = false;
            estCheckbox.disabled = true;
            estWrap.style.opacity = 0.6;
            switchEstimatedUI(false);
        } else {
            estCheckbox.checked = false;
            estCheckbox.disabled = true;
            estWrap.style.opacity = 0.6;
            switchEstimatedUI(false);
        }
    }

    function switchEstimatedUI(isEstimated) {
        if (isEstimated) {
            remValWrap.style.display = 'none';
            remSliderWrap.style.display = 'block';

            remValInput.value = '';
            remSliderText.textContent = remSlider.value;
            remPercentHidden.value = remSlider.value;
        } else {
            remValWrap.style.display = 'block';
            remSliderWrap.style.display = 'none';
            remPercentHidden.value = '';
        }
        updateConsumedPreview();
    }

    function num(v) {
        const n = parseFloat(v);
        return isNaN(n) ? 0 : n;
    }

    function fmt(n) {
        const s = n.toFixed(2);
        return s.replace(/\.?0+$/, '');
    }

    function clamp(n, min, max) {
        return Math.max(min, Math.min(max, n));
    }

    function updateConsumedPreview() {
        const offered = num(offeredInput.value);

        if (!dietSelect.value || offered <= 0) {
            consumedPreview.textContent = '—';
            return;
        }

        let consumed = 0;

        if (estCheckbox.checked && currentType === 'solid') {
            const pct = clamp(num(remSlider.value), 0, 100);
            const remaining = offered * (pct / 100);
            consumed = offered - remaining;
        } else {
            const remaining = clamp(num(remValInput.value), 0, offered);
            consumed = offered - remaining;
        }

        if (consumed < 0) consumed = 0;

        const u = unitDisplay(currentUnit);
        consumedPreview.textContent = fmt(consumed) + (u ? u : '');
    }

    function updateFromDiet() {
        const opt = dietSelect.options[dietSelect.selectedIndex];
        if (!opt || !opt.dataset.type) {
            setType('');
            setUnits('');
            estCheckbox.checked = false;
            estCheckbox.disabled = true;
            switchEstimatedUI(false);
            updateConsumedPreview();
            return;
        }

        const type = opt.dataset.type;
        const unit = opt.dataset.unit;

        setType(type);
        setUnits(unit);

        if (type === 'solid') {
            switchEstimatedUI(estCheckbox.checked);
        } else {
            estCheckbox.checked = false;
            switchEstimatedUI(false);
        }

        updateConsumedPreview();
    }

    function handleEstimatedToggle() {
        if (currentType !== 'solid') {
            estCheckbox.checked = false;
            switchEstimatedUI(false);
            return;
        }
        switchEstimatedUI(estCheckbox.checked);
    }

    function handleRefusedClick() {
        if (!dietSelect.value) return;

        const offered = num(offeredInput.value);
        if (offered <= 0) return;

        if (estCheckbox.checked && currentType === 'solid') {
            remSlider.value = 100;
            remSliderText.textContent = '100';
            remPercentHidden.value = 100;
        } else {
            remValInput.value = offeredInput.value;
        }
        updateConsumedPreview();
    }

    function handleSkippedClick() {
        offeredInput.disabled = true;
        remValInput.disabled = true;
        remSlider.disabled = true;
        estCheckbox.disabled = true;
        dietSelect.disabled = true;
    }

    dietSelect.addEventListener('change', updateFromDiet);
    estCheckbox.addEventListener('change', handleEstimatedToggle);

    offeredInput.addEventListener('input', updateConsumedPreview);
    remValInput.addEventListener('input', updateConsumedPreview);

    remSlider.addEventListener('input', function() {
        remSliderText.textContent = remSlider.value;
        remPercentHidden.value = remSlider.value;
        updateConsumedPreview();
    });

    btnRefused.addEventListener('click', handleRefusedClick);
    btnSkipped.addEventListener('click', handleSkippedClick);

    updateFromDiet();
})();
</script>
