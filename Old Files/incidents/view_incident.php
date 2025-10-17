<?php
include_once "authentication.php";
include_once "connect_to_mysql.php";

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

/* Template Name: View Incident */

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

if (isset($_GET["incident_id"])) {
    $incident_id = test_input($_GET["incident_id"]);
} else {
    echo $lang['INC_SELECT_INCIDENT_TO_VIEW'];
   //exit();
   goto end; 
}

if (isset($_GET["alert"])) {
    $alert = test_input($_GET["alert"]);

    if ($alert = 1) {
        $alertmsg = "<div class='alert alert-success' role='alert'>
        This incident was updated in the database.
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
$sql = 'SELECT * FROM rescue_incidents WHERE incident_id=:incident_id LIMIT 1';
$statement = $conn->prepare($sql);
$statement->bindParam(':incident_id', $incident_id, PDO::PARAM_INT);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
/*---------------------------------------------------------------------------------*/
if ($result) {

    $inc_id = $result["incident_id"];
    $inc_date = $result["incident_date"];
    $inc_add_1 = $result["incident_location_line_1"];
    $inc_line_2 = $result["incident_location_line_2"];
    $inc_city = $result["incident_location_city"];
    $inc_postcode = $result["incident_location_postcode"];
    $inc_centre_ref = $result["incident_centre_ref"];
    $inc_tot_cas = $result["incident_total_casualties"];
    $inc_mass_cas = $result["incident_mass_cas"];
    $inc_doa_cas = $result["incident_doa"];
} else {
    echo "Error 2";
    exit();
}

if (isset($_POST['linkpatientForm'])) {

	$rel_inc_id = $_POST["incident_id"];
    $rel_cen_id = $_POST["centre_id"];
    $rel_adm_id = $_POST["admitid"];
	$rel_usr_id = $_POST["user_id"];

    try {
        $statement = $conn->prepare('INSERT INTO rescue_incident_related
            (incident_id, 
            centre_id,
            admission_id,
			user_id)
            
            VALUES (:incident_id, 
            :centre_id,
            :admission_id,
			:user_id)');

        $statement->execute([
            'incident_id' => $rel_inc_id,
            'centre_id' => $rel_cen_id,
            'admission_id' => $rel_adm_id,
			'user_id' => $rel_usr_id
        ]);
		
		  echo "<script>window.location = window.location</script>";
		
    } catch (PDOException $e) {
        echo "Database Error: The relationship could not added.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The relationship could not be added.<br>" . $e->getMessage();
        exit();
    }
}
/*------------------------------------------------------------------ FORM PROCESSING - Soft delete the relationship-------------------------------------------------------------------*/
if (isset($_POST['unlinkpt'])) {
    $post_rel_id = $_POST["inc_rel_id"];
    $post_is_del = $_POST["is_deleted"];
    try {
        $statement = $conn->prepare('INSERT INTO rescue_incident_related
            ( 
            inc_rel_id,
			is_deleted)            
            VALUES (
            :inc_rel_id,
			:is_deleted) 			
			ON DUPLICATE KEY UPDATE
			is_deleted = :is_deleted	
			');

        $statement->execute([
            'inc_rel_id' => $post_rel_id,
            'is_deleted' => $post_is_del  
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

<!-- Display incident details -->
<div class="card shadow mb-4" id="databasetable">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><?php echo $lang['INCIDENT']; ?> INC-<?php echo $inc_id; ?>  </h6>
    </div>
        <div class="card-body">
        <div class="alert alert-secondary" role="alert">
            <div class="row lead_form_row">
		        <div class="col-md-5">
                <h6><u><?php echo $lang['INCIDENT']; ?></u></h6><br>
                <?php echo $lang['DATE_OF']; ?> <?php echo $lang['INCIDENT']; ?> <?php echo $inc_date; ?><br>
                <?php echo $inc_add_1; ?><br>
                <?php echo $inc_line_2; ?><br>
                <?php echo $inc_city; ?><br>
                <?php echo $inc_postcode; ?><br>
                </div>
                <div class="col-md-2">
                </div>
                <div class="col-md-5">
                <h6><u><?php echo $lang['INC_CASUALTY_INFO']; ?> </u></h6><br>
                <?php echo $lang['INC_TOTAL_CASUALTIES']; ?> <?php echo $inc_tot_cas; ?><br>
                <?php echo $lang['INC_DOA_CASUALTIES']; ?> <?php echo $inc_doa_cas; ?><br>
                <?php echo $lang['INC_MASS_CASUALTY']; ?> <?php echo $mas_cas; ?><br>
                <br>
                <?php echo $lang['INC_CENTRE_REF']; ?> <?php echo $inc_centre_ref; ?>
                </div>
            </div>
        </div>
                
                    
 <!-------------this table displays patient's linked to the incident and creates form to link a new one ------------------------------------------>

 <div class="table-responsive">
    <h5><u><?php echo $lang['INC_LINKED_PATIENTS']; ?></u></h5>
    <table class="table table-bordered table-sm table-hover" id="linkedpts" width="100%" cellspacing="0">
        <thead class="thead-dark">
        <tr>
            <th class="align-middle"><?php echo $lang['NAME']; ?></th>
            <th class="align-middle"><?php echo $lang['SEX']; ?></th>
            <th class="align-middle"><?php echo $lang['SPECIES']; ?></th>
            <th class="align-middle"><?php echo $lang['AGE']; ?></th>
            <th class="align-middle"><?php echo $lang['PRESENTING_COMPLAINT']; ?></th>
            <th class="align-middle" width="200"></th>
        </tr>
        </thead>
        <tbody>
        <?php 
        $stmt = $conn->prepare("SELECT * FROM rescue_incident_related										
            LEFT JOIN rescue_admissions 
                ON rescue_admissions.admission_id = rescue_incident_related.admission_id
            LEFT JOIN rescue_patients
                ON rescue_patients.patient_id = rescue_admissions.patient_id
            WHERE rescue_incident_related.incident_id = :incident_id 
                AND rescue_incident_related.is_deleted = 0
            ORDER by name ASC");
        $stmt->bindParam(':incident_id', $incident_id, PDO::PARAM_INT);

            $linkedpatients = array();
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pt_name= $row["name"];
            $inc_rel_id = $row["inc_rel_id"];
            $pt_pres_comp = $row["presenting_complaint"];
            $pt_age = $row["age_on_admission"];
            $pt_micro = $row["microchipped"];
            $pt_micro_no = $row["microchip_number"];
            $pt_animal_type = $row["animal_type"];
            $pt_animal_order = $row["animal_order"];
            $pt_animal_species = $row["animal_species"];
            $pt_sex = $row["sex"];      
            
            
        ?>
    
        <tr>
            <td><?php echo $pt_name; ?></td>
            <td><?php echo $pt_sex; ?></td>
            <td><?php echo $pt_animal_type; ?>, <?php echo $pt_animal_species; ?></td>
            <td><?php echo $pt_age; ?></td>
            <td><?php echo $pt_pres_comp; ?></td>
            <td><form method="post" action="">
                <input type="hidden" id="inc_rel_id" name="inc_rel_id" value="<?php echo $inc_rel_id; ?>">
                <input type="hidden" id="is_deleted" name="is_deleted" value="1">
                <button type="submit" class="btn btn-outline-danger btn-sm" name="unlinkpt"><i class="fas fa-unlink"></i> <?php echo $lang['INC_BUT_UNLINK']; ?></button> 
            </form></td> <?php }?>
        </tr>
        </tbody>
</table>
</div>
<form action="" method="post" class="lead_form" id="linkpatientForm" onSubmit="window.location.reload()">
<div class="row lead_form_row"> 
<div class="col-md-6 my-auto">
    <p class="angelo_form_label"><?php echo $lang['INC_SELECT_TO_LINK']; ?></p>
    <select name="patient" id="patient" required>
                    <option value="" disabled selected><?php echo $lang['SELECT_PATIENT']; ?></option>
                        <?php
                        //Find patients stored in the patients table 
                        $stmt = $conn->prepare("SELECT * 
                        FROM rescue_admissions											
                        LEFT JOIN rescue_patients ON rescue_admissions.patient_id = rescue_patients.patient_id
						WHERE rescue_admissions.status = 'active' AND rescue_patients.centre_id = :centre_id ORDER BY admission_id DESC");
                        $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
                        // initialise an array for the results
                        $patientlist = array();
                        $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                        $patient_id = $row["patient_id"];
                        $patient_name = $row["name"];
                        $patient_species = $row["animal_species"];
						$admission_id = $row["admission_id"];
                
                        print '<option value="' . $patient_id . '" data-admid="' . $admission_id . '">CRN:' . $admission_id . ' ' . $patient_name . ' - ' . $patient_species . '</option>';
                                }
                                ?>
                </select>
		</div>
        <div class="col-md-6 my-auto">
        <input type="hidden" id="admitid" name="admitid" placeholder="admission id">
        <input type="hidden" name="incident_id" id="incident_id" value="<?php echo $inc_id; ?>">
        <input type="hidden" name="centre_id" id="centre_id" value="<?php echo $centre_id; ?>">
        <input type="hidden" name="user_id" id="user_id" value="<?php $current_user = wp_get_current_user(); print($current_user->id); ?>">
        <p class="angelo_form_label">&nbsp;</p>
        <button type="submit" id="submit" name="linkpatientForm" value="Link this patient" class="btn btn-primary btn-sm"data-toggle="tooltip" data-placement="top" title="Link this patient"><i class="fas fa-link" ></i> <?php echo $lang['INC_BUT_LINK']; ?></button>
    </div>








        </div>
    </div>
</div>		
</div>
<!-- End of vfiew incidents -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
$('#patient').change(function(e){
  var optionChange = ($('#patient option:selected').data('admid'));
  $('#admitid').val(optionChange);

});
</script>
<?php end: ?>