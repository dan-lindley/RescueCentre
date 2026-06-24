<?php
if (!defined('APP_LOADED')) exit;

$edit_user_id = (int)($_GET['id'] ?? 0);

if ($edit_user_id <= 0) {
    echo '<div class="rc-alert red">No user selected.</div>';
    return;
}

$stmt = $pdo->prepare("
    SELECT a.*, r.role_name
    FROM accounts a
    LEFT JOIN rescue_roles r ON r.role_id = a.rescue_role
    WHERE a.id = :id
      AND a.centre_id = :centre_id
    LIMIT 1
");
$stmt->execute([
    ':id' => $edit_user_id,
    ':centre_id' => $centre_id
]);
$edit_account = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$edit_account) {
    echo '<div class="rc-alert red">User not found for this centre.</div>';
    return;
}

$roleStmt = $pdo->prepare("
    SELECT role_id, role_name
    FROM rescue_roles
    WHERE centre_id = :centre_id
    ORDER BY role_name
");
$roleStmt->execute([':centre_id' => $centre_id]);
$centre_roles = $roleStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="rc-panel">
    <div class="rc-split-head">
        <div>
            <h3>Edit User Account</h3>
            <p class="rc-muted">Update this centre user's account details.</p>
        </div>
        <div class="rc-actions">
            <a href="user_accounts.php?tab=users" class="btn alt">Back to Users</a>
        </div>
    </div>

    <form method="post" action="user_accounts.php" class="xform">
        <input type="hidden" name="edit_user_account_form" value="1">
        <input type="hidden" name="edit_user_id" value="<?= (int)$edit_account['id'] ?>">

        <div class="xform-grid">
            <div class="xform-field">
                <label class="xform-label" for="first_name">First Name</label>
                <input type="text" class="xform-input" name="first_name" id="first_name" value="<?= htmlspecialchars((string)($edit_account['first_name'] ?? ''), ENT_QUOTES) ?>">
            </div>

            <div class="xform-field">
                <label class="xform-label" for="last_name">Last Name</label>
                <input type="text" class="xform-input" name="last_name" id="last_name" value="<?= htmlspecialchars((string)($edit_account['last_name'] ?? ''), ENT_QUOTES) ?>">
            </div>

            <div class="xform-field">
                <label class="xform-label" for="username">Username*</label>
                <input type="text" class="xform-input" name="username" id="username" required value="<?= htmlspecialchars((string)$edit_account['username'], ENT_QUOTES) ?>">
            </div>

            <div class="xform-field">
                <label class="xform-label" for="email">Email*</label>
                <input type="email" class="xform-input" name="email" id="email" required value="<?= htmlspecialchars((string)$edit_account['email'], ENT_QUOTES) ?>">
            </div>

            <div class="xform-field">
                <label class="xform-label" for="rescue_role">Role*</label>
                <select class="xform-input" name="rescue_role" id="rescue_role" required>
                    <option value="">Select role...</option>
                    <?php foreach ($centre_roles as $role): ?>
                        <option value="<?= (int)$role['role_id'] ?>" <?= (int)$edit_account['rescue_role'] === (int)$role['role_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)$role['role_name'], ENT_QUOTES) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="xform-field">
                <label class="xform-label" for="activation_code">Status</label>
                <select class="xform-input" name="activation_code" id="activation_code">
                    <option value="activated" <?= (string)$edit_account['activation_code'] === 'activated' ? 'selected' : '' ?>>Activated</option>
                    <option value="deactivated" <?= (string)$edit_account['activation_code'] === 'deactivated' ? 'selected' : '' ?>>Deactivated</option>
                </select>
            </div>

            <div class="xform-field">
                <label class="xform-label" for="password">New Password</label>
                <input type="text" class="xform-input" name="password" id="password" autocomplete="off" placeholder="Leave blank to keep current password">
            </div>

            <div class="xform-field">
                <label class="xform-label" for="password_confirm">Confirm New Password</label>
                <input type="text" class="xform-input" name="password_confirm" id="password_confirm" autocomplete="off" placeholder="Repeat new password">
            </div>
        </div>

        <label class="rc-inline-list">
            <input type="checkbox" name="approved" value="1" <?= !empty($edit_account['approved']) ? 'checked' : '' ?>>
            Approved
        </label>

        <div class="xform-actions">
            <button type="submit" class="btn green">Save User</button>
            <a href="user_accounts.php?tab=users" class="btn alt">Cancel</a>
        </div>
    </form>
</div>
