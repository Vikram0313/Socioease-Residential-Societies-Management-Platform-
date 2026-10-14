<?php
require 'auth_check_admin.php';

// Stats
$total_members      = $conn->query("SELECT COUNT(*) FROM registration")->fetch_row()[0] ?? 0;
$pending_complaints = $conn->query("SELECT COUNT(*) FROM combox WHERE status='Pending'")->fetch_row()[0] ?? 0;
$inprogress_complaints = $conn->query("SELECT COUNT(*) FROM combox WHERE status='In Progress'")->fetch_row()[0] ?? 0;
$pending_bookings   = $conn->query("SELECT COUNT(*) FROM facility_bookings WHERE LOWER(status)='pending'")->fetch_row()[0] ?? 0;
$total_notices      = $conn->query("SELECT COUNT(*) FROM notices")->fetch_row()[0] ?? 0;
$total_staff        = $conn->query("SELECT COUNT(*) FROM staff_members")->fetch_row()[0] ?? 0;
$total_watchmen     = $conn->query("SELECT COUNT(*) FROM watchmen WHERE is_active=1")->fetch_row()[0] ?? 0;
$total_revenue      = $conn->query("SELECT COALESCE(SUM(Amount),0) FROM payrecords WHERE Status='Success'")->fetch_row()[0] ?? 0;
$today              = date('Y-m-d');
$visitors_today     = $conn->query("SELECT COUNT(*) FROM visitor_log WHERE DATE(time_in) = CURDATE()")->fetch_row()[0] ?? 0;
$pending_parcels    = $conn->query("SELECT COUNT(*) FROM parcel_log WHERE status='Pending'")->fetch_row()[0] ?? 0;
$active_emergency   = $conn->query("SELECT COUNT(*) FROM emergency_log WHERE status='Active'")->fetch_row()[0] ?? 0;
$parking_today      = $conn->query("SELECT COUNT(*) FROM visitor_parking WHERE start_date = CURDATE()")->fetch_row()[0] ?? 0;

// Recent data
$recent_complaints = $conn->query("SELECT * FROM combox ORDER BY ID DESC LIMIT 5");
$recent_notices    = $conn->query("SELECT * FROM notices ORDER BY ID DESC LIMIT 4");
$recent_payments   = $conn->query("SELECT * FROM payrecords WHERE Status='Success' ORDER BY ID DESC LIMIT 5");

