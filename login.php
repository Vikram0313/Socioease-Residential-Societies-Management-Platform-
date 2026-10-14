<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csrf.php';

if (!empty($_SESSION['loggedin'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        $_SESSION['role'] = 'user';
        header("Location: Welcome.php");
    }
    exit;
}

$error   = '';
$success = '';

if (isset($_GET['timeout'])) $error = "Your session expired. Please log in again.";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    csrf_verify();
    require_once __DIR__ . '/db_conn.php';

    $ip       = $_SERVER['REMOTE_ADDR'];
    $username = trim($_POST['username'] ?? '');
    $flatno   = trim($_POST['flatno']   ?? '');
    $password = trim($_POST['password'] ?? '');

    // Rate limiting
    $la = $conn->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip_address=?");
    $la->bind_param("s", $ip); $la->execute();
    $la_row = $la->get_result()->fetch_assoc();

    if ($la_row) {
        $mins_since = (time() - strtotime($la_row['last_attempt'])) / 60;
        if ($la_row['attempts'] >= LOGIN_MAX_ATTEMPTS && $mins_since < LOGIN_LOCKOUT_MINS) {
            $wait  = ceil(LOGIN_LOCKOUT_MINS - $mins_since);
            $error = "Too many failed attempts. Please wait $wait minute(s).";
            goto render;
        }
        if ($mins_since >= LOGIN_LOCKOUT_MINS) {
            $del = $conn->prepare("DELETE FROM login_attempts WHERE ip_address=?");
            $del->bind_param("s", $ip); $del->execute();
            $la_row = null;
        }
    }

    $stmt = $conn->prepare("SELECT * FROM registration WHERE Username=? AND Flatno=?");
    $stmt->bind_param("ss", $username, $flatno); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row) {
        $valid = password_verify($password, $row['Password']) || ($password === trim($row['Password']));
        if ($valid) {
            if (!(int)$row['is_verified']) {
                $error = "Please verify your email before logging in.";
                goto render;
            }
            if (!password_get_info($row['Password'])['algo']) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $upd  = $conn->prepare("UPDATE registration SET Password=? WHERE Id=?");
                $upd->bind_param("si", $hash, $row['Id']); $upd->execute();
            }
            $clr = $conn->prepare("DELETE FROM login_attempts WHERE ip_address=?");
            $clr->bind_param("s", $ip); $clr->execute();

            $_SESSION['loggedin']      = true;
            $_SESSION['role']          = 'user';
            $_SESSION['username']      = $row['Username'];
            $_SESSION['flatno']        = $row['Flatno'];
            $_SESSION['last_activity'] = time();
            header("Location: Welcome.php"); exit;
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "User not found. Check your username and flat number.";
    }

    if ($error && $error !== "Please verify your email before logging in.") {
        $ups = $conn->prepare("INSERT INTO login_attempts (ip_address,attempts) VALUES (?,1) ON DUPLICATE KEY UPDATE attempts=attempts+1,last_attempt=NOW()");
        $ups->bind_param("s", $ip); $ups->execute();
    }
}

if (isset($_GET['registered'])) $success = "Registration successful! You can now log in.";
if (isset($_GET['registered_direct'])) $success = "Registration successful! You can now log in.";
if (isset($_GET['verified']))   $success = "Email verified! You can now log in.";
if (isset($_GET['reg_error']))  $error   = htmlspecialchars($_GET['reg_error']);

