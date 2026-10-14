<?php
require 'auth_check_watchman.php';
$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $current  = trim($_POST['current_password']  ?? '');
    $new_pw   = trim($_POST['new_password']      ?? '');
    $confirm  = trim($_POST['confirm_password']  ?? '');

    if (strlen($new_pw) < 6)    { $error = "New password must be at least 6 characters."; }
    elseif ($new_pw !== $confirm){ $error = "Passwords do not match."; }
    else {
        $wid  = $_SESSION['watchman_id'];
        $stmt = $conn->prepare("SELECT password FROM watchmen WHERE id=?");
        $stmt->bind_param("i",$wid); $stmt->execute();
        $row  = $stmt->get_result()->fetch_assoc();
        if (!$row || !password_verify($current, $row['password'])) {
            $error = "Current password is incorrect.";
        } else {
            $hash = password_hash($new_pw, PASSWORD_DEFAULT);
            $upd  = $conn->prepare("UPDATE watchmen SET password=? WHERE id=?");
            $upd->bind_param("si",$hash,$wid); $upd->execute();
            $success = "Password changed successfully!";
        }
    }
}
$page_title = "Change Password";
require 'header_watchman.php';
require 'sidebar_watchman.php';
?>
<style>
.pw-card{max-width:500px;margin:0 auto;padding:35px;border-top:5px solid #f39c12;}
.pw-card h2{color:#0B3A45;margin-bottom:25px;font-size:22px;}
.input-box{width:100%;padding:12px;border-radius:8px;border:1.5px solid #ddd;background:#f9f9f9;font-size:15px;outline:none;margin-bottom:15px;transition:0.3s;font-family:inherit;}
.input-box:focus{border-color:#f39c12;}
.btn-save{width:100%;padding:14px;background:#f39c12;color:#1a1a2e;border:none;border-radius:8px;font-weight:800;cursor:pointer;font-size:16px;}
</style>
<div class="main-container" id="main-container">
    <div class="card-box pw-card">
        <h2><i class="fas fa-key" style="color:#f39c12;margin-right:10px;"></i>Change Password</h2>
        <?php if ($success): ?><div class="flash-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="flash-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
            <?= csrf_field() ?>
            <label style="font-weight:600;font-size:14px;color:#444;display:block;margin-bottom:6px;">Current Password</label>
            <input type="password" name="current_password" class="input-box" required>
            <label style="font-weight:600;font-size:14px;color:#444;display:block;margin-bottom:6px;">New Password</label>
            <input type="password" name="new_password" class="input-box" required minlength="6">
            <label style="font-weight:600;font-size:14px;color:#444;display:block;margin-bottom:6px;">Confirm New Password</label>
            <input type="password" name="confirm_password" class="input-box" required>
            <button type="submit" class="btn-save">Update Password</button>
        </form>
    </div>
</div>
<?php require 'footer.php'; ?>
