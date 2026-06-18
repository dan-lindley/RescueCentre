<?php defined('ABSPATH') or die('This script cannot be accessed directly.');

// Report all errors except E_NOTICE   
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */

include_once "authentication.php";
include_once "connect_to_mysql.php";


echo "<div class='app_page_container'>";
/**
 * The template for displaying pages
 *
 * Do not overload this file directly. Instead have a look at templates/single.php file in us-core plugin folder:
 * you should find all the needed hooks there.
 */

/* Template Name: Settings */

get_header();

include_once "app_header.php";

$current_user_id = get_current_user_id();

//Get the current Rescue Centre data from the database
$sql = 'SELECT * FROM rescue_centres WHERE rescue_id=:centre_id LIMIT 1';
$statement = $conn->prepare($sql);
$statement->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
/*---------------------------------------------------------------------------------*/
if ($result) {
    $rescue_name = $result["rescue_name"];
    $centre_type = $result["centre_type"];
    $email = $result["email"];
    $office_tel = $result["office_tel"];
    $mobile = $result["mobile"];
    $twentyfour = $result["24_hour"];
    $address_line_one = $result["address_line_one"];
    $address_line_two = $result["address_line_two"];
    $city = $result["city"];
    $postcode = $result["postcode"];
    $accepting_admissions = $result["accepting_admissions"];
    $species_accepted = $result["species_accepted"];
    $opening_hours = $result["opening_hours"];
} else {
    echo "Rescue centre not found";
    exit();
}


if (isset($_POST['deletepatient'])) {
    $patient_id = $_POST["patient_id"];

    try {
        $statement = $conn->prepare('DELETE FROM rescue_patients
          WHERE patient_id = :patient_id
			');

        $statement->execute([
            'patient_id' => $patient_id
			           
        ]);
		echo "<meta http-equiv='refresh' content='0'>";
		
    } catch (PDOException $e) {
        echo "Database Error: The patient could not be deleted.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The patient could not be deleted.<br>" . $e->getMessage();
        exit();
    }
}




?>

<div id="page-top">
    <div class="container-fluid">
        <div class="row dashboard_heading_withfilter">
            <div class="col-md-6 my-auto">
                <h1 class="h3 mb-0 text-gray-800 portal_heading">Settings</h1>
            </div>
        </div>
    <div id="alertMsg"><?php echo $alertMsg; ?></div>

<!-- LOCATION Management CARD-->

<div class="card shadow mb-4" id="databasetable">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Locations</h6>
        <p class="card_subheading">Manage the locations in your centre <BR>
			<!-- Add new location Button -->
            <br><button type="button" class="btn btn-success" data-toggle="modal" data-target="#locationModal"> Add Location </button> 
            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#areaModal"> Add Area</button></p>	
    </div>
    <div class="card-body">
			<?php include ("settings/locations.php"); ?>
	</div>  
<br>
</div>	
</div>
                
                
                
<!-- ALERTS MANAGEMENT-->
<div class="card shadow mb-4" id="databasetable">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Alerts</h6>
		<p class="card_subheading">Here you can manage additional alerts that appear on your rescue's dashboard or patient profiles</p><br>
		<!-- Alert Button -->
        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addalertModal"> Add Centre Alert</button> 
        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addpatientalertModal"> Add Patient Alert</button> 
	</div>
    <div class="card-body">
		<?php include_once("operations/centre_alerts.php"); ?><br>	
		<?php include_once("operations/patient_alerts.php"); ?>	
	</div>  <br>
</div>			
	
	</div>
</div>

<!-- MANAGE DUPLICATE PATIENTS-->
		
<div class="card shadow mb-4" id="databasetable">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Non-admitted Patients</h6>
    </div>
    <div class="card-body">
		<div class="alert alert-danger" role="alert">
  Deleting patients can not be undone. They can not be recovered. Be certain you wish to delete.
        </div>
    
<table class="table table-bordered table-sm table-hover" id="addnewpts" width="100%" cellspacing="0">
    <thead class="thead-dark">
    <tr>
        <th class="align-middle" width="180"><?php echo $lang['NAME']; ?>/<?php echo $lang['IDENTIFIER']; ?></th>
	    <th class="align-middle" width="100" class="align-middle"><?php echo $lang['SEX']; ?></th>
        <th class="align-middle" width="50"><?php echo $lang['RINGED']; ?>?</th>
	    <th class="align-middle" width="70"><?php echo $lang['RING']; ?> <?php echo $lang['NUMBER_ABBR']; ?>:</th>
        <th class="align-middle" width="50"><?php echo $lang['MICROCHIP']; ?>?</th>
	    <th class="align-middle" width="70"><?php echo $lang['MICROCHIP']; ?> <?php echo $lang['NUMBER_ABBR']; ?>:</th>
        <th class="align-middle" width="200"><?php echo $lang['SPECIES']; ?></th>
        <th class="align-middle" width="110"></th>
    </tr>
    </thead>
    <tbody>
    <?php			
        ///Loop from admissions table
        $stmt = $conn->prepare("SELECT *
        FROM rescue_patients
        WHERE rescue_patients.centre_id = :centre_id AND rescue_patients.state = 'To admit' 
        ORDER by name ASC");
        $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);

        // initialise an array for the results
        $notadmitted = array();
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pt_name= $row["name"];
        $newpt_id = $row["patient_id"];
        $pt_ringed = $row["ringed"];
        $pt_ring_no = $row["ring_number"];
        $pt_micro = $row["microchipped"];
        $pt_micro_no = $row["microchip_number"];
        $pt_animal_type = $row["animal_type"];
        $pt_animal_order = $row["animal_order"];
        $pt_animal_species = $row["animal_species"];
        $pt_sex = $row["sex"];                               							
	?>

    <tr>
        <td><b>CRN: <?php echo $newpt_id;?></b><br><?php echo $pt_name; ?></td>
        <td><?php echo $pt_sex; ?></td>
        <td><?php echo $pt_ringed; ?></td>
        <td><?php echo $pt_ring_no; ?></td>
        <td><?php echo $pt_micro; ?></td>
        <td><?php echo $pt_micro_no; ?></td>
        <td><?php echo $pt_animal_order; ?> - <?php echo $pt_animal_type; ?>, <?php echo $pt_animal_species; ?></td>
        <td>
        <form method="post" action="">
            <input type="hidden" id="patient_id" name="patient_id" value="<?php echo $newpt_id; ?>">
            <button type="submit" class="btn btn-secondary btn-danger" name="deletepatient"><b>Delete</b></button> 
        </form>
        
        </td> <?php }?>
    </tr>
    <tr>
    
