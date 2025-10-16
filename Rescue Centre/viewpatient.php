<?php
include 'dashmain.php';
include 'models/patient_data.php';

?>
<?=template_admin_header('CRN: ' . $patient_id .  ' - ' . $patient_name . ' - View individual Patient', 'patients', 'viewpatient')?>

<div class="content-title">
    <div class="title">
        <div class="icon">
            <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M144 0a80 80 0 1 1 0 160A80 80 0 1 1 144 0zM512 0a80 80 0 1 1 0 160A80 80 0 1 1 512 0zM0 298.7C0 239.8 47.8 192 106.7 192h42.7c15.9 0 31 3.5 44.6 9.7c-1.3 7.2-1.9 14.7-1.9 22.3c0 38.2 16.8 72.5 43.3 96c-.2 0-.4 0-.7 0H21.3C9.6 320 0 310.4 0 298.7zM405.3 320c-.2 0-.4 0-.7 0c26.6-23.5 43.3-57.8 43.3-96c0-7.6-.7-15-1.9-22.3c13.6-6.3 28.7-9.7 44.6-9.7h42.7C592.2 192 640 239.8 640 298.7c0 11.8-9.6 21.3-21.3 21.3H405.3zM224 224a96 96 0 1 1 192 0 96 96 0 1 1 -192 0zM128 485.3C128 411.7 187.7 352 261.3 352H378.7C452.3 352 512 411.7 512 485.3c0 14.7-11.9 26.7-26.7 26.7H154.7c-14.7 0-26.7-11.9-26.7-26.7z"/></svg>
        </div>
        <div class="txt">
            <h2 class="pagehead">View Patient</h2><h6>CRN: <?php echo $patient_id; ?> - <?php echo $patient_name; ?></h6>           
        </div>   
    </div>
        <div class="btns">
            <button id="wraBtn" type="button" class="btn orange" data-placement="top" title="Print a care plan">Edit Details</button>  
            <button id="wraBtn" type="button" class="btn green" data-placement="top" title="Print a care plan">Edit Admission</button>
            <button id="wraBtn" type="button" class="btn blue" data-placement="top" title="Print a care plan">Print Care Plan</button>
        </div>
</div>


<div class="tabs">
    <a href="#">Triage</a>
    <a href="#" class="active">Care Notes</a>
    <a href="#">Treatments</a>
    <a href="#">Weights &<BR>Measurements</a>
    <a href="#">Prescriptions &<BR>Medication</a>
    <a href="#">Observations</a>
    <a href="#">Lab Results</a>
    <a href="#">Partner Logs</a>
</div>
<div class="tab-content"><div class="content-block"><?php include 'views/triages.php' ;?></div></div>
<div class="tab-content active"><?php include 'views/carenotes.php' ;?></div>
<div class="tab-content"><div class="content-block"><?php include 'views/treatments.php' ;?></div></div>
<div class="tab-content"><div class="content-block"><?php include 'views/weightsmeasures.php'; ?></div></div>
<div class="tab-content"><div class="content-block">Prescribing</div></div>
<div class="tab-content"><div class="content-block">Obs</div></div>
<div class="tab-content"><?php include 'views/labs.php' ;?></div>
<div class="tab-content"><?php include 'views/partnerlogs.php' ;?></div>
</div>








<?=template_admin_footer()?>