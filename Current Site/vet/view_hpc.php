<?php
   //Get triage information from the admission form
   $stmt = $conn->prepare("SELECT * FROM rescue_admissions WHERE patient_id=:patient_id");
   $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

   // initialise an array for the results
   $vettriages = array();
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
<p class="text-primary">
<b><U>Admission Triage</u></b>
<br><b>Presenting Complaint:</b> <?php echo $presenting_complaint; ?>
<br><b>HPC:</b> <?php echo $tr_hpc; ?>
<br><b>On Examination:</b> <?php echo $tr_oe; ?>

<hr>
<table>
    <tr>
        <td class="align-middle <?php echo $wraclass; ?>"><b><h5><p class="text-primary">Wildlife Rapid <br>Assessment Score: <?php echo $wra_score; ?></b></h5></td>
        <td class="align-middle <?php echo $wraclass; ?>"><b><p class="text-primary">Body Condition Score:</b> <?php echo $tr_bcs_text; ?> (<?php echo $tr_bc; ?>)</td>
        <td class="align-middle <?php echo $wraclass; ?>"><b><p class="text-primary">Injury Severity Score:</b> <?php echo $tr_ss_text; ?> (<?php echo $tr_ss; ?>)</td>
        <td class="align-middle <?php echo $wraclass; ?>"><b><p class="text-primary">Age Score:</b> <?php echo $tr_age_text; ?> (<?php echo $tr_age; ?>)</td>
    </tr>
</table>