
<?php
error_reporting(E_ALL);
error_reporting(-1);
var_dump($_POST); die;

use FireText\Api;
require 'vendor/autoload.php';

$apiKey = '[MYFIRETEXTAPIKEY]';
$client = new Api\Client(new Api\Credentials\ApiKey($apiKey));

if (isset($_POST['smsForm'])) {
$message = $_POST["sms_message"];
$from = 'FROM';
$to = 'VARIABKE';
}
$request = $client->request('SendSms', $message, $from, $to);

$result = $client->response($request);

if($result->isSuccessful()) {
    echo "Sent {$result->getCount()} messages".PHP_EOL;
} else {
    throw $result->getStatus()
        ->getException();
}
?>







