
<?php 

?>

<div class="table-responsive">
   <table class="table table-bordered table-sm table-hover" id="meds_in" width="100%" cellspacing="0">
   <thead class="thead-dark">
   <tr>
      <th class="align-middle">Date Added</th>
	   <th class="align-middle">Medication</th>
	   <th class="align-middle">Batch Number</th>
      <th class="align-middle">Expiry</th>
      <th class="align-middle">Date Opened</th>
      <th class="align-middle">Date Finished</th>
      <th class="align-middle">Estimated Volume</th>
   </tr>
   </thead>

   <tbody>
<?php
//Get the stock from the stock table
$stmt = $conn->prepare("SELECT *
FROM rescue_medication_trans
JOIN rescue_medications ON rescue_medications.medication_id = rescue_medication_trans.med_profile_id
LEFT JOIN rescue_stock_medication ON rescue_stock_medication.medication  = rescue_medication_trans.med_profile_id
WHERE rescue_medication_trans.centre_id = :centre_id
ORDER BY medication_name ASC");
$stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);

// initialise an array for the results
$stk_meds = array();
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

   $stk_dat = $row["date"];
   $stk_bn = $row["batch_number"];
   $stk_exp = $row["expiry"];
   $stk_med = $row["medication_name"];
   $stk_dos= $row["concentration_dose"];
   $stk_vol = $row["concentration_volume"];
   $stk_open = $row["date_opened"];
   $stk_fin = $row["date_finished"];
   $stk_est = $row["est_volume"];
   $stk_v_type = $row["concentration_volume_type"];
   $stk_d_type = $row["concentration_dose_type"];

   
   ?>

      <tr>
      <td><?php echo $stk_dat; ?></td>
      <td><?php echo $stk_med; ?> (<?php echo $stk_dos;?> <?php echo $stk_d_type; ?> in <?php echo $stk_vol; ?> <?php echo $stk_v_type; ?>)</td>
      <td><?php echo $stk_bn; ?></td>
      <td><?php echo $stk_exp; ?></td>
      <td><?php echo $stk_open; ?></td>
      <td><?php echo $stk_fin; ?></td>
      <td><?php echo $stk_est, $stk_v_type  ?></td>
      <?php }?></tr>

   </table>
   </div>

  