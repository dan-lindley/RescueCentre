<?php ?>
<!-- VIEW ALL THE PATIENTS LABE RESULTS --> 
<div class="table-responsive">
    <table class="table table-bordered table-sm table-hover" id="meds" width="100%" cellspacing="0">
    <thead class="thead-dark">
        <tr>
			<th class="align-middle" width="150">Date</th>
			<th class="align-middle" width="150">Severity Score</th>
            <th class="align-middle">Body Condition</th>
		    <th class="align-middle">Age</th>
            <th class="align-middle">WRA Score</th>
            <th class="align-middle">Notes</th>
        </tr>
    </thead>
    <tbody>

<?php
    //gets the medications from the table to display 
        $stmt = $conn->prepare("SELECT * FROM rescue_observations AS o
                                WHERE o.patient_id = :patient_id ORDER by obs_id DESC");
                                $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

        // initialise an array for the results
        $obs = array();
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $obs_date = $row["obs_date"];
        $obs_id = $row["obs_id"];
        $obs_ss = $row["obs_severity_score"];
        $obs_ss_text = $row["obs_severity_text"];
        $obs_bcs = $row["obs_bcs_score"];
        $obs_bcs_text = $row["obs_bcs_text"];
        $obs_age = $row["obs_age_score"];
        $obs_age_text = $row["obs_age_text"];
        $obs_notes = $row["obs_notes"];
		$obsformat_date = new DateTime($obs_date);
   		$obsformat_date = $obsformat_date->format('d-m-Y');
		$obsformat_time = new DateTime($obs_date);
		$obsformat_time = $obsformat_time->format('H:i');
        
        $wra = ($obs_bcs + $obs_age) + $obs_ss; 
        // TRAFFIC LIGHT SYSTEM FOR WRA score
 	    if ($wra > 90 ) {
      $wraclass = '';
      $wra = "N/A";
      } elseif ($wra >= 6) {
      $wraclass = 'table-danger';
      } elseif ($wra >= 3) {
      $wraclass = 'table-warning';
      } elseif ($wra < 3) {
      $wraclass = 'table-success';
      } 

        ?>
							 <tr>
                                <td><?php echo $obsformat_date; ?> 
                                        <br><b><?php echo $obsformat_time; ?></b></td>
								<td class="align-middle"><?php echo $obs_ss_text; ?>
                                        <br><b> (+<?php echo $obs_ss; ?>) </b></td>
                                <td class="align-middle"><?php echo $obs_bcs_text; ?>
                                        <br><b> (+<?php echo $obs_bcs; ?>)</b></td>
								<td class="align-middle"><?php echo $obs_age_text; ?>
                                        <br><b> (+<?php echo $obs_age; ?>) </b></td>
                                <td class="align-middle text-center <?php echo $wraclass; ?>"><b> <h3><?php echo $wra; ?></h3> </b></td>
								<td class="align-middle"><?php echo $obs_notes; ?> </td> </tr> <?php } ?> </tbody> </table>
                                               
                            <!-- Add new test Button -->
                            <BR><button type="button" class="btn btn-success" data-toggle="modal" data-target="#observationsModal"> Add Observation
                            </button><br>