<?php
require 'auth_check.php';

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $current  = trim($_POST['current_password']  ?? '');
    $new_pw   = trim($_POST['new_password']      ?? '');
    $confirm  = trim($_POST['confirm_password']  ?? '');

    if (strlen($new_pw) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif ($new_pw !== $confirm) {
        $error = "New passwords do not match.";
    } else {
        $username = $_SESSION['username'];
        $flatno   = $_SESSION['flatno'];

        $stmt = $conn->prepare("SELECT Password FROM registration WHERE Username=? AND Flatno=?");
        $stmt->bind_param("ss", $username, $flatno);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row) {
            $error = "Account not found. Please log in again.";
        } else {
            $valid = password_verify($current, $row['Password'])
                     || ($current === trim($row['Password']));
            if (!$valid) {
                $error = "Current password is incorrect.";
            } else {
                $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
                $upd = $conn->prepare("UPDATE registration SET Password=? WHERE Username=? AND Flatno=?");
                $upd->bind_param("sss", $hashed, $username, $flatno);
                if ($upd->execute()) {
                    $success = "Password changed successfully!";
                } else {
                    $error = "Database error. Please try again.";
                }
            }
        }
    }
}

$page_title = "Change Password";
require 'header_user.php';
require 'sidebar_user.php';
?>
<style>
.pw-card { max-width: 500px; margin: 0 auto; padding: 35px; border-top: 5px solid #19B3D3; }
.pw-card h2 { color: #0B3A45; margin-bottom: 25px; font-size: 22px; }
</style>

<div class="main-container" id="main-container">
    <div class="card-box pw-card">
        <h2><i class="fas fa-key" style="color:#19B3D3;margin-right:10px;"></i>Change Password</h2>

        <?php if ($success): ?><div class="flash-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="flash-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="POST">
            <?= csrf_field() ?>
            <label style="font-weight:600;color:#444;font-size:14px;display:block;margin-bottom:6px;">Current Password</label>
            <input type="password" name="current_password" class="field" placeholder="Enter current password" required>

            <label style="font-weight:600;color:#444;font-size:14px;display:block;margin-bottom:6px;">New Password</label>
            <input type="password" name="new_password" class="field" placeholder="Min. 6 characters" required minlength="6">

            <label style="font-weight:600;color:#444;font-size:14px;display:block;margin-bottom:6px;">Confirm New Password</label>
            <input type="password" name="confirm_password" class="field" placeholder="Repeat new password" required>

            <button type="submit" class="btn btn-full" style="margin-top:5px;padding:14px;font-size:16px;">Update Password</button>
        </form>
    </div>
</div>

<?php require 'footer.php'; ?>
