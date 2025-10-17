
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
$patient_id = test_input($_POST["weight_thepatientid"]);

    $alertmsg = "";

        //date uses date picker on form
        $date_added = test_input($_POST["date"]);

        $weight = test_input($_POST["weight"]);
        $weight_unit = test_input($_POST["weight_unit"]);


        try {
        //Insert into the table
        $statement = $conn->prepare('INSERT INTO rescue_weights
        (patient_id,
        date,

        weight,
        weight_unit)

        VALUES (:patient_id,
        :date,

        :weight,
        :weight_unit)');

        $statement->execute([
            'patient_id' => $patient_id,
            'date' => $date_added,

            'weight' => $weight,
            'weight_unit' => $weight_unit
        ]);
 

        $alertmsg = '<div class="alert alert-success" role="alert">
        The weight was added to the patient record.
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