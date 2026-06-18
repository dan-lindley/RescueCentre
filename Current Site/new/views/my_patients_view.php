<?php 
//Row Count
$sql = "SELECT * 
FROM rescue_admissions
INNER JOIN rescue_patients
ON rescue_admissions.patient_id = rescue_patients.patient_id
WHERE rescue_admissions.disposition = 'Held in captivity' AND rescue_patients.centre_id = :centre_id
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
  $("#careNotesModal").on("show.bs.modal", function (e) {
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
    <h6 class="m-0 font-weight-bold text-primary"><?php echo $lang['PAT_YOU_HAVE']; ?> <?php echo $admission_row_count; ?> <?php echo $lang['PAT_IN_RESCUE']; ?> </h6>
      <Br> <!--<a href="https://rescuecentre.org.uk/new_admission/" class="btn btn-outline-success"><//?php echo $lang['LM_NEW_ADMISSION']; ?></a>-->
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
      <th width="50" class="align-middle text-center" colspan="2">WRA <?php echo $lang['PAT_SCORE']; ?></th>  
			<th width="50"rowspan="2"></th>
      <!--<th width="350"rowspan="2"></th>-->
    </tr>
    <tr>
      <th class="text-center"><h7><?php echo $lang['ADMISSION']; ?></h7></th>
      <th class="text-center"><h7><?php echo $lang['CURRENT']; ?></h7></th>
    </tr>  
    </thead>
 
    <tbody>
     <?php			
      //Loop from admissions table
      $stmt = $pdo->prepare("SELECT rescue_admissions.admission_id
     , rescue_admissions.presenting_complaint
	 , rescue_admissions.admission_date
	 , rescue_admissions.current_location
	 , rescue_admissions.bc_score
	 , rescue_admissions.age_score
	 , rescue_admissions.severity_score
     , rescue_observations.obs_date
     , rescue_observations.obs_severity_score
     , rescue_observations.obs_bcs_score
     , rescue_observations.obs_age_score
     , rescue_observations.obs_bcs_text
	 , rescue_patients.name
	 , rescue_patients.sex
	 , rescue_patients.animal_species
  , rescue_patients.animal_type
  , rescue_patients.patient_id
, DATEDIFF(NOW(), rescue_admissions.admission_date) AS daysincare
FROM rescue_admissions
LEFT JOIN
(
    SELECT ROW_NUMBER() OVER(PARTITION BY O.patient_id ORDER BY O.obs_date DESC) RowNumber
         , O.*
    FROM rescue_observations O
) rescue_observations
	ON rescue_admissions.patient_id = rescue_observations.patient_id
    AND rescue_observations.RowNumber = 1

LEFT JOIN
rescue_patients
ON rescue_admissions.patient_id = rescue_patients.patient_id
      WHERE rescue_patients.centre_id = :centre_id AND rescue_admissions.disposition = 'Held in captivity' 
      ORDER by daysincare DESC, current_location ASC");
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
      $admission_weight = $row["weight"];
	    $admission_location = $row["current_location"];
      $admission_date = $row["admission_date"];
	    $days = $row["daysincare"];
      
      //CALCULATES WRA SCORE
      $bcs = $row["bc_score"];
      $as = $row["age_score"];
      $ss = $row["severity_score"];
      $wra = ($bcs + $as) + $ss;

      //latest WRA
      $newbcstext = $row["obs_bcs_text"];
            if (empty($newbcstext)) {
            $nullifier = 99;
      } elseif (!empty($newbcstext)) {
            $nullifier = 0 ; 
      }

      $newbcs = $row["obs_bcs_score"];
      $newss = $row["obs_severity_score"];
      $newage = $row["obs_age_score"];
      $newwra = ($newbcs + $newage) + $newss + $nullifier; 

			$adm_format_date = new DateTime($admission_date);
   		$adm_format_date = $adm_format_date->format('d-m-Y <\b\r> H:i'); 
								
			// TRAFFIC LIGHT SYSTEM FOR DAYS IN CARE COLOURS
 			/*if ($days > 120 ) {
     	$daysclass = 'table-dark';
 			} elseif ($days > 90) {
			$daysclass = 'table-danger';
  		} elseif ($days > 60) {
    	$daysclass = 'table-warning';
  		} elseif ($days > 31) {
      $daysclass = 'table-primary';
 			} elseif ($days <= 31) {
     	$daysclass = 'table-success';
  		}*/

      // PHP 8 match:
      $daysclass = match (true) {
      $days > 120 => 'table-dark',
      $days > 90  => 'table-danger',
      $days > 60  => 'table-warning',
      $days > 31  => 'table-primary',
      $days <= 31 => 'table-success'
    };             
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

      // TRAFFIC LIGHT SYSTEM FOR NEW WRA score
 	  if ($newwra > 90 ) {
      $newwraclass = '';
      $newwra = "N/A";
      } elseif ($newwra >= 6) {
      $newwraclass = 'table-danger';
      } elseif ($newwra >= 3) {
      $newwraclass = 'table-warning';
      } elseif ($newwra < 3) {
      $newwraclass = 'table-success';
      } 

      //set the patient id if it is empty to the admission patient id
      {
        $patient_id = $admission_patient_id;
      }
      
	?>
      <tr>
        <td class="align-middle clickable-row" data-href="https://rescuecentre.org.uk/view-patient/?patient_id=<?php echo $admission_patient_id; ?>"><h6>CRN: <?php echo $admission_patient_id; ?> - <b><?php echo $admission_name; ?></b> (<?php echo $admission_sex; ?>)<BR><?php echo $admission_animal_species; ?> (<?php echo  $admission_animal_type; ?>)</h6></td>
        <td><?php echo $adm_format_date; ?></td>
        
				<td class="align-middle red <?php echo $daysclass; ?>"><center><h5><?php echo $days; ?></h5></center></td>
        <td class="align-middle"><?php echo $admission_location; ?></td>
			 
        <td class="align-middle"><?php echo $admission_presenting_complaint; ?></td> 
        <td class="align-middle <?php echo $wraclass; ?>"><center><strong><h5><?php echo $wra; ?></center></strong></h5></td>
        <td class="align-middle <?php echo $newwraclass; ?>"> <center><strong><h5><?php echo $newwra; ?></center></strong></h5></td>
				<td class="align-middle"><div class="btn red"><button type="button" class="btn btn red" data-toggle="modal" data-target="#dispositionModal" data-admitid="<?php echo $admission_id; ?>" data-id="<?php echo $admission_patient_id; ?>" data-name="<?php echo $admission_name; ?>"data-toggle="tooltip" data-placement="top" title="Discharge">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M569 337C578.4 327.6 578.4 312.4 569 303.1L425 159C418.1 152.1 407.8 150.1 398.8 153.8C389.8 157.5 384 166.3 384 176L384 256L272 256C245.5 256 224 277.5 224 304L224 336C224 362.5 245.5 384 272 384L384 384L384 464C384 473.7 389.8 482.5 398.8 486.2C407.8 489.9 418.1 487.9 425 481L569 337zM224 160C241.7 160 256 145.7 256 128C256 110.3 241.7 96 224 96L160 96C107 96 64 139 64 192L64 448C64 501 107 544 160 544L224 544C241.7 544 256 529.7 256 512C256 494.3 241.7 480 224 480L160 480C142.3 480 128 465.7 128 448L128 192C128 174.3 142.3 160 160 160L224 160z"/></svg>
        </button></div></td>
    </tr><tr> 
        <td colspan="8" class="align-middle">
<!-- icon button group -->
	      <div class="btn-group">
	        <a href="viewpatient.php?patient_id=<?php echo $admission_patient_id; ?>" type="button" class="btn green" data-toggle="tooltip" data-placement="top" title="Manage Patient Record">Care plan</a>				
          
          <button id="carenotesBtn" type="button" onclick='careNotes(this)' class="btn blue"  data-target="#careNotes" data-id="<?php echo $admission_patient_id; ?>" data-name="<?php echo $admission_name; ?>"data-toggle="tooltip" data-placement="top" title="Add a care note">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M320 544C461.4 544 576 436.5 576 304C576 171.5 461.4 64 320 64C178.6 64 64 171.5 64 304C64 358.3 83.2 408.3 115.6 448.5L66.8 540.8C62 549.8 63.5 560.8 70.4 568.3C77.3 575.8 88.2 578.1 97.5 574.1L215.9 523.4C247.7 536.6 282.9 544 320 544zM281.6 217.6C281.6 207 290.2 198.4 300.8 198.4L339.2 198.4C349.8 198.4 358.4 207 358.4 217.6L358.4 265.6L406.4 265.6C417 265.6 425.6 274.2 425.6 284.8L425.6 323.2C425.6 333.8 417 342.4 406.4 342.4L358.4 342.4L358.4 390.4C358.4 401 349.8 409.6 339.2 409.6L300.8 409.6C290.2 409.6 281.6 401 281.6 390.4L281.6 342.4L233.6 342.4C223 342.4 214.4 333.8 214.4 323.2L214.4 284.8C214.4 274.2 223 265.6 233.6 265.6L281.6 265.6L281.6 217.6z"/></svg>
          </button>
          
          <button id="observationBtn" type="button" class="btn blue" data-toggle="modal"  data-target="#observationsModal" data-id="<?php echo $admission_patient_id; ?>" data-name="<?php echo $admission_name; ?>" data-toggle="tooltip" data-placement="top" title="Add an observation">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M439.4 96L448 96C483.3 96 512 124.7 512 160L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 160C128 124.7 156.7 96 192 96L200.6 96C211.6 76.9 232.3 64 256 64L384 64C407.7 64 428.4 76.9 439.4 96zM376 176C389.3 176 400 165.3 400 152C400 138.7 389.3 128 376 128L264 128C250.7 128 240 138.7 240 152C240 165.3 250.7 176 264 176L376 176zM256 320C256 302.3 241.7 288 224 288C206.3 288 192 302.3 192 320C192 337.7 206.3 352 224 352C241.7 352 256 337.7 256 320zM288 320C288 333.3 298.7 344 312 344L424 344C437.3 344 448 333.3 448 320C448 306.7 437.3 296 424 296L312 296C298.7 296 288 306.7 288 320zM288 448C288 461.3 298.7 472 312 472L424 472C437.3 472 448 461.3 448 448C448 434.7 437.3 424 424 424L312 424C298.7 424 288 434.7 288 448zM224 480C241.7 480 256 465.7 256 448C256 430.3 241.7 416 224 416C206.3 416 192 430.3 192 448C192 465.7 206.3 480 224 480z"/></svg>
          </button> 
          
          <button id="medicationBtn" type="button" class="btn blue" data-toggle="modal"  data-target="#medicationModal" data-id="<?php echo $admission_patient_id; ?>" data-name="<?php echo $admission_name; ?>" data-toggle="tooltip" data-placement="top" title="Medications">
           <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M529.5 47C520.1 37.6 504.9 37.6 495.6 47C486.3 56.4 486.2 71.6 495.6 80.9L510.6 95.9L464.5 142L401.5 79C392.1 69.6 376.9 69.6 367.6 79C358.3 88.4 358.2 103.6 367.6 112.9L374.6 119.9L296.5 198L337.5 239C346.9 248.4 346.9 263.6 337.5 272.9C328.1 282.2 312.9 282.3 303.6 272.9L262.6 231.9L216.5 278L257.5 319C266.9 328.4 266.9 343.6 257.5 352.9C248.1 362.2 232.9 362.3 223.6 352.9L182.6 311.9L144.9 349.6C134.4 360.1 128.5 374.3 128.5 389.2L128.5 478L71.5 535C62.1 544.4 62.1 559.6 71.5 568.9C80.9 578.2 96.1 578.3 105.4 568.9L162.4 511.9L251.2 511.9C266.1 511.9 280.3 506 290.8 495.5L520.5 265.8L527.5 272.8C536.9 282.2 552.1 282.2 561.4 272.8C570.7 263.4 570.8 248.2 561.4 238.9L498.4 175.9L544.5 129.8L559.5 144.8C568.9 154.2 584.1 154.2 593.4 144.8C602.7 135.4 602.8 120.2 593.4 110.9L529.4 46.9z"/></svg>
          </button>
	        
          <button id="treatmentBtn" type="button" class="btn blue" data-toggle="modal" data-target="#treatmentModal" data-id="<?php echo $admission_patient_id; ?>" data-name="<?php echo $admission_name; ?>" data-toggle="tooltip" data-placement="top" title="Add a treatment">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M160 141.3C160 134 165.9 128 173.3 128C176.8 128 180.2 129.4 182.7 131.9L197.6 146.8C194 155.9 192.1 165.7 192.1 176C192.1 195.9 199.3 214 211.3 228C206 237.2 207.3 249.1 215.1 257C224.5 266.4 239.7 266.4 249 257L353 153C362.4 143.6 362.4 128.4 353 119.1C345.2 111.2 333.2 110 324 115.3C310 103.3 291.9 96.1 272 96.1C261.7 96.1 251.8 98.1 242.8 101.6L227.9 86.6C213.4 72.1 193.7 64 173.3 64C130.6 64 96 98.6 96 141.3L96 320C78.3 320 64 334.3 64 352C64 369.7 78.3 384 96 384L96 432C96 460.4 108.4 486 128 503.6L128 544C128 561.7 142.3 576 160 576C177.7 576 192 561.7 192 544L192 528L448 528L448 544C448 561.7 462.3 576 480 576C497.7 576 512 561.7 512 544L512 503.6C531.6 486 544 460.5 544 432L544 384C561.7 384 576 369.7 576 352C576 334.3 561.7 320 544 320L160 320L160 141.3z"/></svg>
          </button>	
          
          <button id="labsBtn" type="button" class="btn blue" data-toggle="modal" data-target="#labsModal" data-id="<?php echo $admission_patient_id; ?>" data-admission="<?php echo $admission_id; ?>" data-name="<?php echo $admission_name; ?>" data-toggle="tooltip" data-placement="top" title="Add lab result"><i class="fas fa-flask" ></i>
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M96 64C78.3 64 64 78.3 64 96C64 113.7 78.3 128 96 128L96 480C96 533 139 576 192 576C245 576 288 533 288 480L288 128L352 128L352 480C352 533 395 576 448 576C501 576 544 533 544 480L544 128C561.7 128 576 113.7 576 96C576 78.3 561.7 64 544 64L96 64zM224 128L224 256L160 256L160 128L224 128zM480 128L480 256L416 256L416 128L480 128z"/></svg>
          </button>	
		   
        </div>
        <div class="btn-group">	
	        <button id="weightBtn" type="button" class="btn grey" data-toggle="modal" data-target="#weightModal" data-id="<?php echo $admission_patient_id; ?>" data-name="<?php echo $admission_name; ?>"data-toggle="tooltip" data-placement="top" title="Add weight">
          <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M212.6 256C209.6 245.9 208 235.1 208 224C208 162.1 258.1 112 320 112C381.9 112 432 162.1 432 224C432 235.1 430.4 245.9 427.4 256L356.4 256L381 211.7C387.4 200.1 383.3 185.5 371.7 179.1C360.1 172.7 345.5 176.8 339.1 188.4L301.5 256.1L212.7 256.1zM224 96L160 96C124.7 96 96 124.7 96 160L96 480C96 515.3 124.7 544 160 544L480 544C515.3 544 544 515.3 544 480L544 160C544 124.7 515.3 96 480 96L416 96C389.3 75.9 356 64 320 64C284 64 250.7 75.9 224 96z"/></svg>
        </button>

        <button id="measurementBtn" type="button" class="btn grey" data-toggle="modal" data-target="#measurementModal" data-id="<?php echo $admission_patient_id; ?>" data-name="<?php echo $admission_name; ?>"data-toggle="tooltip" data-placement="top" title="Add Measurement">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M80 448C53.5 448 32 426.5 32 400L32 240C32 213.5 53.5 192 80 192L104 192L104 296C104 309.3 114.7 320 128 320C141.3 320 152 309.3 152 296L152 192L200 192L200 264C200 277.3 210.7 288 224 288C237.3 288 248 277.3 248 264L248 192L296 192L296 296C296 309.3 306.7 320 320 320C333.3 320 344 309.3 344 296L344 192L392 192L392 264C392 277.3 402.7 288 416 288C429.3 288 440 277.3 440 264L440 192L488 192L488 296C488 309.3 498.7 320 512 320C525.3 320 536 309.3 536 296L536 192L560 192C586.5 192 608 213.5 608 240L608 400C608 426.5 586.5 448 560 448L80 448z"/></svg>
        </button>
        </div>
    </td></tr>
    
					<?php ; } ?> 
	
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
<?php include ("controllers/care_notes_modal.php"); ?>

<?php include "views/wra_explained.php"?>

<?php /*include ("care_plans/add_carenote.php"); 
			include ("care_plans/add_treatment.php");
		  include ("care_plans/add_medsadmin.php");
 		  include ("care_plans/add_weight.php"); 
      include ("care_plans/add_labs.php"); 
      include ("care_plans/add_measurement.php"); 
      include ("care_plans/add_observation.php"); 

      include ("care_plans/wra_score_explained.php"); 
      include ("care_plans/add_disposition.php"); */ ?>



<script>			
jQuery(document).ready(function($) {
    $(".clickable-row").click(function() {
        window.location = $(this).data("href");
    });
});
</script>
