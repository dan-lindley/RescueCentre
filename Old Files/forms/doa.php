<?php 

//Get the information from the database
$sql = 'SELECT * FROM rescue_patients WHERE patient_id=:patient_id LIMIT 1';
$statement = $conn->prepare($sql);
$statement->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
/*---------------------------------------------------------------------------------*/
if ($result) {
    $patient_name = $result["name"];
    $patient_ringed = $result["ringed"];
    $patient_ring_number = $result["ring_number"];
    $patient_microchipped = $result["microchipped"];
    $patient_microchip_number = $result["microchip_number"];
    $patient_animal_type = $result["animal_type"];
    $patient_animal_order = $result["animal_order"];
    $patient_animal_species = $result["animal_species"];
    $patient_sex = $result["sex"];
    $patient_status = $result["status"];
    $date_added = $result["date_added"];

    $formatted_date = new DateTime($date_added);
    $formatted_date = $formatted_date->format('d-m-Y H:i');
} else {
    echo "Error 2";
    exit();
}

?>

	
<!-- DATA ENTRY FORM -->
		
<!-- Add A New admision header -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 text-primary">Deceased/Euthanised on Arrival Form - <strong><?php echo $patient_name; ?></strong> - <?php echo $patient_animal_species; ?></h6>
        	<div id="alertMsg"><?php echo $alertMsg; ?></div>
    </div>
    <div class="card-body">
    <P>This form will create a closed record for a casualty that has been attended to that has either died or has
        been euthanised. 
        <br>It will not be added to "My Patients" but can still be accessed from the "Patient Archive"
    </div>
	  
<div class="card-header" id="Admission">
<h4 class="mb-0">Animal Details</h4>
</div>
    <div class="card-body">
    <form action="https://rescuecentre.org.uk/wp-content/themes/brikk-child/ajax/insert_doa.php" method="post" class="lead_form" id="manualForm"  onSubmit="window.location.reload()">
                    
        <div class="row lead_form_row">  
      

            <div class="col-md-6">
            <p class="angelo_form_label">Disposition</p>
                <select id="disposition" name="disposition">
                    <option value="Died - Euthanised">Died - Euthanised</option>
                    <option value="Died - after 48 hours">Died - after 48 hours</option>
                    <option value="Died - within 48 hours">Died - within 48 hours</option>
                    <option value="Died - on admission">Died - on admission or arrival</option>
                </select>
            </div>
			
            <div class="col-md-6">	
                <p class="angelo_form_label">Euthanasia Method</p>
                <select id="euthanasia_method" name="euthanasia_method">
                    <option value="Not Applicable" selected>Not applicable</option>
                    <option value="Pharmacological - Vet">Pharmacological - Vet</option>
                    <option value="Pharmacological - Centre">Pharmacological - Centre</option>
					<option value="Manual">Manual</option>
                    <option value="Captive Bolt">Captive Bolt</option>
                    <option value="Shot">Shot</option>
                    <option value="Other">Other</option>
                </select>
            </div>
        
        </div>     
		
        <div class="row lead_form_row">
            <div class="col-md-4">
              <p class="angelo_form_label">Age</p>
            <select name="age_on_admission" id="age_on_admission">
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
                <p class="angelo_form_label">Dehydrated?</p>
                <select id="dehydrated" name="dehydrated">
                    <option value="Yes">Yes</option>
                    <option value="No" selected>No</option>
                </select>
            </div>

            <div class="col-md-4 my-auto">
                <p class="angelo_form_label">Starved?</p>
                <select id="starved" name="starved">
                    <option value="Yes">Yes</option>
                    <option value="No" selected>No</option>
                </select>
            </div>
        </div>


</div>

   
<div class="card">
    <div class="card-header" id="Collection">
        <h4 class="mb-0"> Collection Information</h4>
    </div>
    
<div class="card-body">
	<div class="row lead_form_row">
          <div class="col-md-4">
                <p class="angelo_form_label">Date and Time Found</p>
				<input type="datetime-local" name="admission_date" id="admission_date" placeholder="date" required>
            </div> 
        <div class="col-md-4">
            <p class="angelo_form_label">Who found this animal?</p>
            <input type="text" placeholder="Name of person who found animal" name="finder_name" id="finder_name">
        </div>
        <div class="col-md-4 my-auto">
            <p class="angelo_form_label">Contact Number</p>
            <input type="text" placeholder="Contact Number in case of need to contact" name="finder_tel" id="finder_tel">
		</div>
    </div>
    
    <div class="row lead_form_row">
        <div class="col-md-4 my-auto">
            <p class="angelo_form_label">Where was the animal collected?</p>
            <input type="text" placeholder="Collection Location (postcode)" name="location" id="location" aria-describedby="postcodehelp" required>
            <BR>
            <small id="postcodehelp" class="form-text text-muted">
            Please use a postcode (if applicable)</small>
		</div>						
        <div class="col-md-4">
            <p class="angelo_form_label">Latitude</p>
            <input type="text" placeholder="Latitude" name="location_lat" id="location_lat">
        </div>
        <div class="col-md-4">
            <p class="angelo_form_label">Longitude</p>
            <input type="text" placeholder="Longitude" name="location_long" id="location_long">
        </div>
	</div> 	  
	
		
</div>
</div>


<div class="card">
    <div class="card-header" id="headingThree">
        <h4 class="mb-0">Patient biometrics</h4>
    </div>
    
