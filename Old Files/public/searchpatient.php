<?php
//Check if the notes form was submitted
if (isset($_POST['findpatient'])) {

	$patient_id = $_POST["crn"];
    $passphrase = $_POST["passphrase"];
}

//header("location: https://rescuecentre.org.uk/getupdate/?patient_id=$patient_id&passphrase=$passphrase");
// using a text box form, user inputs crn of patient and passphrase which posts to URL
// path to the update https://rescuecentre.org.uk/getupdate/?patient_id=572&passphrase=blue

// come back to this to insert as a shortcode in wordpress
?>
<link href="https://rescuecentre.org.uk/wp-content/themes/brikk-child/css/sb-admin-2.min.css" rel="stylesheet">
<!--Login form to check user submitted CRN and passphrase before going to page -->

<!--form action="" method="POST"-->
    <div class="row">
        <div class="col-md-3">
            <label for="crn"><strong>Patients CRN</strong>:</label>
        </div>
        <div class="col-md-3">
            <label for="crn"><strong>Passphrase:</strong></label>
        </div>
        <div class="col-md-2">            
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            <input id="crn" class="textboxclass" required type="text" placeholder="Unique Number - CRN" />
        </div>
          <div class="col-md-3">
            <input id="passphrase" type="text" required placeholder="Passphrase" />
        </div>
            <div class="col-md-2">
           <!-- <button class="rz-button btn-success" name="findpatient">Submit</button>
            <button onclick="window.location.href='http://www.rescuecentre.org.uk/getupdate?patient_id='+document.getElementById('crn').value + '&amp;passphrase=' + document.getElementById('passphrase').value" class="rz-button btn-success" style="--x: 2.5px; --y: 7.390625px;">Find Patient</button>-->
        </div>
    </div>
</form>