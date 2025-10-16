<?php
//Get the weight and measurement units which were used upon admission for this patient
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
<canvas id="weightchart"></canvas>

<script>
	new Chart(document.getElementById("weightchart"), {
		type : 'line',
		data : {
			labels : [ <?php
                        //Loop through the measurement months from the database
                        $stmt = $conn->prepare("SELECT * FROM rescue_weights WHERE patient_id = :patient_id ORDER by date ASC");
                        $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
                        // initialise an array for the results
                        $weightlabels = array();
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
                        //Loop through this patient's weights from the database
                        $stmt = $conn->prepare("SELECT * FROM rescue_weights WHERE patient_id = :patient_id ORDER by date ASC");
                        $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

                        // initialise an array for the results
                        $weightdata = array();
                        $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                            $graph_weight = $row["weight"];

                            print '' . $graph_weight . ',';
                        }

                        ?>
                         ],
						label : "<?php echo $patient_name;?>'s Weight",
						borderColor : "#148805ff",
						fill : false,
                        tension : 0.4
					    },

                        {
                        data : [ 
                            <?php print  '' . $species_weight_to . ', ' . $species_weight_to . ', ' . $species_weight_to . ', ' . $species_weight_to . ''; ?>
                         ],
						label : "Upper Weight",
						borderColor : "#f11f10ff",
                        borderWidth: 1,
                        fill : false,
                        tension : 0.4    
                        },

                        {
                        data : [ 
                            <?php print  '' . $species_weight_from . ', ' . $species_weight_from . ', ' . $species_weight_from . ', ' . $species_weight_from . ''; ?>
                         ],
						label : "Lower Weight",
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
				text : 'Weight Tracker'
			}
		}
	});
</script>



<div class="table-responsive">
   <table class="table table-bordered table-sm table-hover" id="weights" width="100%" cellspacing="0">
   <thead class="thead-dark">
   <tr>
      <th class="align-middle">Date Added</th>
	   <th class="align-middle">Weight</th>
	   <th class="align-middle"></th>
   </tr>
   </thead>

   <tbody>
<?php
//Get the stock from the stock table
$stmt = $conn->prepare("SELECT *
FROM rescue_weights
WHERE rescue_weights.patient_id = :patient_id
ORDER BY date ASC");
$stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

// initialise an array for the results
$pt_weights = array();
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

   $weight_dat = $row["date"];
   $weight_wt = $row["weight"];
   $weight_u = $row["weight_unit"];
   $wt_id = $row["weight_id"];
    
   $wt_format_date = new DateTime($weight_dat);
   $wt_format_date = $wt_format_date->format('d-m-Y H:i'); 
  
   ?>

      <tr>
      <td class="align-middle" ><?php echo $wt_format_date; ?></td>
      <td class="align-middle"><?php echo $weight_wt; ?><?php echo $weight_u;?> </td>
      <td class="align-middle"><form method="post" action=""><input type="hidden" id="wt_id" name="wt_id" value="<?php echo $wt_id; ?> "><!-- <button type="submit" class="btn-sm btn-danger" name="weight_delete">Delete</button> -->
      </form></td>
      <?php }?></tr>

   </table>
   </div>