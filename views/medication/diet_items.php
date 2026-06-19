<?php

if (!isset($pdo)) {
    echo '<div class="rc-alert red">' . ($lang['DATABASE_CONNECTION_MISSING'] ?? 'Database connection missing.') . '</div>';
    return;
}
if (!isset($centre_id) || !$centre_id) {
    echo '<div class="rc-alert red">' . ($lang['CENTRE_CONTEXT_MISSING'] ?? 'Centre context missing.') . '</div>';
    return;
}

// ------------------------------------------------------------
// Inputs
// ------------------------------------------------------------
$q    = trim($_GET['q'] ?? '');
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;

$perPage = 25;
$offset  = ($page - 1) * $perPage;

// ------------------------------------------------------------
// Flash-ish messages via querystring (simple + consistent)
// diet_controller can redirect back with ?msg=...
// ------------------------------------------------------------
$msg = $_GET['msg'] ?? '';
$alertHtml = '';
if ($msg === 'added') {
    $alertHtml = '<div class="rc-alert green">' . ($lang['DIET_MSG_ADDED'] ?? 'Item added to your centre list.') . '</div>';
} elseif ($msg === 'updated') {
    $alertHtml = '<div class="rc-alert green">' . ($lang['DIET_MSG_UPDATED'] ?? 'Centre diet item updated.') . '</div>';
} elseif ($msg === 'deleted') {
    $alertHtml = '<div class="rc-alert green">' . ($lang['DIET_MSG_DELETED'] ?? 'Centre diet item removed.') . '</div>';
} elseif ($msg === 'error') {
    $alertHtml = '<div class="rc-alert red">' . ($lang['DIET_MSG_ERROR'] ?? 'Something went wrong. Please try again.') . '</div>';
}

// ------------------------------------------------------------
// Fetch centre-linked items
// ------------------------------------------------------------
$centreItemsStmt = $pdo->prepare("
    SELECT
        cdi.centre_diet_item_id,
        cdi.diet_item_id,
        cdi.use_within_days,
        cdi.is_enabled,
        cdi.notes,
        di.name,
        di.type,
        di.category,
        di.default_unit
    FROM rescue_centre_diet_items cdi
    INNER JOIN rescue_diet_items di ON di.diet_item_id = cdi.diet_item_id
    WHERE cdi.centre_id = ?
    ORDER BY di.name ASC
");
$centreItemsStmt->execute([$centre_id]);
$centreItems = $centreItemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Build a quick lookup set of already-linked diet_item_id
$linked = [];
foreach ($centreItems as $row) {
    $linked[(int)$row['diet_item_id']] = true;
}

// ------------------------------------------------------------
// Master list (paginated + searchable)
// ------------------------------------------------------------
$whereSql = "";
$params   = [];

if ($q !== '') {
    $whereSql = "WHERE di.name LIKE ?";
    $params[] = '%' . $q . '%';
}

// Count total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM rescue_diet_items di $whereSql");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) $page = $totalPages;

// Recalc offset if clamped
$offset = ($page - 1) * $perPage;

// Page rows
$listSql = "
    SELECT
        di.diet_item_id,
        di.name,
        di.type,
        di.category,
        di.default_unit,
        di.shelf_life_days,
        di.kcal_per_g,
        di.kcal_per_ml,
        di.notes
    FROM rescue_diet_items di
    $whereSql
    ORDER BY di.name ASC
    LIMIT $perPage OFFSET $offset
";
$listStmt = $pdo->prepare($listSql);
$listStmt->execute($params);
$masterItems = $listStmt->fetchAll(PDO::FETCH_ASSOC);

// Helper: default use-within days based on your locked category rules
function default_use_within_days(string $category): int {
    return ($category === 'liquid') ? 730 : 365;
}

// Helper: preserve querystring in pagination links
function build_qs(array $overrides = []): string {
    $base = $_GET;
    foreach ($overrides as $k => $v) {
        $base[$k] = $v;
    }
    // Remove empties that clutter URLs
    foreach ($base as $k => $v) {
        if ($v === '' || $v === null) unset($base[$k]);
    }
    return http_build_query($base);
}
// ------------------------------------------------------------
// CSRF token (per page/form group)
// ------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['csrf_tokens'])) {
    $_SESSION['csrf_tokens'] = [];
}

$csrf_form_key = 'centre_diet_items';

