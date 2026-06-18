<?php
/*----------------------- FORM PROCESSING DISPOSITION-------------------*/
// disposition fields for admission table: disposition, disposition_date, disposition_user, 
// disposition_centre, disposition_comment, euthanasia_method 
// update for the patient form: status (Captive, Released or Deceased)
// disposition lookup (Held in captivity, Released, Transferred to another rescue, Died - Euthanised, Died - within 48 hours, Died - after 48 hours, Died - On admmission )

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* Edit Details Form Processing */
include "connect_to_mysql.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    function test_input($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    
  //Admissions table posted values
  $disp_admission_id = test_input($_POST["theadmissionid"]);
  $disp_centre = test_input($_POST["centre_id"]);
  $disp_user = test_input($_POST["disposition_user"]);
  $disp_comment = test_input($_POST["disposition_comment"]);
  $disp_disposition = test_input($_POST["disposition"]);
  $disp_euthanasia = test_input($_POST["euthanasia_method"]);
  $disp_date = test_input($_POST["disposition_date"]);
  $admission_status = 'Closed';

//Patient table value - just need the ID here
  $pat_patient_id = test_input($_POST["patient_id"]);

// figure out the patient's status from the posted disposition
  if ($disp_disposition == 'Released') {
        $pat_status = 'Released';
        $pat_state = 'Discharged';

  } elseif ($disp_disposition == 'Transferred out') {
        $pat_status = 'Transferred';
        $pat_state = 'Transferred';
      
  } elseif ($disp_disposition == 'Died - Euthanised') {
        $pat_status = 'Deceased';
        $pat_state = 'Deceased';
  } elseif ($disp_disposition == 'Died - within 48 hours') {
        $pat_status = 'Deceased';
        $pat_state = 'Deceased';
  } elseif ($disp_disposition == 'Died - after 48 hours') {
        $pat_status = 'Deceased';
        $pat_state = 'Deceased';
  } elseif ($disp_disposition == 'Died - on admission') {
        $pat_status = 'Deceased';
        $pat_state = 'Deceased';
  }

    try {
        //Update the database table
        $query1 = "UPDATE rescue_admissions
                      SET 
                  rescue_admissions.euthanasia_method = :euthanasia_method,
                  rescue_admissions.disposition_user = :disp_user,
                  rescue_admissions.disposition_centre = :disp_centre,
                  rescue_admissions.disposition = :disposition,
                  rescue_admissions.disposition_date = :disp_date,
                  rescue_admissions.status = :adm_status,
                  rescue_admissions.disposition_comment = :disp_comment
                  WHERE rescue_admissions.admission_id = :admission_id";
        
        $query2 = "UPDATE rescue_patients
                      SET 
                  rescue_patients.status = :pat_status,
                  rescue_patients.state = :pat_state
                  WHERE rescue_patients.patient_id = :patient_id";

        $stmt = $conn->prepare($query1);
        $stmt->bindParam('admission_id', $disp_admission_id, PDO::PARAM_INT);
        $stmt->bindParam('euthanasia_method', $disp_euthanasia, PDO::PARAM_STR);
        $stmt->bindParam('disp_user', $disp_user, PDO::PARAM_STR);
        $stmt->bindParam('adm_status', $admission_status, PDO::PARAM_STR);
        $stmt->bindParam('disp_centre', $disp_centre, PDO::PARAM_STR);
        $stmt->bindParam('disp_date', $disp_date, PDO::PARAM_STR);
        $stmt->bindParam('disp_comment', $disp_comment, PDO::PARAM_STR);
        $stmt->bindParam('disposition', $disp_disposition, PDO::PARAM_STR);

        $stmt->execute();
        
        $stmt2 = $conn->prepare($query2);
        $stmt2->bindParam('patient_id', $pat_patient_id, PDO::PARAM_INT);
        $stmt2->bindParam('pat_status', $pat_status, PDO::PARAM_STR);
        $stmt2->bindParam('pat_state', $pat_state, PDO::PARAM_STR);

        $stmt2->execute();
    } catch (PDOException $e) {
		echo $e->getMessage();
        die($e->getMessage());
    }
} else {
    echo "Error from the disposition";
    exit();
}

/*---------------------------------------------------------------------------------*/
