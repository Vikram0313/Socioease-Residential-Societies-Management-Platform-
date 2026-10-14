<?php
// Returns the logged-in user's email for Razorpay prefill
session_start();
require_once __DIR__ . '/db_conn.php';
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['email' => '']);
    exit;
}

$stmt = $conn->prepare("SELECT Email FROM registration WHERE Username=?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
echo json_encode(['email' => $row['Email'] ?? '']);
