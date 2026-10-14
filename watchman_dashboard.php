<?php
require 'auth_check_watchman.php';

$wname  = $_SESSION['watchman_name'];
$wshift = $_SESSION['watchman_shift'];
$wuser  = $_SESSION['watchman_user'];

// Today's stats
$today = date('Y-m-d');
$visitors_today  = $conn->query("SELECT COUNT(*) FROM visitor_log WHERE DATE(time_in) = CURDATE()")->fetch_row()[0] ?? 0;
$active_visitors = $conn->query("SELECT COUNT(*) FROM visitor_log WHERE DATE(time_in) = CURDATE() AND time_out IS NULL")->fetch_row()[0] ?? 0;
$pending_parcels = $conn->query("SELECT COUNT(*) FROM parcel_log WHERE status='Pending'")->fetch_row()[0] ?? 0;
// Active parking = bookings currently ongoing right now
$now_dt = date('Y-m-d H:i:s');
$active_parking = $conn->query("
    SELECT COUNT(*) FROM visitor_parking
    WHERE start_date <= '$today' AND end_date >= '$today'
    AND CONCAT(start_date,' ',TIME_FORMAT(STR_TO_DATE(start_time,'%h:%i %p'),'%H:%i:%s')) <= '$now_dt'
    AND CONCAT(end_date,  ' ',TIME_FORMAT(STR_TO_DATE(end_time,  '%h:%i %p'),'%H:%i:%s')) >= '$now_dt'
")->fetch_row()[0] ?? 0;
$active_emergency= $conn->query("SELECT COUNT(*) FROM emergency_log WHERE status='Active'")->fetch_row()[0] ?? 0;

// Recent visitors (last 5)
$recent_visitors = $conn->query("SELECT * FROM visitor_log ORDER BY time_in DESC LIMIT 5");

// Greeting based on CURRENT real time, not shift
date_default_timezone_set("Asia/Kolkata");
$h = (int)date('H');
$greeting = $h < 12 ? "Good Morning" : ($h < 17 ? "Good Afternoon" : "Good Evening");
// Shift icon
$shift_icon = match($wshift) {
    'Morning' => 'fa-sun',
    'Evening' => 'fa-cloud-sun',
    'Night'   => 'fa-moon',
    default   => 'fa-clock'
};

$page_title = "Security Dashboard";
require 'header_watchman.php';
require 'sidebar_watchman.php';
?>
<style>
.welcome-card{background:linear-gradient(135deg,#1a1a2e,#0f3460);color:white;padding:30px 35px;border-radius:12px;margin-bottom:30px;display:flex;justify-content:space-between;align-items:center;}
.welcome-card h1{font-size:24px;margin-bottom:5px;}
.welcome-card p{opacity:0.7;font-size:14px;}
.shift-badge{background:rgba(243,156,18,0.2);border:1px solid #f39c12;color:#f39c12;padding:8px 18px;border-radius:20px;font-weight:700;font-size:13px;}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:20px;margin-bottom:30px;}
.stat-card{background:white;padding:22px;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.07);display:flex;align-items:center;gap:18px;border-left:5px solid #f39c12;transition:0.3s;}
.stat-card:hover{transform:translateY(-3px);}
.stat-card.danger{border-left-color:#e74c3c;}
.stat-card.success{border-left-color:#27ae60;}
.stat-card.info{border-left-color:#3498db;}
.stat-icon{width:50px;height:50px;border-radius:10px;background:#fef9e7;display:flex;align-items:center;justify-content:center;font-size:20px;color:#f39c12;flex-shrink:0;}
.stat-card.danger .stat-icon{background:#fdf0ef;color:#e74c3c;}
.stat-card.success .stat-icon{background:#eafaf1;color:#27ae60;}
.stat-card.info .stat-icon{background:#ebf5fb;color:#3498db;}
.stat-num{font-size:30px;font-weight:800;color:#0B3A45;line-height:1;}
.stat-lbl{font-size:12px;color:#888;margin-top:3px;}
.quick-actions{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:15px;margin-bottom:30px;}
.qa-btn{background:white;border:2px solid #e8ecef;border-radius:10px;padding:18px;text-align:center;text-decoration:none;color:#0B3A45;font-weight:700;font-size:14px;transition:0.3s;display:flex;flex-direction:column;align-items:center;gap:8px;}
.qa-btn i{font-size:24px;color:#f39c12;}
.qa-btn:hover{border-color:#f39c12;background:#fef9e7;transform:translateY(-2px);}
.qa-btn.emergency-btn i{color:#e74c3c;}
.qa-btn.emergency-btn:hover{border-color:#e74c3c;background:#fdf0ef;}
</style>

<div class="main-container" id="main-container">

    <?php if ($active_emergency > 0): ?>
    <div style="background:#e74c3c;color:white;padding:14px 20px;border-radius:10px;margin-bottom:20px;display:flex;align-items:center;gap:12px;font-weight:700;">
        <i class="fas fa-exclamation-triangle fa-lg"></i>
        <?= $active_emergency ?> Active Emergency Alert<?= $active_emergency>1?'s':'' ?>!
        <a href="watchman_emergency.php" style="color:white;margin-left:auto;background:rgba(255,255,255,0.2);padding:6px 14px;border-radius:6px;font-size:13px;">View &rarr;</a>
    </div>
    <?php endif; ?>

    <div class="welcome-card">
        <div>
            <h1><?= $greeting ?>, <?= htmlspecialchars($wname) ?> 👋</h1>
            <p>Today is <?= date('l, d M Y') ?> &nbsp;|&nbsp; <?= date('h:i A') ?></p>
        </div>
        <div class="shift-badge"><i class="fas <?= $shift_icon ?>"></i> <?= $wshift ?> Shift</div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div><div class="stat-num"><?= $visitors_today ?></div><div class="stat-lbl">Visitors Today</div></div>
        </div>
        <div class="stat-card info">
            <div class="stat-icon"><i class="fas fa-sign-in-alt"></i></div>
            <div><div class="stat-num"><?= $active_visitors ?></div><div class="stat-lbl">Currently Inside</div></div>
        </div>
        <div class="stat-card" style="border-left-color:#9b59b6;">
            <div class="stat-icon" style="background:#f5eef8;color:#9b59b6;"><i class="fas fa-box"></i></div>
            <div><div class="stat-num"><?= $pending_parcels ?></div><div class="stat-lbl">Pending Parcels</div></div>
        </div>
        <div class="stat-card success">
            <div class="stat-icon"><i class="fas fa-car"></i></div>
            <div><div class="stat-num"><?= $active_parking ?></div><div class="stat-lbl">Parking Today</div></div>
        </div>
        <?php if ($active_emergency > 0): ?>
        <div class="stat-card danger">
            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div><div class="stat-num"><?= $active_emergency ?></div><div class="stat-lbl">Active Emergency</div></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <h3 style="color:#0B3A45;margin-bottom:15px;font-size:18px;">Quick Actions</h3>
    <div class="quick-actions">
        <a href="watchman_visitors.php" class="qa-btn"><i class="fas fa-user-plus"></i>Log Visitor</a>
        <a href="watchman_parcels.php"  class="qa-btn"><i class="fas fa-box"></i>Log Parcel</a>
        <a href="watchman_parking.php"  class="qa-btn"><i class="fas fa-car"></i>View Parking</a>
        <a href="watchman_notices.php"  class="qa-btn"><i class="fas fa-bullhorn"></i>Notices</a>
        <a href="watchman_emergency.php" class="qa-btn emergency-btn"><i class="fas fa-exclamation-triangle"></i>Emergency</a>
    </div>

    <!-- Recent Visitors -->
    <h3 style="color:#0B3A45;margin-bottom:15px;font-size:18px;">Recent Visitors</h3>
    <div class="table-container">
        <table class="content-table">
            <thead><tr><th>No.</th><th>Visitor</th><th>Flat</th><th>Purpose</th><th>Time In</th><th>Status</th></tr></thead>
            <tbody>
            <?php if ($recent_visitors->num_rows > 0):
                $ri = 1;
                while ($rv = $recent_visitors->fetch_assoc()):
                    $status = $rv['time_out'] ? 'Exited' : 'Inside';
                    $sc     = $rv['time_out'] ? 'badge-success' : 'badge-info';
            ?>
                <tr>
                    <td style="color:#888;"><?= $ri++ ?></td>
                    <td><strong><?= htmlspecialchars($rv['visitor_name']) ?></strong></td>
                    <td><?= htmlspecialchars($rv['flat_no']) ?></td>
                    <td><?= htmlspecialchars($rv['purpose'] ?? '-') ?></td>
                    <td><?= date('d/m/Y h:i A', strtotime($rv['time_in'])) ?></td>
                    <td><span class="badge <?= $sc ?>"><?= $status ?></span></td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="6" style="text-align:center;padding:30px;color:#999;">No visitors logged today.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require 'footer.php'; ?>
