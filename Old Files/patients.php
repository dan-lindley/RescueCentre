<?php defined('ABSPATH') or die('This script cannot be accessed directly.');

//Report all errors except E_NOTICE   
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL); 

include_once "authentication.php";
include_once "connect_to_mysql.php";

echo "<div class='app_page_container'>";
/**
 * The template for displaying pages
 *
 * Do not overload this file directly. Instead have a look at templates/single.php file in us-core plugin folder:
 * you should find all the needed hooks there.
 */

/* Template Name: Patients */

get_header();


include_once "app_header.php";

$current_user_id = get_current_user_id();
$alertMsg = "";
?>

<div id="page-top">
<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div>
        <div class="row dashboard_heading_withfilter">
            <div class="col-md-6 my-auto">
            <h1 class="h3 mb-0 text-gray-800 portal_heading"><?php echo $lang['LM_MY_PATIENTS']; ?></h1>
            </div>
        </div>
    </div>
<div id="alertMsg2"><?php echo $alertMsg; ?></div>
		
<?php include ("my_patients.php"); ?>

       
<!-- Add A New patient -->
</div>		                          
<div class="card shadow mb-4">
<div class="card-header py-3">
    <h6 class="m-0 font-weight-bold text-primary"><?php echo $lang['ADD_PATIENTS']; ?></h6>
</div>
<div class="card-body">	
	
<div class="table-responsive">
    
