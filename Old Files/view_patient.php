<?php
include_once "authentication.php";
include_once "connect_to_mysql.php";
require_once(__DIR__.'/vendor/autoload.php');

//Get logged in user's name 
$user_info = get_userdata(get_current_user_id());
$wp_first_name = $user_info->first_name;
$wp_last_name = $user_info->last_name;
$wp_fullname = "" . $wp_first_name . " " . $wp_last_name . "";


echo "<div class='app_page_container'>";
/**
 * The template for displaying pages
 *
 * Do not overload this file directly. Instead have a look at templates/single.php file in us-core plugin folder:
 * you should find all the needed hooks there.
 */

/* Template Name: View Individual Patient */

get_header();

include_once "app_header.php";


//Retrieve the GET value from the URL, and sanitise it for security purposes
function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if (isset($_GET["patient_id"])) {
    $patient_id = test_input($_GET["patient_id"]);
} else {
    echo "Error #1 - Patient not found.";
    exit();
}

if (isset($_GET["alert"])) {
    $alert = test_input($_GET["alert"]);

    if ($alert = 1) {
        $alertmsg = "<div class='alert alert-success' role='alert'>
        This patient's details were updated in the database.
        </div>";
    } else if ($alert = 2) {
        $alertmsg = "<div class='alert alert-success' role='alert'>
        Alert
        </div>";
    } else {
        $alertmsg = "";
    }
} else {
    $alertmsg = "";
}


//Get the information from the database
$sql = 'SELECT * FROM rescue_patients WHERE patient_id=:patient_id AND centre_id=:centre_id LIMIT 1';
$statement = $conn->prepare($sql);
$statement->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
$statement->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
/*---------------------------------------------------------------------------------*/
if ($result) {
    $patient_name = $result["name"];
    $patient_ringed = $result["ringed"];
    $patient_ring_number = $result["ring_number"];
    $patient_microchipped = $result["microchipped"];
    $patient_microchip_number = $result["microchip_number"];
    $patient_animal_type = $result["animal_type"];
    $patient_animal_order = $result["animal_order"];
    $patient_animal_species = $result["animal_species"];
    $patient_sex = $result["sex"];
    $patient_status = $result["status"];
    $date_added = $result["date_added"];

    $formatted_date = new DateTime($date_added);
    $formatted_date = $formatted_date->format('d-m-Y H:i');
} else {
    echo "The patient ID was not found or does not relate to your rescue";
    exit();
}


/*-------------------------------------------------------- FORM PROCESSING & LOGIC FOR "Edit Admission"  ------------------------------------------------------*/

//Search the database and see if there are any exisitng admissions for this particular patient
try {
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare the SQL statement
    $stmt = $conn->prepare('SELECT * FROM rescue_admissions WHERE patient_id = :id ORDER by admission_date DESC');

    // Bind the ID parameter
    $stmt->bindParam(':id', $patient_id, PDO::PARAM_INT);

    // Execute the query
    $stmt->execute();

    // Fetch the row
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if a row was found
    if ($row) {
        //An admission exists. Output the edit admissions button
        $edit_admission = '<!-- Edit Admission Button -->
        <button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#editAdmission">
        Edit Admission
        </button>
       ';

        //Get all of the details of the current admission from the database
        $currentAdmission_id = $row["admission_id"];
		$currentAdmission_date = $row["admission_date"];
        $currentAdmision_status = $row["status"];
		$currentAdmision_current_location = $row["current_location"];
        $currentAdmission_collection_location = $row["collection_location"];
        $currentAdmission_finder_name = $row["finder_name"];
        $currentAdmission_age_on_admission = $row["age_on_admission"];
        $currentAdmission_presenting_complaint = $row["presenting_complaint"];
        $currentAdmission_dehydrated = $row["dehydrated"];
        $currentAdmission_starved = $row["starved"];
        $currentAdmission_weight = $row["weight"];
        $currentAdmission_weight_unit = $row["weight_unit"];
        $currentAdmission_measurement = $row["measurement"];
        $currentAdmission_measurement_unit = $row["measurement_unit"];
        $currentAdmission_disposition = $row["disposition"];
        $dbpassphrase = $row["passphrase"];
        $dbfindertel = $row["finder_tel"];
        $dbfindername = $row["finder_name"];
    } else {
        //An admission does not exist
    }
} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}

