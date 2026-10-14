<?php
require 'auth_check_watchman.php';
$id  = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM visitor_log WHERE id=?");
$stmt->bind_param("i",$id); $stmt->execute();
$v = $stmt->get_result()->fetch_assoc();
if (!$v) { echo "Gate pass not found."; exit; }

// Society details
$sec = $conn->query("SELECT * FROM society_details LIMIT 1")->fetch_assoc();
$society_name = $sec['name'] ?? 'Socioease Society';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Gate Pass — <?= htmlspecialchars($v['gate_pass_code']) ?></title>
    <script src="https://kit.fontawesome.com/2edfbc5391.js" crossorigin="anonymous"></script>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',Tahoma,sans-serif;}
        body{background:#f0f4f8;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;}
        .pass{background:white;width:380px;border-radius:16px;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,0.15);}
        .pass-header{background:linear-gradient(135deg,#1a1a2e,#0f3460);color:white;padding:25px;text-align:center;}
        .pass-header .shield{font-size:40px;color:#f39c12;margin-bottom:10px;}
        .pass-header h2{font-size:18px;margin-bottom:4px;}
        .pass-header p{font-size:12px;opacity:0.6;}
        .pass-code{background:#f39c12;color:#1a1a2e;text-align:center;padding:14px;font-size:28px;font-weight:900;letter-spacing:6px;}
        .pass-body{padding:25px;}
        .pass-row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f1f1f1;font-size:14px;}
        .pass-row:last-child{border:none;}
        .pass-label{color:#888;font-weight:600;}
        .pass-value{color:#0B3A45;font-weight:700;text-align:right;max-width:60%;}
        .pass-footer{background:#f7f9fc;padding:15px;text-align:center;}
        .pass-footer p{font-size:11px;color:#aaa;}
        .status-badge{background:#e6fffa;color:#27ae60;padding:3px 10px;border-radius:10px;font-size:12px;font-weight:700;}
        .status-exit{background:#fff5f5;color:#e74c3c;}
        .print-btn{display:block;width:100%;padding:13px;background:#f39c12;color:#1a1a2e;border:none;font-weight:800;font-size:15px;cursor:pointer;margin-top:15px;border-radius:8px;}
        @media print{.print-btn{display:none;}body{background:white;}
        .pass{box-shadow:none;border:1px solid #ddd;}}
    </style>
</head>
<body>
<div>
<div class="pass">
    <div class="pass-header">
        <div class="shield"><i class="fas fa-shield-alt"></i></div>
        <h2><?= htmlspecialchars($society_name) ?></h2>
        <p>Official Visitor Gate Pass</p>
    </div>
    <div class="pass-code"><?= htmlspecialchars($v['gate_pass_code']) ?></div>
    <div class="pass-body">
        <div class="pass-row">
            <span class="pass-label">Visitor</span>
            <span class="pass-value"><?= htmlspecialchars($v['visitor_name']) ?></span>
        </div>
        <div class="pass-row">
            <span class="pass-label">Visiting Flat</span>
            <span class="pass-value"><?= htmlspecialchars($v['flat_no']) ?></span>
        </div>
        <div class="pass-row">
            <span class="pass-label">Resident</span>
            <span class="pass-value"><?= htmlspecialchars($v['resident_name']) ?></span>
        </div>
        <?php if ($v['vehicle_no']): ?>
        <div class="pass-row">
            <span class="pass-label">Vehicle</span>
            <span class="pass-value"><?= htmlspecialchars($v['vehicle_no']) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($v['purpose']): ?>
        <div class="pass-row">
            <span class="pass-label">Purpose</span>
            <span class="pass-value"><?= htmlspecialchars($v['purpose']) ?></span>
        </div>
        <?php endif; ?>
        <div class="pass-row">
            <span class="pass-label">Time In</span>
            <span class="pass-value"><?= date('d/m/Y h:i A', strtotime($v['time_in'])) ?></span>
        </div>
        <div class="pass-row">
            <span class="pass-label">Time Out</span>
            <span class="pass-value">
                <?php if ($v['time_out']): ?>
                    <?= date('d/m/Y h:i A', strtotime($v['time_out'])) ?>
                <?php else: ?>
                    <span class="status-badge">Active</span>
                <?php endif; ?>
            </span>
        </div>
        <div class="pass-row">
            <span class="pass-label">Issued By</span>
            <span class="pass-value"><?= htmlspecialchars($v['logged_by']) ?></span>
        </div>
        <div class="pass-row">
            <span class="pass-label">Status</span>
            <span class="pass-value">
                <span class="status-badge <?= $v['time_out']?'status-exit':'' ?>">
                    <?= $v['time_out'] ? 'Exited' : 'Valid — Inside' ?>
                </span>
            </span>
        </div>
    </div>
    <div class="pass-footer">
        <p>This pass is valid for one-time entry only.</p>
        <p>Issued: <?= date('d/m/Y h:i A') ?> &nbsp;|&nbsp; <?= htmlspecialchars($society_name) ?></p>
    </div>
</div>
<button class="print-btn" onclick="window.print()"><i class="fas fa-print"></i> Print Gate Pass</button>
</div>
</body>
</html>
