<?php

$current_user_id = get_current_user_id();
/*-------------   FORM PROCESSING OBSERVATIONS   -------------------*/
//Check if the form was submitted
if (isset($_POST['observationform'])) {

	$patient_id = $_POST["patient_id"];
    $admission_id = $_POST["admission_id"];
    $obs_user_id = $_POST["obs_user_id"];
	$obs_sev_text = $_POST["obs_sev_text"];
	$obs_bcs_text = $_POST["obs_bcs_text"];
	$obs_age_text = $_POST["obs_age_text"];
    $obs_notes = $_POST["obs_notes"];

    //Get the current time from the server
    $date = date('Y-m-d H:i:s');

    // figure out the age score from the posted age
if ($obs_age_text == 'Newborn') {
    $obs_age_sc = '3';
} elseif ($obs_age_text == 'Dependent Juvenile') {
    $obs_age_sc = '2';
} elseif ($obs_age_text == 'Independent Juvenile') {
    $obs_age_sc = '1';
} elseif ($obs_age_text == 'Hatchling') {
    $obs_age_sc = '3';
} elseif ($obs_age_text == 'Fledgling') {
    $obs_age_sc = '2';
} elseif ($obs_age_text == 'Adult') {
    $obs_age_sc = '0';
}

// figure out the severity score from the severity 
if ($obs_sev_text == 'Apparently Healthy') {
    $obs_sev_sc = '0';
} elseif ($obs_sev_text == 'Mildly unwell') {
    $obs_sev_sc = '0';
} elseif ($obs_sev_text == 'Obvious Injuries') {
    $obs_sev_sc = '1';
} elseif ($obs_sev_text == 'Severe Injuries') {
    $obs_sev_sc = '2';
} elseif ($obs_sev_text == 'Near Death') {
    $obs_sev_sc = '3';
}

// figure out the body score from posted 
if ($obs_bcs_text == 'BCS 1 Skeletal') {
    $obs_bcs_sc = '3';
} elseif ($obs_bcs_text == 'BCS 2 Underweight') {
    $obs_bcs_sc= '2';
} elseif ($obs_bcs_text == 'BCS 3 Slightly Underweight') {
    $obs_bcs_sc = '1';
} elseif ($obs_bcs_text == 'BCS 4 Healthy') {
    $obs_bcs_sc = '0';
} elseif ($obs_bcs_text == 'BCS 5 Overweight') {
    $obs_bcs_sc = '0';
}

    try {
        $statement = $conn->prepare('INSERT INTO rescue_observations
            (patient_id, 
            admission_id,
            user_id,
            obs_severity_score,
            obs_severity_text,
            obs_bcs_score,
            obs_bcs_text,
            obs_age_score,
			obs_age_text,
            obs_notes,
            obs_date)
            
            VALUES (:patient_id, 
            :admission_id,
            :user_id,
            :obs_severity_score,
            :obs_severity_text,
            :obs_bcs_score,
            :obs_bcs_text,
            :obs_age_score,
            :obs_age_text,
            :obs_notes,
            :obs_date)');

        $statement->execute([
            'patient_id' => $patient_id,
            'admission_id' => $admission_id,
            'user_id' => $obs_user_id,
			'obs_severity_score' => $obs_sev_sc,
            'obs_severity_text' => $obs_sev_text,
            'obs_bcs_score' => $obs_bcs_sc,
            'obs_bcs_text' => $obs_bcs_text,
            'obs_age_score' => $obs_age_sc,
            'obs_age_text' => $obs_age_text,
            'obs_notes' => $obs_notes,
            'obs_date' => $date
        ]);
		
		echo "<script>window.location = window.location</script>";
    } catch (PDOException $e) {
        echo "Database Error: The observation could not be added.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The observation could not be added.<br>" . $e->getMessage();
        exit();
    }
}
/*------ END FORM PROCESSING  ----------*/
?>
				
<!-- Observations modal -->
				   
<div class="modal fade" id="observationsModal" tabindex="-1" role="dialog" aria-labelledby="observationsModal" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="font-weight-bold text-primary">Add an observation</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span></button>
             </div>

<div class="modal-body">
	<b>Patient - <span class="admissionnameDisplay"><?php echo $patient_name ?></span></b> (CRN: <span class="admissionIDDisplay"><?php echo $patient_id ?></span>)
    <form action="" method="post">
    <input type="hidden" id="obs_user_id" name="obs_user_id" value="<?php echo $current_user_id; ?>">
		
    <div class="row lead_form_row">		
        <div class="col-md-4 my-auto">
            <p class="angelo_form_label">Current Age</p>
            <select name="obs_age_text" id="obs_age_text">
		        <optgroup label="Mammals">
                <option value="Newborn" selected>Newborn</option>
			    <option value="Dependent Juvenile">Dependent Juvenile</option>
			    <option value="Independent Juvenile">Independent Juvenile</option>
			    <option value="Adult">Adult</option>
			    <optgroup label="Birds">
                <option value="Hatchling">Hatchling</option>
                <option value="Fledgling">Fledgling</option>
                <option value="Adult">Adult</option>
            </select>
        </div>
        <div class="col-md-4 my-auto">
            <p class="angelo_form_label">Current Severity Score</p>
            <select name="obs_sev_text" id="obs_sev_text" required class="js-example-responsive" style="width: 100%">
            <option value="" disabled selected>Injury Severity Score</option>
                <?php
                    //Find severity scores
                    $stmt = $conn->prepare("SELECT * 
                                            FROM rescue_severity_score
                                            ORDER BY ss_id ASC");

                    // initialise an array for the results
                    $severity = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $ss_category = $row["ss_category"];
                    $ss_description = $row["ss_incare_desc"];
                    
                    print '<option value="' . $ss_category . '">' . $ss_description . ' (' . $ss_category . ')</option>';
                                            } ?>
            </select>
        </div>
        <div class="col-md-4 my-auto">
        <p class="angelo_form_label">Body condition Score</p>
                <select id="obs_bcs_text" name="obs_bcs_text">
                    <option value="BCS 1 Skeletal">1 - Emaciated/Skeletal</option>
                    <option value="BCS 2 Underweight">2 - Underweight</option>
                    <option value="BCS 3 Slightly Underweight">3 - Slightly Underweight</option>
                    <option value="BCS 4 Healthy">4 - Healthy</option>
                    <option value="BCS 5 Overweight">5 - Overweight</option>
                </select>
        </div>
    </div>

    <div class="row lead_form_row">		
        <div class="col-md-6 my-auto">
            <p><label for="note">Notes:</label></p>
            <textarea id="obs_notes" name="obs_notes" rows="4" cols="50"></textarea>
		    <input type="hidden" id="patient_id" name="patient_id" value="<?php echo $patient_id;?>">
            <input type="hidden" id="admission_id" name="admission_id" value="<?php echo $admission_id;?>">
            <input type="submit" id="submit" name="observationform" value="Add Observation" class="form_submit">
		    </form>
        </div>
    </div>
</div>
        </div>
    </div>  
</div>
 <!--- End Of observations ---->								
								