/*------------------------------------------------------------------ FORM PROCESSING - Delete location-------------------------------------------------------------------*/
if (isset($_POST['shareform'])) {

    $patient_id = $_POST["patient_id"];
    $transfer_id = $_POST["transfer_id"];

    try {
        $statement = $conn->prepare('INSERT INTO rescue_patients
            ( 
            patient_id,
	    	transfer_id)
            
            VALUES (
            :patient_id,
	        :transfer_id) 
			
			ON DUPLICATE KEY UPDATE
			transfer_id = :transfer_id	
			');

        $statement->execute([
            'patient_id' => $patient_id,
            'transfer_id' => $transfer_id
			
            
        ]);
		echo "<meta http-equiv='refresh' content='0'>";
		
    } catch (PDOException $e) {
        echo "Database Error: The note could not be added.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The note could not be added.<br>" . $e->getMessage();
        exit();
    }

}

/*------------------------------------------------------------------ END OF FORM PROCESSING -------------------------------------------------------------------*/
?>
<!-- ALL CENTRE ALERTING SYSTEM -->			 					 			 
			 <?php
                $stmt = $conn->prepare("SELECT * FROM rescue_alerts WHERE patient_id = {$patient_id} AND is_active = 'yes' AND is_deleted=0");
                                    // initialise an array for the results
                                    $alerts = array();
                                    $stmt->execute();
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            // initialise an array for the results
                            // $result = $conn->query($sql);
							//foreach($result as $row) {
                                $alert_message = $row["alert_message"];
                                $alert_type = $row["alert_type"];
								$is_closed = $row["is_closed"];
								$date = $row["date"];
							    $url = $row["url"];

									//This displays the alert IF is_closed is empty
									if (empty($is_closed)){	
										
                                print ' <div class="alert ' . $alert_type . ' alert-dismissible fade show" role="alert">
 										' . $date . ' - ' . $alert_message . '.  
										</div>'; }
							 }

                            ?>	
			
	<div class="alert alert-warning mb-3" role="alert">
 		 <?php
         include ("forms/clear_tel.php"); 
                        print '				
	<h4 class="alert-heading"><h1 class="h3 mb-0 text-black-900"><strong>' . $patient_name . '</strong></h1></h4> <strong>' . $patient_animal_type . '</strong> [' . $patient_sex . '] - <U>' . $patient_animal_species . '</u> (' . $patient_animal_order . ')
  <p><strong>CRN (Casualty Reference Number):</strong> ' . $patient_id . ' | Status: ' . $patient_status . ' | Ringed: ' . $patient_ringed . ' - (Number: ' . $patient_ring_number . ') | Microchipped: ' . $patient_microchipped . ' - (Number: ' . $patient_microchip_number . ')  
    <br>Finder: ' . $dbfindername . ' | Tel: ' . $dbfindertel . '  
        
    
    | <strong>Passphrase:</strong> ' . $dbpassphrase . ' </p>

  <hr><div class="row lead_form_row"> 
	 <div class="col-md-6"> 
  <p class="mb-0"><button type="button" class="btn btn-outline-success" data-toggle="modal" data-target="#exampleModal">
                        Edit ' . $patient_name . '&#39s Details
								</button>
								' . $edit_admission . ' <a href="https://rescuecentre.org.uk/print-patient/?patient_id=' . $patient_id . '" rel="noopener" target="_blank" class="btn btn-outline-info"><i class="fas fa-print"></i> Print Care Plan</a></div>
			
                    ';
		include("operations/share.php");
		print '</div></div>';
		?>					
										
<!-- Begin Page Content -->
</div>

<div class="container-fluid">
		
<!-- FULL CARD -->
<div class="col">
    <div class="card shadow mb-4">
        
<!-- SMS FORM -->
<!-- query to get finder details -->
<?php
    $stmt = $conn->prepare("SELECT * 
    FROM rescue_admissions
    INNER JOIN rescue_patients
    ON rescue_admissions.patient_id = rescue_patients.patient_id
    LEFT JOIN rescue_centres
    ON rescue_centres.rescue_id = rescue_admissions.centre_id
    WHERE rescue_patients.patient_id = :patient_id
    AND rescue_admissions.finder_tel LIKE '07%'
    AND rescue_admissions.consent_to_update = '1'
    ORDER by `admission_date` DESC");
                                                             
    $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
                                 
    // initialise an array for the results
    $finder_info = array();
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $finder_tel = $row["finder_tel"];
    $finder_name = $row["finder_name"];
    $rescue_name = $row["rescue_name"];
     //This displays sms form if phone number stored for patient
			if (!empty($finder_tel)){
			 print '<form action="" method="post" class="smsForm" id="smsForm">		  		
             </form>'; }
                            }
                           
                            ?>					 

							 </div>					
<div class="row lead_form_row">
        <?php  include("sms_form.php"); ?>
</div>
<div id="alertMsg2"><?php echo $SMSalert; ?></div>		

<div class="row">
<div class="col">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
<h4 class="font-weight-bold text-primary">This Admission</h4>
 <!-- This admission section -->	
     </div>
            <div class="card-body">   
                        
<table class="table table-bordered angelo_table " id="dataTable" width="100%" cellspacing="0">
							<thead><tr>
							    <th>Current Location</th>
                                <th>Admission date:</th>
                                <th>Age on Admission:</th>
                                <th>Presenting Complaint</th>
                                <th>Dehydrated</th>
								<th>Starved</th></tr></thead>
	<tbody>
                                    <?php
                                    //gets stuff from admissions table
                                    $stmt = $conn->prepare("SELECT * FROM rescue_admissions WHERE patient_id=:patient_id ORDER by admission_date DESC LIMIT 1");
                                    $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

                                    // initialise an array for the results
                                    $admissioninfo = array();
                                    $stmt->execute();
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $admission_date = $row["admission_date"];
                                        $age_on_admission = $row["age_on_admission"];
                                        $presenting_complaint = $row["presenting_complaint"];
                                        $dehydrated = $row["dehydrated"];
                                        $starved = $row["starved"];
										$current_location = $row["current_location"];



                                        print '<tr>
									<td>' . $current_location . '</td>	
									<td>' . $admission_date . '</td>
                                    <td>' . $age_on_admission . '</td>
                                    <td>' . $presenting_complaint . '</td>
                                    <td>' . $dehydrated . '</td>
                                    <td>' . $starved . '</td>
									
                                 
                                    </tr></tbody></table> ';
                                    }
                                    ?>
                        
 
<!-- BLOCK SECTION - Care plan -->
<div class="accordion" id="accordionExample">

<!-- SECTION - Patient Triage -->	
<div class="card">
    <div class="card-header" id="injuryassessment">
      <h2 class="mb-0">
        <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapseinj" aria-expanded="true" aria-controls="collapseinj">
          Patient Triage
        </button>
      </h2>
    </div>

    <div id="collapseinj" class="collapse" aria-labelledby="injuryassessment" data-parent="#accordionExample">
      <div class="card-body">

        <?php include_once("care_plans/add_triage.php"); ?>
        <?php include_once("care_plans/view_triage.php"); ?>	 

      </div>
    </div>
</div>	
<!-- END SECTION - Triage-->

<!-- SECTION - Care Notes -->		
  <div class="card">
    <div class="card-header" id="headingOne">
      <h2 class="mb-0">
        <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
          Patient Care Notes
        </button>		                             
      </h2>
    </div>
    <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordionExample">
      <div class="card-body">  

		<?php include_once("care_plans/view_carenotes.php"); ?>	
	    <?php include_once("care_plans/add_carenote.php"); ?>
      </div>
    </div>
  </div> 
<!-- END SECTION - Care notes -->
	  
<!-- SECTION - Lab Results -->	
<div class="card">
    <div class="card-header" id="headingLabs">
      <h2 class="mb-0">
        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseLabs" aria-expanded="false" aria-controls="collapseLabs">
          Lab Results
        </button>
      </h2>
    </div>
    <div id="collapseLabs" class="collapse" aria-labelledby="headingLabs" data-parent="#accordionExample">
      <div class="card-body">		  
		  <?php include_once("care_plans/add_labs.php"); ?>	
          <?php include_once("care_plans/view_labs.php"); ?>	

      </div>
    </div>
 </div>	
<!--- END SECTION - Lab Results --->	  	  
  	  
<!-- SECTION - Prescriptions -->	
	<div class="card">
    <div class="card-header" id="prescriptions">
      <h2 class="mb-0">
        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseprescriptions" aria-expanded="false" aria-controls="collapseprescriptions">
          Prescriptions
        </button>
      </h2>
    </div> 
    <div id="collapseprescriptions" class="collapse" aria-labelledby="prescriptions" data-parent="#accordionExample">
      <div class="card-body">
		
        <?php 
        include ("care_plans/view_prescription.php");
        if ($accesslevel === "1"){ 
        include_once("care_plans/add_prescription.php");
        } else {
            echo "<div class='alert alert-info' role='alert'>Only vets or Managers can add a new prescription to the care plan</div>";
        }
        ?>						
    </div>
  </div>
</div>
<!--- END SECTION - Prescriptions --->	 
	
<!--- SECTION - Medication -->		  
  <div class="card">
    <div class="card-header" id="headingThree">
      <h2 class="mb-0">
        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
          Medication Given
        </button>
      </h2>
    </div>
    <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample">
      <div class="card-body">		  
		  <?php include_once("care_plans/add_medsadmin.php"); ?>	
          <?php include_once("care_plans/view_medsgiven.php"); ?>	
		<?php /*include_once("operations/calculator.php"); */ ?>	
      </div>
    </div>
 </div>	
<!-- END SECTION - Medication -->
	
<!-- SECTION - Treatment -->		
<div class="card">
    <div class="card-header" id="headingFour">
      <h2 class="mb-0">
        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
          Treatments Given
        </button>
      </h2>
    </div>
    <div id="collapseFour" class="collapse" aria-labelledby="headingFour" data-parent="#accordionExample">
        <div class="card-body">
            <BR><button type="button" class="btn btn-success" data-toggle="modal" data-target="#treatmentModal"> Add A Treatment</button><br>
            <?php include_once("care_plans/add_treatment.php"); ?>
			<?php include_once("care_plans/view_treatments.php"); ?>   
        </div>		
    </div>
</div>	
<!-- END SECTION - Treatment -->

<!-- SECTION - Observations -->		
<div class="card">
    <div class="card-header" id="headingOBS">
      <h2 class="mb-0">
        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseOBS" aria-expanded="false" aria-controls="collapseOBS">
          Observations
        </button>
      </h2>
    </div>
    <div id="collapseOBS" class="collapse" aria-labelledby="headingOBS" data-parent="#accordionExample">
      <div class="card-body">
        
        <?php include_once("care_plans/add_observation.php"); ?>
		<?php include_once("care_plans/view_observations.php"); ?>   
    </div>		
  </div>
</div>	
<!-- END SECTION - Observations -->

<!--SECTION - Partner Log -->		
 <div class="card">
    <div class="card-header" id="partner">
      <h2 class="mb-0">
        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapsepartner" aria-expanded="false" aria-controls="collapsepartner">
          Partner Log
        </button>
      </h2>
    </div>
    <div id="collapsepartner" class="collapse" aria-labelledby="partner" data-parent="#accordionExample">
      <div class="card-body">
        <p>The partner log allows you to track and record links with other agencies. This can be used for logging a reference from a helpline referral, crime reference or other log numbers.</p> 
            
            <?php include_once("care_plans/add_partner.php"); ?>
            <?php include_once("care_plans/view_partner.php"); ?>
    </div>		
  </div>
</div>		
<!-- END SECTION - Partner Log-->				
                   
<?php echo $alertmsg; ?>
                </div>
            </div>
        </div>
    </div>

<!-- END BLOCK SECTION - Care plan -->

<!-- NEW CARD - weights and measurements -->
<div class="row">
<div class="col">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
	        <h6 class="m-0 font-weight-bold text-primary">Weights and Measurements</h6>
        </div>
    <div class="card-body">

<div class="row lead_form_row">
<!-- col 1 weights -->
    <div class="col-md-6 my-auto"> 
        <?php include_once("care_plans/view_weights.php"); ?>  
    </div>
<!-- col 2 measure -->
    <div class="col-md-6 my-auto">  
        <?php include_once("care_plans/view_measurements.php"); ?>  
    </div>

    </div>
</div>
</div>
<!-- END OF CARD - weights and measurements -->



<!-- NEW CARD - QR AND IMAGES -->
<div class="row">
    <div class="col">
  

<div class="row lead_form_row">
<!-- col 1 QR -->
    <div class="col-md-6 my-auto"> 
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Patient QR code</h6>   
            </div>
        <div class="card-body">        
        <!-- QR code generator shortcode -->
            <?php echo do_shortcode('[kaya_qrcode title_align="alignnone" ecclevel="L" size="150" color="#1abd82" align="alignnone" alt="Patient Record" content_url="1"]'); ?>
            <br />
            To use the QR code, right click and save as or copy to either print or add to documents.
           <br />	
        </div>  
        </div> 
    </div>

<!-- col 2 measure -->
    <div class="col-md-6 my-auto"> 
        <div class="card shadow mb-4"> 
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Image upload</h6>   
            </div>
            <div class="card-body">    
     
                <form action="https://rescuecentre.org.uk/wp-content/themes/brikk-child/operations/upload_image.php" method="post" enctype="multipart/form-data">
                <br>Select an image to upload: <br>&nbsp;<br>
                <input type="hidden" id="patient_id" name="patient_id" value="<?php echo $patient_id;?>">
                <input type="hidden" id="rescue_name" name="rescue_name" value="<?php echo $rescue_name;?>">
                <input type="hidden" id="centre_id" name="centre_id" value="<?php echo $centre_id;?>">
                <input type="file" name="fileToUpload" id="fileToUpload"><br>
                <input type="submit" value="Upload Image" name="upload_image"><br>&nbsp;
                </form> 
                <?php echo $imgmsg; ?>
            </div>  
        </div>
     </div>
    
    </div>
</div>

<!-- END OF CARD - weights and measurements -->
     
	 
	 
	 
<!-- Edit Admission Modal -->
<div class="modal fade" id="editAdmission" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Edit <?php echo $patient_name; ?>'s Admission</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span></button>
        </div>
        
        <div class="modal-body">
        <form action="https://rescuecentre.org.uk/wp-content/themes/brikk-child/ajax/edit_admission.php" method="post" class="lead_form" id="editAdmissionForm">
            
        <div class="row lead_form_row">
            <div class="col-md-4">
                <p class="angelo_form_label">Change current Location
                <select id="current_location" name="current_location">
                <option value="<?php echo $currentAdmission_current_location; ?>" selected>Current location - <?php echo $currentAdmission_current_location; ?></option>
                 <?php
                  //Find locations stored in the patients table 
                  $stmt = $conn->prepare("SELECT * 
                                FROM rescue_locations
                                WHERE centre_id = :centre_id ORDER BY 'location_name' DESC");
                  $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);

                  // initialise an array for the results
                  $locations = array();
                  $stmt->execute();
                  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                  $location_id = $row["location_id"];
                  $location_name = $row["location_name"];
                  $location_type = $row["location_type"];
				
                  print '<option value="' . $location_name . '">' . $location_name . ' - ' . $location_type . '</option>';
                                }
                               ?>
                </select></p>
            </div>

            <div class="col-md-4 my-auto">
                <p class="angelo_form_label">Admission Status
                    <select name="status" name="status" id="status" required>
                        <option value="<?php echo $currentAdmision_status; ?>" selected>Current status - <?php echo $currentAdmision_status; ?></option>
                        <option value="Active">Active</option>
                        <option value="Closed">Closed</option>
                    </select>
                </p>
            </div>
                    
            <div class="col-md-4 my-auto">
                <p class="angelo_form_label">Current Disposition
                    <select id="disposition" name="disposition">
                        <option value="<?php echo $currentAdmission_disposition; ?>" selected>Current disposition - <?php echo $currentAdmission_disposition; ?></option>
                        <option value="Held in captivity">Held in captivity</option>
                        <option value="Released">Released</option>
						<option value="Transferred out">Transferred to another rescue</option>
                        <option value="Died - Euthanised">Died - Euthanised</option>
                        <option value="Died - after 48 hours">Died - after 48 hours</option>
                        <option value="Died - within 48 hours">Died - within 48 hours</option>
                        <option value="Died - on admission">Died - on admission</option>
                    </select>
                </p>
            </div>
        </div>
