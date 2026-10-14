<?php
require 'auth_check_admin.php';

$sql = "SELECT fb.id, fb.user_name, fb.flatno, fb.booking_date, fb.slot_id, fb.status, f.facility_name
        FROM facility_bookings fb
        LEFT JOIN facilities f ON fb.facility_id = f.facility_id
        ORDER BY fb.id ASC";
$res = $conn->query($sql);

$page_title = "Facility Admin";
require 'header_admin.php';
require 'sidebar_admin.php';
?>
<style>
.status-badge { padding:6px 14px; border-radius:20px; font-weight:700; font-size:12px; color:white; }
.status-pending  { background:#f1c40f; color:#000; }
.status-approved { background:#28a745; }
.status-rejected { background:#dc3545; }
.action-link { padding:8px 15px; border-radius:8px; font-weight:700; cursor:pointer; border:none; font-size:12px; color:white; text-decoration:none; display:inline-block; margin:2px; }
.btn-approve { background:#0B87A6; }
.btn-reject  { background:#dc3545; }
</style>

<div class="main-container" id="main-container">
    <h2 class="page-title">Facility Booking Management</h2>

    <div class="filter-box">
        <input type="text" id="searchInput" class="search-input" placeholder="Search by name, flat, or facility...">
        <select id="statusFilter" class="filter-select">
            <option value="all">All Status</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
        </select>
    </div>

    <div class="table-container">
        <table class="content-table" id="bookingTable">
            <thead><tr><th>ID</th><th>Facility</th><th>User</th><th>Flat</th><th>Date</th><th>Slot</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php
            $slotMap = [1=>"6AM-12PM",2=>"12PM-6PM",3=>"6PM-12AM",4=>"6AM-8AM",5=>"8AM-10AM",6=>"10AM-12PM",7=>"12PM-2PM",8=>"2PM-4PM",9=>"4PM-6PM",10=>"6PM-8PM",11=>"8PM-10PM"];
            if ($res && $res->num_rows > 0):
                while ($b = $res->fetch_assoc()):
                    $slotText   = $slotMap[(int)$b['slot_id']] ?? 'N/A';
                    $statusClass = "status-" . strtolower($b['status']);
            ?>
                <tr>
                    <td><?= $b['id'] ?></td>
                    <td><strong><?= htmlspecialchars($b['facility_name']) ?></strong></td>
                    <td><?= htmlspecialchars($b['user_name']) ?></td>
                    <td><?= htmlspecialchars($b['flatno']) ?></td>
                    <td><?= htmlspecialchars($b['booking_date']) ?></td>
                    <td><?= $slotText ?></td>
                    <td><span class="status-badge <?= $statusClass ?>"><?= ucfirst($b['status']) ?></span></td>
                    <td>
                        <?php if (strtolower($b['status']) === 'pending'): ?>
                            <a href="facility_action.php?action=approve&id=<?= $b['id'] ?>" class="action-link btn-approve"
                               onclick="return confirm('Approve this booking?')">Approve</a>
                            <a href="facility_action.php?action=reject&id=<?= $b['id'] ?>"  class="action-link btn-reject"
                               onclick="return confirm('Reject this booking?')">Reject</a>
                        <?php else: ?>
                            <span style="color:#888;font-style:italic;font-size:12px;">No Action</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="8" style="text-align:center;padding:40px;color:#999;">No records found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$extra_js = "
var si=document.getElementById('searchInput');
var sf=document.getElementById('statusFilter');
var rows=document.querySelectorAll('#bookingTable tbody tr');
function filterTable(){
    var s=si.value.toLowerCase();
    var st=sf.value.toLowerCase();
    rows.forEach(function(r){
        var txt=r.innerText.toLowerCase();
        var rs=r.cells[6]?r.cells[6].innerText.toLowerCase():'';
        r.style.display=(txt.includes(s)&&(st==='all'||rs===st))?'':'none';
    });
}
si.addEventListener('keyup',filterTable);
sf.addEventListener('change',filterTable);
";
require 'footer.php';
?>
