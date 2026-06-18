<?php
function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

include "../connect_to_mysql.php";

//Start new triage
$patient_id = test_input($_POST["patient_id"]);

    $alertmsg = "";

        //date uses date picker on form
        $centre_id = test_input($_POST["centre_id"]);
        $triage_date = test_input($_POST["triage_date"]);
        $admission_id = test_input($_POST["admission_id"]);
        $care_form_used = test_input($_POST["care_form_used"]);


        try {
        //Insert into the table
        $statement = $conn->prepare('INSERT INTO rescue_triages
        (patient_id,
        admission_id,
		triage_date,
        centre_id,
        care_form_used)

        VALUES (:patient_id,
        :admission_id,
        :centre_id,
		:triage_date,
        :care_form_used)');

        $statement->execute([
            'patient_id' => $patient_id,
            'centre_id' => $centre_id,
			'triage_date' => $triage_date,
            'admission_id' => $admission_id,
            'care_form_used' => $care_form_used
        ]);
 

        $alertmsg = '<div class="alert alert-success" role="alert">
        The triage has been started
        </div>';
    } catch (PDOException $e) {
        die($e->getMessage());
    }
 

}
else {
    echo "error";
    exit();
}

?>