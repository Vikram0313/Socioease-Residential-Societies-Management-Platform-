<?php
require 'auth_check_admin.php';

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    csrf_verify();
    $name    = trim($_POST['staff_name']    ?? '');
    $role    = trim($_POST['staff_role']    ?? '');
    $mobile  = trim($_POST['staff_mobile']  ?? '');
    $email   = trim($_POST['staff_email']   ?? '');
    $address = trim($_POST['staff_address'] ?? '');

    if ($name && $mobile && $role) {
        $stmt = $conn->prepare("INSERT INTO staff_members (name, role, mobile, email, address) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssss", $name, $role, $mobile, $email, $address);
        $stmt->execute();
        $message = "Staff member added successfully!";
    }
}

$staff_list = [];
$res = $conn->query("SELECT * FROM staff_members ORDER BY id ASC");
if ($res) while ($row = $res->fetch_assoc()) $staff_list[] = $row;

$page_title = "Manage Staff";
require 'header_admin.php';
require 'sidebar_admin.php';
?>
<style>
.add-card { max-width:800px; margin:0 auto 40px; padding:30px; border-left:6px solid #19B3D3; }
.add-card h2 { font-size:20px; color:#0B3A45; margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:10px; }
</style>

<div class="main-container" id="main-container">
    <h2 class="page-title">Staff Management</h2>

    <?php if ($message): ?><div class="flash-success" style="max-width:800px;margin:0 auto 20px;"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <div class="card-box add-card">
        <h2><i class="fas fa-user-plus"></i> Add New Staff Member</h2>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="add_staff" value="1">
            <input type="text"  class="field" name="staff_name"    placeholder="Full Name*"           required>
            <input type="text"  class="field" name="staff_role"    placeholder="Role / Designation*"  required>
            <input type="text"  class="field" name="staff_mobile"  placeholder="Mobile Number*"       required>
            <input type="email" class="field" name="staff_email"   placeholder="Email (optional)">
            <textarea            class="field" name="staff_address" placeholder="Address (optional)" rows="2"></textarea>
            <button class="btn btn-full" style="padding:14px;font-size:16px;">Add Staff</button>
        </form>
    </div>

    <h2 class="page-title" style="font-size:22px;">Existing Staff Members</h2>
    <div class="table-container">
        <table class="content-table">
            <thead><tr><th>ID</th><th>Name</th><th>Role</th><th>Mobile</th><th>Email</th><th>Address</th><th>Action</th></tr></thead>
            <tbody>
            <?php if (empty($staff_list)): ?>
                <tr><td colspan="7" style="text-align:center;padding:40px;color:#999;">No staff added yet.</td></tr>
            <?php else: foreach ($staff_list as $s): ?>
                <tr>
                    <td><?= $s['id'] ?></td>
                    <td><?= htmlspecialchars($s['name']) ?></td>
                    <td><?= htmlspecialchars($s['role']) ?></td>
                    <td><?= htmlspecialchars($s['mobile']) ?></td>
                    <td><?= htmlspecialchars($s['email']) ?></td>
                    <td><?= htmlspecialchars($s['address']) ?></td>
                    <td>
                        <a href="editstaff.php?id=<?= $s['id'] ?>" class="action-btn btn-edit">Edit</a>
                        <a href="deletestaff.php?id=<?= $s['id'] ?>" class="action-btn btn-del"
                           onclick="return confirm('Delete <?= htmlspecialchars(addslashes($s['name'])) ?>?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require 'footer.php'; ?>
