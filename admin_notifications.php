<?php
require 'auth_check_admin.php';

// Mark all as read
$conn->prepare("UPDATE notifications SET is_read=1 WHERE username='Admin' AND role='admin'")->execute();

// Fetch all
$notifs = $conn->query(
    "SELECT * FROM notifications WHERE username='Admin' AND role='admin' ORDER BY created_at DESC"
)->fetch_all(MYSQLI_ASSOC);

$page_title = "Admin Notifications";
require 'header_admin.php';
require 'sidebar_admin.php';
?>
<style>
.notif-page-wrap { max-width: 860px; margin: 0 auto; }
.notif-top { display:flex; justify-content:space-between; align-items:center; margin-bottom:22px; flex-wrap:wrap; gap:12px; }
.notif-top h2 { font-size:24px; font-weight:800; color:#0D2137; }
.notif-item {
    background:white; border-radius:12px; padding:16px 20px;
    margin-bottom:10px; display:flex; gap:14px; align-items:flex-start;
    box-shadow:0 2px 8px rgba(13,33,55,0.07);
    border:1px solid #E2EAF4; border-left:4px solid #0EA5C9;
    transition:0.2s;
}
.notif-item:hover { transform:translateX(3px); }
.notif-item.emergency { border-left-color:#DC2626; }
.notif-item.payment   { border-left-color:#059669; }
.notif-item.complaint { border-left-color:#F59E0B; }
.notif-item.booking   { border-left-color:#7C3AED; }
.notif-item.parking   { border-left-color:#0369A1; }
.notif-icon {
    width:40px; height:40px; border-radius:10px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:16px; background:#EFF6FF; color:#0EA5C9;
}
.notif-item.emergency .notif-icon { background:#FEF2F2; color:#DC2626; }
.notif-item.payment   .notif-icon { background:#F0FDF4; color:#059669; }
.notif-item.complaint .notif-icon { background:#FFFBEB; color:#F59E0B; }
.notif-item.booking   .notif-icon { background:#F5F3FF; color:#7C3AED; }
.notif-item.parking   .notif-icon { background:#E0F2FE; color:#0369A1; }
.notif-msg  { font-size:14px; color:#0F172A; line-height:1.5; margin:0 0 4px; }
.notif-time { font-size:12px; color:#94A3B8; }
.clear-btn  { background:#DC2626; color:white; border:none; padding:9px 18px; border-radius:8px; font-weight:700; cursor:pointer; font-size:13px; }
.clear-btn:hover { background:#B91C1C; }
.empty-state { text-align:center; padding:70px 20px; background:white; border-radius:14px; border:1px solid #E2EAF4; }
.empty-state i { font-size:50px; color:#CBD5E1; margin-bottom:16px; display:block; }
.empty-state h3 { color:#94A3B8; font-size:17px; font-weight:700; margin-bottom:8px; }
.empty-state p  { color:#CBD5E1; font-size:13px; }
</style>

<div class="main-container" id="main-container">
<div class="notif-page-wrap">

    <div class="notif-top">
        <h2><i class="fas fa-bell" style="color:#0EA5C9;margin-right:10px;"></i>Admin Notifications</h2>
        <?php if (!empty($notifs)): ?>
            <button class="clear-btn" onclick="deleteAll()">
                <i class="fas fa-trash-alt"></i> Clear All
            </button>
        <?php endif; ?>
    </div>

    <?php if (empty($notifs)): ?>
        <div class="empty-state">
            <i class="fas fa-bell-slash"></i>
            <h3>No notifications yet</h3>
            <p>You'll see notifications here when residents register, submit complaints, make payments, book facilities, or park visitors.</p>
        </div>
    <?php else:
        $icons = [
            'complaint'  => 'clipboard-list',
            'payment'    => 'rupee-sign',
            'booking'    => 'calendar-check',
            'parking'    => 'car',
            'emergency'  => 'exclamation-triangle',
            'notice'     => 'bullhorn',
            'general'    => 'info-circle',
        ];
        foreach ($notifs as $n):
            $diff = time() - strtotime($n['created_at']);
            if      ($diff < 60)    $ago = 'Just now';
            elseif  ($diff < 3600)  $ago = floor($diff/60)  . 'm ago';
            elseif  ($diff < 86400) $ago = floor($diff/3600) . 'h ago';
            else                    $ago = date('d/m/Y H:i', strtotime($n['created_at']));
            $icon = $icons[$n['type']] ?? 'bell';
    ?>
        <div class="notif-item <?= htmlspecialchars($n['type']) ?>">
            <div class="notif-icon"><i class="fas fa-<?= $icon ?>"></i></div>
            <div style="flex:1;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
                    <p class="notif-msg"><?= htmlspecialchars($n['message']) ?></p>
                    <button onclick="deleteNotif(<?= $n['id'] ?>)" title="Delete"
                            style="background:none;border:none;cursor:pointer;color:#CBD5E1;font-size:13px;flex-shrink:0;padding:2px 4px;transition:0.2s;"
                            onmouseover="this.style.color='#DC2626'" onmouseout="this.style.color='#CBD5E1'">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <div style="display:flex;align-items:center;gap:12px;">
                    <span class="notif-time"><i class="far fa-clock" style="margin-right:4px;"></i><?= $ago ?></span>
                    <?php if ($n['link']): ?>
                        <a href="<?= htmlspecialchars($n['link']) ?>" style="font-size:12px;color:#0EA5C9;font-weight:600;text-decoration:none;">View &rarr;</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; endif; ?>

</div>
</div>

<script>
function deleteNotif(id){
    if(!confirm('Delete this notification?')) return;
    fetch('notifications_api.php?action=delete&id='+id+'&role=admin')
    .then(function(){ location.reload(); });
}
function deleteAll(){
    if(!confirm('Clear all admin notifications?')) return;
    fetch('notifications_api.php?action=delete_all&role=admin')
    .then(function(){ location.reload(); });
}
</script>

<?php require 'footer.php'; ?>
