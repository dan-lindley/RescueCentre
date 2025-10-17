
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

    $admission_id = $_POST["theadmissionid"];
	
	 


    //Set variables using the POST data from the form
    $new_status = test_input($_POST["status"]);
    $new_disposition = test_input($_POST["disposition"]);
	$new_current_location = test_input($_POST["current_location"]);

	
	
    try {
        //Update the database table
        $query = "UPDATE rescue_admissions
		
		SET 
        rescue_admissions.status = :status,
		rescue_admissions.current_location = :current_location,
        rescue_admissions.disposition = :disposition
		
		
	
        WHERE rescue_admissions.admission_id = :admission_id 
	
		LIMIT 1";

        $stmt = $conn->prepare($query);
        $stmt->bindParam('admission_id', $admission_id, PDO::PARAM_INT);
		$stmt->bindParam('current_location', $new_current_location, PDO::PARAM_STR);
        $stmt->bindParam('status', $new_status, PDO::PARAM_STR);
        $stmt->bindParam('disposition', $new_disposition, PDO::PARAM_STR);
		
		
        $stmt->execute();
    } catch (PDOException $e) {
        die($e->getMessage());
    }
} else {
    echo "Error 4: Admission ID not defined";
    //exit();
}
/*---------------------------------------------------------------------------------*/