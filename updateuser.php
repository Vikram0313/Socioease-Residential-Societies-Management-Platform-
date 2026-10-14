<?php
session_start();
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/db_conn.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("Location: Adminlogin.php"); exit;
}

$Id  = (int)($_POST['Id'] ?? 0);
$row = null;
$message = '';
$error   = '';

// Handle actual update POST
if (isset($_POST['update'])) {
    csrf_verify();
    $Username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $flatno   = trim($_POST['flatno']   ?? '');
    $mobileno = trim($_POST['mobno']    ?? '');

    $stmt = $conn->prepare("UPDATE registration SET Username=?,Email=?,Flatno=?,MobileNo=? WHERE Id=?");
    $stmt->bind_param("ssssi", $Username, $email, $flatno, $mobileno, $Id);
    if ($stmt->execute()) {
        header("Location: managemem.php?updated=1");
        exit;
    } else {
        $error = "Update failed. Please try again.";
    }
}

// Fetch current data for display
if ($Id > 0) {
    $stmt = $conn->prepare("SELECT * FROM registration WHERE Id=?");
    $stmt->bind_param("i", $Id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
}

if (!$row) {
    echo "<script>alert('Record not found.'); window.location.href='managemem.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Member | Socioease Admin</title>
    <script src="https://kit.fontawesome.com/2edfbc5391.js" crossorigin="anonymous"></script>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',Tahoma,sans-serif;}
        body{background:#b3e7f1;display:flex;justify-content:center;align-items:center;min-height:100vh;}
        .container{background:#19B3D3;width:550px;padding:35px;box-shadow:0 0 25px rgba(0,0,0,0.3);border-radius:15px;text-align:center;}
        .container h1{color:white;font-size:26px;margin-bottom:25px;}
        .error-box{background:rgba(255,255,255,0.3);color:white;padding:10px;border-radius:8px;margin-bottom:15px;font-weight:600;}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;}
        .container input{background:white;color:#333;width:100%;height:42px;border:1px solid #ccc;border-radius:6px;padding:0 12px;font-size:14px;outline:none;}
        .btn-group{display:flex;justify-content:center;gap:15px;margin-top:20px;}
        .update-btn{padding:10px 28px;background:#0B87A6;color:white;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:15px;}
        .update-btn:hover{background:#07657c;}
        .close-btn{padding:10px 20px;background:rgba(255,255,255,0.25);color:white;border:none;border-radius:8px;font-weight:600;text-decoration:none;font-size:15px;display:flex;align-items:center;}
        .close-btn:hover{background:rgba(255,255,255,0.4);}
    </style>
</head>
<body>
<div class="container">
    <h1>Update Member</h1>
    <?php if ($error): ?><div class="error-box"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST" action="updateuser.php">
        <?= csrf_field() ?>
        <input type="hidden" name="Id" value="<?= $row['Id'] ?>">
        <div class="form-row">
            <input type="text"  name="username" value="<?= htmlspecialchars($row['Username']) ?>" placeholder="Username"      required>
            <input type="email" name="email"    value="<?= htmlspecialchars($row['Email'])    ?>" placeholder="Email"         required>
        </div>
        <div class="form-row">
            <input type="text"  name="flatno"   value="<?= htmlspecialchars($row['Flatno'])   ?>" placeholder="Flat No."      required>
            <input type="tel"   name="mobno"    value="<?= htmlspecialchars($row['MobileNo']) ?>" placeholder="Mobile Number" required>
        </div>
        <div class="btn-group">
            <button type="submit" name="update" class="update-btn">Save Changes</button>
            <a href="managemem.php" class="close-btn">Cancel</a>
        </div>
    </form>
</div>
</body>
</html>
