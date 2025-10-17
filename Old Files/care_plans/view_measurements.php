<?php
//Get the weight and measurement units which were used upon admission for this patient
$sql = 'SELECT * FROM rescue_measurements WHERE patient_id=:patient_id ORDER BY date ASC LIMIT 1';
$statement = $conn->prepare($sql);
$statement->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
/*---------------------------------------------------------------------------------*/
if ($result) {
    $first_measurement_unit = $result["measurement_unit"];

    //echo $first_measurement_unit;
} else {
    echo "Error - Admission measurement not found";
    exit();
}
/*---------------------------------------------------------------------------------*/
?>


    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">This patient's measurements over time</h6>
	</div>

<div class="card-body">
	<P><?php
    //gets the targetsizes from the table to display 
    $stmt = $conn->prepare("SELECT * FROM rescue_patients
                            RIGHT JOIN rescue_animal_species ON rescue_animal_species.species_name = rescue_patients.animal_species
							WHERE patient_id = :patient_id");
    $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

    // initialise an array for the results
    $target_sizes = array();
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $animal_species = $row["animal_species"];
	$species_measurement_from = $row["species_measurement_from"];
	$species_measurement_to = $row["species_measurement_to"];
	$species_measurement_unit = $row["species_measurement_unit"];
	$scientific_name = $row["scientific_name"];
	$reference = $row["reference"];
	$species_measurement_standard = $row["species_measurement_standard"];
    
    print '
            A typical adult ' . $animal_species . ' <I> (' . $scientific_name . ')</i> ' . $species_measurement_standard . ' should measure between <strong> ' . $species_measurement_from . '' . $species_measurement_unit . '  </strong> and
			<strong> ' . $species_measurement_to . '' . $species_measurement_unit . ' </strong> <BR>Reference: <i> ' . $reference . ' </i>
              ';
               }
              ?>

<div class="chart-area">
    <canvas id="measurementChart"></canvas>
</div>	
						
 <div class="card-header py-3">	
    <h6 class="m-0 font-measurement-bold text-primary">Add measurement</h6></div>
					 
	 <form action="https://rescuecentre.org.uk/wp-content/themes/brikk-child/ajax/add_measurement.php" method="post" class="lead_form" id="addMeasurementForm">
        <div class="row lead_form_row">
			<div class="col-md-6">
                <p class="angelo_form_label">Measurement</p>
                 <input type="text" placeholder="Animal measurement" name="measurement" id="measurement">
            </div>
            <div class="col-md-6 my-auto">
                <p class="angelo_form_label">Measurement unit</p>
                <select name="measurement_unit" name="measurement_unit" id="measurement_unit">
                    <option value="mm">Millimeters</option>
                    <option value="cm">Centimeters</option>
                    <option value="m">Meters</option>
                    <option value="in">Inches</option>
                    <option value="ft">Feet</option>
                </select>
            </div>   
        </div>
        <div class="row lead_form_row">
			<div class="col-md-6 my-auto">
				<p class="angelo_form_label">Date and Time</p>
				<input type="datetime-local" name="date" id="date" placeholder="date" required>
			</div>
			<div class="col-md-6 my-auto">
                <input type="hidden" name="measurement_thepatientid" id="measurement_thepatientid" value="<?php echo $patient_id; ?>">
                <input type="submit" name="form4" value="Add measurement">
            </div>
        </div>
        </form>
		
        <div class="row lead_form_row">
	        <?php include_once("table_measurements.php"); ?>
        </div>
</div>										
               