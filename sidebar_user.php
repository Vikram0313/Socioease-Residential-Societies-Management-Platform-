<?php
$_cur = basename($_SERVER['PHP_SELF']);
$_uname = htmlspecialchars($_SESSION['username'] ?? 'User');
// Unread count already fetched in header_user.php as $_unread
$_unread_badge = (isset($_unread) && $_unread > 0)
    ? '<span style="background:#ff4757;color:white;border-radius:10px;padding:1px 7px;font-size:10px;font-weight:700;margin-left:auto;">' . ($_unread > 9 ? '9+' : $_unread) . '</span>'
    : '';
?>
<aside class="sidebar" id="sidebar" style="height:calc(100vh - 68px);overflow-y:auto;overflow-x:hidden;">
    <div class="user-panel">
        <img src="Images/download.png" alt="Profile">
        <h4><?= $_uname ?></h4>
    </div>
    <nav class="nav-links" style="padding-bottom:20px;">
        <a href="Welcome.php"         <?= $_cur==='Welcome.php'         ?'class="active"':'' ?>><i class="fas fa-th-large"></i>       Dashboard</a>
        <a href="noticebrd.php"       <?= $_cur==='noticebrd.php'       ?'class="active"':'' ?>><i class="fas fa-bullhorn"></i>        Notice Board</a>
        <a href="complaint.php"       <?= $_cur==='complaint.php'       ?'class="active"':'' ?>><i class="fas fa-clipboard-list"></i>  Complaints</a>
        <a href="payment.php"         <?= $_cur==='payment.php'         ?'class="active"':'' ?>><i class="fas fa-wallet"></i>          Payments</a>
        <a href="visitorparking.php"  <?= $_cur==='visitorparking.php'  ?'class="active"':'' ?>><i class="fas fa-car"></i>             Parking Area</a>
        <a href="facilities.php"      <?= $_cur==='facilities.php'      ?'class="active"':'' ?>><i class="fas fa-dumbbell"></i>        Facilities</a>
        <a href="notifications.php"   <?= $_cur==='notifications.php'   ?'class="active"':'' ?>
           style="display:flex;align-items:center;justify-content:space-between;">
            <span><i class="fas fa-bell"></i> Notifications</span>
            <?= $_unread_badge ?>
        </a>
        <a href="change_password.php" <?= $_cur==='change_password.php' ?'class="active"':'' ?>><i class="fas fa-key"></i>            Change Password</a>
    </nav>
</aside>
