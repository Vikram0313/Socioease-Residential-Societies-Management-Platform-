<?php
session_start();
require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/notifications_helper.php';

if (empty($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("Location: Adminlogin.php"); exit;
}

$action = trim($_GET['action'] ?? '');
$id     = (int)($_GET['id']    ?? 0);

if ($id <= 0 || !in_array($action, ['approve','reject'])) {
    header("Location: facility_admin.php?error=invalid"); exit;
}

$stmt = $conn->prepare("SELECT fb.*, f.facility_name FROM facility_bookings fb LEFT JOIN facilities f ON fb.facility_id=f.facility_id WHERE fb.id=?");
$stmt->bind_param("i", $id); $stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
if (!$booking) { header("Location: facility_admin.php?error=notfound"); exit; }

$slot_map  = [1=>"6AM-12PM",2=>"12PM-6PM",3=>"6PM-12AM",4=>"6AM-8AM",5=>"8AM-10AM",6=>"10AM-12PM",7=>"12PM-2PM",8=>"2PM-4PM",9=>"4PM-6PM",10=>"6PM-8PM",11=>"8PM-10PM"];
$slot_name = $slot_map[$booking['slot_id']] ?? "Slot ".$booking['slot_id'];
$fac_name  = $booking['facility_name'] ?? 'Facility';
$username  = $booking['user_name'];

$user_row = $conn->query("SELECT Email FROM registration WHERE Username='$username' LIMIT 1")->fetch_assoc();
$user_email = $user_row['Email'] ?? '';

if ($action === 'approve') {
    $chk = $conn->prepare("SELECT COUNT(*) AS cnt FROM facility_bookings WHERE facility_id=? AND booking_date=? AND slot_id=? AND LOWER(status)='approved' AND id<>?");
    $chk->bind_param("isii",$booking['facility_id'],$booking['booking_date'],$booking['slot_id'],$id); $chk->execute();
    if ($chk->get_result()->fetch_assoc()['cnt'] > 0) {
        header("Location: facility_admin.php?error=conflict"); exit;
    }

    $upd = $conn->prepare("UPDATE facility_bookings SET status='Approved' WHERE id=?");
    $upd->bind_param("i",$id); $upd->execute();

    push_notification($conn, $username,
        "Your $fac_name booking on {$booking['booking_date']} ($slot_name) has been APPROVED!",
        'booking', 'facilities.php'
    );
    if ($user_email) {
        $body = email_template("Facility Booking Approved ✓", "
            <p>Hi <strong>$username</strong>, great news! Your facility booking has been approved.</p>
            <div class='info-box'>
                <p><strong>Facility:</strong> $fac_name</p>
                <p><strong>Date:</strong> {$booking['booking_date']}</p>
                <p><strong>Time Slot:</strong> $slot_name</p>
                <p><strong>Status:</strong> ✅ Approved</p>
            </div>
            <p>Please carry your resident ID for entry verification.</p>
        ");
        send_email($user_email, $username, "Facility Booking Approved  -  Socioease", $body);
    }
    header("Location: facility_admin.php?msg=approved"); exit;
}

if ($action === 'reject') {
    $upd = $conn->prepare("UPDATE facility_bookings SET status='Rejected' WHERE id=?");
    $upd->bind_param("i",$id); $upd->execute();

    push_notification($conn, $username,
        "Your $fac_name booking on {$booking['booking_date']} ($slot_name) was rejected.",
        'booking', 'facilities.php'
    );
    if ($user_email) {
        $body = email_template("Facility Booking Rejected", "
            <p>Hi <strong>$username</strong>, unfortunately your facility booking has been rejected.</p>
            <div class='info-box'>
                <p><strong>Facility:</strong> $fac_name</p>
                <p><strong>Date:</strong> {$booking['booking_date']}</p>
                <p><strong>Time Slot:</strong> $slot_name</p>
                <p><strong>Status:</strong> ❌ Rejected</p>
            </div>
            <p>Please contact the society office for more information or try booking a different slot.</p>
        ");
        send_email($user_email, $username, "Facility Booking Rejected  -  Socioease", $body);
    }
    header("Location: facility_admin.php?msg=rejected"); exit;
}

header("Location: facility_admin.php");
