<?php 
//Row Count
$sql = "SELECT * 
FROM rescue_admissions
INNER JOIN rescue_patients
ON rescue_admissions.patient_id = rescue_patients.patient_id
WHERE rescue_admissions.disposition = 'Long-term Captive' AND rescue_patients.centre_id = :centre_id
ORDER by `admission_date` DESC";
$stmt = $conn->prepare($sql);

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
  $("#carenotesModal").on("show.bs.modal", function (e) {
    //get data-id attribute of the clicked element
    var admissionPatientId = $(e.relatedTarget).data("id");
	  var patientName = $(e.relatedTarget).data("name");

    //populate the form
    $(e.currentTarget).find(".admissionnameDisplay").text(patientName);
	$(e.currentTarget).find(".admissionIDDisplay").text(admissionPatientId);
    $(e.currentTarget).find('input[name="patient_id"]').val(admissionPatientId);
  });
});
</script>
<script>
$(function () {
  //triggered when modal is about to be shown
  $("#treatmentModal").on("show.bs.modal", function (e) {
    //get data-id attribute of the clicked element
    var admissionPatientId = $(e.relatedTarget).data("id");
	var patientName = $(e.relatedTarget).data("name");

    //populate the form
    $(e.currentTarget).find(".admissionnameDisplay").text(patientName);
    $(e.currentTarget).find(".admissionIDDisplay").text(admissionPatientId);
    $(e.currentTarget).find('input[name="patient_id"]').val(admissionPatientId);
  });
});
</script>
<script>
$(function () {
  //triggered when modal is about to be shown
  $("#observationsModal").on("show.bs.modal", function (e) {
    //get data-id attribute of the clicked element
    var admissionPatientId = $(e.relatedTarget).data("id");
	var patientName = $(e.relatedTarget).data("name");

    //populate the form
    $(e.currentTarget).find(".admissionnameDisplay").text(patientName);
    $(e.currentTarget).find(".admissionIDDisplay").text(admissionPatientId);
    $(e.currentTarget).find('input[name="patient_id"]').val(admissionPatientId);
  });
});
</script>
<script>
$(function () {
  //triggered when modal is about to be shown
  $("#medicationModal").on("show.bs.modal", function (e) {
    //get data-id attribute of the clicked element
    var admissionPatientId = $(e.relatedTarget).data("id");
    var patientName = $(e.relatedTarget).data("name");
    //populate the form
    $(e.currentTarget).find(".admissionnameDisplay").text(patientName);
	  $(e.currentTarget).find(".admissionIDDisplay").text(admissionPatientId);
    $(e.currentTarget).find('input[name="patient_id"]').val(admissionPatientId);
  });
});
</script>
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

<script>
$(function () {
  //triggered when modal is about to be shown
  $("#weightModal").on("show.bs.modal", function (e) {
    //get data-id attribute of the clicked element
    var admissionPatientId = $(e.relatedTarget).data("id");
    var patientName = $(e.relatedTarget).data("name");
    //populate the form
    $(e.currentTarget).find(".admissionnameDisplay").text(patientName);
	$(e.currentTarget).find(".admissionIDDisplay").text(admissionPatientId);
    $(e.currentTarget).find('input[name="weight_thepatientid"]').val(admissionPatientId);
  });
});
</script>

<script>
$(function () {
  //triggered when modal is about to be shown
  $("#measurementModal").on("show.bs.modal", function (e) {
    //get data-id attribute of the clicked element
    var admissionPatientId = $(e.relatedTarget).data("id");
    var patientName = $(e.relatedTarget).data("name");
    //populate the form
    $(e.currentTarget).find(".admissionnameDisplay").text(patientName);
	$(e.currentTarget).find(".admissionIDDisplay").text(admissionPatientId);
    $(e.currentTarget).find('input[name="measurement_thepatientid"]').val(admissionPatientId);
  });
});
</script>
<script>
$(function () {
  //triggered when modal is about to be shown
  $("#labsModal").on("show.bs.modal", function (e) {
    //get data-id attribute of the clicked element
    var admissionPatientId = $(e.relatedTarget).data("id");
    var patientName = $(e.relatedTarget).data("name");
    var admissionId = $(e.relatedTarget).data("admission");
    //populate the form
    $(e.currentTarget).find(".admissionnameDisplay").text(patientName);
	$(e.currentTarget).find(".admissionIDDisplay").text(admissionPatientId);
    $(e.currentTarget).find('input[name="patient_id"]').val(admissionPatientId);
    $(e.currentTarget).find('input[name="admission_id"]').val(admissionId);
  });
});
</script>


