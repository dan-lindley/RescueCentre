<?php
/**
 * Global include bootstrap for the Rescue Centre system
 * Loads config, DB connection, user session info, and shared helpers.
 */

// --- Ensure errors show in development (optional) ---
// Comment these out in production.
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Load config (database settings etc.) ---
require_once __DIR__ . '/config.php';

/// Build our own PDO here (same pattern you used in search_species.php)
try {
    $pdo = new PDO(
        "mysql:host=" . db_host . ";dbname=" . db_name . ";charset=" . db_charset,
        db_user,
        db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]
    );
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed.'
    ]);
    exit;
}

// --- Load global user information ---
require_once __DIR__ . '/getuserinfo.php';


// Add any shared helper functions below
// function safeId($id) { return (int)$id; }

?>
