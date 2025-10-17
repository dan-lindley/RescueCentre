<?php
include_once "../authentication.php";
include_once "../connect_to_mysql.php";

/* PHP script to create a ew user */
// Extra info to add

// All fields needed - loginname, passowrd, Display name (usernicename and display name), centre_id, Rescue_role. user email
//get post data form form
if (isset($_POST['adduser'])) {

$login = $_POST ['login'];
$pass = $_POST ['pass'];
$username =$_POST ['username'];
$email = $_POST ['email'];
$centre_id = $_POST ['centre_id'];
$role = $_POST ['role'];

{

$userdata = array(
    'user_login' =>  $login,
    'user_nicename'=> $username,
    'display_name'=>$username,
    'user_email'=>  $email,
    'user_pass' => $pass,
);


$user_id = wp_insert_user( $userdata ) ;
// update the new user with the rescue information
try {

$query1 = "UPDATE wpxp_users
                      SET 
                  wpxp_users.rescue_role = :rescue_role,
                  wpxp_users.centre_id = :centre_id
                  WHERE wpxp_users.user_email = :user_email";

$stmt = $conn->prepare($query1);
        $stmt->bindParam('centre_id', $centre_id, PDO::PARAM_INT);
        $stmt->bindParam('rescue_role', $role, PDO::PARAM_INT);
        $stmt->bindParam('user_email', $email, PDO::PARAM_STR);
        $stmt->execute();

        //need an extra section here to create the relationships in the other table 


		//echo "<meta http-equiv='refresh' content='0'>";
		
    } catch (PDOException $e) {
        echo "Database Error: The user could not be added.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The user could not be added.<br>" . $e->getMessage();
        exit();
    }

}


// On success.
if ( ! is_wp_error( $user_id ) ) { ?>

<div class="alert alert-success" role="alert">
  New user (<?php echo $username; ?>) Added
</div>

<?php ;
}
}


?>