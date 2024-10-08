<?php
require_once __DIR__ . '/modules/gateways/vendor/autoload.php';

define("DEFINE_MY_ACCESS", true);
define("DEFINE_DHRU_FILE", true);

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

include 'comm.php';
require 'includes/fun.inc.php';
include 'includes/gateway.fun.php';
include 'includes/invoice.fun.php';

$GATEWAY = loadGatewayModule('bkashgateway');

// Log request details
file_put_contents('logs/bkashgateway_callback.log', "Request Time: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
file_put_contents('logs/bkashgateway_callback.log', "Headers: " . print_r(getallheaders(), true) . "\n", FILE_APPEND);
file_put_contents('logs/bkashgateway_callback.log', "GATEWAY: " . print_r($GATEWAY, true) . "\n", FILE_APPEND);

// Fetch GET parameters
$getParams = $_GET;

file_put_contents('logs/bkashgateway_callback.log', "GET DATA: " . print_r($getParams, true) . "\n", FILE_APPEND);


$currentUrl = isset($_GET['currentUrl']) ? $_GET['currentUrl'] : null;

// print_r($getParams['status']);
// die();

// Validate the status
if (!isset($getParams['status']) || $getParams['status'] !== 'success') {
    error_log("Payment status is not success or status is missing.");
    header("Location: $currentUrl");
    exit();
}

function getBkashNewToken($app_key, $app_secret, $username, $password, $sendbox)
{
    $base_url = $sendbox ? 'https://tokenized.sandbox.bka.sh' : 'https://tokenized.pay.bka.sh';
    $version = '/v1.2.0-beta';
    $client = new Client();

    try {
        $requestParams = [
            'json' => [
                'app_key' => $app_key,
                'app_secret' => $app_secret,
            ],
            'headers' => [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'password' => $password,
                'username' => $username,
            ]
        ];

        $response = $client->request('POST', $base_url . $version . '/tokenized/checkout/token/grant', $requestParams);

        $responseBody = $response->getBody();
        $responseData = json_decode($responseBody, true);

        if (isset($responseData['id_token'])) {
            return $responseData['id_token'];
        }

        error_log('Token generation failed');
        return null;

    } catch (RequestException $e) {
        error_log('Request Exception: ' . $e->getMessage());
        return null;
    }
}

if (isset($_GET['paymentID'], $_GET['status']) && $_GET['status'] == 'success') {

    $sendbox = $GATEWAY['SendBox'];
    $bkashPassword = $sendbox ? 'D7DaC<*E*eG' : '4I-iS%jR?H6';

    $token = getBkashNewToken($GATEWAY['app_key'], $GATEWAY['app_secret'], $GATEWAY['username'], $bkashPassword, $sendbox);

    $paymentID = $_GET['paymentID'];

    // Replace with your actual values
    $auth = $token;
    $base_url = $sendbox ? 'https://tokenized.sandbox.bka.sh' : 'https://tokenized.pay.bka.sh';
    $app_key = $GATEWAY['app_key'];

    $post_token = array('paymentID' => $paymentID);
    $url = $base_url . '/v1.2.0-beta/tokenized/checkout/execute';

    // Initialize cURL
    $ch = curl_init($url);

    // Convert POST data to JSON
    $posttoken = json_encode($post_token);

    // Set cURL options
    $headers = array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $auth,
        'X-APP-Key: ' . $app_key
    );

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $posttoken);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

    // Execute the request
    $resultdata = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        error_log('cURL Error: ' . curl_error($ch));
        curl_close($ch);
        exit();
    }

    // Close cURL session
    curl_close($ch);

    // Decode the JSON response
    $obj = json_decode($resultdata);
    file_put_contents('logs/bkashgateway_callback.log', "API Response: " . print_r($obj, true) . "\n", FILE_APPEND);

    //echo '<pre>'; print_r($obj); 

    if ($obj) {
        // Access the response fields
        $customerMsisdn = $obj->customerMsisdn ?? null;
        $paymentID = $obj->paymentID ?? null;
        $trxID = $obj->trxID ?? null;
        $merchantInvoiceNumber = $obj->merchantInvoiceNumber ?? null;
        $time = $obj->paymentExecuteTime ?? null;
        $transactionStatus = $obj->transactionStatus ?? null;
        $amount = $obj->amount ?? null;
        $statusCode = $obj->statusCode ?? null;

        if ($statusCode == 2062) {
            $statusMessage = $obj->statusMessage ?? null;
            error_log($statusMessage);
            header("Location: $currentUrl");
            exit();
        }

        // Example log or process the response data
        error_log("Payment Details: PaymentID=$paymentID, TrxID=$trxID, MerchantInvoiceNumber=$merchantInvoiceNumber, Amount=$amount, Status=$transactionStatus");

        // Proceed with updating invoice or handling payment
        // For example:
        //updateInvoiceStatus($merchantInvoiceNumber, 'Paid', $amount, $time);
        //echo '<pre>'; print_r($obj->merchantInvoiceNumber); die();

        file_put_contents('logs/bkashgateway_callback.log', "merchantInvoiceNumber: " . print_r($obj->merchantInvoiceNumber, true) . "\n", FILE_APPEND);
       
        addPayment($merchantInvoiceNumber, $trxID, $amount, 0, 'Bkash Payment successfully');

        logTransaction('Bkash Payment successfully', json_encode($obj), 'Successful');

        // Redirect to a success page
        header("Location: $currentUrl");
        exit();
    } else {
        error_log('Failed to decode JSON response');
        header("Location: $currentUrl");
        exit();
    }
} else {
    error_log('Invalid request parameters or payment status is not success');
    header("Location: $currentUrl");
    exit();
}

/**
 * Update Invoice Status in Dhru CMS
 */
/**
 * Update Invoice Status in Dhru CMS
 */
/*function updateInvoiceStatus($invoiceID, $status, $amount, $paymentTime)
{
    // Example database connection
    $db = new mysqli('localhost', 'username', 'password', 'database');

    if ($db->connect_error) {
        file_put_contents('logs/bkashgateway_callback.log', "DB connection: " . print_r("Database connection failed", true) . "\n", FILE_APPEND);
        error_log('Database connection failed: ' . $db->connect_error);
        return;
    }

    // Prepare and execute update statement
    $stmt = $db->prepare("UPDATE invoices SET status=?, total=?, datepaid=? WHERE id=?");

    if ($stmt) {
        // Properly bind the parameters
        $stmt->bind_param('sdsi', $status, $amount, $paymentTime, $invoiceID);
        $stmt->execute();
        
        // Check if the query affected any rows
        if ($stmt->affected_rows > 0) {
            error_log("Invoice updated successfully: InvoiceID=$invoiceID");
            file_put_contents('logs/bkashgateway_callback.log', "invoice tbl update: " . print_r("Invoice updated successfully: InvoiceID=$invoiceID", true) . "\n", FILE_APPEND);
        } else {
            error_log("No rows were updated for InvoiceID=$invoiceID");
            file_put_contents('logs/bkashgateway_callback.log', "invoice tbl failed: " . print_r("No rows were updated for InvoiceID=$invoiceID", true) . "\n", FILE_APPEND);
        }

        $stmt->close();
    } else {
        error_log('Prepare statement failed: ' . $db->error);
        file_put_contents('logs/bkashgateway_callback.log', "DB error: " . print_r("Prepare statement failed", true) . "\n", FILE_APPEND);
    }

    $db->close();
}*/

?>