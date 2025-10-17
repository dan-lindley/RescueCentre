<!-- bootstrap ref/stylesheet stuff-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet"/>


<!-- Display people from the database -->
        <div class="card shadow mb-4" id="databasetable">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Patients awaiting admission</h6>
                <p class="card_subheading">These are patients which are currently waiting admission or triage.</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead class="thead-dark">
                            <tr>
                                <th>Patient Name</th>
                                <th>Sex</th>
                                <th>Animal Type</th>
                                <th>Animal Species</th>
                                <th>Date Added</th>
								<th></th>
								<th></th>
                                
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th>Patient Name</th>
                                <th>Sex</th>
                                <th>Animal Type</th>
                                <th>Animal Species</th>
                                <th>Date Added</th>
                               	<th></th>
								<th></th>
                            </tr>
                        </tfoot>
                        <tbody>

                            <?php
                            //Find patients in table. 
                            $stmt = $conn->prepare("SELECT * 
								FROM rescue_patients
								LEFT JOIN rescue_admissions on rescue_admissions.patient_id = rescue_patients.patient_id
								WHERE admission_id IS NULL
								AND rescue_patients.status = 'Captive' AND rescue_patients.centre_id = :centre_id ORDER BY date_added DESC");
                            $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);

                            // initialise an array for the results
                            $applicants = array();
                            $stmt->execute();
					       
						
							
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                                $patient_id = $row["patient_id"];
                                $patient_name = $row["name"];
                                $ringed = $row["ringed"];
                                $ring_number = $row["ring_number"];
                                $microchipped = $row["microchipped"];
                                $microchip_number = $row["microchip_number"];
								$animal_type = $row["animal_type"];
                                $animal_order = $row["animal_order"];
                                $animal_species = $row["animal_species"];
                                $sex = $row["sex"];
                                $status = $row["status"];
                                $date_added = $row["date_added"];
                          
                                

                                print '<tr>
                            <td>' . $patient_name . '</a></td>
                            <td>' . $sex . '</td>
                            <td>' . $animal_type . '</td>
                            <td>' . $animal_species . '</td>
                            <td>' . $date_added . '</td>
								
							<td><a href="/admissions" class="btn btn-info" role="button">Admit</a>
							<!-- <button type="button" class="btn btn-success" data-toggle="modal" data-target="#">Admit</button></td> -->                     
                            <td><button type="button" class="btn btn-success" data-toggle="modal" data-target="#">Triage</button></td>  
							</tr>';
								
					
								
                            }

                            ?>


                        </tbody> 
                    </table>
                </div>
            </div>
		</div>



		
