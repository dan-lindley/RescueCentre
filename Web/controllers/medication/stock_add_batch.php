<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../dashmain.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../medicationstock.php?sub=add");
    exit;
}

$centre_id = $GLOBALS['centre_id'];
$user_id   = $GLOBALS['user_id'];

// Collect form data
$profile_id    = (int)$_POST['med_profile_id'];
$packs_in      = (float)$_POST['packs_in'];
$batch_number  = trim($_POST['batch_number']);
$expiry        = !empty($_POST['expiry']) ? $_POST['expiry'] : null;
$notes         = trim($_POST['notes'] ?? '');


// 1️⃣ Load the medication profile
$stmt = $pdo->prepare("
    SELECT 
        pack_quantity,
        stock_form_id,
        concentration_dose,
        concentration_volume,
        medication
    FROM rescue_stock_medication
    WHERE medication_profile_id = :id
      AND centre_id = :cid
");
$stmt->execute([
    ':id'  => $profile_id,
    ':cid' => $centre_id
]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    die("Invalid medication profile selected.");
}

$pack_quantity = (float)$profile['pack_quantity'];   // The size of one bottle/pack


// 2️⃣ Calculate total stock volume for the entire batch
$total_volume_in = $packs_in * $pack_quantity;


// 3️⃣ Insert the batch
$stmt = $pdo->prepare("
    INSERT INTO rescue_medication_trans (
        date,
        med_profile_id,
        packs_in,
        centre_id,
        user_id,
        batch_number,
        expiry,
        est_volume,
        reason_destroyed
    ) VALUES (
        NOW(),
        :profile,
        :packs_in,
        :centre_id,
        :user_id,
        :batch_number,
        :expiry,
        :est_volume,
        NULL
    )
");

$stmt->execute([
    ':profile'     => $profile_id,
    ':packs_in'    => $packs_in,
    ':centre_id'   => $centre_id,
    ':user_id'     => $user_id,
    ':batch_number'=> $batch_number,
    ':expiry'      => $expiry,
    ':est_volume'  => $total_volume_in
]);

$batch_id = $pdo->lastInsertId();


// 4️⃣ Insert the physical pack records
$insertPack = $pdo->prepare("
    INSERT INTO rescue_medication_packs 
        (med_trans_id, amount_remaining, status)
    VALUES 
        (:batch, :amount_remaining, 'sealed')
");

for ($i = 0; $i < $packs_in; $i++) {
    $insertPack->execute([
        ':batch'            => $batch_id,
        ':amount_remaining' => $pack_quantity
    ]);
}


// 5️⃣ Redirect back to the stock list
header("Location: ../../medicationstock.php?sub=list");
exit;