$page_title = "Admin Dashboard";
require 'header_admin.php';
require 'sidebar_admin.php';
?>
<style>
/* Stats */
.stats-grid { display:grid; grid-template-columns:repeat(6,1fr); gap:18px; margin-bottom:28px; }
@media(max-width:1400px){ .stats-grid{ grid-template-columns:repeat(3,1fr); } }
@media(max-width:768px){  .stats-grid{ grid-template-columns:repeat(2,1fr); } }
.stat-card {
    background:white; padding:22px 20px; border-radius:14px;
    box-shadow:0 2px 12px rgba(13,33,55,0.07);
    display:flex; align-items:center; gap:16px;
    border:1px solid #E2EAF4;
    border-left:5px solid #0EA5C9;
    transition:0.25s; cursor:default;
}
.stat-card:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(13,33,55,0.12); }
.stat-card.danger  { border-left-color:#DC2626; }
.stat-card.warning { border-left-color:#F59E0B; }
.stat-card.success { border-left-color:#059669; }
.stat-card.purple  { border-left-color:#7C3AED; }
.stat-card.amber   { border-left-color:#D97706; }
.stat-card.rose    { border-left-color:#E11D48; }
.stat-icon {
    width:52px; height:52px; border-radius:12px;
    background:#EFF6FF; display:flex; align-items:center;
    justify-content:center; font-size:20px; color:#0EA5C9; flex-shrink:0;
}
.stat-card.danger  .stat-icon { background:#FEF2F2; color:#DC2626; }
.stat-card.warning .stat-icon { background:#FFFBEB; color:#F59E0B; }
.stat-card.success .stat-icon { background:#F0FDF4; color:#059669; }
.stat-card.purple  .stat-icon { background:#F5F3FF; color:#7C3AED; }
.stat-card.amber   .stat-icon { background:#FFFBEB; color:#D97706; }
.stat-card.rose    .stat-icon { background:#FFF1F2; color:#E11D48; }
.stat-number { font-size:30px; font-weight:800; color:#0D2137; line-height:1; }
.stat-label  { font-size:12px; color:#94A3B8; margin-top:4px; font-weight:600; letter-spacing:0.2px; }
.stat-trend  { font-size:11px; margin-top:3px; font-weight:700; }
.trend-up   { color:#059669; }
.trend-warn { color:#F59E0B; }

/* Emergency banner */
.emergency-banner {
    background:linear-gradient(135deg,#DC2626,#B91C1C);
    color:white; padding:16px 22px; border-radius:12px;
    margin-bottom:22px; display:flex; align-items:center;
    gap:14px; animation:pulse 2s infinite;
}
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.85} }

/* Section title */
.section-title { font-size:17px; font-weight:800; color:#0D2137; margin-bottom:14px; display:flex; align-items:center; gap:10px; }

/* Quick actions */
.quick-actions { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px; margin-bottom:28px; }
.qa-btn {
    background:white; border:1.5px solid #E2EAF4;
    border-radius:12px; padding:16px 12px; text-align:center;
    text-decoration:none; color:#0D2137; font-weight:700; font-size:13px;
    transition:0.2s; display:flex; flex-direction:column; align-items:center; gap:8px;
    box-shadow:0 1px 6px rgba(13,33,55,0.05);
}
.qa-btn i { font-size:20px; color:#0EA5C9; }
.qa-btn:hover { border-color:#0EA5C9; background:#EFF6FF; transform:translateY(-2px); }

/* Two col layout */
.two-col { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
.three-col { display:grid; grid-template-columns:1fr 1fr 1fr; gap:20px; margin-bottom:20px; }

/* Mini table cards */
.dash-card { background:white; border-radius:14px; box-shadow:0 2px 12px rgba(13,33,55,0.07); overflow:hidden; border:1px solid #E2EAF4; }
.dash-card-header { padding:16px 20px; background:#0D2137; color:white; display:flex; justify-content:space-between; align-items:center; }
.dash-card-header h3 { font-size:15px; font-weight:700; }
.dash-card-header a { font-size:12px; color:#0EA5C9; text-decoration:none; font-weight:600; }
.dash-mini-table { width:100%; border-collapse:collapse; }
.dash-mini-table td { padding:12px 18px; font-size:13px; border-bottom:1px solid #F1F5F9; }
.dash-mini-table tr:last-child td { border-bottom:none; }
.dash-mini-table tr:hover td { background:#F8FAFF; }

/* Revenue card */
.revenue-card {
    background:linear-gradient(135deg,#0D2137,#0369A1);
    border-radius:14px; padding:28px; color:white;
    display:flex; flex-direction:column; justify-content:space-between;
}
.revenue-card h3 { font-size:14px; opacity:0.7; margin-bottom:8px; font-weight:600; }
.revenue-amount { font-size:36px; font-weight:800; }
.revenue-sub { font-size:13px; opacity:0.6; margin-top:6px; }

/* Today card */
.today-card { background:linear-gradient(135deg,#059669,#047857); border-radius:14px; padding:28px; color:white; }
.today-card h3 { font-size:14px; opacity:0.7; margin-bottom:14px; font-weight:600; }
.today-stat { display:flex; justify-content:space-between; margin-bottom:10px; font-size:14px; }
.today-stat span:last-child { font-weight:800; font-size:16px; }

@media(max-width:1024px){ .two-col,.three-col{grid-template-columns:1fr;} }
@media(max-width:700px){ .stats-grid{grid-template-columns:1fr 1fr;} }
</style>

<div class="main-container" id="main-container">

    <?php if ($active_emergency > 0): ?>
    <div class="emergency-banner">
        <i class="fas fa-exclamation-triangle fa-lg"></i>
        <div>
            <strong><?= $active_emergency ?> Active Emergency Alert<?= $active_emergency>1?'s':'' ?>!</strong>
            <div style="font-size:13px;opacity:0.85;">Raised by security staff — requires immediate attention.</div>
        </div>
        <a href="watchman_emergency.php" style="margin-left:auto;background:rgba(255,255,255,0.2);color:white;padding:8px 16px;border-radius:8px;text-decoration:none;font-weight:700;font-size:13px;">View &rarr;</a>
    </div>
    <?php endif; ?>

    <h2 class="page-title">Admin Dashboard</h2>

    <!-- STATS GRID — 6 cards uniform -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div>
                <div class="stat-number"><?= $total_members ?></div>
                <div class="stat-label">Total Members</div>
            </div>
        </div>
        <div class="stat-card danger">
            <div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div>
            <div>
                <div class="stat-number"><?= $pending_complaints ?></div>
                <div class="stat-label">Pending Complaints</div>
                <?php if ($inprogress_complaints > 0): ?>
                <div class="stat-trend trend-warn"><?= $inprogress_complaints ?> In Progress</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="stat-card warning">
            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
            <div>
                <div class="stat-number"><?= $pending_bookings ?></div>
                <div class="stat-label">Pending Bookings</div>
            </div>
        </div>
        <div class="stat-card success">
            <div class="stat-icon"><i class="fas fa-bullhorn"></i></div>
            <div>
                <div class="stat-number"><?= $total_notices ?></div>
                <div class="stat-label">Notices Posted</div>
            </div>
        </div>
        <div class="stat-card purple">
            <div class="stat-icon"><i class="fas fa-users-cog"></i></div>
            <div>
                <div class="stat-number"><?= $total_staff + $total_watchmen ?></div>
                <div class="stat-label">Staff &amp; Watchmen</div>
            </div>
        </div>
        <div class="stat-card amber">
            <div class="stat-icon"><i class="fas fa-car-side"></i></div>
            <div>
                <div class="stat-number"><?= $parking_today ?></div>
                <div class="stat-label">Parking Today</div>
            </div>
        </div>
    </div>

    <!-- REVENUE + TODAY OVERVIEW -->
    <div class="two-col" style="margin-bottom:28px;">
        <div class="revenue-card">
            <div>
                <h3><i class="fas fa-rupee-sign"></i> Total Revenue Collected</h3>
                <div class="revenue-amount">₹<?= number_format($total_revenue, 2) ?></div>
                <div class="revenue-sub">Via Razorpay — auto verified payments</div>
            </div>
            <div style="margin-top:20px;display:flex;gap:12px;">
                <a href="viewpayment.php" style="background:rgba(255,255,255,0.15);color:white;padding:9px 16px;border-radius:8px;text-decoration:none;font-weight:700;font-size:13px;">View Ledger &rarr;</a>
            </div>
        </div>
        <div class="today-card">
            <h3><i class="fas fa-calendar-day"></i> Today's Activity — <?= date('d/m/Y') ?></h3>
            <div class="today-stat"><span>Visitors Logged</span><span><?= $visitors_today ?></span></div>
            <div class="today-stat"><span>Parking Bookings</span><span><?= $parking_today ?></span></div>
            <div class="today-stat"><span>Pending Parcels</span><span><?= $pending_parcels ?></span></div>
            <?php if ($active_emergency > 0): ?>
            <div class="today-stat" style="color:#ff9f9f;"><span>🚨 Active Emergencies</span><span><?= $active_emergency ?></span></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="section-title"><i class="fas fa-bolt" style="color:#0EA5C9;"></i> Quick Actions</div>
    <div class="quick-actions">
        <a href="addnotice.php"               class="qa-btn"><i class="fas fa-bullhorn"></i>Add Notice</a>
        <a href="viewcomplaints.php"           class="qa-btn"><i class="fas fa-clipboard-list"></i>Complaints</a>
        <a href="viewpayment.php"              class="qa-btn"><i class="fas fa-file-invoice-dollar"></i>Payments</a>
        <a href="facility_admin.php"           class="qa-btn"><i class="fas fa-user-shield"></i>Bookings</a>
        <a href="visitorparking_admin.php"     class="qa-btn"><i class="fas fa-car-side"></i>Parking</a>
        <a href="managemem.php"                class="qa-btn"><i class="fas fa-users"></i>Members</a>
        <a href="managestaff.php"              class="qa-btn"><i class="fas fa-users-cog"></i>Staff</a>
        <a href="manage_watchmen.php"          class="qa-btn"><i class="fas fa-hard-hat"></i>Watchmen</a>
        <a href="staff_attendance_report.php"  class="qa-btn"><i class="fas fa-calendar-check"></i>Attendance</a>
        <a href="society_details.php"          class="qa-btn"><i class="fas fa-building"></i>Society</a>
    </div>

    <!-- RECENT COMPLAINTS + PAYMENTS -->
    <div class="two-col">
        <div class="dash-card">
            <div class="dash-card-header">
                <h3><i class="fas fa-clipboard-list"></i> Recent Complaints</h3>
                <a href="viewcomplaints.php">View All &rarr;</a>
            </div>
            <table class="dash-mini-table">
            <?php if ($recent_complaints->num_rows === 0): ?>
                <tr><td style="text-align:center;color:#94A3B8;padding:20px;">No complaints yet.</td></tr>
            <?php else: while ($c = $recent_complaints->fetch_assoc()):
                $sc = ['Pending'=>'badge-pending','Resolved'=>'badge-success','In Progress'=>'badge-info','Rejected'=>'badge-danger'][$c['status']] ?? 'badge-pending';
            ?>
                <tr>
                    <td>
                        <strong style="color:#0D2137;font-size:13px;"><?= htmlspecialchars($c['Title']) ?></strong>
                        <div style="font-size:11px;color:#94A3B8;margin-top:2px;"><?= htmlspecialchars($c['username'] ?? '') ?></div>
                    </td>
                    <td style="text-align:right;"><span class="badge <?= $sc ?>"><?= $c['status'] ?></span></td>
                </tr>
            <?php endwhile; endif; ?>
            </table>
        </div>

        <div class="dash-card">
            <div class="dash-card-header">
                <h3><i class="fas fa-check-circle"></i> Recent Payments</h3>
                <a href="viewpayment.php">View All &rarr;</a>
            </div>
            <table class="dash-mini-table">
            <?php if ($recent_payments->num_rows === 0): ?>
                <tr><td style="text-align:center;color:#94A3B8;padding:20px;">No payments yet.</td></tr>
            <?php else: while ($p = $recent_payments->fetch_assoc()): ?>
                <tr>
                    <td>
                        <strong style="color:#0D2137;font-size:13px;"><?= htmlspecialchars($p['Name']) ?></strong>
                        <div style="font-size:11px;color:#94A3B8;">Flat <?= $p['Flatno'] ?> &nbsp;·&nbsp; <?= $p['month_name'] ?></div>
                    </td>
                    <td style="text-align:right;font-weight:700;color:#059669;">₹<?= number_format($p['Amount'],2) ?></td>
                </tr>
            <?php endwhile; endif; ?>
            </table>
        </div>
    </div>

    <!-- RECENT NOTICES -->
    <div class="dash-card">
        <div class="dash-card-header">
            <h3><i class="fas fa-bullhorn"></i> Recent Notices</h3>
            <a href="addnotice.php">Manage &rarr;</a>
        </div>
        <table class="dash-mini-table">
        <?php if ($recent_notices->num_rows === 0): ?>
            <tr><td style="text-align:center;color:#94A3B8;padding:20px;">No notices posted yet.</td></tr>
        <?php else: while ($n = $recent_notices->fetch_assoc()): ?>
            <tr>
                <td>
                    <strong style="color:#0D2137;font-size:13px;"><?= htmlspecialchars($n['Name']) ?></strong>
                    <div style="font-size:11px;color:#94A3B8;margin-top:2px;"><?= date('d/m/Y', strtotime($n['Noticedate'])) ?></div>
                </td>
                <td><span class="badge badge-info"><?= htmlspecialchars($n['Type']) ?></span></td>
                <td style="color:#64748B;font-size:13px;max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?= htmlspecialchars($n['Message']) ?>
                </td>
            </tr>
        <?php endwhile; endif; ?>
        </table>
    </div>

</div>
<?php require 'footer.php'; ?>