<!-- Patient Management THIS includes single line forms!!!!-->
        <div class="card shadow mb-4" id="databasetable">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Captive patients</h6>
                <p class="card_subheading">The patients below are showing on the system as Captive - To keep your records up to date, mark as released or deceased if they are not in your care.</p>
				
            </div>
            <div class="card-body">
			
	
	              <div class="table-responsive">      
			             <table class="table table-bordered table-sm table-hover" id="dataTable" width="100%" cellspacing="0">
                        <thead class="thead-dark">
                            <tr>
                                <th>Patient</th>
                                <th>Species</th>
								<th>Admission Status</th>
                                <th width="90px"></th>
								<th width="90px"></th>
								<TH width="90px"></TH>
                              
                            </tr>
                        </thead>

                        <tbody>

                            <?php
                            
                            $stmt = $conn->prepare("SELECT * 
                            FROM rescue_patients
                        JOIN rescue_admissions on rescue_patients.patient_id = rescue_admissions.patient_id
                            WHERE rescue_patients.status = 'Captive' AND rescue_patients.centre_id = :centre_id 
                            ORDER by `name` DESC");
                            
							$stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);

                            // initialise an array for the results
                            $active_patients = array();
                            $stmt->execute();
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $patient_id = $row["patient_id"];
                                $name = $row["name"];
                                $species = $row["animal_species"];
								$status = $row["disposition"];
                        

                                print '<tr>
                                <td>' . $name . '</td>
                                <td>' . $species . '</td>
								<td>' . $status . '</td>
                                

                                <td><form method="post" action=""><input type="hidden" id="patient_id" name="patient_id" value="'. $patient_id . '"><input type="hidden" id="status" name="status" value="Deceased"><button type="submit" class="delete btn btn-danger btn-sm" name="patientstatusform">Deceased</button> 
                    </form>	</td><td>
							<form method="post" action=""><input type="hidden" id="patient_id" name="patient_id" value="'. $patient_id . '"><input type="hidden" id="status" name="status" value="Released"><button type="submit" class="delete btn btn-info btn-sm" name="patientstatusform">Released</button> 
                    </form></td>	
								
								
								<td><a href="https://rescuecentre.org.uk/view-patient/?patient_id=' . $patient_id . '"> <button type="submit" class="btn btn-success btn-sm">Manage</button></a></td>';
                            }

                            ?>


                        </tbody>
                    </table>
				</div>  <br></div>	</div>		




 <!--- Injury assessment section ---->
    <!-- first we need a form to set up a new triage and create the relationships to this patient -->
		  
		  <?php
                    //Gets the essential data to set up the form
                    $stmt = $conn->prepare("SELECT * FROM rescue_admissions WHERE patient_id=:patient_id");
                    $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

                    // initialise an array for the results
                    $triage_data = array();
                    $stmt->execute();	 	 
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                        $patient_id = $row["patient_id"];
                        $admission_id = $row["admission_id"];
                        $centre_id = $row["centre_id"];

                        $formatted_date = new DateTime($triage_date);
                        $formatted_date = $formatted_date->format('jS \o\f F Y \a\t H:i');
						

                        print '
                            
                           <form action="https://rescuecentre.org.uk/wp-content/themes/brikk-child/ajax/insert_triage.php" method="post" class="triageform" id="triageform">	
			  
			  <div class="row lead_form_row">
				  <div class="col-md-4">
					  <p class="angelo_form_label">Select Care Form</p></div>
				  <div class="col-md-4">
						<select id="care_form_used" name="care_form_used">
                             <option value="bat_care.php">Bat</option>
							 <option value="Bird Care Form">Bird</option>
                             <option value="Hedgehog Care Form">Hedgehog</option>
                             <option value="Mammal Care Form">Mammal</option>    
 					    </select>
					</div>
					<div class="col-md-4 my-auto">
				        <input type="hidden" id="patient_id" name="patient_id" value="' . $patient_id . '">
			            <input type="hidden" id="admission_id" name="admission_id" value="' . $admission_id .'">
			            <input type="hidden" id="centre_id" name="centre_id" value="' .$centre_id . '">
					    <input type="hidden" id="triage_date" name="triage_date" value="' .$triage_date . '">
                       <button type="submit" class="delete btn btn-outline-secondary" name="triageform">Start New Triage</button>     </form>                                 </div>
			  </div>';					
			  ;
                  }
                    ?>
		  
		  <!-- THIS section will show the injury assessment form IF a triage has been started -->
			 <?php
                            $stmt = $conn->prepare("SELECT * 
                            FROM rescue_admissions
											INNER JOIN rescue_patients
											ON rescue_admissions.patient_id = rescue_patients.patient_id
											LEFT JOIN rescue_triages
											ON rescue_triages.patient_id = rescue_admissions.patient_id
											WHERE rescue_patients.patient_id = :patient_id
											
											ORDER by `admission_date` DESC");
                            
							$stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

                            // initialise an array for the results
                            $triage_info = array();
                            $stmt->execute();
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $care_form_used = $row["care_form_used"];   
                        
									
//This displays sms form if phone number stored for patient
									if (!empty($care_form_used)){
								include("care_forms/$care_form_used");
                         }
                            }
                            ?>					 

  <!-- SEARCH SECTION -->

