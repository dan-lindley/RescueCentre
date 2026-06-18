 <?php defined('ABSPATH') or die('This script cannot be accessed directly.');

include_once "authentication.php";
include_once "connect_to_mysql.php";


echo "<div class='app_page_container'>";
/**
 * The template for displaying pages
 *xx
 * Do not overload this file directly. Instead have a look at templates/single.php file in us-core plugin folder:
 * you should find all the needed hooks there.
 */

/* Template Name: Resources */ 

get_header();
$current_user_id = get_current_user_id();

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

} else {
    echo "Rescue centre not found";
    exit();

}

// CHECK POST FOR KEYWORD
if (isset($_POST['Search'])) {

$keyword = $_POST["keyword"];

}
?>
 
    <!-- Begin Page Content -->
    <div class="container-fluid">

        <!-- Page Heading -->
        <div>
            <div class="row dashboard_heading_withfilter">
                <div class="col-md-6 my-auto">
                    <h1 class="h3 mb-0 text-gray-800 portal_heading">Useful Resources</h1>
                    
                </div>
            </div>
        </div>

<div class="card shadow mb-4">
<div class="card-header py-3">
    <h6 class="m-0 font-weight-bold text-primary">Useful Documents/Links</h6>   
</div>
<div class="card-body">	
<a href="https://www.rcvs.org.uk/document-library/bvzs-good-practice-guidelines-for-wildlife-rehabilitation/" target="_blank">BVZS Good Practice Guidlines for Wildlife Rehabilitation</a>
<br><a href="https://www.bats.org.uk/resources/guidance-for-professionals/bat-care-guidelines-a-guide-to-bat-care-for-rehabilitators" target="_blank">Bat Conservation Trust - Bat Care Guidelines</a>
<br><a href="https://www.bwrc.org.uk/bwrc-guidelines/">British Wildlife Rehabilitation Council Guidelines</a>
<br><a href="https://www.bwrc.org.uk/wildlife-images-and-social-media/">Social Media and Wildlife (BWRC)</a>
<br><a href="https://www.helpanimals.co.uk/portal/">Help Animals - Portal of resources for Rescue Centres</a>
</div>
</div>

<!-- Display species  from the database -->
<div class="card shadow mb-4">
<div class="card-header py-3">
    <h6 class="m-0 font-weight-bold text-primary">View All Species</h6>
    <P>Prior to adding any species to the database please check from the table below that the species is not already in the database
   
</div>
<div class="card-body">	

<div class="table-responsive">
<table class="display compact" id="allspeciestable" width="100%" cellspacing="0">
  <thead class="thead-dark">
    <tr>                  
	<th class="align-middle" width="200">Species Name</th>
    <th class="align-middle">Type</th>
	<th class="align-middle">Scientific Name</th>  
	<th class="align-middle">Weight From</th>
    <th class="align-middle">Weight To</th>
    <th class="align-middle">Measurement from</th>
    <th class="align-middle">Measurement to</th>
    <th class="align-middle">Reference</th>
    <th class="align-middle">IUCN Status</th>
    <th></th>
    </tr>
  </thead>
  <tbody>
     <?php			
      //Loop from admissions table
      $stmt = $conn->prepare("SELECT *
      FROM rescue_animal_species
      ORDER by species_name ASC");
      // initialise an array for the results
      $species = array();
      $stmt->execute();
      while ($row = $stmt->fetch()) {
      $sp_name = $row["species_name"];
      $sp_scient = $row["scientific_name"];
      $sp_type = $row["animal_type"];
      $sp_weight_f = $row["species_weight_from"];
      $sp_weight_t = $row["species_weight_to"];
      $sp_weight_u = $row["species_weight_unit"];
      $sp_measure_f = $row["species_measurement_from"];
      $sp_measure_t = $row["species_measurement_to"];
      $sp_measure_u = $row["species_measurement_unit"];
      $sp_measure_s = $row["species_measurement_standard"];
      $sp_reference = $row["reference"];
      $sp_iucn = $row["iucn_status"];       

      //colour code IUCN status
      if ($sp_iucn == "") {
        $iucnclass = '';
        } elseif ($sp_iucn == "Critically Endangered") {
        $iucnclass = 'table-dark';
         } elseif ($sp_iucn == "Vulnerable") {
        $iucnclass = 'table-danger';
         } elseif ($sp_iucn == "Near Threatened") {
        $iucnclass = 'table-warning';
    } elseif ($sp_iucn = 'Data Deficient') {
        $iucnclass = 'table-secondary';
            } elseif ($sp_iucn == "Least Concern") {
        $iucnclass = 'table-primary';
         }

	?>
      <tr>
        <td class="align-middle"><?php echo $sp_name ?></td>
		<td class="align-middle"><?php echo $sp_type; ?></td>
		<td class="align-middle"><?php echo $sp_scient ?></td> 
		<td class="align-middle"> <?php echo $sp_weight_f, $sp_weight_u; ?></td> 
		<td class="align-middle"><?php echo $sp_weight_t, $sp_weight_u; ?></td> 
		<td class="align-middle"><?php echo $sp_measure_f, $sp_measure_u; ?><BR>(<?php echo $sp_measure_s ?>)</td> 
        <td class="align-middle"> <?php echo $sp_measure_t, $sp_measure_u; ?><BR>(<?php echo $sp_measure_s ?>)</td>
        <td class="align-middle"><?php echo $sp_reference ?></td> 
        <td class="align-middle <?php echo $iucnclass; ?>"><?php echo $sp_iucn ?></td> 
        <td class="align-middle">

					<?php } ?> 
				</td>
      </tbody>
  </table>
       </div></div></div>


<div class="card shadow mb-4">
    <div class="card-header py-3">
    <h6 class="m-0 font-weight-bold text-primary">Add Species</h6> 
    <b>HAVE YOU CHECKED THE TABLE ABOVE PRIOR TO SUBMITTING?</b>
    </div>
<div class="card-body">	
  <?php include ("care_plans/add_species.php"); ?>	
</div>
        </div></div>
 

          
				
        <!------------------------------------------------------->



<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
<link href="DataTables/datatables.min.css" rel="stylesheet">
<link href="https://rescuecentre.org.uk/wp-content/themes/brikk-child/vendor/datatables/datatables.css" rel="stylesheet">
<script src="DataTables/datatables.min.js"></script>
<script>
new DataTable('#allspeciestable', {
   layout: {
        bottomEnd: {
            paging: {
                firstLast: false
            }
        }
    }
	 });
</script>