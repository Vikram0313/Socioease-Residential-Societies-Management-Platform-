<?php
require 'auth_check.php';

$user = $_SESSION['username'];
$flat = $_SESSION['flatno'];

// Base maintenance
$sec_stmt = $conn->query("SELECT maintenance_amt FROM society_details LIMIT 1");
$sec_data = $sec_stmt ? $sec_stmt->fetch_assoc() : null;
$base_maintenance = (float)($sec_data['maintenance_amt'] ?? 1500);

// Extra charges — only visitor parking (facility bookings are free)
$flat_int = (int)$flat;

$pk = $conn->prepare("SELECT COALESCE(SUM(total_price),0) AS total FROM visitor_parking WHERE flatno=? AND is_paid=0");
$pk->bind_param("i", $flat_int);
$pk->execute();
$parking_charges = (float)$pk->get_result()->fetch_assoc()['total'];

$extra_charges = $parking_charges;
$total_due = $base_maintenance + $extra_charges;

// Paid months
$paid_months = [];
$chk = $conn->prepare("SELECT month_name FROM payrecords WHERE Flatno=? AND Status='Success'");
$chk->bind_param("s", $flat);
$chk->execute();
$chk_res = $chk->get_result();
while ($p = $chk_res->fetch_assoc()) { $paid_months[] = $p['month_name']; }

// Available months for dropdown
$all_months = ["January","February","March","April","May","June",
               "July","August","September","October","November","December"];
$unpaid_months = array_filter($all_months, fn($m) => !in_array($m, $paid_months));

