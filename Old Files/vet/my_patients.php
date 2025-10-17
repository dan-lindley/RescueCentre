
<!-- Display patients from the database -->
<div class="card shadow mb-4" id="databasetable">
  <div class="card-header py-3">
    <h6 class="m-0 font-weight-bold text-primary">All Patients linked to your practice </h6>
      <Br> <!--<a href="https://rescuecentre.org.uk/new_admission/" class="btn btn-outline-success"><//?php echo $lang['LM_NEW_ADMISSION']; ?></a>-->
      <button type="button" class="btn btn-outline-success" data-toggle="modal" data-target="#wraModal" data-toggle="tooltip" data-placement="top" title="Wildlife Rapid Assessment Score Explained">WRA Score Explained</button>
  </div>
            
<div class="card-body">
  <div class="table-responsive">
     <?php			
      //Loop from admissions table
      $stmt = $conn->prepare("SELECT 
      rescue_centres.rescue_name,
rescue_patients.centre_id, 
rescue_admissions.presenting_complaint, 
rescue_admissions.admission_date, 
rescue_admissions.bc_score, 
rescue_admissions.age_score, 
rescue_admissions.severity_score, 
rescue_observations.obs_date, 
rescue_observations.obs_severity_score, 
rescue_observations.obs_bcs_score, 
rescue_observations.obs_age_score, 
rescue_observations.obs_bcs_text,
rescue_patients.name, 
rescue_patients.sex, 
rescue_patients.animal_species, 
rescue_patients.animal_type, 
rescue_patients.patient_id, 
rescue_admissions.patient_id,
rescue_vet_centres.user_id,

DATEDIFF(NOW(), rescue_admissions.admission_date) AS daysincare
FROM rescue_admissions
LEFT JOIN
	(SELECT ROW_NUMBER() OVER(PARTITION BY O.patient_id ORDER BY O.obs_date DESC) RowNumber, O.*
    FROM rescue_observations O) 
    rescue_observations
	ON rescue_admissions.patient_id = rescue_observations.patient_id
    AND rescue_observations.RowNumber = 1
LEFT JOIN
rescue_patients
ON rescue_admissions.patient_id = rescue_patients.patient_id

LEFT JOIN
rescue_vet_centres
ON rescue_vet_centres.centre_id = rescue_patients.centre_id

LEFT JOIN
rescue_centres
ON rescue_vet_centres.centre_id = rescue_centres.rescue_id

WHERE rescue_vet_centres.user_id = :user_id AND rescue_admissions.disposition = 'Held in captivity' 
ORDER by rescue_vet_centres.centre_id, daysincare DESC, current_location ASC");

$stmt->bindParam(':user_id', $wp_id, PDO::PARAM_INT);

// initialise an array for the results
//$allvetpatients = array();
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC); //added in - remove if plan doesnt work!
foreach ($data as $rescuename => $rescue_centre) {
  
     ; ?> 
      
    <table class="table table-bordered table-sm table-hover" id="admittable" width="100%" cellspacing="0">
    <thead class="thead-dark">
    <tr>
      <th class="align-middle"rowspan="2">Centre: <?php echo $rescuename; ?></th>
      <th width="120" rowspan="2"><?php echo $lang['DATE_OF']; ?><br><?php echo $lang['ADMISSION']; ?></th>                   
	  <th width="75" class="align-middle"rowspan="2" ><?php echo $lang['PAT_DAYS_IN_CARE']; ?></th>
      <th class="align-middle"rowspan="2"><?php echo $lang['PRESENTING_COMPLAINT']; ?></th>
      <th width="50" class="align-middle text-center" colspan="2">WRA <?php echo $lang['PAT_SCORE']; ?></th>  
    </tr>
    <tr>
      <th class="text-center"><h7><?php echo $lang['ADMISSION']; ?></h7></th>
      <th class="text-center"><h7><?php echo $lang['CURRENT']; ?></h7></th>
    </tr>  
    </thead>
<?php ;

    foreach ($rescue_centre as $row) {
      $admission_id = $row["admission_id"];
      $admission_patient_id = $row["patient_id"];
      $admission_date = $row["admission_date"];
      $admission_name = $row["name"];
      $admission_animal_type = $row["animal_type"];
      $admission_animal_species = $row["animal_species"];
      $admission_sex = $row["sex"];
      $admission_presenting_complaint = $row["presenting_complaint"];
      $admission_weight = $row["weight"];
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
        
		<td class="align-middle <?php echo $daysclass; ?>"><center><h4><?php echo $days; ?></h4></center></td>
			 
        <td class="align-middle"><?php echo $admission_presenting_complaint; ?></td> 
        <td class="align-middle <?php echo $wraclass; ?>"><center><strong><h5><?php echo $wra; ?></center></strong></h5></td>
        <td class="align-middle <?php echo $newwraclass; ?>"> <center><strong><h5><?php echo $newwra; ?></center></strong></h5></td>
				
    </tr><tr> 
        <td colspan="8" class="align-middle">
<!-- icon button group -->
	      <div class="btn-group">
	        <a href="https://rescuecentre.org.uk/vet-view-patient/?patient_id=<?php echo $admission_patient_id; ?>" type="button" class="btn btn-success" data-toggle="tooltip" data-placement="top" title="Manage Patient Record"><i class="fas fa-clipboard" ></i> View Care Plan</a>				
          <button type="button" class="btn btn-info" data-toggle="modal" data-target="#carenotesModal" data-id="<?php echo $admission_patient_id; ?>" data-name="<?php echo $admission_name; ?>"data-toggle="tooltip" data-placement="top" title="Add a care note"><i class="fas fa-notes-medical" ></i></button>
          <button type="button" class="btn btn-info" data-toggle="modal"  data-target="#observationsModal" data-id="<?php echo $admission_patient_id; ?>" data-name="<?php echo $admission_name; ?>" data-toggle="tooltip" data-placement="top" title="Add an observation"><i class="fas fa-eye"></i></button> 
          <button type="button" class="btn btn-info" data-toggle="modal"  data-target="#medicationModal" data-id="<?php echo $admission_patient_id; ?>" data-name="<?php echo $admission_name; ?>" data-toggle="tooltip" data-placement="top" title="Medications"><i class="fas fa-syringe" ></i></button>
	        <button type="button" class="btn btn-info" data-toggle="modal" data-target="#treatmentModal" data-id="<?php echo $admission_patient_id; ?>" data-name="<?php echo $admission_name; ?>" data-toggle="tooltip" data-placement="top" title="Add a treatment"><i class="fas fa-bath" ></i></button>	
          <button type="button" class="btn btn-info" data-toggle="modal" data-target="#labsModal" data-id="<?php echo $admission_patient_id; ?>" data-admission="<?php echo $admission_id; ?>" data-name="<?php echo $admission_name; ?>" data-toggle="tooltip" data-placement="top" title="Add lab result"><i class="fas fa-flask" ></i></button>	
		    </div>
      
					
					<?php }  ?> 
				</td><?php } ?>
      </tbody>
  </table>






  <!-- KEY TO DAYS IN CARE -->
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
