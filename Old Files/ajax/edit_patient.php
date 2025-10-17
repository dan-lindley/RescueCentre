<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* Edit Details Form Processing */
include "../connect_to_mysql.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    function test_input($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    $patient_id = $_POST["thepatientid"];

    //Set variables using the POST data from the form
    $new_name = test_input($_POST["name"]);
    $new_sex = test_input($_POST["sex"]);
    $new_ringed = test_input($_POST["ringed"]);
    $new_ring_number = test_input($_POST["ring_number"]);
    $new_microchipped = test_input($_POST["microchipped"]);
    $new_microchip_number = test_input($_POST["microchip_number"]);
    $new_animal_orders = test_input($_POST["animal_orders"]);
    $new_animal_types = test_input($_POST["animal_types"]);
    $new_animal_species = test_input($_POST["animal_species"]);
    $new_status = test_input($_POST["status"]);

    try {
        //Update the database table
        $query = "UPDATE rescue_patients SET 
        name = :name,
        ringed = :ringed,
        ring_number = :ring_number,
        microchipped = :microchipped,
        microchip_number = :microchip_number,
        animal_type = :animal_type,
        animal_order = :animal_order,
        animal_species = :animal_species,
        sex = :sex,
        status = :status
        WHERE patient_id = :patient_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam('patient_id', $patient_id, PDO::PARAM_INT);
        $stmt->bindParam('name', $new_name, PDO::PARAM_STR);
        $stmt->bindParam('ringed', $new_ringed, PDO::PARAM_STR);
        $stmt->bindParam('ring_number', $new_ring_number, PDO::PARAM_STR);
        $stmt->bindParam('microchipped', $new_microchipped, PDO::PARAM_STR);
        $stmt->bindParam('microchip_number', $new_microchip_number, PDO::PARAM_STR);
        $stmt->bindParam('animal_type', $new_animal_types, PDO::PARAM_STR);
        $stmt->bindParam('animal_order', $new_animal_orders, PDO::PARAM_STR);
        $stmt->bindParam('animal_species', $new_animal_species, PDO::PARAM_STR);
        $stmt->bindParam('sex', $new_sex, PDO::PARAM_STR);
        $stmt->bindParam('status', $new_status, PDO::PARAM_STR);
        $stmt->execute();
    } catch (PDOException $e) {
        die($e->getMessage());
    }
} else {
    echo "Error 4: Patient ID not defined";
    exit();
}
/*---------------------------------------------------------------------------------*/
//-- insert here MODAL for patient edit -->
?>