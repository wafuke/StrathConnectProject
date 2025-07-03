<?php
// Get the raw POST data
$mpesaResponse = file_get_contents('php://input');

// Optionally log it to a file for inspection
file_put_contents('mpesa_callback.json', $mpesaResponse . PHP_EOL, FILE_APPEND);

// Decode JSON if you want to process or display it
$data = json_decode($mpesaResponse, true);

// Example: Log specific details
if (isset($data['Body']['stkCallback'])) {
    $stkCallback = $data['Body']['stkCallback'];
    $log = "CheckoutRequestID: " . ($stkCallback['CheckoutRequestID'] ?? 'N/A') . "\n";
    $log .= "ResultCode: " . ($stkCallback['ResultCode'] ?? 'N/A') . "\n";
    $log .= "ResultDesc: " . ($stkCallback['ResultDesc'] ?? 'N/A') . "\n";
    file_put_contents('mpesa_callback.log', $log . PHP_EOL, FILE_APPEND);
}

// Respond to M-Pesa
echo json_encode([
    "ResultCode" => 0,
    "ResultDesc" => "Accepted"
]);
?>
