<?php
require_once __DIR__ . '/db_conn.php';
header('Content-Type: application/json');

$sd = trim($_GET['sd'] ?? '');
$ed = trim($_GET['ed'] ?? '');

if (!$sd || !$ed) { echo json_encode(['available'=>10]); exit; }

// Exclude bookings whose end datetime has already passed
$now_dt = date('Y-m-d H:i:s');
$stmt = $conn->prepare("
    SELECT SUM(slots) AS booked FROM visitor_parking
    WHERE NOT (end_date < ? OR start_date > ?)
    AND CONCAT(end_date, ' ', TIME_FORMAT(STR_TO_DATE(end_time, '%h:%i %p'), '%H:%i:%s')) >= ?
");
$stmt->bind_param("sss", $sd, $ed, $now_dt);
$stmt->execute();
$booked    = (int)($stmt->get_result()->fetch_assoc()['booked'] ?? 0);
$available = max(0, 10 - $booked);
echo json_encode(['available' => $available]);
