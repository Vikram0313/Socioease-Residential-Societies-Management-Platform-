<?php
session_start();
require_once __DIR__ . '/db_conn.php';
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || !isset($_GET['id'])) {
    echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit;
}

$id     = (int)$_GET['id'];
$user   = $_SESSION['username'];
$flatno = $_SESSION['flatno'] ?? '';

$stmt = $conn->prepare("SELECT start_date, start_time, total_price FROM visitor_parking WHERE id=? AND username=?");
$stmt->bind_param("is", $id, $user);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    echo json_encode(['success'=>false,'message'=>'Booking not found.']); exit;
}

$start_ts   = strtotime($booking['start_date'] . ' ' . $booking['start_time']);
$diff_secs  = $start_ts - time();

if ($diff_secs < 86400) {
    echo json_encode(['success'=>false,'message'=>'Cancellation blocked: less than 24 hours remaining.']); exit;
}

$conn->begin_transaction();
try {
    // Delete the booking entirely. payment.php will no longer detect it, automatically removing it from the bill!
    $del = $conn->prepare("DELETE FROM visitor_parking WHERE id=?");
    $del->bind_param("i", $id);
    $del->execute();

    $conn->commit();
    echo json_encode(['success'=>true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success'=>false,'message'=>'Transaction failed: '.$e->getMessage()]);
}