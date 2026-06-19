<?php
// ---------------------------------------------------------
// USER PERMISSIONS (AJAX-enabled)
// ---------------------------------------------------------
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

if (!defined('APP_LOADED')) exit;

$is_ajax = isset($_GET['ajax']) && $_GET['ajax'] == "1";

// Load list of centre users
$stmt = $pdo->prepare("
    SELECT id, username, first_name, last_name, rescue_role
    FROM accounts
    WHERE centre_id = :centre_id
    ORDER BY username
");
$stmt->execute(['centre_id' => $centre_id]);
$centre_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If AJAX mode: load selected user & render only permissions table
if ($is_ajax) {

    $selected_user_id = (int)($_GET['user_id'] ?? 0);

    if (!$selected_user_id) {
        echo '<div class="rc-alert amber">No user selected.</div>';
        exit;
    }

    include __DIR__ . '/permissions_users_table.php';
    exit;
}
?>

<!-- Full mode (initial page load inside tab) -->

<div class="rc-panel">
    <h3>User Permissions</h3>
    <p class="rc-muted">Manage permissions for individual users.</p>

    <div class="xform">
    <div class="xform-field">
        <label class="xform-label">Select User</label>

        <select id="userPermissionSelect" class="xform-input">
            <option value="">-- Choose a user --</option>

            <?php foreach ($centre_users as $u): ?>
                <?php
                $label = $u['username'];
                if ($u['first_name'] || $u['last_name']) {
                    $label .= " (" . trim($u['first_name'] . " " . $u['last_name']) . ")";
                }
                ?>
                <option value="<?=$u['id']?>"><?=$label?></option>
            <?php endforeach; ?>
        </select>
    </div>
    </div>
</div>

<div id="userPermissionsContainer" class="rc-stack">
    <!-- AJAX-loaded content appears here -->
</div>

<script>
document.getElementById('userPermissionSelect').addEventListener('change', function() {
    let userId = this.value;
    let container = document.getElementById('userPermissionsContainer');

    if (!userId) {
        container.innerHTML = "";
        return;
    }

    container.innerHTML = '<div class="rc-card">Loading permissions...</div>';

    fetch("operations/ajax_load_user_permissions.php?ajax=1&user_id=" + userId)
        .then(res => res.text())
        .then(html => container.innerHTML = html)
        .catch(() => container.innerHTML = '<div class="rc-alert red">Error loading permissions.</div>');
});
</script>
