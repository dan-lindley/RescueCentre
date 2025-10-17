<?php
/*-------------   FORM PROCESSING TREATMENT   -------------------*/
//Check if the notes form was submitted
if (isset($_POST['treatmentform'])) {

	$patient_id = $_POST["patient_id"];
    $treatment= $_POST["treatment"];
    $done_by = $_POST["done_by"];
	$treatment_free_text = $_POST["treatment_free_text"];

    //Get the current time from the server
    $date = date('Y-m-d H:i:s');

    try {
        $statement = $conn->prepare('INSERT INTO rescue_treatments
            (patient_id, 
            treatment,
            treatment_free_text,
			done_by,
            date)
            
            VALUES (:patient_id, 
            :treatment,
            :treatment_free_text,
			:done_by,
            :date)');

        $statement->execute([
            'patient_id' => $patient_id,
            'treatment' => $treatment,
            'treatment_free_text' => $treatment_free_text,
			'done_by' => $done_by,
            'date' => $date
        ]);
		
		echo "<script>window.location = window.location</script>";
    } catch (PDOException $e) {
        echo "Database Error: The note could not be added.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The note could not be added.<br>" . $e->getMessage();
        exit();
    }
}
/*------ END FORM PROCESSING  ----------*/
?>
				
<!-- treatment notes modal -->
				   
<div class="modal fade" id="treatmentModal" tabindex="-1" role="dialog" aria-labelledby="treatmentModal" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
            <h4 class="font-weight-bold text-primary">Add a treatment</h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span></button>
        </div>

        <div class="modal-body">
		<b>Patient - <span class="admissionnameDisplay"><?php echo $patient_name ?></span></b> (CRN: <span class="admissionIDDisplay"><?php echo $patient_id ?></span>)
        <form action="" method="post">
        <input type="hidden" id="done_by" name="done_by" value="<?php
            $current_user = wp_get_current_user();
            print($current_user->user_firstname); ?>">
				<td><select id="treatment" name="treatment">
                    <option>Heating Pad</option>
                    <option>Food</option>
                    <option>Water</option>
                    <option>IV</option>
                    <option>Subcutaneous Fluids</option>
					<option>Pain relief</option>
					<option>Parasite Removal</option>
					<option>Tick Removal</option>
					<option>Bath</option>
					<option>Incubator</option>
					<option>Maggot Removal</option>
					<option>Flystrike (eggs) Removal</option>
					<option>Topical Treatment</option>
					<option>Over-counter Medication</option>
					<option>Natural Remedy</option>
					<option>Other (use notes to describe</option>
                    </select></td>

                    <p><label for="new_note">Notes:</label></p>
                    <textarea id="treatment_free_text" name="treatment_free_text" rows="4" cols="50"></textarea>
					<input type="hidden" id="patient_id" name="patient_id" value="<?php echo $patient_id;?>">
                    <input type="submit" id="submit" name="treatmentform" value="Add Treatment" class="form_submit">
                    
					</form>
            </div>
        </div>
    </div>
</div>  

 <!--- End Of Notes ---->								
								