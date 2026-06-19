<?php
// views/groups/message_board.php
// Network message board tab for viewnetwork.php
// Requires: $pdo and $network_id from wrapper (viewnetwork.php)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_user_id = isset($_SESSION['account_id']) ? (int)$_SESSION['account_id'] : 0;

// $network_id is provided by viewnetwork.php wrapper.
// Fallback to GET for safety.
$nid = isset($network_id) ? (int)$network_id : (int)($_GET['network_id'] ?? 0);

if ($nid <= 0) {
    echo '<div class="alert-box alert-red pad-3 mar-bot-3">Missing network_id.</div>';
    return;
}

// Filters + pagination
$tag  = isset($_GET['tag']) ? trim($_GET['tag']) : '';
$q    = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

// Thread view?
$thread_id = isset($_GET['thread']) ? (int)$_GET['thread'] : 0;

// Load the network board (one per network/group)
$stmt = $pdo->prepare("
    SELECT id, name
    FROM rescue_boards
    WHERE network_id = :nid
      AND slug = 'network'
      AND is_enabled = 1
    LIMIT 1
");
$stmt->execute([':nid' => $nid]);
$board = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$board) {
    echo '<div class="alert-box alert-red pad-3 mar-bot-3">Network message board not found for this network.</div>';
    return;
}

function mb_name($row) {
    $name = trim(($row['user_first_name'] ?? '') . ' ' . ($row['user_last_name'] ?? ''));
    if (!empty($row['rescue_name'])) $name .= ' (' . $row['rescue_name'] . ')';
    return $name ?: 'Unknown';
}

// Build links back into viewnetwork.php (preserve network + tab)
$baseQs = ['network_id' => $nid, 'tab' => 'board'];
$makeUrl = function(array $extra) use ($baseQs) {
    // Keep only allowed keys we use on this tab
    $merged = array_merge($baseQs, $extra);
    // Remove nulls
    foreach ($merged as $k => $v) {
        if ($v === null || $v === '') unset($merged[$k]);
    }
    return 'viewnetwork.php?' . http_build_query($merged);
};

