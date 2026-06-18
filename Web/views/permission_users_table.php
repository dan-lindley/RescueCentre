<?php
// Requires $selected_user_id, $pdo, $centre_id already available

require_once __DIR__ . '/../operations/module_permissions.php';
module_permissions_register_all($pdo, (int)$centre_id);

// Load selected user
$stmt = $pdo->prepare("
    SELECT id, username, first_name, last_name, rescue_role
    FROM accounts
    WHERE id = :id AND centre_id = :centre_id
    LIMIT 1
");
$stmt->execute([
    ':id' => $selected_user_id,
    ':centre_id' => $centre_id
]);
$selected_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$selected_user) {
    echo '<div class="rc-alert red">User not found.</div>';
    exit;
}

// Permissions (excluding system_) - ORDER BY type then key
$permissions = $pdo->query("
    SELECT permission_id, permission_key, description, type
    FROM rescue_permissions
    WHERE permission_key NOT LIKE 'system\_%'
    ORDER BY COALESCE(NULLIF(type,''),'other'), permission_key
")->fetchAll(PDO::FETCH_ASSOC);

// Group permissions by STORED TYPE (page/action/field/etc.)
$grouped_permissions = [];
foreach ($permissions as $perm) {
    $ptype = $perm['type'] ?? '';
    $ptype = trim((string)$ptype);
    if ($ptype === '') $ptype = 'other';

    // Pretty group label: custom_field -> CUSTOM FIELD
    $groupLabel = $ptype === 'module' ? 'MODULES' : strtoupper(str_replace(['_', '-'], ' ', $ptype));

    $grouped_permissions[$groupLabel][] = $perm;
}

// Load overrides
$userOverrides = [];
$roleCentrePerms = [];
$roleSystemPerms = [];
$role_id = (int)$selected_user['rescue_role'];

// User overrides
$stmt = $pdo->prepare("
    SELECT permission_id, allow
    FROM rescue_user_permissions
    WHERE user_id = :uid
");
$stmt->execute([':uid' => $selected_user_id]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $userOverrides[(int)$row['permission_id']] = (int)$row['allow'];
}

// Centre role overrides
$stmt = $pdo->prepare("
    SELECT permission_id, allow
    FROM rescue_role_permissions
    WHERE centre_id = :centre_id AND role_id = :rid
");
$stmt->execute(['centre_id' => $centre_id, 'rid' => $role_id]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $roleCentrePerms[(int)$row['permission_id']] = (int)$row['allow'];
}

// System role defaults
$stmt = $pdo->prepare("
    SELECT permission_id, allow
    FROM rescue_role_permissions
    WHERE centre_id = 0 AND role_id = :rid
");
$stmt->execute(['rid' => $role_id]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $roleSystemPerms[(int)$row['permission_id']] = (int)$row['allow'];
}

// Helper functions
$role_allow_for = function($pid) use ($roleCentrePerms, $roleSystemPerms) {
    $pid = (int)$pid;
    if (array_key_exists($pid, $roleCentrePerms)) return (int)$roleCentrePerms[$pid];
    if (array_key_exists($pid, $roleSystemPerms)) return (int)$roleSystemPerms[$pid];
    return 0;
};

$get_state = function($pid) use ($userOverrides, $role_allow_for) {
    $pid = (int)$pid;

    if (array_key_exists($pid, $userOverrides)) {
        $ov = (int)$userOverrides[$pid];
        return [
            'mode' => 'override',
            'override' => $ov,
            'role' => $role_allow_for($pid),
            'effective' => $ov,
        ];
    }

    $roleVal = $role_allow_for($pid);
    return [
        'mode' => 'inherit',
        'override' => null,
        'role' => $roleVal,
        'effective' => $roleVal,
    ];
};
?>

<div class="rc-panel">
<div class="rc-split-head">
    <div>
        <h3>Permissions for: <?=htmlspecialchars($selected_user['username'], ENT_QUOTES)?></h3>
        <p class="rc-muted">Set user-specific overrides, or inherit the role default.</p>
    </div>
</div>

<form method="post" action="user_accounts.php" class="xform">
    <input type="hidden" name="user_permissions_form" value="1">
    <input type="hidden" name="selected_user_id" value="<?=$selected_user_id?>">
    <input type="hidden" name="active_tab" value="tab-user-perms">

    <div class="rc-table-scroll">
    <table class="rc-table row-hover">
        <thead>
            <tr>
                <th>Permission</th>
                <th>Role Default</th>
                <th>User Override</th>
                <th>Effective</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($grouped_permissions as $groupLabel => $perms): ?>
                <!-- TYPE Heading Row -->
                <tr>
                    <td colspan="4">
                        <span class="rc-badge mid"><?=htmlspecialchars($groupLabel, ENT_QUOTES)?></span>
                    </td>
                </tr>

                <?php foreach ($perms as $perm): ?>
                    <?php $state = $get_state((int)$perm['permission_id']); ?>
                    <tr>
                        <td>
                            <!-- permission.name -->
                            <strong><?=htmlspecialchars($perm['permission_key'], ENT_QUOTES)?></strong>

                            <!-- reader viewable text -->
                            <?php if (!empty($perm['description'])): ?>
                                <br><span class="rc-muted"><?=htmlspecialchars($perm['description'], ENT_QUOTES)?></span>
                            <?php endif; ?>
                        </td>

                        <td><span class="rc-badge <?= $state['role'] ? 'ok' : 'bad' ?>"><?= $state['role'] ? "Allow" : "Deny" ?></span></td>

                        <td>
                            <select name="perm[<?= (int)$perm['permission_id'] ?>]" class="xform-input">
                                <option value="inherit" <?= $state['mode']==="inherit" ? "selected" : "" ?>>Inherit</option>
                                <option value="1" <?= $state['override']===1 ? "selected" : "" ?>>Allow</option>
                                <option value="0" <?= $state['override']===0 ? "selected" : "" ?>>Deny</option>
                            </select>
                        </td>

                        <td><span class="rc-badge <?= $state['effective'] ? 'ok' : 'bad' ?>"><?= $state['effective'] ? "Allow" : "Deny" ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <div class="xform-actions">
        <button class="btn green">Save User Permissions</button>
    </div>
</form>
</div>