<hr class="hr hr-blurry" />

        <div class="row lead_form_row">
            <div class="col-md-4">
            <p class="angelo_form_label">Admission Date and Time</p>
            <input type="datetime-local" name="admission_date" id="admission_date"  aria-describedby="admissiondate">
            <small id="admissiondate" class="form-text text-muted">
            Admission date:<?php echo $currentAdmission_date; ?></small>
        </div>
            <div class="col-md-4">
            </div>
            <div class="col-md-4">
            </div>               
        </div>




       <br />
             <input type="hidden" name="theadmissionid" id="theadmissionid" value="<?php echo $currentAdmission_id; ?>">
			<input type="hidden" id="patient_id" name="patient_id" value="<?php echo $patient_id ?>">
              <input type="submit" name="editAdmissionSubmit" value="Update Admission Record">	
 </form>

                                </div>
                            </div>
                        </div>
                    </div>
                    <!-------------------------------------------------------------------------->

<!-------------------------------------------------------------------------->

<!-- Edit Details Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Edit <?php echo $patient_name; ?>'s Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            <form action="https://rescuecentre.org.uk/wp-content/themes/brikk-child/ajax/edit_patient.php" method="post" class="lead_form" id="manualForm">
                <div class="row lead_form_row">
                    <div class="col-md-6">
                        <p class="angelo_form_label">Name or identifier</p>
                        <input type="text" placeholder="Name or identifier" name="name" id="name" value="<?php echo $patient_name; ?>" required>
                    </div>
                    <div class="col-md-6">
                    <p class="angelo_form_label">Sex</p>
                        <select name="sex" name="sex" id="sex">
                            <option value="<?php echo $patient_sex; ?>" selected><?php echo $patient_sex; ?></option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Female (lactating)">Female (lactating)</option>
                            <option value="Female (pregnant)">Female (pregnant)</option>
                            <option value="Undetermined">Undetermined</option>
                        </select>
                    </div>
                </div>

                <div class="row lead_form_row">
                    <div class="col-md-6 my-auto">
                        <p class="angelo_form_label">Ringed</p>
                        <select name="ringed" name="ringed" id="ringed" required>
                            <option value="<?php echo $patient_ringed; ?>" selected><?php echo $patient_ringed; ?></option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <p class="angelo_form_label">Ring Number</p>
                            <input type="text" placeholder="Ring Number" name="ring_number" id="ring_number" value="<?php echo $patient_ring_number; ?>">
                    </div>
                </div>

                <div class="row lead_form_row">
                    <div class="col-md-6 my-auto">
                        <p class="angelo_form_label">Is this animal Microchipped?</p>
                        <select name="microchipped" name="microchipped" id="microchipped">
                            <option value="<?php echo $patient_microchipped; ?>" selected><?php echo $patient_microchipped; ?></option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                    <p class="angelo_form_label">Microchip Number</p>
                        <input type="text" placeholder="Microchip Number" name="microchip_number" id="microchip_number" value="<?php echo $patient_microchip_number; ?>">
                    </div>
                </div>

                <div class="row lead_form_row">
                    <div class="col-md-6 my-auto">
                        <p class="angelo_form_label">Animal Order</p>
                        <select id="animal_orders" name="animal_orders">
                            <option value="<?php echo $patient_animal_order; ?>" selected><?php echo $patient_animal_order; ?></option>
                            <option value="Amphibian">Amphibian</option>
                            <option value="Bird">Bird</option>
                            <option value="Fish">Fish</option>
                            <option value="Mammal">Mammal</option>
                            <option value="Reptile">Reptile</option>
                            <option value="Unknown">Unknown</option>
                        </select>
                    </div>

                    <div class="col-md-6 my-auto">
                        <p class="angelo_form_label">Animal Type</p>
                            <select id="animal_types" name="animal_types">
                                <option value="<?php echo $patient_animal_type; ?>" selected><?php echo $patient_animal_type; ?></option>
                                <option>Please select an animal type</option>
                            </select>
                    </div>
                </div>

                <div class="row lead_form_row">
                    <div class="col-md-6">
                        <p class="angelo_form_label">Animal Species</p>
                        <select id="animal_species" name="animal_species">
                            <option value="<?php echo $patient_animal_species; ?>" selected><?php echo $patient_animal_species; ?></option>
                            <option>Please select an animal species</option>
                        </select>
                    </div>

                    <div class="col-md-6 my-auto">
                        <p class="angelo_form_label">Animal Status</p>
                        <select name="status" name="status" id="status">
                            <option value="<?php echo $patient_status; ?>" selected><?php echo $patient_status; ?></option>
                            <option value="Captive">Captive</option>
                            <option value="Released">Released</option>
                            <option value="Deceased">Deceased</option>
                        </select>
                    </div>
    <input type="hidden" name="thepatientid" id="thepatientid" value="<?php echo $patient_id; ?>">
            </div>
             <br />
              <input type="submit" name="form3" value="Update Patient Record">
