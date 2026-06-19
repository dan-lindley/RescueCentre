<?php
require_once __DIR__ . '/../dashmain.php';
require_once __DIR__ . '/../operations/audit.php';
require_once __DIR__ . '/../getcentreinfo.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['create_account'])) {
    header("Location: ../user_accounts.php");
    exit;
}

session_start();

$new_first_name   = trim($_POST['first_name'] ?? '');
$new_last_name    = trim($_POST['last_name'] ?? '');
$new_username     = trim($_POST['username'] ?? '');
$new_email        = trim($_POST['email'] ?? '');
$new_rescue_role  = isset($_POST['rescue_role']) ? (int)$_POST['rescue_role'] : 0;
$new_password     = $_POST['password'] ?? '';
$new_password2    = $_POST['password_confirm'] ?? '';

$errors = [];

// Basic validation
if ($new_username === '') $errors[] = 'Username is required.';
if ($new_email === '' || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
if ($new_rescue_role <= 0) $errors[] = 'Role is required.';
if ($new_password === '' || $new_password2 === '') $errors[] = 'Password fields required.';
elseif ($new_password !== $new_password2) $errors[] = 'Passwords do not match.';
elseif (strlen($new_password) < 8) $errors[] = 'Password must be 8+ characters.';

// User & email uniqueness
if (!$errors) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM accounts WHERE username = :u");
    $stmt->execute([':u' => $new_username]);
    if ($stmt->fetchColumn()) $errors[] = 'Username is already taken.';

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM accounts WHERE email = :e");
    $stmt->execute([':e' => $new_email]);
    if ($stmt->fetchColumn()) $errors[] = 'Email is already in use.';
}

if ($errors) {
    $_SESSION['create_user_errors'] = $errors;
    $_SESSION['create_user_old']    = $_POST;

    header("Location: ../user_accounts.php?tab=tab-users");
    exit;
}

// Create user
$hash = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO accounts
    (username, password, email, role, rescue_role, approved, activation_code, centre_id, first_name, last_name, onboarded, registered)
VALUES
    (:username, :password, :email, 'Member', :rescue_role, 1, 'activated', :centre_id, :first_name, :last_name, 1, NOW())

");

$stmt->execute([
    ':username'    => $new_username,
    ':password'    => $hash,
    ':email'       => $new_email,
    ':rescue_role' => $new_rescue_role,
    ':centre_id'   => $centre_id,
    ':first_name'  => $new_first_name,
    ':last_name'   => $new_last_name
]);

$new_user_id = $pdo->lastInsertId();

// Audit log
audit_write($pdo, 'account_created', 'accounts', null, [
    'new_user_id' => $new_user_id,
    'username'    => $new_username,
    'email'       => $new_email,
    'rescue_role' => $new_rescue_role
]);

header("Location: ../user_accounts.php?success_msg=1&tab=tab-users");
exit;
