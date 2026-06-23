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
        'hidden' => 'selected_species_ids',
        'hint' => 'Matches species names, scientific names, species types and classes.',
    ],
    'medications' => [
        'title' => 'Medication',
        'count' => $localCounts['medications'],
        'placeholder' => 'Search medication name or class...',
        'hidden' => 'selected_medications_ids',
        'hint' => 'Matches medication names, common names and medication classes.',
    ],
    'feed' => [
        'title' => 'Food / Feed',
        'count' => $localCounts['feed'],
        'placeholder' => 'Search food, feed type or category...',
        'hidden' => 'selected_feed_ids',
        'hint' => 'Matches feed names, types and categories.',
    ],
];
?>

<style>
.sync-picker { position: relative; }
.sync-picker .js-sync-search {
    position: relative;
    z-index: 2;
    cursor: text;
}
.sync-results {
    position: absolute;
    z-index: 20;
    left: 0;
    right: 0;
    top: calc(100% + 4px);
    max-height: 260px;
    overflow: auto;
    border: 1px solid var(--rc-border, #d7dde8);
    border-radius: 10px;
    background: var(--rc-surface, #fff);
    box-shadow: 0 14px 30px rgba(0,0,0,.16);
}
.sync-results[hidden] {
    display: none !important;
}
.sync-result {
    display: block;
    width: 100%;
    padding: 10px 12px;
    border: 0;
    border-bottom: 1px solid var(--rc-border, #e6ebf2);
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
    min-height: 34px;
}
.sync-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    border-radius: 999px;
    border: 1px solid var(--rc-blue-border, #2f80ed);
    background: var(--rc-blue-bg, rgba(47, 128, 237, .12));
    color: var(--rc-blue-text, inherit);
}
.sync-chip button {
    border: 0;
    background: transparent;
    color: inherit;
    font-weight: 700;
    cursor: pointer;
}
.sync-footer-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
</style>

<div class="rc-stack">
    <div class="content-block">
        <h3>Sync</h3>
        <p class="rc-muted">
            Search the hosted Rescue Centre catalogue, select the records you want, then sync them into this Lite install. Existing local records are left unchanged.
        </p>
        <?php if (!$syncSettings['enabled']): ?>
            <div class="rc-alert amber">Hosted sync is not enabled for this install. Local-only installs cannot download hosted catalogues until sync is configured.</div>
        <?php else: ?>
            <div class="rc-alert blue">Connected to hosted sync at <?= htmlspecialchars($syncSettings['api_url']) ?>.</div>
        <?php endif; ?>
    </div>

    <form method="post" action="controllers/sync_controller.php" class="xform" id="catalogueSyncForm">
        <input type="hidden" name="sync_action" value="selected_catalogues">
        <?php foreach ($cards as $catalogue => $card): ?>
            <input type="hidden" name="<?= htmlspecialchars($card['hidden']) ?>" value="[]" class="js-selected-ids" data-catalogue="<?= htmlspecialchars($catalogue) ?>">
        <?php endforeach; ?>

        <div class="rc-card-grid">
            <?php foreach ($cards as $catalogue => $card): ?>
                <div class="rc-card">
                    <h3><?= htmlspecialchars($card['title']) ?></h3>
                    <p class="rc-muted">Local records: <?= number_format((int)$card['count']) ?></p>

                    <div class="xform-field sync-picker" data-catalogue="<?= htmlspecialchars($catalogue) ?>">
                        <label class="xform-label">Search</label>
                        <input type="search" class="xform-input js-sync-search" placeholder="<?= htmlspecialchars($card['placeholder']) ?>" autocomplete="off">
                        <div class="sync-results js-sync-results" hidden></div>
                    </div>

                    <p class="rc-muted"><?= htmlspecialchars($card['hint']) ?></p>
                    <div class="sync-chips js-sync-chips" data-catalogue="<?= htmlspecialchars($catalogue) ?>" aria-live="polite"></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="content-block">
            <div class="sync-footer-actions">
                <button class="btn green" type="submit" <?= !$syncSettings['enabled'] ? 'disabled' : '' ?>>Sync selected items</button>
            </div>
        </div>
    </form>
</div>

<script>
(() => {
const syncSelections = {
    species: new Map(),
    medications: new Map(),
    feed: new Map()
};

const getLabel = (item) => item.label || item.name || item.species_name || item.medication_name || 'Selected item';

const updateHidden = (catalogue) => {
    const hidden = document.querySelector('.js-selected-ids[data-catalogue="' + catalogue + '"]');
    if (!hidden) return;
    hidden.value = JSON.stringify(Array.from(syncSelections[catalogue].keys()).map((id) => Number(id)));
};

const renderChips = (catalogue) => {
    const chips = document.querySelector('.js-sync-chips[data-catalogue="' + catalogue + '"]');
    if (!chips) return;
    chips.innerHTML = '';

    syncSelections[catalogue].forEach((item, id) => {
        const chip = document.createElement('span');
        chip.className = 'sync-chip';
        chip.textContent = item.label;

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.textContent = 'x';
        remove.setAttribute('aria-label', 'Remove ' + item.label);
        remove.addEventListener('click', () => {
            syncSelections[catalogue].delete(id);
            updateHidden(catalogue);
            renderChips(catalogue);
        });

        chip.appendChild(remove);
        chips.appendChild(chip);
    });
};

document.querySelectorAll('.sync-picker').forEach((picker) => {
    const catalogue = picker.dataset.catalogue;
    const input = picker.querySelector('.js-sync-search');
    const results = picker.querySelector('.js-sync-results');
    let timer = null;

    const hideResults = () => {
        results.hidden = true;
        results.innerHTML = '';
    };

    const addItem = (item) => {
        const id = String(item.id || '');
        if (!id || syncSelections[catalogue].has(id)) return;
        syncSelections[catalogue].set(id, { label: getLabel(item) });
        input.value = '';
        hideResults();
        updateHidden(catalogue);
        renderChips(catalogue);
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
            button.querySelector('strong').textContent = getLabel(item);
            button.querySelector('span').textContent = item.subtitle || '';
            button.addEventListener('click', () => addItem(item));
            results.appendChild(button);
        });
        results.hidden = false;
    };

    const searchHostedCatalogue = () => {
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
        timer = window.setTimeout(searchHostedCatalogue, 250);
    });

    document.addEventListener('click', (event) => {
        if (!picker.contains(event.target)) {
            hideResults();
        }
    });
});
})();
</script>
