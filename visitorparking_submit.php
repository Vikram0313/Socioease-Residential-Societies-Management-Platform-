<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/notifications_helper.php';

if (!isset($_SESSION['loggedin'])) { header('Location: login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: visitorparking.php'); exit; }
csrf_verify();

$username = $_SESSION['username'];
$flatno   = $_SESSION['flatno'] ?? '';
if (!$flatno) {
    $st = $conn->prepare("SELECT Flatno FROM registration WHERE Username=?");
    $st->bind_param("s", $username); $st->execute();
    $flatno = $st->get_result()->fetch_assoc()['Flatno'] ?? '';
    $_SESSION['flatno'] = $flatno;
}

$visitor_name = trim($_POST['visitor_name'] ?? '');
$vehicle_no   = trim($_POST['vehicle_no']   ?? '');
$slots        = max(1,(int)($_POST['slots'] ?? 1));
$start_date   = trim($_POST['start_date']   ?? '');
$end_date     = trim($_POST['end_date']     ?? '');
$start_time   = trim($_POST['start_time']   ?? '');
$end_time     = trim($_POST['end_time']     ?? '');

// createFromFormat is required  -  new DateTime() misparses "h:i A" AM/PM strings
$startDT = DateTime::createFromFormat('Y-m-d h:i A', "$start_date $start_time");
$endDT   = DateTime::createFromFormat('Y-m-d h:i A', "$end_date $end_time");

if (!$startDT || !$endDT) {
    echo "<script>alert('Invalid date or time format. Please try again.');window.history.back();</script>";
    exit;
}

$diff_seconds = $endDT->getTimestamp() - $startDT->getTimestamp();
if ($diff_seconds <= 0) {
    echo "<script>alert('End time must be after start time.');window.history.back();</script>";
    exit;
}
$total_hours = round($diff_seconds / 3600, 4);

// Fetch dynamic parking rate
$sec_stmt = $conn->query("SELECT visitor_parking_rate FROM society_details LIMIT 1");
$sec_data = $sec_stmt ? $sec_stmt->fetch_assoc() : null;
$parking_rate = (float)($sec_data['visitor_parking_rate'] ?? 50);

$total_price = round($total_hours * $parking_rate * $slots, 2);

// Capacity check
$chk = $conn->prepare("SELECT SUM(slots) AS booked FROM visitor_parking WHERE NOT (end_date <= ? OR start_date >= ?)");
$chk->bind_param("ss", $start_date, $end_date); $chk->execute();
$booked = (int)($chk->get_result()->fetch_assoc()['booked'] ?? 0);
if ($booked + $slots > 10) {
    echo "<script>alert('Parking full for selected dates!');window.history.back();</script>"; exit;
}

$conn->begin_transaction();
try {
    // Insert into visitor_parking (payment.php will automatically detect this and add to bill!)
    $ins = $conn->prepare("INSERT INTO visitor_parking (username,flatno,visitor_name,vehicle_no,slots,start_date,end_date,start_time,end_time,total_hours,total_price) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $ins->bind_param("ssssissssdd", $username,$flatno,$visitor_name,$vehicle_no,$slots,$start_date,$end_date,$start_time,$end_time,$total_hours,$total_price);
    $ins->execute();
    
    $conn->commit();

    // Notify admin
    push_admin_notification($conn, "Visitor parking booked by $username (Flat $flatno) for $visitor_name ($vehicle_no)", 'parking', 'visitorparking_admin.php');
    // Notify user
    push_notification($conn, $username,
        "Visitor parking booked for $visitor_name ($vehicle_no) from $start_date $start_time. ₹$total_price added to your bill.",
        'parking', 'visitorparking.php'
    );

    // Email
    $user_row = $conn->query("SELECT Email FROM registration WHERE Username='$username' LIMIT 1")->fetch_assoc();
    if ($user_row) {
        $body = email_template("Visitor Parking Confirmed", "
            <p>Hi <strong>$username</strong>, your visitor parking has been booked successfully.</p>
            <div class='info-box'>
                <p><strong>Visitor:</strong> $visitor_name</p>
                <p><strong>Vehicle:</strong> $vehicle_no</p>
                <p><strong>From:</strong> $start_date $start_time</p>
                <p><strong>To:</strong> $end_date $end_time</p>
                <p><strong>Slots:</strong> $slots</p>
                <p><strong>Amount Billed:</strong> ₹$total_price</p>
            </div>
            <p>This amount has been added to your flat's maintenance balance. Please settle it at month-end via the Payments page.</p>
        ");
        send_email($user_row['Email'], $username, "Visitor Parking Confirmed  -  Socioease", $body);
    }

    header("Location: visitorparking.php?msg=success");
} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}