$page_title = "Payments";
$extra_head = '<script src="https://checkout.razorpay.com/v1/checkout.js"></script>';
require 'header_user.php';
require 'sidebar_user.php';
?>
<style>
.payment-grid { display:grid; grid-template-columns:1fr 1.4fr; gap:30px; margin-bottom:40px; }
.payment-card { background:white; padding:35px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.08); border-top:5px solid #0EA5C9; }
.payment-card h2 { color:#0D2137; margin-bottom:20px; font-size:20px; }

/* Amount display card */
.amount-card { background:#0D2137; border-radius:12px; padding:35px 30px; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; }
.amount-label { color:rgba(255,255,255,0.6); font-size:14px; margin-bottom:8px; }
.amount-value { color:white; font-size:48px; font-weight:800; line-height:1; }
.amount-breakdown { margin-top:18px; width:100%; border-top:1px solid rgba(255,255,255,0.15); padding-top:16px; }
.breakdown-row { display:flex; justify-content:space-between; color:rgba(255,255,255,0.7); font-size:13px; margin-bottom:8px; }
.breakdown-row.total { color:white; font-weight:700; font-size:15px; border-top:1px solid rgba(255,255,255,0.2); padding-top:10px; margin-top:5px; }

/* Form elements */
.form-label { font-weight:600; font-size:14px; color:#444; display:block; margin-bottom:8px; }
.input-box { width:100%; padding:12px; border-radius:8px; border:1px solid #ddd; background:#f9f9f9; margin-bottom:15px; font-size:14px; outline:none; transition:0.3s; font-family:inherit; }
.input-box:focus { border-color:#0EA5C9; background:white; }

/* Razorpay button */
.btn-razorpay {
    width:100%; padding:15px; border:none; border-radius:10px;
    background:linear-gradient(135deg, #0EA5C9, #0284A8);
    color:white; font-size:16px; font-weight:700;
    cursor:pointer; transition:0.3s;
    display:flex; align-items:center; justify-content:center; gap:10px;
    box-shadow:0 4px 15px rgba(14,165,201,0.4);
}
.btn-razorpay:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(14,165,201,0.5); }
.btn-razorpay:disabled { background:#ccc; cursor:not-allowed; transform:none; box-shadow:none; }
.btn-razorpay img { width:22px; height:22px; filter:brightness(10); }

/* No months left */
.all-paid-box { background:#e6fffa; border:1px solid #b2f5ea; border-radius:10px; padding:25px; text-align:center; }
.all-paid-box i { font-size:36px; color:#38a169; margin-bottom:10px; display:block; }
.all-paid-box p { color:#276749; font-weight:600; font-size:15px; }

/* Payment success overlay */
.payment-overlay {
    display:none; position:fixed; top:0; left:0; width:100%; height:100%;
    background:rgba(0,0,0,0.7); z-index:9999;
    align-items:center; justify-content:center;
}
.payment-overlay.show { display:flex; }
.success-modal {
    background:white; border-radius:20px; padding:50px 40px;
    text-align:center; max-width:420px; width:90%;
    animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
@keyframes popIn {
    from { transform:scale(0.5); opacity:0; }
    to   { transform:scale(1);   opacity:1; }
}
.success-icon { width:80px; height:80px; background:#e6fffa; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; }
.success-icon i { font-size:36px; color:#38a169; }
.success-modal h3 { color:#0D2137; font-size:24px; margin-bottom:10px; }
.success-modal p { color:#666; font-size:14px; line-height:1.6; }
.success-modal .txn-id { font-family:monospace; background:#f7f9fc; padding:8px 14px; border-radius:6px; font-size:13px; color:#444; margin:15px 0; display:inline-block; }
.btn-done { background:#0EA5C9; color:white; border:none; padding:12px 30px; border-radius:8px; font-weight:700; cursor:pointer; margin-top:10px; font-size:15px; }

@media(max-width:900px){ .payment-grid{grid-template-columns:1fr;} }
</style>

<div class="main-container" id="main-container">

    <!-- Payment success overlay -->
    <div class="payment-overlay" id="successOverlay">
        <div class="success-modal">
            <div class="success-icon"><i class="fas fa-check"></i></div>
            <h3>Payment Successful!</h3>
            <p>Your maintenance payment has been verified and recorded.</p>
            <div class="txn-id" id="successTxnId"></div>
            <p style="font-size:13px;color:#aaa;">A receipt has been sent to your registered email.</p>
            <button class="btn-done" onclick="location.reload()">Done</button>
        </div>
    </div>

    <div class="payment-grid">

        <!-- LEFT — Form -->
        <div class="payment-card">
            <h2><i class="fas fa-file-invoice" style="color:#0EA5C9;margin-right:10px;"></i>Pay Maintenance</h2>

            <?php if (empty($unpaid_months)): ?>
                <div class="all-paid-box">
                    <i class="fas fa-check-circle"></i>
                    <p>All months for 2026 are paid. You're all caught up!</p>
                </div>
            <?php else: ?>
                <div style="background:#fdfaf0;padding:14px;border-radius:8px;margin-bottom:18px;border-left:4px solid #f9ebbe;">
                    <p style="font-size:13px;color:#856404;">
                        <i class="fas fa-info-circle"></i>
                        <strong>Base:</strong> ₹<?= number_format($base_maintenance,2) ?>
                        &nbsp;|&nbsp;
                        <strong>Extra charges:</strong> ₹<?= number_format($extra_charges,2) ?>
                    </p>
                </div>

                <label class="form-label">Select Month</label>
                <select id="payMonth" class="input-box">
                    <?php foreach ($unpaid_months as $m): ?>
                        <option value="<?= $m ?>" <?= date('F')===$m?'selected':'' ?>><?= $m ?> 2026</option>
                    <?php endforeach; ?>
                </select>

                <button class="btn-razorpay" id="payBtn" onclick="startPayment()">
                    <i class="fas fa-lock"></i>
                    Pay ₹<?= number_format($total_due,2) ?> Securely
                </button>

                <div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-top:14px;">
                    <img src="https://razorpay.com/favicon.ico" style="width:16px;height:16px;opacity:0.6;">
                    <span style="font-size:11px;color:#aaa;">Secured by Razorpay &nbsp;|&nbsp; UPI · Cards · Net Banking</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT — Amount display -->
        <div class="amount-card">
            <div class="amount-label">Total Due This Month</div>
            <div class="amount-value">₹<?= number_format($total_due,2) ?></div>
            <div class="amount-breakdown">
                <div class="breakdown-row">
                    <span>Base Maintenance</span>
                    <span>₹<?= number_format($base_maintenance,2) ?></span>
                </div>
                <?php if ($parking_charges > 0): ?>
                <div class="breakdown-row">
                    <span>Visitor Parking Charges</span>
                    <span>₹<?= number_format($parking_charges,2) ?></span>
                </div>
                <?php endif; ?>
                <div class="breakdown-row total">
                    <span>Total Payable</span>
                    <span>₹<?= number_format($total_due,2) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment history -->
    <div class="table-container">
        <h3 style="margin-bottom:20px;color:#0D2137;"><i class="fas fa-history"></i> Payment History</h3>
        <table class="content-table">
            <thead>
                <tr><th>Month</th><th>Amount</th><th>Transaction ID (Razorpay)</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php
            $hist = $conn->prepare("SELECT * FROM payrecords WHERE Flatno=? ORDER BY ID DESC");
            $hist->bind_param("s", $flat);
            $hist->execute();
            $hist_res = $hist->get_result();
            if ($hist_res->num_rows === 0):
            ?>
                <tr><td colspan="5" style="text-align:center;padding:40px;color:#999;">No payment records yet.</td></tr>
            <?php else:
                while ($r = $hist_res->fetch_assoc()):
                    $sc  = ($r['Status']==='Success') ? 'badge-success' : 'badge-pending';
                    // Detect if paid via Razorpay (starts with pay_) or manual UTR
                    // All payments via Razorpay
            ?>
                <tr>
                    <td style="font-weight:600;"><?= htmlspecialchars($r['month_name']) ?></td>
                    <td>₹<?= number_format($r['Amount'],2) ?></td>
                    <td style="font-family:monospace;font-size:13px;color:#666;"><?= htmlspecialchars($r['utr_number'] ?? '-') ?></td>
                    <td><span class="badge <?= $sc ?>"><?= htmlspecialchars($r['Status']) ?></span></td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$extra_js = "
var RAZORPAY_KEY = '" . RAZORPAY_KEY_ID . "';
var TOTAL_DUE    = " . $total_due . ";
var USER_NAME    = '" . addslashes($user) . "';
var USER_EMAIL   = '';

// Fetch user email for prefill
fetch('get_user_email.php').then(r=>r.json()).then(function(d){ USER_EMAIL = d.email || ''; });

function startPayment() {
    var month = document.getElementById('payMonth').value;
    if (!month) { alert('Please select a month'); return; }

    var btn = document.getElementById('payBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Creating order...';

    // Step 1: Create order server-side
    var fd = new FormData();
    fd.append('amount', TOTAL_DUE);
    fd.append('month',  month);

    fetch('payment_order.php', { method:'POST', body:fd })
    .then(r=>r.json())
    .then(function(data) {
        if (!data.success) {
            alert(data.message || 'Could not initiate payment');
            btn.disabled = false;
            btn.innerHTML = '<i class=\"fas fa-lock\"></i> Pay ₹" . number_format($total_due,2) . " Securely';
            return;
        }

        // Step 2: Open Razorpay checkout
        var options = {
            key:         RAZORPAY_KEY,
            amount:      data.amount,
            currency:    data.currency,
            name:        'Socioease',
            description: 'Maintenance - ' + month + ' 2026',
            image:       '',
            order_id:    data.order_id,
            prefill: {
                name:  USER_NAME,
                email: USER_EMAIL,
            },
            theme: { color: '#0EA5C9' },
            modal: {
                ondismiss: function() {
                    btn.disabled = false;
                    btn.innerHTML = '<i class=\"fas fa-lock\"></i> Pay ₹" . number_format($total_due,2) . " Securely';
                }
            },
            handler: function(response) {
                // Step 3: Verify payment server-side
                btn.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Verifying payment...';
                var vfd = new FormData();
                vfd.append('razorpay_payment_id', response.razorpay_payment_id);
                vfd.append('razorpay_order_id',   response.razorpay_order_id);
                vfd.append('razorpay_signature',  response.razorpay_signature);
                vfd.append('month',  month);
                vfd.append('amount', TOTAL_DUE);

                fetch('payment_verify.php', { method:'POST', body:vfd })
                .then(r=>r.json())
                .then(function(vdata) {
                    if (vdata.success) {
                        document.getElementById('successTxnId').textContent = 'Txn: ' + response.razorpay_payment_id;
                        document.getElementById('successOverlay').classList.add('show');
                    } else {
                        alert('Verification failed: ' + (vdata.message || 'Please contact admin'));
                        btn.disabled = false;
                        btn.innerHTML = '<i class=\"fas fa-lock\"></i> Pay ₹" . number_format($total_due,2) . " Securely';
                    }
                });
            }
        };

        var rzp = new Razorpay(options);
        rzp.on('payment.failed', function(response){
            alert('Payment failed: ' + response.error.description);
            btn.disabled = false;
            btn.innerHTML = '<i class=\"fas fa-lock\"></i> Pay ₹" . number_format($total_due,2) . " Securely';
        });
        rzp.open();
    })
    .catch(function(err){
        alert('Network error. Please try again.');
        btn.disabled = false;
        btn.innerHTML = '<i class=\"fas fa-lock\"></i> Pay ₹" . number_format($total_due,2) . " Securely';
    });
}
";
require 'footer.php';
?>
