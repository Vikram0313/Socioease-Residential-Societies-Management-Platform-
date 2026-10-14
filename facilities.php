<?php
require 'auth_check.php';
require_once __DIR__ . '/notifications_helper.php';
$user = $_SESSION['username'];

$facilities = [];
$res = $conn->query("SELECT facility_id, facility_name, description, slot_type FROM facilities ORDER BY facility_name ASC");
while ($row = $res->fetch_assoc()) { $facilities[] = $row; }

$page_title = "Facility Booking";
$extra_head = '
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<style>
/* ── Flatpickr theme ── */
.flatpickr-calendar { background:#fff; border-radius:12px !important; box-shadow:0 8px 30px rgba(0,0,0,0.15) !important; border:none !important; }
.flatpickr-months { background:#0B3A45; border-radius:12px 12px 0 0; padding:6px 0; }
.flatpickr-months .flatpickr-month { color:white; fill:white; }
.flatpickr-current-month span.cur-month,.flatpickr-current-month input.cur-year { color:white !important; font-weight:700; }
.flatpickr-months .flatpickr-prev-month svg,.flatpickr-months .flatpickr-next-month svg { fill:white !important; }
.flatpickr-months .flatpickr-prev-month:hover,.flatpickr-months .flatpickr-next-month:hover { background:rgba(255,255,255,0.1) !important; }
.flatpickr-weekdays { background:#0B3A45; }
span.flatpickr-weekday { background:#0B3A45; color:#19B3D3 !important; font-weight:700; }
.flatpickr-day { border-radius:8px !important; color:#333; }
.flatpickr-day:hover { background:#e6f7fa !important; border-color:#19B3D3 !important; }
.flatpickr-day.selected,.flatpickr-day.selected:hover { background:#19B3D3 !important; border-color:#19B3D3 !important; color:white !important; }
.flatpickr-day.today { border-color:#19B3D3 !important; color:#0B3A45 !important; font-weight:700; }
.flatpickr-day.today.selected { color:white !important; }

/* ── Layout ── */
.content-grid { display:grid; grid-template-columns:1.6fr 1fr; gap:25px; max-width:1350px; margin:auto; align-items:start; }
.booking-card { background:white; padding:30px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.05); border-top:5px solid #19B3D3; }
.booking-card h2 { color:#0B3A45; margin-bottom:25px; font-size:20px; display:flex; align-items:center; gap:12px; }
.form-group { margin-bottom:20px; }
.form-group label { display:block; margin-bottom:8px; font-weight:600; color:#555; font-size:13.5px; }
.date-wrapper { position:relative; }
.date-wrapper i { position:absolute; right:12px; top:50%; transform:translateY(-50%); color:#19B3D3; pointer-events:none; }
.input-box, .form-select { width:100%; padding:11px 14px; border-radius:8px; border:1.5px solid #e1e1e1; background:#f9f9f9; font-size:14px; outline:none; font-family:inherit; cursor:pointer; transition:0.3s; }
.input-box:hover,.input-box:focus,.form-select:focus { border-color:#19B3D3; background:white; }

/* ── Cinema Slot Grid ── */
.slot-section { margin-top:5px; }
.slot-legend { display:flex; gap:18px; margin-bottom:14px; flex-wrap:wrap; }
.slot-legend-item { display:flex; align-items:center; gap:6px; font-size:12px; color:#666; font-weight:600; }
.legend-dot { width:14px; height:14px; border-radius:4px; flex-shrink:0; }
.legend-available { background:#e6f7fa; border:2px solid #19B3D3; }
.legend-booked    { background:#f0f0f0; border:2px solid #ccc; }
.legend-selected  { background:#0B3A45; border:2px solid #0B3A45; }

.slot-grid { display:grid; gap:10px; }
.slot-grid.grid-3 { grid-template-columns: repeat(3,1fr); }
.slot-grid.grid-4 { grid-template-columns: repeat(4,1fr); }

.slot-btn {
    padding:14px 8px; border-radius:10px; border:2px solid #19B3D3;
    background:#e6f7fa; color:#0B3A45; font-weight:700; font-size:13px;
    cursor:pointer; transition:all 0.2s; text-align:center;
    position:relative; user-select:none;
}
.slot-btn:hover:not(.slot-booked) {
    background:#19B3D3; color:white; transform:translateY(-2px);
    box-shadow:0 4px 12px rgba(25,179,211,0.35);
}
.slot-btn.slot-selected {
    background:#0B3A45; border-color:#0B3A45; color:white;
    box-shadow:0 4px 12px rgba(11,58,69,0.4);
    transform:translateY(-2px);
}
.slot-btn.slot-selected::after {
    content:"✓"; position:absolute; top:-8px; right:-8px;
    background:#19B3D3; color:white; border-radius:50%;
    width:20px; height:20px; font-size:11px; font-weight:900;
    display:flex; align-items:center; justify-content:center;
}
.slot-btn.slot-booked {
    background:#f0f0f0; border-color:#ddd; color:#bbb;
    cursor:not-allowed; opacity:0.7;
}
.slot-btn.slot-booked span.slot-label { text-decoration:line-through; }
.slot-btn.slot-booked::before {
    content:"Booked"; display:block; font-size:9px;
    color:#e74c3c; font-weight:800; text-transform:uppercase; margin-bottom:2px;
    text-decoration:none !important;
}

.slot-time { font-size:11px; font-weight:400; opacity:0.8; display:block; margin-top:2px; }

.slot-loading { text-align:center; padding:30px; color:#aaa; font-size:14px; }
.slot-placeholder {
    text-align:center; padding:35px 20px; border:2px dashed #ddd;
    border-radius:10px; color:#bbb; font-size:14px;
}

.btn-submit { width:100%; padding:14px; background:#19B3D3; border:none; border-radius:8px; font-size:16px; font-weight:700; color:white; cursor:pointer; transition:0.2s; margin-top:5px; }
.btn-submit:hover { background:#0B87A6; transform:translateY(-1px); }
.btn-submit:disabled { background:#ccc; cursor:not-allowed; transform:none; }

/* ── Info panel ── */
.info-panel { background:#1a333d; color:white; padding:30px; border-radius:12px; }
.info-panel h3 { color:#19B3D3; margin-bottom:20px; font-size:18px; display:flex; align-items:center; gap:10px; }
.info-item { display:flex; gap:15px; margin-bottom:18px; }
.info-item i { color:#19B3D3; font-size:14px; margin-top:3px; }
.info-item p { font-size:13px; line-height:1.6; color:#cfd8dc; }

/* ── History table ── */
.table-section { margin-top:40px; max-width:1350px; margin-left:auto; margin-right:auto; }
.table-card { background:white; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.05); overflow:hidden; }
.table-header-box { padding:20px 25px; border-bottom:1px solid #f1f1f1; }
.table-header-box h2 { font-size:20px; color:#0B3A45; display:flex; align-items:center; gap:12px; margin:0; }
.content-table th { background:#f8f9fa; padding:15px 25px; font-size:13px; text-align:left; color:#333; font-weight:700; text-transform:uppercase; }
.content-table td { padding:15px 25px; font-size:14px; border-top:1px solid #f1f1f1; text-align:left; }
.cancel-btn { background:#ff4757; color:white; border:none; padding:6px 14px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:700; }
.locked-text { font-size:12px; color:#aaa; font-style:italic; }

@media(max-width:1024px){ .content-grid{grid-template-columns:1fr;} }
@media(max-width:480px){ .slot-grid.grid-3,.slot-grid.grid-4{grid-template-columns:repeat(2,1fr);} }
</style>';

require 'header_user.php';
require 'sidebar_user.php';
?>

<div class="main-container" id="main-container">
    <div class="content-grid">

        <!-- BOOKING FORM -->
        <div class="booking-card">
            <h2><i class="fas fa-calendar-check"></i> Book a Facility</h2>
            <form action="facility_submit.php" method="POST" id="bookingForm">
                <?= csrf_field() ?>

                <!-- Hidden field for selected slot -->
                <input type="hidden" name="slot_id"     id="selectedSlotId"     value="">
                <input type="hidden" name="facility_id" id="selectedFacilityId" value="">

                <div class="form-group">
                    <label>Select Facility</label>
                    <select id="facilitySelect" class="form-select" required>
                        <option value="" disabled selected>Choose a facility...</option>
                        <?php foreach ($facilities as $f): ?>
                            <option value="<?= $f['facility_id'] ?>"
                                    data-slot="<?= htmlspecialchars($f['slot_type']) ?>">
                                <?= htmlspecialchars($f['facility_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Preferred Date</label>
                    <div class="date-wrapper">
                        <input type="text" name="booking_date" id="bookingDate"
                               class="input-box" required placeholder="Select a date" readonly>
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>

                <!-- Cinema-style slot picker -->
                <div class="form-group slot-section" id="slotSection" style="display:none;">
                    <label>Select Time Slot</label>
                    <div class="slot-legend">
                        <div class="slot-legend-item"><div class="legend-dot legend-available"></div> Available</div>
                        <div class="slot-legend-item"><div class="legend-dot legend-booked"></div> Booked</div>
                        <div class="slot-legend-item"><div class="legend-dot legend-selected"></div> Your Selection</div>
                    </div>
                    <div id="slotGrid" class="slot-grid grid-3">
                        <div class="slot-placeholder"><i class="fas fa-hand-pointer"></i><br>Select a facility and date above</div>
                    </div>
                    <div id="selectedSlotDisplay" style="display:none;margin-top:12px;background:#e6f7fa;border-radius:8px;padding:12px 16px;font-size:14px;color:#0B3A45;font-weight:600;border-left:4px solid #19B3D3;">
                        <i class="fas fa-check-circle" style="color:#19B3D3;"></i>
                        Selected: <span id="selectedSlotName"></span>
                    </div>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn" disabled>
                    <i class="fas fa-check-circle"></i> Confirm Booking
                </button>
            </form>
        </div>

        <!-- INFO PANEL -->
        <div class="info-panel">
            <h3><i class="fas fa-info-circle"></i> Booking Rules</h3>
            <div class="info-item"><i class="fas fa-clock"></i><p>Bookings are first-come, first-served.</p></div>
            <div class="info-item"><i class="fas fa-calendar-times"></i><p><strong>Cancellation:</strong> Must cancel at least <strong>24 hours</strong> before the slot starts.</p></div>
            <div class="info-item"><i class="fas fa-user-shield"></i><p>Maintain cleanliness. Damage will be billed to your flat account.</p></div>
            <div class="info-item"><i class="fas fa-id-card"></i><p>Carry your resident ID for entry verification.</p></div>
            <div class="info-item"><i class="fas fa-broom"></i><p>Clear the facility of all trash after your session.</p></div>
            <div style="margin-top:20px;padding:15px;background:rgba(25,179,211,0.1);border-radius:8px;border-left:3px solid #19B3D3;">
                <p style="font-size:12px;color:#19B3D3;">
                    <i class="fas fa-film"></i>
                    <strong>Tip:</strong> Booked slots are shown blurred like cinema seats — only available slots can be selected.
                </p>
            </div>
        </div>
    </div>

    <!-- HISTORY TABLE -->
    <div class="table-section">
        <div class="table-card">
            <div class="table-header-box">
                <h2><i class="fas fa-history"></i> Your Booking History</h2>
            </div>
            <table class="content-table">
                <thead>
                    <tr><th>ID</th><th>Facility</th><th>Date</th><th>Slot</th><th>Status</th><th style="text-align:right;">Action</th></tr>
                </thead>
                <tbody id="myBookingsBody">
                    <tr><td colspan="6" style="text-align:center;padding:30px;color:#aaa;">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$extra_js = "
// ── Slot data ──────────────────────────────────────────────
var slotDefs = {
    '6hour': [
        {id:1, label:'Morning',   time:'06:00 AM - 12:00 PM'},
        {id:2, label:'Afternoon', time:'12:00 PM - 06:00 PM'},
        {id:3, label:'Evening',   time:'06:00 PM - 12:00 AM'}
    ],
    '2hour': [
        {id:4,  label:'Slot 1', time:'6AM - 8AM'},
        {id:5,  label:'Slot 2', time:'8AM - 10AM'},
        {id:6,  label:'Slot 3', time:'10AM - 12PM'},
        {id:7,  label:'Slot 4', time:'12PM - 2PM'},
        {id:8,  label:'Slot 5', time:'2PM - 4PM'},
        {id:9,  label:'Slot 6', time:'4PM - 6PM'},
        {id:10, label:'Slot 7', time:'6PM - 8PM'},
        {id:11, label:'Slot 8', time:'8PM - 10PM'}
    ]
};

var selectedSlotId   = null;
var selectedSlotName = '';

// ── Flatpickr ──────────────────────────────────────────────
flatpickr('#bookingDate', {
    dateFormat: 'Y-m-d',
    minDate: 'today',
    onChange: loadSlots
});

document.getElementById('facilitySelect').addEventListener('change', function(){
    document.getElementById('selectedFacilityId').value = this.value;
    resetSlotSelection();
    loadSlots();
});

// ── Load + render cinema-style slots ──────────────────────
async function loadSlots() {
    var facId = document.getElementById('facilitySelect').value;
    var date  = document.getElementById('bookingDate').value;
    if (!facId || !date) return;

    var selOpt   = document.querySelector('#facilitySelect option[value=\"'+facId+'\"]');
    var slotType = selOpt ? selOpt.dataset.slot : '2hour';
    var defs     = slotDefs[slotType] || slotDefs['2hour'];
    var gridCols = slotType === '6hour' ? 'grid-3' : 'grid-4';

    var grid = document.getElementById('slotGrid');
    document.getElementById('slotSection').style.display = 'block';
    grid.innerHTML = '<div class=\"slot-loading\"><i class=\"fas fa-spinner fa-spin\"></i> Checking availability...</div>';
    grid.className = 'slot-grid ' + gridCols;
    resetSlotSelection();

    try {
        var booked = await fetch('facility_available_slots.php?facility_id='+facId+'&booking_date='+date).then(r=>r.json());

        grid.innerHTML = '';
        defs.forEach(function(s) {
            var isBooked = booked.includes(s.id);
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'slot-btn' + (isBooked ? ' slot-booked' : '');
            btn.dataset.slotId   = s.id;
            btn.dataset.slotName = s.label + ' (' + s.time + ')';
            btn.innerHTML = '<span class=\"slot-label\">' + s.label + '</span><span class=\"slot-time\">' + s.time + '</span>';
            if (!isBooked) {
                btn.addEventListener('click', function(){ selectSlot(this); });
            }
            grid.appendChild(btn);
        });

        // If previously selected slot is now booked, reset
        if (selectedSlotId) {
            var stillAvail = !booked.includes(parseInt(selectedSlotId));
            if (!stillAvail) resetSlotSelection();
        }

    } catch(e) {
        grid.innerHTML = '<div class=\"slot-placeholder\">Error loading slots. Please try again.</div>';
    }
}

function selectSlot(btn) {
    // Deselect previous
    document.querySelectorAll('.slot-btn.slot-selected').forEach(function(b){ b.classList.remove('slot-selected'); });

    btn.classList.add('slot-selected');
    selectedSlotId   = btn.dataset.slotId;
    selectedSlotName = btn.dataset.slotName;

    document.getElementById('selectedSlotId').value = selectedSlotId;
    document.getElementById('selectedSlotName').textContent = selectedSlotName;
    document.getElementById('selectedSlotDisplay').style.display = 'block';
    document.getElementById('submitBtn').disabled = false;
}

function resetSlotSelection() {
    selectedSlotId   = null;
    selectedSlotName = '';
    document.getElementById('selectedSlotId').value   = '';
    document.getElementById('selectedSlotDisplay').style.display = 'none';
    document.getElementById('submitBtn').disabled     = true;
    document.querySelectorAll('.slot-btn.slot-selected').forEach(function(b){ b.classList.remove('slot-selected'); });
}

// ── Form validation before submit ─────────────────────────
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    var facId  = document.getElementById('facilitySelect').value;
    var date   = document.getElementById('bookingDate').value;
    var slotId = document.getElementById('selectedSlotId').value;
    if (!facId || !date || !slotId) {
        e.preventDefault();
        alert('Please select a facility, date and a time slot before confirming.');
    }
});

// ── Booking history ────────────────────────────────────────
fetch('facility_list.php').then(r=>r.json()).then(function(rows){
    var tb = document.getElementById('myBookingsBody');
    if (!rows.length) {
        tb.innerHTML = '<tr><td colspan=\"6\" style=\"color:#888;text-align:center;padding:30px;\">No booking history found.</td></tr>';
        return;
    }
    tb.innerHTML = ''; // clear loading row before appending
    var today = new Date(); today.setHours(0,0,0,0);
    rows.forEach(function(r, index){
        var bd   = new Date(r.booking_date); bd.setHours(0,0,0,0);
        var diff = (bd - today) / 86400000;
        var act  = diff >= 1
            ? '<button onclick=\"cancelFacility('+r.id+')\" class=\"cancel-btn\">Cancel</button>'
            : '<span class=\"locked-text\">Locked</span>';
        var sc = r.status==='Approved'?'badge-success':r.status==='Rejected'?'badge-danger':'badge-pending';
        tb.innerHTML += '<tr>'
            + '<td style=\"color:#888;\">'+(index+1)+'</td>'
            + '<td style=\"font-weight:600;color:#0B3A45;\">'+r.facility_name+'</td>'
            + '<td>'+r.booking_date+'</td>'
            + '<td>'+r.slot+'</td>'
            + '<td><span class=\"badge '+sc+'\">'+r.status+'</span></td>'
            + '<td style=\"text-align:right;\">'+act+'</td>'
            + '</tr>';
    });
});

function cancelFacility(id) {
    if (confirm('Cancel this booking?')) {
        fetch('facility_cancel.php?id='+id).then(r=>r.json()).then(function(res){
            if (res.success) location.reload(); else alert(res.message);
        });
    }
}
";
require 'footer.php';
?>
