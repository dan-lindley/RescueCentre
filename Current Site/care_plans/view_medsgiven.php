<?php ?> 
<div class="table-responsive">
                    <table class="table table-bordered table-sm table-hover" id="meds" width="100%" cellspacing="0">
                        <thead class="thead-dark">
                            <tr>
								<th class="align-middle" width="150">Given on</th>
								<th class="align-middle" width="150">At</th>
                                <th class="align-middle">Medication</th>
								<th class="align-middle">Dose</th>
                                <th class="align-middle">Given by</th>
                            </tr>
                        </thead>
                        <tbody>

<?php
                                    //gets the medications from the table to display 
                                    $stmt = $conn->prepare("SELECT * FROM rescue_medications_given WHERE patient_id = :patient_id ORDER by date DESC LIMIT 10");
                                    $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

                                    // initialise an array for the results
                                    $applicants = array();
                                    $stmt->execute();
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                                        $date = $row["date"];
                                        $medication_given = $row["medication_given"];
                                        $dose = $row["dose"];
                                        $dose_type = $row["dose_type"];
                                        $given_by = $row["given_by"];
										$med_format_date = new DateTime($date);
   										$med_format_date = $med_format_date->format('d-m-Y');
										$med_format_time = new DateTime($date);
							            $med_format_time = $med_format_time->format('H:i');?>

							 <tr>
                                <td><?php echo $med_format_date; ?></td>
								<td><b><?php echo $med_format_time; ?></b></td>
                                <td class="align-middle"><?php echo $medication_given; ?></td>
								<td class="align-middle"><?php echo $dose; ?><i><b><?php echo $dose_type; ?></b></i></td>
								<td class="align-middle"><?php echo $given_by; ?> </td> </tr> <?php } ?> </tbody> </table> 
                                            
                            <!-- Add Medication Button -->
                            <BR><button type="button" class="btn btn-success" data-toggle="modal" data-target="#medicationModal"> Add A Medication
                            </button><br>
 </div>