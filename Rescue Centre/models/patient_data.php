<?php 
//Retrieve the GET value from the URL, and sanitise it for security purposes
function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if (isset($_GET["patient_id"])) {
    $patient_id = test_input($_GET["patient_id"]);
} else {
    echo "Error #1 - Patient not found.";
    exit();
}

if (isset($_GET["alert"])) {
    $alert = test_input($_GET["alert"]);

    if ($alert = 1) {
        $alertmsg = "<div class='alert alert-success' role='alert'>
        This patient's details were updated in the database.
        </div>";
    } else if ($alert = 2) {
        $alertmsg = "<div class='alert alert-success' role='alert'>
        Alert
        </div>";
    } else {
        $alertmsg = "";
    }
} else {
    $alertmsg = "";
}


//Get the information from the database
$sql = 'SELECT * FROM rescue_patients WHERE patient_id=:patient_id AND centre_id=:centre_id LIMIT 1';
$statement = $conn->prepare($sql);
$statement->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
$statement->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
/*---------------------------------------------------------------------------------*/
if ($result) {
    $patient_name = $result["name"];
    $patient_ringed = $result["ringed"];
    $patient_ring_number = $result["ring_number"];
    $patient_microchipped = $result["microchipped"];
    $patient_microchip_number = $result["microchip_number"];
    $patient_animal_type = $result["animal_type"];
    $patient_animal_order = $result["animal_order"];
    $patient_animal_species = $result["animal_species"];
    $patient_sex = $result["sex"];
    $patient_status = $result["status"];
    $date_added = $result["date_added"];

    $formatted_date = new DateTime($date_added);
    $formatted_date = $formatted_date->format('d-m-Y H:i');
} else {
    echo "The patient ID was not found or does not relate to your rescue";
    exit();
}
