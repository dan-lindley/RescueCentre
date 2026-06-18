<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

include "../connect_to_mysql.php";

//Add New Patient Form 
$current_user_id = $_POST["thestaffid"];
$centre_id = 1;

    $alertmsg = "";
    $errorName = "";
    $errorSex = "";
    $errorRinged = "";
    $errorMicrochipped = "";
    $errorOrder = "";
    $errorType = "";
    $errorSpecies = "";


    function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

    if (
        empty($_POST["name"]) || empty($_POST["sex"]) || empty($_POST["ringed"]) ||
        empty($_POST["microchipped"]) || empty($_POST["animal_orders"]) || empty($_POST["animal_types"])
        || empty($_POST["animal_species"]) || empty($_POST["status"])
    ) {

        if (empty($_POST["name"])) {
            $errorName = "<span class='form_error'>Please enter a name or identifier</span>";
        }
        if (empty($_POST["sex"])) {
            $errorSex = "<span class='form_error'>Please select a sex</span>";
        }
        if (empty($_POST["ringed"])) {
            $errorRinged = "<span class='form_error'>Please specify if this animal is ringed</span>";
        }
        if (empty($_POST["microchipped"])) {
            $errorMicrochipped = "<span class='form_error'>Please specify if this animal is microchipped</span>";
        }
        if (empty($_POST["animal_orders"])) {
            $errorOrder = "<span class='form_error'>Please select an animal order</span>";
        }
        if (empty($_POST["animal_types"])) {
            $errorType = "<span class='form_error'>Please select an animal type</span>";
        }
        if (empty($_POST["animal_species"])) {
            $errorSpecies = "<span class='form_error'>Please select an animal species</span>";
        }
        if (empty($_POST["status"])) {
            $errorStatus = "<span class='form_error'>Please select a status</span>";
        }
    } else {

        //Get the current time from the server
        $date_added = date('Y-m-d H:i:s');

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
        $centre_id = test_input($_POST["centre_id"]);
		$new_state = test_input($_POST["state"]);
        $new_dob = test_input($_POST["dob"]);


        //Insert into the table
        $statement = $conn->prepare('INSERT INTO rescue_patients
        (name,
        ringed,
        ring_number,
        microchipped,
        microchip_number,
        animal_type,
        animal_order,
        animal_species,
        sex,
        approx_dob,
        status,
        staff_wp_id,
        centre_id,
        created_by,
		state,
        date_added)

        VALUES (:name,
        :ringed,
        :ring_number,
        :microchipped,
        :microchip_number,
        :animal_type,
        :animal_order,
        :animal_species,
        :sex,
        :dob,
        :status,
        :staff_wp_id,
        :centre_id,
        :created_by,
		:state,
        :date_added)');

        $statement->execute([
            'name' => $new_name,
            'ringed' => $new_ringed,
            'ring_number' => $new_ring_number,
            'microchipped' => $new_microchipped,
            'microchip_number' => $new_microchip_number,
            'animal_type' => $new_animal_types,
            'animal_order' => $new_animal_orders,
            'animal_species' => $new_animal_species,
            'sex' => $new_sex,
            'dob' => $new_dob,
            'status' => $new_status,
            'staff_wp_id' => $current_user_id,
            'centre_id' => $centre_id,
            'created_by' => $centre_id,
			'state' => $new_state,
            'date_added' => $date_added
        ]);
 

        $alertmsg = '<div class="alert alert-success" role="alert">
        The new patient was successfully added to the database.
        </div>';
    
    }

}
else {
    echo "error";
    exit();
}
?>
