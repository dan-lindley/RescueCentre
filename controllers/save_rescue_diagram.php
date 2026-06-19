<?php

require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../getuserinfo.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");

// Read raw JSON input **ONCE ONLY**
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// If no JSON was sent (GET request), show a simple message and exit safely
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(["status" => "ready"]);
    exit;
}

// If JSON failed to decode during POST, stop
if (!$data) {
    echo json_encode(["status" => "error", "message" => "No JSON received"]);
    exit;
}


// If no JSON was sent (GET request), show a simple message and exit safely
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(["status" => "ready"]);
    exit;
}

// If JSON failed to decode during POST, stop
if (!$data) {
    echo json_encode(["status" => "error", "message" => "No JSON received"]);
    exit;
}


// ✅ Extract expected fields safely
$centre_id     = (int)$data["centre_id"];
$patient_id    = (int)$data["patient_id"];
$user_id       = (int)$data["user_id"];

$background_used = trim($data["background_used"]);
$diagram_png     = $data["diagram_png"];   // base64 text PNG
$label_data      = isset($data["label_data"]) 
                   ? json_encode($data["label_data"]) 
                   : null;

$canvas_width  = isset($data["canvas_width"])  ? (int)$data["canvas_width"]  : 1500;
$canvas_height = isset($data["canvas_height"]) ? (int)$data["canvas_height"] : 900;

// ✅ Basic validation
if (
  !$centre_id || !$patient_id || !$user_id ||
  !$background_used || !$diagram_png
) {
  http_response_code(400);
  echo json_encode(["error" => "Missing required fields"]);
  exit;
}

// ✅ Insert into database
$sql = "
  INSERT INTO rescue_diagrams
  (
    centre_id,
    patient_id,
    user_id,
    background_used,
    diagram_png,
    label_data,
    canvas_width,
    canvas_height
  )
  VALUES
  (?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = $pdo->prepare($sql);

$ok = $stmt->execute([
  $centre_id,
  $patient_id,
  $user_id,
  $background_used,
  $diagram_png,
  $label_data,
  $canvas_width,
  $canvas_height
]);

if (!$ok) {
    echo json_encode([
        "status" => "db_error",
        "error"  => $stmt->errorInfo()
    ]);
    exit;
}


$diag_id = $pdo->lastInsertId();

// ✅ Success response
echo json_encode([
  "status"  => "success",
  "diag_id" => $diag_id
]);
