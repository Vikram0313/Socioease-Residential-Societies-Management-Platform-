<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csrf.php';

if (!empty($_SESSION['loggedin']) && $_SESSION['role'] === 'admin') {
    header("Location: admin_dashboard.php"); exit;
}

$error = '';
if (isset($_GET['timeout'])) $error = "Your session expired. Please log in again.";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logina'])) {
    csrf_verify();
    require_once __DIR__ . '/db_conn.php';

    $ip     = $_SERVER['REMOTE_ADDR'];
    $user   = trim($_POST['username']  ?? '');
    $adcode = trim($_POST['admincode'] ?? '');

    // Rate limit admin login too
    $la = $conn->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip_address=?");
    $la->bind_param("s", $ip); $la->execute();
    $la_row = $la->get_result()->fetch_assoc();

    if ($la_row) {
        $mins_since = (time() - strtotime($la_row['last_attempt'])) / 60;
        if ($la_row['attempts'] >= LOGIN_MAX_ATTEMPTS && $mins_since < LOGIN_LOCKOUT_MINS) {
            $wait  = ceil(LOGIN_LOCKOUT_MINS - $mins_since);
            $error = "Too many failed attempts. Wait $wait minute(s).";
            goto render;
        }
        if ($mins_since >= LOGIN_LOCKOUT_MINS) {
            $conn->prepare("DELETE FROM login_attempts WHERE ip_address=?")->bind_param("s",$ip);
        }
    }

    if ($user === "Admin" && $adcode === "100") {
        // Clear any failed attempts
        $del = $conn->prepare("DELETE FROM login_attempts WHERE ip_address=?");
        $del->bind_param("s", $ip); $del->execute();

        $_SESSION['loggedin']      = true;
        $_SESSION['role']          = 'admin';
        $_SESSION['username']      = $user;
        $_SESSION['last_activity'] = time();
        header("Location: admin_dashboard.php");
        exit;
    } else {
        $error = "Invalid admin username or code.";
        $ups = $conn->prepare("INSERT INTO login_attempts (ip_address,attempts) VALUES (?,1) ON DUPLICATE KEY UPDATE attempts=attempts+1, last_attempt=NOW()");
        $ups->bind_param("s",$ip); $ups->execute();
    }
}

$csrf = csrf_generate();
render:
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Socioease</title>
    <script src="https://kit.fontawesome.com/2edfbc5391.js" crossorigin="anonymous"></script>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:system-ui,sans-serif;background:#EFF6FF;min-height:100vh;display:flex;flex-direction:column;}
        .navbar{display:flex;justify-content:space-between;align-items:center;padding:20px 60px;}
        .navbar h1{color:#0D2137;font-size:26px;font-weight:800;}
        .navbar .btn{border:2px solid #0EA5C9;color:#0EA5C9;padding:8px 16px;border-radius:20px;text-decoration:none;font-weight:600;transition:0.3s;}
        .navbar .btn:hover{background:#0EA5C9;color:white;}
        .main-container{flex:1;display:flex;justify-content:center;align-items:center;padding:20px;}
        .form-container{width:100%;max-width:400px;background:white;padding:45px 35px;border-radius:20px;box-shadow:0 15px 35px rgba(0,0,0,0.1);text-align:center;}
        .admin-header i{font-size:50px;color:#19B3D3;margin-bottom:15px;}
        .admin-header h2{color:#0D2137;font-size:24px;font-weight:800;margin-bottom:8px;}
        .admin-header p{color:#777;font-size:14px;margin-bottom:30px;}
        .msg{padding:12px;border-radius:8px;margin-bottom:15px;font-size:13px;font-weight:600;}
        .msg-error{background:#f8d7da;color:#721c24;}
        .msg-warning{background:#fff3cd;color:#856404;}
        input{width:100%;padding:14px;margin:10px 0;border-radius:12px;border:1px solid #ddd;background:#fdfdfd;font-size:15px;outline:none;transition:0.3s;}
        input:focus{border-color:#0EA5C9;box-shadow:0 0 0 3px rgba(14,165,201,0.12);}
        .btn-losi{width:100%;padding:15px;background:#0EA5C9;color:white;border:none;border-radius:12px;font-size:16px;font-weight:700;cursor:pointer;margin-top:20px;transition:0.3s;}
        .btn-losi:hover{background:#0284A8;}
        footer{background:#0D2137;color:white;padding:30px 60px;text-align:center;}
        footer p{font-size:13px;opacity:0.7;}
    </style>
</head>
<body>
<nav class="navbar">
    <h1>Socio<span style="color:#0B87A6;">ease</span></h1>
    <a href="login.php" class="btn">User Login</a>
</nav>
<main class="main-container">
    <div class="form-container">
        <div class="admin-header">
            <i class="fas fa-user-lock"></i>
            <h2>Admin Login</h2>
            <p>Secure access for society administrators</p>
        </div>
        <?php if ($error): ?>
            <div class="msg <?= str_contains($error,'expired') ? 'msg-warning' : 'msg-error' ?>">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <form action="Adminlogin.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="text"     name="username"  placeholder="Username"   required autocomplete="username">
            <input type="password" name="admincode" placeholder="Admin Code" required autocomplete="current-password">
            <button type="submit" name="logina" class="btn-losi">Access Dashboard</button>
        </form>
    </div>
</main>
<footer><p>&copy; <?= date('Y') ?> Socioease. All rights reserved.</p></footer>
</body>
</html>
