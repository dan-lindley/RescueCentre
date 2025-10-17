<?php 

?>
<table class="table table-bordered table-sm table-hover" id="addnewpts" width="100%" cellspacing="0">
    <thead class="thead-dark">
    <tr>
        <th class="align-middle">Animal</th>
	    <th class="align-middle">Admission Date</th>
        <th class="align-middle">Postcode</th>
	    <th class="align-middle">Finder Name</th>
        <th class="align-middle">Finder Phone</th>
	    <th class="align-middle">Age</th>
        <th class="align-middle">Dehydrated</th>
        <th class="align-middle">Starved</th>
        <th class="align-middle"></th>
    </tr>
    </thead>
    <tbody>
<!-- AUTOSET status to active, disposition to "held in captivity" -->
    <?php			
        ///Loop from patients who are not admitted 
        $stmt = $conn->prepare("SELECT *
        FROM rescue_patients
        WHERE rescue_patients.centre_id = :centre_id AND rescue_patients.state = 'To admit' 
        ORDER by name ASC");
        $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);

        // initialise an array for the results
        $notadmitted = array();
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pt_id = $row["patient_id"];
        $pt_name= $row["name"];
        $pt_animal_species = $row["animal_species"];                          							
	?>

    <tr> 
    <form action="https://rescuecentre.org.uk/wp-content/themes/brikk-child/ajax/insert_admission.php" method="post" class="lead_form" id="manualForm">
        <td><?php echo $pt_name; ?> (<?php echo $pt_animal_species; ?>)<input type="hidden" name="the_patient" value="<?php echo $pt_id; ?>"></td>
        <td width="50"><input type="datetime-local" name="admission_date" class="form-control" id="admission_date" placeholder="date" required></td>
        <td><input type="text" placeholder="Collection Location (postcode)" name="location" id="location" required></td>
        <td><input type="text" placeholder="Finder Name" name="finder_name" id="finder_name"></td>
        <td><input type="text" placeholder="Finder Telephone number" name="finder_tel" id="finder_tel"></td>
        <td><select name="age_on_admission" id="age_on_admission">
			<optgroup label="Mammals">
                <option value="Newborn" selected>Newborn</option>
				<option value="Dependent Juvenile">Dependent Juvenile</option>
				<option value="Independent Juvenile">Independent Juvenile</option>
				<option value="Adult">Adult</option>
				<optgroup label="Birds">
                <option value="Hatchling">Hatchling</option>
                <option value="Fledgling">Fledgling</option>
                <option value="Adult">Adult</option>
            </select></td>
        <td><select id="dehydrated" name="dehydrated">
                <option value="Yes">Yes</option>
                <option value="No" selected>No</option>
            </select></td>
        <td><select id="starved" name="starved">
                <option value="Yes">Yes</option>
                <option value="No" selected>No</option>
            </select></tD>

        <td class="align-middle"><button type="submit" class= "btn btn-success" name="form1"><i class="fas fa-save"></i></button>
            <input type="hidden" name="thestaffid" value="<?php echo $current_user_id; ?>">
            <input type="hidden" name="status" value="Active">
            <input type="hidden" name="disposition" value="Held in captivity">
            <input type="hidden" name="centre_id" value="<?php echo $centre_id; ?>"></form></td>

        
        <?php }?>   
    </tr>

</tbody>

</table>
