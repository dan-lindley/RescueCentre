<?php
// views/messageboard/thread.php
// Requires: $pdo, $active_tab, $_GET['thread']
// Optional: $_GET['page'] for reply pagination

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_user_id = isset($_SESSION['account_id']) ? (int)$_SESSION['account_id'] : 0;

$thread_id = isset($_GET['thread']) ? (int)$_GET['thread'] : 0;
if ($thread_id <= 0) {
    echo '<div class="alert-box alert-red pad-3 mar-bot-3">Invalid thread.</div>';
    return;
}

// Reply pagination (replies only)
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

// Load board (global)
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

// Load starter (must be in this board and be a starter)
$stmt = $pdo->prepare("
    SELECT
      p.*,
      a.first_name AS user_first_name,
      a.last_name  AS user_last_name,
      rc.rescue_name AS rescue_name
    FROM rescue_posts p
    JOIN accounts a ON a.id = p.user_id
    LEFT JOIN rescue_centres rc ON rc.rescue_id = a.centre_id
    WHERE p.id = :id
      AND p.board_id = :board_id
      AND p.parent_id IS NULL
    LIMIT 1
");
$stmt->execute([
    ':id' => $thread_id,
    ':board_id' => (int)$board['id']
]);
$starter = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$starter) {
    echo '<div class="alert-box alert-red pad-3 mar-bot-3">Thread not found in this board.</div>';
    return;
}

