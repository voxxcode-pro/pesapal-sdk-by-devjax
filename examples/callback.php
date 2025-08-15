<?php
/**
 * Example: Callback/IPN Handler
 *
 * @file     examples/callback.php
 * @author   DevJax
 */

$orderTrackingId = $_GET['OrderTrackingId'] ?? null;
$merchantReference = $_GET['OrderMerchantReference'] ?? null;

$logFile = 'ipn_log.txt';
$logMessage = sprintf(
    "[%s] IPN Received: TrackingID=%s, MerchantRef=%s\n",
    date('Y-m-d H:i:s'),
    $orderTrackingId,
    $merchantReference
);

file_put_contents($logFile, $logMessage, FILE_APPEND);

// IMPORTANT: After logging, you should use the `getTransactionStatus` method 
// from the SDK to get the final status and update your database.

echo "Callback received.";
