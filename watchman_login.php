<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csrf.php';

if (!empty($_SESSION['loggedin']) && $_SESSION['role'] === 'watchman') {
    header("Location: watchman_dashboard.php"); exit;
}

$error = '';
if (isset($_GET['timeout'])) $error = "Session expired. Please log in again.";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['watchman_login'])) {
    csrf_verify();
    require_once __DIR__ . '/db_conn.php';

    $ip       = $_SERVER['REMOTE_ADDR'];
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Rate limiting
    $la = $conn->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip_address=?");
    $la->bind_param("s", $ip); $la->execute();
    $la_row = $la->get_result()->fetch_assoc();
    if ($la_row) {
        $mins = (time() - strtotime($la_row['last_attempt'])) / 60;
        if ($la_row['attempts'] >= LOGIN_MAX_ATTEMPTS && $mins < LOGIN_LOCKOUT_MINS) {
            $error = "Too many failed attempts. Wait " . ceil(LOGIN_LOCKOUT_MINS - $mins) . " minute(s).";
            goto render;
        }
        if ($mins >= LOGIN_LOCKOUT_MINS) {
            $del = $conn->prepare("DELETE FROM login_attempts WHERE ip_address=?");
            $del->bind_param("s", $ip); $del->execute();
        }
    }

    $stmt = $conn->prepare("SELECT * FROM watchmen WHERE username=? AND is_active=1");
    $stmt->bind_param("s", $username); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row && password_verify($password, $row['password'])) {
        $clr = $conn->prepare("DELETE FROM login_attempts WHERE ip_address=?");
        $clr->bind_param("s", $ip); $clr->execute();

        $_SESSION['loggedin']       = true;
        $_SESSION['role']           = 'watchman';
        $_SESSION['watchman_id']    = $row['id'];
        $_SESSION['watchman_name']  = $row['name'];
        $_SESSION['watchman_user']  = $row['username'];
        $_SESSION['watchman_shift'] = $row['shift'];
        $_SESSION['last_activity']  = time();
        header("Location: watchman_dashboard.php"); exit;
    } else {
        $error = "Invalid username or password.";
        $ups = $conn->prepare("INSERT INTO login_attempts (ip_address,attempts) VALUES (?,1) ON DUPLICATE KEY UPDATE attempts=attempts+1,last_attempt=NOW()");
        $ups->bind_param("s", $ip); $ups->execute();
    }
}

$csrf = csrf_generate();
render:
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Security Login — Socioease</title>
    <script src="https://kit.fontawesome.com/2edfbc5391.js" crossorigin="anonymous"></script>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:system-ui,sans-serif;background:linear-gradient(135deg,#1a1a2e,#16213e,#0f3460);min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;}
        .card{background:rgba(255,255,255,0.05);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.1);padding:45px 40px;border-radius:20px;width:100%;max-width:400px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.5);}
        .shield{font-size:55px;color:#f39c12;margin-bottom:15px;}
        h2{color:white;font-size:26px;margin-bottom:8px;}
        p.sub{color:rgba(255,255,255,0.5);font-size:14px;margin-bottom:30px;}
        .msg{padding:12px;border-radius:8px;margin-bottom:18px;font-size:13px;font-weight:600;background:rgba(231,76,60,0.2);color:#ff6b6b;border:1px solid rgba(231,76,60,0.3);}
        .msg-warn{background:rgba(243,156,18,0.2);color:#f39c12;border-color:rgba(243,156,18,0.3);}
        input{width:100%;padding:14px;margin:8px 0;border-radius:10px;border:1px solid rgba(255,255,255,0.1);background:rgba(255,255,255,0.08);color:white;font-size:15px;outline:none;transition:0.3s;}
        input::placeholder{color:rgba(255,255,255,0.4);}
        input:focus{border-color:#f39c12;background:rgba(255,255,255,0.12);}
        .btn{width:100%;padding:15px;background:#f39c12;color:#1a1a2e;border:none;border-radius:10px;font-size:16px;font-weight:800;cursor:pointer;margin-top:15px;transition:0.3s;}
        .btn:hover{background:#e67e22;transform:translateY(-2px);}
        .back{display:block;margin-top:20px;color:rgba(255,255,255,0.4);font-size:13px;text-decoration:none;transition:0.3s;}
        .back:hover{color:#f39c12;}
        .badge{display:inline-flex;align-items:center;gap:6px;background:rgba(243,156,18,0.15);border:1px solid rgba(243,156,18,0.3);color:#f39c12;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:700;margin-bottom:20px;}
    </style>
</head>
<body>
<div class="card">
    <div class="shield"><i class="fas fa-shield-alt"></i></div>
    <div class="badge"><i class="fas fa-hard-hat"></i> SECURITY PORTAL</div>
    <h2>Watchman Login</h2>
    <p class="sub">Authorised security personnel only</p>

    <?php if ($error): ?>
        <div class="msg <?= str_contains($error,'expired')?'msg-warn':'' ?>"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="text"     name="username" placeholder="Username"  required autocomplete="username">
        <input type="password" name="password" placeholder="Password"  required autocomplete="current-password">
        <button type="submit" name="watchman_login" class="btn"><i class="fas fa-sign-in-alt"></i> Login</button>
    </form>
    <a href="login.php" class="back"><i class="fas fa-arrow-left"></i> Back to Resident Login</a>
</div>
</body>
</html>
