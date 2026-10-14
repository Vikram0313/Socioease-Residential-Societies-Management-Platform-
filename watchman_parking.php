<?php
require 'auth_check_watchman.php';

$today   = date('Y-m-d');
$now_dt  = date('Y-m-d H:i:s'); // Current datetime for time comparison

// Only show bookings that are currently active (started but not yet ended)
// Convert 12hr time stored in DB to comparable format using STR_TO_DATE
$bookings = $conn->query(
    "SELECT vp.*, r.Username,
             CONCAT(vp.start_date, ' ', TIME_FORMAT(STR_TO_DATE(vp.start_time, '%h:%i %p'), '%H:%i:%s')) AS start_dt,
             CONCAT(vp.end_date,   ' ', TIME_FORMAT(STR_TO_DATE(vp.end_time,   '%h:%i %p'), '%H:%i:%s')) AS end_dt
     FROM visitor_parking vp
     LEFT JOIN registration r ON vp.flatno = r.Flatno
     WHERE vp.start_date <= '$today' AND vp.end_date >= '$today'
     HAVING start_dt <= '$now_dt' AND end_dt >= '$now_dt'
     ORDER BY vp.flatno ASC"
);

$occupied_slots = [];
$slot_data = []; // Flattened array: 1 entry per occupied slot
$booking_data = []; // Retained for the table UI

// 1. Process Booked Parking Slots
while ($b = $bookings->fetch_assoc()) {
    $booking_data[] = $b;
    for ($s = 1; $s <= $b['slots']; $s++) {
        $occupied_slots[] = count($occupied_slots) + 1;
        $slot_data[] = [
            'type' => 'booked',
            'vehicle_no' => $b['vehicle_no'],
            'flatno' => $b['Flatno'] ?? $b['flatno']
        ];
    }
}

