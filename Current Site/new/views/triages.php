<?php
   //Get triage information from the admission form
   $stmt = $pdo->prepare("SELECT * FROM rescue_admissions WHERE patient_id=:patient_id");
   $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

   // initialise an array for the results
   $triages = array();
   $stmt->execute();
   while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

   $tr_ss = $row["severity_score"];
   $tr_ss_text = $row["ss_text"];
   $tr_bc = $row["bc_score"];
   $tr_bcs_text= $row["bcs_text"];
   $tr_age = $row["age_score"];
   $tr_age_text = $row["age_on_admission"];
   $tr_hpc = $row["hpc"];
   $tr_oe = $row["on_examination"];

   $wra_score = ($tr_ss + $tr_bc) + $tr_age; 
   
         // TRAFFIC LIGHT SYSTEM FOR WRA score
         if ($wra_score > 90 ) {
            $wraclass = '';
            $wra_score = "N/A";
            } elseif ($wra_score >= 6) {
            $wraclass = 'table-danger';
            } elseif ($wra_score >= 3) {
            $wraclass = 'table-warning';
            } elseif ($wra_score < 3) {
            $wraclass = 'table-success';
            } 
      }
   ?>

<div class="table-responsive">
   <table class="table table-bordered table-sm table-hover" id="wrascore" width="100%" cellspacing="0">
   <thead class="thead-dark">
   <tr>
		<th class="align-middle">Age</th>
		<th class="align-middle">Injury Severity</th>
      <th class="align-middle">Body Condition</th>
   </tr>
   </thead>
   <tbody>
      <tr>
      <td><?php echo $tr_age_text; ?> (Score: <?php echo $tr_age; ?>)</td>
      <td><?php echo $tr_ss_text; ?> (Score: <?php echo $tr_ss; ?>)</td>
      <td><?php echo $tr_bcs_text; ?> (Score: <?php echo $tr_bc; ?>)</td>
   </tr>
   <tr>
      <td colspan="3" class="align-middle <?php echo $wraclass; ?>"><h4>Wildlife Rapid Assessment Score: <?php echo $wra_score; ?></h4></td>
   </tr>
   </table>
   </div>

   <div class="table-responsive">
   <table class="table table-bordered table-sm table-hover" id="hpc" width="100%" cellspacing="0">
   <thead class="thead-dark">
   <tr>
		<th class="align-middle">History of Presenting Complaint</th>
   </tr>
   </thead>
   <tbody>
      <tr>
      <td><?php echo $tr_hpc; ?></td>
   </tr>
   </table>
   </div>


   <div class="table-responsive">
   <table class="table table-bordered table-sm table-hover" id="oe" width="100%" cellspacing="0">
   <thead class="thead-dark">
   <tr>
		<th class="align-middle">On Examination</th>
   </tr>
   </thead>
   <tbody>
      <tr>
      <td><?php echo $tr_oe; ?></td>
   </tr>
   </table>
   </div>