</form>

</div>
</div>
</div>
</div>
                    <!-------------------------------------------------------------------------->



 <!-- Add A Measurement Modal -->
                    <div class="modal fade" id="measurementModal" tabindex="-1" role="dialog" aria-labelledby="measurementModal" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel">Add a measurement for <?php echo $patient_name; ?></h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                  <div class="row lead_form_row"> 
                                    <form action="https://rescuecentre.org.uk/wp-content/themes/brikk-child/ajax/add_measurement.php" method="post" class="lead_form" id="addMeasurementForm">
                                      <div class="col-md-6">
                                        <p class="angelo_form_label">Date and Time</p>
										<input type="datetime-local" name="date" id="date" placeholder="date" required>
										</div></div>
										<div class="row lead_form_row">
                                            <div class="col-md-6">
												<p class="angelo_form_label">Measurement</p>
                                        <input type="text" name="measurement" id="measurement" placeholder="Animal Measurement" required>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <p class="angelo_form_label">Measurement Unit</p>
                                                <select id="measurement_unit" name="measurement_unit">
                                                    <option value="mm">Millimeters</option>
                                                    <option value="cm">Centimeters</option>
                                                    <option value="m">Meters</option>
                                                    <option value="in">Inches</option>
                                                    <option value="ft">Feet</option>
                                                </select>
                                            </div>
											
											
                                        </div>

                                        <input type="hidden" name="measurement_thepatientid" id="measurement_thepatientid" value="<?php echo $patient_id; ?>">

                                        <input type="submit" name="form4" value="Update Patient Record">
                                    </form>
                                </div>

                                <br />





                            </div>

                        </div>
                    </div>

						
  <!-- Add A weight Modal -->
                    <div class="modal fade" id="weightModal" tabindex="-1" role="dialog" aria-labelledby="weightModal" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel">Add a weight for <?php echo $patient_name; ?></h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">

                                    <form action="https://rescuecentre.org.uk/wp-content/themes/brikk-child/ajax/add_weight.php" method="post" class="lead_form" id="addweightForm">
                                        <div class="row lead_form_row">
										<div class="col-md-6">
										 
                                                <p class="angelo_form_label">Date and Time</p>
										<input type="datetime-local" name="date" id="date" placeholder="date" required>
										</div></div>
										<div class="row lead_form_row">
                                            <div class="col-md-6">
                                                <p class="angelo_form_label">Animal Weight</p>
                                                    <input type="text" name="weight" id="weight" placeholder="Animal Weight" required>
 
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <p class="angelo_form_label">Weight Unit</p>
                                                <select id="weight_unit" name="weight_unit">
                                                    <option value="g">Grams</option>
                                                    <option value="kg">Kilograms</option>
                                                    <option value="lbs">Pounds</option>
                                                </select>
                                            </div>
											
											
                                        </div>

                                        

                                        <input type="hidden" name="weight_thepatientid" id="weight_thepatientid" value="<?php echo $patient_id; ?>">

                                        <input type="submit" name="form5" value="Update Patient Record">
                                    </form>
                                </div>

                                <br />


                            </div>

                        </div>
                    </div>


                    <p>&nbsp;</p>
				



							
		
                </div>
            </div>
        </div>
 </div>


    <!------------------------------------------------- End Of Row -------------------------------------------------->


    <!-- Row Two -->
    <div class="row">

        <!-- Admissions -->
        <div class="col">

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Previous admissions</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered angelo_table" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Admission Date</th>
                                    <th>Status</th>
                                    <th>Age On Admission</th>
                                    <th>Collection Location</th>
                                    <th>Presenting Complaint</th>
                                    <th>Dehydrated</th>
                                    <th>Starved</th>
                                    <th>Weight</th>
                                    <th>Measurement</th>
                                    <th>Disposition</th>
                                </tr>
                            </thead>
                            <tbody>

                                <?php
                                //Find adminssion. Make sure they aren't already there
                                $stmt = $conn->prepare("SELECT * FROM rescue_admissions WHERE patient_id = :patient_id ORDER by admission_date DESC");
                                $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

                                // initialise an array for the results
                                $applicants = array();
                                $stmt->execute();
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                                    $admission_date = $row["admission_date"];
                                    $admission_status = $row["status"];
                                    $admission_age = $row["age_on_admission"];
                                    $admission_collection_location = $row["collection_location"];
                                    $admission_presenting_complaint = $row["presenting_complaint"];
                                    $admission_dehydrated = $row["dehydrated"];
                                    $admission_starved = $row["starved"];
                                    $admission_disposition = $row["disposition"];
                                    $admission_weight = $row["weight"];
                                    $admission_weight_unit = $row["weight_unit"];
                                    $admission_measurement = $row["measurement"];
                                    $admission_measurement_unit = $row["measurement_unit"];

                                    $formatted_date = new DateTime($admission_date);
                                    $formatted_date = $formatted_date->format('d/m/Y - H:i');

                                    print '<tr>
                                    <td>' . $admission_date . '</td>
                                    <td>' . $admission_status . '</td>
                                    <td>' . $admission_age . '</td>
                                    <td>' . $admission_collection_location . '</td>
                                    <td>' . $admission_presenting_complaint . '</td>
                                    <td>' . $admission_dehydrated . '</td>
                                    <td>' . $admission_starved . '</td>
								    
                                    <td>' . $admission_weight . '' . $admission_weight_unit . '</td>
                                    <td>' . $admission_measurement . '' . $admission_measurement_unit . '</td>
                                    <td>' . $admission_disposition . '</td>
                                    </tr>';
                                }

                                ?>


                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
		</div>

         
 

    <!------------------------------------------------- End Of Row -------------------------------------------------->



