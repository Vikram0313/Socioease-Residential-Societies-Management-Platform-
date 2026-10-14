<?php
// =============================================================
//  config.php  —  Central configuration for Socioease
//  NEVER expose this file publicly (.htaccess blocks it)
// =============================================================

// --- DATABASE ---
define('DB_HOST', 'Enter Host');
define('DB_USER', 'Enter User');
define('DB_PASS', 'Enter DB Password');
define('DB_NAME', 'Enter DB name');

// --- SMTP (Gmail) ---
// 1. Use your Gmail address below
// 2. For password: go to Google Account → Security → App Passwords
//    → generate a 16-char app password (NOT your real Gmail password)
// 3. After hosting, change SITE_URL to your live domain
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_USER',     'Enter your email id');   // <-- change this
define('SMTP_PASS',     'Enter password'); // <-- change this
define('SMTP_FROM_NAME','Enter Name');

// --- RAZORPAY ---
// Key ID is safe to be here. Paste your Key Secret below.
// NEVER commit this file to GitHub or share it publicly.
define('RAZORPAY_KEY_ID',     'Enter Razorpay ID');
define('RAZORPAY_KEY_SECRET', 'Enter Razorpay Key'); // <-- paste your secret here

// --- SITE ---
define('SITE_URL',  'Your site URL'); // change after hosting
define('SITE_NAME', 'Enter site name');

// --- SECURITY ---
define('LOGIN_MAX_ATTEMPTS',  5);    // lock after this many failures
define('LOGIN_LOCKOUT_MINS',  15);   // lockout duration in minutes
define('RESET_TOKEN_EXPIRY',  60);   // password reset link valid for X minutes
define('SESSION_TIMEOUT',     1800); // auto-logout after 30 min inactivity
