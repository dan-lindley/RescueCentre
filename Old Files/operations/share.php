<!-- this form handles the share of records to another rescue -->

	 <div class="col-md-4"> 
<form method="post" action="">
                            <select name="transfer_id" class="selectpicker show-menu-arrow" name="transfer_id" id="transfer_id">
                                <option value="" disabled selected>Select a rescue to share with</option>
								<option value="0">Unshare</option>
                                               <?php
                    //Get ALL connections
                    $stmt = $conn->prepare("SELECT * FROM rescue_connections
                   		 LEFT JOIN rescue_centres
                   		 ON rescue_centres.rescue_id = rescue_connections.to_centre
					WHERE (from_centre = :centre_id) 
					AND rescue_connections.approved = 'Yes' 
					ORDER BY 'rescue_name'
					"); 
									
                    $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);	
                    //initialise an array for the results
                    $approvedconnections = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                    $a_rescue_name = $row["rescue_name"];
					$a_rescue_city= $row["city"];
                    $a_approved = $row["approved"];
					$a_transfer_id = $row["to_centre"];
						
                    print '

					<option value="' . $a_transfer_id .'">' . $a_rescue_name . ' - ' . $a_rescue_city . '</option>

                          
                          ';
                    }

                    ?>
        		<?php
                    //Get ALL connections
                    $stmt = $conn->prepare("SELECT * FROM rescue_connections
                   		 LEFT JOIN rescue_centres
                   		 ON rescue_centres.rescue_id = rescue_connections.from_centre
					WHERE (to_centre = :centre_id) 
					AND rescue_connections.approved = 'Yes' 
					ORDER BY 'rescue_name'
					"); 
									
                    $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);	
                    //initialise an array for the results
                    $approvedconnections = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                    $b_rescue_name = $row["rescue_name"];
					$b_rescue_city= $row["city"];
                    $b_approved = $row["approved"];
						$b_transfer_id = $row["from_centre"];

                    print '
                   <option value="' . $b_transfer_id .'">' . $b_rescue_name . ' - ' . $b_rescue_city . '</option>
                          
                          ';
                    }

                    ?>
								
	</select></div>
		 <div class="col-md-2"> 
			 <input type="hidden" id="patient_id" name="patient_id" value="<?php echo  $patient_id ?>"><div class="col-md-2"> <button type="submit" class="btn btn-outline-danger" name="shareform">Share/unshare</button> </div></div>