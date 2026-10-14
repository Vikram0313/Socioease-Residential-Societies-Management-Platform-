<?php
require 'auth_check.php';

$user_name = $_SESSION['username'];

$stmt = $conn->prepare("SELECT Flatno FROM registration WHERE Username=?");
$stmt->bind_param("s", $user_name);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc();
$flatno = $r['Flatno'] ?? 'N/A';

// Secretary
$secRes  = $conn->query("SELECT * FROM society_details LIMIT 1");
$secData = ($secRes && $secRes->num_rows) ? $secRes->fetch_assoc() : [];
$secName  = $secData['name']           ?? 'Not Set';
$secPhone = $secData['mobile_number']  ?? 'N/A';
$secEmail = $secData['email_address']  ?? 'N/A';
$secAddr  = $secData['address']        ?? 'N/A';

// Greeting
date_default_timezone_set("Asia/Kolkata");
$h = (int)date('H');
$greeting = $h < 12 ? "Good Morning" : ($h < 17 ? "Good Afternoon" : "Good Evening");

$page_title = "Dashboard";
require 'header_user.php';
require 'sidebar_user.php';
?>
<style>
.welcome-card { background:white; padding:35px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.05); margin-bottom:30px; border-left:6px solid #19B3D3; }
.grid-container { display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:25px; }
.dash-card { background:white; height:250px; padding:40px 25px; border-radius:12px; text-align:center; box-shadow:0 4px 12px rgba(0,0,0,0.05); transition:0.3s; border-bottom:4px solid #19B3D3; display:flex; flex-direction:column; align-items:center; justify-content:center; }
.dash-card:hover { transform:translateY(-4px); box-shadow:0 8px 20px rgba(0,0,0,0.1); }
.dash-card i { font-size:50px; color:#19B3D3; margin-bottom:15px; }
.dash-card h3 { color:#0B3A45; font-size:24px; margin-bottom:5px; }
.dash-card p { color:#888; font-weight:800; font-size:12px; letter-spacing:1px; }
.flip-card { background:transparent; width:100%; height:250px; perspective:1000px; }
.flip-card-inner { position:relative; width:100%; height:100%; text-align:center; transition:transform 0.8s; transform-style:preserve-3d; }
.flip-card:hover .flip-card-inner { transform:rotateY(180deg); }
.flip-front, .flip-back { position:absolute; width:100%; height:100%; -webkit-backface-visibility:hidden; backface-visibility:hidden; border-radius:12px; display:flex; flex-direction:column; align-items:center; justify-content:center; box-shadow:0 4px 12px rgba(0,0,0,0.05); }
.flip-front { background:white; border-bottom:4px solid #19B3D3; }
.flip-front i { font-size:50px; color:#19B3D3; margin-bottom:15px; }
.flip-front h3 { color:#0B3A45; font-size:22px; margin-bottom:5px; }
.flip-front p { color:#888; font-weight:800; font-size:12px; letter-spacing:1px; }
.flip-back { background:#0B3A45; color:white; transform:rotateY(180deg); padding:20px; text-align:left; align-items:flex-start; }
.sec-detail { margin-bottom:10px; font-size:14px; display:flex; align-items:center; gap:10px; }
.sec-detail i { color:#19B3D3; width:20px; text-align:center; }
</style>

<div class="main-container" id="main-container">
    <div class="welcome-card">
        <h1><?= $greeting ?>, <?= htmlspecialchars($user_name) ?> 👋</h1>
        <p style="color:#666;margin-top:8px;">Welcome back to your resident portal.</p>
    </div>

    <div class="grid-container">
        <div class="dash-card">
            <i class="fas fa-user-circle"></i>
            <h3><?= htmlspecialchars($user_name) ?></h3>
            <p>Resident Name</p>
        </div>
        <div class="dash-card">
            <i class="fas fa-building"></i>
            <h3><?= htmlspecialchars($flatno) ?></h3>
            <p>Flat Number</p>
        </div>
        <div class="flip-card">
            <div class="flip-card-inner">
                <div class="flip-front">
                    <i class="fas fa-user-shield"></i>
                    <h3><?= htmlspecialchars($secName) ?></h3>
                    <p>Society Secretary</p>
                    <small style="color:#19B3D3;margin-top:10px;font-size:10px;">HOVER TO REVEAL INFO</small>
                </div>
                <div class="flip-back">
                    <h3 style="color:#19B3D3;margin-bottom:15px;border-bottom:1px solid rgba(255,255,255,0.1);width:100%;padding-bottom:5px;">Contact Info</h3>
                    <div class="sec-detail"><i class="fas fa-phone"></i><span><?= htmlspecialchars($secPhone) ?></span></div>
                    <div class="sec-detail"><i class="fas fa-envelope"></i><span style="font-size:12px;"><?= htmlspecialchars($secEmail) ?></span></div>
                    <div class="sec-detail" style="align-items:flex-start;"><i class="fas fa-map-marker-alt" style="margin-top:3px;"></i><span style="font-size:12px;"><?= htmlspecialchars($secAddr) ?></span></div>
                    <div style="margin-top:auto;font-size:10px;opacity:0.6;width:100%;text-align:center;">Socioease Managed</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require 'footer.php'; ?>
