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

/* Template Name: User Profile Edit */

get_header();
$current_user_id = get_current_user_id();

include_once "app_header.php";

$current_user_id = get_current_user_id();

?>
    <!-- Begin Page Content -->
    <div class="container-fluid">

        <!-- Page Heading -->
        <div>
            <div class="row dashboard_heading_withfilter">
                <div class="col-md-6 my-auto">
                    <h1 class="h3 mb-0 text-gray-800 portal_heading">Edit User Profile</h1>
                </div>
            </div>
        </div>

    
 <!-- Display query from the database -->
        <div class="card shadow mb-4" id="databasetable">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">My information</h6>
    
 
            <div class="card-body">
          

                            <?php
                            //Find applicants in the WP Users table. Make sure they aren't already a member 
                            $stmt = $conn->prepare("SELECT * from wpxp_users
													INNER JOIN rescue_roles 
														ON rescue_roles.role_id=wpxp_users.rescue_role
													INNER JOIN rescue_centres 
														ON rescue_centres.rescue_id = wpxp_users.centre_id
													WHERE ID = ' ". $current_user_id. " '");
                            

                            // initialise an array for the results
                            $userinfo = array();
                            $stmt->execute();
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                                $user_login = $row["user_login"];
								$user_nicename = $row["user_nicename"];
                                $user_email = $row["user_email"];
                                $display_name = $row["display_name"];
								$centre = $row["rescue_name"];
								$rescue_role = $row["role_name"];
                              	
  

                                print '
                            <strong>User: </strong>' . $display_name . ' <br>
                            <strong>Login: </strong>' . $user_nicename . ' <br>
                            <strong>Email: </strong>' . $user_email. ' <br>
                            <strong>Centre: </strong>' . $centre . ' <br>       
                            <strong>Role: </strong>' . $rescue_role . ' <br>		
							';
	
                    }

                    ?>


                        </tbody> 
                    </table>

			
			
                </div>
            </div>
        </div>
        <!------------------------------------------------------->



















   
<!-- edit user details modal -->

				   
                    <div class="modal fade" id="querybuilderModal" tabindex="-1" role="dialog" aria-labelledby="profileeditModal" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                               <div class="modal-header">
                                    <h4 class="font-weight-bold text-primary">Edit my details</h4>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">

                   
                                        </div></div></div></div>

                    <!--- End Of Notes ---->
                         
                        
                    

                    <!---------------END of Query builder modal ----------------------------------------------------------->



<?php include_once "app_footer.php";
?>

</div>


<!-- End of Main Content -->

<?php
echo "</div>";?>