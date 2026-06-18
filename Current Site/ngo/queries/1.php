<?php

// MASTER QUERY FOR THIS ORG 
// SELECT * FROM rescue_admissions 
// LEFT JOIN rescue_patients ON rescue_admissions.patient_id = rescue_patients.patient_id 
// WHERE presenting_complaint = 'attacked - cat' AND animal_type = 'bat'

?>


<!-- Display Query results in user friendly format -->
<div class="card shadow mb-4" id="databasetable">
<div class="card-header py-3">
<h6 class="m-0 font-weight-bold text-primary">Agreed Query Table</h6>
<p> All Bats from database with presenting complaint as Attacked - Cat </p>
</div>
          
<div class="card-body">
<div class="table-responsive">
<table class="display compact" id="query1table" width="100%" cellspacing="0">
  <thead class="thead-dark">
    <tr>       
    <th class="align-middle" width="200">Admission date</th>           
	<th class="align-middle">Species Name</th>
	<th class="align-middle">Sex</th>
    <th class="align-middle">Age</th>
    <th class="align-middle">History</th>
    <th class="align-middle">Disposition</th>
    </tr>
  </thead>
  <tbody>
     <?php			
      //DATA QUERY
      $stmt = $conn->prepare("SELECT * 
      FROM rescue_admissions 
      LEFT JOIN rescue_patients ON rescue_admissions.patient_id = rescue_patients.patient_id 
      WHERE presenting_complaint = 'attacked - cat' AND animal_type = 'bat'
      ORDER by admission_date ASC");
      // initialise an array for the results
      $qry1 = array();
      $stmt->execute();
      while ($row = $stmt->fetch()) {
      $q1_adm_date = $row["admission_date"];
      $q1_species = $row["animal_species"];
      $q1_sex = $row["sex"];
      $q1_age = $row["age_on_admission"];
      $q1_disp = $row["disposition"];
      $q1_hpc = $row["hpc"];

	?>
      <tr>
        <td class="align-middle" width="200"><?php echo $q1_adm_date ?></td>
		<td class="align-middle"><?php echo $q1_species; ?></td>
		<td class="align-middle"><?php echo $q1_sex; ?></td> 
		<td class="align-middle"><?php echo $q1_age; ?></td> 
        <td class="align-middle"><?php echo $q1_hpc; ?></td> 
		<td class="align-middle"><?php echo $q1_disp; ?></td> 

					<?php } ?> 
				</td>
      </tbody>
  </table>
</div>

 

<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
<link href="DataTables/datatables.min.css" rel="stylesheet">
<link href="https://rescuecentre.org.uk/wp-content/themes/brikk-child/vendor/datatables/datatables.css" rel="stylesheet">
<script src="DataTables/datatables.min.js"></script>
<script>
new DataTable('#query1table', {
   layout: {
        bottomEnd: {
            paging: {
                firstLast: false
            }
        }
    }
	 });
</script>
