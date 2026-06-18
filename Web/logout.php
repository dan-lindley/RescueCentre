<?php
session_start();
require 'main.php';

// Revoke remember-me token server-side
if (!empty($_SESSION['account_id'])) {
    $stmt = $pdo->prepare(
        'UPDATE accounts SET remember_me_code = NULL WHERE id = ?'
    );
    $stmt->execute([ $_SESSION['account_id'] ]);
}

// Remove remember-me cookie client-side
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/');
    unset($_COOKIE['remember_me']);
}

// Destroy session
session_destroy();

// Redirect to login
header('Location: index.php');
exit;
