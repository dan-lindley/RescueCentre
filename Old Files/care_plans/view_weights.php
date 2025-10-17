<?php //Get the weight and measurement units which were used upon admission for this patient
$sql = 'SELECT * FROM rescue_weights WHERE patient_id=:patient_id ORDER BY date ASC LIMIT 1';
$statement = $conn->prepare($sql);
$statement->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
/*---------------------------------------------------------------------------------*/
if ($result) {
    $first_weight_unit = $result["weight_unit"];

    //echo $first_weight_unit;
} else {
    echo "Error - Admission weight not found";
    exit();
}
/*---------------------------------------------------------------------------------*/ 
?>



    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">This patient's weight over time</h6>
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
				$species_weight_from = $row["species_weight_from"];
				$species_weight_to = $row["species_weight_to"];
				$species_weight_unit = $row["species_weight_unit"];
				$scientific_name = $row["scientific_name"];
				$reference = $row["reference"];
                                        
        print '
                 A typical adult ' . $animal_species . ' <I> (' . $scientific_name . ')</i> should weigh between <strong> ' . $species_weight_from . '' . $species_weight_unit . '  </strong> and
				<strong> ' . $species_weight_to . '' . $species_weight_unit . ' </strong> <BR>Reference: <i> ' . $reference . ' </i>
                                    ';
                                    }
                                    ?>		

    <div class="chart-area">
        <canvas id="myAreaChart"></canvas>
    </div>

    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Add weight</h6></div>
					 
	 <form action="https://rescuecentre.org.uk/wp-content/themes/brikk-child/ajax/add_weight.php" method="post" class="lead_form" id="addweightForm">
                                        					
	<div class="row lead_form_row">
		<div class="col-md-6">
            <p class="angelo_form_label">Weight</p>
            <input type="text" placeholder="Animal weight" name="weight" id="weight">
        </div>
        <div class="col-md-6 my-auto">
        <p class="angelo_form_label">Weight unit</p>
            <select name="weight_unit" name="weight_unit" id="weight_unit">
                <option value="g">Grams</option>
                <option value="kg">Kilograms</option>
                <option value="lbs">Pounds</option>
            </select>
        </div>  
    </div>
    <div class="row lead_form_row">
		<div class="col-md-6">
            <p class="angelo_form_label">Date and Time</p>
				<input type="datetime-local" name="date" id="date" placeholder="date" required>
	    </div>
		<div class="col-md-6 my-auto">
            <input type="hidden" name="weight_thepatientid" id="weight_thepatientid" value="<?php echo $patient_id; ?>">
            <input type="submit" name="form5" value="Add weight">
		</div>
    </div>
        </form>
	<div class="row lead_form_row">
			 <?php include_once("table_weights.php"); ?>
    </div>
		 
</div>						


