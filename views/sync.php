<?php
require_once __DIR__ . '/../operations/lite_sync_catalogue.php';

$syncSettings = lite_sync_settings($pdo);

$localCounts = [
    'species' => (int)$pdo->query('SELECT COUNT(*) FROM rescue_animal_species')->fetchColumn(),
    'medications' => (int)$pdo->query('SELECT COUNT(*) FROM rescue_medications')->fetchColumn(),
    'feed' => (int)$pdo->query('SELECT COUNT(*) FROM rescue_diet_items')->fetchColumn(),
];

$cards = [
    'species' => [
        'title' => 'Species',
        'count' => $localCounts['species'],
        'placeholder' => 'Search species, type or class...',
        'button' => 'Sync selected species',
        'modes' => [
            'all' => 'All species',
            'type' => 'By species type',
            'class' => 'By class',
        ],
        'value_placeholder' => 'e.g. Bat, Mammal',
    ],
    'medications' => [
        'title' => 'Medication',
        'count' => $localCounts['medications'],
        'placeholder' => 'Search medication name or class...',
        'button' => 'Sync selected medication',
        'modes' => [
            'all' => 'All medications',
            'class' => 'By medication class',
        ],
        'value_placeholder' => 'e.g. antibiotic, NSAID',
    ],
    'feed' => [
        'title' => 'Feed',
        'count' => $localCounts['feed'],
        'placeholder' => 'Search feed name, type or category...',
        'button' => 'Sync selected feed',
        'modes' => [
            'all' => 'All feed items',
            'type' => 'By feed type',
            'category' => 'By category',
        ],
        'value_placeholder' => 'e.g. liquid, invertebrate',
    ],
];
?>

