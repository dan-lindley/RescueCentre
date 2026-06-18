<!-- DISPOSITION MODAL -->
				   
<div class="modal fade" id="dispositionModal" tabindex="-1" role="dialog" aria-labelledby="dispositionModal" aria-hidden="true">						
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
      <h4 class="font-weight-bold text-primary">Discharge Patient</h4> 
 	  <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
      </div>
           
	<div class="modal-body">
	  <b>Discharging Patient - <span class="admissionnameDisplay"><?php echo $patient_name ?></span></b> (CRN: <span class="admissionIDDisplay"><?php echo $patient_id ?></span>)
        <form action="https://rescuecentre.org.uk/wp-content/themes/brikk-child/care_plans/update_disposition.php" method="post" class="lead_form" id="dispositionform">

        <p class="angelo_form_label">Current Disposition

        <select name="disposition" id="disposition" required  style="width: 100%">
        <option value="" disabled selected>Select patient disposition</option>
        <?php
        //Find dispositions
        $stmt = $conn->prepare("SELECT * 
                                FROM rescue_dispositions
                                ORDER BY disposition ASC");
        // initialise an array for the results
        $lkdisp = array();
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $lkdisid = $row["disposition_id"];
        $lkdis = $row["disposition"];
                    
        print '<option value="' . $lkdis. '">' . $lkdis. ' </option>';
                                            } ?>
        </select>


				<p class="angelo_form_label">Disposition date and Time</p>
				<input type="datetime-local" name="disposition_date" id="disposition_date" placeholder="date" required>

        <p class="angelo_form_label">Euthanasia Method
        <select id="euthanasia_method" name="euthanasia_method">
        <option value="Not Applicable" selected>Not applicable</option>
          <option value="Pharmacological - Vet">Pharmacological - Vet</option>
          <option value="Pharmacological - Centre">Pharmacological - Centre</option>
					<option value="Manual">Manual</option>
          <option value="Captive Bolt">Captive Bolt</option>
          <option value="Shot">Shot</option>
          <option value="Other">Other</option>
        </select>

        <p><label for="disposition_comment">Add comment:</label></p>
        <textarea id="disposition_comment" name="disposition_comment" rows="3" cols="50"></textarea>
        <input type="hidden" id="disposition_user" name="disposition_user" value="<?php $current_user = wp_get_current_user();
                                                                                                    print($current_user->id); ?>">
				<input type="hidden" id="patient_id" name="patient_id" value="<?php echo $patient_id; ?>">
				<input type="hidden" id="theadmissionid" name="theadmissionid" value="<?php echo $admission_id; ?>">
        <input type="hidden" id="centre_id" name="centre_id" value="<?php echo $centre_id; ?>">
        <input type="submit" id="submit" name="formdisp" value="Discharge Patient">
                    
        </form>
      </div>
    </div>
  </div>
</div>

<!--- END OF DISPOSITION MODAL  ---->

<script>
    //AJAX Scripts

    //Edit Patient AJAX
    $(document).ready(function() {
        $('#dispositionform').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                type: 'post',
                url: 'https://rescuecentre.org.uk/wp-content/themes/brikk-child/care_plans/update_disposition.php',
                data: $('#dispositionform').serialize(),
                success: function() {
                    location.reload();
                }
            });
        });
    });

</script>
