<?php
require 'auth_check_admin.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_secretary'])) {
    csrf_verify();
    $name    = trim($_POST['secretary_name']  ?? '');
    $mobile  = trim($_POST['mobile_number']   ?? '');
    $email   = trim($_POST['email_address']   ?? '');
    $address = trim($_POST['address']         ?? '');
    $maint   = (float)($_POST['maintenance_amt'] ?? 1500);
    $prate   = (float)($_POST['visitor_parking_rate'] ?? 50);

    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $_SESSION['flash_error'] = "Mobile number must be exactly 10 digits!";
    } elseif ($name !== '') {
        $check = $conn->query("SELECT id FROM society_details LIMIT 1");
        if ($check && $check->num_rows === 1) {
            $stmt = $conn->prepare("UPDATE society_details SET name=?,mobile_number=?,email_address=?,address=?,maintenance_amt=?,visitor_parking_rate=?,updated_at=NOW() WHERE id=1");
            $stmt->bind_param("ssssdd", $name, $mobile, $email, $address, $maint, $prate);
        } else {
            $stmt = $conn->prepare("INSERT INTO society_details (name,mobile_number,email_address,address,maintenance_amt,visitor_parking_rate,updated_at) VALUES (?,?,?,?,?,?,NOW())");
            $stmt->bind_param("ssssdd", $name, $mobile, $email, $address, $maint, $prate);
        }
        $stmt->execute();
        $_SESSION['flash_success'] = "Society details updated successfully!";
    }
    header("Location: society_details.php");
    exit;
}

$res = $conn->query("SELECT * FROM society_details LIMIT 1");
$sec = ($res && $res->num_rows) ? $res->fetch_assoc() : [];
$currentName    = $sec['name']            ?? 'Not Set';
$currentMobile  = $sec['mobile_number']   ?? 'Not Set';
$currentEmail   = $sec['email_address']   ?? 'Not Set';
$currentAddress = $sec['address']         ?? 'Not Set';
$currentMaint   = $sec['maintenance_amt'] ?? 1500;
$currentPRate   = $sec['visitor_parking_rate'] ?? 50;

$success = $_SESSION['flash_success'] ?? '';
$error   = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$page_title = "Society Details";
require 'header_admin.php';
require 'sidebar_admin.php';
?>
<style>
.sec-card { max-width:700px; margin:0 auto 30px; padding:30px; border-top:5px solid #19B3D3; }
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
.form-group { margin-bottom:15px; }
.form-group.full { grid-column:span 2; }
.form-group label { font-weight:600; color:#444; font-size:13px; display:block; margin-bottom:8px; }
.form-group input, .form-group textarea { width:100%; padding:12px; font-size:15px; border-radius:8px; border:1px solid #ddd; background:#fcfdfe; outline:none; transition:0.3s; font-family:inherit; }
.form-group input:focus, .form-group textarea:focus { border-color:#19B3D3; }
.current-sec { max-width:700px; margin:0 auto; background:#0B3A45; color:white; padding:30px; border-radius:15px; box-shadow:0 6px 20px rgba(0,0,0,0.15); }
.info-row { display:flex; justify-content:space-between; border-bottom:1px solid rgba(255,255,255,0.1); padding:12px 0; }
.info-row:last-child { border:none; }
.info-label { color:#19B3D3; font-weight:600; font-size:14px; }
.info-value { font-size:15px; }
</style>

<div class="main-container" id="main-container">
    <h2 class="page-title">Society Details</h2>

    <div class="card-box sec-card">
        <?php if ($success): ?><div class="flash-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="flash-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="POST">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="form-group full">
                    <label>Full Name</label>
                    <input type="text" name="secretary_name" value="<?= $currentName !== 'Not Set' ? htmlspecialchars($currentName) : '' ?>" placeholder="Name of Secretary" required>
                </div>
                <div class="form-group">
                    <label>Mobile Number (10 digits)</label>
                    <input type="text" name="mobile_number"
                           value="<?= $currentMobile !== 'Not Set' ? htmlspecialchars($currentMobile) : '' ?>"
                           placeholder="xxxxxxxxxx" maxlength="10" pattern="\d{10}"
                           oninput="this.value=this.value.replace(/[^0-9]/g,'')" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email_address" value="<?= $currentEmail !== 'Not Set' ? htmlspecialchars($currentEmail) : '' ?>" placeholder="xxxxxx@gmail.com" required>
                </div>
                <div class="form-group">
                    <label>Monthly Maintenance Amount (₹)</label>
                    <input type="number" name="maintenance_amt" value="<?= (float)$currentMaint ?>" placeholder="1500" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Visitor Parking Rate (₹/hr)</label>
                    <input type="number" name="visitor_parking_rate" value="<?= (float)$currentPRate ?>" placeholder="50" min="0" step="0.01" required>
                </div>
                <div class="form-group full">
                    <label>Office Address</label>
                    <textarea name="address" rows="3" placeholder="Enter building/office details..."><?= $currentAddress !== 'Not Set' ? htmlspecialchars($currentAddress) : '' ?></textarea>
                </div>
            </div>
            <button type="submit" name="save_secretary" class="btn btn-full" style="height:50px;font-size:16px;">Update Profile</button>
        </form>
    </div>

    <div class="current-sec">
        <h3 style="margin-bottom:20px;border-bottom:2px solid #19B3D3;display:inline-block;padding-bottom:5px;">Current Secretary Profile</h3>
        <div class="info-row"><span class="info-label">Name:</span>        <span class="info-value"><?= htmlspecialchars($currentName) ?></span></div>
        <div class="info-row"><span class="info-label">Mobile:</span>      <span class="info-value"><?= htmlspecialchars($currentMobile) ?></span></div>
        <div class="info-row"><span class="info-label">Email:</span>       <span class="info-value"><?= htmlspecialchars($currentEmail) ?></span></div>
        <div class="info-row"><span class="info-label">Maintenance:</span> <span class="info-value">₹<?= number_format((float)$currentMaint, 2) ?>/month</span></div>
        <div class="info-row"><span class="info-label">Parking Rate:</span><span class="info-value">₹<?= number_format((float)$currentPRate, 2) ?>/hour</span></div>
        <div class="info-row"><span class="info-label">Address:</span>     <span class="info-value"><?= htmlspecialchars($currentAddress) ?></span></div>
    </div>
</div>

<?php require 'footer.php'; ?>