<div class="card-body">
    

        <div class="row lead_form_row">
		<div class="col-md-12 my-auto"><p><u>Animal admission weight and measurements</u><br>If you would rather do this later, just insert 0 for the values but ensure you select the units you will use</p></div>
						
		    <div class="col-md-4 my-auto">
                <p class="angelo_form_label">Animal Weight (on admission)</p>
                <input type="text" placeholder="Animal weight" name="weight" id="weight" value="0" required>
                </div>
            <div class="col-md-2 my-auto">
                <p class="angelo_form_label">Weight Unit</p>
                <select id="weight_unit" name="weight_unit">
                    <option value="g">Grams</option>
                    <option value="kg">Kilograms</option>
                    <option value="lbs">Pounds</option>
                </select>
            </div>

            <div class="col-md-4 my-auto">
                <p class="angelo_form_label">Animal Measurement (on admission)</p>
				<input type="text" placeholder="Animal measurement" name="measurement" id="measurement"  value="0" required>
            </div>

            <div class="col-md-2 my-auto">
                <p class="angelo_form_label">Measurement Unit</p>
                <select id="measurement_unit" name="measurement_unit">
                    <option value="mm">Millimeters</option>
                    <option value="cm">Centimeters</option>
                    <option value="m">Meters</option>
                    <option value="in">Inches</option>
                    <option value="ft">Feet</option>
                </select>
            </div>
        </div>

</div>
</div>
		
<div class="card">
    <div class="card-header" id="triageheading">
        <h4 class="mb-0">Triage and Assessment</h4>
    </div>
    
<div class="card-body">
	<div class="row lead_form_row">
        <div class="col-md-6 my-auto">
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
        <div class="col-md-6 my-auto">
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
    </div>
    <div class="row lead_form_row">
        <div class="col-md-12">
        <p class="angelo_form_label">History of Presenting Complaint</p>
        <textarea id="hpc" name="hpc" rows="2" placeholder="This is to describe the events by which the animal was found"></textarea>
        </div>
    </div>

    <div class="row lead_form_row">
        <div class="col-md-12">
        <p class="angelo_form_label">On examination</p>
        <textarea id="on_examination" name="on_examination" rows="8" placeholder="Here you can write your clinical assessment of the patient including injuries"></textarea>
        </div>
    </div>
</div>
</div>

<div class="card">
    <div class="card-header" id="headingFour">
      <h4 class="mb-0">Weather Data (optional)</h4>
    </div>
      <div class="card-body">
        <P>
					 It is reccommended that for the weather data, you use the weather at the place of collection
				</P>
				    <div class="row lead_form_row">
                        <div class="col-md-2 my-auto">
                            <p class="angelo_form_label">Temp (degrees c)</p>
                            <input type="text" placeholder="Temperature (degrees c)" name="w_temp" id="w_temp">
                        </div>

                        <div class="col-md-2">
                            <p class="angelo_form_label">Wind Speed (mph)</p>
                            <input type="text" placeholder="Wind Speed (mph)" name="w_wind" id="w_wind">
                        </div>

                        <div class="col-md-2 my-auto">
                            <p class="angelo_form_label">Humidity (%)</p>
                            <input type="text" placeholder="Humidity (%)" name="w_humidity" id="w_humidity">
                        </div>

                        <div class="col-md-6">
                            <p class="angelo_form_label">Free Text</p>
                            <input type="text" placeholder="You can use this space to describe any weather abnormalities" name="w_freetext" id="w_freetext">
                        </div>
                    </div> 
			</div>
  
<div class="card-body">
    <input type="hidden" name="status" value="Closed"> 
    <input type="hidden" name="consent_to_update" value="0"> 
    <input type="hidden" name="current_location" value=""> 
    <input type="hidden" name="the_patient" value="<?php echo $patient_id; ?>"> 
	<input type="hidden" name="thestaffid" value="<?php echo $current_user_id; ?>"> 
    <input type="hidden" name="centre_id" value="<?php echo $centre_id; ?>"> <!-- to change when record is transferred permanantly -->
	<input type="hidden" name="owner_id" value="<?php echo $current_user_id; ?>"> <!-- used for legacy tracking of record - original user -->
	<input type="submit" name="form1" value="Add to records">
    <div id="alertMsg2"><?php echo $alertMsg; ?></div>
                </form>
		</div>	</div>	</div>
		
		
	<!-- end of euthanised or DOA form -->	
		
		

		
    <script>
    
    $('#ss_text').select2({
        dropdownParent: $('#headingThree')
    });
    
    $("#postcodelookup").select2({
  tags: true
    });
    
    </script>		
		
		
		
		
		
		
		
		

<script>
//AJAX Scripts
//Insert Admission AJAX
    $(document).ready(function() {
        $('#manualForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                type: 'post',
                url: 'https://rescuecentre.org.uk/wp-content/themes/brikk-child/ajax/insert_doa.php',
                data: $('#manualForm').serialize(),
                success: function() {

                    document.getElementById("alertMsg").innerHTML = '<div class="alert alert-success" role="alert">Your DOA or euthanised patient was added to the database.</div>';
                    document.getElementById("alertMsg2").innerHTML = '<div class="alert alert-success" role="alert">Your DOA or euthanised patient was added to the database.</div>';
					document.getElementById("manualForm").reset();
                    window.location = "https://rescuecentre.org.uk/patients/";

                }
            });
        });
    });
</script>