if (empty($_SESSION['csrf_tokens'][$csrf_form_key])) {
    $_SESSION['csrf_tokens'][$csrf_form_key] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_tokens'][$csrf_form_key];

?>

<div class="rc-stack">

    <?= $alertHtml ?>

    <div class="content-block">
        <h3><?= $lang['DIET_CENTRE_TITLE'] ?? 'Centre Diet Items' ?></h3>
        <p class="rc-muted">
            <?= $lang['DIET_CENTRE_SUBTITLE'] ?? 'Below is the list of foodstuffs you regularly use in your centre. Toggle items on/off, adjust “use within” defaults, or remove items. Add new items from the master list underneath.' ?>
        </p>

        <?php if (empty($centreItems)): ?>
            <div class="rc-alert blue" style="margin:0;">
                <?= $lang['DIET_CENTRE_EMPTY'] ?? 'You haven’t added any diet items to your centre yet. Use the master list below to add your first items.' ?>
            </div>
        <?php else: ?>
            <div class="rc-table-scroll">
                <table class="rc-table row-hover">
                    <thead>
                        <tr>
                            <th><?= $lang['DIET_TH_ITEM'] ?? 'Item' ?></th>
                            <th><?= $lang['DIET_TH_TYPE'] ?? 'Type' ?></th>
                            <th><?= $lang['DIET_TH_UNIT'] ?? 'Unit' ?></th>
                            <th style="width:150px;"><?= $lang['USE_WITHIN_DAYS'] ?? 'Use within (days)' ?></th>
                            <th style="width:120px;"><?= $lang['DIET_TH_ENABLED'] ?? 'Enabled' ?></th>
                            <th style="width:160px;"><?= $lang['ACTIONS'] ?? 'Actions' ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($centreItems as $row): ?>
                        <?php
                            $centreDietId = (int)$row['centre_diet_item_id'];
                            $enabled      = (int)$row['is_enabled'] === 1;
                            $useWithin    = $row['use_within_days'];
                            $useWithinVal = ($useWithin === null || $useWithin === '') ? default_use_within_days((string)$row['category']) : (int)$useWithin;
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($row['name']) ?></strong><br>
                                <span class="rc-muted">
                                    <?= $lang['DIET_CATEGORY_LABEL'] ?? 'Category:' ?> <?= htmlspecialchars($row['category']) ?>
                                </span>
                            </td>
                            <td><span class="rc-badge"><?= htmlspecialchars($row['type']) ?></span></td>
                            <td><span class="rc-badge na"><?= htmlspecialchars($row['default_unit']) ?></span></td>

                            <td>
                                <form class="rc-actions" method="post" action="/controllers/diet_controller.php">
                                    <input type="hidden" name="action" value="update_centre_diet_item">
                                    <input type="hidden" name="centre_id" value="<?= (int)$centre_id ?>">
                                    <input type="hidden" name="centre_diet_item_id" value="<?= $centreDietId ?>">

                                    <div class="rc-inline-list">
                                        <input type="number" name="use_within_days" min="0" step="1"
                                               value="<?= (int)$useWithinVal ?>">
                                    </div>
                            </td>

                            <td>
                                    <div class="rc-inline-list">
                                        <label class="rc-muted" style="display:flex; gap:8px; align-items:center; margin:0;">
                                            <input type="checkbox" name="is_enabled" value="1" <?= $enabled ? 'checked' : '' ?>>
                                            <?= $lang['ACTIVE'] ?? 'Active' ?>
                                        </label>
                                    </div>
                            </td>

                            <td>
                                <input type="hidden" name="csrf_form" value="<?= htmlspecialchars($csrf_form_key) ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <input type="hidden" name="audit_action" value="<?= $lang['DIET_AUDIT_UPDATED'] ?? 'Diet item updated' ?>">

                                    <button type="submit" class="btn blue"><?= $lang['SAVE'] ?? 'Save' ?></button>
                                </form>

                                <form method="post" action="/controllers/diet_controller.php" style="display:inline;"
                                      onsubmit="return confirm('<?= htmlspecialchars($lang['DIET_CONFIRM_REMOVE'] ?? 'Are you sure you want to remove this item from your centre?'); ?>');">
                                    <input type="hidden" name="action" value="delete_centre_diet_item">
                                    <input type="hidden" name="centre_id" value="<?= (int)$centre_id ?>">
                                    <input type="hidden" name="centre_diet_item_id" value="<?= $centreDietId ?>">
                                    <input type="hidden" name="csrf_form" value="<?= htmlspecialchars($csrf_form_key) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <input type="hidden" name="audit_action" value="<?= $lang['DIET_AUDIT_DELETED'] ?? 'Diet item deleted' ?>">

                                    <button type="submit" class="btn red"><?= $lang['DELETE'] ?? 'Delete' ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="content-block">
        <div class="rc-split-head">
            <div>
                <h3 style="margin-bottom:4px;"><?= $lang['DIET_MASTER_TITLE'] ?? 'Master Diet Library' ?></h3>
                <div class="rc-muted">
                    <?= ($lang['DIET_SHOWING'] ?? 'Showing') ?>
                    <?= number_format($totalRows) ?>
                    <?= ($lang['DIET_ITEMS'] ?? 'items') ?>
                    <?= $q !== '' ? ' ' . ($lang['DIET_MATCHING'] ?? 'matching') . ' “' . htmlspecialchars($q) . '”' : '' ?>.
                </div>
            </div>

            <form class="rc-actions" method="get" action="medicationstock.php">
                <input type="hidden" name="sub" value="diet">
                <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="<?= $lang['DIET_SEARCH_PLACEHOLDER'] ?? 'Search diet items by name...' ?>">
                <button class="btn grey" type="submit"><?= $lang['SEARCH'] ?? 'Search' ?></button>
                <?php if ($q !== ''): ?>
                    <a class="btn" href="medicationstock.php?sub=diet"><?= $lang['CLEAR'] ?? 'Clear' ?></a>
                <?php endif; ?>
            </form>
        </div>

        <div class="rc-table-scroll" style="margin-top:10px;">
            <table class="rc-table row-hover">
                <thead>
                    <tr>
                        <th><?= $lang['DIET_TH_ITEM'] ?? 'Item' ?></th>
                        <th><?= $lang['DIET_TH_TYPE'] ?? 'Type' ?></th>
                        <th><?= $lang['DIET_TH_CATEGORY'] ?? 'Category' ?></th>
                        <th><?= $lang['DIET_TH_UNIT'] ?? 'Unit' ?></th>
                        <th style="width:180px;"><?= $lang['DIET_TH_ACTION'] ?? 'Action' ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($masterItems as $item): ?>
                    <?php
                        $dietItemId = (int)$item['diet_item_id'];
                        $alreadyAdded = isset($linked[$dietItemId]);
                        $defaultUseWithin = default_use_within_days((string)$item['category']);
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($item['name']) ?></strong>
                            <?php if (!empty($item['notes'])): ?>
                                <div class="rc-muted"><?= htmlspecialchars($item['notes']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><span class="rc-badge"><?= htmlspecialchars($item['type']) ?></span></td>
                        <td><span class="rc-badge na"><?= htmlspecialchars($item['category']) ?></span></td>
                        <td><span class="rc-badge na"><?= htmlspecialchars($item['default_unit']) ?></span></td>
                        <td>
                            <?php if ($alreadyAdded): ?>
                                <span class="rc-badge ok"><?= $lang['DIET_ITEM_ADDED_BADGE'] ?? 'Item added' ?></span>
                            <?php else: ?>
                                <form method="post" action="/controllers/diet_controller.php" style="margin:0;">
                                    <input type="hidden" name="action" value="add_to_centre">
                                    <input type="hidden" name="centre_id" value="<?= (int)$centre_id ?>">
                                    <input type="hidden" name="diet_item_id" value="<?= $dietItemId ?>">
                                    <input type="hidden" name="use_within_days" value="<?= (int)$defaultUseWithin ?>">
                                    <input type="hidden" name="csrf_form" value="<?= htmlspecialchars($csrf_form_key) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <input type="hidden" name="audit_action" value="<?= $lang['DIET_AUDIT_ADDED'] ?? 'Diet item added to centre' ?>">

                                    <button type="submit" class="btn blue"><?= $lang['DIET_ADD_TO_RESCUE'] ?? '+ Add to rescue' ?></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($masterItems)): ?>
                    <tr>
                        <td colspan="5">
                            <div class="rc-alert blue" style="margin:0;">
                                <?= $lang['DIET_NONE_FOUND'] ?? 'No diet items found' ?><?= $q !== '' ? ' ' . ($lang['DIET_FOR_THAT_SEARCH'] ?? 'for that search.') : '.' ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="rc-pager">
                <?php
                $prev = $page - 1;
                $next = $page + 1;
                ?>
                <?php if ($page > 1): ?>
                    <a class="rc-pager-btn" href="medicationstock.php?<?= htmlspecialchars(build_qs(['sub'=>'diet','page'=>$prev])) ?>">&larr; <?= $lang['PAG_PREV_TEXT'] ?? 'Prev' ?></a>
                <?php endif; ?>

                <?php
                // Compact pager: show up to 7 pages around current
                $start = max(1, $page - 3);
                $end   = min($totalPages, $page + 3);
                if ($start > 1) echo '<span>…</span>';
                for ($p = $start; $p <= $end; $p++) {
                    if ($p === $page) {
                        echo '<span class="rc-pager-btn active">' . $p . '</span>';
                    } else {
                        echo '<a class="rc-pager-btn" href="medicationstock.php?' . htmlspecialchars(build_qs(['sub'=>'diet','page'=>$p])) . '">' . $p . '</a>';
                    }
                }
                if ($end < $totalPages) echo '<span>…</span>';
                ?>

                <?php if ($page < $totalPages): ?>
                    <a class="rc-pager-btn" href="medicationstock.php?<?= htmlspecialchars(build_qs(['sub'=>'diet','page'=>$next])) ?>"><?= $lang['PAG_NEXT_TEXT'] ?? 'Next' ?> &rarr;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</div>
