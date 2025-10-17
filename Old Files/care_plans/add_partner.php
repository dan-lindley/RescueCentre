<?php
/*--------------------------- FORM PROCESSINGL - add partnership info----------------------*/

//Check if the notes form was submitted
if (isset($_POST['addpartnerform'])) {

    $add_date = $_POST["date"];
    $add_partner_type = $_POST["partner_type"];
    $add_log_number = $_POST["log_number"];
    $add_log_notes = $_POST["log_notes"];
    $add_crime = $_POST["is_crime"];
	$add_partner_user = $_POST["user_id"];
	$add_partner_centre = $_POST["centre_id"];
    $add_partner_patient = $_POST["patient_id"];
    $add_partner_admission = $_POST["admission_id"];

    try {
        $statement = $conn->prepare('INSERT INTO rescue_partner_log
            (patient_id, 
			centre_id,
			admission_id,
            user_id,
            log_notes,
            is_crime,
			log_number,
			partner_type,
			date)
            
            VALUES (:patient_id, 
			:centre_id,
			:admission_id,
            :user_id,
            :log_notes,
            :is_crime,
			:log_number,
			:partner_type,
			:date)');

        $statement->execute([
            'patient_id' => $add_partner_patient,
			'centre_id' => $add_partner_centre,
			'admission_id' => $add_partner_admission,
            'user_id' => $add_partner_user,
            'log_notes' => $add_log_notes,
            'is_crime' => $add_crime,
            'log_number' => $add_log_number,
            'partner_type' => $add_partner_type,
			'date' => $add_date
        ]);
    } catch (PDOException $e) {
        echo "Database Error: The partner notes could not be added.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The partner notes could not be added.<br>" . $e->getMessage();
        exit();
    }
}

/*------------------------------------------------------------------ END OF FORM PROCESSING -------------------------------------------------------------------*/
?>

<!--- PARTNER MODAL ---- This form is to add partnership notes for the patient -->
                   
		<div class="modal fade" id="partnerModal" tabindex="-1" role="dialog" aria-labelledby="partnerModal" aria-hidden="true">
             <div class="modal-dialog" role="document">
             <div class="modal-content">
             	<div class="modal-header">
             	<h4 class="font-weight-bold text-primary">Add Partnership notes</h4>
             	<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span> </button>
            	</div>
			<div class="modal-body">  
				<b>Patient - <span class="admissionnameDisplay"><?php echo $patient_name ?></span></b> (CRN: <span class="admissionIDDisplay"><?php echo $patient_id ?></span>)
			<form action="" method="post">
					<div class="row lead_form_row"> 

                    <div class="col-md-2 my-auto">  
                     <p class="angelo_form_label">Date and Time</p>
				      <input type="datetime-local" name="date" id="date" placeholder="Date" required>
                    </div>

					<div class="col-md-4 my-auto">
                               <p class="angelo_form_label">Partner/type</p>
                               <select name="partner_type" name="partner_type" id="partner_type" required class="js-example-responsive" style="width: 100%">
                               <option value="" disabled selected>Partner</option>
                                  <?php
                                  //Find sample types
                                  $stmt = $conn->prepare("SELECT * 
                                  FROM rescue_partner_types
                                  ORDER BY partner_type ASC");
                                  // initialise an array for the results
                                  $partner_types = array();
                                  $stmt->execute();
                                  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                
									$partner_type = $row["partner_type"];
                                    $p_type_id = $row["p_type_id"];
									  
                 print '<option value="' . $p_type_id . '">' . $partner_type . '</option>';}?></select>
						</div>


                    <div class="col-md-4 my-auto"> 
                    <p class="angelo_form_label">Log Number</p>
                    <input type="text" placeholder="Log Number" name="log_number" id="log_number">
					</div>

                    <div class="col-md-2 my-auto"> 
                    <p class="angelo_form_label">Is this a crime?</p>
                        <select name="is_crime">
                            <Option value="Yes">Yes</option>
                            <option value="" selected>No</option>
                        </select>
					</div>
				</div>


				<div class="row lead_form_row"> 
                    
					<div class="col-md-6 my-auto">
					<p class="angelo_form_label">Notes:</p>
                    <textarea id="log_notes" name="log_notes" rows="4" cols="50"></textarea>  
					</div>	

				</div>
 							<input type="hidden" name="centre_id" id="centre_id" value="<?php echo $centre_id; ?>">	
                            <input type="hidden" name="patient_id" id="patient_id" value="<?php echo $patient_id; ?>">
                            <input type="hidden" name="user_id" id="user_id" value="<?php $current_user = wp_get_current_user(); print($current_user->id); ?>">
                            <input type="hidden" name="admission_id" id="admission_id" value="<?php echo $currentAdmission_id; ?>">
                            <input type="submit" id="submit" name="addpartnerform" value="Add Partner Log" class="form_submit">
                                    </form>
                                </div>
                                <br />
                            </div>
                        </div>
                    </div>
<script>$('#partner_type').select2({
        dropdownParent: $('#partnerModal')
    });</script>	
<!---------------END of PARTNER modal ----------------------------------------------------------->