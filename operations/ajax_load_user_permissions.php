<?php
require_once __DIR__ . '/../dashmain.php';
require_once __DIR__ . '/permissions.php';
include __DIR__ . '/../getcentreinfo.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
$centre_id = $_SESSION['centre_id'] ?? null;

$selected_user_id = (int)($_GET['user_id'] ?? 0);
if (!$selected_user_id) {
    echo "No user selected.";
    exit;
}

// Make $pdo, $centre_id, $selected_user_id available.
include __DIR__ . '/../views/permission_users_table.php';

