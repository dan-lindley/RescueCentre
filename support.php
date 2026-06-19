<?php
include 'dashmain.php';
include 'getcentreinfo.php';

if (!isset($pdo)) {
    echo '<div class="rc-alert red">Database connection missing.</div>';
    exit;
}
if (!isset($centre_id) || !$centre_id) {
    echo '<div class="rc-alert red">Centre context missing.</div>';
    exit;
}

/* -------------------------------------------
   CONFIG: external support links (EDIT THESE)
------------------------------------------- */
$facebook_support_group_url = 'https://www.facebook.com/groups/6347770428619595/';
$whatsapp_support_group_url = 'https://chat.whatsapp.com/LsMUUtKlBXiAlv3Jl5NAWO';

/* -------------------------------------------
   Helpers
------------------------------------------- */
function decode_thread($json): array {
    if (!$json) return [];
    $arr = json_decode($json, true);
    return is_array($arr) ? $arr : [];
}

function build_qs(array $overrides = []): string {
    $base = $_GET;
    foreach ($overrides as $k => $v) $base[$k] = $v;
    foreach ($base as $k => $v) if ($v === '' || $v === null) unset($base[$k]);
    return http_build_query($base);
}

function label_progress(string $p): string {
    if ($p === 'not_started') return 'Not started';
    if ($p === 'in_dev') return 'In dev';
    if ($p === 'completed') return 'Completed';
    return $p;
}

function label_priority(string $p): string {
    if ($p === 'low') return 'Low';
    if ($p === 'med') return 'Medium';
    if ($p === 'high') return 'High';
    return $p;
}

function status_band_style(string $progress): string {
    if ($progress === 'completed') return 'border-left:6px solid #22c55e;';
    if ($progress === 'in_dev') return 'border-left:6px solid #f59e0b;';
    return 'border-left:6px solid #ef4444;';
}

/* -------------------------------------------
   Flash messages (?msg=) - consistent with controller
------------------------------------------- */
$msg = $_GET['msg'] ?? '';
$alertHtml = '';

if ($msg === 'added') {
    $alertHtml = '<div class="rc-alert green">Ticket submitted. Thank you.</div>';
} elseif ($msg === 'updated') {
    $alertHtml = '<div class="rc-alert green">Ticket updated.</div>';
} elseif ($msg === 'missing') {
    $alertHtml = '<div class="rc-alert red">Please complete all required fields.</div>';
} elseif ($msg === 'personal') {
    $alertHtml = '<div class="rc-alert red">Your ticket appears to include personal information. This system is public. For login/account issues email <strong>support@myrescuecentre.com</strong>.</div>';
} elseif ($msg === 'forbidden') {
    $alertHtml = '<div class="rc-alert red">You do not have permission to do that.</div>';
} elseif ($msg === 'error') {
    $alertHtml = '<div class="rc-alert red">Something went wrong. Please try again.</div>';
}

/* -------------------------------------------
   Inputs: search + filters + paging
------------------------------------------- */
$q        = trim($_GET['q'] ?? '');
$progress = trim($_GET['progress'] ?? '');
$priority = trim($_GET['priority'] ?? '');
$page     = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;

$allowed_progress = ['not_started','in_dev','completed'];
$allowed_priority = ['low','med','high'];

if ($progress && !in_array($progress, $allowed_progress, true)) $progress = '';
if ($priority && !in_array($priority, $allowed_priority, true)) $priority = '';

$perPage = 25;
$offset  = ($page - 1) * $perPage;

/* -------------------------------------------
   Build WHERE
------------------------------------------- */
$where  = ['t.is_hidden = 0', '(t.duplicate_of IS NULL OR t.duplicate_of = 0)'];
$params = [];

