<?php
require 'auth_check_admin.php';

$page_title = "View Payments";
require 'header_admin.php';
require 'sidebar_admin.php';

// 1. Capture filter values from URL
$filter_month = $_GET['filter_month'] ?? '';
$filter_flat  = $_GET['filter_flat'] ?? '';
$filter_year  = $_GET['filter_year'] ?? '';
?>

<div class="main-container" id="main-container">
    <h2 class="page-title">Payment Ledger</h2>

    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin-bottom:20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
        <form method="GET" style="display:flex; gap:15px; flex-wrap:wrap; align-items:flex-end;">
            
            <div style="flex:1; min-width:110px;">
                <label style="font-size:12px; font-weight:700; color:#64748b; margin-bottom:5px; display:block;">YEAR</label>
                <select name="filter_year" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;">
                    <option value="">All Years</option>
                    <?php
                    $currentY = date('Y');
                    for($y = $currentY; $y >= 2024; $y--) {
                        $selected = ($filter_year == $y) ? 'selected' : '';
                        echo "<option value='$y' $selected>$y</option>";
                    }
                    ?>
                </select>
            </div>

            <div style="flex:1; min-width:130px;">
                <label style="font-size:12px; font-weight:700; color:#64748b; margin-bottom:5px; display:block;">MONTH</label>
                <select name="filter_month" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;">
                    <option value="">All Months</option>
                    <?php
                    $months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                    foreach($months as $m) {
                        $sel = ($filter_month == $m) ? 'selected' : '';
                        echo "<option value='$m' $sel>$m</option>";
                    }
                    ?>
                </select>
            </div>

            <div style="flex:1; min-width:130px;">
                <label style="font-size:12px; font-weight:700; color:#64748b; margin-bottom:5px; display:block;">FLAT NO.</label>
                <input type="text" name="filter_flat" value="<?= htmlspecialchars($filter_flat) ?>" placeholder="Ex: 101" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;">
            </div>

            <div style="display:flex; gap:10px;">
                <button type="submit" style="background:#0B87A6; color:white; border:none; padding:10px 20px; border-radius:6px; cursor:pointer; font-weight:600;">Filter</button>
                <a href="?" style="background:#f1f5f9; color:#475569; padding:10px 15px; border-radius:6px; text-decoration:none; font-size:14px; border:1px solid #cbd5e1;">Reset</a>
            </div>
        </form>
    </div>

    <?php
    // 2. Build the Dynamic SQL Query
    $sql = "SELECT * FROM payrecords WHERE 1=1";
    
    if (!empty($filter_year)) {
        $sql .= " AND YEAR(created_at) = '" . $conn->real_escape_string($filter_year) . "'";
    }
    if (!empty($filter_month)) {
        $sql .= " AND month_name = '" . $conn->real_escape_string($filter_month) . "'";
    }
    if (!empty($filter_flat)) {
        $sql .= " AND Flatno LIKE '%" . $conn->real_escape_string($filter_flat) . "%'";
    }
    
    $sql .= " ORDER BY ID DESC";
    $res = $conn->query($sql);

    // 3. Calculate Summary for the Filtered Results
    $total_amt = 0;
    $data = [];
    while($row = $res->fetch_assoc()){
        $data[] = $row;
        $total_amt += $row['Amount'];
    }
    ?>

    <div style="margin-bottom:15px; display:flex; justify-content:space-between; align-items:center; background:#f8fafc; padding:10px 15px; border-radius:8px; border:1px solid #e2e8f0;">
        <span style="color:#64748b; font-size:14px;">Showing <strong><?= count($data) ?></strong> Records</span>
        <span style="color:#0B87A6; font-size:16px; font-weight:700;">Total Collection: ₹<?= number_format($total_amt, 2) ?></span>
    </div>

    <div class="table-container">
        <table class="content-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Date/Time</th>
                    <th>Month</th>
                    <th>Resident</th>
                    <th>Flat</th>
                    <th>Transaction ID</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php 
            $i = 1;
            if(count($data) > 0):
                foreach($data as $row): 
                    $sc = ($row['Status'] === 'Success') ? 'badge-success' : 'badge-pending';
            ?>
                <tr>
                    <td style="color:#94A3B8;"><?= $i++ ?></td>
                    <td style="font-size:12px;"><?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></td>
                    <td><strong><?= htmlspecialchars($row['month_name']) ?></strong></td>
                    <td><?= htmlspecialchars($row['Name']) ?></td>
                    <td><?= htmlspecialchars($row['Flatno']) ?></td>
                    <td style="font-family:monospace;font-size:12px;color:#5469d4;"><?= htmlspecialchars($row['utr_number'] ?? '-') ?></td>
                    <td style="font-weight:700;">₹<?= number_format($row['Amount'],2) ?></td>
                    <td><span class="badge <?= $sc ?>"><?= htmlspecialchars($row['Status']) ?></span></td>
                </tr>
            <?php 
                endforeach; 
            else:
            ?>
                <tr><td colspan="8" style="text-align:center; padding:30px; color:#94A3B8;">No records found for the selected filters.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require 'footer.php'; ?>