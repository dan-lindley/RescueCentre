
<!-- Edit Patient Details Modal -->
<div class="modal fade" id="editPatient" tabindex="-1" role="dialog" aria-labelledby="editPatient" aria-hidden="true">
    <div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Edit <?php echo $patient_name; ?>'s Details</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        
        <div class="modal-body">
        <form action="https://rescuecentre.org.uk/wp-content/themes/brikk-child/ajax/edit_patient.php" method="post" class="lead_form" id=editpatientForm">
            <div class="row lead_form_row">
                <div class="col-md-6">
                    <p class="angelo_form_label">Name or identifier</p>
                    <input type="text" placeholder="Name or identifier" name="name" id="name" value="<?php echo $patient_name; ?>" required>
                </div>
                <div class="col-md-6">
                    <p class="angelo_form_label">Sex</p>
                    <select name="sex" name="sex" id="sex">
                        <option value="<?php echo $patient_sex; ?>" selected><?php echo $patient_sex; ?></option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Female (lactating)">Female (lactating)</option>
                        <option value="Female (pregnant)">Female (pregnant)</option>
                        <option value="Undetermined">Undetermined</option>
                        </select>
                </div>
            </div>

            <div class="row lead_form_row">
                <div class="col-md-6 my-auto">
                    <p class="angelo_form_label">Ringed</p>
                    <select name="ringed" name="ringed" id="ringed" required>
                        <option value="<?php echo $patient_ringed; ?>" selected><?php echo $patient_ringed; ?></option>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <p class="angelo_form_label">Ring Number</p>
                        <input type="text" placeholder="Ring Number" name="ring_number" id="ring_number" value="<?php echo $patient_ring_number; ?>">
                </div>
            </div>

            <div class="row lead_form_row">
                <div class="col-md-6 my-auto">
                    <p class="angelo_form_label">Is this animal Microchipped?</p>
                    <select name="microchipped" name="microchipped" id="microchipped">
                        <option value="<?php echo $patient_microchipped; ?>" selected><?php echo $patient_microchipped; ?></option>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <p class="angelo_form_label">Microchip Number</p>
                    <input type="text" placeholder="Microchip Number" name="microchip_number" id="microchip_number" value="<?php echo $patient_microchip_number; ?>">
                </div>
            </div>

            <div class="row lead_form_row">
                <div class="col-md-6 my-auto">
                    <p class="angelo_form_label">Animal Order</p>
                    <select id="animal_orders" name="animal_orders">
                        <option value="<?php echo $patient_animal_order; ?>" selected><?php echo $patient_animal_order; ?></option>
                        <option value="Amphibian">Amphibian</option>
                        <option value="Bird">Bird</option>
                        <option value="Fish">Fish</option>
                        <option value="Mammal">Mammal</option>
                        <option value="Reptile">Reptile</option>
                        <option value="Unknown">Unknown</option>
                    </select>
                </div>

                <div class="col-md-6 my-auto">
                    <p class="angelo_form_label">Animal Type</p>
                    <select id="animal_types" name="animal_types">
                        <option value="<?php echo $patient_animal_type; ?>" selected><?php echo $patient_animal_type; ?></option>
                        <option>Please select an animal type</option>
                    </select>
                </div>
            </div>

            <div class="row lead_form_row">
                <div class="col-md-6">
                    <p class="angelo_form_label">Animal Species</p>
                    <select id="animal_species" name="animal_species">
                        <option value="<?php echo $patient_animal_species; ?>" selected><?php echo $patient_animal_species; ?></option>
                        <option>Please select an animal species</option>
                    </select>
                </div>

                <div class="col-md-6 my-auto">
                    <p class="angelo_form_label">Animal Status</p>
                    <select name="status" name="status" id="status">
                        <option value="<?php echo $patient_status; ?>" selected><?php echo $patient_status; ?></option>
                        <option value="Captive">Captive</option>
                        <option value="Released">Released</option>
                        <option value="Deceased">Deceased</option>
                    </select>
                </div>

                <input type="hidden" name="thepatientid" id="thepatientid" value="<?php echo $patient_id; ?>">

            </div>

            <br />
            <input type="submit" name="form3" value="Update Patient Record">
        </form>
        </div>
    </div>
    </div>
</div>

<script>
//Edit Patient AJAX
    $(document).ready(function() {
        $('#editpatientForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                type: 'post',
                url: 'https://rescuecentre.org.uk/wp-content/themes/brikk-child/ajax/edit_patient.php',
                data: $('#editpatientForm').serialize(),
                success: function() {
                    location.reload();
                }
            });
        });
    });
</script>