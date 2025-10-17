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

/* Template Name: Onboarding Form */

get_header();


function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

include_once "app_header.php";

$current_user_id = get_current_user_id();

//Get the current ID of the last rescue centre which was added to the database
$sql = 'SELECT * FROM rescue_centres ORDER by rescue_id DESC LIMIT 1';
$statement = $conn->prepare($sql);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
/*---------------------------------------------------------------------------------*/
if ($result) {
    $last_added_id = $result["rescue_id"];
}
/*---------------------------------------------------------------------------------*/

$new_centre_id = $last_added_id + 1;


//Onboarding Form Processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //Set variables using the POST data from the form
    $rescue_name = test_input($_POST["rescue_name"]);
    $centre_type = test_input($_POST["centre_type"]);
    $email = test_input($_POST["email"]);
    $office_tel = test_input($_POST["office_tel"]);
    $mobile = test_input($_POST["mobile"]);
    $twentyfour = test_input($_POST["twentyfour"]);
    $address_line_one = test_input($_POST["address_line_one"]);
    $address_line_two = test_input($_POST["address_line_two"]);
    $city = test_input($_POST["city"]);
    $postcode = test_input($_POST["postcode"]);
    $accepting_admissions = test_input($_POST["accepting_admissions"]);
    $opening_hours = test_input($_POST["opening_hours"]);



    //INSERT a record into the rescue_centres table
    try {
        $statement = $conn->prepare('INSERT INTO rescue_centres
        (rescue_name,
        owner_id,
        centre_type,
        email, 
        office_tel,
        mobile, 
        24_hour,
        address_line_one, 
        address_line_two, 
        city, 
        postcode, 
        accepting_admissions,
        opening_hours)

        VALUES ( 
        :rescue_name,
        :owner_id,
        :centre_type,
        :email, 
        :office_tel,
        :mobile, 
        :24_hour,
        :address_line_one, 
        :address_line_two, 
        :city, 
        :postcode, 
        :accepting_admissions,
        :opening_hours)');

        $statement->execute([
            
            'rescue_name'  => $rescue_name,
            'owner_id' => $current_user_id,
            'centre_type'  => $centre_type,
            'email'  => $email,
            'office_tel'  => $office_tel,
            'mobile'  => $mobile,
            '24_hour'  => $twentyfour,
            'address_line_one' => $address_line_one,
            'address_line_two'  => $address_line_two,
            'city'  => $city,
            'postcode'  => $postcode,
            'accepting_admissions'  => $accepting_admissions,
            'opening_hours'  => $opening_hours
        ]);
    } catch (PDOException $e) {
        die($e->getMessage());
    }

    //UPDATE the wpxp_users table
    $rescue_role = 1;

    try {
        //Update the database table
        $query = "UPDATE wpxp_users SET 
        centre_id = :centre_id,
        rescue_role = :rescue_role
        WHERE ID = :wpxp_user_id LIMIT 1";

        $stmt = $conn->prepare($query);
        $stmt->bindParam('centre_id', $new_centre_id, PDO::PARAM_INT);
        $stmt->bindParam('wpxp_user_id', $current_user_id, PDO::PARAM_INT);
        $stmt->bindParam('rescue_role', $rescue_role, PDO::PARAM_INT);
        
        $stmt->execute();
    } catch (PDOException $e) {
        echo $e->getMessage();
        die($e->getMessage());
    }

    echo '<script>window.location.replace("https://rescuecentre.org.uk/dashboard");</script>';
}


?>

<div id="page-top">

    <!-- Begin Page Content -->
    <div class="container-fluid">

        <!-- Page Heading -->
        <div>
            <div class="row dashboard_heading_withfilter">
                <div class="col-md-6 my-auto">
                    <h1 class="h3 mb-0 text-gray-800 portal_heading">Rescue Centre Onboarding Form</h1>
                </div>
            </div>
        </div>
        <div id="alertMsg"><?php echo $alertMsg; ?></div>


        <!-- Display people from the database -->
        <div class="card shadow mb-4" id="databasetable">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Add Your Rescue Centre</h6>
                <p class="card_subheading">To start using Rescue Centre, please enter your centre's details using the form below.</p>
            </div>
            <div class="card-body">
                <form action="https://rescuecentre.org.uk/onboarding-form/" method="post" class="lead_form" id="manualForm">

                    <div class="row lead_form_row">
                        <div class="col-md-6">
                            <p class="angelo_form_label">Rescue Centre Name</p>
                            <input type="text" name="rescue_name" id="rescue_name" value="" required>
                        </div>
                        <div class="col-md-6">
                            <p class="angelo_form_label">Centre Type</p>
                            <select name="centre_type" id="centre_type">
                                <option value="Rescue Centre">Rescue Centre</option>
                                <option value="Vet">Vet</option>
                            </select>
                        </div>
                    </div>

                    <div class="row lead_form_row">
                        <div class="col-md-6 my-auto">
                            <p class="angelo_form_label">Email Address</p>
                            <input type="text" name="email" id="email" value="" required>
                        </div>

                        <div class="col-md-6">
                            <p class="angelo_form_label">Office Telephone</p>
                            <input type="text" name="office_tel" id="office_tel" value="">
                        </div>
                    </div>

                    <div class="row lead_form_row">
                        <div class="col-md-6 my-auto">
                            <p class="angelo_form_label">Mobile Number</p>
                            <input type="text" name="mobile" id="mobile" value="">
                        </div>

                        <div class="col-md-6">
                            <p class="angelo_form_label">24 Hour Number</p>
                            <input type="text" name="twentyfour" id="twentyfour" value="">
                        </div>
                    </div>

                    <div class="row lead_form_row">
                        <div class="col-md-6 my-auto">
                            <p class="angelo_form_label">Address Line One</p>
                            <input type="text" name="address_line_one" id="address_line_one" value="" required>
                        </div>

                        <div class="col-md-6 my-auto">
                            <p class="angelo_form_label">Address Line Two</p>
                            <input type="text" name="address_line_two" id="address_line_two" value="">
                        </div>
                    </div>

                    <div class="row lead_form_row">
                        <div class="col-md-6 my-auto">
                            <p class="angelo_form_label">City</p>
                            <input type="text" name="city" id="city" value="" required>
                        </div>

                        <div class="col-md-6 my-auto">
                            <p class="angelo_form_label">Postcode</p>
                            <input type="text" name="postcode" id="postcode" value="" required>
                        </div>
                    </div>

                    <div class="row lead_form_row">

                        <div class="col-md-6">
                            <p class="angelo_form_label">Accepting Admissions</p>
                            <select name="accepting_admissions" id="accepting_admissions">
                             
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                        </div>


                        <div class="col-md-6">
                            <p class="angelo_form_label">Opening Hours</p>
                            <textarea id="opening_hours" name="opening_hours" rows="4" cols="50"></textarea>
                        </div>

                    </div>


                    <br />

                    <input type="submit" name="form3" value="Update Centre Settings">

                </form>

            </div>
        </div>
        <!------------------------------------------------------->


    </div>
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






<script>
    //AJAX Scripts
</script>

<!-- Add an "active" CSS class to the current page on the menu -->
<script>
    document.getElementById("settings_link").classList.add("active");
</script>


</div>
<!-- End of Main Content -->
<?php get_footer();


echo "</div>";