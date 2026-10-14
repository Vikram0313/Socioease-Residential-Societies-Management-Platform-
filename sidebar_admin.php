<?php
$_cur = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar" style="height:calc(100vh - 68px);overflow-y:auto;overflow-x:hidden;">
    <div class="user-panel">
        <img src="Images/download.png" alt="Profile">
        <h4>Administrator</h4>
    </div>
    <nav class="nav-links" style="padding-bottom:20px;">
        <a href="admin_dashboard.php"      <?= $_cur==='admin_dashboard.php'      ?'class="active"':'' ?>><i class="fas fa-tachometer-alt"></i>    Dashboard</a>
        <a href="managemem.php"            <?= $_cur==='managemem.php'            ?'class="active"':'' ?>><i class="fas fa-users"></i>              Manage Members</a>
        <a href="managestaff.php"          <?= $_cur==='managestaff.php'          ?'class="active"':'' ?>><i class="fas fa-users-cog"></i>          Manage Staff</a>
        <a href="manage_watchmen.php"      <?= $_cur==='manage_watchmen.php'      ?'class="active"':'' ?>><i class="fas fa-hard-hat"></i>           Manage Watchmen</a>
        <a href="addnotice.php"            <?= $_cur==='addnotice.php'            ?'class="active"':'' ?>><i class="fas fa-bullhorn"></i>            Add Notice</a>
        <a href="viewcomplaints.php"       <?= $_cur==='viewcomplaints.php'       ?'class="active"':'' ?>><i class="fas fa-envelope-open-text"></i>  View Complaints</a>
        <a href="viewpayment.php"          <?= $_cur==='viewpayment.php'          ?'class="active"':'' ?>><i class="fas fa-file-invoice-dollar"></i> View Payments</a>
        <a href="facility_admin.php"       <?= $_cur==='facility_admin.php'       ?'class="active"':'' ?>><i class="fas fa-user-shield"></i>         Facility Admin</a>
        <a href="visitorparking_admin.php" <?= $_cur==='visitorparking_admin.php' ?'class="active"':'' ?>><i class="fas fa-car-side"></i>            Visitor Parking</a>
        <a href="society_details.php"      <?= $_cur==='society_details.php'      ?'class="active"':'' ?>><i class="fas fa-building"></i>           Society Details</a>
        <a href="staff_attendance_report.php" <?= $_cur==='staff_attendance_report.php'?'class="active"':'' ?>><i class="fas fa-calendar-check"></i>   Attendance Report</a>
        <a href="admin_notifications.php"  <?= $_cur==='admin_notifications.php'  ?'class="active"':'' ?>><i class="fas fa-bell"></i>               Notifications</a>
    </nav>
</aside>
