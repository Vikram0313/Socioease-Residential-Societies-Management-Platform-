<?php
// insertfile.php — Add a new member from admin
session_start();
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/db_conn.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("Location: Adminlogin.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $Username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email']    ?? '');
    $flatno    = trim($_POST['flatno']   ?? '');
    $mobileno  = trim($_POST['mobno']    ?? '');
    $familymem = (int)($_POST['fammem']  ?? 1);
    $password  = trim($_POST['password'] ?? '');

    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare(
        "INSERT INTO registration (Username, Email, Flatno, MobileNo, no_of_family_members, Password) VALUES (?,?,?,?,?,?)"
    );
    $stmt->bind_param("ssssis", $Username, $email, $flatno, $mobileno, $familymem, $hashed);
    if ($stmt->execute()) {
        echo "<script>alert('Member added successfully!'); window.location.href='managemem.php';</script>";
    } else {
        echo "<script>alert('Save failed. Please try again.'); window.history.back();</script>";
    }
}
