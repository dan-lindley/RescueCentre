<?php 
/*--------------------------- FORM PROCESSING Medication -----------------------*/
//Check if the notes form was submitted
if (isset($_POST['medicationform'])) {

    $medication_given = $_POST["medication_given"];
    $dose = $_POST["dose"];
    $dose_type = $_POST["dose_type"];
    $given_by = $_POST["given_by"];
	$stock_item_used = $_POST["stock_item_used"];
	$med_centre_id = $_POST["centre_id"];
    $bn_given = $_POST["bn_given"];
    $exp_given = $_POST["exp_given"];
	$given_by_id = $_POST["given_by_id"];
    $given_vol = $_POST["volume_used"];
    $date_given = $_POST["date_given"];

    try {

        $statement = $conn->prepare('INSERT INTO rescue_medications_given
            (patient_id, 
			centre_id,
			given_by_id,
            medication_given,
            given_by,
			dose,
			dose_type,
			stock_item_used,
            batch_given,
            exp_given,
            vol_given,
            date)
            
            VALUES (:patient_id, 
			:centre_id,
			:given_by_id,
            :medication_given,
            :given_by,
			:dose,
			:dose_type,
			:stock_item_used,
            :batch_given,
            :exp_given,
            :vol_given,
            :date)');

         $statement->execute([
            'patient_id' => $patient_id,
			'centre_id' => $med_centre_id,
			'given_by_id' => $given_by_id,
            'medication_given' => $medication_given,
            'given_by' => $given_by,
            'dose' => $dose,
            'dose_type' => $dose_type,
            'batch_given' => $bn_given,
            'exp_given' => $exp_given,
            'vol_given' => $given_vol,
			'stock_item_used' => $stock_item_used,
            'date' => $date_given]);        
            
        
    // Update stock balance
    $query2 = "UPDATE rescue_medication_trans 
        SET 
        rescue_medication_trans.med_trans_id = :stock_item_used,
        rescue_medication_trans.est_volume = rescue_medication_trans.est_volume - :given_vol 
        WHERE rescue_medication_trans.med_trans_id = :stock_item_used"; 
        $stmt2 = $conn->prepare($query2); 
        $stmt2->execute(['stock_item_used' => $stock_item_used, 'given_vol' => $given_vol]);
        //echo "Rows updated by the UPDATE statement: ".$stmt2->rowCount().PHP_EOL; */
    
    
    } catch (PDOException $e) {
        echo "Database Error: The medication could not be added.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The medication could not be added.<br>" . $e->getMessage();
        exit();
    }
}

/*------------------------------------------------------------------ END OF FORM PROCESSING -------------------------------------------------------------------*/
?>



<!--- MEDICATION MODAL ---- This form is for when meds are given to patient -->
                   
<div class="modal fade" id="medicationModal" tabindex="-1" role="dialog" aria-labelledby="medicationModal" aria-hidden="true">
<div class="modal-dialog" role="document">
<div class="modal-content">
<div class="modal-header">
<h4 class="font-weight-bold text-primary">Add Medication</h4>
<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span> </button>
</div>

<div class="modal-body">  
<b>Patient - <span class="admissionnameDisplay"><?php echo $patient_name ?></span></b> (CRN: <span class="admissionIDDisplay"><?php echo $patient_id ?></span>)

<!-- column for FORM-->

<div class="row">
    <div class="col-sm-9">  <form action="" method="post">
        <div class="row lead_form_row"> 
	<div class="col-md-4 my-auto">
    <p class="angelo_form_label">Medication Given</p>
    <select name="medication_given" name="medication_given" id="medication_given" required class="js-example-responsive" style="width: 100%">
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
									  
    print '<option value="' . $medication_name . '">' . $class . ' - ' . $medication_name . ' (' . $common_name . ')</option>';}?>
    </select>
	</div>
	
    <div class="col-md-2 my-auto">
		<p class="angelo_form_label">Dose:</p>
		<input type="text" id="dose" name="dose" rows="1" cols="5">
	</div>
						
    <div class="col-md-2 my-auto">
		<p class="angelo_form_label">&nbsp;</p>
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
        <div class="col-md-2">
                <p class="angelo_form_label">Given on</p>
                <input type="datetime-local" name="date_given" id="date_given" placeholder="date" required>
        </div>	

    </div>
	
    <div class="row lead_form_row"> 	
				<div class="col-md-5 my-auto">
				<p class="angelo_form_label">Use from stock</p>
                  <select name="stock_item_used" name="stock_item_used" id="stock_item_used">
                    <option value="" disabled selected>Medication</option>
                      <?php
                                //Find medications
                                $stmt = $conn->prepare("SELECT * from rescue_medication_trans
								LEFT JOIN rescue_medications ON rescue_medication_trans.med_profile_id = rescue_medications.medication_id
                                LEFT JOIN rescue_stock_medication ON rescue_stock_medication.medication = rescue_medication_trans.med_profile_id
								WHERE rescue_medication_trans.centre_id = :centre_id
								ORDER BY common_name ASC");
								$stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);		
                                // initialise an array for the results
                                $instock = array();
                                $stmt->execute();
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                                $instk_id = $row["med_trans_id"];
								$instk_bn= $row["batch_number"];
								$instk_exp = $row["expiry"];
								$instk_name = $row["common_name"]; 
                                $mcd = $row["concentration_dose"];
                                $mcv = $row["concentration_volume"];
												
                print '<option value="' . $instk_id . '" data-exp="' . $instk_exp . '" data-batch=" ' . $instk_bn . '" data-dose="' . $mcd . '" data-volume="' . $mcv . '">' . $instk_name . ' - ' . $mcd . '<i>' . $dose_type . '</i>  in ' . $mcv . '<i>' . $volume_type. '</i> (' . $instk_bn . ' exp: ' . $instk_exp . ') </option>'; } ?> </select>						    
			</div>
            <div class="col-md-2 my-auto">
            <p class="angelo_form_label">Volume</p>
            <input type="text" id="volume_used" name="volume_used" placeholder="Volume used">
            </div>
            <div class="col-md-3 my-auto">
            <p class="angelo_form_label">Batch Number</p>
            <input type="text" id="bn_given" name="bn_given" placeholder="Batch Number">
            </div>
            <div class="col-md-2 my-auto">
            <p class="angelo_form_label">Expiry Date</p>
            <input type="date" id="exp_given" name="exp_given" placeholder="Expiry">
            </div>
           </div>
						
            <div class="row">
            <div class="col-sm-12 my-auto"> 
								<div class="alert alert-primary" role="alert">
 								 Check Batch Code and Expiry to ensure you are selecting the correct medication!</div> 
						</div>
				</div>
                    <input type="hidden" name="patient_id" id="patient_id" value="<?php echo $patent_id; ?>">
 							<input type="hidden" name="centre_id" id="centre_id" value="<?php echo $centre_id; ?>">
							<input type="hidden" id="given_by" name="given_by" value="<?php $given_by_name = get_userdata(get_current_user_id());
							$given_first_name = $given_by_name->first_name; 
							$given_last_name = $given_by_name->last_name;
							$given_fullname = "" . $given_first_name . " " . $given_last_name . ""; 
																					  print $given_fullname; ?>">
							<input type="hidden" id="given_by_id" name="given_by_id" value="<?php $current_user = wp_get_current_user();
                                                                                                    print($current_user->id); ?>">
			
				
                            <input type="submit" id="submit" name="medicationform" value="Add Medication" class="form_submit">
                    </form>
    </div>