</div>
<!-- /.container-fluid -->




<?php include_once "app_footer.php"; ?>

</div>


<script type="text/javascript">
    /* Load in animal types depending on the user's input */
    $(function() {
        $("#animal_orders").change(function() {
            $("#animal_types").load("https://rescuecentre.org.uk/wp-content/themes/brikk-child/get_types.php?id=" + $(this).val());
            var theorder = ($(this).val());

            console.log(theorder);

            if(theorder === "Mammal") {
                $("#animal_species").load("https://rescuecentre.org.uk/wp-content/themes/brikk-child/get_species.php?id=Badger");
            }
            else if(theorder === "Amphibian") {
                $("#animal_species").load("https://rescuecentre.org.uk/wp-content/themes/brikk-child/get_species.php?id=Frog");
            }
            else if(theorder === "Bird") {
                var birdValue = encodeURIComponent("Birds of Prey");
                $("#animal_species").load("https://rescuecentre.org.uk/wp-content/themes/brikk-child/get_species.php?id=" + birdValue);
            }
            else if(theorder === "Fish") {
                var fishValue = encodeURIComponent("Marine Fish");
                $("#animal_species").load("https://rescuecentre.org.uk/wp-content/themes/brikk-child/get_species.php?id=" + fishValue);
            }
            else if(theorder === "Reptile") {
                $("#animal_species").load("https://rescuecentre.org.uk/wp-content/themes/brikk-child/get_species.php?id=Lizard");
            }
            else if(theorder === "Unknown") {
                $("#animal_species").load("https://rescuecentre.org.uk/wp-content/themes/brikk-child/get_species.php?id=Unknown");
            }

        });
    });

    /* Load in animal species depending on the user's input */
    $(function() {

        $("#animal_types").change(function() {

            var value = encodeURIComponent($(this).val());

            $("#animal_species").load("https://rescuecentre.org.uk/wp-content/themes/brikk-child/get_species.php?id=" + value);

            var thespecies = ($(this).val());
            //$('#animal_species').show();
        });
    });
