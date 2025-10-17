<?php 
/*----------------------- FORM PROCESSING CARE NOTES-------------------*/
//Check if the notes form was submitted
if (isset($_POST['addweightForm'])) {

	$patient_id = $_POST["weight_thepatientid"];
  $add_weight = $_POST["weight"];
  $add_weight_unit = $_POST["weight_unit"];
	$add_date = $_POST["date"];

    try {
        $statement = $conn->prepare('INSERT INTO rescue_weights
            (patient_id, 
            weight,
            weight_unit,
            date)
            
            VALUES (:patient_id, 
            :weight,
            :weight_unit,
            :date)');

        $statement->execute([
            'patient_id' => $patient_id,
            'weight' => $add_weight,
            'weight_unit' => $add_weight_unit,
            'date' => $add_date
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
/*------------ END FORM ----------------*/
?>
<!-- ADD WEIGHT MODAL -->
<div class="modal fade" id="weightModal" tabindex="-1" role="dialog" aria-labelledby="weightModal" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
       <div class="modal-header">
       <h4 class="font-weight-bold text-primary">Add weight</h4> 
       <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
       </div>
                           
	   <div class="modal-body">
	   <b>Patient - <span class="admissionnameDisplay"><?php echo $patient_name ?></span></b> (CRN: <span class="admissionIDDisplay"><?php echo $patient_id ?></span>)

    <form action="" method="post" class="lead_form" id="addweightForm">
    <div class="row lead_form_row">
				<div class="col-md-12">
				<p class="angelo_form_label">Date and Time</p>
				<input type="datetime-local" name="date" id="date" placeholder="date" required>
				</div>
		  </div>
										
		<div class="row lead_form_row">
      <div class="col-md-12">
      <p class="angelo_form_label">Animal Weight</p>
      <input type="text" name="weight" id="weight" placeholder="Animal Weight" required>
			</div>
		</div>
			 
    <div class="row lead_form_row">                              
      <div class="col-md-12">
      <p class="angelo_form_label">Weight Unit</p>
      <select id="weight_unit" name="weight_unit">
        <option value="g">Grams</option>
        <option value="kg">Kilograms</option>
        <option value="lbs">Pounds</option>
      </select>
      </div>
		</div>
			 
    <div class="row lead_form_row">                              
      <div class="col-md-12">
				<input type="hidden" name="weight_thepatientid" id="weight_thepatientid" value="<?php echo $patient_id; ?>">
				<input type="submit" name="addweightForm" value="Update Patient Record">
				</form>
			 </div>
		  </div>
			          
   </div>
  </div>
</div>
</div>

<!--- END OF ADD WEIGHT MODAL  ---->