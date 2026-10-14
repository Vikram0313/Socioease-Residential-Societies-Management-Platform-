<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/notifications_helper.php';

if (!isset($_SESSION['loggedin'])) { header('Location: login.php'); exit; }
csrf_verify();

$username    = $_SESSION['username'];
$flatno      = $_SESSION['flatno'] ?? '';
$facility_id = (int)($_POST['facility_id']  ?? 0);
$booking_date= trim($_POST['booking_date']  ?? '');
$slot_id     = (int)($_POST['slot_id']      ?? 0);

if (!$facility_id || !$booking_date || !$slot_id) {
    echo "<script>alert('All fields required.');window.location='facilities.php';</script>"; exit;
}

$chk = $conn->prepare("SELECT COUNT(*) AS cnt FROM facility_bookings WHERE facility_id=? AND booking_date=? AND slot_id=? AND LOWER(status) IN ('approved','pending')");
$chk->bind_param("isi",$facility_id,$booking_date,$slot_id); $chk->execute();
if ($chk->get_result()->fetch_assoc()['cnt'] > 0) {
    echo "<script>alert('Slot already booked or pending!');window.location='facilities.php';</script>"; exit;
}

$ins = $conn->prepare("INSERT INTO facility_bookings (facility_id,user_name,flatno,booking_date,slot_id,status) VALUES (?,?,?,?,?,'Pending')");
$ins->bind_param("isssi",$facility_id,$username,$flatno,$booking_date,$slot_id);

if ($ins->execute()) {
    // Get facility name for messages
    $fn = $conn->prepare("SELECT facility_name FROM facilities WHERE facility_id=?");
    $fn->bind_param("i",$facility_id); $fn->execute();
    $fac_name = $fn->get_result()->fetch_assoc()['facility_name'] ?? 'Facility';

    $slot_map = [1=>"6AM-12PM",2=>"12PM-6PM",3=>"6PM-12AM",4=>"6AM-8AM",5=>"8AM-10AM",6=>"10AM-12PM",7=>"12PM-2PM",8=>"2PM-4PM",9=>"4PM-6PM",10=>"6PM-8PM",11=>"8PM-10PM"];
    $slot_name = $slot_map[$slot_id] ?? "Slot $slot_id";

    push_admin_notification($conn, "Facility booking request: $fac_name on $booking_date ($slot_name) by $username (Flat $flatno)", 'booking', 'facility_admin.php');
    push_notification($conn, $username,
        "$fac_name booking request submitted for $booking_date ($slot_name). Awaiting admin approval.",
        'booking', 'facilities.php'
    );

    $user_row = $conn->query("SELECT Email FROM registration WHERE Username='$username' LIMIT 1")->fetch_assoc();
    if ($user_row) {
        $body = email_template("Facility Booking Requested", "
            <p>Hi <strong>$username</strong>, your facility booking request has been submitted.</p>
            <div class='info-box'>
                <p><strong>Facility:</strong> $fac_name</p>
                <p><strong>Date:</strong> $booking_date</p>
                <p><strong>Time Slot:</strong> $slot_name</p>
                <p><strong>Status:</strong> Pending Admin Approval</p>
            </div>
            <p>You will receive another email once the admin approves or rejects your request.</p>
        ");
        send_email($user_row['Email'], $username, "Facility Booking Submitted  -  Socioease", $body);
    }

    echo "<script>alert('Booking request submitted! Await admin approval.');window.location='facilities.php';</script>";
} else {
    echo "<script>alert('Database error.');window.location='facilities.php';</script>";
}
