<?php
/*--------------------------------------------------- FORM PROCESSING add_incident-------------------------------------------------------------------*/
//Check the incident was submitted
if (isset($_POST['add_incident'])) {

    $inc_post_date = $_POST["inc_date"];
    $inc_post_line1 = $_POST["inc_line1"];
	$inc_post_line2 = $_POST["inc_line2"]; 
	$inc_post_city = $_POST["inc_city"];
	$inc_post_postcode = $_POST["inc_postcode"];
	$inc_post_ref = $_POST["inc_reference"];
    $inc_post_cas_tot = $_POST["inc_cas_tot"];
    $inc_post_cas_doa = $_POST ["inc_cas_doa"];
	$inc_post_mass = $_POST["inc_mass"];
    $inc_post_centre = $_POST["inc_centre"];
	$inc_post_user = $_POST["inc_user"];

    try {
        $statement = $conn->prepare('INSERT INTO rescue_incidents
            (incident_date,
			incident_location_line_1,
			incident_location_line_2,
			incident_location_city,
			incident_location_postcode,
			incident_centre_ref,
			incident_total_casualties,
			incident_doa,
			incident_mass_cas,
			centre_id,
			user_id
			)
            
            VALUES (:incident_date,
			:incident_location_line_1,
			:incident_location_line_2,
			:incident_location_city,
			:incident_location_postcode,
			:incident_centre_ref,
			:incident_total_casualties,
			:incident_doa,
			:incident_mass_cas,
			:centre_id,
			:user_id
			)');

        $statement->execute([
            'incident_date' => $inc_post_date,
			'incident_location_line_1' => $inc_post_line1,
			'incident_location_line_2' => $inc_post_line2,
			'incident_location_city' => $inc_post_city,
			'incident_location_postcode' => $inc_post_postcode,
			'incident_centre_ref' => $inc_post_ref,
			'incident_total_casualties' => $inc_post_cas_tot,
            'incident_doa' => $inc_post_cas_doa,
            'incident_mass_cas' => $inc_post_mass,
			'centre_id' => $inc_post_centre,
			'user_id' => $inc_post_user,		
        ]);

		//echo "<script>window.location = window.location</script>";

    } catch (PDOException $e) {
        echo "Database Error: The incident could not be added.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The incident could not be added.<br>" . $e->getMessage();
        exit();
    }
}
?>

 <!-- Add Incident modal -->
				   
<div class="modal fade" id="add_incidentModal" tabindex="-1" role="dialog" aria-labelledby="add_incidentModal" aria-hidden="true">
<div class="modal-dialog" role="document">
<div class="modal-content">
<div class="modal-header">
<h4 class="font-weight-bold text-primary">Add an incident</h4>
<button type="button" class="close" data-dismiss="modal" aria-label="Close">
<span aria-hidden="true">&times;</span>
</button>
</div>

<div class="modal-body">
	<form action="" method="post">

	<!-- incident date, centre reference -->
	<div class="row lead_form_row"> 
		<div class="col-md-4 my-auto">	
        <p class="angelo_form_label">Incident Date<br>&nbsp;</p>
        <input type="date" placeholder="date of incident" name="inc_date" id="inc_date" value="">
		</div>
        <div class="col-md-4 my-auto">
		<p class="angelo_form_label">Centre Reference<br>&nbsp;</p>
        <input type="text" placeholder="Your centre reference" name="inc_reference" id="inc_reference" value="">		
		</div>
		<div class="col-md-4 my-auto">
		</div>
	</div>

    <!-- Incident location details -->
    <div class="row lead_form_row"> 
	    <div class="col-md-4 my-auto">
		<p class="angelo_form_label">Address Line 1<br>&nbsp;</p>
        <input type="text" placeholder="Address line 1 or name" name="inc_line1" id="inc_line1" value="">		
		</div>
	    <div class="col-md-3 my-auto">
		<p class="angelo_form_label">Address Line 2<br>&nbsp;</p>
        <input type="text" placeholder="Address line 2" name="inc_line2" id="inc_line2" value="">		
		</div>
	    <div class="col-md-3 my-auto">
		<p class="angelo_form_label">City<br>&nbsp;</p>
        <input type="text" placeholder="incident location city" name="inc_city" id="inc_city" value="">		
		</div>
	    <div class="col-md-2 my-auto">
		<p class="angelo_form_label">Location Postcode<br>&nbsp;</p>
        <input type="text" placeholder="Postcode" name="inc_postcode" id="inc_postcode" value="">		
		</div>
    </div>

<!-- incident casualty details -->
    <div class="row lead_form_row"> 
	    <div class="col-md-3 my-auto">
		<p class="angelo_form_label">Total Casualties<br>&nbsp;</p>
        <input type="text" placeholder="total Casualties" name="inc_cas_tot" id="inc_cas_tot" value="">		
		</div>
	    <div class="col-md-3 my-auto">
		<p class="angelo_form_label">Casualties DoA<br>&nbsp;</p>
        <input type="text" placeholder="Dead on Arrival" name="inc_cas_doa" id="inc_cas_doa" value="">		
		</div>
	    <div class="col-md-3 my-auto">
		<p class="angelo_form_label">Is mass casualty?<br>&nbsp;</p>
            <select name="inc_mass" id="inc_mass">
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>	
		</div>
		<div class="col-md-3 my-auto">		
		</div>
    </div>

   
							   
<input type="hidden" name="inc_centre" id="inc_centre" value="<?php echo $centre_id; ?>">
<input type="hidden" name="inc_user" id="inc_user" value="<?php $current_user = wp_get_current_user(); print($current_user->id); ?>">

<input type="submit" id="submit" name="add_incident" value="Add Incident" class="form_submit">
                    
 </form></div></div></div></div>

                    <!--- End Of add incident modal---->
