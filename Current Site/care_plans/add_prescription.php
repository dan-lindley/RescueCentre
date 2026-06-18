<?php 
/*---------------- FORM PROCESSING SECTION --------------*/
//Check if the prescription form was submitted
if (isset($_POST['prescriptionform'])) {

    $patient_id= $_POST["patient_id"];
    $centre_id = $_POST["centre_id"];
	$admission_id = $_POST["admission_id"];
	$medication= $_POST["medication"];
    $dose = $_POST["dose"];
	$dose_type = $_POST["dose_type"];
	$duration = $_POST["duration"];
	$frequency= $_POST["frequency"];
    $frequency_id = $_POST["frequency_id"];
	$route = $_POST["route"];
	$date = $_POST["date"];

    try {
        $statement = $conn->prepare('INSERT INTO rescue_prescriptions
            (patient_id, 
			centre_id,
			admission_id,
            medication,
            dose,
			dose_type,
			duration,
			frequency,
			frequency_id,
			route,
            date)
            
            VALUES (:patient_id, 
            :centre_id,
			:admission_id,
            :medication,
            :dose,
			:dose_type,
			:duration,
			:frequency,
			:frequency_id,
			:route,
            :date)');

        $statement->execute([
            'patient_id' => $patient_id,
            'centre_id' => $centre_id,
			'admission_id' => $admission_id,
            'medication' => $medication,
            'dose' => $dose,
			'dose_type' => $dose_type,
			'duration' => $duration,
			'frequency' => $frequency,
			'frequency_id' => $frequency_id,
			'route' => $route,
            'date' => $date
        ]);
    } catch (PDOException $e) {
        echo "Database Error: The prescription could not be added.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The presctiption could not be added.<br>" . $e->getMessage();
        exit();
    }
}
/* -----------   END OF FORM PROCESSING --------------*/
?>


<br>For once per week/fortnight type prescriptions, add a prescription for each day you need to administer the medication<br>
                                               
    <!-- Add prescription Button -->
    <BR><button type="button" class="btn btn-success" data-toggle="modal" data-target="#prescriptionModal"> Add A Prescription</button><br>


<!-- END OF THE PRESCRIPTION SECTION --->
	
	
<!-- MODAL FOR ADDING NEW PRESCRIPTIONS -->
<div class="modal fade" id="prescriptionModal" tabindex="-1" role="dialog" aria-labelledby="prescriptionModal" aria-hidden="true">
<div class="modal-dialog" role="document">	
<div class="modal-content">
	<div class="modal-header">
    <h4 class="font-weight-bold text-primary">Add Prescription</h4>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
    <span aria-hidden="true">&times;</span></button>
</div>
                                
<div class="modal-body">
	<div class="row lead_form_row">
        <div class="col-md-3">
        <form action="" method="post">
		    <p class="angelo_form_label">Date started:</p>
		    <input type="date" name="date" id="date" placeholder="date">
		</div>
									
        <div class="col-md-9">
        <p class="angelo_form_label">Medication</p>
            <select name="medication" name="medication" id="medication" required class="js-example-responsive" style="width: 100%">
            <option value="" disabled selected>Medication</option>
                    <?php
                    //Find medications
                    $stmt = $conn->prepare("SELECT * 
                                            FROM rescue_medications
                                            ORDER BY class ASC");

                    // initialise an array for the results
                    $medications = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $medication_name = $row["medication_name"];
                        $class = $row["class"];
                        $common_name = $row["common_name"];
                        
                        print '<option value="' . $medication_name . '">' . $class . ' - ' . $medication_name . ' (' . $common_name . ')</option>';
                                            } ?>
            </select>
        </div>
    </div>

    <div class="row lead_form_row">
        <div class="col-md-3">
            <p class="angelo_form_label">Route</p>
                <select id="route" name="route">
                    <option value="Subcut">Subcutaneus Injection</option>
                    <option value="IV">Intravenous Injection</option>
                    <option value="Oral">Oral</option>
					<option value="Topical">Topical</option>
                </select>
		</div>
    
        <div class="col-md-2"> 
            <p class="angelo_form_label"><label for="dose">Dose:</label></p>
            <input type="text" id="dose" name="dose" rows="1">
        </div>
        <div class="col-md-1"> 
        <p class="angelo_form_label"><label for="dose type">Dose type</label></p>      
		    <select id="dose_type" name="dose_type">
                <option>mcg</option>
                <option>mg</option>
                <option>g</option>
                <option>ml</option>
                <option>l</option>
                <option>prn</option>
                <option>spray</option>
            </select>
        </div>
                                            
        <div class="col-md-3">
			<p class="angelo_form_label"><label for="duration">Duration (days):</label></p>
            <input type="text" id="duration" name="duration" rows="1" cols="3">
        </div>

        <div class="col-md-3">
        <p class="angelo_form_label"><label for="duration">Frequency</label></p>
            <select name="frequency" name="frequency" id="frequency" required>
                <option value="" disabled selected>Frequency</option>
                    <?php
                    //Find frequencies
                    $stmt = $conn->prepare("SELECT * 
                                            FROM rescue_frequencies
                                            ORDER BY frequency");
                    // initialise an array for the results
                    $frequencies = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $frequency = $row["frequency"];
                                               
                    print '<option value="' . $frequency . '">' . $frequency . '</option>';
                                            } ?>
            </select>
        </div>
    </div>
										
	<input type="hidden" name="user_id" id="user_id" value="<?php $current_user = wp_get_current_user(); print($current_user->id); ?>">
	<input type="hidden" id="admission_id" name="admission_id" value="<?php echo $currentAdmission_id ?>">
	<input type="hidden" id="patient_id" name="patient_id" value="<?php echo $patient_id ?>">
	<input type="hidden" name="centre_id" value="<?php echo $centre_id; ?>">
    <input type="submit" id="submit" name="prescriptionform" value="Add Prescription" class="form_submit">
    </form>

                                <br />
                            </div>
</div> </div></div>
	<script>$('#medication').select2({
        dropdownParent: $('#prescriptionModal')
    });</script>
<!----  END OF PRESCRIPTION MODAL ----->
