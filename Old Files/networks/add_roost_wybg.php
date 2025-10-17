<?php
/*--------------------------- FORM PROCESSING roosts-----------------------*/
/* tbl: rescue_roost_record. FIELDS: record_id (pk), centre_id,  network_id    */
/* os_grid, address, town, postcode, date, recorded_by, confirmed_by, species  */
/* number_age, indoor_outdoor, record_type                                     */

//Check if the notes form was submitted
if (isset($_POST['addroostform'])) {

    $roost_centre_id = $_POST["centre_id"];
    $roost_network_id = $_POST["network_id"];
    $roost_os_grid = $_POST["os_grid"];
    $roost_address = $_POST["address"];
	$roost_town = $_POST["town"];
	$roost_postcode = $_POST["postcode"];
    $roost_date = $_POST["date"];
    $roost_recorded_by = $_POST["recorded_by"];
    $roost_confirmed_by = $_POST["confirmed_by"];
    $roost_species = $_POST["address"];
	$roost_town = $_POST["town"];
	$roost_postcode = $_POST["postcode"];
    $roost_date = $_POST["date"];
   

    try {
        $statement = $conn->prepare('INSERT INTO rescue_labs
            (patient_id, 
			centre_id,
			admission_id,
            lab_date,
            sample_type,
			lab_result,
			reported_by,
			lab_test)
            
            VALUES (:patient_id, 
			:centre_id,
			:admission_id,
            :lab_date,
            :sample_type,
			:lab_result,
			:reported_by,
			:lab_test)');

        $statement->execute([
            'patient_id' => $add_lab_patient_id,
			'centre_id' => $add_lab_centre_id,
			'admission_id' => $add_lab_admission_id,
            'lab_date' => $add_lab_date,
            'sample_type' => $add_sample_type,
            'lab_result' => $add_lab_result,
            'reported_by' => $add_reported_by,
			'lab_test' => $add_lab_test
        ]);
    } catch (PDOException $e) {
        echo "Database Error: The lab test could not be added.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The lab test could not be added.<br>" . $e->getMessage();
        exit();
    }
}

/*------------------------------------------------------------------ END OF FORM PROCESSING -------------------------------------------------------------------*/
?>

<!--- MEDICATION MODAL ---- This form is for when meds are given to patient -->
                   
		<div class="modal fade" id="labsModal" tabindex="-1" role="dialog" aria-labelledby="labsModal" aria-hidden="true">
             <div class="modal-dialog" role="document">
             <div class="modal-content">
             	<div class="modal-header">
             	<h4 class="font-weight-bold text-primary">Add a lab result</h4>
             	<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span> </button>
            	</div>
			<div class="modal-body">  
				<b>Patient - <span class="admissionnameDisplay"><?php echo $patient_name ?></span></b> (CRN: <span class="admissionIDDisplay"><?php echo $patient_id ?></span>)
			<form action="" method="post">
					<div class="row lead_form_row"> 

                    <div class="col-md-2 my-auto">  
                     <p class="angelo_form_label">Date and Time</p>
				      <input type="datetime-local" name="lab_date" id="dlab_ate" placeholder="Result Date" required>
                    </div>

					<div class="col-md-4 my-auto">
                               <p class="angelo_form_label">Sample Type</p>
                               <select name="sample_type" name="sample_type" id="sample_type" required>
                               <option value="" disabled selected>Sample Type</option>
                                  <?php
                                  //Find sample types
                                  $stmt = $conn->prepare("SELECT * 
                                  FROM rescue_sample_types
                                  ORDER BY sample_type ASC");
                                  // initialise an array for the results
                                  $sample_types = array();
                                  $stmt->execute();
                                  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                
									$sample_type = $row["sample_type"];
                                    $sample_type_id = $row["s_type_id"];
									  
                 print '<option value="' . $sample_type_id . '">' . $sample_type . '</option>';}?></select>
						</div>

						<div class="col-md-4 my-auto">
								<p class="angelo_form_label">Lab Test:</p>
                                <select name="lab_test" name="lab_test" id="lab_test" required class="js-example-responsive" style="width: 100%">
                               <option value="" disabled selected>Test</option>
                                  <?php
                                  //Find sample types
                                  $stmt = $conn->prepare("SELECT * 
                                  FROM rescue_labs_tests
                                  ORDER BY lab_test ASC");
                                  // initialise an array for the results
                                  $labs_tests = array();
                                  $stmt->execute();
                                  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                
									$lab_test = $row["lab_test"];
                                    $lab_cat = $row["lab_category"];
                                    $lab_test_id = $row["l_test_id"];
									  
                 print '<option value="' . $lab_test_id . '">' . $lab_test . ' (' . $lab_cat . ')</option>'; }?></select>
						</div>

                        <div class="col-md-2 my-auto"> 
                    <p class="angelo_form_label">Result</p>
                    <input type="text" placeholder="Result" name="lab_result" id="lab_result">
					</div>
				</div>


				<div class="row lead_form_row"> 
                    
					<div class="col-md-6 my-auto">
					<p class="angelo_form_label">Reported By</p>
                    <input type="text" value="<?php $given_by_name = get_userdata(get_current_user_id());
							$given_first_name = $given_by_name->first_name; 
							$given_last_name = $given_by_name->last_name;
							$given_fullname = "" . $given_first_name . " " . $given_last_name . ""; 
																					  print $given_fullname; ?>" name="reported_by" id="reported_by">	    
					</div>	

				</div>
 							<input type="hidden" name="centre_id" id="centre_id" value="<?php echo $centre_id; ?>">	
                            <input type="hidden" name="patient_id" id="patient_id" value="<?php echo $patient_id; ?>">
                            <input type="hidden" name="admission_id" id="admission_id" value="<?php echo $admission_id; ?>">
                            <input type="submit" id="submit" name="addlabsform" value="Add Lab Result" class="form_submit">
                                    </form>
                                </div>
                                <br />
                            </div>
                        </div>
                    </div>
<script>$('#lab_test').select2({
        dropdownParent: $('#labsModal')
    });</script>	
<!---------------END of medication modal ----------------------------------------------------------->