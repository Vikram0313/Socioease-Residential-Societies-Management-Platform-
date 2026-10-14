<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/db_conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: login.php"); exit; }
csrf_verify();

$username  = trim($_POST['username'] ?? '');
$email     = trim($_POST['email']    ?? '');
$flatno    = trim($_POST['flatno']   ?? '');
$mobileno  = trim($_POST['mobno']    ?? '');
$familymem = (int)($_POST['fammem']  ?? 1);
$password  = trim($_POST['password'] ?? '');

// Validation
$errors = [];
if (strlen($username) < 3)  $errors[] = "Username must be at least 3 characters.";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email address.";
if (strlen($password) < 6)  $errors[] = "Password must be at least 6 characters.";
if (!preg_match('/^[0-9]{10}$/', $mobileno)) $errors[] = "Mobile must be exactly 10 digits.";
if ($errors) {
    header("Location: login.php?reg_error=" . urlencode(implode(' | ', $errors)));
    exit;
}

// Duplicate check
$chk = $conn->prepare("SELECT Id FROM registration WHERE Username=? AND Flatno=?");
$chk->bind_param("ss", $username, $flatno);
$chk->execute();
if ($chk->get_result()->num_rows > 0) {
    header("Location: login.php?reg_error=" . urlencode("Username already registered for this flat."));
    exit;
}

// Create account (unverified)
$hashed = password_hash($password, PASSWORD_DEFAULT);
$token  = bin2hex(random_bytes(32)); // 64-char hex token

$stmt = $conn->prepare(
    "INSERT INTO registration (Username,Email,Flatno,MobileNo,No_of_family_members,Password,is_verified,verification_token)
     VALUES (?,?,?,?,?,?,1,NULL)"
);
$stmt->bind_param("ssssis", $username, $email, $flatno, $mobileno, $familymem, $hashed);

if (!$stmt->execute()) {
    header("Location: login.php?reg_error=" . urlencode("Registration failed. Please try again."));
    exit;
}

// Send welcome email (no verification needed for local install)
require_once __DIR__ . '/mailer.php';
$verify_link = SITE_URL . "/login.php"; // just link to login
$body = email_template("Verify Your Email", "
    <p>Hi <strong>" . htmlspecialchars($username) . "</strong>, welcome to Socioease!</p>
    <p>Your account has been created. Please verify your email address to activate your account and start using the portal.</p>
    <div style='text-align:center;margin:25px 0;'>
        <a href='$verify_link' class='btn'>Verify My Email</a>
    </div>
    <div class='info-box'>
        <p><strong>Flat No:</strong> $flatno &nbsp;&nbsp; <strong>Username:</strong> " . htmlspecialchars($username) . "</p>
    </div>
    <p style='font-size:13px;color:#888;'>If the button doesn't work, copy and paste this link into your browser:<br>$verify_link</p>
    <p style='font-size:13px;color:#888;'>This link does not expire  -  you can verify anytime.</p>
");

send_email($email, $username, "Verify your Socioease account", $body);

// Notify admin of new registration
require_once __DIR__ . '/notifications_helper.php';
push_admin_notification($conn,
    "New member registered: $username (Flat $flatno). Awaiting email verification.",
    'general', 'managemem.php'
);

header("Location: login.php?registered_direct=1");
exit;
