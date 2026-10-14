<?php
// =============================================================
//  payment_verify.php
//  Called via AJAX after Razorpay checkout succeeds client-side.
//  Verifies the payment signature server-side, then records it.
// =============================================================
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/notifications_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$razorpay_payment_id = trim($_POST['razorpay_payment_id']  ?? '');
$razorpay_order_id   = trim($_POST['razorpay_order_id']    ?? '');
$razorpay_signature  = trim($_POST['razorpay_signature']   ?? '');
$month               = trim($_POST['month']                ?? '');
$amount              = (float)($_POST['amount']            ?? 0);

if (!$razorpay_payment_id || !$razorpay_order_id || !$razorpay_signature || !$month || !$amount) {
    echo json_encode(['success' => false, 'message' => 'Missing payment data']);
    exit;
}

// --- SERVER-SIDE SIGNATURE VERIFICATION ---
// This is the critical security step — proves payment is genuine
$generated_signature = hash_hmac(
    'sha256',
    $razorpay_order_id . '|' . $razorpay_payment_id,
    RAZORPAY_KEY_SECRET
);

if ($generated_signature !== $razorpay_signature) {
    echo json_encode(['success' => false, 'message' => 'Payment verification failed — invalid signature']);
    exit;
}

// --- SIGNATURE VALID — record the payment ---
$username = $_SESSION['username'];
$flat     = $_SESSION['flatno'];

// Check not already paid for this month
$chk = $conn->prepare("SELECT ID FROM payrecords WHERE Flatno=? AND month_name=? AND Status='Success'");
$chk->bind_param("ss", $flat, $month);
$chk->execute();
if ($chk->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => "Payment for $month already recorded."]);
    exit;
}

$conn->begin_transaction();
try {
    // Insert payment record — store Razorpay Payment ID in utr_number column
    $ins = $conn->prepare(
        "INSERT INTO payrecords (Name, Flatno, month_name, Amount, utr_number, Status)
         VALUES (?, ?, ?, ?, ?, 'Success')"
    );
    $ins->bind_param("sssds", $username, $flat, $month, $amount, $razorpay_payment_id);
    $ins->execute();

    // Mark all unpaid parking and facility bookings as paid
    $flat_int = (int)$flat;
    $upk = $conn->prepare("UPDATE visitor_parking SET is_paid=1 WHERE flatno=? AND is_paid=0");
    $upk->bind_param("i", $flat_int);
    $upk->execute();

    // Only parking charges are billed — facility bookings are free

    $conn->commit();

    // In-app notification for user
    push_notification(
        $conn, $username,
        "Payment of ₹" . number_format($amount, 2) . " for $month verified and confirmed! ✅",
        'payment', 'payment.php'
    );
    // Notify admin
    push_admin_notification(
        $conn,
        "Payment received: " . htmlspecialchars($username) . " (Flat $flat) paid ₹" . number_format($amount,2) . " for $month via Razorpay.",
        'payment', 'viewpayment.php'
    );

    // Receipt email
    $user_row = $conn->prepare("SELECT Email FROM registration WHERE Username=?");
    $user_row->bind_param("s", $username);
    $user_row->execute();
    $ur = $user_row->get_result()->fetch_assoc();
    if ($ur && $ur['Email']) {
        $body = email_template("Payment Receipt - $month", "
            <p>Hi <strong>" . htmlspecialchars($username) . "</strong>, your payment has been received and verified.</p>
            <div class='info-box'>
                <p><strong>Month:</strong> $month</p>
                <p><strong>Amount Paid:</strong> ₹" . number_format($amount, 2) . "</p>
                <p><strong>Transaction ID:</strong> $razorpay_payment_id</p>
                <p><strong>Status:</strong> ✅ Payment Successful</p>
            </div>
            <p>Thank you for your timely payment! No further action is required.</p>
        ");
        send_email($ur['Email'], $username, "Payment Receipt for $month - Socioease", $body);
    }

    echo json_encode([
        'success'    => true,
        'payment_id' => $razorpay_payment_id,
        'message'    => "Payment for $month confirmed!"
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