</script>




<!-- Add an "active" CSS class to the current page on the menu -->

<script>
    document.getElementById("patients_link").classList.add("active");
</script>
<script>
    $('#ss_text').select2({
        dropdownParent: $('#headingOBS')
    });
</script>	
<script>
    //AJAX Scripts

    //Edit Patient AJAX
    $(document).ready(function() {
        $('#manualForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                type: 'post',
                url: 'https://rescuecentre.org.uk/wp-content/themes/brikk-child/ajax/edit_patient.php',
                data: $('#manualForm').serialize(),
                success: function() {
                    location.reload();
                }
            });
        });
    });

    //Edit Admission AJAX
    $(document).ready(function() {
        $('#editAdmissionForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                type: 'post',
                url: 'https://rescuecentre.org.uk/wp-content/themes/brikk-child/ajax/edit_admission.php',
                data: $('#editAdmissionForm').serialize(),
                success: function() {
                    location.reload();
                }
            });
        });
    });

    //Insert Measurement AJAX
    $(document).ready(function() {
        $('#addMeasurementForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                type: 'post',
                url: 'https://rescuecentre.org.uk/wp-content/themes/brikk-child/ajax/insert_measurement.php',
                data: $('#addMeasurementForm').serialize(),
                success: function() {
                    location.reload();
                }
            });
        });
    });
	
    //Insert Weight AJAX
    $(document).ready(function() {
        $('#addweightForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                type: 'post',
                url: 'https://rescuecentre.org.uk/wp-content/themes/brikk-child/ajax/insert_weight.php',
                data: $('#addweightForm').serialize(),
                success: function() {
                    location.reload();
                }
            });
        });
    });
	
	//Insert triage AJAX
    $(document).ready(function() {
        $('#triageform').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                type: 'post',
                url: 'https://rescuecentre.org.uk/wp-content/themes/brikk-child/ajax/insert_triage.php',
                data: $('#triageform').serialize(),
                success: function() {
                    location.reload();
                }
            });
        });
    });
