<?php
require 'auth_check.php';
require_once __DIR__ . '/notifications_helper.php';

// Mark all read when page opened
$stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE username=?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();

// Fetch all notifications
$stmt = $conn->prepare("SELECT * FROM notifications WHERE username=? ORDER BY created_at DESC");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$notifs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = "Notifications";
require 'header_user.php';
require 'sidebar_user.php';
?>
<style>
.notif-card { max-width:700px; margin:0 auto; }
.notif-item { background:white; border-radius:10px; padding:18px 22px; margin-bottom:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); display:flex; gap:16px; align-items:flex-start; border-left:4px solid #19B3D3; }
.notif-item.unread { border-left-color:#ff4757; background:#fffbfb; }
.notif-icon { width:40px; height:40px; border-radius:50%; background:#e6f7fa; display:flex; align-items:center; justify-content:center; color:#19B3D3; font-size:16px; flex-shrink:0; }
.notif-item.unread .notif-icon { background:#fff0f0; color:#ff4757; }
.notif-msg  { font-size:14px; color:#333; line-height:1.5; margin:0 0 5px; }
.notif-time { font-size:12px; color:#aaa; }
</style>

<div class="main-container" id="main-container">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;"><h2 style="color:#0B3A45;font-size:28px;font-weight:700;">All Notifications</h2>
        <button onclick="deleteAll()" style="background:#e74c3c;color:white;border:none;padding:9px 18px;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px;"><i class='fas fa-trash-alt'></i> Clear All</button>
    </div>
    <div class="notif-card">
    <?php if (empty($notifs)): ?>
        <div style="text-align:center;padding:60px;color:#999;">
            <i class="fas fa-bell-slash" style="font-size:40px;margin-bottom:15px;display:block;"></i>
            No notifications yet.
        </div>
    <?php else:
        $icons = ['booking'=>'calendar-check','complaint'=>'clipboard-list','payment'=>'wallet','parking'=>'car','notice'=>'bullhorn','general'=>'bell'];
        foreach ($notifs as $n):
            $diff = time() - strtotime($n['created_at']);
            $ago  = $diff < 60 ? 'Just now' : ($diff < 3600 ? floor($diff/60).'m ago' : ($diff < 86400 ? floor($diff/3600).'h ago' : date('d M Y', strtotime($n['created_at']))));
            $icon = $icons[$n['type']] ?? 'bell';
            $cls  = $n['is_read'] ? '' : 'unread';
    ?>
        <div class="notif-item <?= $cls ?>">
            <div class="notif-icon"><i class="fas fa-<?= $icon ?>"></i></div>
            <div>
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
                    <div style="flex:1;">
                        <p class="notif-msg"><?= htmlspecialchars($n['message']) ?></p>
                        <span class="notif-time"><?= $ago ?></span>
                        <?php if ($n['link']): ?>
                            &nbsp;<a href="<?= htmlspecialchars($n['link']) ?>" style="font-size:12px;color:#0B87A6;font-weight:600;">View &rarr;</a>
                        <?php endif; ?>
                    </div>
                    <button onclick="deleteNotif(<?= $n['id'] ?>)" title="Delete"
                            style="background:none;border:none;cursor:pointer;color:#ccc;font-size:14px;padding:2px 4px;flex-shrink:0;transition:0.2s;"
                            onmouseover="this.style.color='#e74c3c'" onmouseout="this.style.color='#ccc'">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    <?php endforeach; endif; ?>
    </div>
</div>


<script>
function deleteNotif(id){
    if(!confirm('Delete this notification?')) return;
    fetch('notifications_api.php?action=delete&id='+id+'&role=user')
    .then(r=>r.json()).then(function(){ location.reload(); });
}
function deleteAll(){
    if(!confirm('Delete ALL notifications? This cannot be undone.')) return;
    fetch('notifications_api.php?action=delete_all&role=user')
    .then(r=>r.json()).then(function(){ location.reload(); });
}
</script>
<?php require 'footer.php'; ?>
