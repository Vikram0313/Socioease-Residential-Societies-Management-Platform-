<?php
require 'auth_check_admin.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/notifications_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notice'])) {
    csrf_verify();
    $name    = trim($_POST['Name']       ?? '');
    $type    = trim($_POST['Type']       ?? '');
    $date    = trim($_POST['Noticedate'] ?? '');
    $message = trim($_POST['Message']    ?? '');

    $stmt = $conn->prepare("INSERT INTO notices (Name, Type, Noticedate, Message) VALUES (?,?,?,?)");
    $stmt->bind_param("ssss", $name, $type, $date, $message);
    $stmt->execute();

    // --- Notify ALL registered & verified residents ---
    $residents = $conn->query(
        "SELECT Username, Email FROM registration WHERE is_verified = 1"
    );

    while ($r = $residents->fetch_assoc()) {
        // In-app notification
        push_notification(
            $conn,
            $r['Username'],
            "New notice posted: \"$name\" ($type) — $date",
            'notice',
            'noticebrd.php'
        );

        // Email
        if (!empty($r['Email'])) {
            $body = email_template("New Society Notice", "
                <p>Hi <strong>" . htmlspecialchars($r['Username']) . "</strong>,</p>
                <p>A new notice has been posted by the society administration.</p>
                <div class='info-box'>
                    <p><strong>Title:</strong> " . htmlspecialchars($name) . "</p>
                    <p><strong>Category:</strong> " . htmlspecialchars($type) . "</p>
                    <p><strong>Date:</strong> $date</p>
                    <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>
                </div>
                <div style='text-align:center;margin:25px 0;'>
                    <a href='" . SITE_URL . "/noticebrd.php' class='btn'>View Notice Board</a>
                </div>
            ");
            send_email($r['Email'], $r['Username'], "New Notice: " . htmlspecialchars($name) . " - Socioease", $body);
        }
    }

    echo "<script>alert('Notice Created and residents notified!');window.location='addnotice.php';</script>";
    exit;
}

$page_title = "Add Notice";
$extra_head = '
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<style>
.flatpickr-calendar { background:#fff; border-radius:12px !important; box-shadow:0 8px 30px rgba(0,0,0,0.15) !important; border:none !important; font-family:"Segoe UI",Tahoma,sans-serif; }
.flatpickr-months { background:#0B3A45; border-radius:12px 12px 0 0; padding:6px 0; }
.flatpickr-months .flatpickr-month { color:white; fill:white; }
.flatpickr-current-month span.cur-month, .flatpickr-current-month input.cur-year { color:white !important; font-weight:700; }
.flatpickr-months .flatpickr-prev-month svg, .flatpickr-months .flatpickr-next-month svg { fill:white !important; }
.flatpickr-months .flatpickr-prev-month:hover, .flatpickr-months .flatpickr-next-month:hover { background:rgba(255,255,255,0.1) !important; }
.flatpickr-weekdays { background:#0B3A45; }
span.flatpickr-weekday { background:#0B3A45; color:#19B3D3 !important; font-weight:700; }
.flatpickr-day { border-radius:8px !important; color:#333; }
.flatpickr-day:hover { background:#e6f7fa !important; border-color:#19B3D3 !important; }
.flatpickr-day.selected, .flatpickr-day.selected:hover { background:#19B3D3 !important; border-color:#19B3D3 !important; color:white !important; }
.flatpickr-day.today { border-color:#19B3D3 !important; color:#0B3A45 !important; font-weight:700; }
.flatpickr-day.today.selected { color:white !important; }
.notice-form-card { max-width:800px; margin:0 auto 40px; padding:35px; border-left:6px solid #19B3D3; }
.notice-form-card h2 { text-align:center; color:#0B3A45; font-size:26px; margin-bottom:25px; }
.date-wrapper { position:relative; }
.date-wrapper i { position:absolute; right:12px; top:50%; transform:translateY(-50%); color:#19B3D3; pointer-events:none; }
</style>';

require 'header_admin.php';
require 'sidebar_admin.php';
?>

<div class="main-container" id="main-container">
    <div class="card-box notice-form-card">
        <h2>Create Notice</h2>
        <form action="addnotice.php" method="POST">
            <?= csrf_field() ?>
            <input type="text" name="Name"    class="field" placeholder="Notice Title"                         required maxlength="100">
            <input type="text" name="Type"    class="field" placeholder="Notice Type (Event, Rules, Meeting…)" required maxlength="20">
            <div class="date-wrapper">
                <input type="text" id="noticedate" name="Noticedate" class="field"
                       placeholder="Select Date" readonly required style="cursor:pointer;">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <textarea name="Message" class="field" placeholder="Enter Notice Message"
                      rows="4" required maxlength="500"></textarea>
            <button class="btn btn-full" name="send_notice" style="padding:14px;font-size:18px;">
                <i class="fas fa-paper-plane"></i> Submit &amp; Notify All Residents
            </button>
        </form>
    </div>

    <h2 class="page-title" style="font-size:24px;">Previous Notices</h2>
    <div class="table-container">
        <table class="content-table">
            <thead><tr><th>ID</th><th>Title</th><th>Type</th><th>Date</th><th>Message</th></tr></thead>
            <tbody>
            <?php
            $res = $conn->query("SELECT * FROM notices ORDER BY ID DESC");
            if ($res && $res->num_rows > 0):
                while ($n = $res->fetch_assoc()):
            ?>
                <tr>
                    <td><?= $n['ID'] ?></td>
                    <td><strong><?= htmlspecialchars($n['Name']) ?></strong></td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($n['Type']) ?></span></td>
                    <td><?= htmlspecialchars($n['Noticedate']) ?></td>
                    <td style="text-align:left;max-width:350px;"><?= nl2br(htmlspecialchars($n['Message'])) ?></td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="5" style="text-align:center;padding:40px;color:#999;">No notices yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$extra_js = "flatpickr('#noticedate', { minDate:'today', dateFormat:'Y-m-d' });";
require 'footer.php';
?>