<!--<div class="row lead_form_row"> 
	<div class="card-header">Search Result</div>
	<div class="col-md-6 my-auto" id="searchSection">
	  <div class="form-group">
		<input type="text" name="search" id="search" class="form-control" placeholder="Type your search keyword here" />
	  </div>
	  <div class="table-responsive" id="searchResult"></div>
	</div>
</div> 
<div class="row lead_form_row">
    <div class="col-md-4 my-auto">
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <select class="custom-select" id="inputGroupSelect01">
                    <option selected>Choose...</option>
                    <option value="1">UK postcode</option>
                    <option value="2">EU</option>
                    <option value="3">US</option>
                </select>
            </div>
            <input type="text" class="form-control" aria-label="Text input with dropdown button">
        </div>
    </div>
results here
</div>-->





<!-- END SEARCH -->



   <!-- SEARCH MODAL BTN -->
   <button type="button" class="btn btn-info" data-toggle="modal" data-target="#latsearchModal">Search </button>

   <!-- Table of results -->

   
<div class="row lead_form_row"> 
    <div class="col-md-6 my-auto">

    <table class="table-bordered table-sm table-hover" id="" width="100%" cellspacing="0">
                <thead>
                        <tr>
		                <th>Postcode</th>
                        <th>Country/state</th>
                        <th>Latitude</th>
		                <th>Longitude</th>
                        </tr>
                        </thead>
                        <tbody> 
                        <?php
                            //get orescriotioins
                            $stmt = $conn->prepare("SELECT * FROM
	                    postcodelatlng,
	                    lat_search 
                        WHERE INSTR(postcodelatlng.postcode, lat_search.query) > 0 AND
						lat_search.centre_id = :centre_id ");
                            $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);

                            // initialise an array for the results
                            $postcodesearcharray = array();
                            $stmt->execute();
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                                $postcode = $row["postcode"];
								$country_code = $row["country_code"];
                                $latitude = $row["latitude"];
                                $longitude = $row["longitude"]; ?>
  

                        <tr>
						    <td><?php echo $postcode; ?></td>
                      
                            <td><?php echo $country_code; ?></td>
                            <td><?php echo $latitude; ?></td>
                            <td><?php echo $longitude; ?></td>
                       
                     <?php } ?>
                            </tr>
                           

                            </tbody> 
                            </table>
    </div>
</div> 

<!--end tablke -->

<!-- get to patient record public view -->

 	
<?php echo $passphrase; ?> and <?php echo $patient_id; ?> and 
<input type="text" id="test"><br>
<input type="text" id="test2"><br><br>
<button onclick="window.location.href='http://www.rescuecentre.org.uk/getupdate?patient_id='+document.getElementById('test').value + '&passphrase=' + document.getElementById('test2').value">Submit</button>

<button class="delete btn btn-outline-secondary" onclick="window.location.href='https://www.firetext.co.uk/api/sendsms?apiKey=HTAwW657uONeIpkl1RfwITHbGFmY2g&message='+document.getElementById('sms_message').value + '&from=RescueCtr&to=' + document.getElementById('sms_send_to').value">Submit</button>

   <!-- SEARCH USERS BY EMAIL CARD
		            	 
         <div class="card shadow mb-4" id="databasetable">
			  <div class="card-header py-3">
		 <h6 class="m-0 font-weight-bold text-primary">Search Users</h6>				
									You can search for a new user and assign them to your centre
							<form action="" method="post">
								</div>
  	<div class="card-body">	
                    <div class="row lead_form_row">
                        <div class="col-md-6 my-auto">
                           <input type="text" name="search_email" id="search_email" placeholder="Type the users email here" value="" required>
                        </div>
				
                        <div class="col-md-6 my-auto">
						<input type="hidden" id="centre_id" name="centre_id" value="">
                        <input type="submit" id="submit" name="usersearchform" value="Search by User Email" class="form_submit">
                        </div>
                    </div>
                    </form> 
		
                
<!--- this section diplays search results 			
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
				
<!-- form to update user 
 <form action="" method="post">
                            <?php
                               //Find searchresults for user search

                               /*
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
                          
			 </div>	 </div> 
		
 END OF SEARCH USERS CARD -->