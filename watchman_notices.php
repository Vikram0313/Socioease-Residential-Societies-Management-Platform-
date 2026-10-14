<?php
require 'auth_check_watchman.php';
$notices = $conn->query("SELECT * FROM notices ORDER BY ID DESC");
$page_title = "Notices";
require 'header_watchman.php';
require 'sidebar_watchman.php';
?>
<style>
.notice-card{background:white;border-radius:10px;padding:22px 25px;margin-bottom:16px;box-shadow:0 2px 10px rgba(0,0,0,0.06);border-left:5px solid #f39c12;transition:0.3s;}
.notice-card:hover{transform:translateX(4px);}
.notice-title{font-size:17px;font-weight:700;color:#0B3A45;margin-bottom:6px;}
.notice-meta{display:flex;gap:15px;font-size:12px;color:#aaa;margin-bottom:10px;flex-wrap:wrap;}
.notice-msg{font-size:14px;color:#555;line-height:1.6;}
.notice-type{background:#fef3cd;color:#856404;padding:3px 10px;border-radius:10px;font-size:11px;font-weight:700;}
.new-badge{background:#e74c3c;color:white;padding:2px 8px;border-radius:4px;font-size:10px;margin-left:8px;}
</style>

<div class="main-container" id="main-container">
    <h2 class="page-title">Society Notices</h2>
    <p style="text-align:center;color:#888;margin-bottom:25px;font-size:14px;"><i class="fas fa-info-circle"></i> Read-only view — contact admin to post notices.</p>

    <?php if ($notices->num_rows > 0):
        while ($n = $notices->fetch_assoc()):
            $ts  = strtotime($n['created_at'] ?? $n['Noticedate']);
            $new = (time() - $ts) < 86400;
    ?>
        <div class="notice-card">
            <div class="notice-title">
                <?= htmlspecialchars($n['Name']) ?>
                <?php if ($new): ?><span class="new-badge">NEW</span><?php endif; ?>
            </div>
            <div class="notice-meta">
                <span><i class="fas fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($n['Noticedate'])) ?></span>
                <span class="notice-type"><?= htmlspecialchars($n['Type']) ?></span>
            </div>
            <div class="notice-msg"><?= nl2br(htmlspecialchars($n['Message'])) ?></div>
        </div>
    <?php endwhile; else: ?>
        <div style="text-align:center;padding:60px;color:#999;background:white;border-radius:12px;">
            <i class="fas fa-bullhorn" style="font-size:40px;margin-bottom:15px;display:block;opacity:0.3;"></i>
            No notices posted yet.
        </div>
    <?php endif; ?>
</div>

<?php require 'footer.php'; ?>