</script>

<!-- End of Main Content -->





<script>
    // Set new default font family and font color to mimic Bootstrap's default styling
    Chart.defaults.global.defaultFontFamily = 'Nunito', '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
    Chart.defaults.global.defaultFontColor = '#858796';

    function number_format(number, decimals, dec_point, thousands_sep) {
        // *     example: number_format(1234.56, 2, ',', ' ');
        // *     return: '1 234,56'
        number = (number + '').replace(',', '').replace(' ', '');
        var n = !isFinite(+number) ? 0 : +number,
            prec = !isFinite(+decimals) ? 2 : Math.abs(decimals),
            sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
            dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
            s = '',
            toFixedFix = function(n, prec) {
                var k = Math.pow(10, prec);
                return '' + Math.round(n * k) / k;
            };
        // Fix for IE parseFloat(0.55).toFixed(0) = 0;
        s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
        if (s[0].length > 3) {
            s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
        }
        if ((s[1] || '').length < prec) {
            s[1] = s[1] || '';
            s[1] += new Array(prec - s[1].length + 1).join('0');
        }
        return s.join(dec);
    }

    // Area Chart Example - WEIGHT
    var ctx = document.getElementById("myAreaChart");
    var myLineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [<?php
                        //Loop through the measurement months from the database
                        $stmt = $conn->prepare("SELECT * FROM rescue_weights WHERE patient_id = :patient_id ORDER by date ASC");
                        $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

                        // initialise an array for the results
                        $applicants = array();
                        $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                            $graph_date = $row["date"];


                            $day = date('j', strtotime($graph_date));

                            $suffix = '';

                            if ($day == 1 || $day == 21 || $day == 31) {
                                $suffix = 'st';
                            } elseif ($day == 2 || $day == 22) {
                                $suffix = 'nd';
                            } elseif ($day == 3 || $day == 23) {
                                $suffix = 'rd';
                            } else {
                                $suffix = 'th';
                            }

                            $dayWithSuffix = $day . $suffix;


                            $monthAbbreviation = date('M', strtotime($graph_date));
                            $yearDigits = date('y', strtotime($graph_date));

                            print '"' . $dayWithSuffix . ' ' . $monthAbbreviation . ' ' . $yearDigits . '",';
                        }

                        ?>],
            datasets: [{
                label: "Weight",
                lineTension: 0.3,
                backgroundColor: "rgba(78, 115, 223, 0.05)",
                borderColor: "rgba(78, 115, 223, 1)",
                pointRadius: 3,
                pointBackgroundColor: "rgba(78, 115, 223, 1)",
                pointBorderColor: "rgba(78, 115, 223, 1)",
                pointHoverRadius: 3,
                pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                pointHitRadius: 10,
                pointBorderWidth: 2,
                data: [<?php
                        //Loop through this patient's weights from the database
                        $stmt = $conn->prepare("SELECT * FROM rescue_weights WHERE patient_id = :patient_id ORDER by date ASC");
                        $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

                        // initialise an array for the results
                        $applicants = array();
                        $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                            $graph_weight = $row["weight"];

                            print '' . $graph_weight . ',';
                        }

                        ?>],						
						
            }],
        },
        options: {
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 25,
                    top: 25,
                    bottom: 0
                }
            },
            scales: {
                xAxes: [{
                    time: {
                        unit: 'date'
                    },
                    gridLines: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        maxTicksLimit: 7
                    }
                }],
                yAxes: [{
                    ticks: {
                        maxTicksLimit: 5,
                        padding: 10,
                        // Include a dollar sign in the ticks
                        callback: function(value, index, values) {
                            return number_format(value) + '<?php echo $first_weight_unit; ?>';
                        }
                    },
                    gridLines: {
                        color: "rgb(234, 236, 244)",
                        zeroLineColor: "rgb(234, 236, 244)",
                        drawBorder: false,
                        borderDash: [2],
                        zeroLineBorderDash: [2]
                    }
                }],
            },
            legend: {
                display: false
            },
            tooltips: {
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                titleMarginBottom: 10,
                titleFontColor: '#6e707e',
                titleFontSize: 14,
                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: false,
                intersect: false,
                mode: 'index',
                caretPadding: 10,
                callbacks: {
                    label: function(tooltipItem, chart) {
                        var datasetLabel = chart.datasets[tooltipItem.datasetIndex].label || '';
                        return datasetLabel + ': ' + number_format(tooltipItem.yLabel) + '<?php echo $first_weight_unit; ?>';
                    }
                }
            }
        }
    });
