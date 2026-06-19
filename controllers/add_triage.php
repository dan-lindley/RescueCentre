<?php 
/*----- GET data from database to populate the form fields -----*/
$sql = 'SELECT * FROM rescue_admissions WHERE patient_id=:patient_id LIMIT 1';
$statement = $conn->prepare($sql);
$statement->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
/*---------------------------------------------------------------------------------*/
if ($result) {
    $hpc = $result["hpc"];
    $oe = $result["on_examination"];
} else {
    echo "Admission not found";
    exit();
}

/*----------------------- FORM PROCESSING ADD TRIAGE SCORE-------------------*/
//Check if the notes form was submitted
if (isset($_POST['triagescoreform'])) {

	


//Get the current time from the server
  

    try {
        $statement = $conn->prepare('INSERT INTO rescue_triages
            (patient_id, 
            centre_id,
            admission_id,
			recorded_by,
            bcs,
            ss,
            class,
            age,
            triage_date)
            
            VALUES (:patient_id, 
            :centre_id,
            :admission_id,
			:recorded_by,
            :bcs,
            :ss,
            :class,
            :age,
            :triage_date)');

        $statement->execute([
            'patient_id' => $triage_patient,
            'centre_id' => $triage_centre,
            'admission_id' => $triage_admission,
			'recorded_by' => $triage_user,
            'bcs' => $triage_bcs,
            'ss' => $triage_ss,
            'class' => $triage_class,
            'age' =>  $triage_age,
            'triage_date' => $date
        ]);
		
		  echo "<script>window.location = window.location</script>";
		
    } catch (PDOException $e) {
        echo "Database Error: The triage could not be added.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The triage could not be added.<br>" . $e->getMessage();
        exit();
    }
}
/*------------ END FORM ----------------*/
?>
<!-- ADD TRIAGE MODAL -->
				   
<div class="modal fade" id="triageModal" tabindex="-1" role="dialog" aria-labelledby="triageModal" aria-hidden="true">
<div class="modal-dialog" role="document">
<div class="modal-content">
    <div class="modal-header">
        <h4 class="font-weight-bold text-primary">Triage</h4> 
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
                                
    <div class="modal-body">
		<b>Triage Patient - <span class="admissionnameDisplay"><?php echo $patient_name ?></span></b> (CRN: <span class="admissionIDDisplay"><?php echo $patient_id ?></span>)<span class="triagescore"></span>
        <form action="" method="post">

        <div class="row lead_form_row">
        <div class="col-md-4">
            <p class="angelo_form_label">Presenting Complaint</p>
            <select id="presenting_complaint" name="presenting_complaint">
                <optgroup label="Attacked">
                    <option value="Attacked - Cat" selected>Attacked - Cat</option>
					<option value="Attacked - Dog">Attacked - Dog</option>
					<option value="Attacked - Badger">Attacked - Badger</option>
					<option value="Attacked - Other">Attacked - Other predator</option>
				<optgroup label="Injured">
                    <option value="Injured - Unknown">Injured - Unknown</option>
					<option value="Injured - Vehicle">Injured - Vehicle</option>
					<option value="Injured - Garden tool">Injured - Garden Tool</option>
					<option value="Injured - Glue trap">Injured - Glue Trap</option>	
				<optgroup label="Medical">
                    <option value="Heavy Parasite Load">Heavy parasite load</option>
					<option value="Disease">Disease</option>
					<option value="Neurological Symptoms">Neurological Symptoms</option>
					<option value="Emaciated">Emaciated</option>
					<option value="Myiasis">Myiasis</option>		
				<optgroup label="Behaviour">
                    <option value="Lost Baby/Juvenile">Lost baby/juvenile</option>
                    <option value="Grounded/Laying passively">Grounded/laying passively</option>
					<option value="Out during day">Out during day</option>
					<option value="Running around in circles">Running in circles</option>	
				<optgroup label="Other">
                    <option value="Other">Other</option>
                    <option value="Captured for Research">Captured for Research</option>	
            </select>
        </div>

        <div class="col-md-4 my-auto">
        <p class="angelo_form_label">Severity Score</p>
            <select name="ss_text" id="ss_text" required class="js-example-responsive" style="width: 100%">
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
                    $ss_description = $row["ss_description"];
                    
                    print '<option value="' . $ss_category . '">' . $ss_description . ' (' . $ss_category . ')</option>';
                                            } ?>
            </select>
        </div>
        <div class="col-md-4 my-auto">
        <p class="angelo_form_label">Body condition Score</p>
                <select id="bcs_text" name="bcs_text">
                    <option value="BCS 1 Skeletal">1 - Emaciated/Skeletal</option>
                    <option value="BCS 2 Underweight">2 - Underweight</option>
                    <option value="BCS 3 Slightly Underweight">3- Slightly Underweight</option>
                    <option value="BCS 4 Healthy">4 - Healthy</option>
                    <option value="BCS 5 Overweight">5 - Overweight</option>
                </select>
        </div>
    </div>

    <div class="row lead_form_row">
        <div class="col-md-12">
        <p class="angelo_form_label">History of Presenting Complaint</p>
        <textarea id="hpc" name="hpc" rows="2" placeholder="Here you can describe the situation why the animal came to you"><?php echo htmlspecialchars($hpc); ?></textarea>
        </div>
    </div>

    <div class="row lead_form_row">
        <div class="col-md-12">
        <p class="angelo_form_label">On examination</p>
        <textarea id="on_examination" name="on-examination" rows="8" placeholder="Here you can write your clinical assessment of the patient including injuries"><?php echo htmlspecialchars($oe); ?></textarea>
        </div>
    </div>   


<input type="hidden" id="recorded_by" name="recorded_by" value="<?php $current_user = wp_get_current_user(); print($current_user->user_firstname); ?>">                        
<input type="hidden" id="admission_id" name="admission_id" value="<?php echo $admission_id;?>">
<input type="hidden" id="patient_id" name="patient_id" value="<?php echo $patient_id;?>">
<input type="hidden" id="centre_id" name="centre_id" value="<?php echo $centre_id;?>">
<input type="submit" id="submit" name="triagescoreform" value="Add Triage Scoring" class="form_submit">                  
</form></div></div></div></div>

<!--- END OF TRIAGE MODAL  ---->
<script>$('#severity_score').select2({
        dropdownParent: $('#triageModal')
    });</script>