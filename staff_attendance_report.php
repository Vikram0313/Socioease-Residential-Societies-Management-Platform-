<?php
require 'auth_check_admin.php';

// Month/year filter
$month = (int)($_GET['month'] ?? date('m'));
$year  = (int)($_GET['year']  ?? date('Y'));
$month = max(1, min(12, $month));
$year  = $year < 2020 ? date('Y') : $year;

$month_name = date('F', mktime(0,0,0,$month,1,$year));
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);

// Fetch all staff
$staff_list = $conn->query("SELECT * FROM staff_members ORDER BY name ASC");
$staff_rows = [];
while ($s = $staff_list->fetch_assoc()) $staff_rows[] = $s;

// Fetch all attendance for selected month
$month_str = sprintf('%04d-%02d', $year, $month);
$att_stmt  = $conn->prepare(
    "SELECT staff_id, date, status FROM staff_attendance
     WHERE DATE_FORMAT(date, '%Y-%m') = ?"
);
$att_stmt->bind_param("s", $month_str);
$att_stmt->execute();
$att_res = $att_stmt->get_result();

// Build map: att_data[staff_id][day] = status
$att_data = [];
while ($a = $att_res->fetch_assoc()) {
    $day = (int)date('j', strtotime($a['date']));
    $att_data[$a['staff_id']][$day] = $a['status'];
}

// Summary counts
function count_status(array $days, string $status): int {
    return count(array_filter($days, fn($s) => $s === $status));
}

