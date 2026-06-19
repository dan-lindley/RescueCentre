<?php
// ajax/load_form.php

if (empty($_GET['form']) || empty($_GET['patient_id'])) {
    http_response_code(400);
    exit("Missing parameters");
}

$form = $_GET['form'];
$patient_id = (int)$_GET['patient_id'];

// Map form keys to controller files
$map = [
    'carenote'     => 'care_notes_form.php',
    'observation'  => 'add_observation.php',
    'prescription' => 'add_prescription.php',
    'medication'   => 'add_medsadmin.php',
    'treatment'    => 'add_treatment.php',
    'labs'         => 'add_labs.php',
    'weight'       => 'add_weight.php',
    'measurement'  => 'add_measurement.php',
    'tasks'        => 'add_tasks.php',
    'discharge'    => 'add_disposition.php'
];

if (!isset($map[$form])) {
    http_response_code(404);
    exit("Unknown form");
}

$target = __DIR__ . '/../controllers/' . $map[$form];

// Important: load ONLY the form markup — no headers, no wrappers
if (file_exists($target)) {
    include $target;
} else {
    http_response_code(404);
    echo "Form file not found.";
}
