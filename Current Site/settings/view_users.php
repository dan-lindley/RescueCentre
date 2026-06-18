
<div class="table-responsive">
    <table table class="table table-bordered table-sm table-hover" id="dataTable" width="100%" cellspacing="0">
		<thead class="thead-dark">
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
				<th></th>
                <th></th>
			</tr>
        </thead>
<!-- This section shows current users assigned to the centre -->
<!-- form to update user - remove from centre -->


	<?php
    //Get current users for this centre
        $stmt = $conn->prepare("SELECT * FROM wpxp_users LEFT JOIN rescue_roles
								on wpxp_users.rescue_role = rescue_roles.role_id 
								WHERE centre_id=:centre_id ORDER BY display_name DESC");
					
        $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
					
        // initialise an array for the results
        $centre_users = array();
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        
		$add_user_id = $row["ID"];
        $display_name = $row["display_name"];
		$user_email = $row["user_email"];
        $rescue_role = $row["rescue_role"];
		$role_name = $row["role_name"]; ?>
                           
        <tr>
		    <td width = "150" class="align-middle"><?php echo $display_name; ?>  </td>
            <td width = "400" class="align-middle"><?php echo $user_email; ?></td>
            <form method="post" action="">
            <td class="align-middle">
				    <input type="hidden" id="add_user_id" name="add_user_id" value="<?php echo $add_user_id; ?>">
				    <input type="hidden" id="centre_id" name="centre_id" value="<?php echo $centre_id;?>">
                    <select name="rescue_role" id="rescue_role">
                        <option value="<?php echo $rescue_role;?>" selected><?php echo $role_name; ?></option>
						<option value="2">Volunteer</option>
                        <option value="3">Staff</option>
                        <option value="4">Vet</option>
						<option value="5">Vet Nurse</option>
						<option value="6">Administrator</option>
						<option value="7">Driver</option>	
					</select></td>
	         <!--	<input type="submit" id="submit" name="updateuserform" value="Remove" class="btn btn-danger">-->
			<td width = "100" class="align-middle"><button type="submit" class="btn btn-info" name="updateuserform">Update</button></form>
			</td><form method="post" action="">
            <td width = "100" class="align-middle">
				    <input type="hidden" id="add_user_id" name="add_user_id" value="<?php echo $add_user_id; ?>">
				    <input type="hidden" id="centre_id" name="centre_id" value="0">
				    <input type="hidden" id="rescue_role" name="rescue_role" value="0">
	         <!--	<input type="submit" id="submit" name="updateuserform" value="Remove" class="btn btn-danger">-->
				    <button type="submit" class="btn btn-danger" name="updateuserform">Remove</button> 
                </form>
			</td>

            <?php ; } ?>
        </tr>
    </table>

    <button class="btn btn-info" onClick="window.location.href=window.location.href">Refresh Table</button>

