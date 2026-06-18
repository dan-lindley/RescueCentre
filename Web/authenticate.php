<?php
include 'main.php';

/* -----------------------------------------
   SIMPLE RATE LIMIT (SESSION-BASED)
   - 5 failures in 10 mins => 60s lockout
----------------------------------------- */
$MAX_FAILS     = 5;
$WINDOW_SECS   = 10 * 60;  // 10 minutes
$LOCKOUT_SECS  = 60;       // 60 seconds

if (!isset($_SESSION['login_rl'])) {
    $_SESSION['login_rl'] = [
        'fails' => 0,
        'first_fail_ts' => 0,
        'locked_until' => 0
    ];
}

$now = time();

// If locked, block immediately
if (!empty($_SESSION['login_rl']['locked_until']) && $now < (int)$_SESSION['login_rl']['locked_until']) {
    $remaining = (int)$_SESSION['login_rl']['locked_until'] - $now;
    exit('Error: Too many failed attempts. Please wait ' . $remaining . ' seconds and try again.');
}

// Reset window if expired
if (!empty($_SESSION['login_rl']['first_fail_ts']) && ($now - (int)$_SESSION['login_rl']['first_fail_ts']) > $WINDOW_SECS) {
    $_SESSION['login_rl'] = ['fails' => 0, 'first_fail_ts' => 0, 'locked_until' => 0];
}

// Helper to record a failure
$record_fail = function() use ($now, $MAX_FAILS, $LOCKOUT_SECS) {
    if (empty($_SESSION['login_rl']['first_fail_ts'])) {
        $_SESSION['login_rl']['first_fail_ts'] = $now;
    }
    $_SESSION['login_rl']['fails'] = (int)($_SESSION['login_rl']['fails'] ?? 0) + 1;

    if ($_SESSION['login_rl']['fails'] >= $MAX_FAILS) {
        $_SESSION['login_rl']['locked_until'] = $now + $LOCKOUT_SECS;
    }
};
/* --------------------------------------- */

// Validate POST
if (!isset($_POST['identity'], $_POST['password'])) {
    $record_fail();
    exit('Error: Please fill both fields!');
}

$identity = trim((string)$_POST['identity']);
$password = (string)$_POST['password'];

// Look up account by username OR email
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE username = ? OR email = ? LIMIT 1');
$stmt->execute([ $identity, $identity ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

// Verify credentials
if (!$account || !password_verify($password, (string)$account['password'])) {
    $record_fail();
    exit('Error: Incorrect username/email or password!');
}

// Enforce existing account status rules
if (account_activation && ($account['activation_code'] ?? '') !== 'activated') {
    $record_fail();
    exit('Error: Please activate your account to login! Click <a href="resend-activation.php" class="form-link">here</a> to resend the activation email.');
}
if (($account['activation_code'] ?? '') === 'deactivated') {
    $record_fail();
    exit('Error: Your account has been deactivated!');
}
if (account_approval && empty($account['approved'])) {
    $record_fail();
    exit('Error: Your account has not been approved yet!');
}

// SUCCESS: reset rate limit counters
$_SESSION['login_rl'] = ['fails' => 0, 'first_fail_ts' => 0, 'locked_until' => 0];

// Create session
session_regenerate_id(true);

$_SESSION['account_loggedin'] = true;
$_SESSION['account_name']     = $account['username'];
$_SESSION['account_id']       = (int)$account['id'];
$_SESSION['centre_id']        = $account['centre_id'] ?? null;
$_SESSION['account_role']     = $account['role'];
$_SESSION['rescue_role']      = $account['rescue_role'] ?? null;

// Secondary access + onboarding
$_SESSION['vet_id']     = $account['vet_id'] ?? null;
$_SESSION['ngo_id']     = $account['ngo_id'] ?? null;
$_SESSION['vet_ok']     = (int)($account['vet_ok'] ?? 0);
$_SESSION['ngo_ok']     = (int)($account['ngo_ok'] ?? 0);
$_SESSION['onboarded']  = (int)($account['onboarded'] ?? 0);
$_SESSION['dark_mode']  = (int)($account['dark_mode'] ?? 0);

// --- LOCALIZATION: Fetch country & county from user's centre/ngo ---
$country_code = null;
$county       = null;

// Try to fetch from rescue centre first (primary location)
if (!empty($account['centre_id'])) {
    $centre_columns = [];
    try {
        foreach ($pdo->query('SHOW COLUMNS FROM rescue_centres')->fetchAll(PDO::FETCH_ASSOC) as $col) {
            $centre_columns[(string)$col['Field']] = true;
        }
    } catch (Throwable $e) {
        $centre_columns = [];
    }

    $select = [];
    if (!empty($centre_columns['country_code'])) $select[] = 'country_code';
    if (!empty($centre_columns['county'])) $select[] = 'county';

    if ($select) {
        $stmt = $pdo->prepare('SELECT ' . implode(', ', $select) . ' FROM rescue_centres WHERE rescue_id = ? LIMIT 1');
        $stmt->execute([ $account['centre_id'] ]);
        $centre = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($centre && !empty($centre['country_code'])) {
            $country_code = (string)$centre['country_code'];
        }
        if ($centre && !empty($centre['county'])) {
            $county = (string)$centre['county'];
        }
    }
}

// Try to fetch county from NGO if available (some users belong to NGOs)
if (!empty($account['ngo_id'])) {
    $stmt = $pdo->prepare('SELECT county, country FROM rescue_orgs WHERE org_id = ? LIMIT 1');
    $stmt->execute([ $account['ngo_id'] ]);
    $ngo = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($ngo) {
        if (empty($county) && !empty($ngo['county'])) {
            $county = (string)$ngo['county'];
        }
        if (empty($country_code) && !empty($ngo['country'])) {
            $country_code = (string)$ngo['country'];
        }
    }
}

// Store in session using helper function
if (file_exists(__DIR__ . '/lib/localization_helper.php')) {
    require_once __DIR__ . '/lib/localization_helper.php';
    set_user_location_context($country_code, $county);
} else {
    // Fallback if helper not loaded
    $_SESSION['country_code'] = $country_code;
    $_SESSION['county']       = $county;
}

// CSRF token for this authenticated session
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

/* ---------------- REMEMBER ME (HARDENED) ---------------- */
if (!empty($_POST['remember_me'])) {
    $token = bin2hex(random_bytes(32));
    $hash  = hash('sha256', $token);

    $stmt = $pdo->prepare('UPDATE accounts SET remember_me_code = ? WHERE id = ?');
    $stmt->execute([ $hash, $account['id'] ]);

    setcookie('remember_me', $token, [
        'expires'  => time() + (60 * 60 * 24 * 30),
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}
/* -------------------------------------------------------- */

// Update last seen
$stmt = $pdo->prepare('UPDATE accounts SET last_seen = NOW() WHERE id = ?');
$stmt->execute([ $account['id'] ]);

// Force onboarding first (your existing behaviour)
if ($_SESSION['onboarded'] !== 1) {
    echo 'Redirect: onboarding.php';
} else {
    echo 'Redirect: home.php';
}
