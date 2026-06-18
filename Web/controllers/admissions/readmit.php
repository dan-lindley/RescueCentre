<?php
// controllers/admission/readmit.php
// ------------------------------------------------------------
// Re-admit existing patient (microchip/ring search flow)
// - Validates logged-in + centre_id
// - Validates patient exists
// - Creates a NEW admission row for current centre
// - Redirects straight to admission wizard Section 2
// ------------------------------------------------------------

declare(strict_types=1);

// You want the same auth/session + $pdo that the rest of Reception uses
require_once __DIR__ . '/../../dashmain.php';      // defines $pdo + session + check_loggedin()
require_once __DIR__ . '/../../getuserinfo.php';
require_once __DIR__ . '/../../getcentreinfo.php';

// ------------------------------------------------------------
// INPUT
// ------------------------------------------------------------
$pid = isset($_GET['pid']) ? (int)$_GET['pid'] : 0;

if ($pid <= 0) {
    http_response_code(400);
    exit('Invalid patient_id.');
}

// ------------------------------------------------------------
// CONTEXT
// ------------------------------------------------------------
$centre_id = (int)($_SESSION['centre_id'] ?? 0);
if ($centre_id <= 0) {
    http_response_code(403);
    exit('No centre_id in session.');
}

// ------------------------------------------------------------
// LOAD PATIENT (must exist)
// ------------------------------------------------------------
$stmt = $pdo->prepare("SELECT patient_id FROM rescue_patients WHERE patient_id = :pid LIMIT 1");
$stmt->execute([':pid' => $pid]);
$exists = $stmt->fetchColumn();

if (!$exists) {
    http_response_code(404);
    exit('Patient not found.');
}

// ------------------------------------------------------------
// OPTIONAL SAFETY: If already has an ACTIVE admission, redirect to it
// (prevents accidental duplicates if staff click twice).
// Adjust the status value here if your system uses something else.
// ------------------------------------------------------------
try {
    $stmt = $pdo->prepare("
        SELECT admission_id
        FROM rescue_admissions
        WHERE patient_id = :pid
          AND status = 'active'
        ORDER BY admission_date DESC, admission_id DESC
        LIMIT 1
    ");
    $stmt->execute([':pid' => $pid]);
    $activeAid = (int)($stmt->fetchColumn() ?: 0);

    if ($activeAid > 0) {
        header('Location: ../../admission.php?sid=2&pid=' . $pid . '&aid=' . $activeAid);
        exit;
    }
} catch (Exception $e) {
    // If status column differs in some installs, don’t block the workflow.
}

// ------------------------------------------------------------
// CREATE NEW ADMISSION
// Minimal fields only; Section 2+ will populate the rest.
// ------------------------------------------------------------
$stmt = $pdo->prepare("
    INSERT INTO rescue_admissions
        (patient_id, centre_id, admission_date, status)
    VALUES
        (:pid, :cid, NOW(), 'active')
");
$stmt->execute([
    ':pid' => $pid,
    ':cid' => $centre_id
]);

$aid = (int)$pdo->lastInsertId();
if ($aid <= 0) {
    http_response_code(500);
    exit('Failed to create admission.');
}

// ------------------------------------------------------------
// REDIRECT → Section 2 (skip Section 1)
// ------------------------------------------------------------
header('Location: ../../admission.php?sid=2&pid=' . $pid . '&aid=' . $aid);
exit;