</tbody>

</table>


</div> 
</div>

<!-- END OF MANAGE USERS CARD -->	


<!-- ROTA CARD -->
		
<div class="card shadow mb-4" id="databasetable">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Calendar (in development)</h6>
    </div>
    <div class="card-body">
		<?php include ("settings/calendar_view.php"); ?>
</div> 
</div>

<!-- ROTA CARD -->				
	





 <!-- SETTINGS CARD -->
			
        <div class="card shadow mb-4" id="databasetable">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Edit Your Rescue Centre</h6>
                <p class="card_subheading">You can edit the details for your rescue centre, using the form below.</p>
            </div>
            <div class="card-body">
                <form action="" method="post" class="lead_form" id="manualForm">

                    <div class="row lead_form_row">
                        <div class="col-md-6">
                            <p class="angelo_form_label">Rescue Centre Name</p>
                            <input type="text" name="rescue_name" id="rescue_name" value="<?php echo $rescue_name; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <p class="angelo_form_label">Centre Type</p>
                            <select name="centre_type" id="centre_type">
                                <option value="<?php echo $centre_type; ?>" selected><?php echo $centre_type; ?></option>
                                <option value="Rescue Centre">Rescue Centre</option>
                                <option value="Vet">Vet</option>
                            </select>
                        </div>
                    </div>

                    <div class="row lead_form_row">
                        <div class="col-md-6 my-auto">
                            <p class="angelo_form_label">Email Address</p>
                            <input type="text" name="email" id="email" value="<?php echo $email; ?>" required>
                        </div>

                        <div class="col-md-6">
                            <p class="angelo_form_label">Office Telephone</p>
                            <input type="text" name="office_tel" id="office_tel" value="<?php echo $office_tel; ?>">
                        </div>
                    </div>

                    <div class="row lead_form_row">
                        <div class="col-md-6 my-auto">
                            <p class="angelo_form_label">Mobile Number</p>
                            <input type="text" name="mobile" id="mobile" value="<?php echo $mobile; ?>">
                        </div>

                        <div class="col-md-6">
                            <p class="angelo_form_label">24 Hour Number</p>
                            <input type="text" name="twentyfour" id="twentyfour" value="<?php echo $twentyfour; ?>">
                        </div>
                    </div>

                    <div class="row lead_form_row">
                        <div class="col-md-6 my-auto">
                            <p class="angelo_form_label">Address Line One</p>
                            <input type="text" name="address_line_one" id="address_line_one" value="<?php echo $address_line_one; ?>" required>
                        </div>

                        <div class="col-md-6 my-auto">
                            <p class="angelo_form_label">Address Line Two</p>
                            <input type="text" name="address_line_two" id="address_line_two" value="<?php echo $address_line_two; ?>">
                        </div>
                    </div>

                    <div class="row lead_form_row">
                        <div class="col-md-6 my-auto">
                            <p class="angelo_form_label">City</p>
                            <input type="text" name="city" id="city" value="<?php echo $city; ?>" required>
                        </div>

                        <div class="col-md-6 my-auto">
                            <p class="angelo_form_label">Postcode</p>
                            <input type="text" name="postcode" id="postcode" value="<?php echo $postcode; ?>" required>
                        </div>
                    </div>

                    <div class="row lead_form_row">

                        <div class="col-md-6">
                            <p class="angelo_form_label">Accepting Admissions</p>
                            <select name="accepting_admissions" id="accepting_admissions">
                                <option value="<?php echo $accepting_admissions; ?>" selected><?php echo $accepting_admissions; ?></option>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                        </div>


                        <div class="col-md-6">
                            <p class="angelo_form_label">Opening Hours</p>
                            <textarea id="opening_hours" name="opening_hours" rows="4" cols="50"><?php echo $opening_hours; ?></textarea>
                        </div>

                    </div>


                    <br />
                    <input type="hidden" id="centre_id" name="centre_id" value="<?php echo $centre_id; ?>">
                    <input type="submit" name="form3" value="Update Centre Settings">

                </form>

            </div>
			 </div></div>
