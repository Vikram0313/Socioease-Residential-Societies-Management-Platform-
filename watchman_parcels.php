<?php
require 'auth_check_watchman.php';
$wuser = $_SESSION['watchman_user'];

// Log new parcel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_parcel'])) {
    csrf_verify();
    $flat     = trim($_POST['flat_no']        ?? '');
    $resident = trim($_POST['resident_name']  ?? '');
    $courier  = trim($_POST['courier_company']?? '');
    $desc     = trim($_POST['description']    ?? '');
    $stmt = $conn->prepare("INSERT INTO parcel_log (flat_no,resident_name,courier_company,description,logged_by) VALUES (?,?,?,?,?)");
    $stmt->bind_param("sssss",$flat,$resident,$courier,$desc,$wuser);
    $stmt->execute();

    push_admin_notification($conn,
        "New parcel for Flat $flat ($resident) from $courier. Awaiting collection.",
        'general', 'admin_dashboard.php'
    );
    $_SESSION['flash_success'] = "Parcel logged successfully!";
    header("Location: watchman_parcels.php"); exit;
}

// Mark collected
if (isset($_GET['collect_id'])) {
    $cid      = (int)$_GET['collect_id'];
    $cby      = trim($_GET['by'] ?? 'Resident');
    $stmt     = $conn->prepare("UPDATE parcel_log SET status='Collected',collected_at=NOW(),collected_by=? WHERE id=?");
    $stmt->bind_param("si",$cby,$cid); $stmt->execute();
    header("Location: watchman_parcels.php"); exit;
}

$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

$pending  = $conn->query("SELECT * FROM parcel_log WHERE status='Pending' ORDER BY received_at DESC");
$collected= $conn->query("SELECT * FROM parcel_log WHERE status='Collected' ORDER BY collected_at DESC LIMIT 20");
$residents= $conn->query("SELECT Flatno, Username FROM registration ORDER BY Flatno ASC");

$page_title = "Parcel Log";
require 'header_watchman.php';
require 'sidebar_watchman.php';
?>
<style>
.pform{max-width:900px;margin:0 auto 30px;padding:30px;border-top:5px solid #f39c12;}
.pform h2{color:#0B3A45;margin-bottom:20px;font-size:20px;}
.row-2{display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:15px;}
.input-box{width:100%;padding:11px 14px;border-radius:8px;border:1.5px solid #ddd;background:#f9f9f9;font-size:14px;outline:none;font-family:inherit;transition:0.3s;}
.input-box:focus{border-color:#f39c12;background:white;}
.btn-log{background:#f39c12;color:#1a1a2e;border:none;padding:13px 30px;border-radius:8px;font-weight:800;cursor:pointer;font-size:15px;}
.btn-collect{background:#27ae60;color:white;border:none;padding:6px 14px;border-radius:6px;cursor:pointer;font-size:12px;font-weight:700;}
</style>

<div class="main-container" id="main-container">
    <?php if ($success): ?><div class="flash-success" style="max-width:900px;margin:0 auto 20px;"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="card-box pform">
        <h2><i class="fas fa-box" style="color:#f39c12;margin-right:8px;"></i>Log Incoming Parcel</h2>
        <form method="POST">
            <?= csrf_field() ?>
            <div class="row-2">
                <div>
                    <label style="font-weight:600;font-size:13px;color:#444;display:block;margin-bottom:6px;">Flat No *</label>
                    <select name="flat_no" class="input-box" required onchange="fillRes(this)">
                        <option value="">Select Flat</option>
                        <?php while($r=$residents->fetch_assoc()): ?>
                            <option value="<?= $r['Flatno'] ?>" data-name="<?= htmlspecialchars($r['Username']) ?>">
                                Flat <?= $r['Flatno'] ?> — <?= htmlspecialchars($r['Username']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label style="font-weight:600;font-size:13px;color:#444;display:block;margin-bottom:6px;">Resident Name *</label>
                    <input type="text" name="resident_name" id="resName" class="input-box" required>
                </div>
            </div>
            <div class="row-2">
                <div>
                    <label style="font-weight:600;font-size:13px;color:#444;display:block;margin-bottom:6px;">Courier Company</label>
                    <input type="text" name="courier_company" class="input-box" placeholder="e.g. Amazon, Flipkart, DTDC">
                </div>
                <div>
                    <label style="font-weight:600;font-size:13px;color:#444;display:block;margin-bottom:6px;">Description</label>
                    <input type="text" name="description" class="input-box" placeholder="e.g. Small box, envelope">
                </div>
            </div>
            <button type="submit" name="log_parcel" class="btn-log"><i class="fas fa-save"></i> Log Parcel</button>
        </form>
    </div>

    <h3 style="color:#e74c3c;margin-bottom:15px;font-size:18px;"><i class="fas fa-clock"></i> Pending Collection (<?= $pending->num_rows ?>)</h3>
    <div class="table-container" style="margin-bottom:30px;">
        <table class="content-table">
            <thead><tr><th>No</th><th>Flat</th><th>Resident</th><th>Courier</th><th>Description</th><th>Received</th><th>Action</th></tr></thead>
            <tbody>
            <?php if ($pending->num_rows > 0):
                $i=1; while($p=$pending->fetch_assoc()):
            ?>
                <tr>
                    <td style="color:#888;"><?= $i++ ?></td>
                    <td><?= htmlspecialchars($p['flat_no']) ?></td>
                    <td><strong><?= htmlspecialchars($p['resident_name']) ?></strong></td>
                    <td><?= htmlspecialchars($p['courier_company'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($p['description'] ?? '-') ?></td>
                    <td><?= date('d/m/Y h:i A', strtotime($p['received_at'])) ?></td>
                    <td>
                        <a href="?collect_id=<?= $p['id'] ?>&by=<?= urlencode($p['resident_name']) ?>"
                           class="btn-collect"
                           onclick="return confirm('Mark as collected by <?= htmlspecialchars(addslashes($p['resident_name'])) ?>?')">
                            <i class="fas fa-check"></i> Collected
                        </a>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="7" style="text-align:center;padding:30px;color:#999;">No pending parcels.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <h3 style="color:#27ae60;margin-bottom:15px;font-size:18px;"><i class="fas fa-check-circle"></i> Recently Collected</h3>
    <div class="table-container">
        <table class="content-table">
            <thead><tr><th>No</th><th>Flat</th><th>Resident</th><th>Courier</th><th>Received</th><th>Collected</th></tr></thead>
            <tbody>
            <?php if ($collected->num_rows > 0):
                $i=1; while($c=$collected->fetch_assoc()):
            ?>
                <tr>
                    <td style="color:#888;"><?= $i++ ?></td>
                    <td><?= htmlspecialchars($c['flat_no']) ?></td>
                    <td><?= htmlspecialchars($c['resident_name']) ?></td>
                    <td><?= htmlspecialchars($c['courier_company'] ?? '-') ?></td>
                    <td><?= date('d/m/Y h:i A', strtotime($c['received_at'])) ?></td>
                    <td><?= date('d/m/Y h:i A', strtotime($c['collected_at'])) ?></td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="6" style="text-align:center;padding:30px;color:#999;">No collected parcels yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$extra_js = "function fillRes(sel){ var opt=sel.options[sel.selectedIndex]; document.getElementById('resName').value=opt.dataset.name||''; }";
require 'footer.php';
?>
