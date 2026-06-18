
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

//Add New Measurment Form 
$patient_id = test_input($_POST["measurement_thepatientid"]);

    $alertmsg = "";

        //Get the current time from the server
        $date_added = test_input($_POST["date"]);

        $measurement = test_input($_POST["measurement"]);
        $measurement_unit = test_input($_POST["measurement_unit"]);


        try {
        //Insert into the table
        $statement = $conn->prepare('INSERT INTO rescue_measurements
        (patient_id,
        date,

        measurement,
        measurement_unit)

        VALUES (:patient_id,
        :date,

        :measurement,
        :measurement_unit)');

        $statement->execute([
            'patient_id' => $patient_id,
            'date' => $date_added,

            'measurement' => $measurement,
            'measurement_unit' => $measurement_unit
        ]);
 

        $alertmsg = '<div class="alert alert-success" role="alert">
        The measurement was added to the patient record.
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