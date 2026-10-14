<?php
require 'auth_check_watchman.php';
$wuser = $_SESSION['watchman_user'];
$wname = $_SESSION['watchman_name'];

// Handle log entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_entry'])) {
    csrf_verify();
    $flat     = trim($_POST['flat_no']      ?? '');
    $resident = trim($_POST['resident_name'] ?? '');
    $visitor  = trim($_POST['visitor_name']  ?? '');
    $mobile   = trim($_POST['visitor_mobile']?? '');
    $vehicle  = trim($_POST['vehicle_no']    ?? '');
    $purpose  = trim($_POST['purpose']       ?? '');
    $gp_code  = strtoupper(substr(md5(uniqid()), 0, 8)); // Gate pass code

    $stmt = $conn->prepare(
        "INSERT INTO visitor_log (flat_no,resident_name,visitor_name,visitor_mobile,vehicle_no,purpose,logged_by,gate_pass_code)
         VALUES (?,?,?,?,?,?,?,?)"
    );
    $stmt->bind_param("ssssssss", $flat,$resident,$visitor,$mobile,$vehicle,$purpose,$wuser,$gp_code);
    $stmt->execute();
    $log_id = $conn->insert_id;

    // Notify admin
    push_admin_notification($conn,
        "Visitor logged: $visitor visiting Flat $flat. Gate Pass: $gp_code",
        'general', 'admin_dashboard.php'
    );

    $_SESSION['flash_success'] = "Visitor logged! Gate Pass: <strong>$gp_code</strong> &nbsp; <a href='gate_pass.php?id=$log_id' target='_blank' style='color:#19B3D3;'>Print Gate Pass</a>";
    header("Location: watchman_visitors.php"); exit;
}