// Load tags for starter
$stmt = $pdo->prepare("
    SELECT t.slug, t.label
    FROM rescue_tags t
    JOIN rescue_post_tags pt ON pt.tag_id = t.id
    WHERE pt.post_id = :post_id
    ORDER BY t.label
");
$stmt->execute([':post_id' => $thread_id]);
$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count replies for pagination
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS cnt
    FROM rescue_posts
    WHERE parent_id = :thread_id
      AND status IN ('active','locked')
");
$stmt->execute([':thread_id' => $thread_id]);
$totalReplies = (int)($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);
$totalPages   = max(1, (int)ceil($totalReplies / $perPage));

// Clamp page
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// Fetch replies (paginated)
$stmt = $pdo->prepare("
    SELECT
      p.*,
      a.first_name AS user_first_name,
      a.last_name  AS user_last_name,
      rc.rescue_name AS rescue_name
    FROM rescue_posts p
    JOIN accounts a ON a.id = p.user_id
    LEFT JOIN rescue_centres rc ON rc.rescue_id = a.centre_id
    WHERE p.parent_id = :thread_id
      AND p.status IN ('active','locked')
    ORDER BY p.created_at ASC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':thread_id', $thread_id, PDO::PARAM_INT);
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$replies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pagination URL helper (preserve tab + thread)
$makeUrl = function(int $targetPage) use ($active_tab, $thread_id) {
    $qs = ['tab' => $active_tab, 'thread' => $thread_id, 'page' => $targetPage];
    return '?' . http_build_query($qs) . '#replies';
};

function mb_name($row) {
    $name = trim(($row['user_first_name'] ?? '') . ' ' . ($row['user_last_name'] ?? ''));
    if (!empty($row['rescue_name'])) $name .= ' (' . $row['rescue_name'] . ')';
    return $name ?: 'Unknown';
}

$starterAlert = ((int)$starter['user_id'] === $current_user_id && $current_user_id > 0) ? 'alert-amber' : 'alert-blue';

global $csrf_token;
?>

<!-- Header -->
<div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
    <div>
        <a href="?tab=<?= urlencode($active_tab) ?>" style="text-decoration:none;">← Back to <?= htmlspecialchars($board['name']) ?></a>
        <h3 style="margin:10px 0 0 0;"><?= htmlspecialchars($starter['title'] ?? '(Untitled)') ?></h3>

        <?php if (!empty($tags)): ?>
            <div style="margin-top:8px; display:flex; gap:6px; flex-wrap:wrap;">
                <?php foreach ($tags as $chip): ?>
                    <a href="?tab=<?= urlencode($active_tab) ?>&tag=<?= urlencode($chip['slug']) ?>"
                       style="font-size:12px; padding:2px 8px; border-radius:999px; border:1px solid rgba(0,0,0,.18); text-decoration:none;">
                        #<?= htmlspecialchars($chip['label']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div style="text-align:right; font-size:12px; opacity:.9; white-space:nowrap;">
        <div><?= htmlspecialchars($starter['created_at']) ?></div>
        <div>Status: <?= htmlspecialchars($starter['status']) ?></div>
    </div>
</div>

<hr>

<!-- Starter (blue or amber if own) -->
<div class="alert-box <?= $starterAlert ?> pad-3 mar-bot-1" style="border-radius:8px; color:#1f2933;">
    <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-start; flex-wrap:wrap;">
        <div style="font-weight:700;">
            Thread starter • <?= htmlspecialchars(mb_name($starter)) ?>
        </div>
        <div style="font-size:12px; opacity:.9; white-space:nowrap;">
            <?= htmlspecialchars($starter['created_at']) ?>
        </div>
    </div>

    <div style="margin-top:8px; white-space:pre-wrap; font-size:18px; opacity:.95;">
        <?= htmlspecialchars($starter['body']) ?>
    </div>
</div>

<!-- Replies -->
<div id="replies" style="margin-top:10px;">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
        <h4 style="margin:0;">Replies (<?= (int)$totalReplies ?>)</h4>

        <?php if ($totalPages > 1): ?>
            <div style="opacity:.8; font-size:13px;">Page <?= (int)$page ?> of <?= (int)$totalPages ?></div>
        <?php endif; ?>
    </div>

    <?php if ($totalReplies === 0): ?>
        <div class="alert-box alert-teal pad-3 mar-bot-3" style="border-radius:8px; color:#1f2933; margin-top:10px;">
            No replies yet.
        </div>
    <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:0; margin-top:10px;">
            <?php foreach ($replies as $p): ?>
                <?php
                    // Own reply => amber, otherwise teal
                    $replyAlert = ((int)$p['user_id'] === $current_user_id && $current_user_id > 0) ? 'alert-amber' : 'alert-teal';
                ?>
                <div class="alert-box <?= $replyAlert ?> pad-3 mar-bot-1" style="border-radius:8px; color:#1f2933;">
                    <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-start; flex-wrap:wrap;">
                        <div style="font-weight:700;">
                            Reply • <?= htmlspecialchars(mb_name($p)) ?>
                        </div>
                        <div style="font-size:12px; opacity:.9; white-space:nowrap;">
                            <?= htmlspecialchars($p['created_at']) ?>
                        </div>
                    </div>

                    <div style="margin-top:8px; white-space:pre-wrap; font-size:18px; opacity:.95;">
                        <?= htmlspecialchars($p['body']) ?>
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
</div>

<hr>

<!-- Reply form (plain, no alert styling) -->
<div id="reply" style="border:1px solid #e5e5e5; border-radius:10px; padding:12px;">
    <h4 style="margin:0 0 10px 0;">Reply</h4>

    <?php if (($starter['status'] ?? '') === 'locked'): ?>
        <div class="alert-box alert-red pad-3 mar-bot-3" style="border-radius:8px;">
            This thread is locked.
        </div>
    <?php else: ?>
        <form method="post">
            <input type="hidden" name="action" value="create_reply">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
            <input type="hidden" name="thread_id" value="<?= (int)$thread_id ?>">

            <textarea name="body" rows="4" placeholder="Write a reply..."
                      style="padding:10px; border:1px solid rgba(0,0,0,.25); border-radius:6px; width:100%;" required></textarea>

            <div style="margin-top:8px;">
                <button type="submit" class="btn">Post reply</button>
            </div>
        </form>
    <?php endif; ?>
</div>
