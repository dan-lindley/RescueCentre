<?php
// views/messageboard/feed.php
// Global feed: recent activity across all global boards (network_id IS NULL)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_user_id = isset($_SESSION['account_id']) ? (int)$_SESSION['account_id'] : 0;

// Pull recent activity (posts + replies), with author + rescue centre + board info
$stmt = $pdo->query("
    SELECT
      p.id,
      p.parent_id,
      COALESCE(p.parent_id, p.id) AS thread_id,
      p.board_id,
      p.user_id,
      p.body,
      p.created_at,

      a.first_name AS user_first_name,
      a.last_name  AS user_last_name,
      rc.rescue_name AS rescue_name,

      b.slug AS board_slug,
      b.name AS board_name
    FROM rescue_posts p
    JOIN rescue_boards b ON b.id = p.board_id
    JOIN accounts a ON a.id = p.user_id
    LEFT JOIN rescue_centres rc ON rc.rescue_id = a.centre_id
    WHERE b.network_id IS NULL
      AND p.status = 'active'
    ORDER BY p.created_at DESC
    LIMIT 50
");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Load starter titles for thread_id set (so feed links show the real thread title)
$threadIds = array_values(array_unique(array_map(fn($r) => (int)$r['thread_id'], $items)));
$titlesByThread = [];

if (!empty($threadIds)) {
    $in = implode(',', array_fill(0, count($threadIds), '?'));
    $stmt = $pdo->prepare("
        SELECT id, title
        FROM rescue_posts
        WHERE id IN ($in)
    ");
    $stmt->execute($threadIds);
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $titlesByThread[(int)$r['id']] = $r['title'];
    }
}

function mb_name($row) {
    $name = trim(($row['user_first_name'] ?? '') . ' ' . ($row['user_last_name'] ?? ''));
    if (!empty($row['rescue_name'])) $name .= ' (' . $row['rescue_name'] . ')';
    return $name ?: 'Unknown';
}
?>

<h3 style="margin:0;">Feed</h3>
<p style="margin:6px 0 14px 0; opacity:.8;">Recent activity across all boards.</p>

<?php if (empty($items)): ?>
    <div class="alert-box alert-blue pad-3 mar-bot-3" style="border-radius:8px; color:#1f2933;">
        No activity yet.
    </div>
<?php else: ?>
    <div style="display:flex; flex-direction:column; gap:0;">
        <?php foreach ($items as $it): ?>
            <?php
                $threadId = (int)$it['thread_id'];
                $isReply  = !empty($it['parent_id']);
                $title    = $titlesByThread[$threadId] ?? '(Untitled)';
                $tabSlug  = $it['board_slug'];

                // Colour rules:
                // - Starter posts in feed: blue (or amber if it's yours)
                // - Replies in feed: teal (or amber if it's yours)
                $isOwn = ((int)$it['user_id'] === $current_user_id && $current_user_id > 0);
                if ($isOwn) {
                    $alert = 'alert-amber';
                } else {
                    $alert = $isReply ? 'alert-teal' : 'alert-blue';
                }
            ?>

            <div class="alert-box <?= $alert ?> pad-3 mar-bot-1" style="border-radius:8px; color:#1f2933;">
                <!-- Top row: context left, timestamp right -->
                <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-start;">
                    <div style="min-width:0; font-size:12px; opacity:.95;">
                        <?= $isReply ? 'Reply' : 'Post' ?>
                        in
                        <a href="?tab=<?= urlencode($tabSlug) ?>" style="text-decoration:none; font-weight:700; ">
                            <?= htmlspecialchars($it['board_name']) ?>
                        </a>
                        <span style="opacity:.75;"> • </span>
                        <?= htmlspecialchars(mb_name($it)) ?>
                    </div>

                    <div style="white-space:nowrap; font-size:12px; opacity:.9; padding-top:2px;">
                        <?= htmlspecialchars($it['created_at']) ?>
                    </div>
                </div>

                <!-- Thread title link -->
                <div style="margin-top:6px; font-weight:700; font-size:17px; text-decoration:none;">
                    <a href="?tab=<?= urlencode($tabSlug) ?>&thread=<?= $threadId ?>" style="text-decoration:none;">
                        <?= htmlspecialchars($title) ?>
                    </a>
                </div>

                <!-- Snippet -->
                <div style="margin-top:6px; font-size:16px; opacity:.95;">
                    <?= htmlspecialchars(mb_strimwidth(strip_tags($it['body']), 0, 160, '…')) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
