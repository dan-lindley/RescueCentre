<?php
// FORM PROCESSING 
// Locations
//Check if the location form was submitted
if (isset($_POST['locationform'])) {
    $location_name = $_POST["location_name"];
    $location_type = $_POST["location_type"];
    $max_occupancy = $_POST["max_occupancy"];
	$location_area = $_POST["location_area"];


    try {
        $statement = $conn->prepare('INSERT INTO rescue_locations
            (centre_id, 
            location_name,
			location_type,
			location_area,
            max_occupancy)           
            VALUES (:centre_id, 
            :location_name,
            :location_type,
			:location_area,
            :max_occupancy)	
			');
		
        $statement->execute([
            'centre_id' => $centre_id,
            'location_name' => $location_name,
            'location_type' => $location_type,
			'location_area' => $location_area,
            'max_occupancy' => $max_occupancy
        ]);
    } catch (PDOException $e) {
        echo "Database Error: The location could not be added.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The location could not be added.<br>" . $e->getMessage();
        exit();
    }
}

//Areas

if (isset($_POST['areaform'])) {
    $area_name = $_POST["area_name"];
    try {
        $statement = $conn->prepare('INSERT INTO rescue_areas
            (centre_id, 
            area_name)        
            VALUES (:centre_id, 
            :area_name)
			');
		

        $statement->execute([
            'centre_id' => $centre_id,
            'area_name' => $area_name
        ]);
    } catch (PDOException $e) {
        echo "Database Error: The area could not be added.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The area could not be added.<br>" . $e->getMessage();
        exit();
    }
}
// This Deletes locations

if (isset($_POST['locupdate'])) {
    $location_id = $_POST["location_id"];
    $deleted = $_POST["deleted"];

    try {
        $statement = $conn->prepare('INSERT INTO rescue_locations
            ( 
            location_id,
			deleted)            
            VALUES (
            :location_id,
			:deleted) 			
			ON DUPLICATE KEY UPDATE
			deleted = :deleted	
			');

        $statement->execute([
            'location_id' => $location_id,
            'deleted' => $deleted
			            
        ]);
		echo "<meta http-equiv='refresh' content='0'>";
		
    } catch (PDOException $e) {
        echo "Database Error: The note could not be added.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The note could not be added.<br>" . $e->getMessage();
        exit();
    }

}

//This updates locations

if (isset($_POST['occupform'])) {
    $location_id = $_POST["location_id"];
    $max_occupancy = $_POST["max_occupancy"];

    try {
        $statement = $conn->prepare('INSERT INTO rescue_locations
            ( 
            location_id,
			max_occupancy)         
            VALUES (
            :location_id,
			:max_occupancy) 			
			ON DUPLICATE KEY UPDATE
			max_occupancy = :max_occupancy	
			');

        $statement->execute([
            'location_id' => $location_id,
            'max_occupancy' => $max_occupancy
			           
        ]);
		echo "<meta http-equiv='refresh' content='0'>";
		
    } catch (PDOException $e) {
        echo "Database Error: The note could not be added.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The note could not be added.<br>" . $e->getMessage();
        exit();
    }

}
?>


<div class="table-responsive">  
<table class="table table-bordered table-sm table-hover" id="addnewpts" width="100%" cellspacing="0">

<!-- Show locations -->
<?php
$stmt = $conn->prepare("SELECT  location_area, rescue_locations.*
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
                    <td width="300" class="align-middle">' . htmlspecialchars($row['location_name']) . '</td>                  
                    <td width="150" class="align-middle">' . htmlspecialchars($row['max_occupancy']) . '</td>
                    <td width="150" class="align-middle"> ';
                        $location_id = $row['location_id'];
                        $location_type = $row["location_type"];
                        echo $location_type; ?>
                    </td>
                    <form method="post" action="">
                    <td width = "150" class="align-middle">
                        <input type="text" name="max_occupancy" id="max_occupancy" style="width: 5ch;" value="<?php echo htmlspecialchars($row['max_occupancy']);?>"> 
                        <input type="hidden" id="location_id" name="location_id" value="<?php echo $location_id; ?>"> 
                        <button type="submit" class="btn btn-secondary btn-info" name="occupform">Update</button> </form>
                    </td>
                    <form method="post" action="">
                    <td class="align-middle">                      
                        <input type="hidden" id="location_id" name="location_id" value="<?php echo$location_id; ?>">
                        <input type="hidden" id="deleted" name="deleted" value="1">
                        <button type="submit" class="btn btn-secondary btn-danger" name="locupdate">Delete</button> 
                        </form>
                    </td>
                </tr>
            <?php ; } 
        } ?>
        
        </tbody></table>
<!-- Add A location Modal -->
<div class="modal fade" id="locationModal" tabindex="-1" role="dialog" aria-labelledby="locationModal" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="font-weight-bold text-primary">Add Location</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p>Locations are individual cages/hutches etc and areas are places where these are kept, eg shed, barn, field etc.
                <form action="" method="post">
                <p><label for="location_name">Name for location:</label>
                <input type="text" id="location_name" name="location_name" rows="1" cols="5"></p>
				<p><label for="location_type">Type:</label>
                <select id="location_type" name="location_type">
                    <option>Incubator</option>
                    <option>Tank</option>
					<option>Pen</option>
					<option>Kennel</option>
					<option>Paddock</option>
					<option>Hutch</option>
					<option>Aviary</option>
					<option>Flight Cage</option>
					<option>Cage</option>
                    <option>Bat Box</option>
                    <option>Bird box</option>
				</select></p>
                                            
				<p><label for="area">Area:</label></p>
				<p><select name="location_area" name="location_area" id="location_area">
                        <option value="" disabled selected>Select area</option>
                        <?php
                        //Find areas stored in the patients table 
                        $stmt = $conn->prepare("SELECT * 
                        FROM rescue_areas
                        WHERE centre_id = :centre_id ORDER BY area_name DESC");
                        $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);

                        // initialise an array for the results
                        $areas = array();
                        $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $area_name = $row["area_name"]; 
                        print '<option value="' . $area_name. '">' . $area_name . ' </option>';
                                }
                                ?>
                    </select>
											 
                    <input type="hidden" id="centre_id" name="centre_id" value="<?php echo $centre_id; ?>">
                    <input type="submit" id="submit" name="locationform" value="Add Location" class="form_submit">
                </form>
            </div>
        <br />
        </div>
    </div>
</div>
<!---------------END of location modal ----------------------------------------------------------->


<!-- Add Area Modal -->
<div class="modal fade" id="areaModal" tabindex="-1" role="dialog" aria-labelledby="areaModal" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="font-weight-bold text-primary">Add Area</h4> <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span> </button>
            </div>
            <div class="modal-body">
                <p>Use areas for places where you keep the cages/hutches/tanks etc 
                <form action="" method="post">
                    <p><label for="location_name">Area:</label>
                    <input type="text" id="area_name" name="area_name" rows="1" cols="5"></p>
				    <input type="hidden" id="centre_id" name="centre_id" value="<?php echo $centre_id; ?>">
                    <input type="submit" id="submit" name="areaform" value="Add Location" class="form_submit">
                </form>
            </div>
            <br />
        </div>
    </div>
</div>
<!---------------END of Area modal ----------------------------------------------------------->			