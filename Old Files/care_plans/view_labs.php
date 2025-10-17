<?php ?>
<!-- VIEW ALL THE PATIENTS LABE RESULTS --> 
<div class="table-responsive">
                    <table class="table table-bordered table-sm table-hover" id="meds" width="100%" cellspacing="0">
                        <thead class="thead-dark">
                            <tr>
								<th class="align-middle" width="150">Date</th>
								<th class="align-middle" width="150">Sample Type</th>
                                <th class="align-middle">Test</th>
								<th class="align-middle">Result</th>
                                <th class="align-middle">Reported by</th>
                            </tr>
                        </thead>
                        <tbody>

<?php
                                    //gets the medications from the table to display 
                                    $stmt = $conn->prepare("SELECT * FROM rescue_labs
                                            LEFT JOIN rescue_labs_tests
                                            ON rescue_labs_tests.l_test_id = rescue_labs.lab_test
                                            LEFT JOIN rescue_sample_types
                                            ON rescue_sample_types.s_type_id = rescue_labs.sample_type
                                     WHERE patient_id = :patient_id ORDER by lab_date DESC");
                                    $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

                                    // initialise an array for the results
                                    $lab_results = array();
                                    $stmt->execute();
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                                        $labs_date = $row["lab_date"];
                                        $lab_sample_type = $row["sample_type"];
                                        $lab_result = $row["lab_result"];
                                        $lab_reported_by = $row["reported_by"];
                                        $lab_test = $row["lab_test"];
                                        $lab_category = $row["lab_category"];
										$lab_format_date = new DateTime($labs_date);
   										$lab_format_date = $lab_format_date->format('d-m-Y');
										$lab_format_time = new DateTime($labs_date);
							            $lab_format_time = $lab_format_time->format('H:i');?>

							 <tr>
                                <td><?php echo $lab_format_date; ?> <b><?php echo $lab_format_time; ?></b></td>
								<td class="align-middle"><?php echo $lab_sample_type; ?></td>
                                <td class="align-middle"><?php echo $lab_test; ?> (<?php echo $lab_category; ?>)</td>
								<td class="align-middle"><?php echo $lab_result; ?></td>
								<td class="align-middle"><?php echo $lab_reported_by; ?> </td> </tr> <?php } ?> </tbody> </table>
                                               
                            <!-- Add new test Button -->
                            <BR><button type="button" class="btn btn-success" data-toggle="modal" data-target="#labsModal"> Add A Result
                            </button><br>