<?php
// ------------------------------------------------------------
// getuserinfo.php  
// Loads logged-in user details from "accounts"
// Joins rescue_roles for human-readable role_name
// Stores FK IDs in globals, and text role only in record_name
// Also exposes user_id and centre_id in $_SESSION for other systems
// ------------------------------------------------------------

// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------------------------------
// 1. Detect logged-in user
// ------------------------------------------------------------

if (!isset($_SESSION['account_id']) || empty($_SESSION['account_id'])) {

    $GLOBALS['user_id']     = null;
    $GLOBALS['role']        = null;
    $GLOBALS['rescue_role'] = null;
    $GLOBALS['centre_id']   = null;
    $GLOBALS['first_name']  = null;
    $GLOBALS['last_name']   = null;
    $GLOBALS['record_name'] = null;

    // Also clear the convenience session copies
    unset($_SESSION['user_id'], $_SESSION['centre_id'], $_SESSION['country_code'], $_SESSION['county']);

    return;
}

$user_id = $_SESSION['account_id'];


// ------------------------------------------------------------
// 2. Query DB using $pdo + JOIN rescue_roles
// ------------------------------------------------------------

try {
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.role,
            a.rescue_role,      -- FK ID (keep this)
            a.centre_id,        -- 🔹 centre FK
            a.first_name,
            a.last_name,
            rr.role_name AS rescue_role_name
        FROM accounts a
        LEFT JOIN rescue_roles rr 
            ON a.rescue_role = rr.role_id   -- correct FK mapping
        WHERE a.id = :id
        LIMIT 1
    ");

    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {

        $GLOBALS['user_id']     = null;
        $GLOBALS['role']        = null;
        $GLOBALS['rescue_role'] = null;
        $GLOBALS['centre_id']   = null;
        $GLOBALS['first_name']  = null;
        $GLOBALS['last_name']   = null;
        $GLOBALS['record_name'] = null;

        unset($_SESSION['user_id'], $_SESSION['centre_id'], $_SESSION['country_code'], $_SESSION['county']);

        return;
    }

    // --------------------------------------------------------
    // 3. Populate global variables
    // --------------------------------------------------------

    // IDs (as requested)
    $GLOBALS['user_id']     = $user['id'];
    $GLOBALS['role']        = $user['role'];
    $GLOBALS['rescue_role'] = $user['rescue_role'];  // <-- FK ID
    $GLOBALS['centre_id']   = $user['centre_id'];    // <-- NEW

    // Mirror to session for convenience elsewhere (audit, etc.)
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['centre_id'] = $user['centre_id'];

    if (!empty($user['centre_id'])) {
        $centreColumns = [];
        try {
            foreach ($pdo->query('SHOW COLUMNS FROM rescue_centres')->fetchAll(PDO::FETCH_ASSOC) as $column) {
                $centreColumns[(string)$column['Field']] = true;
            }
        } catch (Throwable $e) {
            $centreColumns = [];
        }

        $locationSelect = [];
        if (!empty($centreColumns['country_code'])) $locationSelect[] = 'country_code';
        if (!empty($centreColumns['county'])) $locationSelect[] = 'county';

        if ($locationSelect) {
            $locationStmt = $pdo->prepare('SELECT ' . implode(', ', $locationSelect) . ' FROM rescue_centres WHERE rescue_id = :centre_id LIMIT 1');
            $locationStmt->execute([':centre_id' => $user['centre_id']]);
            $location = $locationStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $_SESSION['country_code'] = !empty($location['country_code']) ? (string)$location['country_code'] : null;
            $_SESSION['county'] = !empty($location['county']) ? (string)$location['county'] : null;
        }
    }

    // Names
    $GLOBALS['first_name']  = $user['first_name'];
    $GLOBALS['last_name']   = $user['last_name'];

    // Human readable role name (for display only)
    $role_text = $user['rescue_role_name'] ?? 'Unknown';

    // Display-friendly format
    $GLOBALS['record_name'] =
        $user['first_name'] . ' ' . $user['last_name'] . ' (' . $role_text . ')';

} catch (Exception $e) {

    $GLOBALS['user_id']     = null;
    $GLOBALS['role']        = null;
    $GLOBALS['rescue_role'] = null;
    $GLOBALS['centre_id']   = null;
    $GLOBALS['first_name']  = null;
    $GLOBALS['last_name']   = null;
    $GLOBALS['record_name'] = null;

    unset($_SESSION['user_id'], $_SESSION['centre_id'], $_SESSION['country_code'], $_SESSION['county']);
}

?>