<!-- END OF CENTRE SETTINGS CARD ---->		
	
				
		</div>


    </div>
    <!-- /.container-fluid -->



   


<?php include_once "app_footer.php";?>
</div>
<!-- Page level plugins -->
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/vendor/datatables/dataTables.bootstrap4.min.js"></script>

<!-- Page level custom scripts -->
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/js/demo/datatables-demo.js"></script>

 <script type="text/javascript">
                        function deleteLocation(location_id) {
                            if (location_id == "") {
                                console.log("Not found");
                                return;
                            } else {
                                var xmlhttp = new XMLHttpRequest();
                                xmlhttp.onreadystatechange = function() {
                                    if (this.readyState == 4 && this.status == 200) {
                                        console.log("success");
                                        var comment = document.getElementById(location_id);
                                        comment.style.display = 'none'; //or
                                    }
                                };
                                xmlhttp.open("GET", "https://rescuecentre.org.uk/wp-content/themes/brikk-child/ajax/delete_location.php?id=" + location_id, true);
                                xmlhttp.send();
								
							window.location.reload();	
                            }
							
                        }
						
                    </script>
					




<script>
    //AJAX Scripts

    //Update Settings AJAX
    $(document).ready(function() {
        $('#manualForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                type: 'post',
                url: 'https://rescuecentre.org.uk/wp-content/themes/brikk-child/ajax/edit_centre.php',
                data: $('#manualForm').serialize(),
                success: function() {
                    location.reload();
                } 
            });
        });
    });
</script>


<!-- Add an "active" CSS class to the current page on the menu -->
<script>
    document.getElementById("settings_link").classList.add("active");
</script>
<script>
    document.getElementById("settings_link_2").classList.add("active");
</script>

</div>
<!-- End of Main Content -->
