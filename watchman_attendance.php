<?php
require 'auth_check_watchman.php';
$wuser = $_SESSION['watchman_user'];
$today = date('Y-m-d');

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $date       = trim($_POST['att_date'] ?? $today);
    $attendance = $_POST['attendance'] ?? [];

    foreach ($attendance as $staff_id => $status) {
        $staff_id = (int)$staff_id;
        $status   = in_array($status, ['Present','Absent','Half-Day']) ? $status : 'Present';

        $stmt = $conn->prepare(
            "INSERT INTO staff_attendance (staff_id, date, status, marked_by)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE status=VALUES(status), marked_by=VALUES(marked_by)"
        );
        $stmt->bind_param("isss", $staff_id, $date, $status, $wuser);
        $stmt->execute();
    }

    // If AJAX request return JSON, else redirect normally
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    $_SESSION['flash_success'] = "Attendance saved for " . date('d/m/Y', strtotime($date)) . "!";
    header("Location: watchman_attendance.php?date=$date");
    exit;
}

$selected_date = trim($_GET['date'] ?? $today);

// Fetch all staff
$staff_list = $conn->query("SELECT * FROM staff_members ORDER BY name ASC");
$staff_rows = [];
while ($s = $staff_list->fetch_assoc()) $staff_rows[] = $s;

// Fetch existing attendance for selected date
$att_map = [];
$att_stmt = $conn->prepare("SELECT staff_id, status FROM staff_attendance WHERE date=?");
$att_stmt->bind_param("s", $selected_date);
$att_stmt->execute();
$att_res = $att_stmt->get_result();
while ($a = $att_res->fetch_assoc()) {
    $att_map[$a['staff_id']] = $a['status'];
}

$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

