<?php
require 'auth_check.php';

$query_run = $conn->query("SELECT * FROM notices ORDER BY ID DESC");

$page_title = "Notice Board";
require 'header_user.php';
require 'sidebar_user.php';
?>
<style>
.page-header { background:white; padding:30px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.05); margin-bottom:30px; border-left:6px solid #0EA5C9; }
.notice-type { background:#e6f7fa; color:#0284A8; padding:5px 12px; border-radius:20px; font-size:11px; font-weight:700; text-transform:uppercase; }
.new-badge { background:#ff4757; color:white; padding:2px 8px; border-radius:4px; font-size:10px; margin-left:8px; vertical-align:middle; }
.msg-cell { max-width:450px; color:#555; line-height:1.5; word-wrap:break-word; }
</style>

<div class="main-container" id="main-container">
    <div class="page-header">
        <h2><i class="fas fa-bullhorn" style="color:#0EA5C9;margin-right:15px;"></i>Society Notices</h2>
    </div>
    <div class="table-container">
        <table class="content-table">
            <thead>
                <tr><th>ID</th><th>Notice Title</th><th>Category</th><th>Date Posted</th><th>Description</th></tr>
            </thead>
            <tbody>
            <?php
            if ($query_run && $query_run->num_rows > 0):
                while ($row = $query_run->fetch_assoc()):
                    // Use created_at if available, otherwise Noticedate
                    $ts = strtotime($row['created_at'] ?? $row['Noticedate']);
                    $is_new = (time() - $ts) < 86400;
            ?>
                <tr>
                    <td style="color:#aaa;font-weight:bold;"><?= $row['ID'] ?></td>
                    <td>
                        <span style="font-weight:600;color:#2c3e50;"><?= htmlspecialchars($row['Name']) ?></span>
                        <?php if ($is_new): ?><span class="new-badge">NEW</span><?php endif; ?>
                    </td>
                    <td><span class="notice-type"><?= htmlspecialchars($row['Type']) ?></span></td>
                    <td style="white-space:nowrap;color:#7f8c8d;"><i class="far fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($row['Noticedate'])) ?></td>
                    <td class="msg-cell"><?= nl2br(htmlspecialchars($row['Message'])) ?></td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="5" style="text-align:center;padding:60px;color:#95a5a6;font-style:italic;">No notices published yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require 'footer.php'; ?>
