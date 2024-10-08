<?php

defined("DEFINE_MY_ACCESS") or die('<h1 style="color: #C00; text-align: center;"><strong>Restricted Access</strong></h1>');

function bkashgateway_config() {
    return array(
        'name' => array('Type' => 'System', 'Value' => 'bKash Payment'),
        'app_key' => array('Name' => 'App Key', 'Type' => 'text', 'Size' => '40', 'Description' => 'Your bKash App Key'),
        'app_secret' => array('Name' => 'App Secret', 'Type' => 'text', 'Size' => '40', 'Description' => 'Your bKash App Secret'),
        'username' => array('Name' => 'bKash Username', 'Type' => 'text', 'Size' => '40', 'Description' => 'Your bKash Username'),
        'password' => array('Name' => 'bKash Password', 'Type' => 'password', 'Description' => 'Your bKash Password'),
        'SendBox' => array('Name' => 'Sandbox Mode', 'Type' => 'yesno', 'Description' => 'Enable or Disable Sandbox Mode for Testing'),
        'info' => array('Name' => 'Other Information', 'Type' => 'textarea', 'Cols' => '5', 'Rows' => '10')
    );
}


function bkashgateway_link($params) {
    $invoiceid = $params['invoiceid'];
    $amount = number_format($params['amount'], 2, '.', ''); // Format amount to 2 decimal places
    $sendbox = $params['SendBox'];

    // Define the bKash password (use different passwords for sandbox and production)
    $bkashPassword = $sendbox ? 'D7DaC<*E*eG' : '4I-iS%jR?H6';

    // Callback URL (bKash will notify this URL after payment completion)
    $callbackURL = $params['systemurl'] . 'bkashgatewaycallback.php';
    
    // Get the full URL of the current page
    // Ensure the function is defined before using it
    function getCurrentUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $requestUri = $_SERVER['REQUEST_URI'];
        return $protocol . '://' . $host . $requestUri;
    }
    
    // Use the function
    $currentUrl = getCurrentUrl();

    file_put_contents('logs/bkashgateway_callback.log', "Current URL: $currentUrl\n", FILE_APPEND);


    // Create the HTML form for bKash payment
    $htmlOutput = '<form action="' . $params['systemurl'] . 'bkashgateway_process.php" method="POST">';
    $htmlOutput .= '<input type="hidden" name="invoiceid" value="' . $invoiceid . '">';
    $htmlOutput .= '<input type="hidden" name="amount" value="' . $amount . '">';
    $htmlOutput .= '<input type="hidden" name="currency" value="' . $params['currency'] . '">';
    $htmlOutput .= '<input type="hidden" name="app_key" value="' . $params['app_key'] . '">';
    $htmlOutput .= '<input type="hidden" name="app_secret" value="' . $params['app_secret'] . '">';
    $htmlOutput .= '<input type="hidden" name="username" value="' . $params['username'] . '">';
    $htmlOutput .= '<input type="hidden" name="password" value="' . $bkashPassword . '">';
    $htmlOutput .= '<input type="hidden" name="sendbox" value="' . $sendbox . '">';
    $htmlOutput .= '<input type="hidden" name="callbackURL" value="' . $callbackURL . '">';
    $htmlOutput .= '<input type="hidden" name="currentUrl" value="' . $currentUrl . '">';
    $htmlOutput .= '<input type="submit" value="Pay with bKash" class="btn btn-success">';
    $htmlOutput .= '</form>';

    return $htmlOutput;
}
?>
