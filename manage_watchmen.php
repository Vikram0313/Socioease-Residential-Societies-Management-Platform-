<?php
require 'auth_check_admin.php';

$message = ''; $error = '';

// Add watchman
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_watchman'])) {
    csrf_verify();
    $name     = trim($_POST['wname']    ?? '');
    $username = trim($_POST['wusername']?? '');
    $password = trim($_POST['wpassword']?? '');
    $mobile   = trim($_POST['wmobile']  ?? '');
    $shift    = trim($_POST['wshift']   ?? 'Morning');

    if (strlen($password) < 6) { $error = "Password must be at least 6 characters."; }
    elseif ($name && $username && $password) {
        $chk = $conn->prepare("SELECT id FROM watchmen WHERE username=?");
        $chk->bind_param("s",$username); $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = "Username already exists.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO watchmen (name,username,password,mobile,shift) VALUES (?,?,?,?,?)");
            $stmt->bind_param("sssss",$name,$username,$hash,$mobile,$shift);
            $stmt->execute();
            $message = "Watchman added successfully!";
        }
    }
}

// Toggle active status
if (isset($_GET['toggle_id'])) {
    $tid  = (int)$_GET['toggle_id'];
    $conn->query("UPDATE watchmen SET is_active = 1 - is_active WHERE id=$tid");
    header("Location: manage_watchmen.php"); exit;
}

// Delete
if (isset($_GET['delete_id'])) {
    $did  = (int)$_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM watchmen WHERE id=?");
    $stmt->bind_param("i",$did); $stmt->execute();
    header("Location: manage_watchmen.php"); exit;
}

$watchmen = $conn->query("SELECT * FROM watchmen ORDER BY id ASC");
$page_title = "Manage Watchmen";
require 'header_admin.php';
require 'sidebar_admin.php';
?>
<style>
.wform{max-width:800px;margin:0 auto 40px;padding:30px;border-left:6px solid #f39c12;}
.wform h2{color:#0B3A45;margin-bottom:20px;font-size:20px;}
.row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:15px;margin-bottom:15px;}
.row-2{display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:15px;}
.btn-add{width:100%;padding:13px;background:#f39c12;color:#1a1a2e;border:none;border-radius:8px;font-weight:800;cursor:pointer;font-size:15px;}
</style>

<div class="main-container" id="main-container">
    <h2 class="page-title">Manage Watchmen</h2>

    <?php if ($message): ?><div class="flash-success" style="max-width:800px;margin:0 auto 20px;"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="flash-error" style="max-width:800px;margin:0 auto 20px;"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card-box wform">
        <h2><i class="fas fa-user-plus" style="color:#f39c12;margin-right:8px;"></i>Add New Watchman</h2>
        <form method="POST">
            <?= csrf_field() ?>
            <div class="row-3">
                <div>
                    <label style="font-weight:600;font-size:13px;color:#444;display:block;margin-bottom:6px;">Full Name *</label>
                    <input type="text" name="wname" class="field" placeholder="Full name" required>
                </div>
                <div>
                    <label style="font-weight:600;font-size:13px;color:#444;display:block;margin-bottom:6px;">Username *</label>
                    <input type="text" name="wusername" class="field" placeholder="Login username" required>
                </div>
                <div>
                    <label style="font-weight:600;font-size:13px;color:#444;display:block;margin-bottom:6px;">Password *</label>
                    <input type="password" name="wpassword" class="field" placeholder="Min 6 chars" required minlength="6">
                </div>
            </div>
            <div class="row-2">
                <div>
                    <label style="font-weight:600;font-size:13px;color:#444;display:block;margin-bottom:6px;">Mobile</label>
                    <input type="text" name="wmobile" class="field" placeholder="10-digit mobile">
                </div>
                <div>
                    <label style="font-weight:600;font-size:13px;color:#444;display:block;margin-bottom:6px;">Shift</label>
                    <select name="wshift" class="field">
                        <option>Morning</option>
                        <option>Evening</option>
                        <option>Night</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="add_watchman" class="btn-add"><i class="fas fa-plus"></i> Add Watchman</button>
        </form>
    </div>

    <h2 class="page-title" style="font-size:22px;">Existing Watchmen</h2>
    <div class="table-container">
        <table class="content-table">
            <thead><tr><th>ID</th><th>Name</th><th>Username</th><th>Mobile</th><th>Shift</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if ($watchmen->num_rows > 0):
                while ($w = $watchmen->fetch_assoc()):
                    $sc = $w['is_active'] ? 'badge-success' : 'badge-danger';
                    $sl = $w['is_active'] ? 'Active' : 'Suspended';
            ?>
                <tr>
                    <td><?= $w['id'] ?></td>
                    <td><strong><?= htmlspecialchars($w['name']) ?></strong></td>
                    <td style="font-family:monospace;"><?= htmlspecialchars($w['username']) ?></td>
                    <td><?= htmlspecialchars($w['mobile']) ?></td>
                    <td><?= htmlspecialchars($w['shift']) ?></td>
                    <td><span class="badge <?= $sc ?>"><?= $sl ?></span></td>
                    <td>
                        <a href="?toggle_id=<?= $w['id'] ?>" class="action-btn btn-edit"
                           onclick="return confirm('Toggle status for <?= htmlspecialchars(addslashes($w['name'])) ?>?')">
                            <?= $w['is_active'] ? 'Suspend' : 'Activate' ?>
                        </a>
                        <a href="?delete_id=<?= $w['id'] ?>" class="action-btn btn-del"
                           onclick="return confirm('Delete <?= htmlspecialchars(addslashes($w['name'])) ?>? This cannot be undone.')">
                            Delete
                        </a>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="7" style="text-align:center;padding:40px;color:#999;">No watchmen added yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php require 'footer.php'; ?>
