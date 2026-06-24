<?php
define('APP_LOADED', true);

include 'dashmain.php'; // Loads $pdo
include 'getcentreinfo.php';
require_once __DIR__ . '/operations/permissions.php';

// Register permission for User Accounts section
registerPermission(
    "page_user_accounts",
    "Access to User Accounts administration page",
    "page"
);

// Enforce permission
requirePermission("page_user_accounts");

// -----------------------------------------------------------------------------
// ✅ POST HANDLERS (SAVE PERMISSIONS)
// -----------------------------------------------------------------------------
try {
    // Recommended: make DB errors visible during development
    // (If you already set this globally, you can remove it)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $currentAccountId = (int)($_SESSION['account_id'] ?? 0);

    $redirectUsers = static function (array $params = []): void {
        $params = array_merge(['tab' => 'users'], $params);
        header('Location: user_accounts.php?' . http_build_query($params));
        exit;
    };

    $fetchCentreAccount = static function (PDO $pdo, int $accountId, int $centreId): ?array {
        if ($accountId <= 0) {
            return null;
        }

        $stmt = $pdo->prepare("
            SELECT id, username, email, rescue_role, activation_code
            FROM accounts
            WHERE id = :id
              AND centre_id = :centre_id
            LIMIT 1
        ");
        $stmt->execute([
            ':id' => $accountId,
            ':centre_id' => $centreId
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    };

    $assertRoleForCentre = static function (PDO $pdo, int $roleId, int $centreId): void {
        $stmt = $pdo->prepare("
            SELECT role_id
            FROM rescue_roles
            WHERE role_id = :role_id
              AND centre_id = :centre_id
            LIMIT 1
        ");
        $stmt->execute([
            ':role_id' => $roleId,
            ':centre_id' => $centreId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new Exception("Role is not valid for this centre.");
        }
    };

    $assertNotLastOwner = static function (PDO $pdo, array $account, int $centreId): void {
        if ((int)($account['rescue_role'] ?? 0) !== 1) {
            return;
        }

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM accounts
            WHERE centre_id = :centre_id
              AND rescue_role = 1
              AND activation_code = 'activated'
        ");
        $stmt->execute([':centre_id' => $centreId]);

        if ((int)$stmt->fetchColumn() <= 1) {
            throw new Exception("You cannot remove or deactivate the last centre administrator.");
        }
    };

    // ---------------------------
    // USER ACCOUNT CREATE
    // ---------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_account'])) {
        $first_name = trim((string)($_POST['first_name'] ?? ''));
        $last_name = trim((string)($_POST['last_name'] ?? ''));
        $username = trim((string)($_POST['username'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $rescue_role = (int)($_POST['rescue_role'] ?? 0);
        $password = (string)($_POST['password'] ?? '');
        $password_confirm = (string)($_POST['password_confirm'] ?? '');

        $errors = [];
        if ($username === '') $errors[] = 'Username is required.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if ($rescue_role <= 0) $errors[] = 'Role is required.';
        if ($password === '' || $password_confirm === '') {
            $errors[] = 'Password fields are required.';
        } elseif ($password !== $password_confirm) {
            $errors[] = 'Passwords do not match.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        if (!$errors) {
            $assertRoleForCentre($pdo, $rescue_role, (int)$centre_id);

            $stmt = $pdo->prepare("SELECT id FROM accounts WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            if ($stmt->fetchColumn()) $errors[] = 'Username is already taken.';

            $stmt = $pdo->prepare("SELECT id FROM accounts WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetchColumn()) $errors[] = 'Email is already in use.';
        }

        if ($errors) {
            $_SESSION['create_user_errors'] = $errors;
            $_SESSION['create_user_old'] = $_POST;
            $redirectUsers(['error' => 'Could not create account. Check the form below.']);
        }

        $stmt = $pdo->prepare("
            INSERT INTO accounts (
                centre_id, username, email, password, role, rescue_role,
                first_name, last_name, approved, activation_code, onboarded, registered
            ) VALUES (
                :centre_id, :username, :email, :password, 'Member', :rescue_role,
                :first_name, :last_name, 1, 'activated', 1, NOW()
            )
        ");
        $stmt->execute([
            ':centre_id' => $centre_id,
            ':username' => $username,
            ':email' => $email,
            ':password' => password_hash($password, PASSWORD_DEFAULT),
            ':rescue_role' => $rescue_role,
            ':first_name' => ($first_name !== '' ? $first_name : null),
            ':last_name' => ($last_name !== '' ? $last_name : null)
        ]);

        if (function_exists('audit_write')) {
            audit_write($pdo, 'account_created', 'accounts', null, [
                'new_user_id' => (int)$pdo->lastInsertId(),
                'username' => $username,
                'email' => $email,
                'rescue_role' => $rescue_role
            ]);
        }

        $redirectUsers(['success_msg' => 1]);
    }

    // ---------------------------
    // USER ACCOUNT QUICK ACTIONS
    // ---------------------------
    $accountActions = ['delete', 'deactivate', 'activate', 'approve', 'resetpw'];
    foreach ($accountActions as $action) {
        if (!isset($_GET[$action])) {
            continue;
        }

        $targetId = (int)$_GET[$action];
        $targetAccount = $fetchCentreAccount($pdo, $targetId, (int)$centre_id);
        if (!$targetAccount) {
            throw new Exception("User not found for this centre.");
        }

        if (($action === 'delete' || $action === 'deactivate') && $targetId === $currentAccountId) {
            throw new Exception("You cannot {$action} your own account.");
        }

        if ($action === 'delete' || $action === 'deactivate') {
            $assertNotLastOwner($pdo, $targetAccount, (int)$centre_id);
        }

        if ($action === 'delete') {
            $pdo->prepare("DELETE FROM rescue_user_permissions WHERE user_id = :id")->execute([':id' => $targetId]);
            $stmt = $pdo->prepare("DELETE FROM accounts WHERE id = :id AND centre_id = :centre_id LIMIT 1");
            $stmt->execute([':id' => $targetId, ':centre_id' => $centre_id]);
            if (function_exists('audit_write')) audit_write($pdo, 'account_deleted', 'accounts', $targetAccount, null);
            $redirectUsers(['success_msg' => 3]);
        }

        if ($action === 'deactivate') {
            $stmt = $pdo->prepare("
                UPDATE accounts
                SET activation_code = 'deactivated',
                    remember_me_code = NULL
                WHERE id = :id
                  AND centre_id = :centre_id
                LIMIT 1
            ");
            $stmt->execute([':id' => $targetId, ':centre_id' => $centre_id]);
            if (function_exists('audit_write')) audit_write($pdo, 'account_deactivated', 'accounts', $targetAccount, ['id' => $targetId]);
            $redirectUsers(['success_msg' => 2]);
        }

        if ($action === 'activate' || $action === 'approve') {
            $setSql = $action === 'activate' ? "activation_code = 'activated'" : "approved = 1";
            $stmt = $pdo->prepare("
                UPDATE accounts
                SET {$setSql}
                WHERE id = :id
                  AND centre_id = :centre_id
                LIMIT 1
            ");
            $stmt->execute([':id' => $targetId, ':centre_id' => $centre_id]);
            if (function_exists('audit_write')) audit_write($pdo, 'account_' . $action, 'accounts', $targetAccount, ['id' => $targetId]);
            $redirectUsers(['success_msg' => 2]);
        }

        if ($action === 'resetpw') {
            $tempPassword = bin2hex(random_bytes(6));
            $stmt = $pdo->prepare("
                UPDATE accounts
                SET password = :password,
                    reset_code = NULL,
                    remember_me_code = NULL
                WHERE id = :id
                  AND centre_id = :centre_id
                LIMIT 1
            ");
            $stmt->execute([
                ':password' => password_hash($tempPassword, PASSWORD_DEFAULT),
                ':id' => $targetId,
                ':centre_id' => $centre_id
            ]);

            $_SESSION['reset_temp_pw'] = $tempPassword;
            $_SESSION['reset_temp_user'] = (string)$targetAccount['username'];
            if (function_exists('audit_write')) audit_write($pdo, 'account_password_reset', 'accounts', $targetAccount, ['id' => $targetId]);
            $redirectUsers(['success_msg' => 5]);
        }
    }

    // ---------------------------
    // ROLE PERMISSIONS SAVE
    // ---------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role_permissions_form'])) {

        $posted = $_POST['perm'] ?? [];

        // Load roles
        $roleStmt = $pdo->prepare("
            SELECT role_id
            FROM rescue_roles
            WHERE centre_id = :centre_id
        ");
        $roleStmt->execute([':centre_id' => $centre_id]);
        $roles = $roleStmt->fetchAll(PDO::FETCH_ASSOC);

        // Load permissions (non system_)
        $perms = $pdo->query("
            SELECT permission_id
            FROM rescue_permissions
            WHERE permission_key NOT LIKE 'system\_%'
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Ensure unique key exists on (centre_id, role_id, permission_id) ideally
        // We'll do SELECT+UPDATE/INSERT safely:

        $selectStmt = $pdo->prepare("
            SELECT id
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

                $isAllowed = (isset($posted[$rid]) && isset($posted[$rid][$pid])) ? 1 : 0;

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

        header("Location: user_accounts.php?tab=role-perms&success=" . urlencode("Role permissions updated."));
        exit;
    }

    // ---------------------------
    // USER PERMISSIONS SAVE
    // ---------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_permissions_form'])) {

        $selected_user_id = (int)($_POST['selected_user_id'] ?? 0);
        $posted = $_POST['perm'] ?? [];

        if ($selected_user_id <= 0) {
            throw new Exception("No user selected.");
        }

        // Confirm user belongs to this centre
        $stmt = $pdo->prepare("
            SELECT id
            FROM accounts
            WHERE id = :id AND centre_id = :centre_id
            LIMIT 1
        ");
        $stmt->execute([
            ':id' => $selected_user_id,
            ':centre_id' => $centre_id
        ]);
        if (!$stmt->fetchColumn()) {
            throw new Exception("User not found for this centre.");
        }

        // We only process known permissions (non system_)
        $permIds = $pdo->query("
            SELECT permission_id
            FROM rescue_permissions
            WHERE permission_key NOT LIKE 'system\_%'
        ")->fetchAll(PDO::FETCH_COLUMN);

        $permIds = array_map('intval', $permIds);

        // Prepare statements:
        // - delete override when inherit
        // - upsert allow for 1/0
        $deleteStmt = $pdo->prepare("
            DELETE FROM rescue_user_permissions
            WHERE user_id = :uid AND permission_id = :pid
        ");

        $selectStmt = $pdo->prepare("
            SELECT id
            FROM rescue_user_permissions
            WHERE user_id = :uid AND permission_id = :pid
            LIMIT 1
        ");

        $updateStmt = $pdo->prepare("
            UPDATE rescue_user_permissions
            SET allow = :allow
            WHERE user_id = :uid AND permission_id = :pid
        ");

        $insertStmt = $pdo->prepare("
            INSERT INTO rescue_user_permissions (user_id, permission_id, allow)
            VALUES (:uid, :pid, :allow)
        ");

        foreach ($permIds as $pid) {
            $val = $posted[$pid] ?? 'inherit';

            if ($val === 'inherit' || $val === '' || $val === null) {
                // remove override
                $deleteStmt->execute([':uid' => $selected_user_id, ':pid' => $pid]);
                continue;
            }

            // normalize allow to 1/0
            $allow = ((string)$val === '1') ? 1 : 0;

            $selectStmt->execute([':uid' => $selected_user_id, ':pid' => $pid]);
            $existingId = $selectStmt->fetchColumn();

            if ($existingId) {
                $updateStmt->execute([':allow' => $allow, ':uid' => $selected_user_id, ':pid' => $pid]);
            } else {
                $insertStmt->execute([':uid' => $selected_user_id, ':pid' => $pid, ':allow' => $allow]);
            }
        }

        header("Location: user_accounts.php?tab=user-perms&success=" . urlencode("User permissions updated.") . "&selected_user_id=" . $selected_user_id);
        exit;
    }

    // ---------------------------
    // USER ACCOUNT EDIT SAVE
    // ---------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user_account_form'])) {

        $edit_user_id = (int)($_POST['edit_user_id'] ?? 0);

        if ($edit_user_id <= 0) {
            throw new Exception("No user selected.");
        }

        // Confirm user belongs to this centre
        $stmt = $pdo->prepare("
            SELECT id
            FROM accounts
            WHERE id = :id AND centre_id = :centre_id
            LIMIT 1
        ");
        $stmt->execute([
            ':id' => $edit_user_id,
            ':centre_id' => $centre_id
        ]);
        if (!$stmt->fetchColumn()) {
            throw new Exception("User not found for this centre.");
        }

        $username = trim((string)($_POST['username'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $first_name = trim((string)($_POST['first_name'] ?? ''));
        $last_name = trim((string)($_POST['last_name'] ?? ''));
        $rescue_role = (int)($_POST['rescue_role'] ?? 0);
        $activation_code = (string)($_POST['activation_code'] ?? 'activated');
        $approved = isset($_POST['approved']) ? 1 : 0;
        $password = (string)($_POST['password'] ?? '');
        $password_confirm = (string)($_POST['password_confirm'] ?? '');

        if ($username === '' || $email === '' || $rescue_role <= 0) {
            throw new Exception("Username, email and role are required.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email address is not valid.");
        }

        if (!in_array($activation_code, ['activated', 'deactivated'], true)) {
            $activation_code = 'activated';
        }

        $assertRoleForCentre($pdo, $rescue_role, (int)$centre_id);

        if ($password !== '' || $password_confirm !== '') {
            if ($password !== $password_confirm) {
                throw new Exception("Passwords do not match.");
            }

            if (strlen($password) < 8) {
                throw new Exception("Password must be at least 8 characters.");
            }
        }

        $stmt = $pdo->prepare("SELECT id FROM accounts WHERE username = :username AND id <> :id LIMIT 1");
        $stmt->execute([':username' => $username, ':id' => $edit_user_id]);
        if ($stmt->fetchColumn()) {
            throw new Exception("Username already exists.");
        }

        $stmt = $pdo->prepare("SELECT id FROM accounts WHERE email = :email AND id <> :id LIMIT 1");
        $stmt->execute([':email' => $email, ':id' => $edit_user_id]);
        if ($stmt->fetchColumn()) {
            throw new Exception("Email address already exists.");
        }

        $params = [
            ':username' => $username,
            ':email' => $email,
            ':first_name' => $first_name,
            ':last_name' => $last_name,
            ':rescue_role' => $rescue_role,
            ':activation_code' => $activation_code,
            ':approved' => $approved,
            ':id' => $edit_user_id,
            ':centre_id' => $centre_id
        ];

        $passwordSql = '';
        if ($password !== '') {
            $passwordSql = ', password = :password, remember_me_code = NULL, reset_code = NULL';
            $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $stmt = $pdo->prepare("
            UPDATE accounts
            SET username = :username,
                email = :email,
                first_name = :first_name,
                last_name = :last_name,
                rescue_role = :rescue_role,
                activation_code = :activation_code,
                approved = :approved
                {$passwordSql}
            WHERE id = :id
              AND centre_id = :centre_id
            LIMIT 1
        ");
        $stmt->execute($params);

        if (function_exists('audit_write')) {
            audit_write($pdo, 'account_updated', 'accounts', ['id' => $edit_user_id], [
                'username' => $username,
                'email' => $email,
                'rescue_role' => $rescue_role,
                'activation_code' => $activation_code,
                'approved' => $approved,
                'password_changed' => ($password !== '')
            ]);
        }

        header("Location: user_accounts.php?tab=users&success=" . urlencode("User account updated."));
        exit;
    }

} catch (Throwable $e) {
    // Send user back to whichever tab they were on (best effort)
    $fallbackTab = 'users';
    if (isset($_POST['role_permissions_form'])) $fallbackTab = 'role-perms';
    if (isset($_POST['user_permissions_form'])) $fallbackTab = 'user-perms';
    if (isset($_POST['create_account'])) $fallbackTab = 'users';
    if (isset($_POST['edit_user_account_form'])) $fallbackTab = 'edit&id=' . (int)($_POST['edit_user_id'] ?? 0);

    header("Location: user_accounts.php?tab={$fallbackTab}&error=" . urlencode($e->getMessage()));
    exit;
}

// -----------------------------------------------------------------------------
// ✅ TAB ROUTING
// -----------------------------------------------------------------------------
$tab = $_GET['tab'] ?? 'users';

$tabRoutes = [
    'users'      => 'views/users.php',
    'edit'       => 'views/user_edit.php',
    'user-perms' => 'views/permissions_users.php',
    'role-perms' => 'views/permissions_roles.php'
];

if (!array_key_exists($tab, $tabRoutes)) {
    $tab = 'users';
}

// ✅ SUCCESS / ERROR ROUTING
$success = $_GET['success'] ?? null;
$error   = $_GET['error'] ?? null;
?>

<?= template_admin_header(
    'Staff - User Accounts - ' . $rescue_name . ' - Rescue Centre - Rescue Management System',
    'staff',
    'accounts'
) ?>

<div class="content-title">
    <div class="title">
        <div class="icon">
            <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M144 0a80 80 0 1 1 0 160A80 80 0 1 1 144 0zM512 0a80 80 0 1 1 0 160A80 80 0 1 1 512 0zM0 298.7C0 239.8 47.8 192 106.7 192h42.7c15.9 0 31 3.5 44.6 9.7c-1.3 7.2-1.9 14.7-1.9 22.3c0 38.2 16.8 72.5 43.3 96c-.2 0-.4 0-.7 0H21.3C9.6 320 0 310.4 0 298.7zM405.3 320c-.2 0-.4 0-.7 0c26.6-23.5 43.3-57.8 43.3-96c0-7.6-.7-15-1.9-22.3c13.6-6.3 28.7-9.7 44.6-9.7h42.7C592.2 192 640 239.8 640 298.7c0 11.8-9.6 21.3-21.3 21.3H405.3zM224 224a96 96 0 1 1 192 0 96 96 0 1 1 -192 0zM128 485.3C128 411.7 187.7 352 261.3 352H378.7C452.3 352 512 411.7 512 485.3c0 14.7-11.9 26.7-26.7 26.7H154.7c-14.7 0-26.7-11.9-26.7-26.7z"/></svg>
        </div>
        <div class="txt">
            <h2>Accounts & Permissions</h2>
            <p>View, edit, create accounts and manage centre permissions</p>
        </div>
    </div>
</div>

<div class="rc-stack">

    <div class="rc-tabs rc-tabs-pill">
        <a class="rc-tab <?= ($tab === 'users' || $tab === 'edit') ? 'is-active' : '' ?>" href="?tab=users">User Accounts</a>
        <a class="rc-tab <?= $tab === 'user-perms' ? 'is-active' : '' ?>" href="?tab=user-perms">User Permissions</a>
        <a class="rc-tab <?= $tab === 'role-perms' ? 'is-active' : '' ?>" href="?tab=role-perms">Role Permissions</a>
    </div>

    <?php if ($success): ?>
        <div class="rc-alert green">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="rc-alert red">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="rc-tab-panel is-active">
        <?php include $tabRoutes[$tab]; ?>
    </div>

</div>

<?= template_admin_footer() ?>