// ------------------------------
// THREAD VIEW (starter + replies)
// ------------------------------
if ($thread_id > 0) {

    // Reply pagination
    $rpage    = max(1, (int)($_GET['rpage'] ?? 1));
    $rPerPage = 20;
    $rOffset  = ($rpage - 1) * $rPerPage;

    // Load starter
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
          AND p.board_id = :bid
          AND p.parent_id IS NULL
        LIMIT 1
    ");
    $stmt->execute([':id' => $thread_id, ':bid' => (int)$board['id']]);
    $starter = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$starter) {
        echo '<div class="alert-box alert-red pad-3 mar-bot-3">Thread not found in this network.</div>';
        return;
    }

    // Tags for starter
    $stmt = $pdo->prepare("
        SELECT t.slug, t.label
        FROM rescue_tags t
        JOIN rescue_post_tags pt ON pt.tag_id = t.id
        WHERE pt.post_id = :pid
        ORDER BY t.label
    ");
    $stmt->execute([':pid' => $thread_id]);
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count replies
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS cnt
        FROM rescue_posts
        WHERE parent_id = :tid
          AND status IN ('active','locked')
    ");
    $stmt->execute([':tid' => $thread_id]);
    $totalReplies = (int)($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);
    $totalRPages  = max(1, (int)ceil($totalReplies / $rPerPage));

    if ($rpage > $totalRPages) {
        $rpage = $totalRPages;
        $rOffset = ($rpage - 1) * $rPerPage;
    }

    // Fetch replies
    $stmt = $pdo->prepare("
        SELECT
          p.*,
          a.first_name AS user_first_name,
          a.last_name  AS user_last_name,
          rc.rescue_name AS rescue_name
        FROM rescue_posts p
        JOIN accounts a ON a.id = p.user_id
        LEFT JOIN rescue_centres rc ON rc.rescue_id = a.centre_id
        WHERE p.parent_id = :tid
          AND p.status IN ('active','locked')
        ORDER BY p.created_at ASC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':tid', $thread_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int)$rPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$rOffset, PDO::PARAM_INT);
    $stmt->execute();
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $starterAlert = (((int)$starter['user_id'] === $current_user_id) && $current_user_id > 0) ? 'alert-amber' : 'alert-blue';
    ?>

    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
        <div>
            <a href="<?= htmlspecialchars($makeUrl([])) ?>" style="text-decoration:none;">← Back to Network Board</a>
            <h3 style="margin:10px 0 0 0;"><?= htmlspecialchars($starter['title'] ?? '(Untitled)') ?></h3>

            <?php if (!empty($tags)): ?>
                <div style="margin-top:8px; display:flex; gap:6px; flex-wrap:wrap;">
                    <?php foreach ($tags as $chip): ?>
                        <a href="<?= htmlspecialchars($makeUrl(['tag' => $chip['slug'], 'thread' => null, 'page' => 1])) ?>"
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

    <!-- Starter -->
    <div class="alert-box <?= $starterAlert ?> pad-3 mar-bot-0" style="border-radius:8px; color:#1f2933;">
        <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-start; flex-wrap:wrap;">
            <div style="font-weight:700;">Thread starter • <?= htmlspecialchars(mb_name($starter)) ?></div>
            <div style="font-size:12px; opacity:.9; white-space:nowrap;"><?= htmlspecialchars($starter['created_at']) ?></div>
        </div>
        <div style="margin-top:8px; white-space:pre-wrap; font-size:13px; opacity:.95;"><?= htmlspecialchars($starter['body']) ?></div>
    </div>

    <!-- Replies -->
    <div id="replies" style="margin-top:10px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
            <h4 style="margin:0;">Replies (<?= (int)$totalReplies ?>)</h4>
            <?php if ($totalRPages > 1): ?>
                <div style="opacity:.8; font-size:13px;">Page <?= (int)$rpage ?> of <?= (int)$totalRPages ?></div>
            <?php endif; ?>
        </div>

        <?php if ($totalReplies === 0): ?>
            <div class="alert-box alert-teal pad-3 mar-bot-3" style="border-radius:8px; color:#1f2933; margin-top:10px;">
                No replies yet.
            </div>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:0; margin-top:10px;">
                <?php foreach ($replies as $p): ?>
                    <?php $replyAlert = (((int)$p['user_id'] === $current_user_id) && $current_user_id > 0) ? 'alert-amber' : 'alert-teal'; ?>
                    <div class="alert-box <?= $replyAlert ?> pad-3 mar-bot-0" style="border-radius:8px; color:#1f2933;">
                        <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-start; flex-wrap:wrap;">
                            <div style="font-weight:700;">Reply • <?= htmlspecialchars(mb_name($p)) ?></div>
                            <div style="font-size:12px; opacity:.9; white-space:nowrap;"><?= htmlspecialchars($p['created_at']) ?></div>
                        </div>
                        <div style="margin-top:8px; white-space:pre-wrap; font-size:13px; opacity:.95;"><?= htmlspecialchars($p['body']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalRPages > 1): ?>
                <div style="margin-top:12px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                    <?php if ($rpage > 1): ?>
                        <a class="btn btn-light" href="<?= htmlspecialchars($makeUrl(['thread' => $thread_id, 'rpage' => $rpage - 1])) ?>#replies">← Prev</a>
                    <?php endif; ?>
                    <div style="opacity:.8;">Page <?= (int)$rpage ?> of <?= (int)$totalRPages ?></div>
                    <?php if ($rpage < $totalRPages): ?>
                        <a class="btn btn-light" href="<?= htmlspecialchars($makeUrl(['thread' => $thread_id, 'rpage' => $rpage + 1])) ?>#replies">Next →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <hr>

    <?php global $csrf_token; ?>
    <div id="reply" style="border:1px solid #e5e5e5; border-radius:10px; padding:12px;">
        <h4 style="margin:0 0 10px 0;">Reply</h4>

        <?php if (($starter['status'] ?? '') === 'locked'): ?>
            <div class="alert-box alert-red pad-3 mar-bot-3" style="border-radius:8px;">This thread is locked.</div>
        <?php else: ?>
            <form method="post" action="viewnetwork.php">
                <input type="hidden" name="action" value="create_reply">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="network_id" value="<?= (int)$nid ?>">
                <input type="hidden" name="thread_id" value="<?= (int)$thread_id ?>">
                <input type="hidden" name="return_to" value="<?= htmlspecialchars($makeUrl(['thread' => $thread_id])) ?>">

                <textarea name="body" rows="4" placeholder="Write a reply..."
                          style="padding:10px; border:1px solid rgba(0,0,0,.25); border-radius:6px; width:100%;" required></textarea>

                <div style="margin-top:8px;">
                    <button type="submit" class="btn">Post reply</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <?php
    return;
}

// ------------------------------
// THREAD LIST VIEW
// ------------------------------

// Count total threads
$countParams = [':board_id' => (int)$board['id']];
$countSql = "SELECT COUNT(*) AS cnt FROM rescue_posts p ";