<!-- END of form column -->

<!-- column for calculator -->
<div class="col-sm-3">
<div class="card mb-4" id="databasetable">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Volume Calculator</h6>
    </div>
    <div class="card-body">
    <div>
</div>

<div class="row">
    <div class="col-8 col-sm-12">
        <h6>Medication strength </h6>
    </div>
</div>
    <div class="row">
        <div class="col-8 col-sm-4">
            <input type="number" id="medication_dose" name="medication_dose" placeholder="mg in">
        </div>
        <div class="col-8 col-sm-4">
            <input type="number" id="medication_volume" name="medication_volume" placeholder="ml">
        </div>
        <div class="col-8 col-sm-4">                
        </div> 
    </div> 
       
       
<div class="row">
    <div class="col-8 col-sm-12">
        <br><h6>Prescription </h6>
    </div>
</div>
       
<div class="row">
    <div class="col-8 col-sm-6">
        <input type="number" id="required_dose" name="required_dose"placeholder="Prescribed dose" aria-describedby="requiredosehelp">
            <small id="requireddosehelp" class="form-text text-muted">
                Prescribed dose (in mg)
            </small>
    </div>
    <div class="col-8 col-sm-6">
            <h6><br>Give: <span class="amount" id="volume" name="volume"></span>mls</h6>
    </div>
</div>

<div class="row">
    <div class="col-8 col-sm-12">
        <br><h6>By weight </h6>
    </div>
</div>
<div class="row">
    <div class="col-8 col-sm-6">
        <input type="number" id="animal_weight" name="animal_weight"placeholder="Weight in kg" aria-describedby="weighthelp">
            <small id="weighthelp" class="form-text text-muted">
                Animal Weight (in kg) 
            </small>
    </div>

    <div class="col-8 col-sm-6">
            <h6>For <span class="prescribed" id="prescribed" name="prescribed"></span>mg per kilo give: <span class="amount" id="perweight" name="perweight"></span>mls</h6>
    </div>
</div>


     





<script>
    
var req_dose = 0;
var med_dose = 0;
var med_vol = 0;
var weight = 0;

$("#required_dose").change( function(){
    req_dose = $("#required_dose").val();
    calcTotals();

});
$("#medication_dose").change( function() {
    med_dose = $("#medication_dose").val();
    calcTotals();
});
$("#medication_volume").change( function() {
    med_vol = $("#medication_volume").val();
    calcTotals();
});
$("#animal_weight").change( function() {
    weight = $("#animal_weight").val();
    calcByweight();
        displayprescription();
});

var selectdose = document.getElementById("stock_item_used");
selectdose.addEventListener("change", function() {
  let context = selectdose.options[selectdose.selectedIndex]
var optiondose = context.getAttribute("data-dose");
  document.getElementById('medication_dose').value = optiondose;
var optionvol = context.getAttribute("data-volume");
  document.getElementById('medication_volume').value = optionvol;
var optionbn = context.getAttribute("data-batch");
  document.getElementById('bn_given').value = optionbn;
var optionexp = context.getAttribute("data-exp");
  document.getElementById('exp_given').value = optionexp;
  req_dose = context.getAttribute("data-dose");
  med_dose = $("#medication_dose").val();
  med_vol = $("#medication_volume").val();
  weight = $("#animal_weight").val();
  calcTotals();
  calcByweight();
  displayprescription();
});

function calcTotals(){
    let result = (req_dose * (med_vol / med_dose));
    $("#volume").text(result.toFixed(3));
}
function calcByweight() {
    let result = (req_dose * (med_vol / med_dose)) * weight;
    $("#perweight").text(result.toFixed(3));
}
function displayprescription(){
    $("#prescribed").text(req_dose);
}
</script>


            </div>
        </div>
    </div>
</div>




                               
                            
                        </div>
                    </div>
                </div>
</div>
    <script>$('#medication_given').select2({
        dropdownParent: $('#medicationModal')
    });</script>
<!---------------END of medication modal ----------------------------------------------------------->