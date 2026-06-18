<table>	
<?php
    //Get by the location count
    $stmt = $conn->prepare("SELECT location_name, rescue_locations.centre_id, current_location, max_occupancy,
							COUNT(current_location) AS in_location
							FROM rescue_admissions
							RIGHT JOIN rescue_locations
							ON rescue_admissions.current_location = rescue_locations.location_name
							WHERE rescue_locations.centre_id = :centre_id AND deleted=0 or location_name is NULL
							GROUP BY location_name
						    ORDER BY location_name
							");
					
    $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);

    // initialise an array for the results
    $occupied = array();
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $max = $row["max_occupancy"];
	$in = $row["in_location"];
	$name = $row["location_name"];
						
	$occupied = ($in / $max) * 100;
    
                         print '
						<tr> <td width ="25%">
						 ' .$name. '</td><td width="5%"><strong>' .$in. '</strong></td><td> <div class="progress">
  <div class="progress-bar progress-bar-striped bg-info" role="progressbar" style="width: ' . $occupied .'%" aria-valuenow="' . $occupied .'" aria-valuemin="0" aria-valuemax="100"></div>
</div></td></tr>			 
						 
						 	';
                    }

		?>
		
	</table>                 


<div class="table-responsive">  
<table class="table table-bordered table-sm table-hover" id="addnewpts" width="100%" cellspacing="0">

<!-- Show locations -->
<?php
$stmt = $conn->prepare("SELECT location_area, rescue_locations.*
    FROM rescue_locations
    WHERE centre_id = :centre_id and deleted = 0
    ORDER by `location_area`, `location_name` DESC");
$stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);


foreach ($data as $location_area => $area) {
    print " <thead class='thead-dark'>
                <tr>
                    <th class='align-middle'><u>".htmlspecialchars($location_area)."</u></th>
                    <th class='align-middle'>Max Occupancy</th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>";


    foreach ($area as $row) {
        print '
            <tbody>
                <tr>
                    <td width="300" class="align-middle">' . htmlspecialchars($row['location_name']) . ' ('. htmlspecialchars($location_type) . ')</td>                  
                    <td width="150" class="align-middle">' . htmlspecialchars($row['max_occupancy']) . '</td>
                    <td width="150" class="align-middle"> ';
                        $location_id = $row['location_id'];
                        $location_type = $row["location_type"];
                        echo $location_type; ?>
                    </td>
                   
                    <td width = "150" class="align-middle">
                        
                    </td>
                    
             
                    </td>
                </tr>
            <?php ; } 
        } ?>
        
        </tbody></table>