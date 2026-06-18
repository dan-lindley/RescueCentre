<?php

/* ------------------------------------------------------------
   GET patient_id (existing behaviour)
   ------------------------------------------------------------ */
function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data);
}

if (!isset($_GET['patient_id'])) {
    echo "Error #1 - Patient not found.";
    exit();
}

$patient_id = (int) test_input($_GET['patient_id']);

/* ------------------------------------------------------------
   SQL — PATIENT (UNCHANGED) + ACTIVE ADMISSION (ADDED)
   ------------------------------------------------------------ */
$sql = "
SELECT
    /* ---------------- PATIENT (ORIGINAL FIELDS) ---------------- */
    p.patient_id,
    p.name,
    p.ringed,
    p.ring_number,
    p.microchipped,
    p.microchip_number,
    p.animal_type,
    p.animal_order,
    p.animal_species,
    p.sex,
    p.status,
    p.date_added,
    p.centre_id,

    /* ---------------- ADMISSION (ADDED ONLY) ---------------- */
    a.admission_id,
    a.admission_date,
    a.current_location,
    a.finder_name,
    a.finder_tel,
    a.presenting_complaint,
    a.passphrase

FROM rescue_patients p

LEFT JOIN rescue_admissions a
       ON a.admission_id = (
            SELECT a2.admission_id
            FROM rescue_admissions a2
            WHERE a2.patient_id = p.patient_id
            ORDER BY a2.admission_date DESC, a2.admission_id DESC
            LIMIT 1
       )

WHERE p.patient_id = :patient_id
  AND p.centre_id = :centre_id

LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
$stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
$stmt->execute();

$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    echo "The patient ID was not found or does not relate to your rescue";
    exit();
}

/* ------------------------------------------------------------
   ORIGINAL PATIENT VARIABLES (UNCHANGED)
   ------------------------------------------------------------ */
$patient_name              = $result["name"];
$patient_ringed            = $result["ringed"];
$patient_ring_number       = $result["ring_number"];
$patient_microchipped      = $result["microchipped"];
$patient_microchip_number  = $result["microchip_number"];
$patient_animal_type       = $result["animal_type"];
$patient_animal_order      = $result["animal_order"];
$patient_animal_species    = $result["animal_species"];
$patient_sex               = $result["sex"];
$patient_status            = $result["status"];

$formatted_date = (new DateTime($result["date_added"]))
                    ->format('d-m-Y H:i');

/* ------------------------------------------------------------
   ADMISSION VARIABLES (NEW, ADDITIVE ONLY)
   ------------------------------------------------------------ */
$admission_id           = $result["admission_id"];
$admission_date         = $result["admission_date"];
$current_location       = $result["current_location"];
$finder_name            = $result["finder_name"];
$finder_tel             = $result["finder_tel"];
$presenting_complaint   = $result["presenting_complaint"];
$passphrase             = $result["passphrase"];
