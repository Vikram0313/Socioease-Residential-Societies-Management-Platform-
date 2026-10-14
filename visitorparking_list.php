<?php
session_start();
require_once __DIR__ . '/db_conn.php';
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin'])) {
    http_response_code(403); echo json_encode([]); exit;
}

$flat = $_SESSION['flatno'] ?? '';
$stmt = $conn->prepare("SELECT * FROM visitor_parking WHERE flatno=? ORDER BY id DESC");
$stmt->bind_param("s", $flat);
$stmt->execute();
$res  = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
    $start_ts  = strtotime($row['start_date'] . ' ' . $row['start_time']);
    $can_cancel = (($start_ts - time()) >= 86400);
    $data[] = [
        'id'           => $row['id'],
        'visitor_name' => $row['visitor_name'],
        'vehicle_no'   => $row['vehicle_no'],
        'slots'        => $row['slots'],
        'start_date'   => $row['start_date'],
        'start_time'   => $row['start_time'],
        'total_price'  => $row['total_price'],
        'can_cancel'   => $can_cancel,
    ];
}
echo json_encode($data);
