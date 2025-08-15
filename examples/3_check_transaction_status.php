<?php
/**
 * Example: Check Transaction Status
 *
 * @file     examples/3_check_transaction_status.php
 * @author   DevJax
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Pesapal/Pesapal.php';
require_once __DIR__ . '/../Pesapal/PesapalException.php';

// Load credentials from a .env file if it exists, otherwise use server variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad(); // Use safeLoad() to avoid errors in production

$consumerKey    = $_ENV['PESAPAL_CONSUMER_KEY']    ?? null;
$consumerSecret = $_ENV['PESAPAL_CONSUMER_SECRET'] ?? null;

// Immediately stop if credentials are not set.
if (!$consumerKey || !$consumerSecret) {
    die("Error: PESAPAL_CONSUMER_KEY and PESAPAL_CONSUMER_SECRET must be set in your .env file or server environment variables.");
}

$isLive = false;
$pesapal = new DevJax\Pesapal\Pesapal($consumerKey, $consumerSecret, $isLive);

$status = null;
$error = '';

if (isset($_GET['order_tracking_id'])) {
    try {
        $orderTrackingId = htmlspecialchars($_GET['order_tracking_id']);
        $status = $pesapal->getTransactionStatus($orderTrackingId);
    } catch (\DevJax\Pesapal\PesapalException | \Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Check Transaction Status</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <h1>Check Transaction Status</h1>
        <p>Enter the `OrderTrackingId` to check its status.</p>
        <form action="" method="GET">
            <div class="form-group">
                <label for="order_tracking_id">Order Tracking ID</label>
                <input type="text" id="order_tracking_id" name="order_tracking_id" value="<?= htmlspecialchars($_GET['order_tracking_id'] ?? '') ?>" required>
            </div>
            <button type="submit">Check Status</button>
        </form>
        <?php if ($error): ?><div class="message error"><?= $error ?></div><?php endif; ?>
        <?php if ($status): ?>
            <div class="status-result">
                <h3>Transaction Details</h3>
                <pre><?php echo json_encode($status, JSON_PRETTY_PRINT); ?></pre>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