$csrf = csrf_generate();
render:
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Socioease — Manage Your Society</title>
    <script src="https://kit.fontawesome.com/2edfbc5391.js" crossorigin="anonymous"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        *{box-sizing:border-box;margin:0;padding:0;}
        body{
            font-family:'Plus Jakarta Sans',system-ui,sans-serif;
            background:#EFF6FF;
            min-height:100vh;
            display:flex;flex-direction:column;
        }

        /* NAVBAR */
        .navbar{
            display:flex;justify-content:space-between;align-items:center;
            padding:18px 8%;
            background:white;
            border-bottom:1px solid #E2EAF4;
        }
        .navbar h1{color:#0D2137;font-size:26px;font-weight:800;}
        .navbar h1 span{color:#0EA5C9;}

        /* MAIN LAYOUT */
        .main-row{
            flex:1;display:grid;grid-template-columns:1fr 1fr;
            padding:60px 8%;gap:60px;align-items:center;max-width:1200px;margin:0 auto;width:100%;
        }

        /* LEFT HERO */
        .hero-title{font-size:44px;font-weight:800;color:#0D2137;line-height:1.2;margin-bottom:16px;}
        .hero-title span{color:#0EA5C9;}
        .hero-sub{font-size:16px;color:#64748B;line-height:1.7;margin-bottom:32px;}
        .feature-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
        .feature-card{
            background:white;padding:18px;border-radius:14px;
            box-shadow:0 1px 8px rgba(14,165,201,0.08);
            border:1px solid #E2EAF4;
        }
        .feature-card i{font-size:20px;color:#0EA5C9;margin-bottom:8px;display:block;}
        .feature-card h4{color:#0D2137;font-size:14px;font-weight:700;margin-bottom:3px;}
        .feature-card p{color:#94A3B8;font-size:12px;}

        /* FORM CARD */
        .form-card{
            background:white;
            border-radius:20px;
            box-shadow:0 4px 30px rgba(13,33,55,0.10);
            overflow:hidden;
            border:1px solid #E2EAF4;
        }

        /* Portal buttons row inside card */
        .portal-row{
            display:flex;gap:0;
            border-bottom:1px solid #E2EAF4;
        }
        .portal-btn{
            flex:1;padding:13px;text-align:center;text-decoration:none;
            font-size:13px;font-weight:700;
            transition:0.2s;display:flex;align-items:center;justify-content:center;gap:7px;
        }
        .portal-btn.admin{
            background:#F8FAFC;color:#0D2137;
            border-right:1px solid #E2EAF4;
        }
        .portal-btn.admin:hover{background:#0D2137;color:white;}
        .portal-btn.security{background:#FFFBF0;color:#B45309;}
        .portal-btn.security:hover{background:#B45309;color:white;}

        /* Form body */
        .form-body{padding:30px 30px 28px;}

        /* Tabs */
        .toggle-btns{display:flex;margin-bottom:24px;position:relative;background:#F1F5F9;border-radius:10px;padding:4px;}
        .toggle-btns span{
            cursor:pointer;font-weight:600;flex:1;text-align:center;
            padding:9px;border-radius:7px;font-size:14px;color:#64748B;
            transition:0.2s;z-index:1;position:relative;
        }
        .toggle-btns span.active-tab{background:white;color:#0D2137;box-shadow:0 1px 4px rgba(0,0,0,0.08);}

        /* Messages */
        .msg{padding:11px 14px;border-radius:10px;font-size:13px;margin-bottom:16px;text-align:center;font-weight:600;}
        .msg-error  {background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;}
        .msg-success{background:#F0FDF4;color:#059669;border:1px solid #BBF7D0;}

        /* Inputs */
        .input-wrap{position:relative;margin-bottom:14px;}
        .input-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94A3B8;font-size:14px;}
        input{
            width:100%;padding:13px 14px 13px 40px;
            border-radius:10px;border:1.5px solid #E2EAF4;
            background:#F8FAFC;font-size:14px;outline:none;
            transition:0.2s;font-family:inherit;color:#0D2137;
        }
        input:focus{border-color:#0EA5C9;background:white;box-shadow:0 0 0 3px rgba(14,165,201,0.10);}
        input::placeholder{color:#94A3B8;}

        /* Login button */
        .btn-losi{
            width:100%;padding:14px;background:#0EA5C9;color:white;
            border:none;border-radius:10px;font-weight:700;font-size:15px;
            cursor:pointer;margin-top:6px;transition:0.2s;font-family:inherit;
        }
        .btn-losi:hover{background:#0284A8;transform:translateY(-1px);}
        .forgot-link{display:block;text-align:right;font-size:12px;color:#0EA5C9;text-decoration:none;margin-bottom:14px;}
        .forgot-link:hover{text-decoration:underline;}

        /* Footer */
        footer{background:#0D2137;color:white;padding:35px 8%;}
        .footer-grid{display:grid;grid-template-columns:1.5fr 1fr 1fr;gap:40px;max-width:1200px;margin:0 auto;}
        .footer-grid h2{font-size:15px;margin-bottom:12px;color:#0EA5C9;font-weight:700;}
        .footer-grid p,.footer-grid li{font-size:13px;opacity:0.65;line-height:1.7;list-style:none;}
        .footer-grid a{color:white;text-decoration:none;}

        @media(max-width:900px){
            .main-row{grid-template-columns:1fr;padding:30px 5%;}
            .hero-title{font-size:32px;}
            .footer-grid{grid-template-columns:1fr;}
        }
    </style>
</head>
<body>

<nav class="navbar">
    <h1>Socio<span>ease</span></h1>
    <span style="font-size:13px;color:#94A3B8;">Society Management Portal</span>
</nav>

<div class="main-row">
    <!-- LEFT -->
    <div>
        <h1 class="hero-title">Manage Your Society,<br><span>In One Place.</span></h1>
        <p class="hero-sub">Stay connected, informed, and organised — from complaints and payments to notices and facilities.</p>
        <div class="feature-grid">
            <div class="feature-card"><i class="fas fa-bullhorn"></i><h4>Notice Board</h4><p>Real-time society updates</p></div>
            <div class="feature-card"><i class="fas fa-wallet"></i><h4>Payments</h4><p>Track maintenance easily</p></div>
            <div class="feature-card"><i class="fas fa-car"></i><h4>Parking</h4><p>Visitor slot management</p></div>
            <div class="feature-card"><i class="fas fa-dumbbell"></i><h4>Facilities</h4><p>Book society amenities</p></div>
        </div>
    </div>

    <!-- RIGHT — Form card -->
    <div>
        <div class="form-card">

            <!-- Portal access row -->
            <div class="portal-row">
                <a href="Adminlogin.php" class="portal-btn admin">
                    <i class="fas fa-user-shield"></i> Admin Portal
                </a>
                <a href="watchman_login.php" class="portal-btn security">
                    <i class="fas fa-hard-hat"></i> Security Portal
                </a>
            </div>

            <div class="form-body">
                <div class="toggle-btns">
                    <span class="active-tab" id="tab-login"    onclick="showLogin()">Login</span>
                    <span               id="tab-register" onclick="showRegister()">Register</span>
                </div>

                <?php if ($error):   ?><div class="msg msg-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="msg msg-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

                <!-- LOGIN -->
                <form action="login.php" method="POST" id="Loginform">
                    <input type="hidden" name="csrf_token"    value="<?= $csrf ?>">
                    <input type="hidden" name="login_submit"  value="1">
                    <div class="input-wrap"><i class="fas fa-user"></i><input type="text"     name="username" placeholder="Username"  required></div>
                    <div class="input-wrap"><i class="fas fa-building"></i><input type="text" name="flatno"   placeholder="Flat No." required></div>
                    <div class="input-wrap"><i class="fas fa-lock"></i><input type="password" name="password" placeholder="Password" required></div>
                    <a class="forgot-link" href="forgot_password.php">Forgot Password?</a>
                    <button type="submit" class="btn-losi">Login</button>
                </form>

                <!-- REGISTER -->
                <form action="register.php" method="POST" id="Regform" style="display:none;">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <div class="input-wrap"><i class="fas fa-user"></i><input type="text"     name="username" placeholder="Username"               required></div>
                    <div class="input-wrap"><i class="fas fa-envelope"></i><input type="email" name="email"   placeholder="Email Address"           required></div>
                    <div class="input-wrap"><i class="fas fa-building"></i><input type="text" name="flatno"   placeholder="Flat No."                required></div>
                    <div class="input-wrap"><i class="fas fa-phone"></i><input type="tel"      name="mobno"   placeholder="Mobile Number"           required></div>
                    <div class="input-wrap"><i class="fas fa-users"></i><input type="number"   name="fammem"  placeholder="No. of Family Members"   required min="1"></div>
                    <div class="input-wrap"><i class="fas fa-lock"></i><input type="password"  name="password" placeholder="Password (min 6 chars)" required minlength="6"></div>
                    <button type="submit" class="btn-losi">Register Now</button>
                    <a href="resend_verification.php" style="display:block;text-align:center;margin-top:12px;font-size:12px;color:#94A3B8;text-decoration:none;">Didn't receive verification email? Resend it</a>
                </form>
            </div>
        </div>
        <p style="text-align:center;font-size:12px;color:#94A3B8;margin-top:14px;">
            <i class="fas fa-shield-alt" style="color:#0EA5C9;"></i> Secured by Socioease &nbsp;|&nbsp; All data encrypted
        </p>
    </div>
</div>

<footer>
    <div class="footer-grid">
        <div><h2>About Socioease</h2><p>Simplifying apartment management for residents and administrators across India.</p></div>
        <div><h2>Quick Links</h2><ul><li><a href="login.php">Resident Login</a></li><li><a href="Adminlogin.php">Admin Login</a></li><li><a href="watchman_login.php">Security Login</a></li></ul></div>
        <div><h2>Contact</h2><p>Pune, Maharashtra</p><p>Socioease@gmail.com</p></div>
    </div>
</footer>

<script>
    function showLogin(){
        document.getElementById('Regform').style.display='none';
        document.getElementById('Loginform').style.display='block';
        document.getElementById('tab-login').classList.add('active-tab');
        document.getElementById('tab-register').classList.remove('active-tab');
    }
    function showRegister(){
        document.getElementById('Regform').style.display='block';
        document.getElementById('Loginform').style.display='none';
        document.getElementById('tab-login').classList.remove('active-tab');
        document.getElementById('tab-register').classList.add('active-tab');
    }
    <?php if (isset($_GET['registered']) || isset($_GET['reg_error'])): ?>showRegister();<?php endif; ?>
</script>
</body>
</html>
