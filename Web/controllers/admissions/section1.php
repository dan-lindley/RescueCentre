<?php
// controllers/admissions/section1.php
// Stage 1 – Animal details (rescue_patients)

// -----------------------------------------------------------------------------
// Single-species prefill (UX convenience only)
// If centre has single_species_prefill enabled + default species set,
// then prefill species fields ONLY when patient has no species yet.
// -----------------------------------------------------------------------------
$centre_id = (int)($GLOBALS['centre_id'] ?? 0);

$centre_single_species_prefill = 0;
$centre_default_species = '';

$SECTION1_FIELDS = [
    'sex'            => true,
    'animal_species' => true,
];

if ($centre_id > 0) {
    $stmt = $pdo->prepare("
        SELECT single_species_prefill, single_species_default_species
        FROM rescue_centre_meta
        WHERE centre_id = ?
        LIMIT 1
    ");
    $stmt->execute([$centre_id]);
    $cfg = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cfg) {
        $centre_single_species_prefill = (int)($cfg['single_species_prefill'] ?? 0);
        $centre_default_species = trim((string)($cfg['single_species_default_species'] ?? ''));
    }
}

// Determine initial species value (do not overwrite existing patient data)
$patient_species = (string)($patient['animal_species'] ?? '');
$initial_species = $patient_species;

if ($initial_species === '' && $centre_single_species_prefill === 1 && $centre_default_species !== '') {
    $initial_species = $centre_default_species;
}

?>
    <div class="rc-card rc-card-muted">
<h3><?= htmlspecialchars(($lang['SECTION'] ?? 'Section') . ' 1 - ' . ($lang['ANIMAL'] ?? 'Animal') . ' ' . ($lang['DETAILS'] ?? 'Details')) ?></h3>
<p><?= htmlspecialchars($lang['ADM_SECTION_1_INTRO'] ?? 'Enter the basic details for this patient.') ?></p>

