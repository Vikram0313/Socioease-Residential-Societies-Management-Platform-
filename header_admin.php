<?php
require_once __DIR__ . '/notifications_helper.php';
$_unread_a = isset($conn) ? get_unread_count($conn, 'Admin', 'admin') : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Admin') ?> | Socioease Admin</title>
    <script src="https://kit.fontawesome.com/2edfbc5391.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="portal.css">
    <?= $extra_head ?? '' ?>
</head>
<body>
<header>
    <div class="header-left">
        <h2>Socioease <span>Admin</span></h2>
        <i class="fas fa-bars" id="menu-toggle"></i>
    </div>
    <div class="header-right" style="display:flex;align-items:center;gap:20px;">
        <!-- Admin Notification Bell -->
        <div class="notif-wrap" style="position:relative;">
            <button onclick="toggleNotifPanel()" id="notif-btn"
                    style="background:none;border:none;cursor:pointer;color:white;font-size:20px;position:relative;padding:5px;">
                <i class="fas fa-bell"></i>
                <?php if ($_unread_a > 0): ?>
                    <span id="notif-badge"
                          style="position:absolute;top:-4px;right:-4px;background:#ff4757;color:white;border-radius:50%;width:18px;height:18px;font-size:10px;display:flex;align-items:center;justify-content:center;font-weight:700;">
                        <?= $_unread_a > 9 ? '9+' : $_unread_a ?>
                    </span>
                <?php endif; ?>
            </button>
            <div id="notif-panel"
                 style="display:none;position:absolute;right:0;top:45px;width:340px;background:white;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,0.15);z-index:9999;overflow:hidden;">
                <div style="background:#0B3A45;padding:14px 18px;display:flex;justify-content:space-between;align-items:center;">
                    <span style="color:white;font-weight:700;font-size:14px;">Admin Notifications</span>
                    <button onclick="markAllRead()" style="background:none;border:none;color:#19B3D3;font-size:12px;cursor:pointer;font-weight:600;">Mark all read</button>
                </div>
                <div id="notif-list" style="max-height:360px;overflow-y:auto;">
                    <p style="text-align:center;padding:20px;color:#999;font-size:13px;">Loading...</p>
                </div>
                <div style="border-top:1px solid #eee;padding:10px;text-align:center;">
                    <a href="admin_notifications.php" style="color:#0B87A6;font-size:13px;font-weight:600;text-decoration:none;">View All Notifications</a>
                </div>
            </div>
        </div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</header>
<script>
function toggleNotifPanel(){
    var p=document.getElementById('notif-panel');
    if(p.style.display==='none'){p.style.display='block';loadNotifications();}
    else p.style.display='none';
}
function loadNotifications(){
    fetch('notifications_api.php?action=list&limit=10&role=admin')
    .then(r=>r.json()).then(function(data){
        var list=document.getElementById('notif-list');
        if(!data.length){list.innerHTML='<p style="text-align:center;padding:20px;color:#999;font-size:13px;">No notifications yet.</p>';return;}
        list.innerHTML=data.map(function(n){
            var bg=n.is_read=='1'?'white':'#f0f8ff';
            var dot=n.is_read=='1'?'':'<span style="width:8px;height:8px;background:#19B3D3;border-radius:50%;flex-shrink:0;margin-top:4px;"></span>';
            var icons={booking:'calendar-check',complaint:'clipboard-list',payment:'wallet',parking:'car',notice:'bullhorn',emergency:'exclamation-triangle',general:'bell'};
            var icon=icons[n.type]||'bell';
            return '<div onclick="openNotif('+n.id+',\''+encodeURIComponent(n.link||'')+'\')" style="padding:13px 16px;border-bottom:1px solid #f1f1f1;cursor:pointer;background:'+bg+';display:flex;gap:10px;align-items:flex-start;">'+dot+'<div style="flex:1;"><p style="font-size:13px;color:#333;margin:0;line-height:1.4;">'+n.message+'</p><span style="font-size:11px;color:#aaa;">'+n.time_ago+'</span></div></div>';
        }).join('');
    });
}
function openNotif(id,link){
    fetch('notifications_api.php?action=read&id='+id+'&role=admin');
    var badge=document.getElementById('notif-badge');
    if(badge){var c=parseInt(badge.innerText)-1;if(c<=0)badge.remove();else badge.innerText=c>9?'9+':c;}
    var url=decodeURIComponent(link);
    if(url&&url!=='null'&&url!=='')window.location.href=url;
    document.getElementById('notif-panel').style.display='none';
}
function markAllRead(){
    fetch('notifications_api.php?action=read_all&role=admin').then(function(){
        var badge=document.getElementById('notif-badge');
        if(badge)badge.remove();
        loadNotifications();
    });
}
document.addEventListener('click',function(e){
    var wrap=document.querySelector('.notif-wrap');
    if(wrap&&!wrap.contains(e.target))document.getElementById('notif-panel').style.display='none';
});
</script>
