<?php
require 'auth_check_watchman.php';
require_once __DIR__ . '/mailer.php';
$wuser = $_SESSION['watchman_user'];
$wname = $_SESSION['watchman_name'];

// Raise emergency
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['raise_emergency'])) {
    csrf_verify();
    $type     = trim($_POST['alert_type']   ?? '');
    $desc     = trim($_POST['description']  ?? '');
    $location = trim($_POST['location']     ?? '');

    $stmt = $conn->prepare("INSERT INTO emergency_log (raised_by,alert_type,description,location) VALUES (?,?,?,?)");
    $stmt->bind_param("ssss",$wuser,$type,$desc,$location);
    $stmt->execute();

    // Alert admin
    push_admin_notification($conn,
        "🚨 EMERGENCY: $type at $location — Reported by $wname",
        'emergency', 'admin_dashboard.php'
    );

    // Email all residents
    $residents = $conn->query("SELECT Email, Username FROM registration WHERE is_verified=1");
    $sec = $conn->query("SELECT * FROM society_details LIMIT 1")->fetch_assoc();
    while ($r = $residents->fetch_assoc()) {
        if ($r['Email']) {
            $body = email_template("Emergency Alert - $type", "
                <p>Hi <strong>".htmlspecialchars($r['Username'])."</strong>,</p>
                <p style='color:#e74c3c;font-weight:700;font-size:16px;'>⚠️ An emergency has been reported in your society.</p>
                <div class='info-box'>
                    <p><strong>Type:</strong> ".htmlspecialchars($type)."</p>
                    <p><strong>Location:</strong> ".htmlspecialchars($location)."</p>
                    <p><strong>Details:</strong> ".htmlspecialchars($desc)."</p>
                    <p><strong>Reported By:</strong> Security — ".htmlspecialchars($wname)."</p>
                    <p><strong>Time:</strong> ".date('d/m/Y h:i A')."</p>
                </div>
                <p>Please follow safety protocols. Contact society office for updates.</p>
            ");
            send_email($r['Email'], $r['Username'], "EMERGENCY ALERT: $type - Society", $body);
        }
    }

    $_SESSION['flash_success'] = "Emergency alert raised and admin notified!";
    header("Location: watchman_emergency.php"); exit;
}

// Resolve
if (isset($_GET['resolve_id'])) {
    $rid  = (int)$_GET['resolve_id'];
    $stmt = $conn->prepare("UPDATE emergency_log SET status='Resolved',resolved_by=?,resolved_at=NOW() WHERE id=?");
    $stmt->bind_param("si",$wname,$rid); $stmt->execute();
    header("Location: watchman_emergency.php"); exit;
}

$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

$active_alerts   = $conn->query("SELECT * FROM emergency_log WHERE status='Active' ORDER BY created_at DESC");
$resolved_alerts = $conn->query("SELECT * FROM emergency_log WHERE status='Resolved' ORDER BY resolved_at DESC LIMIT 10");

$alert_types = ['Fire','Medical Emergency','Security Breach','Power Failure','Water Emergency','Theft','Accident','Other'];

$page_title = "Emergency";
require 'header_watchman.php';
require 'sidebar_watchman.php';
?>
<style>
.emergency-banner{background:linear-gradient(135deg,#c0392b,#e74c3c);color:white;padding:25px 30px;border-radius:12px;margin-bottom:25px;text-align:center;}
.emergency-banner i{font-size:40px;margin-bottom:10px;display:block;}
.emergency-banner h2{font-size:22px;margin-bottom:5px;}
.emergency-banner p{opacity:0.8;font-size:13px;}
.eform{max-width:700px;margin:0 auto 30px;padding:30px;border-top:5px solid #e74c3c;}
.input-box{width:100%;padding:11px 14px;border-radius:8px;border:1.5px solid #ddd;background:#f9f9f9;font-size:14px;outline:none;font-family:inherit;transition:0.3s;margin-bottom:15px;}
.input-box:focus{border-color:#e74c3c;background:white;}
.btn-emergency{width:100%;background:#e74c3c;color:white;border:none;padding:15px;border-radius:8px;font-weight:800;font-size:16px;cursor:pointer;transition:0.3s;}
.btn-emergency:hover{background:#c0392b;}
.btn-resolve{background:#27ae60;color:white;border:none;padding:6px 14px;border-radius:6px;cursor:pointer;font-size:12px;font-weight:700;}
.alert-active{background:#fff5f5;border-left:5px solid #e74c3c;padding:15px;border-radius:8px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:flex-start;}
.alert-type{font-weight:800;color:#e74c3c;font-size:15px;}
.alert-detail{font-size:13px;color:#666;margin-top:5px;}
</style>

<div class="main-container" id="main-container">
    <?php if ($success): ?><div class="flash-success" style="max-width:700px;margin:0 auto 20px;"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <?php if ($active_alerts->num_rows > 0): ?>
    <div class="emergency-banner">
        <i class="fas fa-exclamation-triangle"></i>
        <h2><?= $active_alerts->num_rows ?> Active Emergency Alert<?= $active_alerts->num_rows>1?'s':'' ?></h2>
        <p>Resolve alerts after the situation is handled.</p>
    </div>
    <?php
    $active_alerts->data_seek(0);
    while ($a = $active_alerts->fetch_assoc()): ?>
    <div class="alert-active" style="max-width:700px;margin:0 auto 12px;">
        <div>
            <div class="alert-type"><i class="fas fa-fire"></i> <?= htmlspecialchars($a['alert_type']) ?></div>
            <div class="alert-detail"><?= htmlspecialchars($a['description'] ?? '') ?></div>
            <div class="alert-detail"><strong>Location:</strong> <?= htmlspecialchars($a['location'] ?? '') ?></div>
            <div class="alert-detail"><strong>Time:</strong> <?= date('d/m/Y h:i A', strtotime($a['created_at'])) ?></div>
        </div>
        <a href="?resolve_id=<?= $a['id'] ?>" class="btn-resolve"
           onclick="return confirm('Mark this emergency as resolved?')">
            <i class="fas fa-check"></i> Resolve
        </a>
    </div>
    <?php endwhile; endif; ?>

    <div class="card-box eform">
        <h2 style="color:#e74c3c;margin-bottom:20px;font-size:20px;"><i class="fas fa-exclamation-triangle" style="margin-right:8px;"></i>Raise Emergency Alert</h2>
        <form method="POST">
            <?= csrf_field() ?>
            <label style="font-weight:600;font-size:13px;color:#444;display:block;margin-bottom:6px;">Alert Type *</label>
            <select name="alert_type" class="input-box" required>
                <option value="">Select type</option>
                <?php foreach ($alert_types as $at): ?>
                    <option value="<?= $at ?>"><?= $at ?></option>
                <?php endforeach; ?>
            </select>
            <label style="font-weight:600;font-size:13px;color:#444;display:block;margin-bottom:6px;">Location *</label>
            <input type="text" name="location" class="input-box" placeholder="e.g. B Wing 3rd Floor, Basement" required>
            <label style="font-weight:600;font-size:13px;color:#444;display:block;margin-bottom:6px;">Description</label>
            <textarea name="description" class="input-box" rows="3" placeholder="Describe the emergency situation..."></textarea>
            <button type="submit" name="raise_emergency" class="btn-emergency">
                <i class="fas fa-bell"></i> Raise Emergency Alert — Notify All Residents
            </button>
        </form>
    </div>

    <?php if ($resolved_alerts->num_rows > 0): ?>
    <h3 style="color:#27ae60;margin-bottom:15px;font-size:18px;">Recently Resolved</h3>
    <div class="table-container">
        <table class="content-table">
            <thead><tr><th>No</th><th>Type</th><th>Location</th><th>Raised</th><th>Resolved</th><th>By</th></tr></thead>
            <tbody>
            <?php $i=1; while($r=$resolved_alerts->fetch_assoc()): ?>
                <tr>
                    <td style="color:#888;"><?= $i++ ?></td>
                    <td><?= htmlspecialchars($r['alert_type']) ?></td>
                    <td><?= htmlspecialchars($r['location'] ?? '-') ?></td>
                    <td><?= date('d/m/Y h:i A', strtotime($r['created_at'])) ?></td>
                    <td><?= $r['resolved_at'] ? date('d/m/Y h:i A', strtotime($r['resolved_at'])) : '-' ?></td>
                    <td><?= htmlspecialchars($r['resolved_by'] ?? '-') ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require 'footer.php'; ?>