<style>
.sync-picker { position: relative; }
.sync-results {
    position: absolute;
    z-index: 20;
    left: 0;
    right: 0;
    top: calc(100% + 4px);
    max-height: 260px;
    overflow: auto;
    border: 1px solid var(--border-color, #d7dde8);
    border-radius: 10px;
    background: var(--card-bg, #fff);
    box-shadow: 0 14px 30px rgba(0,0,0,.16);
}
.sync-result {
    display: block;
    width: 100%;
    padding: 10px 12px;
    border: 0;
    border-bottom: 1px solid var(--border-color, #e6ebf2);
    background: transparent;
    color: inherit;
    text-align: left;
    cursor: pointer;
}
.sync-result:hover { background: rgba(47, 128, 237, .12); }
.sync-result strong { display: block; }
.sync-result span { display: block; font-size: .85rem; opacity: .72; }
.sync-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
}
.sync-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    border-radius: 999px;
    border: 1px solid var(--accent-color, #2f80ed);
    background: rgba(47, 128, 237, .12);
}
.sync-chip button {
    border: 0;
    background: transparent;
    color: inherit;
    font-weight: 700;
    cursor: pointer;
}
.sync-card-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
}
</style>

<div class="rc-stack">
    <div class="content-block">
        <h3>Sync</h3>
        <p class="rc-muted">
            Pull hosted Rescue Centre catalogue data into this Lite install. Search, select the records you want, then sync them locally. Existing local records are left unchanged.
        </p>
        <?php if (!$syncSettings['enabled']): ?>
            <div class="rc-alert amber">Hosted sync is not enabled for this install. Local-only installs cannot download hosted catalogues until sync is configured.</div>
        <?php else: ?>
            <div class="rc-alert blue">Connected to hosted sync. Selected records will be added locally only if they do not already exist.</div>
        <?php endif; ?>
    </div>

    <div class="rc-card-grid">
        <?php foreach ($cards as $catalogue => $card): ?>
            <div class="rc-card">
                <h3><?= htmlspecialchars($card['title']) ?></h3>
                <p class="rc-muted">Local records: <?= number_format((int)$card['count']) ?></p>

                <form method="post" action="controllers/sync_controller.php" class="xform sync-selected-form">
                    <input type="hidden" name="catalogue" value="<?= htmlspecialchars($catalogue) ?>">
                    <input type="hidden" name="mode" value="selected">
                    <input type="hidden" name="selected_ids" value="[]" class="js-selected-ids">
                    <?php if ($catalogue === 'feed'): ?>
                        <input type="hidden" name="enable_for_centre" value="1">
                    <?php endif; ?>

                    <div class="xform-field sync-picker" data-catalogue="<?= htmlspecialchars($catalogue) ?>">
                        <label class="xform-label">Search and select</label>
                        <input type="search" class="xform-input js-sync-search" placeholder="<?= htmlspecialchars($card['placeholder']) ?>" autocomplete="off" <?= !$syncSettings['enabled'] ? 'disabled' : '' ?>>
                        <div class="sync-results js-sync-results" hidden></div>
                        <div class="sync-chips js-sync-chips" aria-live="polite"></div>
                    </div>

                    <div class="xform-actions sync-card-actions">
                        <button class="btn green" type="submit" <?= !$syncSettings['enabled'] ? 'disabled' : '' ?>><?= htmlspecialchars($card['button']) ?></button>
                    </div>
                </form>

                <hr>

                <form method="post" action="controllers/sync_controller.php" class="xform">
                    <input type="hidden" name="catalogue" value="<?= htmlspecialchars($catalogue) ?>">
                    <?php if ($catalogue === 'feed'): ?>
                        <input type="hidden" name="enable_for_centre" value="1">
                    <?php endif; ?>
                    <div class="xform-grid">
                        <div class="xform-field span-2">
                            <label class="xform-label">Or sync by group</label>
                            <select name="mode" class="xform-input js-sync-mode">
                                <?php foreach ($card['modes'] as $mode => $label): ?>
                                    <option value="<?= htmlspecialchars($mode) ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="xform-field span-2">
                            <label class="xform-label">Group value</label>
                            <input name="value" class="xform-input js-sync-value" placeholder="<?= htmlspecialchars($card['value_placeholder']) ?>">
                        </div>
                    </div>
                    <div class="xform-actions sync-card-actions">
                        <button class="btn blue" type="submit" <?= !$syncSettings['enabled'] ? 'disabled' : '' ?>>Sync group/all</button>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.querySelectorAll('.js-sync-mode').forEach((select) => {
    const form = select.closest('form');
    const value = form ? form.querySelector('.js-sync-value') : null;
    const update = () => {
        if (!value) return;
        const isAll = select.value === 'all';
        value.disabled = isAll;
        value.required = !isAll;
        if (isAll) value.value = '';
    };
    select.addEventListener('change', update);
    update();
});

document.querySelectorAll('.sync-picker').forEach((picker) => {
    const catalogue = picker.dataset.catalogue;
    const form = picker.closest('form');
    const input = picker.querySelector('.js-sync-search');
    const results = picker.querySelector('.js-sync-results');
    const chips = form.querySelector('.js-sync-chips');
    const hidden = form.querySelector('.js-selected-ids');
    const selected = new Map();
    let timer = null;

    const updateHidden = () => {
        hidden.value = JSON.stringify(Array.from(selected.keys()).map((id) => Number(id)));
    };

    const renderChips = () => {
        chips.innerHTML = '';
        selected.forEach((item, id) => {
            const chip = document.createElement('span');
            chip.className = 'sync-chip';
            chip.textContent = item.label;

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.textContent = 'x';
            remove.setAttribute('aria-label', 'Remove ' + item.label);
            remove.addEventListener('click', () => {
                selected.delete(id);
                updateHidden();
                renderChips();
            });

            chip.appendChild(remove);
            chips.appendChild(chip);
        });
    };

    const hideResults = () => {
        results.hidden = true;
        results.innerHTML = '';
    };

    const addItem = (item) => {
        const id = String(item.id);
        if (!id || selected.has(id)) return;
        selected.set(id, {
            label: item.label || item.name || item.species_name || item.medication_name || 'Selected item'
        });
        input.value = '';
        hideResults();
        updateHidden();
        renderChips();
        input.focus();
    };

    const renderResults = (items) => {
        results.innerHTML = '';
        if (!items.length) {
            results.innerHTML = '<div class="sync-result">No matches found.</div>';
            results.hidden = false;
            return;
        }

        items.forEach((item) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'sync-result';
            button.innerHTML = '<strong></strong><span></span>';
            button.querySelector('strong').textContent = item.label || item.name || item.species_name || item.medication_name || 'Untitled';
            button.querySelector('span').textContent = item.subtitle || '';
            button.addEventListener('click', () => addItem(item));
            results.appendChild(button);
        });
        results.hidden = false;
    };

    const search = () => {
        const q = input.value.trim();
        if (q.length < 2) {
            hideResults();
            return;
        }

        fetch('controllers/sync_controller.php?action=search&catalogue=' + encodeURIComponent(catalogue) + '&q=' + encodeURIComponent(q), {
            headers: { 'Accept': 'application/json' }
        })
            .then((response) => response.json())
            .then((payload) => {
                if (payload.status === 'error') {
                    throw new Error(payload.message || 'Search failed.');
                }
                renderResults(Array.isArray(payload.items) ? payload.items : []);
            })
            .catch((error) => {
                results.innerHTML = '<div class="sync-result">' + error.message + '</div>';
                results.hidden = false;
            });
    };

    input.addEventListener('input', () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(search, 250);
    });

    document.addEventListener('click', (event) => {
        if (!picker.contains(event.target)) {
            hideResults();
        }
    });
});
</script>