<!-- Display patients from the database -->
<div class="card shadow mb-4" id="databasetable">
<div class="card-header py-3">
<h6 class="m-0 font-weight-bold text-primary"><?php echo $lang['PAT_YOU_HAVE']; ?> <?php echo $admission_row_count; ?> <?php echo $lang['PAT_RESIDENTS']; ?> </h6>
<Br> <!--<a href="https://rescuecentre.org.uk/new_admission/" class="btn btn-outline-success"><//?php echo $lang['LM_NEW_ADMISSION']; ?></a>-->
<button type="button" class="btn btn-outline-success" data-toggle="modal" data-target="#wraModal" data-toggle="tooltip" data-placement="top" title="Wildlife Rapid Assessment Score Explained">WRA Score Explained</button>
</div>
            
<div class="card-body">
<div class="table-responsive">
<table class="table table-bordered table-sm table-hover" id="admittable" width="100%" cellspacing="0">
  <thead class="thead-dark">
    <tr>
      <th class="align-middle"rowspan="2"><?php echo $lang['PATIENT']; ?></th>
      <th width="120" rowspan="2"><?php echo $lang['DATE_OF']; ?><br><?php echo $lang['ADMISSION']; ?></th>                   
			<th width="75" class="align-middle"rowspan="2" ><?php echo $lang['PAT_DAYS_IN_CARE']; ?></th>
      <th class="align-middle" width="150"rowspan="2"><?php echo $lang['PAT_LOCATION']; ?></th>
			
      <th class="align-middle"rowspan="2"><?php echo $lang['PRESENTING_COMPLAINT']; ?></th>
      <th width="50" class="align-middle" colspan="2">WRA <?php echo $lang['PAT_SCORE']; ?></th>  
			<th width="50"rowspan="2"></th>
      <!--<th width="350"rowspan="2"></th>-->
    </tr>
    <tr>
    <th><figcaption class="figure-caption">Admission</figcaption></th>
    <th><figcaption class="figure-caption">Current</figcaption></th>
  </thead>
  <tbody>
     <?php			
      //Loop from admissions table
      $stmt = $conn->prepare("SELECT *,
			DATEDIFF(NOW(), rescue_admissions.admission_date) AS daysincare
      FROM rescue_admissions
      INNER JOIN rescue_patients
      ON rescue_admissions.patient_id = rescue_patients.patient_id
      WHERE rescue_patients.centre_id = :centre_id AND rescue_admissions.disposition = 'Long-term Captive' 
      ORDER by daysincare DESC, current_location ASC");
      $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);

      // initialise an array for the results
      $residents = array();
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
      $admission_starved = $row["starved"];
      $admission_dehydrated = $row["dehydrated"];
      $admission_weight = $row["weight"];
      $admission_weight_unit = $row["weight_unit"];
      $admission_measurement = $row["measurement"];
			$admission_location = $row["current_location"];
      $admission_measurement_unit = $row["measurement_unit"];
			$admission_time_to_admission = $row["time_to_admission"];
      $admission_date = $row["admission_date"];
			$days = $row["daysincare"];
      
      //CALCULATES WRA SCORE
      $bcs = $row["bc_score"];
      $as = $row["age_score"];
      $ss = $row["severity_score"];
      $wra = ($bcs + $as) + $ss;
                                
			$adm_format_date = new DateTime($admission_date);
   		$adm_format_date = $adm_format_date->format('d-m-Y <\b\r> H:i'); 
								
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
        <td class="align-middle"><h6>CRN: <?php echo $admission_patient_id; ?> - <b><?php echo $admission_name; ?></b> (<?php echo $admission_sex; ?>)<BR><?php echo $admission_animal_species; ?> (<?php echo  $admission_animal_type; ?>)</h6></td>
        <td><?php echo $adm_format_date; ?></td>
        
				<td class="align-middle <?php echo $daysclass; ?>"><center><h4><?php echo $days; ?></h4></center></td>
        <td class="align-middle"><?php echo $admission_location; ?></td>
			 
        <td class="align-middle"><?php echo $admission_presenting_complaint; ?></td> 
        <td class="align-middle <?php echo $wraclass; ?>"><center><strong><h5><?php echo $wra; ?></center></strong></h5></td>
        <td>news score</td>
				<td class="align-middle"><div class="btn-group"><button type="button" class="btn btn-danger" data-toggle="modal" data-target="#dispositionModal" data-admitid="<?php echo $admission_id; ?>" data-id="<?php echo $admission_patient_id; ?>" data-name="<?php echo $admission_name; ?>"data-toggle="tooltip" data-placement="top" title="Discharge"><i class="fas fa-sign-out-alt"></i></button></div></td>
    </tr><tr> 
        <td colspan="8" class="align-middle">
