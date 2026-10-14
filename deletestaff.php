<?php
session_start();
require_once __DIR__ . '/db_conn.php';

if (empty($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Location: Adminlogin.php'); exit;
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id   = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM staff_members WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header('Location: managestaff.php?deleted=success');
    } else {
        header('Location: managestaff.php?error=delete_failed');
    }
    $stmt->close();
} else {
    header('Location: managestaff.php?error=no_id');
}
exit;
