<?php
if (!defined('APP_LOADED')) exit;

require_once __DIR__ . '/../operations/modules_registry.php';

$moduleError = null;
$modules = [];
$moduleCentreId = (int)($centre_id ?? $_SESSION['centre_id'] ?? $_SESSION['rescue_id'] ?? 0);
try {
    $modules = modules_discover($pdo, $moduleCentreId);
} catch (Throwable $e) {
    $moduleError = $e->getMessage();
}
?>

<style>
    .module-image {
        width:100%;
        height:130px;
        border-radius:6px;
        border:1px solid #e5e7eb;
        background:#f3f4f6;
        object-fit:cover;
        display:block;
    }
    .module-image-placeholder {
        width:100%;
        height:130px;
        border-radius:6px;
        border:1px solid #e5e7eb;
        background:#eef2ff;
        color:#3730a3;
        display:flex;
        align-items:center;
        justify-content:center;
        font-weight:800;
        font-size:1.4rem;
    }
    .module-title { margin:0; font-size:1.25rem; line-height:1.2; }
    .module-path { margin-top:6px; font-size:.82rem; color:#4b5563; }
    .module-dependency-warning { color:var(--rc-red-text); font-weight:700; }
    .module-body { flex:1 1 auto; min-width:0; }
    .module-row { display:flex; flex-direction:column; gap:14px; }
    .module-not-installed { background:var(--rc-amber-bg); color:var(--rc-amber-text); }
    .module-removed { background:var(--rc-red-bg); color:var(--rc-red-text); }
</style>

<?php if ($moduleError): ?>
    <div class="alert-box alert-red">
        <strong>Modules could not be loaded.</strong><br>
        <?= modules_h($moduleError) ?>
    </div>

    <div class="alert-box alert-grey">
        The modules page uses <strong>new/views/modules.php</strong> and stores module activation state in
        <strong>rescue_modules</strong>.
    </div>
<?php elseif (empty($modules)): ?>
    <div class="alert-box alert-grey">
        No modules found yet. Add module folders under <strong>new/modules</strong>.
    </div>
<?php else: ?>
    <div class="rc-card-grid-4">
        <?php foreach ($modules as $module): ?>
            <?php
                $status = (string)($module['status'] ?? 'not installed');
                $statusClass = str_replace(' ', '-', $status);
                $displayStatusClass = in_array($statusClass, ['active', 'inactive'], true) ? $statusClass : 'module-' . $statusClass;
                $moduleKey = (string)$module['module_key'];
                $imagePath = trim((string)($module['image_path'] ?? ''));
                $isCore = !empty($module['core']);
                $isEnabled = !empty($module['enabled']);
                $isInstalled = !empty($module['installed']);
                $dependencies = modules_normalise_dependencies((array)($module['dependencies'] ?? []));
                $unmetDependencies = modules_unmet_dependencies($pdo, $moduleKey, $moduleCentreId);
            ?>
            <div class="rc-card module-row">
                <div>
                    <?php if ($imagePath !== ''): ?>
                        <img class="module-image" src="<?= modules_h($imagePath) ?>" alt="">
                    <?php else: ?>
                        <div class="module-image-placeholder"><?= modules_h(strtoupper(substr($module['module_name'], 0, 1))) ?></div>
                    <?php endif; ?>
                </div>

                <div class="module-body">
                    <h3 class="module-title"><?= modules_h($module['module_name']) ?> <span class="rc-status <?= modules_h($displayStatusClass) ?>"><?= modules_h($status) ?></span></h3>
                    <div class="rc-muted"><?= modules_h($module['description'] ?? '') ?></div>
                    <div class="module-path">/modules/<?= modules_h($moduleKey) ?></div>
                    <div class="module-path">Version: <?= modules_h($module['version'] ?? '') ?><?= $isCore ? ' | Core module' : '' ?></div>
                    <?php if ($dependencies): ?>
                        <div class="module-path">
                            Requires:
                            <?php foreach ($dependencies as $index => $dependency): ?>
                                <?= $index > 0 ? ', ' : '' ?><?= modules_h(modules_dependency_label($pdo, $dependency, $moduleCentreId)) ?><?= !empty($dependency['min_version']) ? ' ' . modules_h($dependency['min_version']) . '+' : '' ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($unmetDependencies): ?>
                        <div class="module-path module-dependency-warning">
                            Missing: <?= modules_h(modules_dependency_message($unmetDependencies)) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($module['updated_at'])): ?>
                        <div class="module-path">Updated: <?= modules_h($module['updated_at']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="rc-actions" style="justify-content:flex-start;">
                    

                    <?php if (!$isEnabled): ?>
                        <form method="post" action="controllers/modules_handler.php" style="margin:0;">
                            <input type="hidden" name="action" value="activate">
                            <input type="hidden" name="centre_id" value="<?= (int)$moduleCentreId ?>">
                            <input type="hidden" name="module_key" value="<?= modules_h($moduleKey) ?>">
                            <button type="submit" class="btn green" <?= $unmetDependencies ? 'disabled' : '' ?>><?= $isInstalled ? 'Activate' : 'Install' ?></button>
                        </form>
                    <?php else: ?>
                        <form method="post" action="controllers/modules_handler.php" style="margin:0;">
                            <input type="hidden" name="action" value="deactivate">
                            <input type="hidden" name="centre_id" value="<?= (int)$moduleCentreId ?>">
                            <input type="hidden" name="module_key" value="<?= modules_h($moduleKey) ?>">
                            <button type="submit" class="btn orange" <?= $isCore ? 'disabled' : '' ?>>Deactivate</button>
                        </form>
                    <?php endif; ?>

                    <!--<form method="post" action="controllers/modules_handler.php" style="margin:0;" onsubmit="return confirm('Remove this module from active use?');">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="centre_id" value="<?= (int)$moduleCentreId ?>">
                        <input type="hidden" name="module_key" value="<?= modules_h($moduleKey) ?>">
                        <button type="submit" class="btn grey" <?= $isCore ? 'disabled' : '' ?>>Remove</button>
                    </form>-->

                    <form method="post" action="controllers/modules_handler.php" style="margin:0;" onsubmit="return confirm('Mark this module data as deleted? Module-specific cleanup hooks can be added as modules are migrated.');">
                        <input type="hidden" name="action" value="delete_data">
                        <input type="hidden" name="centre_id" value="<?= (int)$moduleCentreId ?>">
                        <input type="hidden" name="module_key" value="<?= modules_h($moduleKey) ?>">
                        <button type="submit" class="btn red" <?= $isCore ? 'disabled' : '' ?>>Delete data</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
