<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/db_conn.php';

$token   = trim($_GET['token'] ?? '');
$success = '';
$error   = '';
$valid   = false;
$row     = null;

// Validate token
if ($token) {
    $stmt = $conn->prepare(
        "SELECT * FROM password_resets WHERE token=? AND expires_at > NOW()"
    );
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) $valid = true;
    else $error = "This reset link is invalid or has expired. Please request a new one.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_submit'])) {
    csrf_verify();
    $new_pw  = trim($_POST['new_password']     ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');
    $tk      = trim($_POST['token']            ?? '');

    // Re-validate token from POST
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token=? AND expires_at > NOW()");
    $stmt->bind_param("s", $tk);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row)             { $error = "Reset link expired. Please request a new one."; }
    elseif (strlen($new_pw) < 6) { $error = "Password must be at least 6 characters."; }
    elseif ($new_pw !== $confirm) { $error = "Passwords do not match."; }
    else {
        $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE registration SET Password=? WHERE Username=? AND Flatno=?");
        $upd->bind_param("ssi", $hashed, $row['username'], $row['flatno']);
        $upd->execute();

        // Delete the used token
        $conn->prepare("DELETE FROM password_resets WHERE token=?")->bind_param("s", $tk);

        $success = "Password reset successfully! You can now log in with your new password.";
        $valid   = false;
    }
}

$csrf = csrf_generate();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Reset Password | Socioease</title>
    <script src="https://kit.fontawesome.com/2edfbc5391.js" crossorigin="anonymous"></script>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{background:linear-gradient(135deg,#e6f2f6,#bcd6e6);min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:system-ui,sans-serif;}
        .card{background:white;padding:45px 40px;border-radius:20px;box-shadow:0 10px 30px rgba(0,0,0,0.1);max-width:420px;width:90%;text-align:center;}
        .card i{font-size:50px;color:#19B3D3;margin-bottom:15px;}
        .card h2{color:#0B3A45;margin-bottom:8px;}
        .card p{color:#777;font-size:14px;margin-bottom:25px;}
        .msg{padding:11px;border-radius:8px;font-size:13px;margin-bottom:18px;font-weight:600;}
        .msg-success{background:#d4edda;color:#155724;}
        .msg-error{background:#f9d6d5;color:#d9534f;}
        input{width:100%;padding:13px;margin:8px 0;border-radius:10px;border:1px solid #ddd;background:#f9f9f9;font-size:14px;outline:none;transition:0.3s;}
        input:focus{border-color:#19B3D3;}
        .btn{width:100%;padding:14px;background:#19B3D3;color:white;border:none;border-radius:10px;font-size:16px;font-weight:700;cursor:pointer;margin-top:8px;transition:0.3s;}
        .btn:hover{background:#0B87A6;}
        .back-link{display:block;margin-top:18px;color:#0B87A6;font-size:13px;text-decoration:none;}
    </style>
</head>
<body>
<div class="card">
    <i class="fas fa-key"></i>
    <h2>Set New Password</h2>

    <?php if ($success): ?>
        <div class="msg msg-success"><?= htmlspecialchars($success) ?></div>
        <a href="login.php" class="btn" style="display:block;text-decoration:none;margin-top:10px;">Go to Login</a>
    <?php elseif ($valid && $row): ?>
        <p>Creating new password for <strong><?= htmlspecialchars($row['username']) ?></strong></p>
        <?php if ($error): ?><div class="msg msg-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="token"        value="<?= htmlspecialchars($token ?: $_POST['token'] ?? '') ?>">
            <input type="hidden" name="reset_submit" value="1">
            <input type="password" name="new_password"     placeholder="New password (min 6 chars)" required minlength="6">
            <input type="password" name="confirm_password" placeholder="Confirm new password"       required>
            <button type="submit" class="btn">Set New Password</button>
        </form>
    <?php else: ?>
        <div class="msg msg-error"><?= htmlspecialchars($error ?: "Invalid reset link.") ?></div>
        <a href="forgot_password.php" class="btn" style="display:block;text-decoration:none;">Request New Link</a>
    <?php endif; ?>

    <a href="login.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Login</a>
</div>
</body>
</html>