<!-- icon button group -->
								
	        <div class="btn-group">
	        <a href="https://rescuecentre.org.uk/view-patient/?patient_id=<?php echo $admission_patient_id; ?>" type="button" class="btn btn-success" data-toggle="tooltip" data-placement="top" title="Manage Patient Record"><i class="fas fa-clipboard" ></i></a>				
          <button type="button" class="btn btn-info" data-toggle="modal" data-target="#carenotesModal" data-id="<?php echo $admission_patient_id; ?>" data-name="<?php echo $admission_name; ?>"data-toggle="tooltip" data-placement="top" title="Add a care note"><i class="fas fa-notes-medical" ></i></button>
          <button type="button" class="btn btn-info" data-toggle="modal"  data-target="#observationsModal" data-id="<?php echo $admission_patient_id; ?>" data-name="<?php echo $admission_name; ?>" data-toggle="tooltip" data-placement="top" title="Add an observation"><i class="fas fa-eye"></i></button>
          
          <button type="button" class="btn btn-info" data-toggle="modal"  data-target="#medicationModal" data-id="<?php echo $admission_patient_id; ?>" data-name="<?php echo $admission_name; ?>" data-toggle="tooltip" data-placement="top" title="Medications"><i class="fas fa-syringe" ></i></button>
 	        
	        <button type="button" class="btn btn-info" data-toggle="modal" data-target="#treatmentModal" data-id="<?php echo $admission_patient_id; ?>" data-name="<?php echo $admission_name; ?>" data-toggle="tooltip" data-placement="top" title="Add a treatment"><i class="fas fa-bath" ></i></button>	
          <button type="button" class="btn btn-info" data-toggle="modal" data-target="#labsModal" data-id="<?php echo $admission_patient_id; ?>" data-admission="<?php echo $admission_id; ?>" data-name="<?php echo $admission_name; ?>" data-toggle="tooltip" data-placement="top" title="Add lab result"><i class="fas fa-flask" ></i></button>	
		      </div>
          <div class="btn-group">	
	        <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#weightModal" data-id="<?php echo $admission_patient_id; ?>" data-name="<?php echo $admission_name; ?>"data-toggle="tooltip" data-placement="top" title="Add weight"><i class="fas fa-weight"></i></button>
	        <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#measurementModal" data-id="<?php echo $admission_patient_id; ?>" data-name="<?php echo $admission_name; ?>"data-toggle="tooltip" data-placement="top" title="Add Measurement"><i class="fas fa-ruler"></i></button>
          </div>
					</div>
					<?php } ?> 
				</td>
      </tbody>
  </table>

				 <table class="table table-bordered table-sm table-hover" id="admittable" width="100%" cellspacing="0">
            <thead class="thead-light">
							<th colspan="5"><?php echo $lang['PAT_KEY_TO_DAYS']; ?></th></thead>
                            <tr>
								<TD class="table-success"><?php echo $lang['PAT_LESS_THAN']; ?> 31 <?php echo $lang['DAYS']; ?></TD>
								<td class="table-primary">31 <?php echo $lang['TO']; ?> 60 <?php echo $lang['DAYS']; ?></td>
								<td class="table-warning">61 <?php echo $lang['TO']; ?> 90 <?php echo $lang['DAYS']; ?></td>
								<td class="table-danger">90 <?php echo $lang['TO']; ?> 120 <?php echo $lang['DAYS']; ?></td>
								<td class="table-dark"><?php echo $lang['PAT_MORE_THAN']; ?> 120 <?php echo $lang['DAYS']; ?></td>
							</tr>
				</table>
                </div>
            </div>
        </div>

<?php include ("care_plans/add_carenote.php"); 
			include ("care_plans/add_treatment.php");
		  include ("care_plans/add_medsadmin.php");
 		  include ("care_plans/add_weight.php"); 
      include ("care_plans/add_labs.php"); 
      include ("care_plans/add_measurement.php"); 
      include ("care_plans/add_observation.php"); 

      include ("care_plans/wra_score_explained.php"); 
      include ("care_plans/add_disposition.php");  ?>
       
				
        <!------------------------------------------------------->