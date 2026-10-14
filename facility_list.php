<?php
session_start();
require_once __DIR__ . '/db_conn.php';
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin'])) {
    http_response_code(403); echo json_encode([]); exit;
}

$username = $_SESSION['username'];
$slotMap  = [
    1=>"6AM - 12PM",2=>"12PM - 6PM",3=>"6PM - 12AM",
    4=>"6AM - 8AM",5=>"8AM - 10AM",6=>"10AM - 12PM",
    7=>"12PM - 2PM",8=>"2PM - 4PM",9=>"4PM - 6PM",
    10=>"6PM - 8PM",11=>"8PM - 10PM"
];

$sql  = "SELECT fb.id, fb.booking_date, fb.slot_id, fb.status, f.facility_name
         FROM facility_bookings fb
         LEFT JOIN facilities f ON fb.facility_id = f.facility_id
         WHERE fb.user_name=? ORDER BY fb.id ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$res  = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = [
        'id'           => $row['id'],
        'facility_name'=> $row['facility_name'],
        'booking_date' => $row['booking_date'],
        'slot'         => $slotMap[$row['slot_id']] ?? 'N/A',
        'status'       => $row['status'],
    ];
}
echo json_encode($data);
