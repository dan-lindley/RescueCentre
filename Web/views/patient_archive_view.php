<?php 
//Row Count
$sql = "SELECT * 
FROM rescue_admissions
INNER JOIN rescue_patients
ON rescue_admissions.patient_id = rescue_patients.patient_id
WHERE rescue_patients.centre_id = :centre_id
ORDER by `admission_date` DESC";
$stmt = $pdo->prepare($sql);

// bind parameters
$stmt->bindParam(':centre_id', $centre_id);

// execute query
$stmt->execute();

// get row count
$admission_row_count = $stmt->rowCount();

?>


<script>
$(function () {
  //triggered when modal is about to be shown
  $("#dispositionModal").on("show.bs.modal", function (e) {
    //get data-id attribute of the clicked element
    var admissionPatientId = $(e.relatedTarget).data("id");
	var admissionId = $(e.relatedTarget).data("admitid");
    var patientName = $(e.relatedTarget).data("name");
    //populate the form
    $(e.currentTarget).find(".admissionnameDisplay").text(patientName);
	$(e.currentTarget).find(".admissionIDDisplay").text(admissionPatientId);
    $(e.currentTarget).find('input[name="patient_id"]').val(admissionPatientId);
	$(e.currentTarget).find('input[name="theadmissionid"]').val(admissionId);
  });
});
</script>



<!-- Display patients from the database
<div class="card shadow mb-4" id="databasetable">
<div class="card-header py-3">
<h6 class="m-0 font-weight-bold text-primary"><?php echo $lang['ARC_YOU_CARED_FOR']; ?> <?php echo $admission_row_count; ?> <?php echo $lang['ARC_PATIENTS_IN_RESCUE']; ?> </h6>
</div>
          
<div class="card-body"> -->




    <div class="content-block">
<div class="table-responsive">
<table class="display compact" id="allpttable" width="100%" cellspacing="0">
  <thead class="thead-dark">
    <tr>
    <th><?php echo $lang['DATE_OF']; ?><br><?php echo $lang['ADMISSION']; ?></th>                   
	<th class="align-middle"><?php echo $lang['PATIENT']; ?></th>
    <th class="align-middle" width="200"><?php echo $lang['PRESENTING_COMPLAINT']; ?></th>
	<th class="align-middle"><?php echo $lang['WRA']; ?> <?php echo $lang['PAT_SCORE']; ?></th>  
	<th class="align-middle"><?php echo $lang['DISPOSITION']; ?></th>
    <th class="align-middle"><?php echo $lang['DATE_OF']; ?><br><?php echo $lang['DISPOSITION']; ?></th>
    <th class="align-middle"><?php echo $lang['PAT_DAYS_IN_CARE']; ?><br></th>
    <th></th>
    </tr>
  </thead>
  <tbody>
     <?php			
      //Loop from admissions table
      $stmt = $pdo->prepare("SELECT *,
			DATEDIFF(rescue_admissions.disposition_date, rescue_admissions.admission_date) AS daysincare
      FROM rescue_admissions
      INNER JOIN rescue_patients
      ON rescue_admissions.patient_id = rescue_patients.patient_id
      WHERE rescue_patients.centre_id = :centre_id 
      ORDER by admission_id ASC");
      $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);

      // initialise an array for the results
      $admissions = array();
      $stmt->execute();
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $admission_id = $row["admission_id"];
      $admission_patient_id = $row["patient_id"];
      $admission_date = $row["admission_date"];
      $admission_name = $row["name"];
      $admission_animal_type = $row["animal_type"];
      $admission_animal_species = $row["animal_species"];
      $admission_sex = $row["sex"];
      $admission_presenting_complaint = $row["presenting_complaint"];
      $admission_disposition = $row["disposition"];
      $admission_disposition_date = $row["disposition_date"];

      $admission_date = $row["admission_date"];
	    $days = $row["daysincare"];

    // CALCULATES THE WRA SCORE 
      $bcs = $row["bc_score"];
      $as = $row["age_score"];
      $ss = $row["severity_score"];
      $wra = ($bcs + $as) + $ss;
                                
		$adm_format_date = new DateTime($admission_date);
   	$adm_format_date = $adm_format_date->format('d-m-Y <\b\r> H:i'); 
								
      $dis_format_date = new DateTime($admission_disposition_date);
   		$dis_format_date = $dis_format_date->format('d-m-Y <\b\r> H:i'); 


			// TRAFFIC LIGHT SYSTEM FOR DAYS IN CARE COLOURS
 			if ($days > 120 ) {
     	$daysclass = 'table-dark';
 			} elseif ($days > 90) {
			$daysclass = 'table-danger';
  		} elseif ($days > 60) {
    	$daysclass = 'table-warning';
  		} elseif ($days > 31) {
      $daysclass = 'table-primary';
 			} elseif ($days <= 31) {
     	$daysclass = 'table-success';
  		}
                   
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
        <td><?php echo $adm_format_date; ?></td>
		<td class="align-middle">CRN: <?php echo $admission_patient_id; ?> - <b><?php echo $admission_name; ?></b> (<?php echo $admission_sex; ?>)<BR><?php echo $admission_animal_species; ?> (<?php echo  $admission_animal_type; ?>)</td>
		<td class="align-middle"><?php echo $admission_presenting_complaint; ?></td> 
		<td class="align-middle <?php echo $wraclass; ?>"><center><strong><h5><?php echo $wra; ?></center></strong></h5></td> 
		<td class="align-middle"><?php echo $admission_disposition; ?></td> 
		<td class="align-middle"><?php echo $dis_format_date; ?></td> 
        <td class="align-middle <?php echo $daysclass; ?>"><center><h4><?php echo $days; ?><BR></h4></center></td>

        
        <td class="align-middle">
<!-- icon button group -->
								
	        <div class="btn-group">
			<button type="button" class="btn btn-danger" data-toggle="modal" data-target="#dispositionModal" data-admitid="<?php echo $admission_id; ?>" data-id="<?php echo $admission_patient_id; ?>" data-name="<?php echo $admission_name; ?>"data-toggle="tooltip" data-placement="top" title="Discharge"><i class="fas fa-sign-out-alt"></i></button>
	        <a href="viewpatient.php?patient_id=<?php echo $admission_patient_id; ?>" type="button" class="btn green" data-toggle="tooltip" data-placement="top" title="Manage Patient Record">Care Plan</a>				
            </div>
					<?php } ?> 
				</td>
      </tbody>
  </table>

			
<?php include ("care_plans/add_disposition.php");  ?>
       
	
        <!------------------------------------------------------->
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
<link href="DataTables/datatables.min.css" rel="stylesheet">
<link href="https://rescuecentre.org.uk/wp-content/themes/brikk-child/vendor/datatables/datatables.css" rel="stylesheet">
<script src="DataTables/datatables.min.js"></script>

<script>
new DataTable('#allpttable', {
  
   layout: {
        bottomEnd: {
            paging: {
                firstLast: false
            }
        }
    }

	 });
</script>