<?php
/**
 * Example: Card Payment
 *
 * @file     examples/2_card_payment.php
 * @author   DevJax
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Pesapal/Pesapal.php';
require_once __DIR__ . '/../Pesapal/PesapalException.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$consumerKey = $_ENV['PESAPAL_CONSUMER_KEY'];
$consumerSecret = $_ENV['PESAPAL_CONSUMER_SECRET'];

$isLive = false;
$callbackUrl = 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/callback.php';

$pesapal = new DevJax\Pesapal\Pesapal($consumerKey, $consumerSecret, $isLive);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $notificationId = $_ENV['PESAPAL_IPN_ID'];
         if(empty($notificationId)){
            throw new \Exception("PESAPAL_IPN_ID is not set in your .env file. Please register your IPN URL first.");
        }

        $merchantReference = 'CARD' . time();
        $amount = htmlspecialchars($_POST['amount']);
        $email = htmlspecialchars($_POST['email']);

        $orderDetails = [
            'id' => $merchantReference,
            'currency' => 'KES',
            'amount' => (float)$amount,
            'description' => 'Test Card Payment',
            'callback_url' => $callbackUrl,
            'notification_id' => $notificationId,
            'billing_address' => [
                'email_address' => $email,
            ],
        ];

        $response = $pesapal->submitOrder($orderDetails);

        if (isset($response['redirect_url'])) {
            header('Location: ' . $response['redirect_url']);
            exit;
        } else {
            throw new \Exception($response['error']['message'] ?? 'Failed to create payment link.');
        }

    } catch (\DevJax\Pesapal\PesapalException | \Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Card Payment</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <h1>Card Payment</h1>
        <p>This form will redirect you to Pesapal's secure page to complete the payment.</p>
        <?php if ($error): ?><div class="message error"><?= $error ?></div><?php endif; ?>
        <form action="" method="POST">
            <div class="form-group">
                <label for="amount">Amount (KES)</label>
                <input type="number" id="amount" name="amount" value="1" required>
            </div>
             <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="test@devjax.com" required>
            </div>
            <button type="submit">Proceed to Pay</button>
        </form>
    </div>
</body>
</html>
