<?php 
/*----------------------- FORM PROCESSING MEASUREMENT-------------------*/
//Check if the notes form was submitted
if (isset($_POST['addmeasurementForm'])) {

	$patient_id = $_POST["measurement_thepatientid"];
    $add_measurement = $_POST["measurement"];
    $add_measurement_unit = $_POST["measurement_unit"];
	$add_date = $_POST["date"];

    try {
        $statement = $conn->prepare('INSERT INTO rescue_measurements
            (patient_id, 
            measurement,
            measurement_unit,
            date)
            
            VALUES (:patient_id, 
            :measurement,
            :measurement_unit,
            :date)');

        $statement->execute([
            'patient_id' => $patient_id,
            'measurement' => $add_measurement,
            'measurement_unit' => $add_measurement_unit,
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
<!-- ADD measurement MODAL -->
<div class="modal fade" id="measurementModal" tabindex="-1" role="dialog" aria-labelledby="measurementModal" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
       <div class="modal-header">
       <h4 class="font-measurement-bold text-primary">Add measurement</h4> 
       <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
       </div>
                           
	   <div class="modal-body">
	   <b>Patient - <span class="admissionnameDisplay"><?php echo $patient_name ?></span></b> (CRN: <span class="admissionIDDisplay"><?php echo $patient_id ?></span>)

    <form action="" method="post" class="lead_form" id="addmeasurementForm">
    <div class="row lead_form_row">
				<div class="col-md-12">
				<p class="angelo_form_label">Date and Time</p>
				<input type="datetime-local" name="date" id="date" placeholder="date" required>
				</div>
		  </div>
										
		<div class="row lead_form_row">
      <div class="col-md-12">
      <p class="angelo_form_label">Animal measurement</p>
      <input type="text" name="measurement" id="measurement" placeholder="Animal measurement" required>
			</div>
		</div>
			 
    <div class="row lead_form_row">                              
      <div class="col-md-12">
      <p class="angelo_form_label">measurement Unit</p>
      <select id="measurement_unit" name="measurement_unit">
      <option value="mm">Millimeters</option>
       <option value="cm">Centimeters</option>
        <option value="m">Meters</option>
          <option value="in">Inches</option>
         <option value="ft">Feet</option>
      </select>
      </div>
		</div>
			 
    <div class="row lead_form_row">                              
      <div class="col-md-12">
				<input type="hidden" name="measurement_thepatientid" id="measurement_thepatientid" value="<?php echo $patient_id; ?>">
				<input type="submit" name="addmeasurementForm" value="Update Patient Record">
				</form>
			 </div>
		  </div>
			          
   </div>
  </div>
</div>
</div>

<!--- END OF ADD measurement MODAL  ---->