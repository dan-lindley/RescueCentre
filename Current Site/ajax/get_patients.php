<?php
include "../connect_to_mysql.php";

function secure_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if (isset($_GET["status"])) {
    $patient_status = secure_input($_GET["status"]);
    $centre_id = secure_input($_GET["id"]);

    switch ($patient_status) {

        case "Captive":
            $status_msg = "These are patients which are currently in captivity.";
            break;

        case "Released":
            $status_msg = "These are patients which have been released from your care.";
            break;

        case "Deceased":
            $status_msg = "These are patients which have deceased.";
            break;
    }


    // prepare the query
    $query = "SELECT COUNT(*) as count FROM rescue_patients WHERE status = :patient_status AND centre_id = :centre_id ORDER by date_added DESC";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':patient_status', $patient_status, PDO::PARAM_STR);
    $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);

    // execute the query and fetch the result
    $stmt->execute();
    $count = $stmt->fetchColumn();


    print '<!-- Display people from the database -->
         
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">You have ' . $count . ' ' . $patient_status . ' Patients</h6>
                <p class="card_subheading">' . $status_msg . '</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered angelo_table" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                            <th>Patient Name</th>
                            <th>Sex</th>
                            <th>Animal Type</th>
                            <th>Animal Species</th>
                            <th>Date Added</th>
                            <th></th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                            <th>Patient Name</th>
                            <th>Sex</th>
                            <th>Animal Type</th>
                            <th>Animal Species</th>
                            <th>Date Added</th>
                            <th></th>
                            </tr>
                        </tfoot>
                        <tbody>';


    //Find applicants in the WP Users table. Make sure they aren't already a member 
    $stmt = $conn->prepare("SELECT * FROM rescue_patients WHERE status = :patient_status AND centre_id = :centre_id ORDER by `date_added` DESC");
    $stmt->bindParam(':patient_status', $patient_status, PDO::PARAM_STR);
    $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);


    // initialise an array for the results
    $applicants = array();
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $patient_id = $row["patient_id"];
        $patient_name = $row["name"];
        $ringed = $row["ringed"];
        $ring_number = $row["ring_number"];
        $microchipped = $row["microchipped"];
        $microchip_number = $row["microchip_number"];
        $animal_type = $row["animal_type"];
        $animal_order = $row["animal_order"];
        $animal_species = $row["animal_species"];
        $sex = $row["sex"];
        $status = $row["status"];
        $date_added = $row["date_added"];

        $date_added = new DateTime($date_added);
        $date_added = $date_added->format('d/m/Y - H:i');

        print '<tr>
        <td>' . $patient_name . '</td>
        <td>' . $sex . '</td>
        <td>' . $animal_type . '</td>
        <td>' . $animal_species . '</td>
        <td>' . $date_added . '</td>
        <td><a href="https://rescuecentre.org.uk/view-patient/?patient_id=' . $patient_id . '" class="btn btn-success">View Details</a></td>
        </tr>';
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
