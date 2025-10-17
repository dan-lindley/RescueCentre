<?php 
error_reporting(E_ALL);
error_reporting(-1);
//var_dump($_POST); die;
?>
<div class="col-md-4">	
</div>	
<div class="col-md-5">	
    <form action="https://rescuecentre.org.uk/wp-content/themes/brikk-child/operations/send_sms.php" method="post" class="smsForm" id="smsForm">	
    <input type="hidden" id="sms_send_to" name="sms_send_to" value="<?php echo $finder_tel; ?>">
        <input type="text" placeholder="Send sms to <?php echo $finder_name;?> - <?php echo $finder_tel ?>" name="sms_message" id="sms_message">
        <input type="hidden" id="finder_name" name="finder_name" value="<?php echo $finder_name;?>">
        <input type="hidden" id="rescue_name" name="rescue_name" value="<?php echo $rescue_name;?>">
</div>

<div class="col-md-1"> 
    <button type="submit" class="delete btn btn-outline-secondary" name="smsForm">Send</button> 
    </form>
</div>
<div class="col-md-2"> 
    <form action="https://rescuecentre.org.uk/wp-content/themes/brikk-child/operations/send_welcome.php" method="post" class="smsWelcome" id="smsWelcome">	
    <input type="hidden" id="sms_send_to" name="sms_send_to" value="<?php echo $finder_tel; ?>">
            <input type="hidden" id="finder_name" name="finder_name" value="<?php echo $finder_name;?>">
        <input type="hidden" id="rescue_name" name="rescue_name" value="<?php echo $rescue_name;?>">
        <input type="hidden" id="sms_passphrase" name="sms_passphrase" value="<?php echo $dbpassphrase;?>">
        <input type="hidden" id="sms_crn" name="sms_crn" value="<?php echo $patient_id;?>">
    <button type="submit" class="delete btn btn-outline-secondary" name="smsWelcome">Send Welcome</button> </form>
</div>