if ($q !== '') {
    $where[] = '(t.subject LIKE :q OR t.description LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if ($progress !== '') {
    $where[] = 't.progress = :progress';
    $params[':progress'] = $progress;
}
if ($priority !== '') {
    $where[] = 't.priority = :priority';
    $params[':priority'] = $priority;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

/* -------------------------------------------
   Count total (pager)
------------------------------------------- */
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM rescue_tickets t $whereSql");
$countStmt->execute($params);
$totalRows  = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

/* -------------------------------------------
   Fetch rows
------------------------------------------- */
$listSql = "
    SELECT
        t.id,
        t.centre_name,
        t.created_at,
        t.subject,
        t.description,
        t.priority,
        t.progress,
        t.last_activity_at,
        t.admin_thread
    FROM rescue_tickets t
    $whereSql
    ORDER BY t.last_activity_at DESC, t.id DESC
    LIMIT $perPage OFFSET $offset
";
$listStmt = $pdo->prepare($listSql);
$listStmt->execute($params);
$tickets = $listStmt->fetchAll(PDO::FETCH_ASSOC);

/* -------------------------------------------
   Page
------------------------------------------- */
echo template_admin_header(
    'Support Tickets - ' . ($rescue_name ?? 'Rescue Centre') . ' - Rescue Centre - Rescue Management System',
    'support',
    'support'
);
?>


<div class="content-title">
    <div class="title">
        <div class="txt">
            <h2 class="pagehead">Support Tickets</h2>
            <p>Public bug reports &amp; feature requests (visible to all centres)</p>
        </div>
    </div>
    <div class="btns">
        <a href="#createTicket" class="btn blue">➕ Create Ticket</a>
        <a href="<?= htmlspecialchars($facebook_support_group_url) ?>" target="_blank" rel="noopener" class="btn blue">Facebook Support</a>
        <a href="<?= htmlspecialchars($whatsapp_support_group_url) ?>" target="_blank" rel="noopener" class="btn green">WhatsApp Support</a>
    </div>
</div>

<div class="rc-stack">

    <?= $alertHtml ?>

    <!-- BEFORE YOU POST -->
    <div class="rc-alert amber">
        <strong>Before you post</strong><br>
        This ticket system is <strong>public and visible to all centres</strong>.<br>
        Do <strong>NOT</strong> include personal information (names, emails, phone numbers, animal IDs, addresses, or login details).<br>
        This system is for <strong>bugs and feature requests only</strong>.<br>
        Login or account issues must be emailed to <strong>support@myrescuecentre.com</strong>.
    </div>

    <!-- BACKLOG -->
    <div class="rc-panel">
        <div class="rc-split-head">
            <div>
                <h3 style="margin-bottom:4px;">Ticket Backlog</h3>
                <div class="rc-muted">
                    Showing <?= number_format($totalRows) ?> tickets<?= $q !== '' ? ' matching “' . htmlspecialchars($q) . '”' : '' ?>.
                </div>
            </div>

            <form class="xform-actions" method="get" action="support.php">
                <input type="text" name="q" class="xform-input" value="<?= htmlspecialchars($q) ?>" placeholder="Search tickets...">

                <select name="progress" class="xform-input">
                    <option value="">All status</option>
                    <option value="not_started" <?= $progress==='not_started'?'selected':'' ?>>Not started</option>
                    <option value="in_dev" <?= $progress==='in_dev'?'selected':'' ?>>In dev</option>
                    <option value="completed" <?= $progress==='completed'?'selected':'' ?>>Completed</option>
                </select>

                <select name="priority" class="xform-input">
                    <option value="">All priority</option>
                    <option value="low" <?= $priority==='low'?'selected':'' ?>>Low</option>
                    <option value="med" <?= $priority==='med'?'selected':'' ?>>Medium</option>
                    <option value="high" <?= $priority==='high'?'selected':'' ?>>High</option>
                </select>

                <button class="btn grey" type="submit">Search</button>

                <?php if ($q !== '' || $progress !== '' || $priority !== '' || $page > 1): ?>
                    <a class="btn" href="support.php">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="rc-table-scroll">
            <table class="rc-table row-hover" id="ticketsTable">
                <thead>
                    <tr>
                        <th style="width:100px;">Ticket</th>
                        <th style="width:500px;" >Subject &amp; Description</th>
                        <th style="width:180px;">Centre</th>
                        <th style="width:180px;">Status</th>
                        <th style="width:180px;">Priority</th>
                        <th style="width:180px;">Last activity</th>
                    </tr>
                </thead>
                <tbody>

                <?php foreach ($tickets as $t): ?>
                    <?php
                        $id = (int)$t['id'];
                        $thread = decode_thread($t['admin_thread']);
                        $desc = (string)$t['description'];
                        $short = (mb_strlen($desc) > 220) ? (mb_substr($desc, 0, 220) . '…') : $desc;

                        $rowStyle = status_band_style((string)$t['progress']);

                        $progBadge = 'rc-badge na';
                        if ($t['progress'] === 'in_dev') $progBadge = 'rc-badge warn';
                        if ($t['progress'] === 'completed') $progBadge = 'rc-badge ok';

                        $priBadge = 'rc-badge na';
                        if ($t['priority'] === 'med')  $priBadge = 'rc-badge warn';
                        if ($t['priority'] === 'high') $priBadge = 'rc-badge bad';
                    ?>

                    <!-- SUMMARY ROW -->
                    <tr data-ticket="<?= $id ?>">
                        <td style="<?= $rowStyle ?> padding-left:10px;">
                            <strong>#<?= $id ?></strong><br>
                            <span class="rc-muted"><?= htmlspecialchars($t['created_at']) ?></span>
                        </td>

                        <td>
                            <strong><?= htmlspecialchars($t['subject']) ?></strong><br>
                            <span class="rc-muted"><?= nl2br(htmlspecialchars($short)) ?></span><br>
                            <button type="button" class="btn alt readmore-link" data-id="<?= $id ?>">Read more</button>
                        </td>

                        <td><span class="rc-badge na"><?= htmlspecialchars($t['centre_name']) ?></span></td>
                        <td><span class="<?= $progBadge ?>"><?= htmlspecialchars(label_progress((string)$t['progress'])) ?></span></td>
                        <td><span class="<?= $priBadge ?>"><?= htmlspecialchars(label_priority((string)$t['priority'])) ?></span></td>
                        <td><span class="rc-muted"><?= htmlspecialchars($t['last_activity_at'] ?: $t['created_at']) ?></span></td>
                    </tr>

                    <!-- DETAILS ROW (hidden by default) -->
                    <tr class="ticket-details-row" id="ticket_details_<?= $id ?>" data-ticket="<?= $id ?>" style="display:none;">
                        <td colspan="6">
                            <div class="rc-card-muted">
                                <strong>Description</strong>
                                <div style="margin-top:6px; white-space:pre-wrap;"><?= nl2br(htmlspecialchars($desc)) ?></div>

                                <?php if (!empty($thread)): ?>
                                    <hr>
                                    <strong>Admin updates</strong>
                                    <?php foreach ($thread as $entry): ?>
                                        <div class="rc-card">
                                            <div class="rc-muted">
                                                <?= htmlspecialchars($entry['at'] ?? '') ?>
                                                <?php if (!empty($entry['type'])): ?>
                                                    · <?= htmlspecialchars($entry['type']) ?>
                                                <?php endif; ?>
                                            </div>
                                            <div style="white-space:pre-wrap;"><?= nl2br(htmlspecialchars($entry['msg'] ?? '')) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>

                <?php endforeach; ?>

                <?php if (empty($tickets)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="rc-alert blue">No tickets found.</div>
                        </td>
                    </tr>
                <?php endif; ?>

                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php $prev = $page - 1; $next = $page + 1; ?>

                <?php if ($page > 1): ?>
                    <a href="support.php?<?= htmlspecialchars(build_qs(['page'=>$prev])) ?>">&larr; Prev</a>
                <?php endif; ?>

                <?php
                    $start = max(1, $page - 3);
                    $end   = min($totalPages, $page + 3);
                    if ($start > 1) echo '<span>…</span>';
                    for ($p = $start; $p <= $end; $p++) {
                        if ($p === $page) {
                            echo '<span>' . $p . '</span>';
                        } else {
                            echo '<a href="support.php?' . htmlspecialchars(build_qs(['page'=>$p])) . '">' . $p . '</a>';
                        }
                    }
                    if ($end < $totalPages) echo '<span>…</span>';
                ?>

                <?php if ($page < $totalPages): ?>
                    <a href="support.php?<?= htmlspecialchars(build_qs(['page'=>$next])) ?>">Next &rarr;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- CREATE TICKET (xform styling) -->
    <div class="rc-panel" id="createTicket">
        <h3>Create a Ticket</h3>
        <p class="rc-muted">Add a bug or feature request to the public backlog.</p>

        <form method="post" action="/controllers/support_controller.php" class="xform" id="create_ticket_form">
            <div class="xform-grid">

                <div class="xform-field">
                    <label class="xform-label" for="ticket_subject">Subject</label>
                    <input type="text" name="subject" id="ticket_subject" class="xform-input" required maxlength="180"
                           placeholder="Short summary (e.g. Care plan print button does nothing)">
                </div>

                <div class="xform-field">
                    <label class="xform-label" for="ticket_priority">Priority</label>
                    <select name="priority" id="ticket_priority" class="xform-input" required>
                        <option value="low">Low</option>
                        <option value="med">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>

                <div class="xform-field" style="grid-column: span 4;">
                    <label class="xform-label" for="ticket_description">Description</label>
                    <textarea name="description" id="ticket_description" class="xform-input" rows="4" required
                              placeholder="Describe the bug or feature request. This content is public to all centres."></textarea>
                </div>

            </div>

            <input type="hidden" name="action" value="create_ticket">

            <div class="xform-actions">
                <button type="submit" class="btn blue">Submit Ticket</button>
            </div>
        </form>
    </div>

</div>

<script>
(function(){
    const links = document.querySelectorAll('.readmore-link');
    const detailRows = document.querySelectorAll('.ticket-details-row');

    function closeAll(exceptId) {
        detailRows.forEach(r => {
            const id = r.getAttribute('data-ticket');
            if (!exceptId || id !== String(exceptId)) r.style.display = 'none';
        });
        links.forEach(l => {
            const id = l.getAttribute('data-id');
            if (!exceptId || id !== String(exceptId)) l.textContent = 'Read more';
        });
    }

    links.forEach(link => {
        link.addEventListener('click', function(){
            const id = this.getAttribute('data-id');
            const row = document.getElementById('ticket_details_' + id);
            const isOpen = row && row.style.display === 'table-row';

            // B: only one open at once
            closeAll(id);

            if (row) {
                if (isOpen) {
                    row.style.display = 'none';
                    this.textContent = 'Read more';
                } else {
                    row.style.display = 'table-row';
                    this.textContent = 'Hide';
                }
            }
        });
    });
})();
</script>

<?= template_admin_footer() ?>