if ($tag !== '') {
    $countSql .= " JOIN rescue_post_tags pt ON pt.post_id = p.id JOIN rescue_tags t ON t.id = pt.tag_id ";
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

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// Fetch threads
$params = [':board_id' => (int)$board['id']];
$sql = "
    SELECT
      p.id, p.title, p.body, p.user_id, p.created_at, p.last_activity_at,
      a.first_name AS user_first_name, a.last_name AS user_last_name,
      rc.rescue_name AS rescue_name,
      (SELECT COUNT(*) FROM rescue_posts r WHERE r.parent_id = p.id AND r.status='active') AS reply_count
    FROM rescue_posts p
    JOIN accounts a ON a.id = p.user_id
    LEFT JOIN rescue_centres rc ON rc.rescue_id = a.centre_id
";

if ($tag !== '') {
    $sql .= " JOIN rescue_post_tags pt ON pt.post_id = p.id JOIN rescue_tags t ON t.id = pt.tag_id ";
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
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$threads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tags for chips
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

global $csrf_token;
?>

<div style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
    <div>
        <h3 style="margin:0;"><?= htmlspecialchars($board['name']) ?> — Message Board</h3>
        <p style="margin:6px 0 0 0; opacity:.8;">Threads are only visible within this network.</p>
    </div>

    <form method="get" action="viewnetwork.php" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <input type="hidden" name="tab" value="board">
        <input type="hidden" name="network_id" value="<?= (int)$nid ?>">

        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search..."
               style="padding:8px 10px; border:1px solid #ccc; border-radius:6px;">

        <input type="text" name="tag" value="<?= htmlspecialchars($tag) ?>" placeholder="Tag"
               style="padding:8px 10px; border:1px solid #ccc; border-radius:6px;">

        <button type="submit" class="btn">Filter</button>

        <?php if ($tag !== '' || $q !== ''): ?>
            <a class="btn btn-light" href="<?= htmlspecialchars($makeUrl([])) ?>">Clear</a>
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
                $threadAlert = (((int)$t['user_id'] === $current_user_id) && $current_user_id > 0) ? 'alert-amber' : 'alert-blue';
                $chips = $tagsByThread[(int)$t['id']] ?? [];
            ?>
            <div class="alert-box <?= $threadAlert ?> pad-3 mar-bot-0" style="border-radius:8px; color:#1f2933;">
                <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-start;">
                    <div style="min-width:0;">
                        <a href="<?= htmlspecialchars($makeUrl(['thread' => (int)$t['id'], 'page' => 1, 'rpage' => 1])) ?>"
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

                <div style="margin-top:6px; font-size:13px; opacity:.95;">
                    <?= htmlspecialchars(mb_strimwidth(strip_tags($t['body']), 0, 140, '…')) ?>
                </div>

                <div style="margin-top:8px; display:flex; justify-content:space-between; gap:10px; align-items:center;">
                    <div style="display:flex; gap:6px; flex-wrap:wrap; min-width:0;">
                        <?php foreach ($chips as $chip): ?>
                            <a href="<?= htmlspecialchars($makeUrl(['tag' => $chip['slug'], 'page' => 1])) ?>"
                               style="font-size:12px; padding:2px 8px; border-radius:999px; border:1px solid rgba(0,0,0,.18); text-decoration:none;">
                                #<?= htmlspecialchars($chip['label']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <a href="<?= htmlspecialchars($makeUrl(['thread' => (int)$t['id'], 'page' => 1, 'rpage' => 1])) ?>" style="text-decoration:none;">
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
                <a class="btn btn-light" href="<?= htmlspecialchars($makeUrl(['page' => $page - 1, 'tag' => $tag, 'q' => $q])) ?>">← Prev</a>
            <?php endif; ?>
            <div style="opacity:.8;">Page <?= (int)$page ?> of <?= (int)$totalPages ?></div>
            <?php if ($page < $totalPages): ?>
                <a class="btn btn-light" href="<?= htmlspecialchars($makeUrl(['page' => $page + 1, 'tag' => $tag, 'q' => $q])) ?>">Next →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<hr style="margin-top:14px;">

<!-- Create thread form -->
<div style="border:1px solid #e5e5e5; border-radius:10px; padding:12px; margin-top:12px;">
    <h4 style="margin:0 0 10px 0;">Create a new thread</h4>

    <form method="post" action="viewnetwork.php">
        <input type="hidden" name="action" value="create_thread">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <input type="hidden" name="network_id" value="<?= (int)$nid ?>">
        <input type="hidden" name="return_to" value="<?= htmlspecialchars($makeUrl([])) ?>">

        <div style="display:flex; flex-direction:column; gap:8px;">
            <input type="text" name="title" maxlength="200" placeholder="Title"
                   style="padding:10px; border:1px solid #ccc; border-radius:6px;" required>

            <textarea name="body" rows="4" placeholder="Write your post..."
                      style="padding:10px; border:1px solid #ccc; border-radius:6px;" required></textarea>

            <input type="text" name="tags" placeholder="Tags (comma separated)"
                   style="padding:10px; border:1px solid #ccc; border-radius:6px;">

            <div>
                <button type="submit" class="btn">Post thread</button>
            </div>
        </div>
    </form>
</div>

