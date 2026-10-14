<?php
// =============================================================
//  auth_check.php — User session guard
//  Also handles: session timeout, db connection, CSRF init
// =============================================================
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csrf.php';

// Session timeout
if (isset($_SESSION['last_activity']) &&
    (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit;
}
$_SESSION['last_activity'] = time();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/db_conn.php';
