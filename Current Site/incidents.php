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

/* Template Name: Incidents */

get_header();

include_once "app_header.php";

$current_user_id = get_current_user_id();

?>

<?php include ("incidents/add_incidents.php"); ?>


<div id="page-top">


    <!-- Begin Page Content -->
    <div class="container-fluid">

        <!-- Page Heading -->
        <div>
            <div class="row dashboard_heading_withfilter">
                <div class="col-md-6 my-auto">
                    <h1 class="h3 mb-0 text-gray-800 portal_heading"><?php echo $lang['LM_INCIDENTS']; ?></h1>
                </div>

            </div>
        </div>
  


        <!-- Display list of incidents -->
        <div class="card shadow mb-4" id="databasetable">
            <div class="card-header py-3">
                <p class="card_subheading"><button type="button" class="btn btn-outline-success" data-toggle="modal" data-target="#add_incidentModal"> <?php echo $lang['INC_BUT_ADD_NEW']; ?></button></p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered angelo_table" id="" width="100%" cellspacing="0">
                        <thead>
                            <tr>
							    <th><?php echo $lang['INCIDENT']; ?><br><?php echo $lang['NUMBER_ABBR']; ?></th>
                                <th><?php echo $lang['DATE_OF']; ?><BR><?php echo $lang['INCIDENT']; ?></th>
                                <th><?php echo $lang['LOCATION']; ?></th>
                                <th><?php echo $lang['INC_CENTRE_REF']; ?></th>
                                <th><?php echo $lang['INC_TOTAL_CASUALTIES']; ?></th>
                                <th><?php echo $lang['INC_DOA_CASUALTIES']; ?></th>
                                <th><?php echo $lang['INC_MASS_CASUALTY']; ?></th>
                                <th width="160" ></th>
								                               
                            </tr>
                        </thead>
                        <tbody>

                            <?php
                            //get orescriotioins
                            $stmt = $conn->prepare("SELECT * from rescue_incidents
										WHERE rescue_incidents.centre_id=:centre_id
										ORDER BY incident_id ASC");
                            $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);

                            // initialise an array for the results
                            $incidents = array();
                            $stmt->execute();
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                                $inc_id = $row["incident_id"];
								$inc_date = $row["incident_date"];
                                $inc_add_1 = $row["incident_location_line_1"];
                                $inc_line_2 = $row["incident_location_line_2"];
                                $inc_city = $row["incident_location_city"];
								$inc_postcode = $row["incident_location_postcode"];
								$inc_centre_ref = $row["incident_centre_ref"];
								$inc_tot_cas = $row["incident_total_casualties"];
                                $inc_mass_cas = $row["incident_mass_cas"];
								$inc_doa_cas = $row["incident_doa"]; 
                                
                            // yes no instead of 1 and 0
                            if ($inc_mass_cas == '1') {
                            $mas_cas = $lang['YES'];
                            } elseif ($inc_mass_cas== '0') {
                            $mas_cas = $lang['NO'];
                            }      
                                ?>
  

                        <tr>
						    <td>INC-<?php echo $inc_id; ?></td>
                            <td><?php echo $inc_date; ?></td>
                            <td><?php echo $inc_add_1; ?>, <?php echo $inc_line_2; ?>, <?php echo $inc_city; ?>.</td>
                            <td><?php echo $inc_centre_ref; ?></td>
                            <td><?php echo $inc_tot_cas ?></td>
                            <td><?php echo $inc_doa_cas; ?></td>
                            <td><?php echo $mas_cas; ?></td> 
                            <td class="align-middle"> <a href="https://rescuecentre.org.uk/incidents/?incident_id=<?php echo $inc_id; ?>" type="button" class="btn btn-sm btn-outline-primary" data-toggle="tooltip" data-placement="top" title="View incident"><i class="fas fa-paperclip" ></i> <?php echo $lang['INC_BUT_LINK']; ?></a>	</td><?php } ?>
                            </tr>
                           


                        </tbody> 
                    </table>
                </div>
            </div>
        </div>
        <!------------------------------------------------------->
		

        <?php include ("incidents/view_incident.php"); ?>
    <!-- /.container-fluid -->

</div>
<!-- End of Main Content -->


<?php include_once "app_footer.php";
?>
<!-- Page level plugins -->
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/vendor/datatables/dataTables.bootstrap4.min.js"></script>

<!-- Page level custom scripts -->
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/js/demo/datatables-demo.js"></script>






<!-- Add an "active" CSS class to the current page on the menu -->
<script>
    document.getElementById("incidents_link").classList.add("active");
</script>


</div>
<!-- End of Main Content -->
<?php echo "</div>";