// Handle log exit & Overstay Billing
if (isset($_GET['exit_id'])) {
    $exit_id = (int)$_GET['exit_id'];
    
    // 1. Fetch visitor details BEFORE exiting
    $st = $conn->prepare("SELECT flat_no, time_in, vehicle_no, visitor_name FROM visitor_log WHERE id=? AND time_out IS NULL");
    $st->bind_param("i", $exit_id);
    $st->execute();
    $visitor = $st->get_result()->fetch_assoc();
    
    if ($visitor) {
        // 2. Mark as exited
        $stmt = $conn->prepare("UPDATE visitor_log SET time_out=NOW() WHERE id=?");
        $stmt->bind_param("i", $exit_id); 
        $stmt->execute();
        
        // 3. Check for vehicle and overstay (>8 hours)
        if (!empty(trim($visitor['vehicle_no']))) {
            $time_in = strtotime($visitor['time_in']);
            $time_out = time(); // Current time of exit
            $hours_stayed = floor(($time_out - $time_in) / 3600); // Total full hours
            
            if ($hours_stayed > 8) {
                // Fetch dynamic parking rate
                $sec_stmt = $conn->query("SELECT visitor_parking_rate FROM society_details LIMIT 1");
                $parking_rate = (float)($sec_stmt->fetch_assoc()['visitor_parking_rate'] ?? 50);
                
                $billable_hours = $hours_stayed - 8;
                $total_penalty = $billable_hours * $parking_rate; 
                
                // 4. Insert penalty into visitor_parking
                $sys_user = "Watchman Log";
                $v_name = "Overstay Penalty - " . $visitor['visitor_name'];
                $slots = 1;
                $cur_date = date('Y-m-d');
                $cur_time = date('h:i A');
                
                $ins_penalty = $conn->prepare("
                    INSERT INTO visitor_parking 
                    (username, flatno, visitor_name, vehicle_no, slots, start_date, end_date, start_time, end_time, total_hours, total_price) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $ins_penalty->bind_param("ssssissssdd", 
                    $sys_user, $visitor['flat_no'], $v_name, $visitor['vehicle_no'], 
                    $slots, $cur_date, $cur_date, $cur_time, $cur_time, 
                    $billable_hours, $total_penalty
                );
                $ins_penalty->execute();
                
                $_SESSION['flash_success'] = "Exit logged. <b>Overstay Penalty of ₹{$total_penalty}</b> added to Flat {$visitor['flat_no']} bill.";
            } else {
                $_SESSION['flash_success'] = "Exit logged. No overstay charges applied.";
            }
        } else {
            $_SESSION['flash_success'] = "Exit logged successfully.";
        }
    }
    header("Location: watchman_visitors.php"); exit;
}

$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

// FIXED QUERY: Shows today's visitors AND anyone who hasn't exited yet (even if they came yesterday)
$today = date('Y-m-d');
$visitors = $conn->query("
    SELECT * FROM visitor_log 
    WHERE DATE(time_in)='$today' 
    OR time_out IS NULL 
    ORDER BY (time_out IS NULL) DESC, time_in DESC
");

// Fetch all residents for dropdown
$residents = $conn->query("SELECT Flatno, Username FROM registration ORDER BY Flatno ASC");

$page_title = "Visitor Log";
require 'header_watchman.php';
require 'sidebar_watchman.php';
?>

<style>
.vform{max-width:900px;margin:0 auto 30px;padding:30px;border-top:5px solid #f39c12;}
.vform h2{color:#0B3A45;margin-bottom:20px;font-size:20px;}
.row-2{display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:15px;}
.input-box{width:100%;padding:11px 14px;border-radius:8px;border:1.5px solid #ddd;background:#f9f9f9;font-size:14px;outline:none;font-family:inherit;transition:0.3s;}
.input-box:focus{border-color:#f39c12;background:white;}
.btn-log{background:#f39c12;color:#1a1a2e;border:none;padding:13px 30px;border-radius:8px;font-weight:800;cursor:pointer;font-size:15px;transition:0.3s;}
.btn-log:hover{background:#e67e22;}
.btn-exit{background:#27ae60;color:white;border:none;padding:6px 14px;border-radius:6px;cursor:pointer;font-size:12px;font-weight:700;}
.btn-gp{background:#3498db;color:white;border:none;padding:6px 14px;border-radius:6px;cursor:pointer;font-size:12px;font-weight:700;text-decoration:none;display:inline-block;}
.badge-danger{background:#e74c3c;color:white;padding:3px 8px;border-radius:4px;font-size:11px;font-weight:bold;}
</style>

<div class="main-container" id="main-container">

    <?php if ($success): ?>
        <div class="flash-success" style="max-width:900px;margin:0 auto 20px;"><?= $success ?></div>
    <?php endif; ?>

    <div class="card-box vform">
        <h2><i class="fas fa-user-plus" style="color:#f39c12;margin-right:8px;"></i>Log Visitor Entry</h2>
        <form method="POST">
            <?= csrf_field() ?>
            <div class="row-2">
                <div>
                    <label style="font-weight:600;font-size:13px;color:#444;display:block;margin-bottom:6px;">Flat No *</label>
                    <select name="flat_no" class="input-box" required onchange="fillResident(this)">
                        <option value="">Select Flat</option>
                        <?php while($r = $residents->fetch_assoc()): ?>
                            <option value="<?= $r['Flatno'] ?>" data-name="<?= htmlspecialchars($r['Username']) ?>">
                                Flat <?= $r['Flatno'] ?> — <?= htmlspecialchars($r['Username']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label style="font-weight:600;font-size:13px;color:#444;display:block;margin-bottom:6px;">Resident Name *</label>
                    <input type="text" name="resident_name" id="residentName" class="input-box" placeholder="Auto-filled from flat" required>
                </div>
            </div>
            <div class="row-2">
                <div>
                    <label style="font-weight:600;font-size:13px;color:#444;display:block;margin-bottom:6px;">Visitor Full Name *</label>
                    <input type="text" name="visitor_name" class="input-box" placeholder="Visitor's name" required>
                </div>
                <div>
                    <label style="font-weight:600;font-size:13px;color:#444;display:block;margin-bottom:6px;">Visitor Mobile</label>
                    <input type="tel" name="visitor_mobile" class="input-box" placeholder="10-digit mobile">
                </div>
            </div>
            <div class="row-2">
                <div>
                    <label style="font-weight:600;font-size:13px;color:#444;display:block;margin-bottom:6px;">Vehicle No</label>
                    <input type="text" name="vehicle_no" class="input-box" placeholder="e.g. MH12AB1234">
                </div>
                <div>
                    <label style="font-weight:600;font-size:13px;color:#444;display:block;margin-bottom:6px;">Purpose of Visit</label>
                    <input type="text" name="purpose" class="input-box" placeholder="e.g. Personal visit, Delivery">
                </div>
            </div>
            <button type="submit" name="log_entry" class="btn-log"><i class="fas fa-sign-in-alt"></i> Log Entry &amp; Generate Gate Pass</button>
        </form>
    </div>

    <h3 style="color:#0B3A45;margin-bottom:15px;font-size:18px;">Live Visitor Log — System Active</h3>
    <div class="table-container">
        <table class="content-table">
            <thead><tr><th>No</th><th>Visitor</th><th>Flat</th><th>Mobile</th><th>Purpose</th><th>Time In</th><th>Time Out</th><th>Gate Pass</th><th>Action</th></tr></thead>
            <tbody>
            <?php
            $i = 1;
            if ($visitors->num_rows > 0):
                while ($v = $visitors->fetch_assoc()):
                    $tin  = date('d/m/Y h:i A', strtotime($v['time_in']));
                    $tout_raw = $v['time_out'];
                    $tout = $tout_raw ? date('d/m/Y h:i A', strtotime($tout_raw)) : null;
            ?>
                <tr>
                    <td style="color:#888;"><?= $i++ ?></td>
                    <td><strong><?= htmlspecialchars($v['visitor_name']) ?></strong></td>
                    <td><?= htmlspecialchars($v['flat_no']) ?></td>
                    <td><?= htmlspecialchars($v['visitor_mobile'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($v['purpose'] ?? '-') ?></td>
                    <td style="font-size:12px;"><?= $tin ?></td>
                    <td style="font-size:12px;">
                        <?php 
                        if ($tout) {
                            echo $tout; 
                        } else {
                            // Logic: Check if they are currently overstaying (>8 hours)
                            $stay_start = strtotime($v['time_in']);
                            $diff_hours = (time() - $stay_start) / 3600;

                            if ($diff_hours > 8) {
                                echo '<span class="badge-danger"><i class="fas fa-clock"></i> Overstay</span>';
                            } else {
                                echo '<span class="badge badge-info">Inside</span>';
                            }
                        }
                        ?>
                    </td>
                    <td>
                        <a href="gate_pass.php?id=<?= $v['id'] ?>" target="_blank" class="btn-gp">
                            <i class="fas fa-print"></i> <?= htmlspecialchars($v['gate_pass_code'] ?? '') ?>
                        </a>
                    </td>
                    <td>
                        <?php if (!$v['time_out']): ?>
                            <a href="?exit_id=<?= $v['id'] ?>" class="btn-exit"
                               onclick="return confirm('Log exit for <?= htmlspecialchars(addslashes($v['visitor_name'])) ?>?')">
                                <i class="fas fa-sign-out-alt"></i> Exit
                            </a>
                        <?php else: ?>
                            <span style="color:#aaa;font-size:12px;font-style:italic;">Exited</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="9" style="text-align:center;padding:40px;color:#999;">No active visitors in the building.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function fillResident(sel){
    var opt = sel.options[sel.selectedIndex];
    document.getElementById('residentName').value = opt.dataset.name || '';
}
</script>

<?php
require 'footer.php';
?>