$page_title = "Staff Attendance";
$extra_head = '
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<style>
.flatpickr-calendar{background:#fff;border-radius:12px !important;box-shadow:0 8px 30px rgba(0,0,0,0.15) !important;border:none !important;}
.flatpickr-months{background:#1a1a2e;border-radius:12px 12px 0 0;padding:6px 0;}
.flatpickr-months .flatpickr-month,.flatpickr-current-month span.cur-month,.flatpickr-current-month input.cur-year{color:white !important;fill:white;font-weight:700;}
.flatpickr-months .flatpickr-prev-month svg,.flatpickr-months .flatpickr-next-month svg{fill:white !important;}
.flatpickr-weekdays{background:#1a1a2e;}
span.flatpickr-weekday{background:#1a1a2e;color:#f39c12 !important;font-weight:700;}
.flatpickr-day.selected,.flatpickr-day.selected:hover{background:#f39c12 !important;border-color:#f39c12 !important;color:white !important;}
.att-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;flex-wrap:wrap;gap:15px;}
.att-header h2{color:#0D2137;font-size:22px;font-weight:800;}
.date-picker-wrap{display:flex;gap:10px;align-items:center;}
.date-input{padding:10px 14px;border:1.5px solid #0EA5C9;border-radius:9px;font-size:14px;outline:none;cursor:pointer;font-family:inherit;width:160px;}
.att-table{width:100%;border-collapse:collapse;}
.att-table thead tr{background:#1a1a2e;color:white;}
.att-table th{padding:13px 16px;text-align:left;font-size:13px;font-weight:600;}
.att-table td{padding:14px 16px;border-bottom:1px solid #F1F5F9;font-size:14px;}
.att-table tbody tr:hover{background:#F8FAFF;}
.att-radio-group{display:flex;gap:10px;flex-wrap:wrap;}
.att-radio{display:flex;align-items:center;gap:6px;cursor:pointer;}
.att-radio input[type=radio]{display:none;}
.att-label{padding:6px 14px;border-radius:20px;font-size:12px;font-weight:700;cursor:pointer;border:2px solid;transition:0.2s;}
.att-radio input:checked + .att-label-present{background:#DCFCE7;border-color:#059669;color:#166534;}
.att-label-present{border-color:#E2E8F0;color:#94A3B8;}
.att-radio input:checked + .att-label-absent{background:#FEE2E2;border-color:#DC2626;color:#991B1B;}
.att-label-absent{border-color:#E2E8F0;color:#94A3B8;}
.att-radio input:checked + .att-label-half{background:#FEF9C3;border-color:#CA8A04;color:#854D0E;}
.att-label-half{border-color:#E2E8F0;color:#94A3B8;}
.role-badge{background:#E0F2FE;color:#0369A1;padding:3px 10px;border-radius:10px;font-size:11px;font-weight:700;}
.save-btn{background:#f39c12;color:#1a1a2e;border:none;padding:13px 30px;border-radius:9px;font-weight:800;cursor:pointer;font-size:15px;transition:0.2s;}
.save-btn:hover{background:#e67e22;}
.mark-all{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;}
.mark-btn{padding:7px 16px;border-radius:8px;border:1.5px solid;cursor:pointer;font-size:13px;font-weight:700;background:none;transition:0.2s;}
.mark-btn.present{border-color:#059669;color:#059669;}
.mark-btn.absent {border-color:#DC2626;color:#DC2626;}
.mark-btn.half   {border-color:#CA8A04;color:#CA8A04;}
.mark-btn:hover.present{background:#DCFCE7;}
.mark-btn:hover.absent {background:#FEE2E2;}
.mark-btn:hover.half   {background:#FEF9C3;}
</style>';

require 'header_watchman.php';
require 'sidebar_watchman.php';
?>

<div class="main-container" id="main-container">
    <?php if ($success): ?><div class="flash-success" style="max-width:900px;margin:0 auto 20px;"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="att-header">
        <h2><i class="fas fa-clipboard-check" style="color:#f39c12;margin-right:10px;"></i>Staff Attendance</h2>
        <div class="date-picker-wrap">
            <span style="font-size:14px;color:#64748B;font-weight:600;">Date:</span>
            <input type="text" id="attDatePicker" class="date-input"
                   value="<?= date('d/m/Y', strtotime($selected_date)) ?>"
                   placeholder="Select date" readonly>
        </div>
    </div>

    <?php if (empty($staff_rows)): ?>
        <div style="background:white;border-radius:14px;padding:60px;text-align:center;color:#94A3B8;border:1px solid #E2EAF4;">
            <i class="fas fa-users" style="font-size:40px;margin-bottom:15px;display:block;opacity:0.3;"></i>
            <p style="font-size:15px;font-weight:600;">No staff members found.</p>
            <p style="font-size:13px;margin-top:6px;">Ask admin to add staff members first.</p>
        </div>
    <?php else: ?>

        <div style="background:white;border-radius:14px;padding:25px;box-shadow:0 2px 16px rgba(13,33,55,0.07);border:1px solid #E2EAF4;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                <p style="font-size:14px;color:#64748B;">
                    <i class="fas fa-calendar-day" style="color:#f39c12;"></i>
                    Marking attendance for: <strong style="color:#0D2137;"><?= date('l, d/m/Y', strtotime($selected_date)) ?></strong>
                </p>
                <div class="mark-all">
                    <span style="font-size:13px;color:#64748B;align-self:center;font-weight:600;">Mark All:</span>
                    <button type="button" class="mark-btn present" onclick="markAll('Present')"><i class="fas fa-check"></i> Present</button>
                    <button type="button" class="mark-btn absent"  onclick="markAll('Absent')"><i class="fas fa-times"></i> Absent</button>
                    <button type="button" class="mark-btn half"    onclick="markAll('Half-Day')"><i class="fas fa-adjust"></i> Half-Day</button>
                </div>
            </div>

            <form id="attForm">
                <?= csrf_field() ?>
                <input type="hidden" name="att_date" value="<?= htmlspecialchars($selected_date) ?>">
                <input type="hidden" name="save_attendance" value="1">

                <table class="att-table">
                    <thead>
                        <tr>
                            <th style="width:40px;">No.</th>
                            <th>Staff Name</th>
                            <th>Role</th>
                            <th>Attendance</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($staff_rows as $i => $s):
                        $current_status = $att_map[$s['id']] ?? 'Present';
                    ?>
                        <tr>
                            <td style="color:#94A3B8;font-weight:600;"><?= $i+1 ?></td>
                            <td>
                                <strong style="color:#0D2137;"><?= htmlspecialchars($s['name']) ?></strong>
                                <div style="font-size:12px;color:#94A3B8;"><?= htmlspecialchars($s['mobile']) ?></div>
                            </td>
                            <td><span class="role-badge"><?= htmlspecialchars($s['role']) ?></span></td>
                            <td>
                                <div class="att-radio-group" id="group_<?= $s['id'] ?>">
                                    <label class="att-radio">
                                        <input type="radio" name="attendance[<?= $s['id'] ?>]" value="Present" <?= $current_status==='Present'?'checked':'' ?>>
                                        <span class="att-label att-label-present">✓ Present</span>
                                    </label>
                                    <label class="att-radio">
                                        <input type="radio" name="attendance[<?= $s['id'] ?>]" value="Absent" <?= $current_status==='Absent'?'checked':'' ?>>
                                        <span class="att-label att-label-absent">✗ Absent</span>
                                    </label>
                                    <label class="att-radio">
                                        <input type="radio" name="attendance[<?= $s['id'] ?>]" value="Half-Day" <?= $current_status==='Half-Day'?'checked':'' ?>>
                                        <span class="att-label att-label-half">½ Half-Day</span>
                                    </label>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="margin-top:20px;text-align:right;">
                    <button type="button" class="save-btn" onclick="submitAttendance()">
                        <i class="fas fa-save"></i> Save Attendance
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php
$extra_js = "
flatpickr('#attDatePicker', {
    dateFormat: 'd/m/Y',
    maxDate: 'today',
    onChange: function(selectedDates, dateStr, instance) {
        var parts = dateStr.split('/');
        var ymd = parts[2] + '-' + parts[1] + '-' + parts[0];
        window.location.href = 'watchman_attendance.php?date=' + ymd;
    }
});

function markAll(status) {
    document.querySelectorAll('input[type=radio][value=\"' + status + '\"]')
            .forEach(function(r){ r.checked = true; });
}

function submitAttendance(){
    var form = document.getElementById('attForm');
    var data = new FormData(form);
    var btn  = document.querySelector('.save-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Saving...';

    fetch('watchman_attendance.php', {
        method: 'POST',
        body: data,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r){ return r.json(); })
    .then(function(res){
        if(res.success){
            var card  = document.querySelector('.att-table').closest('div');
            var date  = document.querySelector('[name=att_date]').value;
            var parts = date.split('-');
            var dDisp = parts[2]+'/'+parts[1]+'/'+parts[0];

            card.style.transition = '0.4s';
            card.style.opacity    = '0';
            card.style.transform  = 'scale(0.97)';

            setTimeout(function(){
                card.innerHTML =
                    '<div style=\"padding:60px 20px;text-align:center;\">'+
                    '<div style=\"width:70px;height:70px;background:#DCFCE7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;font-size:32px;\">&#x2705;</div>'+
                    '<h3 style=\"color:#166534;font-size:20px;font-weight:800;margin-bottom:8px;\">Attendance Saved!</h3>'+
                    '<p style=\"color:#64748B;font-size:14px;margin-bottom:24px;\">Attendance for <strong>' + dDisp + '</strong> recorded successfully.</p>'+
                    '<button onclick=\"location.reload()\" style=\"background:#f39c12;color:#1a1a2e;border:none;padding:11px 26px;border-radius:9px;font-weight:800;cursor:pointer;font-size:14px;\">'+
                    '<i class=\"fas fa-plus\"></i> Mark Another Day</button></div>';
                card.style.opacity   = '1';
                card.style.transform = 'scale(1)';
            }, 400);
        }
    })
    .catch(function(e){
        console.error(e);
        btn.disabled = false;
        btn.innerHTML = '<i class=\"fas fa-save\"></i> Save Attendance';
        alert('Error saving attendance. Please try again.');
    });
}
";
require 'footer.php';
?>
