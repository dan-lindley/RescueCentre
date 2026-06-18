<?php
include "../connect_to_mysql.php";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function secure_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if (isset($_GET["status"])) {
    $disposition = secure_input($_GET["status"]);
    $centre_id = $_GET["id"];

    //Row Count
    $sql = "SELECT * 
    FROM rescue_admissions
    INNER JOIN rescue_patients
    ON rescue_admissions.patient_id = rescue_patients.patient_id
    WHERE rescue_admissions.disposition = :disposition AND rescue_patients.centre_id = :centre_id
    ORDER by `admission_date` DESC";
    $stmt = $conn->prepare($sql);

    // bind parameters
    $stmt->bindParam(':centre_id', $centre_id);
    $stmt->bindParam(':disposition', $disposition);

    // execute query
    $stmt->execute();

    // get row count
    $row_count = $stmt->rowCount();


    print '<!-- Display people from the database -->
     
            <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">You have ' . $row_count . ' with the disposition: ' . $disposition . '</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered angelo_table" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        <th>Admission Date</th>
                        <th>Patient</th>
                        <th>Animal Type</th>
                        <th>Sex</th>
                        <th>Presenting Complaint</th>
                        <th>Starved</th>
                        <th>Dehydrated</th>
                        <th>Weight</th>
                        <th>Measurement</th>
                        <th></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th>Admission Date</th>
                        <th>Patient</th>
                        <th>Animal Type</th>
                        <th>Sex</th>
                        <th>Presenting Complaint</th>
                        <th>Starved</th>
                        <th>Dehydrated</th>
                        <th>Weight</th>
                        <th>Measurement</th>
                        <th></th>
                    </tr>
                </tfoot>
                <tbody>';


    $stmt = $conn->prepare("SELECT * 
                FROM rescue_admissions
                INNER JOIN rescue_patients
                ON rescue_admissions.patient_id = rescue_patients.patient_id
                WHERE rescue_admissions.disposition = :disposition AND rescue_patients.centre_id = :centre_id
                ORDER by `admission_date` DESC");
    $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
    $stmt->bindParam(':disposition', $disposition, PDO::PARAM_STR);

    // initialise an array for the results
    $applicants = array();
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $admission_id = $row["admission_id"];
        $admission_patient_id = $row["patient_id"];
        $admission_date = $row["admission_date"];
        $admission_name = $row["name"];
        $admission_animal_type = $row["animal_type"];
        $admission_animal_species = $row["animal_species"];
        $admission_sex = $row["sex"];
        $admission_presenting_complaint = $row["presenting_complaint"];
        $admission_starved = $row["starved"];
        $admission_dehydrated = $row["dehydrated"];
        $admission_weight = $row["weight"];
        $admission_weight_unit = $row["weight_unit"];
        $admission_measurement = $row["measurement"];
        $admission_measurement_unit = $row["measurement_unit"];

        $admission_date  = new DateTime($admission_date);
        $admission_date = $admission_date->format('d/m/Y H:i');

        print '<tr>
                    <td>' . $admission_date . '</td>
                    <td>' . $admission_name . '</td>
                    <td>' . $admission_animal_species . ' (' . $admission_animal_type . ')</td>
                    <td>' . $admission_sex . '</td>
                    <td>' . $admission_presenting_complaint . '</td>
                    <td>' . $admission_starved . '</td>
                    <td>' . $admission_dehydrated . '</td>
                    <td>' . $admission_weight . '' . $admission_weight_unit . '</td>
                    <td>' . $admission_measurement . '' . $admission_measurement_unit . '</td>
                    <td><a href="https://rescuecentre.org.uk/view-patient/?patient_id=' . $admission_patient_id . '" class="btn btn-success">View Patient</a></td>';
    }

    print '
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!------------------------------------------------------->';
} else {
    exit();
}