// 2. Process Live Visitor Log (Walk-ins with vehicles currently inside)
$live_visitors = $conn->query("
    SELECT flat_no as flatno, visitor_name, vehicle_no, time_in 
    FROM visitor_log 
    WHERE time_out IS NULL AND vehicle_no != '' AND vehicle_no IS NOT NULL
");

while ($v = $live_visitors->fetch_assoc()) {
    $occupied_slots[] = count($occupied_slots) + 1;
    $hours_stayed = floor((time() - strtotime($v['time_in'])) / 3600);
    
    $slot_data[] = [
        'type' => ($hours_stayed > 8) ? 'overstay' : 'visitor',
        'vehicle_no' => $v['vehicle_no'],
        'flatno' => $v['flatno'],
        'hours' => $hours_stayed
    ];
}

$total_slots = 10;
$page_title  = "Parking View";
require 'header_watchman.php';
require 'sidebar_watchman.php';
?>
<style>
.parking-header{background:linear-gradient(135deg,#1a1a2e,#0f3460);color:white;padding:25px 30px;border-radius:12px;margin-bottom:25px;display:flex;justify-content:space-between;align-items:center;}
.parking-header h2{font-size:20px;margin-bottom:5px;}
.parking-header p{opacity:0.6;font-size:13px;}
.slots-stats{display:flex;gap:20px;}
.slot-stat{text-align:center;}
.slot-stat .num{font-size:28px;font-weight:800;}
.slot-stat .lbl{font-size:11px;opacity:0.7;}
.slot-stat.avail .num{color:#2ecc71;}
.slot-stat.occ .num{color:#e74c3c;}

/* Cinema parking grid */
.parking-map{background:white;border-radius:12px;padding:30px;box-shadow:0 4px 15px rgba(0,0,0,0.08);margin-bottom:30px;}
.parking-map h3{color:#0B3A45;margin-bottom:20px;font-size:18px;display:flex;align-items:center;gap:10px;}
.road{background:#555;height:30px;border-radius:6px;margin:15px 0;display:flex;align-items:center;justify-content:center;color:white;font-size:11px;font-weight:700;letter-spacing:2px;}
.slot-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;}
.slot-card{border-radius:10px;padding:14px 10px;text-align:center;border:2px solid;transition:0.3s;cursor:default;}
.slot-card.available{background:#e6fffa;border-color:#27ae60;color:#1e8449;}
.slot-card.occupied {background:#fdf0ef;border-color:#e74c3c;color:#c0392b;opacity:0.8;}
.slot-card .slot-num{font-size:20px;font-weight:800;display:block;margin-bottom:4px;}
.slot-card .slot-lbl{font-size:10px;font-weight:700;text-transform:uppercase;}
.slot-card.occupied .slot-lbl{text-decoration:line-through;}
.slot-card .slot-vehicle{font-size:10px;margin-top:4px;font-weight:600;opacity:0.8;}

.legend{display:flex;gap:20px;margin-bottom:20px;flex-wrap:wrap;}
.leg-item{display:flex;align-items:center;gap:8px;font-size:13px;color:#555;font-weight:600;}
.leg-dot{width:16px;height:16px;border-radius:4px;border:2px solid;}
.leg-dot.av{background:#e6fffa;border-color:#27ae60;}
.leg-dot.oc{background:#fdf0ef;border-color:#e74c3c;}

.slot-card.visitor {background:#e8f4f8;border-color:#3498db;color:#2980b9;}
.slot-card.overstay {background:#fcf3cf;border-color:#f39c12;color:#d35400;animation: pulseWarning 2s infinite;}
.leg-dot.vi {background:#e8f4f8;border-color:#3498db;}
.leg-dot.ov {background:#fcf3cf;border-color:#f39c12;}

@keyframes pulseWarning {
    0% { box-shadow: 0 0 0 0 rgba(243, 156, 18, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(243, 156, 18, 0); }
    100% { box-shadow: 0 0 0 0 rgba(243, 156, 18, 0); }
}
</style>

<div class="main-container" id="main-container">

    <div class="parking-header">
        <div>
            <h2><i class="fas fa-car" style="color:#f39c12;margin-right:8px;"></i>Visitor Parking — Today</h2>
            <p><?= date('l, d/m/Y') ?></p>
        </div>
        <div class="slots-stats">
            <div class="slot-stat avail">
                <div class="num"><?= $total_slots - count($occupied_slots) ?></div>
                <div class="lbl">Available</div>
            </div>
            <div class="slot-stat occ">
                <div class="num"><?= count($occupied_slots) ?></div>
                <div class="lbl">Occupied</div>
            </div>
        </div>
    </div>

    <div class="parking-map">
        <h3><i class="fas fa-parking"></i> Parking Slot Map</h3>

        <div class="legend">
            <div class="leg-item"><div class="leg-dot av"></div> Available</div>
            <div class="leg-item"><div class="leg-dot oc"></div> Pre-Booked</div>
            <div class="leg-item"><div class="leg-dot vi"></div> Visitor Log (Inside)</div>
            <div class="leg-item"><div class="leg-dot ov"></div> >8hr Overstay Warning</div>
        </div>

        <div class="road">⬆ ENTRANCE / EXIT ⬆</div>

        <div class="slot-grid">
            <?php
            $booking_idx = 0;
            for ($s = 1; $s <= $total_slots; $s++):
                $is_occupied = in_array($s, $occupied_slots);
                $card_class = 'available';
                $slot_lbl = 'Free';
                $veh = '';
                
                if ($is_occupied && isset($slot_data[$booking_idx])) {
                    $data = $slot_data[$booking_idx];
                    $veh = $data['vehicle_no'];
                    
                    if ($data['type'] === 'overstay') {
                        $card_class = 'overstay';
                        $slot_lbl = 'Overstay (>8h)';
                    } elseif ($data['type'] === 'visitor') {
                        $card_class = 'visitor';
                        $slot_lbl = 'Visitor Log';
                    } else {
                        $card_class = 'occupied';
                        $slot_lbl = 'Booked';
                    }
                    $booking_idx++;
                }
            ?>
            <div class="slot-card <?= $card_class ?>">
                <span class="slot-num">V<?= $s ?></span>
                <span class="slot-lbl"><?= $slot_lbl ?></span>
                <?php if ($is_occupied && $veh): ?>
                    <div class="slot-vehicle"><?= htmlspecialchars($veh) ?></div>
                <?php endif; ?>
            </div>
            <?php endfor; ?>
        </div>

        <div class="road">⬆ ENTRANCE / EXIT ⬆</div>
    </div>

    <h3 style="color:#0B3A45;margin-bottom:15px;font-size:18px;">Today's Pre-Booked Parking Log</h3>
    <div class="table-container">
        <table class="content-table">
            <thead><tr><th>No</th><th>Flat</th><th>Visitor</th><th>Vehicle</th><th>Slots</th><th>From</th><th>To</th><th>Verify</th></tr></thead>
            <tbody>
            <?php if (!empty($booking_data)):
                $i = 1;
                foreach ($booking_data as $b):
            ?>
                <tr>
                    <td style="color:#888;"><?= $i++ ?></td>
                    <td><strong>Flat <?= htmlspecialchars($b['flatno']) ?></strong></td>
                    <td><?= htmlspecialchars($b['visitor_name']) ?></td>
                    <td style="font-family:monospace;font-weight:700;"><?= htmlspecialchars($b['vehicle_no']) ?></td>
                    <td><span style="background:#eef;padding:3px 10px;border-radius:10px;font-weight:700;"><?= $b['slots'] ?></span></td>
                    <td><?= date('d/m/Y', strtotime($b['start_date'])) ?> <?= $b['start_time'] ?></td>
                    <td><?= date('d/m/Y', strtotime($b['end_date'])) ?> <?= $b['end_time'] ?></td>
                    <td><span class="badge badge-success"><i class="fas fa-check"></i> Authorised</span></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="8" style="text-align:center;padding:30px;color:#999;">No parking bookings for today.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require 'footer.php'; ?>