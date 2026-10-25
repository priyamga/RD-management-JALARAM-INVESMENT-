<?php
session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/razorpay_config.php';

header('Content-Type: application/json; charset=utf-8');

function send_error($message, $status = 400) {
    http_response_code($status);
    echo json_encode([
        'success' => false,
        'error' => $message
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Invalid request method.', 405);
}

// Customer must have registered first.
$email = $_SESSION['software_registration_email'] ?? '';

if ($email === '') {
    send_error('Please register before making payment.', 401);
}

// Find the registered customer in the database.
$stmt = $conn->prepare(
    "SELECT id, account_status
     FROM software_users
     WHERE email = ?
     LIMIT 1"
);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();

if (!$customer) {
    send_error('Registered customer was not found. Please register again.', 404);
}

if ($customer['account_status'] !== 'pending') {
    send_error('This account is not awaiting its first payment.', 409);
}

// Do not create a new order while a previous order is still pending.
if (!empty($_SESSION['razorpay_order_id'])) {
    send_error(
        'A payment order already exists. Check its status before creating another.',
        409
    );
}

// Confirm Razorpay keys are configured.
if (
    !defined('RAZORPAY_KEY_ID') ||
    !defined('RAZORPAY_KEY_SECRET') ||
    RAZORPAY_KEY_ID === 'YOUR_TEST_KEY_ID' ||
    RAZORPAY_KEY_SECRET === 'YOUR_TEST_KEY_SECRET'
) {
    send_error('Razorpay keys are not configured.', 500);
}

if (!function_exists('curl_init')) {
    send_error('PHP cURL is not enabled in WAMP.', 500);
}

// Create a ₹500 order (50000 paise).
$payload = json_encode([
    'amount' => 50000,
    'currency' => 'INR',
    'receipt' => 'JRD' . bin2hex(random_bytes(8)),
    'notes' => [
        'software_user_id' => (string)$customer['id'],
        'purpose' => 'Annual software subscription'
    ]
]);

$ch = curl_init('https://api.razorpay.com/v1/orders');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_USERPWD => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($response === false) {
    curl_close($ch);
    send_error('Could not connect to Razorpay. Check internet and WAMP cURL.', 502);
}

curl_close($ch);

$orderData = json_decode($response, true);

if (
    ($status !== 200 && $status !== 201) ||
    empty($orderData['id'])
) {
    send_error(
        $orderData['error']['description']
            ?? 'Razorpay order creation failed. Check your keys and settings.',
        502
    );
}

// Store the order and customer identity on the server session.
$_SESSION['razorpay_order_id'] = $orderData['id'];
$_SESSION['software_user_id'] = (int)$customer['id'];

http_response_code(200);
echo json_encode([
    'success' => true,
    'id' => $orderData['id'],
    'amount' => (int)$orderData['amount'],
    'currency' => $orderData['currency']
]);
exit;
?>