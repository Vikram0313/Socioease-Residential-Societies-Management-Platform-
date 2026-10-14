<?php
session_start();
require_once __DIR__ . '/db_conn.php';
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || !isset($_GET['id'])) {
    echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit;
}

$id       = (int)$_GET['id'];
$username = $_SESSION['username'];

$stmt = $conn->prepare("SELECT id, booking_date, slot_id FROM facility_bookings WHERE id=? AND user_name=?");
$stmt->bind_param("is", $id, $username);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    echo json_encode(['success'=>false,'message'=>'Booking not found or access denied']); exit;
}

$slotStartTimes = [
    1=>"06:00:00",2=>"12:00:00",3=>"18:00:00",
    4=>"06:00:00",5=>"08:00:00",6=>"10:00:00",
    7=>"12:00:00",8=>"14:00:00",9=>"16:00:00",
    10=>"18:00:00",11=>"20:00:00"
];
$startTime = $slotStartTimes[$booking['slot_id']] ?? "00:00:00";
$bookingTs = strtotime($booking['booking_date'] . ' ' . $startTime);
$diffSecs  = $bookingTs - time();

if ($diffSecs < 86400) {
    echo json_encode(['success'=>false,'message'=>'Cannot cancel within 24 hours of the slot.']); exit;
}

$del = $conn->prepare("DELETE FROM facility_bookings WHERE id=?");
$del->bind_param("i", $id);
echo $del->execute()
    ? json_encode(['success'=>true])
    : json_encode(['success'=>false,'message'=>'Database error']);
