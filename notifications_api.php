<?php
session_start();
require_once __DIR__ . '/db_conn.php';
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin'])) { echo json_encode([]); exit; }

$role   = trim($_GET['role'] ?? 'user');
$action = trim($_GET['action'] ?? 'list');

// Determine username based on role
if ($role === 'admin') {
    $username = 'Admin';
} elseif ($role === 'watchman') {
    $username = $_SESSION['watchman_name'] ?? '';
} else {
    $username = $_SESSION['username'] ?? '';
    $role     = 'user';
}

if (!$username) { echo json_encode([]); exit; }

if ($action === 'list') {
    $limit = min(20, (int)($_GET['limit'] ?? 10));
    $stmt  = $conn->prepare(
        "SELECT * FROM notifications WHERE username=? AND role=? ORDER BY created_at DESC LIMIT ?"
    );
    $stmt->bind_param("ssi", $username, $role, $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as &$r) {
        $diff = time() - strtotime($r['created_at']);
        if      ($diff < 60)    $r['time_ago'] = 'Just now';
        elseif  ($diff < 3600)  $r['time_ago'] = floor($diff/60).'m ago';
        elseif  ($diff < 86400) $r['time_ago'] = floor($diff/3600).'h ago';
        else                    $r['time_ago'] = date('d M', strtotime($r['created_at']));
    }
    echo json_encode($rows);

} elseif ($action === 'read') {
    $id   = (int)($_GET['id'] ?? 0);
    $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND username=? AND role=?");
    $stmt->bind_param("iss", $id, $username, $role);
    $stmt->execute();
    echo json_encode(['ok' => true]);

} elseif ($action === 'read_all') {
    $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE username=? AND role=?");
    $stmt->bind_param("ss", $username, $role);
    $stmt->execute();
    echo json_encode(['ok' => true]);
} elseif ($action === 'delete') {
    $id   = (int)($_GET['id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM notifications WHERE id=? AND username=? AND role=?");
    $stmt->bind_param("iss", $id, $username, $role);
    $stmt->execute();
    echo json_encode(['ok' => true]);

} elseif ($action === 'delete_all') {
    $stmt = $conn->prepare("DELETE FROM notifications WHERE username=? AND role=?");
    $stmt->bind_param("ss", $username, $role);
    $stmt->execute();
    echo json_encode(['ok' => true]);

} else {
    echo json_encode(['error' => 'Unknown action']);
}
