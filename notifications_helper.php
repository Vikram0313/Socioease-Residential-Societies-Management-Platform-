<?php
// =============================================================
//  notifications_helper.php
//  Supports user, admin, and watchman notification roles
// =============================================================

/**
 * push_notification()
 * @param string $role  'user' | 'admin' | 'watchman'
 */
function push_notification(
    mysqli $conn,
    string $username,
    string $message,
    string $type  = 'general',
    string $link  = '',
    string $role  = 'user'
): void {
    $stmt = $conn->prepare(
        "INSERT INTO notifications (username, role, message, type, link) VALUES (?,?,?,?,?)"
    );
    $stmt->bind_param("sssss", $username, $role, $message, $type, $link);
    $stmt->execute();
}

/**
 * push_admin_notification() — shortcut for admin bell
 */
function push_admin_notification(mysqli $conn, string $message, string $type = 'general', string $link = ''): void {
    push_notification($conn, 'Admin', $message, $type, $link, 'admin');
}

/**
 * get_unread_count()
 */
function get_unread_count(mysqli $conn, string $username, string $role = 'user'): int {
    $stmt = $conn->prepare(
        "SELECT COUNT(*) FROM notifications WHERE username=? AND role=? AND is_read=0"
    );
    $stmt->bind_param("ss", $username, $role);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_row()[0];
}
