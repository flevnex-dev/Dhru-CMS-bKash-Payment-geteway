<?php

require_once __DIR__ . '/modules/gateways/vendor/autoload.php'; // Assuming you have Guzzle installed
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

function getBkashToken($app_key, $app_secret, $username, $password, $sendbox) {
    $base_url = $sendbox ? 'https://tokenized.sandbox.bka.sh' : 'https://tokenized.pay.bka.sh';
    $version = '/v1.2.0-beta';
    $client = new Client();

    try {
        $response = $client->request('POST', $base_url . $version . '/tokenized/checkout/token/grant', [
            'json' => [
                'app_key' => $app_key,
                'app_secret' => $app_secret,
            ],
            'headers' => [
                'username' => $username,
                'password' => $password,
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['id_token'] ?? null;
    } catch (RequestException $e) {
        error_log('Token generation error: ' . $e->getMessage());
        return null;
    }
}

function createBkashPayment($token, $params) {
    $client = new Client();
    $sendbox = $params['sendbox'];
    $base_url = $sendbox ? 'https://tokenized.sandbox.bka.sh' : 'https://tokenized.pay.bka.sh';
    $version = '/v1.2.0-beta';

    try {
          $callbackURL = ($params['callbackURL'] . '?currentUrl=' . ($params['currentUrl']));

        // print_r($callbackURL); die();
        $response = $client->request('POST', $base_url . $version . '/tokenized/checkout/create', [
            'json' => [
                'mode' => '0011',
                'amount' => $params['amount'],
                'currency' => $params['currency'],
                'intent' => 'sale',
                'payerReference' => 'shop' . rand(),
                'merchantInvoiceNumber' => $params['invoiceid'],
                'callbackURL' => $callbackURL
            ],
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'X-APP-Key' => $params['app_key'],
                'accept' => 'application/json',
                'content-type' => 'application/json'
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        
        file_put_contents('logs/bkashgateway_callback.log', "Bkash create api response data: " . print_r($data, true) . "\n", FILE_APPEND);

        if (isset($data['bkashURL'])) {
            header('Location: ' . $data['bkashURL']);
            exit();
        } else {
            error_log('Payment creation failed: ' . print_r($data, true));
            echo '<p>Error: Payment creation failed.</p>';
        }

    } catch (RequestException $e) {
        error_log('Payment creation error: ' . $e->getMessage());
        echo '<p>Error: Payment request failed.</p>';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve POST data
    $params = [
        'invoiceid' => $_POST['invoiceid'],
        'amount' => $_POST['amount'],
        'currency' => $_POST['currency'],
        'app_key' => $_POST['app_key'],
        'app_secret' => $_POST['app_secret'],
        'username' => $_POST['username'],
        'password' => $_POST['password'],
        'sendbox' => $_POST['sendbox'],
        'callbackURL' => $_POST['callbackURL'],
        'currentUrl' => $_POST['currentUrl']
    ];

    // Get bKash token
    $token = getBkashToken($params['app_key'], $params['app_secret'], $params['username'], $params['password'], $params['sendbox'], $params['currentUrl']);

    if ($token) {
        createBkashPayment($token, $params);
    } else {
        echo '<p>Error: Failed to generate bKash token.</p>';
    }
}
?>
