<?php
require 'auth_check.php';
require_once __DIR__ . '/notifications_helper.php';
$user = $_SESSION['username'];
$flat = $_SESSION['flatno'] ?? '';
if (!$flat) {
    $st = $conn->prepare("SELECT Flatno FROM registration WHERE Username=?");
    $st->bind_param("s", $user); $st->execute();
    $fr = $st->get_result()->fetch_assoc();
    $flat = $fr['Flatno'] ?? 'N/A';
    $_SESSION['flatno'] = $flat;
}

// Fetch dynamic parking rate from society_details
$sec_stmt = $conn->query("SELECT visitor_parking_rate FROM society_details LIMIT 1");
$sec_data = $sec_stmt ? $sec_stmt->fetch_assoc() : null;
$parking_rate = (float)($sec_data['visitor_parking_rate'] ?? 50);

$page_title = "Visitor Parking";
$extra_head = '
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<style>
/* Flatpickr theme */
.flatpickr-calendar{background:#fff;border-radius:12px !important;box-shadow:0 8px 30px rgba(0,0,0,0.15) !important;border:none !important;font-family:"Segoe UI",Tahoma,sans-serif;}
.flatpickr-months{background:#0B3A45;border-radius:12px 12px 0 0;padding:6px 0;}
.flatpickr-months .flatpickr-month{color:white;fill:white;}
.flatpickr-current-month span.cur-month,.flatpickr-current-month input.cur-year{color:white !important;font-weight:700;}
.flatpickr-months .flatpickr-prev-month svg,.flatpickr-months .flatpickr-next-month svg{fill:white !important;}
.flatpickr-months .flatpickr-prev-month:hover,.flatpickr-months .flatpickr-next-month:hover{background:rgba(255,255,255,0.1) !important;}
.flatpickr-weekdays{background:#0B3A45;}
span.flatpickr-weekday{background:#0B3A45;color:#19B3D3 !important;font-weight:700;}
.flatpickr-day{border-radius:8px !important;color:#333;}
.flatpickr-day:hover{background:#e6f7fa !important;border-color:#19B3D3 !important;}
.flatpickr-day.selected,.flatpickr-day.selected:hover{background:#19B3D3 !important;border-color:#19B3D3 !important;color:white !important;}
.flatpickr-day.today{border-color:#19B3D3 !important;color:#0B3A45 !important;font-weight:700;}
.flatpickr-day.today.selected{color:white !important;}
.flatpickr-time input:hover,.flatpickr-time input:focus{background:#e6f7fa !important;}

/* AM/PM Toggle Enhancements */
.flatpickr-time .flatpickr-am-pm {
    cursor: pointer !important;
    font-weight: 800 !important;
    color: #19B3D3 !important;
    transition: 0.2s;
    user-select: none;
}
.flatpickr-time .flatpickr-am-pm:hover {
    color: #0B3A45 !important;
    background: #e6f7fa !important;
}

/* Layout */
.parking-grid{display:grid;grid-template-columns:1.4fr 1fr;gap:30px;margin-bottom:40px;}
.pcard{background:white;padding:35px;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.08);border-top:5px solid #19B3D3;}
.pcard h2{color:#0B3A45;margin-bottom:20px;font-size:22px;display:flex;align-items:center;gap:10px;}
.row-2{display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:15px;}
.form-group label{display:block;margin-bottom:8px;font-weight:600;color:#444;font-size:14px;}
.date-wrapper{position:relative;}
.date-wrapper i{position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#19B3D3;pointer-events:none;font-size:14px;}
.input-box{width:100%;padding:11px 14px;border-radius:8px;border:1.5px solid #ddd;background:#f9f9f9;font-size:14px;outline:none;transition:0.3s;font-family:inherit;cursor:pointer;}
.input-box:focus,.input-box:hover{border-color:#19B3D3;background:white;}
.input-box[readonly]{cursor:pointer;}

/* Cinema Slot Map */
.slot-section{margin-top:5px;}
.slot-legend{display:flex;gap:15px;margin-bottom:14px;flex-wrap:wrap;}
.slot-legend-item{display:flex;align-items:center;gap:6px;font-size:12px;color:#666;font-weight:600;}
.legend-dot{width:14px;height:14px;border-radius:4px;flex-shrink:0;}
.legend-free{background:#e6f7fa;border:2px solid #19B3D3;}
.legend-occupied{background:#f0f0f0;border:2px solid #ccc;}
.legend-selected{background:#0B3A45;border:2px solid #0B3A45;}

.slot-map-label{background:#555;color:white;text-align:center;padding:8px;border-radius:6px;font-size:11px;font-weight:700;letter-spacing:2px;margin:12px 0;}

.parking-slot-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;}
.slot-btn{padding:14px 8px;border-radius:10px;border:2px solid #19B3D3;background:#e6f7fa;
    color:#0B3A45;font-weight:700;font-size:13px;cursor:pointer;transition:all 0.2s;
    text-align:center;position:relative;user-select:none;}
.slot-btn:hover:not(.slot-occupied){background:#19B3D3;color:white;transform:translateY(-2px);box-shadow:0 4px 12px rgba(25,179,211,0.35);}
.slot-btn.slot-selected{background:#0B3A45;border-color:#0B3A45;color:white;box-shadow:0 4px 12px rgba(11,58,69,0.4);transform:translateY(-2px);}
.slot-btn.slot-selected::after{content:"✓";position:absolute;top:-8px;right:-8px;background:#19B3D3;color:white;border-radius:50%;width:20px;height:20px;font-size:11px;font-weight:900;display:flex;align-items:center;justify-content:center;}
.slot-btn.slot-occupied{background:#f0f0f0;border-color:#ddd;color:#bbb;cursor:not-allowed;opacity:0.7;}
.slot-btn.slot-occupied::before{content:"Taken";display:block;font-size:9px;color:#e74c3c;font-weight:800;text-transform:uppercase;margin-bottom:2px;}
.slot-btn.slot-occupied span{text-decoration:line-through;}
.slot-time{font-size:11px;font-weight:400;opacity:0.8;display:block;margin-top:2px;}
.slot-placeholder{text-align:center;padding:30px;border:2px dashed #ddd;border-radius:10px;color:#bbb;font-size:14px;}
.selected-display{display:none;margin-top:12px;background:#e6f7fa;border-radius:8px;padding:12px 16px;font-size:14px;color:#0B3A45;font-weight:600;border-left:4px solid #19B3D3;}

/* Price box */
.price-box{background:#0B3A45;border-radius:10px;padding:16px 18px;margin-top:10px;display:none;}
.price-box p{color:#b0c4cc;font-size:13px;margin:0 0 4px;}
.price-amount{color:#19B3D3;font-size:26px;font-weight:800;}

.btn-submit{width:100%;padding:14px;background:#19B3D3;border:none;border-radius:8px;font-size:16px;font-weight:700;color:white;cursor:pointer;transition:0.3s;margin-top:10px;}
.btn-submit:hover{background:#0B87A6;transform:translateY(-1px);}
.btn-submit:disabled{background:#ccc;cursor:not-allowed;transform:none;}

/* Info card */
.info-card{background:#0B3A45;color:white;padding:30px;border-radius:12px;display:flex;flex-direction:column;gap:15px;}
.info-card h3{color:#19B3D3;font-size:18px;margin-bottom:5px;}
.rule-item{display:flex;align-items:flex-start;gap:10px;font-size:13.5px;opacity:0.9;line-height:1.4;}
.billing-note{background:#fdfaf0;border:1px solid #f9ebbe;padding:15px;border-radius:8px;margin-bottom:20px;}
.cancel-btn{background:#ff4757;color:white;border:none;padding:6px 12px;border-radius:4px;cursor:pointer;font-size:11px;font-weight:700;}
@media(max-width:1024px){.parking-grid{grid-template-columns:1fr;}}
@media(max-width:600px){.row-2{grid-template-columns:1fr;}.parking-slot-grid{grid-template-columns:repeat(2,1fr);}}
</style>';

require 'header_user.php';
require 'sidebar_user.php';
?>

<div class="main-container" id="main-container">
    <div class="parking-grid">
        <div class="pcard">
            <h2><i class="fas fa-ticket-alt"></i> Reserve Visitor Slot</h2>
            <div class="billing-note">
                <p style="font-size:13px;color:#856404;font-weight:600;"><i class="fas fa-info-circle"></i> Integrated Billing</p>
                <p style="font-size:12px;color:#856404;margin-top:4px;">Charges added to maintenance balance. Rate: <strong>₹<?= number_format($parking_rate, 2) ?>/hour per slot.</strong></p>
            </div>
            <form id="vpForm" action="visitorparking_submit.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="slots"      id="selectedSlotsCount" value="">
                <input type="hidden" name="start_date" id="hdStartDate" value="">
                <input type="hidden" name="end_date"   id="hdEndDate"   value="">
                <input type="hidden" name="start_time" id="hdStartTime" value="">
                <input type="hidden" name="end_time"   id="hdEndTime"   value="">

                <div class="row-2">
                    <div class="form-group"><label>Host Name</label><input class="input-box" value="<?= htmlspecialchars($user) ?>" readonly name="username"></div>
                    <div class="form-group"><label>Flat No</label><input class="input-box" value="<?= htmlspecialchars($flat) ?>" readonly name="flatno"></div>
                </div>
                <div class="row-2">
                    <div class="form-group"><label>Visitor Full Name</label><input class="input-box" name="visitor_name" placeholder="Enter visitor name" required></div>
                    <div class="form-group"><label>Vehicle Number</label><input class="input-box" name="vehicle_no" placeholder="e.g. MH12AB1234" required></div>
                </div>
                <div class="row-2">
                    <div class="form-group">
                        <label>Date Range</label>
                        <div style="display:flex;gap:8px;">
                            <div class="date-wrapper" style="flex:1;"><input class="input-box" id="sd" placeholder="Start date" readonly required><i class="fas fa-calendar-alt"></i></div>
                            <div class="date-wrapper" style="flex:1;"><input class="input-box" id="ed" placeholder="End date"   readonly required><i class="fas fa-calendar-alt"></i></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Time Range</label>
                        <div style="display:flex;gap:8px;">
                            <div class="date-wrapper" style="flex:1;"><input class="input-box" id="st" placeholder="Check in"  readonly required><i class="fas fa-clock"></i></div>
                            <div class="date-wrapper" style="flex:1;"><input class="input-box" id="et" placeholder="Check out" readonly required><i class="fas fa-clock"></i></div>
                        </div>
                    </div>
                </div>

                <div class="slot-section" id="slotSection" style="display:none;">
                    <label style="font-weight:600;color:#555;font-size:13.5px;display:block;margin-bottom:10px;">Select Parking Slot(s)</label>
                    <div class="slot-legend">
                        <div class="slot-legend-item"><div class="legend-dot legend-free"></div>Available</div>
                        <div class="slot-legend-item"><div class="legend-dot legend-occupied"></div>Taken</div>
                        <div class="slot-legend-item"><div class="legend-dot legend-selected"></div>Your Selection</div>
                    </div>
                    <div class="slot-map-label">⬆ ENTRANCE / EXIT ⬆</div>
                    <div class="parking-slot-grid" id="parkingSlotGrid">
                        <div class="slot-placeholder"><i class="fas fa-hand-pointer"></i><br>Select dates &amp; time above</div>
                    </div>
                    <div class="slot-map-label">⬆ ENTRANCE / EXIT ⬆</div>
                    <div class="selected-display" id="selectedDisplay">
                        <i class="fas fa-check-circle" style="color:#19B3D3;"></i>
                        <span id="selectedSlotText"></span>
                    </div>
                </div>

                <div class="price-box" id="priceBox">
                    <p>Estimated charge</p>
                    <div class="price-amount" id="priceDisplay">₹0</div>
                </div>
                <div id="priceEmpty" style="background:#f7f9fc;border-radius:10px;padding:14px;border:1.5px dashed #ddd;color:#aaa;font-size:13px;text-align:center;margin-top:10px;">
                    <i class="fas fa-calculator" style="margin-right:6px;"></i>Select slot to see price
                </div>

                <button type="submit" class="btn-submit" id="submitBtn" disabled>
                    <i class="fas fa-check-circle"></i> Confirm &amp; Add to Bill
                </button>
            </form>
        </div>

        <div class="info-card">
            <h3><i class="fas fa-info-circle"></i> Parking Guidelines</h3>
            <div class="rule-item"><i class="fas fa-money-bill-wave"></i><span>Rate: <strong>₹<?= number_format($parking_rate, 2) ?>/hour</strong> per slot.</span></div>
            <div class="rule-item" style="color:#ff9f43;"><i class="fas fa-lock"></i><span>Cancellations blocked within <strong>24 hours</strong> of arrival.</span></div>
            <div class="rule-item"><i class="fas fa-receipt"></i><span>Settle charges at month-end in Payments module.</span></div>
            <div class="rule-item"><i class="fas fa-id-card"></i><span>Display booking confirmation to security on arrival.</span></div>
            <div style="margin-top:auto;padding:15px;background:rgba(25,179,211,0.1);border-radius:8px;border-left:3px solid #19B3D3;">
                <p style="font-size:12px;color:#19B3D3;"><i class="fas fa-film"></i> <strong>Tip:</strong> You can quickly switch between AM & PM by pressing the <strong>'A'</strong> or <strong>'P'</strong> keys on your keyboard!</p>
            </div>
        </div>
    </div>

    <div class="table-container">
        <h3 style="margin-bottom:20px;color:#0B3A45;"><i class="fas fa-history"></i> Your Parking History</h3>
        <table class="content-table">
            <thead><tr><th>No</th><th>Visitor</th><th>Vehicle</th><th>Slots</th><th>From</th><th>Billed</th><th style="text-align:right;">Action</th></tr></thead>
            <tbody id="historyBody"><tr><td colspan="7" style="text-align:center;color:#aaa;padding:30px;">Loading...</td></tr></tbody>
        </table>
    </div>
</div>

<?php
$extra_js = "
var TOTAL_SLOTS = 10;
var PARKING_RATE = " . $parking_rate . ";
var selectedSlots = [];
var sdPicker, edPicker, stPicker, etPicker;

// Init flatpickr - Added syncOutMin to onChange events to prevent AM/PM locking
sdPicker = flatpickr('#sd', { dateFormat:'Y-m-d', minDate:'today', onChange: function(){ syncEndMin(); syncOutMin(); loadSlotMap(); } });
edPicker = flatpickr('#ed', { dateFormat:'Y-m-d', minDate:'today', onChange: function(){ syncOutMin(); loadSlotMap(); } });
stPicker = flatpickr('#st', { enableTime:true, noCalendar:true, dateFormat:'h:i K', time_24hr:false, minuteIncrement:30, onChange: function(){ syncOutMin(); updatePrice(); } });
etPicker = flatpickr('#et', { enableTime:true, noCalendar:true, dateFormat:'h:i K', time_24hr:false, minuteIncrement:30, onChange: updatePrice });

function syncEndMin(){ 
    var sd=document.getElementById('sd').value; 
    if(sd) edPicker.set('minDate',sd); 
}

function syncOutMin(){ 
    var st = document.getElementById('st').value; 
    var sd = document.getElementById('sd').value;
    var ed = document.getElementById('ed').value;
    
    // Only lock the End Time AM/PM toggle if the Start & End dates are the exact same day
    if(st && sd && ed && sd === ed) {
        etPicker.set('minTime', st); 
    } else {
        etPicker.set('minTime', null); // Remove restriction for different days so AM/PM unlocks
    }
}

async function loadSlotMap(){
    var sd=document.getElementById('sd').value;
    var ed=document.getElementById('ed').value;
    if(!sd||!ed) return;

    document.getElementById('slotSection').style.display='block';
    var grid=document.getElementById('parkingSlotGrid');
    grid.innerHTML='<div class=\"slot-placeholder\"><i class=\"fas fa-spinner fa-spin\"></i> Checking availability...</div>';
    selectedSlots=[];
    updateSubmitBtn();
    updateSelectedDisplay();

    try {
        var data = await fetch('visitorparking_check.php?sd='+sd+'&ed='+ed).then(r=>r.json());
        var occupied = TOTAL_SLOTS - data.available;
        grid.innerHTML='';

        for(var i=1; i<=TOTAL_SLOTS; i++){
            var isOccupied = i <= occupied;
            var btn = document.createElement('button');
            btn.type='button';
            btn.className='slot-btn'+(isOccupied?' slot-occupied':'');
            btn.dataset.slot = i;
            btn.innerHTML='<span>V'+i+'</span>';
            if(!isOccupied){
                btn.addEventListener('click', function(){ toggleSlot(this); });
            }
            grid.appendChild(btn);
        }
    } catch(e){
        grid.innerHTML='<div class=\"slot-placeholder\">Error loading slots.</div>';
    }
}

function toggleSlot(btn){
    var s = parseInt(btn.dataset.slot);
    var idx = selectedSlots.indexOf(s);
    if(idx === -1){
        selectedSlots.push(s);
        btn.classList.add('slot-selected');
    } else {
        selectedSlots.splice(idx,1);
        btn.classList.remove('slot-selected');
    }
    document.getElementById('selectedSlotsCount').value = selectedSlots.length;
    updateSubmitBtn();
    updateSelectedDisplay();
    updatePrice();
    // Show price box area immediately when slots selected
    var priceEmpty = document.getElementById('priceEmpty');
    var priceBox   = document.getElementById('priceBox');
    if(selectedSlots.length === 0){
        priceBox.style.display='none';
        priceEmpty.style.display='block';
    }
}

function updateSelectedDisplay(){
    var disp = document.getElementById('selectedDisplay');
    var txt  = document.getElementById('selectedSlotText');
    if(selectedSlots.length > 0){
        var sorted = selectedSlots.slice().sort(function(a,b){return a-b;});
        txt.textContent = selectedSlots.length+' slot'+(selectedSlots.length>1?'s':'')+' selected: V'+sorted.join(', V');
        disp.style.display='block';
    } else {
        disp.style.display='none';
    }
}

function updateSubmitBtn(){
    var sd=document.getElementById('sd').value;
    var ed=document.getElementById('ed').value;
    var st=document.getElementById('st').value;
    var et=document.getElementById('et').value;
    var ok = sd&&ed&&st&&et&&selectedSlots.length>0;
    document.getElementById('submitBtn').disabled = !ok;
    // Sync hidden fields
    document.getElementById('hdStartDate').value = sd;
    document.getElementById('hdEndDate').value   = ed;
    document.getElementById('hdStartTime').value = st;
    document.getElementById('hdEndTime').value   = et;
}

function updatePrice(){
    var sd  = document.getElementById('sd').value;
    var ed  = document.getElementById('ed').value;
    var stv = document.getElementById('st').value;
    var etv = document.getElementById('et').value;
    var slots = selectedSlots.length;
    var box   = document.getElementById('priceBox');
    var empty = document.getElementById('priceEmpty');
    var disp  = document.getElementById('priceDisplay');

    if(sd && ed && stv && etv && slots > 0){
        // Parse using explicit format to avoid AM/PM issues
        var parseTime = function(dateStr, timeStr){
            if(!timeStr) return null;
            var t = timeStr.toUpperCase();
            var match = t.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/);
            if(match){
                var h = parseInt(match[1]);
                var m = parseInt(match[2]);
                if(match[3]==='PM' && h!==12) h+=12;
                if(match[3]==='AM' && h===12) h=0;
                var dt = new Date(dateStr);
                dt.setHours(h, m, 0, 0);
                return dt;
            }
            return new Date(dateStr + ' ' + timeStr);
        };
        var startDT = parseTime(sd, stv);
        var endDT   = parseTime(ed, etv);
        if(startDT && endDT){
            var diffMs = endDT - startDT;
            if(diffMs > 0){
                var hours = diffMs / 3600000;
                var total = Math.round(hours * PARKING_RATE * slots * 100) / 100;
                disp.textContent = '₹' + total.toLocaleString('en-IN', {minimumFractionDigits:2});
                box.style.display   = 'block';
                empty.style.display = 'none';
                return;
            }
        }
    }
    box.style.display   = 'none';
    empty.style.display = 'block';
}

document.getElementById('vpForm').addEventListener('submit', function(e){
    var sd=document.getElementById('sd').value;
    var ed=document.getElementById('ed').value;
    var st=document.getElementById('st').value;
    var et=document.getElementById('et').value;
    if(!sd||!ed||!st||!et||selectedSlots.length===0){
        e.preventDefault();
        alert('Please fill all fields and select at least one parking slot.');
    }
});

// History
function cancelBooking(id){
    if(confirm('Cancel this booking? Charges will be removed from your bill.')){
        fetch('visitorparking_cancel.php?id='+id)
        .then(r=>r.json()).then(function(res){
            if(res.success){alert('Cancelled! Balance updated.');loadHistory();}
            else alert(res.message||'Action failed.');
        });
    }
}
function loadHistory(){
    fetch('visitorparking_list.php').then(r=>r.json()).then(function(rows){
        var tb=document.getElementById('historyBody');
        tb.innerHTML='';
        if(!rows||!rows.length){
            tb.innerHTML='<tr><td colspan=\"7\" style=\"text-align:center;padding:30px;color:#999;\">No bookings yet.</td></tr>';
            return;
        }
        rows.forEach(function(r,index){
            var act=r.can_cancel
                ?'<button onclick=\"cancelBooking('+r.id+')\" class=\"cancel-btn\">Cancel</button>'
                :'<span style=\"font-size:11px;color:#999;font-style:italic;\">Locked</span>';
            tb.innerHTML+='<tr><td style=\"color:#888;\">'+(index+1)+'</td><td style=\"font-weight:600;\">'+r.visitor_name+'</td><td>'+r.vehicle_no+'</td><td>'+r.slots+'</td><td>'+formatDate(r.start_date)+' '+r.start_time+'</td><td style=\"font-weight:700;\">₹'+r.total_price+'</td><td style=\"text-align:right;\">'+act+'</td></tr>';
        });
    });
}
function formatDate(d){
    if(!d) return '-';
    var p=d.split('-');
    return p[2]+'/'+p[1]+'/'+p[0];
}
document.addEventListener('DOMContentLoaded', loadHistory);
";
require 'footer.php';
?>