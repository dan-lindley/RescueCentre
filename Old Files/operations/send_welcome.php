<?php 
use FireText\Api;
//Sends welcome SMS to finder to give instru ctions on how to track the animal they found
require '../vendor/autoload.php';
function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $msg = "The animal you found is being cared for by";
    $msg2 = "To view updates from the rescue visit https://www.rescuecentre.org.uk and input your ";
    $msg3 = "CRN:";
    $msg4 = " and Passphrase:";
    $smsname = test_input($_POST["finder_name"]);
    $smsrescue = test_input($_POST["rescue_name"]);
    $smspassphrase = test_input($_POST["sms_passphrase"]);
    $smscrn = test_input($_POST["sms_crn"]);

    $msg_array = ("Hi $smsname, $msg $smsrescue. $msg2 $msg3 $smscrn $msg4 $smspassphrase");

    $apiKey = 'MYFIRETEXTAPIKEY';
    $client = new Api\Client(new Api\Credentials\ApiKey($apiKey));
   
    $message = $msg_array;
    $from = 'RescueCtr';
    $to = test_input($_POST["sms_send_to"]);
   

}
$request = $client->request('SendSms', $message, $from, $to);

$result = $client->response($request);

if($result->isSuccessful()) {
    
    echo "Sent {$result->getCount()} messages".PHP_EOL;
} else {
    throw $result->getStatus()
        ->getException();
}
echo "<script>
             alert('Your message has been sent to the finder'); 
             window.history.go(-1);
     </script>";
?>