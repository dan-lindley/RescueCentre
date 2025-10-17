<?php ?> 
<div class="content-block">

<div class="table-responsive">
    <table class="table table-bordered table-sm table-hover" id="meds" width="100%" cellspacing="0">
        <thead class="thead-dark">
        <tr>
        <th class="align-middle" width="20"></th>
		<th class="align-middle" width="150">Date</th>
        <th class="align-middle">Partner</th>
		<th class="align-middle" width="150">Log number</th>
        <th class="align-middle">Notes</th>
        </tr>
        </thead>
    <tbody>
<?php
    //gets the partner logs from the table to display 
    $stmt = $pdo->prepare("SELECT * FROM rescue_partner_log 
    LEFT JOIN rescue_partner_types ON rescue_partner_log.partner_type = rescue_partner_types.p_type_id
    WHERE patient_id = :patient_id ORDER by date DESC");
    $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

    // initialise an array for the results
        $partnerlogs = array();
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $log_date = $row["date"];
        $partner = $row["partner_type"];
        $log = $row["log_number"];
        $log_notes = $row["log_notes"];
        $crime = $row["is_crime"];
		
        $log_format_date = new DateTime($log_date);
   	    $log_format_date = $log_format_date->format('d-m-Y');?>




	<tr>
    <td class="align-middle"><?php if (!empty($crime)){print '<i class="fas fa-exclamation-triangle text-danger" data-toggle="tooltip" data-placement="top" title="Crime Log"></i>';}?>
    <td><?php echo $log_format_date; ?></td>
    <td class="align-middle"><?php echo $partner; ?></td>
	<td class="align-middle"><?php echo htmlspecialchars($log) ?></td>
	<td class="align-middle"><?php echo htmlspecialchars($log_notes) ?> </td> </tr> <?php } ?> </tbody> </table>
                                               
        </div>