<form id="section1-form" class="xform"
      data-required-fields="<?= htmlspecialchars(json_encode($SECTION1_FIELDS), ENT_QUOTES, 'UTF-8') ?>"
      onsubmit="event.preventDefault(); saveSection(1, 'section1-form');">


    <input type="hidden" name="sid" value="1">
    <input type="hidden" name="patient_id"
           id="patient_id"
           value="<?= $patient['patient_id'] ?? '' ?>">

    <div class="xform-grid">

        <!-- NAME -->
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['NAME'] ?? 'Name') ?></label>
            <input type="text" name="name" class="xform-input"
                   value="<?= htmlspecialchars($patient['name'] ?? '') ?>"
                   placeholder="<?= htmlspecialchars($lang['NOT_COMPLETED'] ?? 'Not completed') ?>">
        </div>

        <!-- SEX -->
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['SEX'] ?? 'Sex') ?> <span class="req">*</span></label>
            <?php
            $sexOptions = [
                'Male' => $lang['MALE'] ?? 'Male',
                'Female' => $lang['FEMALE'] ?? 'Female',
                'Female (lactating)' => $lang['FEMALE_LACT'] ?? 'Lactating Female',
                'Female (pregnant)' => $lang['FEMALE_PREG'] ?? 'Pregnant Female',
                'Undetermined' => $lang['UNDETERMINED'] ?? 'Undetermined',
            ];
            $currentSex = $patient['sex'] ?? '';
            ?>
            <select name="sex" class="xform-input">
                <option value=""><?= htmlspecialchars(($lang['SELECT'] ?? 'Select') . ' ' . ($lang['SEX'] ?? 'Sex') . '...') ?></option>
                <?php foreach ($sexOptions as $value => $label): ?>
                    <option value="<?= htmlspecialchars($value) ?>"
                        <?= ($value === $currentSex ? 'selected' : '') ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- APPROX DOB -->
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['ADM_APPROX_DOB'] ?? 'Approx DOB') ?></label>
            <input type="date" name="approx_dob" class="xform-input"
                   value="<?= htmlspecialchars($patient['approx_dob'] ?? '') ?>">
        </div>

        <!-- SPECIES SEARCH -->
        <div class="xform-field span-2">
            <label class="xform-label"><?= htmlspecialchars($lang['SPECIES'] ?? 'Species') ?> <span class="req">*</span></label>
            <input type="text" id="species_search"
                   class="xform-input" data-required-field="animal_species" autocomplete="off"
                   placeholder="<?= htmlspecialchars($lang['ADM_SPECIES_SEARCH_PLACEHOLDER'] ?? 'Start typing common or scientific name...') ?>"
                   value="<?= htmlspecialchars($initial_species) ?>"
                   required>
            <div id="species_results" class="rc-autocomplete-results"></div>
            <?php if ($patient_species === '' && $centre_single_species_prefill === 1 && $centre_default_species !== ''): ?>
                <div class="rc-note">
                    <?= htmlspecialchars($lang['ADM_SPECIES_PREFILLED'] ?? 'Prefilled from centre default - you can change this.') ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- HIDDEN FIELDS -->
        <input type="hidden" name="animal_species" id="animal_species"
               value="<?= htmlspecialchars($initial_species) ?>">

        <input type="hidden" name="animal_type" id="animal_type"
               value="<?= htmlspecialchars($patient['animal_type'] ?? '') ?>">

        <input type="hidden" name="animal_order" id="animal_order"
               value="<?= htmlspecialchars($patient['animal_order'] ?? '') ?>">

        <!-- RINGED -->
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['RINGED'] ?? 'Ringed') ?></label>
            <?php $ringVal = $patient['ringed'] ?? 'No'; ?>
            <select name="ringed" class="xform-input">
                <option value="No"  <?= $ringVal==='No'?'selected':'' ?>><?= htmlspecialchars($lang['NO'] ?? 'No') ?></option>
                <option value="Yes" <?= $ringVal==='Yes'?'selected':'' ?>><?= htmlspecialchars($lang['YES'] ?? 'Yes') ?></option>
            </select>
        </div>

        <!-- RING NUMBER -->
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars(($lang['RING'] ?? 'Ring') . ' ' . ($lang['NUMBER'] ?? 'Number')) ?></label>
            <input type="text" name="ring_number" class="xform-input"
                   value="<?= htmlspecialchars($patient['ring_number'] ?? '') ?>">
        </div>

        <!-- MICROCHIPPED -->
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['MICROCHIP'] ?? 'Microchip') ?></label>
            <?php $chipVal = $patient['microchipped'] ?? 'No'; ?>
            <select name="microchipped" class="xform-input">
                <option value="No"  <?= $chipVal==='No'?'selected':'' ?>><?= htmlspecialchars($lang['NO'] ?? 'No') ?></option>
                <option value="Yes" <?= $chipVal==='Yes'?'selected':'' ?>><?= htmlspecialchars($lang['YES'] ?? 'Yes') ?></option>
            </select>
        </div>

        <!-- MICROCHIP NUMBER -->
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars(($lang['MICROCHIP'] ?? 'Microchip') . ' ' . ($lang['NUMBER'] ?? 'Number')) ?></label>
            <input type="text" name="microchip_number" class="xform-input"
                   value="<?= htmlspecialchars($patient['microchip_number'] ?? '') ?>">
        </div>

    </div><!-- /xform-grid -->

    <div class="xform-actions">
        <br><button type="submit" class="btn green">
    <?= htmlspecialchars(($lang['SAVE'] ?? 'Save') . ' ' . ($lang['SECTION'] ?? 'Section') . ' 1') ?>
</button>

    </div>
</form>
                </div>


