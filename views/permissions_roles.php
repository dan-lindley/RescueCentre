<?php
// -----------------------------------------------------------------------------
// ROLE PERMISSIONS MATRIX (CENTRE-SPECIFIC)
// File: views/permissions_roles.php
// -----------------------------------------------------------------------------
// Display format:
//
// TYPE -------------------------------------
// permission_key
// description
//
// (no small "type" text line)
// -----------------------------------------------------------------------------

if (!defined('APP_LOADED')) exit;

if (!isset($pdo)) {
    // Safety guard if included incorrectly
    return;
}

require_once __DIR__ . '/../operations/module_permissions.php';
module_permissions_register_all($pdo, (int)$centre_id);

// We'll use inline style to FORCE checkboxes visible regardless of theme CSS
$checkboxInlineStyle = 'display:inline-block !important; opacity:1 !important; position:static !important; width:16px; height:16px; margin:0; padding:0; cursor:pointer;';

// -----------------------------------------------------------------------------
// 1. HANDLE SAVE
// -----------------------------------------------------------------------------
$role_perms_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role_permissions_form'])) {
    // Expect perm[role_id][permission_id] = 1 if checked
    $posted = $_POST['perm'] ?? [];

    // Load roles
    $roles = $pdo->query("
        SELECT role_id, role_name
        FROM rescue_roles
        ORDER BY role_name
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Load permissions (non system_)
    $perms = $pdo->query("
        SELECT permission_id, permission_key, description, type
        FROM rescue_permissions
        WHERE permission_key NOT LIKE 'system\_%'
        ORDER BY COALESCE(NULLIF(type,''),'other'), permission_key
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Prepare statements for upsert
    $selectStmt = $pdo->prepare("
        SELECT 1
        FROM rescue_role_permissions
        WHERE centre_id = :centre_id
          AND role_id = :role_id
          AND permission_id = :permission_id
        LIMIT 1
    ");

    $updateStmt = $pdo->prepare("
        UPDATE rescue_role_permissions
        SET allow = :allow
        WHERE centre_id = :centre_id
          AND role_id = :role_id
          AND permission_id = :permission_id
    ");

    $insertStmt = $pdo->prepare("
        INSERT INTO rescue_role_permissions (centre_id, role_id, permission_id, allow)
        VALUES (:centre_id, :role_id, :permission_id, :allow)
    ");

    foreach ($roles as $r) {
        $rid = (int)$r['role_id'];

        foreach ($perms as $p) {
            $pid = (int)$p['permission_id'];

            // If checkbox is present, allow = 1, otherwise 0
            $isAllowed = (isset($posted[$rid]) && isset($posted[$rid][$pid])) ? 1 : 0;

            // Check if row exists
            $selectStmt->execute([
                ':centre_id'     => $centre_id,
                ':role_id'       => $rid,
                ':permission_id' => $pid
            ]);
            $existingId = $selectStmt->fetchColumn();

            if ($existingId) {
                $updateStmt->execute([
                    ':allow'         => $isAllowed,
                    ':centre_id'     => $centre_id,
                    ':role_id'       => $rid,
                    ':permission_id' => $pid
                ]);
            } else {
                $insertStmt->execute([
                    ':allow'         => $isAllowed,
                    ':centre_id'     => $centre_id,
                    ':role_id'       => $rid,
                    ':permission_id' => $pid
                ]);
            }
        }
    }

    $role_perms_msg = 'Role permissions updated for this centre.';
}

// -----------------------------------------------------------------------------
// 2. LOAD MATRIX DATA
// -----------------------------------------------------------------------------

// Roles
$roles = $pdo->query("
    SELECT role_id, role_name
    FROM rescue_roles
    ORDER BY role_name
")->fetchAll(PDO::FETCH_ASSOC);

// Permissions (non system_)
$permissions = $pdo->query("
    SELECT permission_id, permission_key, description, type
    FROM rescue_permissions
    WHERE permission_key NOT LIKE 'system\_%'
    ORDER BY COALESCE(NULLIF(type,''),'other'), permission_key
")->fetchAll(PDO::FETCH_ASSOC);

// Group permissions by STORED TYPE (page/action/field/etc.)
$grouped_permissions = []; // [TYPE_LABEL] => [perm,...]
foreach ($permissions as $perm) {
    $ptype = $perm['type'] ?? '';
    $ptype = trim((string)$ptype);
    if ($ptype === '') $ptype = 'other';

    // Make a nice heading label: "field" -> "FIELD", "custom_field" -> "CUSTOM FIELD"
    $groupLabel = $ptype === 'module' ? 'MODULES' : strtoupper(str_replace(['_', '-'], ' ', $ptype));

    if (!isset($grouped_permissions[$groupLabel])) {
        $grouped_permissions[$groupLabel] = [];
    }
    $grouped_permissions[$groupLabel][] = $perm;
}

// Load role permissions: centre-specific overrides + system defaults
$centrePerms = []; // [role_id][permission_id] = allow
$systemPerms = []; // [role_id][permission_id] = allow

// Centre-specific rows
$stmt = $pdo->prepare("
    SELECT role_id, permission_id, allow
    FROM rescue_role_permissions
    WHERE centre_id = :centre_id
");
$stmt->execute([':centre_id' => $centre_id]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $centrePerms[(int)$row['role_id']][(int)$row['permission_id']] = (int)$row['allow'];
}

// System-level defaults (centre_id = 0)
$stmt = $pdo->query("
    SELECT role_id, permission_id, allow
    FROM rescue_role_permissions
    WHERE centre_id = 0
");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $systemPerms[(int)$row['role_id']][(int)$row['permission_id']] = (int)$row['allow'];
}

// Helper to get effective allow for matrix
$effective_allow = function($role_id, $permission_id) use ($centrePerms, $systemPerms) {
    $r = (int)$role_id;
    $p = (int)$permission_id;

    if (isset($centrePerms[$r]) && array_key_exists($p, $centrePerms[$r])) {
        return (int)$centrePerms[$r][$p];
    }
    if (isset($systemPerms[$r]) && array_key_exists($p, $systemPerms[$r])) {
        return (int)$systemPerms[$r][$p];
    }
    return 0;
};
?>

<?php if ($role_perms_msg): ?>
    <div class="rc-alert green">
        <?=htmlspecialchars($role_perms_msg, ENT_QUOTES)?>
    </div>
<?php endif; ?>

<div class="rc-panel">
    <h3>Role Permissions</h3>
    <p class="rc-muted">Configure which roles can access each feature for this centre.</p>

    <?php if (!$roles || !$permissions): ?>
        <div class="rc-alert amber">No roles or permissions found.</div>
    <?php else: ?>

        <!-- Local style to make sure checkboxes are visible -->
        <style>
            /* Extra belt-and-braces in case theme is aggressive */
            .permissions-matrix input[type="checkbox"] {
                display: inline-block !important;
                opacity: 1 !important;
                position: static !important;
                width: 16px;
                height: 16px;
                margin: 0;
                padding: 0;
                cursor: pointer;
            }

            .permissions-matrix {
                max-height: 140vh;
                overflow: auto;
            }

            .permissions-matrix thead th {
                position: sticky;
                top: 0;
                z-index: 5;
                background: var(--rc-surface);
                box-shadow: 0 1px 0 var(--rc-border);
            }
        </style>

        <form method="post" action="user_accounts.php">
            <input type="hidden" name="role_permissions_form" value="1">
            <input type="hidden" name="active_tab" value="tab-role-perms">

            <div class="rc-actions" style="justify-content:flex-start; margin:12px 0;">
                <label class="xform-label" for="permissionTypeFilter" style="margin:0;">Show</label>
                <select id="permissionTypeFilter" class="xform-input" style="width:auto; min-width:220px;">
                    <option value="">All permission types</option>
                    <?php foreach (array_keys($grouped_permissions) as $filterGroupLabel): ?>
                        <option value="<?=htmlspecialchars($filterGroupLabel, ENT_QUOTES)?>">
                            <?=htmlspecialchars($filterGroupLabel, ENT_QUOTES)?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="rc-table-scroll permissions-matrix">
                <table class="rc-table row-hover">
                    <thead>
                        <tr>
                            <th>Permission</th>
                            <?php foreach ($roles as $role): ?>
                                <th>
                                    <?=htmlspecialchars($role['role_name'], ENT_QUOTES)?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($grouped_permissions as $groupLabel => $permsInGroup): ?>
                            <!-- TYPE Heading Row -->
                            <tr data-permission-group="<?=htmlspecialchars($groupLabel, ENT_QUOTES)?>">
                                <td colspan="<?=1 + count($roles)?>">
                                    <span class="rc-badge mid"><?=htmlspecialchars($groupLabel, ENT_QUOTES)?></span>
                                </td>
                            </tr>

                            <?php foreach ($permsInGroup as $perm): ?>
                                <?php
                                    $pid   = (int)$perm['permission_id'];
                                    $pkey  = (string)$perm['permission_key'];
                                    $pdesc = (string)($perm['description'] ?? '');
                                ?>
                                <tr data-permission-group="<?=htmlspecialchars($groupLabel, ENT_QUOTES)?>">
                                    <td>
                                        <!-- permission.name -->
                                        <strong><?=htmlspecialchars($pkey, ENT_QUOTES)?></strong>

                                        <!-- reader viewable text -->
                                        <?php if (trim($pdesc) !== ''): ?>
                                            <br><span class="rc-muted"><?=htmlspecialchars($pdesc, ENT_QUOTES)?></span>
                                        <?php endif; ?>
                                    </td>

                                    <?php foreach ($roles as $role): ?>
                                        <?php
                                            $rid     = (int)$role['role_id'];
                                            $allowed = $effective_allow($rid, $pid);
                                        ?>
                                        <td style="text-align:center;">
                                            <input
                                                type="checkbox"
                                                name="perm[<?=$rid?>][<?=$pid?>]"
                                                value="1"
                                                <?= $allowed ? 'checked' : '' ?>
                                                style="<?=$checkboxInlineStyle?>"
                                            >
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="xform-actions">
                <button type="submit" class="btn green">Save Role Permissions</button>
            </div>
        </form>
        <script>
            document.getElementById('permissionTypeFilter')?.addEventListener('change', function () {
                var selected = this.value;
                document.querySelectorAll('.permissions-matrix tbody tr[data-permission-group]').forEach(function (row) {
                    row.style.display = !selected || row.dataset.permissionGroup === selected ? '' : 'none';
                });
            });
        </script>
    <?php endif; ?>
</div>
