<?php
// Make sure session is available (for flash messages like reset password, create user errors)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('APP_LOADED')) exit;
echo '<div class="content-title">
    <div class="title">
        <div class="txt">
            <h2>User Accounts</h2>
            <p>Create and manage user accounts. Reset passwords.</p>
        </div>
    </div>
</div>';

// -----------------------------------------------------------------------------
// 1. HANDLE ACCOUNT ACTIONS (DELETE / ACTIVATE / DEACTIVATE / APPROVE / RESET PW)
// -----------------------------------------------------------------------------
// NOTE: These are still here because they were already working in your flow.
// If you later want them moved into controllers as well, we can do that as a second step.

// Delete account
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare('DELETE FROM accounts WHERE id = ?');
    $stmt->execute([ $_GET['delete'] ]);
    header('Location: user_accounts.php?success_msg=3');
    exit;
}

// Deactivate (also remove remember me code)
if (isset($_GET['deactivate'])) {
    $stmt = $pdo->prepare('UPDATE accounts SET activation_code = "deactivated", remember_me_code = "" WHERE id = ?');
    $stmt->execute([ $_GET['deactivate'] ]);
    header('Location: user_accounts.php?success_msg=2');
    exit;
}

// Activate
if (isset($_GET['activate'])) {
    $stmt = $pdo->prepare('UPDATE accounts SET activation_code = "activated" WHERE id = ?');
    $stmt->execute([ $_GET['activate'] ]);
    header('Location: user_accounts.php?success_msg=2');
    exit;
}

// Approve
if (isset($_GET['approve'])) {
    $stmt = $pdo->prepare('UPDATE accounts SET approved = 1 WHERE id = ?');
    $stmt->execute([ $_GET['approve'] ]);
    header('Location: user_accounts.php?success_msg=2');
    exit;
}

// Reset password (generate temporary password, store in session, redirect)
if (isset($_GET['resetpw'])) {
    $id = (int) $_GET['resetpw'];

    // Generate a simple temporary password (8 hex chars)
    $temp_password = bin2hex(random_bytes(4));
    $hash          = password_hash($temp_password, PASSWORD_DEFAULT);

    // Get username for message
    $stmt = $pdo->prepare('SELECT username FROM accounts WHERE id = ?');
    $stmt->execute([$id]);
    $uname = $stmt->fetchColumn();

    if ($uname) {
        $stmt = $pdo->prepare('UPDATE accounts SET password = :pass, reset_code = NULL WHERE id = :id');
        $stmt->execute([
            ':pass' => $hash,
            ':id'   => $id
        ]);

        $_SESSION['reset_temp_pw']   = $temp_password;
        $_SESSION['reset_temp_user'] = $uname;
    }

    header('Location: user_accounts.php?success_msg=5');
    exit;
}