</script>

<script>
    // Set new default font family and font color to mimic Bootstrap's default styling
    Chart.defaults.global.defaultFontFamily = 'Nunito', '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
    Chart.defaults.global.defaultFontColor = '#858796';

    function number_format(number, decimals, dec_point, thousands_sep) {
        // *     example: number_format(1234.56, 2, ',', ' ');
        // *     return: '1 234,56'
        number = (number + '').replace(',', '').replace(' ', '');
        var n = !isFinite(+number) ? 0 : +number,
            prec = !isFinite(+decimals) ? 2 : Math.abs(decimals),
            sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
            dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
            s = '',
            toFixedFix = function(n, prec) {
                var k = Math.pow(10, prec);
                return '' + Math.round(n * k) / k;
            };
        // Fix for IE parseFloat(0.55).toFixed(0) = 0;
        s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
        if (s[0].length > 3) {
            s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
        }
        if ((s[1] || '').length < prec) {
            s[1] = s[1] || '';
            s[1] += new Array(prec - s[1].length + 1).join('0');
        }
        return s.join(dec);
    }

    // Area Chart Example - MEASUREMENTS 
    var ctx = document.getElementById("measurementChart");
    var myLineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [<?php
                        //Loop through the measurement months from the database
                        $stmt = $conn->prepare("SELECT * FROM rescue_measurements WHERE patient_id = :patient_id ORDER by date ASC");
                        $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

                        // initialise an array for the results
                        $applicants = array();
                        $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                            $graph_date = $row["date"];


                            $day = date('j', strtotime($graph_date));

                            $suffix = '';

                            if ($day == 1 || $day == 21 || $day == 31) {
                                $suffix = 'st';
                            } elseif ($day == 2 || $day == 22) {
                                $suffix = 'nd';
                            } elseif ($day == 3 || $day == 23) {
                                $suffix = 'rd';
                            } else {
                                $suffix = 'th';
                            }

                            $dayWithSuffix = $day . $suffix;


                            $monthAbbreviation = date('M', strtotime($graph_date));
                            $yearDigits = date('y', strtotime($graph_date));

                            print '"' . $dayWithSuffix . ' ' . $monthAbbreviation . ' ' . $yearDigits . '",';
                        }

                        ?>],
            datasets: [{
                label: "Measurement",
                lineTension: 0.3,
                backgroundColor: "rgba(78, 115, 223, 0.05)",
                borderColor: "rgba(78, 115, 223, 1)",
                pointRadius: 3,
                pointBackgroundColor: "rgba(78, 115, 223, 1)",
                pointBorderColor: "rgba(78, 115, 223, 1)",
                pointHoverRadius: 3,
                pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                pointHitRadius: 10,
                pointBorderWidth: 2,
                data: [<?php
                        //Loop through this patient's measurements from the database
                        $stmt = $conn->prepare("SELECT * FROM rescue_measurements WHERE patient_id = :patient_id ORDER by date ASC");
                        $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

                        // initialise an array for the results
                        $applicants = array();
                        $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                            $graph_measurement = $row["measurement"];

                            print '' . $graph_measurement . ',';
                        }

                        ?>],
            }],
        },
        options: {
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 25,
                    top: 25,
                    bottom: 0
                }
            },
            scales: {
                xAxes: [{
                    time: {
                        unit: 'date'
                    },
                    gridLines: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        maxTicksLimit: 7
                    }
                }],
                yAxes: [{
                    ticks: {
                        maxTicksLimit: 5,
                        padding: 10,
                        // Include a dollar sign in the ticks
                        callback: function(value, index, values) {
                            return number_format(value) + '<?php echo $first_measurement_unit; ?>';
                        }
                    },
                    gridLines: {
                        color: "rgb(234, 236, 244)",
                        zeroLineColor: "rgb(234, 236, 244)",
                        drawBorder: false,
                        borderDash: [2],
                        zeroLineBorderDash: [2]
                    }
                }],
            },
            legend: {
                display: false
            },
            tooltips: {
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                titleMarginBottom: 10,
                titleFontColor: '#6e707e',
                titleFontSize: 14,
                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: false,
                intersect: false,
                mode: 'index',
                caretPadding: 10,
                callbacks: {
                    label: function(tooltipItem, chart) {
                        var datasetLabel = chart.datasets[tooltipItem.datasetIndex].label || '';
                        return datasetLabel + ': ' + number_format(tooltipItem.yLabel) + '<?php echo $first_measurement_unit; ?>';
                    }
                }
            }
        }
    });
</script>

<?php get_footer();






