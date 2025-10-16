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

<?php
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

<canvas id="measurementchart"></canvas>

<script>
	new Chart(document.getElementById("measurementchart"), {
		type : 'line',
		data : {
			labels : [ <?php
                        //Loop through the measurement months from the database
                        $stmt = $conn->prepare("SELECT * FROM rescue_measurements WHERE patient_id = :patient_id ORDER by date ASC");
                        $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

                        // initialise an array for the results
                        $measurementlabels = array();
                        $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $graph_date = $row["date"];
                            $day = date('j', strtotime($graph_date));
                            $suffix = '';

                            if ($day == 1 || $day == 21 || $day == 31) {
                                $suffix = 'st';
                            } elseif ($day == 2 || $day == 22) {
                                $suffix = 'nd';
                            } elseif ($day == 3 || $day == 23) {
                                $suffix = 'rd';
                            } else {
                                $suffix = 'th';
                            }

                            $dayWithSuffix = $day . $suffix;
                            $monthAbbreviation = date('M', strtotime($graph_date));
                            $yearDigits = date('y', strtotime($graph_date));
                            print '"' . $dayWithSuffix . ' ' . $monthAbbreviation . ' ' . $yearDigits . '",';
                        }

                        ?> ],
			datasets : [
					    {
						data : [ 
                            <?php
                        //Loop through this patient's measurements from the database
                        $stmt = $conn->prepare("SELECT * FROM rescue_measurements WHERE patient_id = :patient_id ORDER by date ASC");
                        $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

                        // initialise an array for the results
                        $measurementdata = array();
                        $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                            $graph_measurement = $row["measurement"];

                            print '' . $graph_measurement . ',';
                        }

                        ?>
                         ],
						label : "<?php echo $patient_name;?>'s Measurements",
						borderColor : "#148805ff",
						fill : false,
                        tension : 0.4
					    },

                        {
                        data : [ 
                            <?php print  '' . $species_measurement_to . ', ' . $species_measurement_to . ', ' . $species_measurement_to . ', ' . $species_measurement_to . ''; ?>
                         ],
						label : "Upper Measurement",
						borderColor : "#f11f10ff",
                        borderWidth: 1,
                        fill : false,
                        tension : 0.4    
                        },

                        {
                        data : [ 
                            <?php print  '' . $species_measurement_from . ', ' . $species_measurement_from . ', ' . $species_measurement_from . ', ' . $species_measurement_from . ''; ?>
                         ],
						label : "Lower Measurement",
						borderColor : "#230270ff",
                        borderWidth: 1,
						fill : false,
                        tension : 0.4    
                        }
                    ] 
                    },
		options : {
			title : {
				display : true,
				text : 'Measurement Tracker'
			}
		}
	});
</script>

<div class="table-responsive">
   <table class="table table-bordered table-sm table-hover" id="weights" width="100%" cellspacing="0">
   <thead class="thead-dark">
   <tr>
      <th class="align-middle">Date Added</th>
	   <th class="align-middle">Measurement</th>
	   <th class="align-middle"></th>
   </tr>
   </thead>

   <tbody>
<?php
//Get the stock from the stock table
$stmt = $conn->prepare("SELECT *
FROM rescue_measurements
WHERE rescue_measurements.patient_id = :patient_id
ORDER BY date ASC");
$stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

// initialise an array for the results
$pt_weights = array();
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

   $measure_dat = $row["date"];
   $measure_me = $row["measurement"];
   $measure_u = $row["measurement_unit"];
   $weight_id = $row["weight_id"];
       
   $mea_format_date = new DateTime($measure_dat);
   $mea_format_date = $mea_format_date->format('d-m-Y H:i'); 
  
   ?>

      <tr>
      <td class="align-middle"><?php echo $mea_format_date; ?></td>
      <td class="align-middle"><?php echo $measure_me; ?><?php echo $measure_u;?> </td>
      <td class="align-middle"><form method="post" action=""><input type="hidden" id="weight_id" name="weight_id" value="<?php echo $weight_id; ?> "><!-- <button type="submit" class="btn-sm btn-danger" name="delete">Delete</button> -->
      </form></td>
      <?php }?></tr>

   </table>
   </div>
