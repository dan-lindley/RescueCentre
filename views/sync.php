<?php
require_once __DIR__ . '/../operations/lite_sync_catalogue.php';

$syncSettings = lite_sync_settings($pdo);

$localCounts = [
    'species' => (int)$pdo->query('SELECT COUNT(*) FROM rescue_animal_species')->fetchColumn(),
    'medications' => (int)$pdo->query('SELECT COUNT(*) FROM rescue_medications')->fetchColumn(),
    'feed' => (int)$pdo->query('SELECT COUNT(*) FROM rescue_diet_items')->fetchColumn(),
];
?>

<div class="rc-stack">
    <div class="content-block">
        <h3>Sync</h3>
        <p class="rc-muted">
            Pull hosted Rescue Centre catalogue data into this Lite install. Use this to populate the local species, medication and feed libraries with only the records relevant to your rescue.
        </p>
        <?php if (!$syncSettings['enabled']): ?>
            <div class="rc-alert amber">Hosted sync is not enabled for this install. Local-only installs cannot download hosted catalogues until sync is configured.</div>
        <?php else: ?>
            <div class="rc-alert blue">Connected to hosted sync. Imports update existing records by name and add anything missing locally.</div>
        <?php endif; ?>
    </div>

    <div class="rc-card-grid">
        <div class="rc-card">
            <h3>Species</h3>
            <p class="rc-muted">Local records: <?= number_format($localCounts['species']) ?></p>
            <form method="post" action="controllers/sync_controller.php" class="xform">
                <input type="hidden" name="catalogue" value="species">
                <div class="xform-grid">
                    <div class="xform-field span-2">
                        <label class="xform-label">Import mode</label>
                        <select name="mode" class="xform-input js-sync-mode">
                            <option value="type">By species type</option>
                            <option value="class">By class</option>
                            <option value="search">Individually / search</option>
                            <option value="all">All species</option>
                        </select>
                    </div>
                    <div class="xform-field span-2">
                        <label class="xform-label">Filter/search value</label>
                        <input name="value" class="xform-input js-sync-value" placeholder="e.g. Bat, Mammal, pipistrelle">
                    </div>
                </div>
                <div class="xform-actions">
                    <button class="btn green" type="submit" <?= !$syncSettings['enabled'] ? 'disabled' : '' ?>>Sync species</button>
                </div>
            </form>
        </div>

        <div class="rc-card">
            <h3>Medication</h3>
            <p class="rc-muted">Local records: <?= number_format($localCounts['medications']) ?></p>
            <form method="post" action="controllers/sync_controller.php" class="xform">
                <input type="hidden" name="catalogue" value="medications">
                <div class="xform-grid">
                    <div class="xform-field span-2">
                        <label class="xform-label">Import mode</label>
                        <select name="mode" class="xform-input js-sync-mode">
                            <option value="class">By medication class</option>
                            <option value="search">Individually / search</option>
                            <option value="all">All medications</option>
                        </select>
                    </div>
                    <div class="xform-field span-2">
                        <label class="xform-label">Filter/search value</label>
                        <input name="value" class="xform-input js-sync-value" placeholder="e.g. NSAID, antibiotic, meloxicam">
                    </div>
                </div>
                <div class="xform-actions">
                    <button class="btn green" type="submit" <?= !$syncSettings['enabled'] ? 'disabled' : '' ?>>Sync medication</button>
                </div>
            </form>
        </div>

        <div class="rc-card">
            <h3>Feed</h3>
            <p class="rc-muted">Local records: <?= number_format($localCounts['feed']) ?></p>
            <form method="post" action="controllers/sync_controller.php" class="xform">
                <input type="hidden" name="catalogue" value="feed">
                <div class="xform-grid">
                    <div class="xform-field span-2">
                        <label class="xform-label">Import mode</label>
                        <select name="mode" class="xform-input js-sync-mode">
                            <option value="type">By feed type</option>
                            <option value="category">By category</option>
                            <option value="search">Individually / search</option>
                            <option value="all">All feed items</option>
                        </select>
                    </div>
                    <div class="xform-field span-2">
                        <label class="xform-label">Filter/search value</label>
                        <input name="value" class="xform-input js-sync-value" placeholder="e.g. liquid, invertebrate, formula">
                    </div>
                    <div class="xform-field span-4">
                        <label>
                            <input type="checkbox" name="enable_for_centre" value="1" checked>
                            Add imported feed items to this centre's enabled diet list
                        </label>
                    </div>
                </div>
                <div class="xform-actions">
                    <button class="btn green" type="submit" <?= !$syncSettings['enabled'] ? 'disabled' : '' ?>>Sync feed</button>
                </div>
            </form>
        </div>
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
</script>
