<?php
// views/messageboard/board.php
// Requires: $pdo, $active_tab (slug), optional: $_GET['tag'], $_GET['q'], $_GET['page']

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_user_id = isset($_SESSION['account_id']) ? (int)$_SESSION['account_id'] : 0;

$tag  = isset($_GET['tag']) ? trim($_GET['tag']) : '';
$q    = isset($_GET['q']) ? trim($_GET['q']) : '';

// Pagination
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

/**
 * Load the global board for this tab slug
 */
$stmt = $pdo->prepare("
    SELECT id, slug, name
    FROM rescue_boards
    WHERE network_id IS NULL
      AND slug = :slug
      AND is_enabled = 1
    LIMIT 1
");
$stmt->execute([':slug' => $active_tab]);
$board = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$board) {
    echo '<div class="alert-box alert-red pad-3 mar-bot-3">Board not found or disabled.</div>';
    return;
}

/**
 * Count total threads for pagination
 */
$countParams = [':board_id' => (int)$board['id']];

$countSql = "
    SELECT COUNT(*) AS cnt
    FROM rescue_posts p
";

if ($tag !== '') {
    $countSql .= "
        JOIN rescue_post_tags pt ON pt.post_id = p.id
        JOIN rescue_tags t ON t.id = pt.tag_id
    ";
}

$countSql .= "
    WHERE p.board_id = :board_id
      AND p.parent_id IS NULL
      AND p.status IN ('active','locked')
";

if ($tag !== '') {
    $countSql .= " AND t.slug = :tag_slug ";
    $countParams[':tag_slug'] = $tag;
}

if ($q !== '') {
    $countSql .= " AND (p.title LIKE :q OR p.body LIKE :q) ";
    $countParams[':q'] = '%' . $q . '%';
}

$stmt = $pdo->prepare($countSql);
$stmt->execute($countParams);
$totalThreads = (int)($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);
$totalPages   = max(1, (int)ceil($totalThreads / $perPage));

// Clamp page
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

/**
 * Threads query (starters only), with optional tag + search + pagination
 */
$params = [':board_id' => (int)$board['id']];

$sql = "
    SELECT
      p.id,
      p.title,
      p.body,
      p.user_id,
      p.created_at,
      p.last_activity_at,

      a.first_name AS user_first_name,
      a.last_name  AS user_last_name,
      rc.rescue_name AS rescue_name,

      (SELECT COUNT(*)
       FROM rescue_posts r
       WHERE r.parent_id = p.id
         AND r.status='active') AS reply_count
    FROM rescue_posts p
    JOIN accounts a ON a.id = p.user_id
    LEFT JOIN rescue_centres rc ON rc.rescue_id = a.centre_id
";

if ($tag !== '') {
    $sql .= "
        JOIN rescue_post_tags pt ON pt.post_id = p.id
        JOIN rescue_tags t ON t.id = pt.tag_id
    ";
}

$sql .= "
    WHERE p.board_id = :board_id
      AND p.parent_id IS NULL
      AND p.status IN ('active','locked')
";

if ($tag !== '') {
    $sql .= " AND t.slug = :tag_slug ";
    $params[':tag_slug'] = $tag;
}

if ($q !== '') {
    $sql .= " AND (p.title LIKE :q OR p.body LIKE :q) ";
    $params[':q'] = '%' . $q . '%';
}

$sql .= " ORDER BY p.last_activity_at DESC LIMIT :limit OFFSET :offset ";

$stmt = $pdo->prepare($sql);

// bind normal params
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

$stmt->execute();
$threads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Load tag chips for each thread
$threadIds = array_map(fn($t) => (int)$t['id'], $threads);
$tagsByThread = [];

if (!empty($threadIds)) {
    $in = implode(',', array_fill(0, count($threadIds), '?'));
    $stmt = $pdo->prepare("
        SELECT pt.post_id, t.slug, t.label
        FROM rescue_post_tags pt
        JOIN rescue_tags t ON t.id = pt.tag_id
        WHERE pt.post_id IN ($in)
        ORDER BY t.label
    ");
    $stmt->execute($threadIds);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tagsByThread[(int)$row['post_id']][] = $row;
    }
}

// Helper: build pagination URLs preserving filters
$makeUrl = function(int $targetPage) use ($active_tab, $tag, $q) {
    $qs = ['tab' => $active_tab, 'page' => $targetPage];
    if ($tag !== '') $qs['tag'] = $tag;
    if ($q !== '')   $qs['q'] = $q;
    return '?' . http_build_query($qs);
};

global $csrf_token;
?>

<div style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
    <div>
        <h3 style="margin:0;"><?= htmlspecialchars($board['name']) ?></h3>
        <p style="margin:6px 0 0 0; opacity:.8;">Browse threads, filter by tag, and open a thread to view replies.</p>
    </div>

    <!-- Filter bar -->
    <form method="get" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">

        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search..."
               style="padding:8px 10px; border:1px solid #ccc; border-radius:6px;">

        <input type="text" name="tag" value="<?= htmlspecialchars($tag) ?>" placeholder="Tag (e.g. squirrels)"
               style="padding:8px 10px; border:1px solid #ccc; border-radius:6px;">

        <button type="submit" class="btn">Filter</button>

        <?php if ($tag !== '' || $q !== ''): ?>
            <a class="btn btn-light" href="?tab=<?= urlencode($active_tab) ?>">Clear</a>
        <?php endif; ?>
    </form>
