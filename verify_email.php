<?php
require_once __DIR__ . '/db_conn.php';

$token = trim($_GET['token'] ?? '');
$msg   = '';
$type  = 'error';

if ($token === '') {
    $msg = "Invalid verification link.";
} else {
    $stmt = $conn->prepare(
        "SELECT Id, Username FROM registration WHERE verification_token=? AND is_verified=0"
    );
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row) {
        $upd = $conn->prepare(
            "UPDATE registration SET is_verified=1, verification_token=NULL WHERE Id=?"
        );
        $upd->bind_param("i", $row['Id']);
        $upd->execute();
        $msg  = "Your email has been verified! You can now log in, " . htmlspecialchars($row['Username']) . ".";
        $type = 'success';
    } else {
        // Maybe already verified
        $chk = $conn->prepare("SELECT Id FROM registration WHERE verification_token=?");
        $chk->bind_param("s", $token);
        $chk->execute();
        $msg = $chk->get_result()->num_rows === 0
            ? "This link has already been used or is invalid."
            : "Invalid verification token.";
    }
}

if ($type === 'success') {
    header("Location: login.php?verified=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Email Verification | Socioease</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{background:linear-gradient(135deg,#e6f2f6,#bcd6e6);min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:system-ui,sans-serif;}
        .card{background:white;padding:45px;border-radius:20px;box-shadow:0 10px 30px rgba(0,0,0,0.1);max-width:440px;width:90%;text-align:center;}
        .card i{font-size:55px;color:#e74c3c;margin-bottom:20px;}
        .card h2{color:#0B3A45;margin-bottom:10px;}
        .card p{color:#666;margin-bottom:25px;line-height:1.6;}
        .btn{display:inline-block;background:#19B3D3;color:white;padding:12px 28px;border-radius:10px;text-decoration:none;font-weight:700;}
    </style>
    <script src="https://kit.fontawesome.com/2edfbc5391.js" crossorigin="anonymous"></script>
</head>
<body>
<div class="card">
    <i class="fas fa-times-circle"></i>
    <h2>Verification Failed</h2>
    <p><?= htmlspecialchars($msg) ?></p>
    <a href="login.php" class="btn">Go to Login</a>
</div>
</body>
</html>
