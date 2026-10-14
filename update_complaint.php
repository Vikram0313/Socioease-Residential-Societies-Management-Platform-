<?php
session_start();
require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/notifications_helper.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("Location: Adminlogin.php"); exit;
}

$id     = (int)($_GET['id'] ?? 0);
$status = trim($_GET['s']   ?? '');
$allowed = ['In Progress', 'Resolved', 'Rejected'];

if ($id > 0 && in_array($status, $allowed)) {
    // Fetch complaint details before updating
    $row = $conn->prepare("SELECT Title, username FROM combox WHERE ID=?");
    $row->bind_param("i", $id); $row->execute();
    $complaint = $row->get_result()->fetch_assoc();

    $upd = $conn->prepare("UPDATE combox SET status=? WHERE ID=?");
    $upd->bind_param("si", $status, $id);
    $upd->execute();

    if ($complaint) {
        $username = $complaint['username'];
        $title    = $complaint['Title'];
        $emoji    = $status === 'Resolved' ? '✅' : ($status === 'In Progress' ? '🔄' : '❌');

        push_notification($conn, $username,
            "Your complaint \"$title\" is now: $status $emoji",
            'complaint', 'complaint.php'
        );

        $user_row = $conn->query("SELECT Email FROM registration WHERE Username='$username' LIMIT 1")->fetch_assoc();
        if ($user_row && $user_row['Email']) {
            $body = email_template("Complaint Status Updated", "
                <p>Hi <strong>$username</strong>, there's an update on your complaint.</p>
                <div class='info-box'>
                    <p><strong>Complaint:</strong> " . htmlspecialchars($title) . "</p>
                    <p><strong>New Status:</strong> $emoji $status</p>
                </div>
                " . ($status === 'Resolved' ? "<p>Your complaint has been resolved. Thank you for reporting the issue.</p>" :
                     ($status === 'In Progress' ? "<p>Our team is actively working on your complaint.</p>" :
                      "<p>Please contact the society office if you have any questions.</p>")) . "
            ");
            send_email($user_row['Email'], $username, "Complaint Update: $status  -  Socioease", $body);
        }
    }
}

header("Location: viewcomplaints.php");
exit;
