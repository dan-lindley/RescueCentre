<?php

function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if (isset($_GET["id"])) {

    include_once "connect_to_mysql.php";

    $animal_type = $_GET["id"];

    //Get animal types from the database and loop through them
    $stmt = $conn->prepare("SELECT * FROM rescue_animal_species WHERE animal_type = :animal_type ORDER BY species_name ASC");
    $stmt->bindParam(':animal_type', $animal_type, PDO::PARAM_STR);
	

    // initialise an array for the results
    $applicants = array();
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $species_name = $row["species_name"];
		$scientific_name = $row["scientific_name"];
        print '<option value="' . $species_name . '">' . $species_name . ' (' . $scientific_name . ')</option>';
		print '<input type="hidden" name="species_id" value="' .$patient_id. '">';
    }
} else {
    echo "Error, species not found";
    exit();
}
