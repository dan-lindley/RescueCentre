<?php
/*--------------------- FORM PROCESSING - delete entry--------------------------*/
if (isset($_POST['delete'])) {

    $weight_id = $_POST["weight_id"];

    try {
        $statement = $conn->prepare('DELETE FROM rescue_measurements
            WHERE weight_id=:weight_id 
			');

        $statement->execute([
            'weight_id' => $weight_id,
        ]);

		echo "<meta http-equiv='refresh' content='0'>";
		
    } catch (PDOException $e) {
        echo "Database Error: The note could not be added.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The note could not be added.<br>" . $e->getMessage();
        exit();
    }

} ?>





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
   $mea_format_date = $mea_format_date->format('d-m-Y <\b\r> H:i'); 
  
   ?>

      <tr>
      <td class="align-middle"><?php echo $mea_format_date; ?></td>
      <td class="align-middle"><?php echo $measure_me; ?><?php echo $measure_u;?> </td>
      <td class="align-middle"><form method="post" action=""><input type="hidden" id="weight_id" name="weight_id" value="<?php echo $weight_id; ?> "><button type="submit" class="btn-sm btn-danger" name="delete">Delete</button> 
      </form></td>
      <?php }?></tr>

   </table>
   </div>