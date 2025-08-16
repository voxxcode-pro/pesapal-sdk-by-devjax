<?php
/**
 * Unified Payment Page
 *
 * @file     examples/pay.php
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
$isLive = true; // Set to 'false' for SANDBOX mode.
$callbackUrl = 'https://' . $_SERVER['HTTP_HOST'] . preg_replace('/\/[^\/]*$/', '/', $_SERVER['REQUEST_URI']) . 'callback.php';

$pesapal = new DevJax\Pesapal\Pesapal($consumerKey, $consumerSecret, $isLive);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $merchantReference = 'PAYMENT_' . time();
        $amount = htmlspecialchars($_POST['amount']);
        $email = htmlspecialchars($_POST['email']);
        $phoneNumber = htmlspecialchars($_POST['phone_number']);
        $paymentMethod = htmlspecialchars($_POST['payment_method']);

        $orderDetails = [
            'id' => $merchantReference,
            'currency' => 'TZS',
            'amount' => (float)$amount,
            'description' => 'Website Payment',
            'callback_url' => $callbackUrl,
            'billing_address' => [
                'email_address' => $email,
                'phone_number' => $phoneNumber,
            ]
        ];
        
        // As you discovered, we no longer need to set 'payment_method'.
        // Pesapal's checkout page handles this selection.

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet" />
    <style>
        .powered-by-container { display: flex; align-items: center; justify-content: center; margin-top: 2rem; padding: 10px; background: linear-gradient(90deg, rgba(255, 255, 255, 0) 0%, rgba(230, 230, 230, 0.5) 50%, rgba(255, 255, 255, 0) 100%); border-radius: 5px; position: relative; }
        .powered-by-container::before, .powered-by-container::after { content: ''; position: absolute; top: 50%; width: 35%; height: 1px; background-color: #eaeaea; transform: translateY(-50%); }
        .powered-by-container::before { left: 5%; }
        .powered-by-container::after { right: 5%; }
        .powered-by-text { margin-right: 10px; font-weight: bold; color: #555; z-index: 1; background-color: #ffffff; padding: 0 5px; font-size: 0.8rem; }
        .pesapal-logo { max-height: 24px; z-index: 1; }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8 space-y-6">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-800 flex items-center justify-center gap-2">
                    <i class="ri-secure-payment-line text-blue-500"></i>
                    Secure Payment
                </h2>
                <p class="text-gray-500 mt-2">Enter your details to proceed to checkout.</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Error:</strong>
                    <span class="block sm:inline"><?= $error ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700">Amount (TZS)</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="text-gray-500 sm:text-sm">TZS</span>
                        </div>
                        <input type="number" name="amount" id="amount" class="block w-full rounded-md border-gray-300 pl-12 pr-4 py-3 focus:ring-blue-500 focus:border-blue-500" value="1000" required>
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                         <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="ri-mail-line text-gray-400"></i>
                        </div>
                        <input type="email" name="email" id="email" class="block w-full rounded-md border-gray-300 pl-10 pr-4 py-3 focus:ring-blue-500 focus:border-blue-500" value="test@devjax.com" required>
                    </div>
                </div>

                <div>
                    <label for="phone_number" class="block text-sm font-medium text-gray-700">Phone Number</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                         <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="ri-phone-line text-gray-400"></i>
                        </div>
                        <input type="tel" name="phone_number" id="phone_number" class="block w-full rounded-md border-gray-300 pl-10 pr-4 py-3 focus:ring-blue-500 focus:border-blue-500" placeholder="e.g., 2557..." required>
                    </div>
                </div>
                
                <button type="submit" class="w-full flex justify-center items-center gap-2 py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-300">
                    <span>Continue to Checkout</span>
                    <i class="ri-arrow-right-line"></i>
                </button>
            </form>

            <div class="powered-by-container">
                <span class="powered-by-text">Powered by</span>
                <img src="https://www.pesapal.com/images/pesapal_logo.png" alt="Pesapal Logo" class="pesapal-logo" onerror="this.style.display='none'">
            </div>
        </div>
    </div>
</body>
</html>
