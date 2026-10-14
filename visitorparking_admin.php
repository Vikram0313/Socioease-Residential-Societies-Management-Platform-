<?php
require 'auth_check_admin.php';

$filter_date = trim($_GET['filter_date'] ?? '');

$sql = "SELECT id, flatno, visitor_name, vehicle_no, start_date, start_time, end_date, end_time, slots, total_price
        FROM visitor_parking WHERE 1=1";
$params = [];
$types  = '';
if ($filter_date !== '') {
    $sql   .= " AND start_date = ?";
    $params[] = $filter_date;
    $types    = 's';
}
$sql .= " ORDER BY id ASC";
$stmt = $conn->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$res = $stmt->get_result();

$page_title = "Visitor Parking Admin";
$extra_head = '
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<style>
.flatpickr-calendar { background:#fff; border-radius:12px !important; box-shadow:0 8px 30px rgba(0,0,0,0.15) !important; border:none !important; }
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
.filter-form { display:flex; justify-content:center; align-items:center; gap:12px; margin-bottom:30px; flex-wrap:wrap; }
.date-wrapper { position:relative; display:inline-block; }
.date-wrapper i { position:absolute; right:12px; top:50%; transform:translateY(-50%); color:#19B3D3; pointer-events:none; }
.filter-input { background:#fff; border:2px solid #19B3D3; border-radius:10px; padding:10px 40px 10px 15px; font-size:15px; outline:none; cursor:pointer; width:200px; }
.btn-filter { padding:11px 20px; background:#0B87A6; color:white; border:none; border-radius:10px; font-weight:700; cursor:pointer; transition:0.3s; }
.btn-filter:hover { background:#0B3A45; }
.btn-reset  { padding:11px 20px; background:#19B3D3; color:white; text-decoration:none; border-radius:10px; font-weight:700; transition:0.3s; }
.btn-reset:hover { background:#0B87A6; }
</style>';

require 'header_admin.php';
require 'sidebar_admin.php';
?>

<div class="main-container" id="main-container">
    <h2 class="page-title">Visitor Parking Bookings</h2>

    <form method="GET" class="filter-form" id="filterForm">
        <div class="date-wrapper">
            <input type="text" id="filter_date_input" name="filter_date"
                   class="filter-input" placeholder="Filter by date" readonly
                   value="<?= htmlspecialchars($filter_date) ?>">
            <i class="fas fa-calendar-alt"></i>
        </div>
        <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filter</button>
        <a href="visitorparking_admin.php" class="btn-reset"><i class="fas fa-times"></i> Reset</a>
    </form>

    <div class="table-container">
        <table class="content-table">
            <thead>
                <tr><th>ID</th><th>Flat No</th><th>Visitor</th><th>Vehicle</th><th>Start</th><th>End</th><th>Slots</th><th>Billed</th></tr>
            </thead>
            <tbody>
            <?php if ($res && $res->num_rows > 0):
                while ($row = $res->fetch_assoc()):
                    $sd = ($row['start_date'] && $row['start_date'] !== '0000-00-00') ? date("d M Y", strtotime($row['start_date'])) : '-';
                    $ed = ($row['end_date']   && $row['end_date']   !== '0000-00-00') ? date("d M Y", strtotime($row['end_date']))   : '-';
            ?>
                <tr>
                    <td style="color:#888;"><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['flatno']) ?></td>
                    <td><strong><?= htmlspecialchars($row['visitor_name']) ?></strong></td>
                    <td><?= htmlspecialchars($row['vehicle_no'] ?? '-') ?></td>
                    <td><strong><?= $sd ?></strong><br><small style="color:#888;"><?= htmlspecialchars($row['start_time']) ?></small></td>
                    <td><strong><?= $ed ?></strong><br><small style="color:#888;"><?= htmlspecialchars($row['end_time']) ?></small></td>
                    <td><span style="background:#eef;padding:4px 12px;border-radius:15px;font-weight:bold;"><?= $row['slots'] ?></span></td>
                    <td style="font-weight:700;color:#0B3A45;">₹<?= number_format($row['total_price'] ?? 0, 2) ?></td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="8" style="text-align:center;padding:40px;color:#999;">
                    <?= $filter_date ? "No bookings found for " . htmlspecialchars($filter_date) : "No visitor parking bookings yet." ?>
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$extra_js = "
flatpickr('#filter_date_input', {
    dateFormat: 'Y-m-d',
    onChange: function(selectedDates, dateStr){
        document.getElementById('filterForm').submit();
    }
});
";
require 'footer.php';
?>
