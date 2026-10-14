<?php
// =============================================================
//  mailer.php  —  PHPMailer wrapper
//  Uses Gmail SMTP. Credentials come from config.php.
//  HOW TO INSTALL PHPMailer (free, no Composer needed):
//    1. Download from https://github.com/PHPMailer/PHPMailer
//    2. Extract and put the PHPMailer folder inside socioease/
//    3. The require paths below will work automatically
// =============================================================
require_once __DIR__ . '/config.php';

// Check if PHPMailer is installed — site works without it, emails just won't send
define('PHPMAILER_AVAILABLE',
    file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php')
);

if (PHPMAILER_AVAILABLE) {
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
}

/**
 * send_email()
 * Returns true on success, false if PHPMailer missing or send fails.
 * The rest of the app always continues regardless of return value.
 */
function send_email(string $to_email, string $to_name, string $subject, string $body_html): bool {
    if (!PHPMAILER_AVAILABLE) {
        error_log("PHPMailer not installed — email to $to_email skipped. Download from https://github.com/PHPMailer/PHPMailer");
        return false;
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
        $mail->addAddress($to_email, $to_name);
        $mail->addReplyTo(SMTP_USER, SMTP_FROM_NAME);

        $mail->isHTML(true);
        $mail->CharSet  = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->Subject  = $subject;
        $mail->Body    = $body_html;
        $mail->AltBody = strip_tags(str_replace(['<br>','<br/>','</p>'], "\n", $body_html));

        $mail->send();
        return true;
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log("Mailer error to $to_email: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * email_template()
 * Wraps any message in the standard Socioease email layout.
 */
function email_template(string $title, string $body_content): string {
    return '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<style>
  body{margin:0;padding:0;background:#f0f4f8;font-family:Segoe UI,Arial,sans-serif;}
  .wrap{max-width:600px;margin:30px auto;background:white;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);}
  .hdr{background:#0B3A45;padding:28px 35px;text-align:center;}
  .hdr h1{color:#19B3D3;font-size:26px;margin:0;}
  .hdr p{color:rgba(255,255,255,0.6);font-size:13px;margin:5px 0 0;}
  .body{padding:35px;}
  .body h2{color:#0B3A45;font-size:20px;margin:0 0 15px;}
  .body p{color:#555;line-height:1.7;font-size:15px;margin:0 0 15px;}
  .btn{display:inline-block;background:#19B3D3;color:white;padding:13px 28px;border-radius:8px;text-decoration:none;font-weight:700;font-size:15px;margin:10px 0;}
  .info-box{background:#f7fafc;border-left:4px solid #19B3D3;padding:15px 18px;border-radius:0 8px 8px 0;margin:20px 0;}
  .info-box p{margin:0;color:#444;font-size:14px;}
  .ftr{background:#f7fafc;padding:20px 35px;text-align:center;border-top:1px solid #eee;}
  .ftr p{color:#aaa;font-size:12px;margin:0;}
</style>
</head>
<body>
<div class="wrap">
  <div class="hdr">
    <h1>Socioease</h1>
    <p>Your Society Management Portal</p>
  </div>
  <div class="body">
    <h2>' . $title . '</h2>
    ' . $body_content . '
  </div>
  <div class="ftr">
    <p>This is an automated message from Socioease. Please do not reply to this email.</p>
    <p style="margin-top:5px;">&copy; ' . date('Y') . ' Socioease. All rights reserved.</p>
  </div>
</div>
</body>
</html>';
}
