<?php
// pdo.php
// PDO connection ONLY – extracted verbatim

// Include configuration (defines db_host, db_name, db_user, db_pass, db_charset)
include_once __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset,
        db_user,
        db_pass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    exit('Failed to connect to database: ' . $exception->getMessage());
}
