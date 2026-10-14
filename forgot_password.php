<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/db_conn.php';

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim($_POST['username'] ?? '');
    $flatno   = trim($_POST['flatno']   ?? '');

    $stmt = $conn->prepare(
        "SELECT Id, Username, Email FROM registration WHERE Username=? AND Flatno=? AND is_verified=1"
    );
    $stmt->bind_param("ss", $username, $flatno);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    // Always show success message to prevent user enumeration
    $success = "If that account exists, a reset link has been sent to the registered email.";

    if ($row) {
        // Delete any existing token for this user
        $del = $conn->prepare("DELETE FROM password_resets WHERE username=?");
        $del->bind_param("s", $username);
        $del->execute();

        $token = bin2hex(random_bytes(32));

        // Use MySQL NOW() so PHP/MySQL timezone mismatch cannot cause token to appear expired
        $expiry_mins = RESET_TOKEN_EXPIRY;
        $ins = $conn->prepare(
            "INSERT INTO password_resets (username, flatno, email, token, expires_at)
             VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))"
        );
        $ins->bind_param("sissi", $username, $flatno, $row['Email'], $token, $expiry_mins);
        $ins->execute();

        require_once __DIR__ . '/mailer.php';
        $reset_link = SITE_URL . "/reset_password.php?token=" . $token;
        $body = email_template("Reset Your Password", "
            <p>Hi <strong>" . htmlspecialchars($username) . "</strong>,</p>
            <p>We received a request to reset your Socioease password. Click the button below to set a new password.</p>
            <div style='text-align:center;margin:25px 0;'>
                <a href='$reset_link' class='btn'>Reset My Password</a>
            </div>
            <div class='info-box'>
                <p><i>This link expires in <strong>" . RESET_TOKEN_EXPIRY . " minutes</strong>. If you did not request a reset, you can safely ignore this email.</i></p>
            </div>
            <p style='font-size:13px;color:#888;'>Or paste this link in your browser:<br>$reset_link</p>
        ");
        send_email($row['Email'], $username, "Socioease  -  Password Reset Request", $body);
    }
}

$csrf = csrf_generate();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Forgot Password | Socioease</title>
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
        .back-link:hover{text-decoration:underline;}
    </style>
</head>
<body>
<div class="card">
    <i class="fas fa-lock"></i>
    <h2>Forgot Password</h2>
    <p>Enter your username and flat number. We'll send a reset link to your registered email.</p>

    <?php if ($success): ?><div class="msg msg-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="msg msg-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="text" name="username" placeholder="Username"  required>
        <input type="text" name="flatno"   placeholder="Flat No."  required>
        <button type="submit" class="btn">Send Reset Link</button>
    </form>
    <?php endif; ?>
    <a href="login.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Login</a>
</div>
</body>
</html>
