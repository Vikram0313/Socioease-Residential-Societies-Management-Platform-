<?php
// =============================================================
//  payment_order.php
//  Creates a Razorpay Order server-side before checkout opens.
//  Returns order_id to the frontend via JSON.
// =============================================================
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_conn.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$amount = (float)($_POST['amount'] ?? 0);
$month  = trim($_POST['month']     ?? '');

if (!$amount || !$month) {
    echo json_encode(['success' => false, 'message' => 'Invalid amount or month']);
    exit;
}

$flat = $_SESSION['flatno'];

// Check not already paid
$chk = $conn->prepare("SELECT ID FROM payrecords WHERE Flatno=? AND month_name=? AND Status='Success'");
$chk->bind_param("ss", $flat, $month);
$chk->execute();
if ($chk->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => "$month is already paid."]);
    exit;
}

// Create Razorpay order via REST API
// Amount must be in paise (multiply by 100)
$order_data = [
    'amount'          => (int)($amount * 100),
    'currency'        => 'INR',
    'receipt'         => 'socioease_' . $flat . '_' . time(),
    'payment_capture' => 1
];

$ch = curl_init('https://api.razorpay.com/v1/orders');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($order_data),
    CURLOPT_USERPWD        => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    $err = json_decode($response, true);
    echo json_encode([
        'success' => false,
        'message' => 'Could not create order: ' . ($err['error']['description'] ?? 'Unknown error')
    ]);
    exit;
}

$order = json_decode($response, true);
echo json_encode([
    'success'  => true,
    'order_id' => $order['id'],
    'amount'   => $order['amount'],
    'currency' => $order['currency']
]);
