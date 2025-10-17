<?php 
/*----------------------- FORM PROCESSING PATIENT ALERT-------------------*/
//Check if the notes form was submitted
if (isset($_POST['patient_alerts'])) {

    $alert_message = $_POST["alert_message"];
    $alert_url = $_POST["url"];
	$alert_type = $_POST["alert_type"];
    $alert_centre_id = $_POST["centre_id"];
	$is_active = $_POST["is_active"];
	$alert_patient = $_POST["alert_patient"];


    //Get the current time from the server
    $date = date('Y-m-d H:i:s');

    try {
        $statement = $conn->prepare('INSERT INTO rescue_alerts
            (centre_id,
            alert_message,
            url,
			alert_type,
			is_active,
			patient_id,
            date)
            
            VALUES (:centre_id,
            :alert_message,
            :url,
			:alert_type,
			:is_active,
			:patient_id,
            :date)');

        $statement->execute([
 
			'centre_id' => $alert_centre_id,
            'alert_message' => $alert_message,
            'url' => $alert_url,
			'alert_type' => $alert_type,
			'is_active' => $is_active,
			'patient_id' => $alert_patient,
            'date' => $date
        ]);
    } catch (PDOException $e) {
        echo "Database Error: The alert could not be added.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The alert could not be added.<br>" . $e->getMessage();
        exit();
    }
}
/*------------ END FORM ----------------*/
/*------------ FORM PROCESSING - Delete Alert -------------*/
if (isset($_POST['deletepalert'])) {

    $alert_id = $_POST["alert_id"];
    $is_deleted = $_POST["is_deleted"];

    try {
        $statement = $conn->prepare('INSERT INTO rescue_alerts
            ( 
            alert_id,
			is_deleted)
            
            VALUES (
            :alert_id,
			:is_deleted) 
			
			ON DUPLICATE KEY UPDATE
			is_deleted = :is_deleted	
			');

        $statement->execute([
            'alert_id' => $alert_id,
            'is_deleted' => $is_deleted
			
            
        ]);
		echo "<meta http-equiv='refresh' content='0'>";
		
    } catch (PDOException $e) {
        echo "Database Error: Can not delete the alert.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: Can not delete the alert.<br>" . $e->getMessage();
        exit();
    }

}
?>

<!--- SECTION TO  DSIPAY PREVIOUS ALERTS ---->

<div class="table-responsive">
                    <table class="table table-bordered angelo_table" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
								<th>Patient</th>
                                <th>Alert (patient specific)</th>             
							    <th></th>                         
                            </tr>
                        </thead>
                        <tbody>
                    <?php
                                    //gets the alerts from the table to display 
                                    $stmt = $conn->prepare("SELECT * FROM rescue_alerts 
									JOIN rescue_patients ON rescue_patients.patient_id = rescue_alerts.patient_id
									WHERE NOT rescue_alerts.patient_id=0 AND rescue_alerts.centre_id = :centre_id AND is_deleted=0 ORDER by date DESC LIMIT 10");
                                    $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);

                                    // initialise an array for the results
                                    $patient_alerts = array();
                                    $stmt->execute();
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                                        $date = $row["date"];
										$palert_id = $row["alert_id"];
                                        $v_alert_message = $row["alert_message"];
										$v_alert_is_active = $row["is_active"];
										$v_alert_type = $row["alert_type"];
										$v_patient_id = $row["patient_id"];
										$v_patient_name = $row["name"];
										$v_patient_species = $row["animal_species"]; 
  										
						$v_fd = new DateTime($date);
                        $v_fd = $v_fd->format('d/m/y');

                                        print '
                                    <tr><td>CRN: ' . $v_patient_id . ' - ' . $v_patient_name . ' (' . $v_patient_species . ')</td>
									<td><div class="alert ' . $v_alert_type . ' " role="alert">' . $v_fd . ' - ' . $v_alert_message . ' </div></td>
									<td><form method="post" action=""><input type="hidden" id="alert_id" name="alert_id" value="'. $palert_id . '"><input type="hidden" id="is_deleted" name="is_deleted" value="1"><button type="submit" class="btn btn-secondary btn-danger" name="deletepalert">Delete</button> 
                    </form>      </td>		
                                    </tr></tbody>';
                                    }
                                    ?>
							
							
							
                      
						</table>
                        <br>
<!-- END OF ALERTS SECTION -->	  



<!-- ALERTS MODAL FORM -->
				   
                    <div class="modal fade" id="addpatientalertModal" tabindex="-1" role="dialog" aria-labelledby="addpatientalertModal" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                               <div class="modal-header">
                                    <h4 class="font-weight-bold text-primary">Add a patient alert</h4>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>

                                <div class="modal-body">
								<form action="" method="post">
									<div class="row lead_form_row">
                                    <div class="col-md-6">
										<p class="angelo_form_label">Select Patient</p>
                            <select name="alert_patient" name="alert_patient" id="alert_patient" required>
                                <option value="" disabled selected>Select a patient</option>
                                <?php
                                //Find patients stored in the patients table 
                                $stmt = $conn->prepare("SELECT * 
                                FROM rescue_patients
                                WHERE centre_id = :centre_id AND status = 'Captive' ORDER BY date_added DESC");
                                $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);

                                // initialise an array for the results
                                $listpatients = array();
                                $stmt->execute();
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                                    $patient_id = $row["patient_id"];
                                    $patient_name = $row["name"];
                                    $patient_species = $row["animal_species"];
									$admission_date = $row["admission_date"];
                                    $patient_added = $row["date_created"];

                                    $patient_added  = new DateTime($patient_added);
                                    $patient_added = $patient_added->format('d/m/Y');

                                    print '<option value="' . $patient_id . '">' . $patient_name . ' (' . $patient_species . ')</option>';
                                }

                                ?>
                            </select>
										
										
										
										</div></div>
									<div class="row lead_form_row">
                                    <div class="col-md-6">
										<p class="angelo_form_label"><label for="alert_message">Enter your alert:</label></p>
                                    	<input type="text" id="alert_message" name="alert_message">
									</div>
									<div class="col-md-6">
										<p class="angelo_form_label"><label for="alert_type">Priority:</label></p>
										<select id="alert_type" name="alert_type">
                                                        <option value="alert-primary"selected="selected">Information</option>
                                                        <option value="alert-warning" >Medium</option> 
														<option value="alert-danger">High</option> 
                                        </select>
									</div>
									</div>

                        <input type="hidden" name="is_active" value="yes">
					    <input type="hidden" name="centre_id" value="<?php echo $centre_id; ?>">
                        <input type="submit" id="submit" name="patient_alerts" value="Add Alert" class="form_submit">
                    
                    </form></div></div></div></div>

<!--- END OF PATIENT ALERT MODAL  ---->