<div id="add_species_panel">
  <div class="rc-card rc-card-muted">
    <h4><?= htmlspecialchars(($lang['ADD'] ?? 'Add') . ' ' . ($lang['NEW'] ?? 'New') . ' ' . ($lang['SPECIES'] ?? 'Species')) ?></h4>

    <div class="xform-grid">
      <div class="xform-field span-2">
        <label class="xform-label"><?= htmlspecialchars(($lang['SEARCH'] ?? 'Search') . ' ' . strtolower($lang['EXTERNAL'] ?? 'external')) ?></label>
        <input type="text" id="external_species_q" class="xform-input" autocomplete="off"
               placeholder="<?= htmlspecialchars($lang['ADM_EXTERNAL_SPECIES_PLACEHOLDER'] ?? 'Type a common name... e.g. zebra') ?>">
      </div>
      <div class="xform-field">
        <button type="button" class="btn" id="external_species_search_btn"><?= htmlspecialchars($lang['SEARCH'] ?? 'Search') ?></button>
      </div>
    </div>

    <div id="external_species_table_wrap"></div>

    <div class="rc-inline-list">
      <button type="button" class="btn" id="external_prev"><?= htmlspecialchars($lang['PAG_PREV_TEXT'] ?? 'Prev') ?></button>
      <span id="external_page_label"><?= htmlspecialchars($lang['PAGE'] ?? 'Page') ?> 1</span>
      <button type="button" class="btn" id="external_next"><?= htmlspecialchars($lang['PAG_NEXT_TEXT'] ?? 'Next') ?></button>
    </div>
  </div>
</div>

<script>
console.log("Section 1 script loaded");

