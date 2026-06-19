<?php
// views/feeds.php
// Feeding history list (10 rows + pagination) + includes add feed form.
// Assumes $pdo, $patient_id, $centre_id are available from viewpatient wrapper.

if (!isset($patient_id) || (int)$patient_id <= 0) {
    echo '<div class="alert-box alert-red" style="margin-bottom: 12px;"><strong>Feeding</strong><br>Patient context not available.</div>';
    return;
}

require_once __DIR__ . '/../core/icons.php';

$perPage = 10;
$page = isset($_GET['feed_page']) ? (int)$_GET['feed_page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM rescue_feeding_events
    WHERE patient_id = ?
");
$countStmt->execute([(int)$patient_id]);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($totalRows / $perPage);

$stmt = $pdo->prepare("
    SELECT
        fe.feed_id,
        fe.feed_at,
        fe.feed_type,
        fe.status,
        fe.offered_value,
        fe.offered_unit,
        fe.remaining_value,
        fe.remaining_percent,
        fe.consumed_value,
        fe.consumed_unit,
        fe.is_estimated,
        di.name AS diet_name
    FROM rescue_feeding_events fe
    LEFT JOIN rescue_diet_items di
        ON di.diet_item_id = fe.diet_item_id
    WHERE fe.patient_id = ?
    ORDER BY fe.feed_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute([(int)$patient_id]);
$feeds = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!function_exists('build_feed_page_url')) {
    function build_feed_page_url($targetPage) {
        $params = $_GET;
        $params['feed_page'] = $targetPage;
        return '?' . http_build_query($params);
    }
}

$fmt = function($n) {
    return rtrim(rtrim(number_format((float)$n, 2, '.', ''), '0'), '.');
};

$trend_state = function($current, $previous) {
    if ($previous === null) return '';
    $c = (float)$current;
    $p = (float)$previous;
    $eps = 0.00001;
    if ($c > $p + $eps) return 'up';
    if ($c < $p - $eps) return 'down';
    return 'flat';
};

$trendMap = [];
$lastByKey = [];
$feedsAsc = array_reverse($feeds);
foreach ($feedsAsc as $r) {
    $key = ($r['feed_type'] ?: 'x') . '|' . ($r['consumed_unit'] ?: 'x');
    $consumed = ($r['consumed_value'] !== null) ? (float)$r['consumed_value'] : 0.0;
    $prev = $lastByKey[$key] ?? null;
    $trendMap[$r['feed_id']] = $trend_state($consumed, $prev);
    $lastByKey[$key] = $consumed;
}

if (!function_exists('can')) {
    require_once __DIR__ . '/../operations/permissions.php';
}

$canDeleteFeed = can('patients.feeding.delete');
?>

<?php if (empty($feeds)): ?>

    <div class="alert-box alert-brown" style="margin-bottom: 12px;">
        <strong>Feeding</strong><br>
        No feeding records recorded for this patient.
    </div>

<?php else: ?>

    <div class="alert-box alert-grey"
         style="margin-bottom: 6px; padding: 6px 12px; font-size: 0.75rem; opacity: 0.9;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr>
                    <th align="left" style="width:120px;">Date Added</th>
                    <th align="left" style="padding-left:40px;">Feed</th>
                    <th align="center" style="width:110px;">Consumed</th>
                    <th align="center" style="width:70px;">Action</th>
                </tr>
            </thead>
        </table>
    </div>

    <?php foreach ($feeds as $row): ?>
        <?php
            $dietName = $row['diet_name'] ?: '-';
            $type = $row['feed_type'] ?: '-';

            $offeredDisplay = '-';
            if ($row['offered_value'] !== null && $row['offered_unit']) {
                $offeredDisplay = $fmt($row['offered_value']) . $row['offered_unit'];
            }

            $remainingDisplay = '-';
            if ($row['remaining_percent'] !== null && $row['remaining_percent'] !== '') {
                $remainingDisplay = (int)$row['remaining_percent'] . '%';
            } elseif ($row['remaining_value'] !== null && $row['offered_unit']) {
                $remainingDisplay = $fmt($row['remaining_value']) . $row['offered_unit'];
            }

            $consumedDisplay = '0';
            if ($row['consumed_value'] !== null && $row['consumed_unit']) {
                $consumedDisplay = $fmt($row['consumed_value']) . $row['consumed_unit'];
            }

            $trend = $trendMap[$row['feed_id']] ?? '';
        ?>

        <div class="alert-box alert-brown feed-row" style="margin-bottom: 6px; padding: 8px 12px;">
            <table style="width:100%; border-collapse:collapse;">
                <tbody>
                    <tr>
                        <td style="width:120px; white-space:nowrap; font-size:0.8rem;">
                            <?= htmlspecialchars(date('d/m/y', strtotime($row['feed_at']))) ?><br>
                            <span style="opacity:0.75;">
                                <b><?= htmlspecialchars(date('H:i', strtotime($row['feed_at']))) ?></b>
                            </span>
                        </td>

                        <td style="padding-left:30px;">
                            <strong>
                                <?= htmlspecialchars($dietName) ?>
                                <?php if ($type !== '-'): ?>
                                    <span style="font-size:0.7rem; opacity:0.75;">
                                        (<?= htmlspecialchars($type) ?>)
                                    </span>
                                <?php endif; ?>
                            </strong>

                            <div style="font-size:0.75rem; opacity:0.85;">
                                Offered: <?= htmlspecialchars($offeredDisplay) ?>
                                &nbsp;|&nbsp;
                                Remaining: <?= htmlspecialchars($remainingDisplay) ?>
                            </div>
                        </td>

                        <td style="width:110px; white-space:nowrap; text-align:center;">
                            <strong><?= htmlspecialchars($consumedDisplay) ?></strong>
                            <?php if ($trend !== ''): ?>
                                <span class="trend-chip trend-<?= htmlspecialchars($trend) ?>" title="Trend vs previous <?= htmlspecialchars($type) ?> feed">
                                    <span class="triangle <?= htmlspecialchars($trend) ?>"></span>
                                </span>
                            <?php endif; ?>
                        </td>

                        <td style="width:70px; text-align:center; white-space:nowrap;">
                            <?php if ($canDeleteFeed): ?>
                                <form method="post"
                                      action="controllers/form_handler.php"
                                      style="margin:0; padding:0;"
                                      onsubmit="return confirm('Delete this feed entry?');">
                                    <input type="hidden" name="feed_delete" value="1">
                                    <input type="hidden" name="feed_id" value="<?= (int)$row['feed_id']; ?>">
                                    <input type="hidden" name="patient_id" value="<?= (int)$patient_id; ?>">
                                    <input type="hidden" name="audit_action" value="Feed event deleted">

                                    <button type="submit"
                                            class="btn red"
                                            title="Delete feed entry"
                                            aria-label="Delete feed entry"
                                            style="padding: 2px 6px; font-size: 0.7rem; line-height: 1;">
                                        <?= rc_icon('trash', 20, 'icon', 'aria-hidden="true"') ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="opacity:0.4;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>

<?php endif; ?>

<?php if ($totalPages > 1): ?>
    <div class="pagination" style="margin-top: 10px;">
        <?php if ($page > 1): ?>
            <a class="btn grey" style="padding:2px 8px; font-size:0.75rem;" href="<?= htmlspecialchars(build_feed_page_url($page - 1)) ?>">
                Prev
            </a>
        <?php endif; ?>

        <span style="margin:0 10px; font-size:0.85rem;">
            Page <?= (int)$page ?> of <?= (int)$totalPages ?>
        </span>

        <?php if ($page < $totalPages): ?>
            <a class="btn grey" style="padding:2px 8px; font-size:0.75rem;" href="<?= htmlspecialchars(build_feed_page_url($page + 1)) ?>">
                Next
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="mar-bot-3">
    <?php include __DIR__ . '/../controllers/add_feed.php'; ?>
</div>
