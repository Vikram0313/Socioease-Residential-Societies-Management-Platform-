<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csrf.php';

if (isset($_SESSION['last_activity']) &&
    (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
    session_unset(); session_destroy();
    header("Location: watchman_login.php?timeout=1"); exit;
}
$_SESSION['last_activity'] = time();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true ||
    !isset($_SESSION['role']) || $_SESSION['role'] !== 'watchman') {
    header("Location: watchman_login.php"); exit;
}

require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/notifications_helper.php';