document.addEventListener('DOMContentLoaded', function () {

    const input         = document.getElementById('species_search');
    const resultsBox    = document.getElementById('species_results');
    const hiddenSpecies = document.getElementById('animal_species');
    const hiddenType    = document.getElementById('animal_type');
    const hiddenOrder   = document.getElementById('animal_order');

    // Add-species panel elements (must exist in DOM)
    const addPanel   = document.getElementById('add_species_panel');
    const extQ       = document.getElementById('external_species_q');
    const extBtn     = document.getElementById('external_species_search_btn');
    const extWrap    = document.getElementById('external_species_table_wrap');
    const extPrev    = document.getElementById('external_prev');
    const extNext    = document.getElementById('external_next');
    const extLabel   = document.getElementById('external_page_label');

    let timer = null;
    let extPage = 1;
    const extPageSize = 10;

    function clearResults() {
        resultsBox.innerHTML = '';
    }

    function hideAddPanel() {
        if (addPanel) addPanel.style.display = 'none';
    }

    function showAddPanel(prefill) {
        if (!addPanel) return;
        addPanel.style.display = 'block';
        if (prefill && extQ) extQ.value = prefill;
    }

    function renderExternalTable(rows) {
        if (!Array.isArray(rows) || rows.length === 0) {
            extWrap.innerHTML = '<div class="rc-note"><?= addslashes($lang['NO_RESULTS'] ?? 'No results') ?></div>';
            return;
        }

        let html = '<div class="rc-table-scroll"><table class="rc-table"><thead><tr><th><?= addslashes($lang['SPECIES'] ?? 'Species') ?></th><th></th></tr></thead><tbody>';
        rows.forEach(r => {
            const id = r.gbif_id || r.key || '';
            const disp = (r.display || '').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            html += `<tr>
                <td>${disp}</td>
                <td class="rc-table-actions">
                    <button type="button" class="btn" data-gbif="${id}" data-display="${(r.display || '').replace(/"/g,'&quot;')}"><?= addslashes($lang['ADD'] ?? 'Add') ?></button>

                </td>
            </tr>`;
        });
        html += '</tbody></table></div>';
        extWrap.innerHTML = html;

        extWrap.querySelectorAll('button[data-gbif]').forEach(btn => {
            btn.addEventListener('click', () => {
                const gbif = btn.getAttribute('data-gbif');
                const display = btn.getAttribute('data-display') || '';

                if (!gbif) return;

                fetch('/controllers/select_species_external.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'gbif_id=' + encodeURIComponent(gbif) + '&display=' + encodeURIComponent(display)

                })
                .then(r => r.json())
                .then(saved => {
                    if (!saved || !saved.success) return;

                    input.value         = saved.species_name;
                    hiddenSpecies.value = saved.species_name;
                    hiddenType.value    = saved.type_name || '';
                    hiddenOrder.value   = saved.order_name || '';
                    input.setCustomValidity('');
                    clearResults();
                    hideAddPanel();
                });
            });
        });
    }

    function runExternalSearch() {
        const q = (extQ.value || '').trim();
        if (q.length < 2) return;

        const offset = (extPage - 1) * extPageSize;
        extLabel.textContent = <?= json_encode($lang['PAGE'] ?? 'Page') ?> + ' ' + extPage;

        fetch('/controllers/search_species_external.php?q=' + encodeURIComponent(q) +
              '&limit=' + extPageSize + '&offset=' + offset)
            .then(r => r.json())
            .then(rows => renderExternalTable(rows))
            .catch(() => {
                extWrap.innerHTML = '<div class="rc-note is-danger"><?= addslashes($lang['SEARCH_ERROR'] ?? 'Search error') ?></div>';
            });
    }

    if (extBtn) {
        extBtn.addEventListener('click', () => {
            extPage = 1;
            runExternalSearch();
        });
    }

    if (extPrev) {
        extPrev.addEventListener('click', () => {
            if (extPage > 1) {
                extPage--;
                runExternalSearch();
            }
        });
    }

    if (extNext) {
        extNext.addEventListener('click', () => {
            extPage++;
            runExternalSearch();
        });
    }

    if (extQ) {
        extQ.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                extPage = 1;
                runExternalSearch();
            }
        });
    }

    // INTERNAL AUTOCOMPLETE (UNCHANGED BEHAVIOUR, BUT KEEP HIDDEN SPECIES IN SYNC)
    if (input) {
        input.addEventListener('input', function () {
            const q = input.value.trim();

            // Typing invalidates the previous choice until a result is selected.
            if (hiddenSpecies) hiddenSpecies.value = '';
            if (hiddenType) hiddenType.value = '';
            if (hiddenOrder) hiddenOrder.value = '';
            input.setCustomValidity(q === ''
                ? <?= json_encode($lang['ADM_SELECT_SPECIES_LIST'] ?? 'Select a species from the list.') ?>
                : <?= json_encode($lang['ADM_CLICK_SPECIES_LIST'] ?? 'Click a species from the list.') ?>);

            clearResults();
            hideAddPanel();
            if (q.length < 2) return;

            if (timer) clearTimeout(timer);

            timer = setTimeout(() => {
                fetch('/controllers/search_species.php?q=' + encodeURIComponent(q))
                    .then(res => res.json())
                    .then(data => {
                        clearResults();

                        if (Array.isArray(data) && data.length > 0) {
                            const ul = document.createElement('ul');
                            data.forEach(item => {
                                const li = document.createElement('li');
                                li.textContent = item.species_display;
                                li.className = "rc-autocomplete-option";

                                li.addEventListener('click', function (e) {
                                    e.preventDefault();
                                    e.stopPropagation();

                                    input.value         = item.species_name;
                                    hiddenSpecies.value = item.species_name;
                                    hiddenType.value    = item.type_name;
                                    hiddenOrder.value   = item.order_name;
                                    input.setCustomValidity('');
                                    clearResults();
                                    hideAddPanel();
                                });

                                ul.appendChild(li);
                            });
                            resultsBox.appendChild(ul);
                        } else {
                            // No internal results → offer add-species panel
                            showAddPanel(q);
                        }
                    })
                    .catch(() => clearResults());
            }, 250);
        });

        document.addEventListener('click', function (e) {
            if (!resultsBox.contains(e.target) && e.target !== input) {
                clearResults();
            }
        });
    }
});
</script>