</div>

<?php if (empty($threads)): ?>
    <div class="alert-box alert-blue pad-3 mar-bot-3" style="border-radius:8px; color:#1f2933; margin-top:12px;">
        No threads yet<?= ($tag || $q) ? ' for these filters.' : '.' ?>
    </div>
<?php else: ?>
    <div style="margin-top:12px;">
        <?php foreach ($threads as $t): ?>
            <?php
                // Own thread => amber, otherwise blue
                $threadAlert = ((int)$t['user_id'] === $current_user_id && $current_user_id > 0) ? 'alert-amber' : 'alert-blue';
                $chips = $tagsByThread[(int)$t['id']] ?? [];
            ?>
            <div class="alert-box <?= $threadAlert ?> pad-3 mar-bot-1" style="border-radius:8px; color:#1f2933;">
                <!-- Top row: Title + Author (left), Date (right) -->
                <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-start;">
                    <div style="min-width:0;">
                        <a href="?tab=<?= urlencode($active_tab) ?>&thread=<?= (int)$t['id'] ?>"
                           style="font-weight:700; text-decoration:none; display:inline; line-height:1.2;">
                            <?= htmlspecialchars($t['title'] ?? '(Untitled)') ?>
                        </a>

                        <span style="opacity:.75;"> • </span>

                        <span style="font-size:13px; opacity:.9;">
                            <?= htmlspecialchars(trim(($t['user_first_name'] ?? '') . ' ' . ($t['user_last_name'] ?? ''))) ?>
                            <?php if (!empty($t['rescue_name'])): ?>
                                (<?= htmlspecialchars($t['rescue_name']) ?>)
                            <?php endif; ?>
                        </span>
                    </div>

                    <div style="white-space:nowrap; font-size:12px; opacity:.9; padding-top:2px;">
                        <?= htmlspecialchars($t['last_activity_at']) ?>
                    </div>
                </div>

                <!-- Snippet -->
                <div style="margin-top:6px; font-size:18px; opacity:.95;">
                    <?= htmlspecialchars(mb_strimwidth(strip_tags($t['body']), 0, 140, '…')) ?>
                </div>

                <!-- Bottom row: Tags (left), Replies pill (right) -->
                <div style="margin-top:8px; display:flex; justify-content:space-between; gap:10px; align-items:center;">
                    <div style="display:flex; gap:6px; flex-wrap:wrap; min-width:0;">
                        <?php foreach ($chips as $chip): ?>
                            <a href="?tab=<?= urlencode($active_tab) ?>&tag=<?= urlencode($chip['slug']) ?>"
                               style="font-size:12px; padding:2px 8px; border-radius:999px; border:1px solid rgba(0,0,0,.18); text-decoration:none;">
                                #<?= htmlspecialchars($chip['label']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <a href="?tab=<?= urlencode($active_tab) ?>&thread=<?= (int)$t['id'] ?>" style="text-decoration:none;">
                        <span style="display:inline-block; font-size:12px; padding:3px 10px; border-radius:999px; border:1px solid rgba(0,0,0,.22); white-space:nowrap;">
                            <?= (int)$t['reply_count'] ?> replies
                        </span>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <div style="margin-top:12px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <?php if ($page > 1): ?>
                <a class="btn btn-light" href="<?= htmlspecialchars($makeUrl($page - 1)) ?>">← Prev</a>
            <?php endif; ?>

            <div style="opacity:.8;">Page <?= (int)$page ?> of <?= (int)$totalPages ?></div>

            <?php if ($page < $totalPages): ?>
                <a class="btn btn-light" href="<?= htmlspecialchars($makeUrl($page + 1)) ?>">Next →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>

<hr style="margin-top:14px;">

<!-- Create thread form (kept simple; not alert styled) -->
<div style="border:1px solid #e5e5e5; border-radius:10px; padding:12px; margin-top:12px;">
    <h4 style="margin:0 0 10px 0;">Create a new thread</h4>

    <form method="post">
        <input type="hidden" name="action" value="create_thread">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">

        <div style="display:flex; flex-direction:column; gap:8px;">
            <input type="text" name="title" maxlength="200" placeholder="Title"
                   style="padding:10px; border:1px solid #ccc; border-radius:6px;" required>

            <textarea name="body" rows="4" placeholder="Write your post..."
                      style="padding:10px; border:1px solid #ccc; border-radius:6px;" required></textarea>

            <input type="text" name="tags" placeholder="Tags (comma separated) e.g. squirrels, rehab"
                   style="padding:10px; border:1px solid #ccc; border-radius:6px;">

            <div>
                <button type="submit" class="btn">Post thread</button>
            </div>
        </div>
    </form>
</div>
