<?php 
/*--------------------------------------------------- FORM PROCESSING add_stock_meds-------------------------------------------------------------------*/
//Check if the medicaitons was submitted
if (isset($_POST['stock_meds_add'])) {

    $med_profile_id = $_POST["medication"];
    $packs_in = $_POST["qty_added"];
    $trans_centre_id = $_POST["centre_id"];
    $trans_user_id = $_POST["user_id"];
    $batch_number = $_POST["batch_number"];
    $expiry = $_POST["expiry_date"];
    $est_vol = $_POST["est_vol"];
          
    //Get the current time from the server
       $date = date('Y-m-d');
    
       try {
           $statement = $conn->prepare('INSERT INTO rescue_medication_trans
             (med_profile_id,
            packs_in,
            centre_id,
            user_id,
            batch_number,
            expiry,
            est_volume,
            date
            )
               
             VALUES (:med_profile_id,
            :packs_in,
            :centre_id,
            :user_id,
            :batch_number,
            :expiry,
            :est_volume,
            :date
            )');
    
           $statement->execute([
    
            'med_profile_id' => $med_profile_id,
            'packs_in' => $packs_in,
            'centre_id' => $trans_centre_id,
            'user_id' => $trans_user_id,
            'batch_number' => $batch_number,
            'expiry' => $expiry,
            'date' => $date,
            'est_volume' => $est_vol,
            
           ]);
           echo "<meta http-equiv='refresh' content='0'>";
       } catch (PDOException $e) {
           echo "Database Error: The inventory could not be added.<br>" . $e->getMessage();
           exit();
       } catch (Exception $e) {
           echo "General Error: The inventory could not be added.<br>" . $e->getMessage();
           exit();
       }
    }
?>

<div class="card-header py-3">
	<h6 class="m-0 font-weight-bold text-primary">Medications kept by your centre</h6><br></div> 


<div class="table-responsive">
    <table class="table table-bordered table-sm table-hover" id="" width="100%" cellspacing="0">
        <thead>
        <tr>
		<th>Medication</th>
        <th>Concentration </th>
        <th>Pack size</th>
		<th>Reorder Level</th>
        <th>Days once opened </th> 
        <th>Batch Number</th>
        <th>Expiry</th>
        <th></th>
        </tr>
        </thead>
    <tbody>
    <?php
    //get the centres list of medication profiles
    $stmt = $conn->prepare("SELECT * from rescue_stock_medication
							LEFT JOIN rescue_medications ON rescue_stock_medication.medication = rescue_medications.medication_id
							WHERE rescue_stock_medication.centre_id = :centre_id
							ORDER BY medication_name ASC");
    $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);

    // initialise an array for the results
    $medicationprofiles = array();
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $medication_name = $row["medication_name"];
    $medication_id = $row["medication"];
	$common_name = $row["common_name"];
	$dose = $row["concentration_dose"];
	$c_dose_type = $row["concentration_dose_type"];
    $volume = $row["concentration_volume"];
	$c_volume_type = $row["concentration_volume_type"];
    $pack_size = $row["pack_quantity"];
    $reorder = $row["reorder_level"];
    $use_within = $row["use_within"]; ?>

<tr><form action="" method="post">
	<td class="align-middle"><?php echo $medication_name;?> (<?php echo $common_name; ?>)<input type ="hidden" name="medication" value="<?php echo $medication_id; ?>"></td>
    <td class="align-middle"><?php echo $dose, $c_dose_type; ?><i> in </i><?php echo $volume, $c_volume_type; ?> </td>
    <td class="align-middle"><?php echo $pack_size, $c_volume_type;?>s</td>
    <td class="align-middle"><?php echo $reorder; ?></td>
    <td class="align-middle"><?php echo $use_within; ?> days</td>
    <td class="align-middle"><input type="text" name="batch_number" placeholder="Batch Number" class="form-control-sm" required></td>
    <td class="align-middle"><input type="datetime-local" name="expiry_date" id="expiry_date" class="form-control-sm" required></td>
    <input type="hidden" name="est_vol" value="<?php echo $pack_size; ?>">
    <input type="hidden" name="qty_added" id="qty_added" value="1">		   
	<input type="hidden" name="centre_id" id="centre_id" value="<?php echo $centre_id; ?>">
	<input type="hidden" name="user_id" id="user_id" value="<?php $current_user = wp_get_current_user(); print($current_user->id); ?>">
    <td class="align-middle"> <input type="submit" id="submit" name="stock_meds_add" value="Add to Stock" class="form_submit">

    </form> </td>                 
    
    
    <?php } ?></tr>


                        </tbody> 
                    </table>
                </div>