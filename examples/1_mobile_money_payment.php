<?php
/**
 * Example: Mobile Money (STK Push) Payment
 *
 * @file     examples/1_mobile_money_payment.php
 * @author   DevJax
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Pesapal/Pesapal.php';
require_once __DIR__ . '/../Pesapal/PesapalException.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$consumerKey    = $_ENV['PESAPAL_CONSUMER_KEY']    ?? null;
$consumerSecret = $_ENV['PESAPAL_CONSUMER_SECRET'] ?? null;

if (!$consumerKey || !$consumerSecret) {
    die("Error: PESAPAL_CONSUMER_KEY and PESAPAL_CONSUMER_SECRET must be set.");
}

// --- Configuration ---
// Set to 'true' for LIVE mode, 'false' for SANDBOX mode.
$isLive = true; 
$callbackUrl = 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/callback.php';

$pesapal = new DevJax\Pesapal\Pesapal($consumerKey, $consumerSecret, $isLive);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $notificationId = $_ENV['PESAPAL_IPN_ID']; 
        if(empty($notificationId)){
            throw new \Exception("PESAPAL_IPN_ID is not set. Please register your IPN URL first.");
        }

        $merchantReference = 'TZS_TEST_' . time();
        $amount = htmlspecialchars($_POST['amount']);
        $phoneNumber = htmlspecialchars($_POST['phone_number']);
        $email = htmlspecialchars($_POST['email']);

        $orderDetails = [
            'id' => $merchantReference,
            'currency' => 'TZS', // Set to Tanzanian Shilling
            'amount' => (float)$amount,
            'description' => 'Test Mobile Money Payment',
            'callback_url' => $callbackUrl,
            'notification_id' => $notificationId,
            'billing_address' => [
                'email_address' => $email,
                'phone_number' => $phoneNumber,
            ],
            'payment_method' => 'MobileMoney'
        ];

        $response = $pesapal->submitOrder($orderDetails);

        if (isset($response['redirect_url'])) {
            $message = "STK Push sent successfully! Please enter your PIN on your phone to complete the payment. Order Tracking ID: " . $response['order_tracking_id'];
        } else {
            throw new \Exception($response['error']['message'] ?? 'Failed to initiate payment.');
        }

    } catch (\DevJax\Pesapal\PesapalException | \Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Mobile Money (STK Push)</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <h1>Mobile Money (STK Push)</h1>
        <p>This form will initiate an STK Push to the provided phone number.</p>
        <?php if ($message): ?><div class="message success"><?= $message ?></div><?php endif; ?>
        <?php if ($error): ?><div class="message error"><?= $error ?></div><?php endif; ?>
        <form action="" method="POST">
            <div class="form-group">
                <label for="amount">Amount (TZS)</label>
                <input type="number" id="amount" name="amount" value="1000" required>
            </div>
            <div class="form-group">
                <label for="phone_number">Phone Number (e.g., 255...)</label>
                <input type="tel" id="phone_number" name="phone_number" required>
            </div>
             <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="test@devjax.com" required>
            </div>
            <button type="submit">Pay Now</button>
        </form>
    </div>
</body>
</html>