// -----------------------------------------------------------------------------
// 2. LOAD RESCUE ROLES (for dropdown & filtering)
// -----------------------------------------------------------------------------
$centre_roles = $pdo->query("
    SELECT role_id, role_name 
    FROM rescue_roles 
    ORDER BY role_name
")->fetchAll(PDO::FETCH_ASSOC);

// -----------------------------------------------------------------------------
// 3. RETRIEVE FILTERS, PAGINATION & ACCOUNT LIST (SCOPED BY centre_id)
// -----------------------------------------------------------------------------

// Retrieve the GET request parameters (if specified)
$page      = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search    = isset($_GET['search_query']) ? $_GET['search_query'] : '';
$status    = isset($_GET['status']) ? $_GET['status'] : '';
$role      = isset($_GET['role']) ? $_GET['role'] : ''; // now rescue_role ID
$last_seen = isset($_GET['last_seen']) ? $_GET['last_seen'] : '';

// Order by column
$order = (isset($_GET['order']) && $_GET['order'] == 'DESC') ? 'DESC' : 'ASC';

// Whitelist "logical" columns (we’ll map them to actual SQL columns)
$order_by_whitelist = ['id','username','email','activation_code','role','registered','last_seen'];
$order_by = (isset($_GET['order_by']) && in_array($_GET['order_by'], $order_by_whitelist)) ? $_GET['order_by'] : 'id';

// Map logical order_by to actual SQL
switch ($order_by) {
    case 'username':
        $order_by_sql = 'a.username';
        break;
    case 'email':
        $order_by_sql = 'a.email';
        break;
    case 'activation_code':
        $order_by_sql = 'a.activation_code';
        break;
    case 'role':
        $order_by_sql = 'r.role_name';
        break;
    case 'last_seen':
        $order_by_sql = 'a.last_seen';
        break;
    case 'registered':
        $order_by_sql = 'a.registered';
        break;
    case 'id':
    default:
        $order_by_sql = 'a.id';
        break;
}

// Pagination
$results_per_page = 20;
$accounts         = [];
$param1           = ($page - 1) * $results_per_page;
$param2           = $results_per_page;
$param3           = '%' . $search . '%';

// Build WHERE clauses, always enforce centre_id
$whereClauses = [];
$whereParams  = [];

// Always scope to current centre
$whereClauses[]             = 'centre_id = :centre_id';
$whereParams[':centre_id']  = $centre_id;

// Search filter
if ($search) {
    $whereClauses[]         = '(username LIKE :search OR email LIKE :search)';
    $whereParams[':search'] = $param3;
}

// Role filter (using rescue_role ID)
if ($role !== '') {
    $whereClauses[]              = 'rescue_role = :rescue_role';
    $whereParams[':rescue_role'] = (int)$role;
}

// Last seen filter
$now = date('Y-m-d H:i:s');
if ($last_seen == 'today') {
    $whereClauses[] = 'last_seen > DATE_SUB("'.$now.'", INTERVAL 1 DAY)';
} elseif ($last_seen == 'yesterday') {
    $whereClauses[] = 'last_seen > DATE_SUB("'.$now.'", INTERVAL 2 DAY) AND last_seen < DATE_SUB("'.$now.'", INTERVAL 1 DAY)';
} elseif ($last_seen == 'week') {
    $whereClauses[] = 'last_seen > DATE_SUB("'.$now.'", INTERVAL 1 WEEK)';
} elseif ($last_seen == 'month') {
    $whereClauses[] = 'last_seen > DATE_SUB("'.$now.'", INTERVAL 1 MONTH)';
} elseif ($last_seen == 'year') {
    $whereClauses[] = 'last_seen > DATE_SUB("'.$now.'", INTERVAL 1 YEAR)';
} elseif ($last_seen == 'inactive') {
    $whereClauses[] = 'last_seen < DATE_SUB("'.$now.'", INTERVAL 1 MONTH)';
}

// Status filter
if ($status == 'Activated') {
    $whereClauses[] = 'activation_code = "activated"';
} elseif ($status == 'Deactivated') {
    $whereClauses[] = 'activation_code = "deactivated"';
} elseif ($status == 'Pending Activation') {
    $whereClauses[] = 'activation_code != "activated" AND activation_code != "deactivated"';
} elseif ($status == 'Approved') {
    $whereClauses[] = 'approved = 1';
} elseif ($status == 'Pending Approval') {
    $whereClauses[] = 'approved = 0';
}

// WHERE SQL
$whereSQL = '';
if (!empty($whereClauses)) {
    $whereSQL = ' WHERE ' . implode(' AND ', $whereClauses);
}

// Retrieve total number of accounts
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM accounts ' . $whereSQL);
foreach ($whereParams as $key => $val) {
    if ($key === ':centre_id' || $key === ':rescue_role') {
        $stmt->bindValue($key, (int)$val, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }
}
$stmt->execute();
$total_accounts = (int)$stmt->fetchColumn();

// Retrieve accounts with join to rescue_roles
$stmt = $pdo->prepare('
    SELECT a.*, r.role_name 
    FROM accounts a
    LEFT JOIN rescue_roles r ON r.role_id = a.rescue_role
    ' . $whereSQL . '
    ORDER BY ' . $order_by_sql . ' ' . $order . '
    LIMIT :start_results, :num_results
');
foreach ($whereParams as $key => $val) {
    if ($key === ':centre_id' || $key === ':rescue_role') {
        $stmt->bindValue($key, (int)$val, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }
}
$stmt->bindValue('start_results', (int)$param1, PDO::PARAM_INT);
$stmt->bindValue('num_results', (int)$param2, PDO::PARAM_INT);
$stmt->execute();
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// -----------------------------------------------------------------------------
// 4. SUCCESS / INFO MESSAGES
// -----------------------------------------------------------------------------
if (isset($_GET['success_msg'])) {
    if ($_GET['success_msg'] == 1) {
        $success_msg = 'Account created successfully!';
    }
    if ($_GET['success_msg'] == 2) {
        $success_msg = 'Account updated successfully!';
    }
    if ($_GET['success_msg'] == 3) {
        $success_msg = 'Account deleted successfully!';
    }
    if ($_GET['success_msg'] == 4) {
        $success_msg = 'Accounts imported successfully! ' . $_GET['imported'] . ' accounts were imported.';
    }
    if ($_GET['success_msg'] == 5 && isset($_SESSION['reset_temp_pw'], $_SESSION['reset_temp_user'])) {
        $success_msg = 'Password for <strong>' . htmlspecialchars($_SESSION['reset_temp_user'], ENT_QUOTES) . '</strong> has been reset. Temporary password: <strong>' . htmlspecialchars($_SESSION['reset_temp_pw'], ENT_QUOTES) . '</strong>';
        unset($_SESSION['reset_temp_pw'], $_SESSION['reset_temp_user']);
    }
}

// -----------------------------------------------------------------------------
// 5. CREATE USER ERRORS FROM CONTROLLER (SESSION)
// -----------------------------------------------------------------------------
$create_errors = $_SESSION['create_user_errors'] ?? [];
$create_old    = $_SESSION['create_user_old']    ?? [];
unset($_SESSION['create_user_errors'], $_SESSION['create_user_old']);

// Create base URL (now using user_accounts.php)
$url = 'user_accounts.php?search_query=' . urlencode($search) . '&status=' . urlencode($status) . '&role=' . urlencode($role) . '&last_seen=' . urlencode($last_seen);

// For filter pill: get human-readable role name
$filter_role_name = '';
if ($role !== '') {
    foreach ($centre_roles as $cr) {
        if ((int)$role === (int)$cr['role_id']) {
            $filter_role_name = $cr['role_name'];
            break;
        }
    }
}
?>

<?php if (isset($success_msg)): ?>
<div class="rc-alert green">
    <?=$success_msg?>
</div>
<?php endif; ?>

<?php if (!empty($create_errors)): ?>
<div class="rc-alert red">
    <p><strong>Could not create account:</strong></p>
    <ul>
        <?php foreach ($create_errors as $err): ?>
            <li><?=htmlspecialchars($err, ENT_QUOTES)?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="content-header responsive-flex-column pad-top-5">
    <!-- Toggle create-user form -->
    <button type="button" class="btn" id="toggle-create-user">
        <svg class="icon-left" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32V224H48c-17.7 0-32 14.3-32 32s14.3 32 32 32H192V432c0 17.7 14.3 32 32 32s32-14.3 32-32V288H400c17.7 0 32-14.3 32-32s-14.3-32-32-32H256V80z"/></svg>
        Create Account
    </button>
    <form method="get">
        <div class="filters">
            <a href="#">
                <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M0 416c0 17.7 14.3 32 32 32l54.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48L480 448c17.7 0 32-14.3 32-32s-14.3-32-32-32l-246.7 0c-12.3-28.3-40.5-48-73.3-48s-61 19.7-73.3 48L32 384c-17.7 0-32 14.3-32 32zm128 0a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zM320 256a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zm32-80c-32.8 0-61 19.7-73.3 48L32 224c-17.7 64 0 32 0 32l246.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48l54.7 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-54.7 0c-12.3-28.3-40.5-48-73.3-48zM192 128a32 32 0 1 1 0-64 32 32 0 1 1 0 64zm73.3-64C253 35.7 224.8 16 192 16s-61 19.7-73.3 48L32 64C14.3 64 0 78.3 0 96s14.3 32 32 32l86.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48L480 128c17.7 0 32-14.3 32-32s-14.3-32-32-32L265.3 64z"/></svg>
                Filters
            </a>
            <div class="list">
                <label for="role">Role</label>
                <select name="role" id="role">
                    <option value=""<?=$role===''?' selected':''?>>All</option>
                    <?php foreach ($centre_roles as $cr): ?>
                        <option value="<?=$cr['role_id']?>" <?=$role!=='' && (int)$role===(int)$cr['role_id'] ? 'selected' : ''?>>
                            <?=$cr['role_name']?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="last_seen">Last Seen</label>
                <select name="last_seen" id="last_seen">
                    <option value=""<?=$last_seen==''?' selected':''?>>All</option>
                    <option value="today"<?=$last_seen=='today'?' selected':''?>>Today</option>
                    <option value="yesterday"<?=$last_seen=='yesterday'?' selected':''?>>Yesterday</option>
                    <option value="week"<?=$last_seen=='week'?' selected':''?>>This Week</option>
                    <option value="month"<?=$last_seen=='month'?' selected':''?>>This Month</option>
                    <option value="year"<?=$last_seen=='year'?' selected':''?>>This Year</option>
                    <option value="inactive"<?=$last_seen=='inactive'?' selected':''?>>Inactive</option>
                </select>
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value=""<?=$status==''?' selected':''?>>All</option>
                    <option value="Activated"<?=$status=='Activated'?' selected':''?>>Activated</option>
                    <option value="Deactivated"<?=$status=='Deactivated'?' selected':''?>>Deactivated</option>
                    <option value="Pending Activation"<?=$status=='Pending Activation'?' selected':''?>>Pending Activation</option>
                    <option value="Approved"<?=$status=='Approved'?' selected':''?>>Approved</option>
                    <option value="Pending Approval"<?=$status=='Pending Approval'?' selected':''?>>Pending Approval</option>
                </select>
                <button type="submit">Apply</button>
            </div>
        </div>
        <div class="search">
            <label for="search_query">
                <input id="search_query" type="text" name="search_query" placeholder="Search account..." value="<?=htmlspecialchars($search, ENT_QUOTES)?>" class="responsive-width-100">
                <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/></svg>
            </label>
        </div>
    </form>
</div>

<div class="filter-list">
    <?php if ($role !== ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'role')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Role : <?=htmlspecialchars($filter_role_name ?: $role, ENT_QUOTES)?>
    </div>
    <?php endif; ?>
    <?php if ($last_seen != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'last_seen')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Last Seen : <?=htmlspecialchars($last_seen, ENT_QUOTES)?>
    </div>
    <?php endif; ?>
    <?php if ($status != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'status')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Status : <?=htmlspecialchars($status, ENT_QUOTES)?>
    </div>
    <?php endif; ?>
    <?php if ($search != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'search_query')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Search : <?=htmlspecialchars($search, ENT_QUOTES)?>
    </div>
    <?php endif; ?>
</div>

<div class="content-block no-pad">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td><a href="<?=$url . '&page='.$page.'&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=id'?>">#<?=$order_by=='id' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td colspan="2"><a href="<?=$url . '&page='.$page.'&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=username'?>">Username<?=$order_by=='username' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&page='.$page.'&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=email'?>">Email<?=$order_by=='email' ? $table_icons[strtolower($order)] : ''?></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&page='.$page.'&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=activation_code'?>">Status<?=$order_by=='activation_code' ? $table_icons[strtolower($order)] : ''?></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&page='.$page.'&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=role'?>">Role<?=$order_by=='role' ? $table_icons[strtolower($order)] : ''?></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&page='.$page.'&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=last_seen'?>">Last Seen<?=$order_by=='last_seen' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&page='.$page.'&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=registered'?>">Registered Date<?=$order_by=='registered' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td class="align-center">Action</td>
                </tr>
            </thead>
            <tbody>
                <?php if (!$accounts): ?>
                <tr>
                    <td colspan="20" class="no-results">There are no accounts for this centre.</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($accounts as $account): ?>
                <tr>
                    <td class="alt"><?=$account['id']?></td>
                    <td class="img">
                        <div class="profile-img">
                            <span style="background-color:<?=color_from_string($account['username'])?>"><?=strtoupper(substr($account['username'], 0, 1))?></span>
                            <?php if ($account['last_seen'] > date('Y-m-d H:i:s', strtotime('-15 minutes'))): ?>
                            <i class="online" title="Online"></i>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td><?=htmlspecialchars($account['username'], ENT_QUOTES)?></td>
                    <td class="responsive-hidden"><?=htmlspecialchars($account['email'], ENT_QUOTES)?></td>
                    <td class="responsive-hidden">
                        <?php if (!$account['approved']): ?>
                        <span class="rc-badge warn">Pending Approval</span>
                        <?php elseif ($account['activation_code'] == 'activated'): ?>
                        <span class="rc-badge ok">Activated</span>
                        <?php elseif ($account['activation_code'] == 'deactivated'): ?>
                        <span class="rc-badge bad">Deactivated</span>
                        <?php else: ?>
                        <span class="rc-badge na" title="<?=$account['activation_code']?>">Pending Activation</span>
                        <?php endif; ?>
                    </td>
                    <td class="responsive-hidden">
                        <?php if ($account['role_name'] == 'Admin'): ?>
                            <span class="rc-badge bad"><?=$account['role_name']?></span>
                        <?php elseif ($account['role_name'] == 'Vet' || $account['role_name'] == 'Nurse'): ?>
                            <span class="rc-badge mid"><?=$account['role_name']?></span>
                        <?php elseif ($account['role_name']): ?>
                            <span class="rc-badge na"><?=$account['role_name']?></span>
                        <?php else: ?>
                            <span class="rc-badge na">No Role</span>
                        <?php endif; ?>
                    </td>
                    <td class="responsive-hidden alt" title="<?=$account['last_seen']?>"><?=time_elapsed_string($account['last_seen'])?></td>
                    <td class="responsive-hidden alt"><?=date('Y-m-d H:ia', strtotime($account['registered']))?></td>
                    <td class="actions">
                        <div class="table-dropdown">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                            <div class="table-dropdown-items">
                                <a href="user_accounts.php?tab=edit&id=<?=$account['id']?>">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7zM96 64C43 64 0 107 0 160V416c0 53 43 96 96 96H352c53 0 96-43 96-96V320c0-17.7-14.3-32-32-32s-32 14.3-32 32v96c0 17.7-14.3 32-32 32H96c-17.7 0-32-14.3-32-32V160c0-17.7 14.3-32 32-32h96c17.7 0 32-14.3 32-32s-14.3-32-32-32H96z"/></svg>
                                    </span>
                                    Edit
                                </a>
                                <a class="orange" href="user_accounts.php?resetpw=<?=$account['id']?>" onclick="return confirm('Are you sure you want to reset this user&apos;s password? A new temporary password will be generated.')">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M440.7 12.4c-8.7-4.1-19-2.7-26.2 3.3L374.6 48.5C338.9 17.5 292 0 244 0 146.5 0 64.7 63.1 39.1 156.5c-3.4 12.5 4 25.4 16.5 28.9s25.4-4 28.9-16.5C98.9 96.7 165.8 48 244 48c34.2 0 67.1 11.7 94 32.9L327 92.7c-9 9.3-8.7 24.1 .6 33.1s24.1 8.7 33.1-.6L459.9 24.7c6.2-6.4 8.1-15.6 4.8-23.8s-10.1-13.8-18-14.9zM48 256c0-13.3-10.7-24-24-24S0 242.7 0 256c0 70.7 57.3 128 128 128h37.1l-25.5 25.5c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0L264 352 173.5 261.5c-9.4-9.4-24.6-9.4-33.9 0s-9.4 24.6 0 33.9L165.1 320H128C83.8 320 48 284.2 48 240z"/></svg>
                                    </span>
                                    Reset Password
                                </a>
                                <?php if (!$account['approved']): ?>
                                <a class="green" href="user_accounts.php?approve=<?=$account['id']?>" onclick="return confirm('Are you sure you want to approve this account?')">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M96 128a128 128 0 1 1 256 0A128 128 0 1 1 96 128zM0 482.3C0 383.8 79.8 304 178.3 304h91.4C368.2 304 448 383.8 448 482.3c0 16.4-13.3 29.7-29.7 29.7H29.7C13.3 512 0 498.7 0 482.3zM625 177L497 305c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L591 143c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>
                                    </span>    
                                    Approve
                                </a>
                                <?php endif; ?>
                                <?php if ($account['activation_code'] != 'activated'): ?>
                                <a class="green" href="user_accounts.php?activate=<?=$account['id']?>" onclick="return confirm('Are you sure you want to activate this account?')">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M96 128a128 128 0 1 1 256 0A128 128 0 1 1 96 128zM0 482.3C0 383.8 79.8 304 178.3 304h91.4C368.2 304 448 383.8 448 482.3c0 16.4-13.3 29.7-29.7 29.7H29.7C13.3 512 0 498.7 0 482.3zM625 177L497 305c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L591 143c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>
                                    </span>    
                                    Activate
                                </a>
                                <?php endif; ?>
                                <?php if ($account['activation_code'] != 'deactivated'): ?>
                                <a class="red" href="user_accounts.php?deactivate=<?=$account['id']?>" onclick="return confirm('Are you sure you want to deactivate this account? They will no longer be able to log in.')">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M38.8 5.1C28.4-3.1 13.3-1.2 5.1 9.2S-1.2 34.7 9.2 42.9l592 464c10.4 8.2 25.5 6.3 33.7-4.1s6.3-25.5-4.1-33.7L353.3 251.6C407.9 237 448 187.2 448 128C448 57.3 390.7 0 320 0C250.2 0 193.5 55.8 192 125.2L38.8 5.1zM264.3 304.3C170.5 309.4 96 387.2 96 482.3c0 16.4 13.3 29.7 29.7 29.7H514.3c3.9 0 7.6-.7 11-2.1l-261-205.6z"/></svg>
                                    </span>    
                                    Deactivate
                                </a>
                                <?php endif; ?>
                                <a class="red" href="user_accounts.php?delete=<?=$account['id']?>" onclick="return confirm('Are you sure you want to delete this account?')">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"/></svg>
                                    </span>    
                                    Delete
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="pagination">
    <?php if ($page > 1): ?>
    <a href="<?=$url?>&page=<?=$page-1?>&order=<?=$order?>&order_by=<?=$order_by?>">Prev</a>
    <?php endif; ?>
    <span>Page <?=$page?> of <?=($total_accounts == 0 ? 1 : ceil($total_accounts / $results_per_page))?></span>
    <?php if ($page * $results_per_page < $total_accounts): ?>
    <a href="<?=$url?>&page=<?=$page+1?>&order=<?=$order?>&order_by=<?=$order_by?>">Next</a>
    <?php endif; ?>
</div>

<!-- Inline CREATE USER form (hidden by default) -->
<div id="create-user-form" class="content-block" style="margin-top:20px; display:none;">
    <h3>Create New User</h3>
    <form method="post" action="controllers/create_user.php" class="xform">
        <input type="hidden" name="create_account" value="1">
        <div class="xform-grid">
            <div class="xform-field">
                <label class="xform-label" for="first_name">First Name</label>
                <input type="text" class="xform-input" name="first_name" id="first_name" value="<?=htmlspecialchars($create_old['first_name'] ?? '', ENT_QUOTES)?>">
            </div>
            <div class="xform-field">
                <label class="xform-label" for="last_name">Last Name</label>
                <input type="text" class="xform-input" name="last_name" id="last_name" value="<?=htmlspecialchars($create_old['last_name'] ?? '', ENT_QUOTES)?>">
            </div>
            <div class="xform-field">
                <label class="xform-label" for="username_new">Username*</label>
                <input type="text" class="xform-input" name="username" id="username_new" required value="<?=htmlspecialchars($create_old['username'] ?? '', ENT_QUOTES)?>">
            </div>
            <div class="xform-field">
                <label class="xform-label" for="email_new">Email*</label>
                <input type="email" class="xform-input" name="email" id="email_new" required value="<?=htmlspecialchars($create_old['email'] ?? '', ENT_QUOTES)?>">
            </div>
            <div class="xform-field">
                <label class="xform-label" for="role_new">Role*</label>
                <select class="xform-input" name="rescue_role" id="role_new" required>
                    <option value="">Select role...</option>
                    <?php foreach ($centre_roles as $cr): ?>
                        <option value="<?=$cr['role_id']?>" <?=(isset($create_old['rescue_role']) && (int)$create_old['rescue_role'] === (int)$cr['role_id']) ? 'selected' : ''?>>
                            <?=$cr['role_name']?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="xform-field">
                <label class="xform-label" for="password_new">Password*</label>
                <input type="password" class="xform-input" name="password" id="password_new" required>
            </div>
            <div class="xform-field">
                <label class="xform-label" for="password_confirm_new">Confirm Password*</label>
                <input type="password" class="xform-input" name="password_confirm" id="password_confirm_new" required>
            </div>
        </div>
        <div class="xform-actions" style="margin-top:15px;">
            <button type="submit" class="btn green">Create User</button>
        </div>
    </form>
</div>

<script>
// Toggle create user form
document.getElementById('toggle-create-user').addEventListener('click', function () {
    const form = document.getElementById('create-user-form');
    form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'block' : 'none';
});
</script>
