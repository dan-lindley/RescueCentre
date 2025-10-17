<?php 
/*--------------- FORM PROCESSING - USER SEARCH-----------------------*/
//QUERY - Only one record per centre at one time. *IMPORTANT* as multiple entries per centre will affect data pull. This either create a new one per centre OR updates -
// Key used for this is centre_id and this is indexed in the table rescue_queries
if (isset($_POST['usersearchform'])) {

    $search_email = $_POST["search_email"];
    	
    try {
        $statement = $conn->prepare('INSERT INTO rescue_search_user
            (centre_id, 
            search_email)
            
            VALUES (:centre_id, 
            :search_email) 
			
			ON DUPLICATE KEY UPDATE
			search_email = :search_email
			');

        $statement->execute([
            'centre_id' => $centre_id,
            'search_email' => $search_email
        ]);
    } catch (PDOException $e) {
        echo "Database Error: i messed up again - arn this search<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "Grrr rdone something wrong.<br>" . $e->getMessage();
        exit();
    }
}

/*------------------------------------------------------------------ END OF FORM PROCESSING -------------------------------------------------------------------*/

if (isset($_POST['updateuserform'])) {

    $add_user_id = $_POST["add_user_id"];
    $centre_id = $_POST["centre_id"];
	$rescue_role = $_POST["rescue_role"];
	

    try {
        $statement = $conn->prepare('INSERT INTO wpxp_users
            (ID, 
            centre_id,
			rescue_role)
            
            VALUES (:ID, 
            :centre_id,
			:rescue_role) 
			
			ON DUPLICATE KEY UPDATE
			centre_id = :centre_id,
			rescue_role = :rescue_role
			');

        $statement->execute([
            'centre_id' => $centre_id,
            'rescue_role' => $rescue_role,
			'ID' => $add_user_id
            
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

?>
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Search</h6>
</div>
<div class="card mb-4" >
<div class="card-body">
		
<form action="" method="post">
<div class="row lead_form_row">   
    <div class="col-md-6 my-auto">  
        <input type="text" name="search_email" id="search_email" placeholder="Type the users email here" value="" required>
    </div>
    <div class="col-md-6 my-auto">  
        <input type="hidden" id="centre_id" name="centre_id" value="<?php echo $centre_id; ?>">
        <input type="submit" id="submit" name="usersearchform" value="Search email" class="form_submit">
        </form>
    </div>
</div>
</div>
</div>


    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Results</h6>    
</div>
<div class="card mb-4" >
<div class="card-body">

<!--- this section diplays search results -->			
                <div class="table-responsive">
                    <table class="table table-bordered angelo_table" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>   
								<th></th>
                            </tr>
                        </thead>
				
<!-- form to update user -->
 <form action="" method="post">
    <?php
    //Find searchresults for user search
    $stmt = $conn->prepare("SELECT ID, user_nicename, user_email, rescue_role
							FROM wpxp_users
							INNER JOIN
							rescue_search_user ON user_email = search_email
							WHERE wpxp_users.rescue_role = 0
							AND rescue_search_user.centre_id=:centre_id");
                               
    $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
                                 // initialise an array for the results
    $queries = array();
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
									
	$add_user_id = $row["ID"];
    $update_user_nicename = $row["user_nicename"];
    $update_user_email = $row["user_email"];

    print '<TR>
            <td>' . $update_user_nicename. '</td>									
			<td>' . $update_user_email . '</td>
			<TD>
            <select name="rescue_role" id="rescue_role">
				<option value="2" selected>Volunteer</option>
                <option value="3">Staff</option>
                <option value="4">Vet</option>
				<option value="5">Vet Nurse</option>
				<option value="6">Administrator</option>
				<option value="7">Driver</option>	
			</select>

				</TD>
						<td><input type="hidden" id="add_user_id" name="add_user_id" value="' . $add_user_id . '">
						<input type="hidden" id="centre_id" name="centre_id" value="' . $centre_id . '">
                        <input type="submit" id="submit" name="updateuserform" value="Assign" class="form_submit">
                        
                    </form></td>
									';
                                }

                               ?>
                    </table>
                    </div>
</div>