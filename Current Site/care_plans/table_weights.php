<?php
/*--------------------- FORM PROCESSING - delete entry--------------------------*/
if (isset($_POST['weight_delete'])) {

    $wt_id = $_POST["wt_id"];

    try {
        $statement = $conn->prepare('DELETE FROM rescue_weights
            WHERE weight_id=:wt_id 
			');

        $statement->execute([
            'wt_id' => $wt_id,
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
  
   ?>

      <tr>
      <td class="align-middle" ><?php echo $weight_dat; ?></td>
      <td class="align-middle"><?php echo $weight_wt; ?><?php echo $weight_u;?> </td>
      <td class="align-middle"><form method="post" action=""><input type="hidden" id="wt_id" name="wt_id" value="<?php echo $wt_id; ?> "><button type="submit" class="btn-sm btn-danger" name="weight_delete">Delete</button> 
      </form></td>
      <?php }?></tr>

   </table>
   </div>