<?php
include 'dashmain.php';
include 'getcentreinfo.php';
include 'models/all_dataModel.php';
?>
<?=template_admin_header('Dashboard', 'dashboard')?>

<div class="content-title">
    <div class="title">
        <div class="icon">
            <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zm320 96c0-26.9-16.5-49.9-40-59.3V88c0-13.3-10.7-24-24-24s-24 10.7-24 24V292.7c-23.5 9.5-40 32.5-40 59.3c0 35.3 28.7 64 64 64s64-28.7 64-64zM144 176a32 32 0 1 0 0-64 32 32 0 1 0 0 64zm-16 80a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zm288 32a32 32 0 1 0 0-64 32 32 0 1 0 0 64zM400 144a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/></svg>
        </div>
        <div class="txt">
            <h2>Dashboard</h2>
            <p>View statistics and more.</p>
                    
        </div>
    </div>
</div>


<?php include ('views/stats_view.php'); ?> 
	
<div class="row">
    <div class="col-sm-6">
        <div class="card">
            <h5 class="card-header">Monthly Admissions</h5>
            <div class="card-body">
                <?php include ('views/admissions_chart.php'); ?> 
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="card">
            <h5 class="card-header">Presenting complaints</h5>
            <div class="card-body">
                <?php include ('views/complaints_chart.php'); ?> 
            </div>
        </div>
    </div>
</div>



<?=template_admin_footer()?>