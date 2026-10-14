<?php
session_start();
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/db_conn.php';

if (empty($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Location: Adminlogin.php'); exit;
}

$message = '';
$error   = '';
$staff   = null;

// Handle update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_staff'])) {
    csrf_verify();
    $staff_id   = (int)$_POST['staff_id'];
    $name       = trim($_POST['staff_name']    ?? '');
    $mobile     = trim($_POST['staff_mobile']  ?? '');
    $email      = trim($_POST['staff_email']   ?? '');
    $address    = trim($_POST['staff_address'] ?? '');
    $role       = trim($_POST['staff_role']    ?? '');

    if (!$name || !$mobile || !$role) {
        $error = "Name, Mobile, and Role fields are required.";
    } else {
        $stmt = $conn->prepare("UPDATE staff_members SET name=?,mobile=?,email=?,address=?,role=? WHERE id=?");
        $stmt->bind_param("sssssi", $name, $mobile, $email, $address, $role, $staff_id);
        if ($stmt->execute()) {
            header('Location: managestaff.php?update=success');
            exit;
        } else {
            $error = "Error updating: " . $stmt->error;
        }
    }
}

// Fetch staff record
$fetch_id = (int)($_POST['staff_id'] ?? $_GET['id'] ?? 0);
if ($fetch_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM staff_members WHERE id=?");
    $stmt->bind_param("i", $fetch_id);
    $stmt->execute();
    $staff = $stmt->get_result()->fetch_assoc();
    if (!$staff) $error = "Staff member not found.";
} else {
    header('Location: managestaff.php?error=no_id'); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Staff | Socioease Admin</title>
    <script src="https://kit.fontawesome.com/2edfbc5391.js" crossorigin="anonymous"></script>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',Tahoma,sans-serif;}
        body{background:#b3e7f1;display:flex;justify-content:center;align-items:center;min-height:100vh;padding:20px;}
        .container{background:#19B3D3;width:560px;padding:35px;box-shadow:0 0 25px rgba(0,0,0,0.3);border-radius:15px;text-align:center;}
        .container h1{color:white;font-size:26px;margin-bottom:25px;}
        .msg{padding:10px;border-radius:8px;margin-bottom:15px;font-weight:600;}
        .msg-error{background:rgba(220,53,69,0.25);color:white;}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;}
        .container input,.container textarea{background:white;color:#333;width:100%;height:42px;border:1px solid #ccc;border-radius:6px;padding:0 12px;font-size:14px;outline:none;}
        .container textarea{height:80px;resize:none;padding:10px 12px;}
        .btn-group{display:flex;justify-content:center;gap:15px;margin-top:20px;}
        .save-btn{padding:11px 28px;background:#0B87A6;color:white;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:15px;}
        .save-btn:hover{background:#07657c;}
        .cancel-btn{padding:11px 20px;background:rgba(255,255,255,0.25);color:white;border:none;border-radius:8px;font-weight:600;text-decoration:none;font-size:15px;display:flex;align-items:center;}
        .cancel-btn:hover{background:rgba(255,255,255,0.4);}
    </style>
</head>
<body>
<div class="container">
    <h1>Edit Staff Member</h1>
    <?php if ($error): ?><div class="msg msg-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($staff): ?>
    <form method="POST" action="editstaff.php">
        <?= csrf_field() ?>
        <input type="hidden" name="update_staff" value="1">
        <input type="hidden" name="staff_id"     value="<?= $staff['id'] ?>">
        <div class="form-row">
            <input type="text"  name="staff_name"   value="<?= htmlspecialchars($staff['name']) ?>"   placeholder="Full Name*"           required>
            <input type="text"  name="staff_role"   value="<?= htmlspecialchars($staff['role']) ?>"   placeholder="Role / Designation*"  required>
        </div>
        <div class="form-row">
            <input type="text"  name="staff_mobile" value="<?= htmlspecialchars($staff['mobile']) ?>" placeholder="Mobile Number*"       required>
            <input type="email" name="staff_email"  value="<?= htmlspecialchars($staff['email']) ?>"  placeholder="Email (optional)">
        </div>
        <textarea name="staff_address" placeholder="Full Address (optional)"><?= htmlspecialchars($staff['address']) ?></textarea>
        <div class="btn-group">
            <button type="submit" class="save-btn">Save Changes</button>
            <a href="managestaff.php" class="cancel-btn">Cancel</a>
        </div>
    </form>
    <?php else: ?>
        <p style="color:white;">Could not load staff data.</p>
        <a href="managestaff.php" class="cancel-btn" style="margin-top:15px;justify-content:center;">Back to List</a>
    <?php endif; ?>
</div>
</body>
</html>
