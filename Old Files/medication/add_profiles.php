<?php
/*--------------------------------------------------- FORM PROCESSING add_medication-------------------------------------------------------------------*/
//Check if the medicaitons was submitted
if (isset($_POST['add_medication'])) {

    $medication = $_POST["medication"];
    $concentration_dose = $_POST["concentration_dose"];
	$concentration_volume = $_POST["concentration_volume"]; 
	$dose_type = $_POST["dose_type"];
	$volume_type = $_POST["volume_type"];
	$pack_quantity = $_POST["pack_quantity"];
    $reorder_level = $_POST["reorder_level"];
    $use_within = $_POST ["use_within"];
	$for_centre = $_POST["centre_id"];
	$user_added = $_POST["user_id"];

// determine if need to convert the volume
if ($volume_type == 'ml') {
    $vm = '1';
} elseif ($volume_type == 'l') {
    $vm = '1000';
} elseif ($volume_type == 'tablet') {
    $vm = '1';
} 

// We need the dose multiplier (dm), depending on the posted concentration dose type
if ($dose_type == 'mcg') {
    $dm = '0.001';
} elseif ($dose_type == 'mg') {
    $dm = '1';
} elseif ($dose_type == 'g') {
    $dm = '1000';
}
// calculate the mg per single ml for future calculations

$perml =  ($concentration_dose*$dm) / ($concentration_volume*$vm);

    try {
        $statement = $conn->prepare('INSERT INTO rescue_stock_medication
            (medication,
			concentration_dose,
			concentration_volume,
			concentration_dose_type,
			concentration_volume_type,
			pack_quantity,
			reorder_level,
            use_within,
			centre_id,
            mgml,
			user_id
			)
            
            VALUES (:medication,
			:concentration_dose,
			:concentration_volume,
			:concentration_dose_type,
			:concentration_volume_type,
			:pack_quantity,
			:reorder_level,
            :use_within,
			:centre_id,
            :mgml,
			:user_id
			)');

        $statement->execute([

            'medication' => $medication,
			'concentration_dose' => $concentration_dose,
			'concentration_volume' => $concentration_volume,
			'concentration_volume_type' => $volume_type,
			'concentration_dose_type' => $dose_type,
			'pack_quantity' => $pack_quantity,
			'reorder_level' => $reorder_level,
            'use_within' => $use_within,
			'centre_id' => $for_centre,
			'user_id' => $user_added,	
            'mgml' => $perml,		
        ]);
    } catch (PDOException $e) {
        echo "Database Error: The medication could not be added.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The medication could not be added.<br>" . $e->getMessage();
        exit();
    }
}
?>

 <!-- Add Medication modal -->
				   
<div class="modal fade" id="add_medicationModal" tabindex="-1" role="dialog" aria-labelledby="add_medicationModal" aria-hidden="true">
<div class="modal-dialog" role="document">
<div class="modal-content">
<div class="modal-header">
<h4 class="font-weight-bold text-primary">Set up a medication profile</h4>
<button type="button" class="close" data-dismiss="modal" aria-label="Close">
<span aria-hidden="true">&times;</span>
</button>
</div>

<div class="modal-body">
	<form action="" method="post">
    <div class="row lead_form_row"> 
	    <div class="col-md-6 my-auto">
		<p class="angelo_form_label">Medication<br>&nbsp;</p>
            <select name="medication" name="medication" id="medication" required>
                <option value="" disabled selected>Medication</option>
                    <?php
                    //Find medications
                    $stmt = $conn->prepare("SELECT * 
                    FROM rescue_medications
					ORDER BY medication_name ASC");
                    // initialise an array for the results
                    $medications = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                    $medication_id = $row["medication_id"];
					$medication_name = $row["medication_name"];
                    $class = $row["class"];
                    $common_name = $row["common_name"];

                    print '<option value="' . $medication_id . ' ">' . $medication_name . ' (' . $common_name . ')</option>'; } ?> </select>
					
		</div>
 
        <div class="col-md-2">
            <p class="angelo_form_label">Concentration<br>(strength)</p>
            <input type="text" placeholder="eg 125 for 125mg per 10ml/tablet" name="concentration_dose" id="concentration_dose" value="">
		</div>
			<div class="col-md-1">
            <p class="angelo_form_label"><br>&nbsp;</p>
            <select name="dose_type" id="dose_type">
                <option value="mcg">mcg</option>
                <option value="mg">mg</option>
				<option value="g">g</option>
            </select>
		</div>
		<div class="col-md-2">
        <p class="angelo_form_label">Concentration<BR>(volume or unit)</p>
        <input type="text" placeholder="use 1 for a single tablet or 10 for 10ml" name="concentration_volume" id="concentration_volume" value="">
        </div>
						
        <div class="col-md-1">
        <p class="angelo_form_label"><br>&nbsp;</p>
        <select name="volume_type" id="volume_type">
            <option value="ml">ml</option>
            <option value="l">l</option>
			<option value="tablet">tablet</option>
        </select>
		</div>
    </div>
    <div class="row lead_form_row">
		
        <div class="col-md-4">
        <p class="angelo_form_label">Pack size</p>
        <input type="text" placeholder="use volume eg 500ml or quantity eg 28" name="pack_quantity" id="pack_quantity" value="">
        </div> 
							
        <div class="col-md-4 my-auto">
        <p class="angelo_form_label">Reorder level</p>
        <input type="text" placeholder="minimum stock for reordering" name="reorder_level" id="reorder_level" value="">
        </div>	
                            
        <div class="col-md-4 my-auto">
        <p class="angelo_form_label">Use Within</p>
        <input type="text" placeholder="days to use once opened" name="use_within" id="use_within" value="">
        </div>
	</div>
	<div class="row lead_form_row">
        <div class="col-md-3 my-auto">
        <p class="angelo_form_label">Example suspension (Metacam for dogs)</p>
        <img src="https://rescuecentre.org.uk/wp-content/uploads/2024/09/metacam.png" width="50%" height="50%" alt="Metacam for Dogs">
	</div>
		
        <div class="col-md-3">
        <p class="angelo_form_label">&nbsp;</p>
        <P><b>Medication:</b> Meloxicam<br>
			<b>Concentration (strength):</b> 1.5<br>
			<b>Concentration (volume):</b> 1<br>
			<b>Pack Size:</b> 100<br>
			<b>Reorder Level:</b> 50<br>
            <b>Use within:</b> 7 <i>(days)</i><br>
		</P>
        </div>
		
        <div class="col-md-3 my-auto">
        <p class="angelo_form_label">Example tablet (Vetmedin)</p>
        <img src="https://rescuecentre.org.uk/wp-content/uploads/2024/09/vetmedin.png" width="50%" height="50%" alt="Vetmedin">
		</div>
		
        <div class="col-md-3">
        <p class="angelo_form_label">&nbsp;</p>
        <P><b>Medication:</b> Pimobendan<br>
			<b>Concentration (strength):</b> 5<br>
			<b>Concentration (volume):</b> 1<br>
			<b>Pack Size:</b> 50<br>
			<b>Reorder Level:</b> 10<br>
            <b>Use within:</b> 9999 <i>(days)</i><br>
		</P>
		</div>
	</div>
							   
<input type="hidden" name="centre_id" id="centre_id" value="<?php echo $centre_id; ?>">
<input type="hidden" name="user_id" id="user_id" value="<?php $current_user = wp_get_current_user(); print($current_user->id); ?>">

<input type="submit" id="submit" name="add_medication" value="Add Medication Profile" class="form_submit">
                    
 </form></div></div></div></div>

                    <!--- End Of add medicaiton profile modal ---->
