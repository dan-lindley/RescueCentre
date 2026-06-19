<?php
// controllers/search_species.php

header('Content-Type: application/json');

// =============================
// LOAD CONFIG + CONNECT TO DB
// =============================
require_once __DIR__ . '/../config.php';

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
    echo json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]);
    exit;
}

// =============================
// GET QUERY VALUE
// =============================
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($q === '') {
    echo json_encode([]);
    exit;
}

try {

    // =============================================
    // FIXED: UNIQUE PLACEHOLDERS (:q1, :q2, :q3)
    // =============================================
    $sql = "
        SELECT 
            s.species_id,
            s.species_name,
            s.scientific_name,
            t.type_name,
            t.animal_order AS order_name
        FROM rescue_animal_species s
        LEFT JOIN rescue_animal_types t
              ON t.type_name = s.animal_type
        WHERE 
            LOWER(s.species_name)     LIKE LOWER(:q1)
         OR LOWER(s.scientific_name)  LIKE LOWER(:q2)
         OR LOWER(t.type_name)        LIKE LOWER(:q3)
        ORDER BY s.species_name ASC
    ";

    $stmt = $pdo->prepare($sql);

    $like = '%' . $q . '%';

    $stmt->execute([
        ':q1' => $like,
        ':q2' => $like,
        ':q3' => $like
    ]);

    $rows = $stmt->fetchAll();

    // =============================
    // FORMAT RESULTS
    // =============================
    $out = [];

    foreach ($rows as $r) {

        $display = $r['species_name'];

        if (!empty($r['scientific_name'])) {
            $display .= " (" . $r['scientific_name'] . ")";
        }

        $out[] = [
            'species_display' => $display,
            'species_name'    => $r['species_name'],
            'type_name'       => $r['type_name'],
            'order_name'      => $r['order_name']
        ];
    }

    echo json_encode($out);
    exit;

} catch (Exception $e) {
    echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
    exit;
}
