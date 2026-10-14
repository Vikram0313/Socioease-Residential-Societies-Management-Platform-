<?php
require_once __DIR__ . '/db_conn.php';
header('Content-Type: application/json');

$facility_id  = (int)($_GET['facility_id']  ?? 0);
$booking_date = trim($_GET['booking_date']  ?? '');

if (!$facility_id || !$booking_date) { echo json_encode([]); exit; }

$stmt = $conn->prepare(
    "SELECT slot_id FROM facility_bookings
     WHERE facility_id=? AND booking_date=? AND LOWER(status) IN ('approved','pending')"
);
$stmt->bind_param("is", $facility_id, $booking_date);
$stmt->execute();
$res = $stmt->get_result();

$booked = [];
while ($row = $res->fetch_assoc()) { $booked[] = $row['slot_id']; }
echo json_encode($booked);
