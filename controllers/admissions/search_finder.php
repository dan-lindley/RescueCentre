<?php
// controllers/admissions/search_finder.php
header('Content-Type: application/json');

session_start();

require_once __DIR__ . '/../../config.php';

// ------------------------------------------------------------
// 1. Database connection
// ------------------------------------------------------------
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
        'error' => 'db_fail',
        'details' => $e->getMessage()
    ]);
    exit;
}

// ------------------------------------------------------------
// 2. Validate query string
// ------------------------------------------------------------
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($q === '' || strlen($q) < 2) {
    echo json_encode([]); // must be 2+ chars
    exit;
}

// ------------------------------------------------------------
// 3. Determine centre_id (same approach as save_section.php)
// ------------------------------------------------------------
$user_id   = $_SESSION['account_id'] ?? null;
$centre_id = $_SESSION['centre_id']  ?? null;

if (!$centre_id && $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT centre_id
            FROM accounts
            WHERE id = :uid
            LIMIT 1
        ");
        $stmt->execute([':uid' => $user_id]);
        $centre_id = $stmt->fetchColumn();

        if ($centre_id) {
            $_SESSION['centre_id'] = $centre_id;
        }
    } catch (Exception $e) {
        // ignore
    }
}

// If still no centre_id → no results
if (!$centre_id) {
    if (isset($_GET['debug'])) {
        echo json_encode([
            'error'     => 'no_centre',
            'user_id'   => $user_id,
            'centre_id' => $centre_id,
            'query'     => $q
        ]);
    } else {
        echo json_encode([]);
    }
    exit;
}

// ------------------------------------------------------------
// 4. Search finders — FIXED: use two placeholders (:q1, :q2)
// ------------------------------------------------------------
try {

    $like = '%' . $q . '%';

    $stmt = $pdo->prepare("
        SELECT finder_id, finder_name, finder_tel
        FROM rescue_finders
        WHERE centre_id = :centre_id
          AND deleted = 0
          AND (finder_name LIKE :q1 OR finder_tel LIKE :q2)
        ORDER BY finder_name ASC
        LIMIT 10
    ");

    $stmt->execute([
        ':centre_id' => $centre_id,
        ':q1'        => $like,
        ':q2'        => $like
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --------------------------------------------------------
    // 5. Debug output
    // --------------------------------------------------------
    if (isset($_GET['debug'])) {
        echo json_encode([
            'debug' => [
                'centre_id' => $centre_id,
                'user_id'   => $user_id,
                'query'     => $q,
                'rows'      => $rows
            ]
        ]);
        exit;
    }

    // --------------------------------------------------------
    // 6. Normal (non-debug) output
    // --------------------------------------------------------
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'finder_id'   => (int)$r['finder_id'],
            'finder_name' => $r['finder_name'],
            'finder_tel'  => $r['finder_tel']
        ];
    }

    echo json_encode($out);
    exit;

} catch (PDOException $e) {
    echo json_encode([
        'error'   => 'sql',
        'details' => $e->getMessage()
    ]);
    exit;
}
