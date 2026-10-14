<?php
session_start();
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/db_conn.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("Location: Adminlogin.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    csrf_verify();
    $id = (int)$_POST['Id'];
    $stmt = $conn->prepare("DELETE FROM registration WHERE Id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "<script>alert('Member deleted successfully.');window.location.href='managemem.php';</script>";
    } else {
        echo "<script>alert('Delete failed. Please try again.');window.location.href='managemem.php';</script>";
    }
} else {
    header("Location: managemem.php");
}
