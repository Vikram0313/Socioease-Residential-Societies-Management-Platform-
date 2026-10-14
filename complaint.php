<?php
require 'auth_check.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/notifications_helper.php';

$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lcomplaint'])) {
    csrf_verify();

    $image_path = '';
    if (!empty($_FILES['cimage']['name']) && $_FILES['cimage']['error'] === 0) {
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $ext = strtolower(pathinfo($_FILES['cimage']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $dir = "uploads/";
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $image_path = $dir . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            move_uploaded_file($_FILES['cimage']['tmp_name'], $image_path);
        }
    }

    $username = $_SESSION['username'];
    $title    = trim($_POST['ctitle'] ?? '');
    $msg      = trim($_POST['nmsg']   ?? '');

    $stmt = $conn->prepare(
        "INSERT INTO combox (Title, complaint, image_path, status, username) VALUES (?,?,?,'Pending',?)"
    );
    $stmt->bind_param("ssss", $title, $msg, $image_path, $username);

    if ($stmt->execute()) {
        push_admin_notification($conn, "New complaint from $username: \"$title\"", 'complaint', 'viewcomplaints.php');
        push_notification($conn, $username,
            "Your complaint \"$title\" has been submitted and is pending review.",
            'complaint', 'complaint.php'
        );

        // Confirmation email to resident
        $user_row = $conn->prepare("SELECT Email FROM registration WHERE Username=?");
        $user_row->bind_param("s", $username);
        $user_row->execute();
        $ur = $user_row->get_result()->fetch_assoc();
        if ($ur && $ur['Email']) {
            $body = email_template("Complaint Submitted", "
                <p>Hi <strong>" . htmlspecialchars($username) . "</strong>, your complaint has been received.</p>
                <div class='info-box'>
                    <p><strong>Title:</strong> " . htmlspecialchars($title) . "</p>
                    <p><strong>Status:</strong> 🕐 Pending Review</p>
                </div>
                <p>Our team will review your complaint and update the status. You will receive an email notification when there is a change.</p>
            ");
            send_email($ur['Email'], $username, "Complaint Submitted  -  Socioease", $body);
        }

        $_SESSION['flash_success'] = "Complaint Submitted Successfully!";
        header("Location: complaint.php");
        exit;
    }
}

$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

$page_title = "Complaints";
require 'header_user.php';
require 'sidebar_user.php';
?>
<style>
.form-card { max-width:900px; margin:0 auto 40px; padding:35px; border-top:5px solid #19B3D3; }
.form-card h2 { color:#0B3A45; margin-bottom:25px; font-size:24px; }
.input-group { margin-bottom:20px; }
.input-group label { display:block; font-weight:600; color:#444; margin-bottom:8px; font-size:14px; }
.input-box { width:100%; padding:12px; border-radius:8px; border:1px solid #ddd; background:#f9f9f9; font-size:15px; outline:none; transition:0.3s; font-family:inherit; }
.input-box:focus { border-color:#19B3D3; background:#fff; box-shadow:0 0 8px rgba(25,179,211,0.1); }
textarea.input-box { height:100px; resize:vertical; }
.btn-row { display:flex; gap:15px; margin-top:10px; }
.btn-submit { flex:2; background:#19B3D3; color:white; border:none; padding:14px; border-radius:8px; font-weight:700; cursor:pointer; transition:0.3s; }
.btn-submit:hover { background:#0B87A6; }
.btn-cancel { flex:1; background:#eee; color:#666; border:none; padding:14px; border-radius:8px; text-align:center; text-decoration:none; font-weight:600; display:flex; align-items:center; justify-content:center; }
.img-modal { display:none; position:fixed; z-index:2000; padding-top:80px; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.9); }
.img-modal-content { margin:auto; display:block; max-width:80%; max-height:80%; border-radius:10px; }
.close-modal { position:absolute; top:30px; right:40px; color:white; font-size:40px; cursor:pointer; }
</style>

<div class="main-container" id="main-container">
    <?php if ($success): ?>
        <div class="flash-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="card-box form-card">
        <h2><i class="fas fa-pen-nib" style="color:#19B3D3;"></i> New Complaint</h2>
        <form method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="input-group">
                <label>Complaint Title</label>
                <input type="text" name="ctitle" class="input-box" placeholder="e.g. Water Leakage in Wing B" required maxlength="150">
            </div>
            <div class="input-group">
                <label>Detailed Description</label>
                <textarea name="nmsg" class="input-box" placeholder="Describe the issue in detail..." required maxlength="1000"></textarea>
            </div>
            <div class="input-group">
                <label>Attach Evidence (Optional  -  JPG, PNG, GIF, WEBP)</label>
                <input type="file" name="cimage" class="input-box" accept="image/*" style="padding:8px;">
            </div>
            <div class="btn-row">
                <button type="submit" class="btn-submit" name="lcomplaint">Submit Complaint</button>
                <a href="Welcome.php" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>

    <h3 class="page-title" style="font-size:20px;margin-bottom:15px;text-align:left;">Your Complaint History</h3>
    <div class="table-container">
        <?php
        $username = $_SESSION['username'];
        $res = $conn->prepare("SELECT * FROM combox WHERE username=? ORDER BY ID ASC");
        $res->bind_param("s", $username);
        $res->execute();
        $rows = $res->get_result();
        ?>
        <table class="content-table">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th style="width:180px;">Subject</th>
                    <th>Details</th>
                    <th style="width:100px;text-align:center;">Media</th>
                    <th style="width:120px;text-align:center;">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($rows->num_rows > 0): ?>
                <?php while ($row = $rows->fetch_assoc()):
                    $sc = ['Pending'=>'badge-pending','Resolved'=>'badge-success','Rejected'=>'badge-danger','In Progress'=>'badge-info'][$row['status']] ?? 'badge-pending';
                ?>
                <tr>
                    <td style="color:#999;font-weight:bold;"><?= $row['ID'] ?></td>
                    <td style="font-weight:600;color:#2c3e50;"><?= htmlspecialchars($row['Title']) ?></td>
                    <td><div style="color:#666;line-height:1.4;word-break:break-word;"><?= nl2br(htmlspecialchars($row['complaint'])) ?></div></td>
                    <td style="text-align:center;">
                        <?php if (!empty($row['image_path'])): ?>
                            <img src="<?= htmlspecialchars($row['image_path']) ?>" width="45" height="45"
                                 style="object-fit:cover;border-radius:6px;cursor:pointer;border:1px solid #ddd;"
                                 onclick="openImgModal('<?= htmlspecialchars($row['image_path']) ?>')">
                        <?php else: ?>
                            <span style="color:#ccc;font-size:11px;">N/A</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;"><span class="badge <?= $sc ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align:center;padding:40px;color:#999;">No complaints found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="imgModal" class="img-modal">
    <span class="close-modal" onclick="document.getElementById('imgModal').style.display='none'">&times;</span>
    <img class="img-modal-content" id="modalImage">
</div>

<?php
$extra_js = "
function openImgModal(src){
    document.getElementById('modalImage').src=src;
    document.getElementById('imgModal').style.display='block';
}
window.onclick=function(e){
    if(e.target===document.getElementById('imgModal'))
        document.getElementById('imgModal').style.display='none';
}
";
require 'footer.php';
?>
