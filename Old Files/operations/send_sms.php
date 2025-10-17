<?php 
use FireText\Api;
require '../vendor/autoload.php';
function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $msg = test_input($_POST["sms_message"]);
    $smsname = test_input($_POST["finder_name"]);
    $smsrescue = test_input($_POST["rescue_name"]);

    $msg_array = ("Hi $smsname, $msg, from $smsrescue");

    $apiKey = 'MYFIRETEXTAPIKEY';
    $client = new Api\Client(new Api\Credentials\ApiKey($apiKey));
   
    $message = $msg_array;
    $from = 'FROM';
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