<table class="table table-bordered table-sm table-hover" id="addnewpts" width="100%" cellspacing="0">
    <thead class="thead-dark">
    <tr>
        <th class="align-middle" width="180"><?php echo $lang['NAME']; ?>/<?php echo $lang['IDENTIFIER']; ?></th>
	    <th class="align-middle" width="100" class="align-middle"><?php echo $lang['SEX']; ?></th>
        <th class="align-middle" width="400"><?php echo $lang['SPECIES']; ?></th>
        <th class="align-middle" width="130"><?php echo $lang['DATE_OF']; ?> <?php echo $lang['BIRTH']; ?></th>
        <th class="align-middle" width="130"><?php echo $lang['RINGED']; ?>?</th>
        <th class="align-middle" width="130"><?php echo $lang['MICROCHIP']; ?>?</th>
        <th class="align-middle" width="110"></th>
    </tr>
    </thead>
    <tbody>
    <?php			
        ///Loop from patients table of to admit patints
        $stmt = $conn->prepare("SELECT patient_id, name, ringed, approx_dob, ring_number, microchipped, microchip_number, animal_type, animal_order, sex, animal_species
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
        $pt_dob = $row["approx_dob"];     

/*function displayAge($pt_dob) {
    $dob = new DateTime($pt_dob);
    $now = new DateTime();
    $diff = $dob->diff($now);

    // If less than 1 month → show days
    if ($diff->y == 0 && $diff->m == 0) {
        if ($diff->d == 1) {
            return "1 day old";
        }
        return $diff->d . " days old";
    }

    // If less than 6 months → show weeks
    if ($diff->y == 0 && $diff->m < 6) {
        $weeks = floor($diff->days / 7);
        if ($weeks == 1) {
            return "1 week old";
        }
        return $weeks . " weeks old";
    }

    // If less than 1 year → show months
    if ($diff->y == 0 && $diff->m >= 6) {
        if ($diff->m == 1) {
            return "1 month old";
        }
        return $diff->m . " months old";
    }

    // 1 year or more → years + months
    $ageString = $diff->y . " year" . ($diff->y > 1 ? "s" : "");
    if ($diff->m > 0) {
        $ageString .= " and " . $diff->m . " month" . ($diff->m > 1 ? "s" : "");
    }
    return $ageString . " old";
}*/

	?>

    <tr>
        <td><b>CRN: <?php echo $newpt_id;?></b><br><?php echo $pt_name; ?></td>
        <td><?php echo $pt_sex; ?></td>
        <td><?php echo $pt_animal_order; ?> - <?php echo $pt_animal_type; ?>, <?php echo $pt_animal_species; ?></td>
        <td><?php echo $pt_dob; ?>
            <BR>(<?php //echo displayAge($pt_dob); ?>)</td>
        <td><?php echo $pt_ringed; ?><BR>(<?php echo $pt_ring_no; ?>)</td>
        <td><?php echo $pt_micro; ?><br>(<?php echo $pt_micro_no; ?>)</td>
 
        <td>
            <a href="https://rescuecentre.org.uk/new_admission/?patient_id=<?php echo $newpt_id; ?>&form=new_admission" type="button" class="btn btn-success" data-toggle="tooltip" data-placement="top" title="Admit Patient"><b>Admit</b></a>
            <a href="https://rescuecentre.org.uk/new_admission/?patient_id=<?php echo $newpt_id; ?>&form=doa" type="button" class="btn btn-danger" data-toggle="tooltip" data-placement="top" title="Add DOA or Euthanised Record"><b>DOA</b></a>		
        </td> <?php }?>
    </tr>

    <tr>
    <form action="https://rescuecentre.org.uk/wp-content/themes/brikk-child/ajax/insert_patient.php" method="post" class="lead_form" id="manualForm" onSubmit="window.location.reload()">
        <td class="align-middle"><input type="text" placeholder="<?php echo $lang['NAME']; ?>/<?php echo $lang['IDENTIFIER']; ?>" name="name" id="name" required><?php echo $errorName; ?></td>
        
        <td class="align-middle"><select name="sex" name="sex" id="sex">
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Female (lactating)">Female (lactating)</option>
                <option value="Female (pregnant)">Female (pregnant)</option>
                <option value="Undetermined">Undetermined</option>
            </select> <?php echo $errorSex; ?></td>

        <td class="align-middle">
            <div class="row lead_form_row">
                <div class="col-md-4 my-auto">
                    <select id="animal_orders" name="animal_orders">
                        <option value="" disabled selected><?php echo $lang['SELECT_A'];?> <?php echo $lang['ANIMAL_ORDER'];?></option>
                        <option value="Amphibian"><?php echo $lang['AC_AMPHIBIAN'];?></option>
                        <option value="Bird"><?php echo $lang['AC_BIRD'];?></option>
                        <option value="Fish"><?php echo $lang['AC_FISH'];?></option>
                        <option value="Mammal"><?php echo $lang['AC_MAMMAL'];?></option>
                        <option value="Reptile"><?php echo $lang['AC_REPTILE'];?></option>
                        <option value="Unknown"><?php echo $lang['AC_UNKNOWN'];?></option>
                    </select><?php echo $errorOrder; ?>
                </div>
                <div class="col-md-4 my-auto">  
                    <select id="animal_types" name="animal_types">
                        <option>Please select an animal type</option>
                    </select><?php echo $errorType; ?>
                </div>
                <div class="col-md-4 my-auto">    
                    <select id="animal_species" name="animal_species">
                        <option>Please select an animal species</option>
                    </select><?php echo $errorSpecies; ?>
                </div>
            </div>
        </td>

        <td class="align-middle"><input type="date" id="dob" name="dob"></td>

        <td class="align-middle"><select name="ringed" name="ringed" id="ringed" required>
                <option value="Yes"><?php echo $lang['YES'];?></option>
                <option value="No" selected><?php echo $lang['NO'];?></option>
            </select><?php echo $errorRinged; ?><br>
            <input type="text" placeholder="<?php echo $lang['RING']; ?> <?php echo $lang['NUMBER_ABBR']; ?>" name="ring_number" id="ring_number">
        
        
        </td>
        
        

        <td class="align-middle"><select name="microchipped" name="microchipped" id="microchipped">
                <option value="Yes"><?php echo $lang['YES'];?></option>
                <option value="No" selected><?php echo $lang['NO'];?></option>
            </select><?php echo $errorMicrochipped; ?><BR>
        
        <input type="text" placeholder="<?php echo $lang['MICROCHIP']; ?> <?php echo $lang['NUMBER_ABBR']; ?>" name="microchip_number" id="microchip_number">
        </td>
        
        <td class="align-middle"><button type="submit" class= "btn btn-success" name="form1"><i class="fas fa-save"></i></button>
            <input type="hidden" name="thestaffid" value="<?php echo $current_user_id; ?>">
			<input type="hidden" name="state" value="To admit">
            <input type="hidden" name="status" value="Captive">
            <input type="hidden" name="centre_id" value="<?php echo $centre_id; ?>">
            </form></td>
    </tr>

</tbody>

</table>

            </div>
        </div>
    </div>
</div>
<!-- End of Main Content -->

<?php include_once "app_footer.php"; ?>

<!-- Page level plugins -->
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/vendor/datatables/dataTables.bootstrap4.min.js"></script>

<!-- Page level custom scripts -->
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/js/demo/datatables-demo.js"></script>



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


<script>
    //AJAX Scripts

    //Insert Patient AJAX
    $(document).ready(function() {
        $('#manualForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                type: 'post',
                url: 'https://rescuecentre.org.uk/wp-content/themes/brikk-child/ajax/insert_patient.php',
                data: $('#manualForm').serialize(),
                success: function() {
                    var currentFilter = document.getElementById("status_filter").value;
                    getPeople(currentFilter, <?php echo $current_user_id; ?>);
                    document.getElementById("alertMsg").innerHTML = '<div class="alert alert-success" role="alert">Your patient has been added to the database.</div>';
                }
            });
        });
    });
</script>

<!-- Add an "active" CSS class to the current page on the menu -->
<script>
    document.getElementById("patients_link").classList.add("active");
</script>


</div>
<!-- End of Main Content -->
<?php get_footer();


echo "</div>";