$page_title = "Attendance Report";
$extra_head = '<style>
.report-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;flex-wrap:wrap;gap:15px;}
.month-nav{display:flex;align-items:center;gap:10px;}
.month-nav a{padding:8px 14px;background:#0EA5C9;color:white;border-radius:8px;text-decoration:none;font-weight:700;font-size:13px;transition:0.2s;}
.month-nav a:hover{background:#0284A8;}
.month-title{font-size:20px;font-weight:800;color:#0D2137;min-width:160px;text-align:center;}
.month-select{display:flex;gap:10px;align-items:center;}
.month-select select{padding:9px 12px;border:1.5px solid #0EA5C9;border-radius:8px;font-size:14px;outline:none;font-family:inherit;cursor:pointer;}
.att-wrap{overflow-x:auto;}
.att-report-table{border-collapse:collapse;min-width:100%;}
.att-report-table th,.att-report-table td{border:1px solid #E2EAF4;padding:9px 7px;font-size:12px;text-align:center;white-space:nowrap;}
.att-report-table thead tr:first-child{background:#0D2137;color:white;}
.att-report-table thead tr:last-child{background:#F8FAFF;}
.att-report-table .staff-name{text-align:left;font-weight:700;color:#0D2137;padding-left:14px;min-width:150px;}
.att-report-table .day-num{font-weight:600;color:#64748B;font-size:11px;}
.att-report-table .sunday{background:#FEF9C3;}
td.P{background:#DCFCE7;color:#166534;font-weight:700;}
td.A{background:#FEE2E2;color:#991B1B;font-weight:700;}
td.H{background:#FEF9C3;color:#854D0E;font-weight:700;}
td.blank{background:#F8FAFC;color:#CBD5E1;}
.summary-P{color:#166534;font-weight:800;}
.summary-A{color:#991B1B;font-weight:800;}
.summary-H{color:#854D0E;font-weight:800;}
.export-btn{background:#27ae60;color:white;border:none;padding:10px 20px;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px;}
</style>';

require 'header_admin.php';
require 'sidebar_admin.php';

// Prev/next month
$prev_month = $month == 1 ? 12 : $month - 1;
$prev_year  = $month == 1 ? $year - 1 : $year;
$next_month = $month == 12 ? 1 : $month + 1;
$next_year  = $month == 12 ? $year + 1 : $year;
?>

<div class="main-container" id="main-container">
    <div class="report-header">
        <h2 class="page-title" style="margin:0;font-size:24px;">
            <i class="fas fa-clipboard-list" style="color:#0EA5C9;margin-right:10px;"></i>Staff Attendance Report
        </h2>

        <div style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
            <!-- Month navigation -->
            <div class="month-nav">
                <a href="?month=<?= $prev_month ?>&year=<?= $prev_year ?>"><i class="fas fa-chevron-left"></i></a>
                <span class="month-title"><?= $month_name ?> <?= $year ?></span>
                <a href="?month=<?= $next_month ?>&year=<?= $next_year ?>"><i class="fas fa-chevron-right"></i></a>
            </div>

            <!-- Jump to month/year -->
            <form method="GET" style="display:flex;gap:8px;align-items:center;">
                <select name="month">
                    <?php for ($m=1;$m<=12;$m++): ?>
                        <option value="<?= $m ?>" <?= $m==$month?'selected':'' ?>><?= date('F',mktime(0,0,0,$m,1)) ?></option>
                    <?php endfor; ?>
                </select>
                <select name="year">
                    <?php for ($y=date('Y')-2;$y<=date('Y');$y++): ?>
                        <option value="<?= $y ?>" <?= $y==$year?'selected':'' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn" style="padding:9px 16px;font-size:13px;">Go</button>
            </form>

            <button class="export-btn" onclick="window.print()">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>
    </div>

    <?php if (empty($staff_rows)): ?>
        <div style="background:white;border-radius:14px;padding:60px;text-align:center;color:#94A3B8;border:1px solid #E2EAF4;">
            <i class="fas fa-users" style="font-size:40px;margin-bottom:15px;display:block;opacity:0.3;"></i>
            <p style="font-size:15px;font-weight:600;">No staff members added yet.</p>
        </div>
    <?php else: ?>

    <!-- Legend -->
    <div style="display:flex;gap:15px;margin-bottom:16px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:#166534;"><div style="width:16px;height:16px;background:#DCFCE7;border:1px solid #059669;border-radius:3px;"></div> P = Present</div>
        <div style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:#991B1B;"><div style="width:16px;height:16px;background:#FEE2E2;border:1px solid #DC2626;border-radius:3px;"></div> A = Absent</div>
        <div style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:#854D0E;"><div style="width:16px;height:16px;background:#FEF9C3;border:1px solid #CA8A04;border-radius:3px;"></div> H = Half-Day</div>
        <div style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:#94A3B8;"><div style="width:16px;height:16px;background:#F8FAFC;border:1px solid #E2EAF4;border-radius:3px;"></div> — = Not Marked</div>
    </div>

    <div class="table-container">
        <div class="att-wrap">
            <table class="att-report-table" id="reportTable">
                <thead>
                    <tr>
                        <th rowspan="2" style="text-align:left;padding-left:14px;min-width:150px;">Staff Name</th>
                        <th rowspan="2" style="min-width:90px;">Role</th>
                        <?php for ($d=1;$d<=$days_in_month;$d++): ?>
                            <th><?= $d ?></th>
                        <?php endfor; ?>
                        <th style="background:#DCFCE7;color:#166534;">P</th>
                        <th style="background:#FEE2E2;color:#991B1B;">A</th>
                        <th style="background:#FEF9C3;color:#854D0E;">H</th>
                        <th>Total</th>
                    </tr>
                    <tr>
                        <?php for ($d=1;$d<=$days_in_month;$d++):
                            $dow = date('D', mktime(0,0,0,$month,$d,$year));
                        ?>
                            <th class="day-num <?= $dow==='Sun'?'sunday':'' ?>"><?= $dow ?></th>
                        <?php endfor; ?>
                        <th></th><th></th><th></th><th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($staff_rows as $s):
                    $s_att = $att_data[$s['id']] ?? [];
                    $p_count = count_status($s_att, 'Present');
                    $a_count = count_status($s_att, 'Absent');
                    $h_count = count_status($s_att, 'Half-Day');
                    $total   = $p_count + $a_count + $h_count;
                ?>
                    <tr>
                        <td class="staff-name"><?= htmlspecialchars($s['name']) ?></td>
                        <td style="font-size:11px;color:#64748B;"><?= htmlspecialchars($s['role']) ?></td>
                        <?php for ($d=1;$d<=$days_in_month;$d++):
                            $dow  = date('D', mktime(0,0,0,$month,$d,$year));
                            $stat = $s_att[$d] ?? null;
                            $abbr = $stat ? substr($stat,0,1) : '—';
                            $cls  = $stat ? substr($stat,0,1) : 'blank';
                            $sunday_cls = $dow==='Sun' ? 'sunday' : '';
                        ?>
                            <td class="<?= $cls ?> <?= $sunday_cls ?>"><?= $abbr ?></td>
                        <?php endfor; ?>
                        <td class="summary-P"><?= $p_count ?></td>
                        <td class="summary-A"><?= $a_count ?></td>
                        <td class="summary-H"><?= $h_count ?></td>
                        <td style="font-weight:700;color:#0D2137;"><?= $total ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
@media print {
    .sidebar, header, .month-nav a, form, .export-btn { display:none !important; }
    .main-container { margin-left:0 !important; padding:10px !important; }
    .att-report-table { font-size:10px; }
}
</style>

<?php require 'footer.php'; ?>
