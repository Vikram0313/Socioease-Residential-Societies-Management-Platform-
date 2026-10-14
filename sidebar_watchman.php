<?php
$_cur  = basename($_SERVER['PHP_SELF']);
$_wname = htmlspecialchars($_SESSION['watchman_name'] ?? 'Watchman');
$_wshift = htmlspecialchars($_SESSION['watchman_shift'] ?? '');
?>
<aside class="sidebar" id="sidebar" style="height:calc(100vh - 68px);overflow-y:auto;overflow-x:hidden;">
    <div class="user-panel">
        <img src="Images/watchman.svg" onerror="this.src='Images/download.png'" alt="Watchman">
        <h4><?= $_wname ?></h4>
        <?php if ($_wshift): ?>
        <p style="color:#f39c12;font-size:12px;margin-top:4px;"><i class="fas fa-clock"></i> <?= $_wshift ?> Shift</p>
        <?php endif; ?>
    </div>
    <nav class="nav-links" style="padding-bottom:20px;">
        <a href="watchman_dashboard.php"    <?= $_cur==='watchman_dashboard.php'    ?'class="active"':'' ?>><i class="fas fa-tachometer-alt"></i>  Dashboard</a>
        <a href="watchman_visitors.php"     <?= $_cur==='watchman_visitors.php'     ?'class="active"':'' ?>><i class="fas fa-users"></i>            Visitor Log</a>
        <a href="watchman_parcels.php"      <?= $_cur==='watchman_parcels.php'      ?'class="active"':'' ?>><i class="fas fa-box"></i>              Parcel Log</a>
        <a href="watchman_parking.php"      <?= $_cur==='watchman_parking.php'      ?'class="active"':'' ?>><i class="fas fa-car"></i>              Parking View</a>
        <a href="watchman_attendance.php"   <?= $_cur==='watchman_attendance.php'   ?'class="active"':'' ?>><i class="fas fa-clipboard-check"></i>    Staff Attendance</a>
        <a href="watchman_emergency.php"    <?= $_cur==='watchman_emergency.php'    ?'class="active"':'' ?>><i class="fas fa-exclamation-triangle"></i> Emergency</a>
        <a href="watchman_notices.php"      <?= $_cur==='watchman_notices.php'      ?'class="active"':'' ?>><i class="fas fa-bullhorn"></i>          Notices</a>
        <a href="watchman_notifications.php"<?= $_cur==='watchman_notifications.php'?'class="active"':'' ?>><i class="fas fa-bell"></i>             Notifications</a>
        <a href="watchman_change_password.php"<?= $_cur==='watchman_change_password.php'?'class="active"':'' ?>><i class="fas fa-key"></i>          Change Password</a>
    </nav>
</aside>
