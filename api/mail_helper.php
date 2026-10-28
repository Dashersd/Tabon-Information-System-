<?php
// api/mail_helper.php - Email Dispatch Helper for Barangay Tabon with PHPMailer & Gmail SMTP Support

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function send_barangay_pin_email($to_email, $to_name, $pin_code) {
    $subject = "Barangay Tabon Verification PIN: " . $pin_code;
    
    $safe_name = htmlspecialchars($to_name ?: 'Resident', ENT_QUOTES, 'UTF-8');
    $safe_pin  = htmlspecialchars($pin_code, ENT_QUOTES, 'UTF-8');
    
    $html_body = "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Barangay Tabon Verification Code</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f3f4f6; margin: 0; padding: 20px; color: #1f2937; }
            .email-container { max-width: 520px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; }
            .email-header { background: linear-gradient(135deg, #093d25 0%, #137547 100%); padding: 26px 20px; text-align: center; color: #ffffff; }
            .email-header h1 { margin: 0; font-size: 20px; font-weight: 700; letter-spacing: 0.5px; }
            .email-header p { margin: 6px 0 0 0; font-size: 13px; opacity: 0.9; }
            .email-body { padding: 30px 24px; text-align: center; }
            .email-body h2 { font-size: 18px; color: #093d25; margin-top: 0; margin-bottom: 12px; }
            .email-body p { font-size: 14px; line-height: 1.6; color: #4b5563; margin: 0 0 20px 0; }
            .pin-display-box { background-color: #e8f5ee; border: 2px dashed #137547; border-radius: 10px; padding: 18px; margin: 24px auto; max-width: 280px; }
            .pin-code { font-family: 'Courier New', Courier, monospace; font-size: 34px; font-weight: 800; letter-spacing: 8px; color: #093d25; margin: 0; }
            .expiry-badge { display: inline-block; background-color: #fef3c7; color: #92400e; font-size: 12px; font-weight: 700; padding: 5px 12px; border-radius: 20px; margin-top: 10px; }
            .email-footer { background-color: #f9fafb; padding: 18px 20px; text-align: center; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280; }
            .email-footer p { margin: 4px 0; }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='email-header'>
                <h1>BARANGAY TABON</h1>
                <p>Information & Resident Service System</p>
            </div>
            <div class='email-body'>
                <h2>Verification PIN Code</h2>
                <p>Hello <strong>{$safe_name}</strong>,</p>
                <p>You are submitting an inquiry or document request to Barangay Tabon. Please use the verification PIN below to confirm your request:</p>
                
                <div class='pin-display-box'>
                    <div class='pin-code'>{$safe_pin}</div>
                    <div class='expiry-badge'>⏱ Expires in 3 Minutes</div>
                </div>

                <p style='font-size: 13px; color: #6b7280;'>If you did not initiate this request, you can safely ignore this email.</p>
            </div>
            <div class='email-footer'>
                <p><strong>Barangay Tabon Hall</strong></p>
                <p>Official Contact & Inquiry Verification Service</p>
            </div>
        </div>
    </body>
    </html>
    ";

    // Load SMTP Configuration
    $smtp_config = [];
    if (file_exists(__DIR__ . '/smtp_config.php')) {
        $smtp_config = require __DIR__ . '/smtp_config.php';
    }

    $mail_sent = false;
    $smtp_used = false;
    $error_msg = '';

    // 1. If SMTP is enabled and credentials are provided, use PHPMailer for real Gmail delivery
    if (!empty($smtp_config['smtp_enabled']) && !empty($smtp_config['smtp_username']) && !empty($smtp_config['smtp_password'])) {
        try {
            require_once __DIR__ . '/phpmailer/Exception.php';
            require_once __DIR__ . '/phpmailer/PHPMailer.php';
            require_once __DIR__ . '/phpmailer/SMTP.php';

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $smtp_config['smtp_host'] ?: 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_config['smtp_username'];
            $mail->Password   = $smtp_config['smtp_password'];
            $mail->SMTPSecure = ($smtp_config['smtp_secure'] === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)($smtp_config['smtp_port'] ?: 587);
            $mail->CharSet    = 'UTF-8';

            $fromEmail = !empty($smtp_config['from_email']) ? $smtp_config['from_email'] : $smtp_config['smtp_username'];
            $fromName  = !empty($smtp_config['from_name']) ? $smtp_config['from_name'] : 'Barangay Tabon';
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to_email, $to_name);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html_body;
            $mail->AltBody = "Barangay Tabon Verification PIN: " . $pin_code . " (Expires in 3 minutes)";

            $mail->send();
            $mail_sent = true;
            $smtp_used = true;
        } catch (Exception $e) {
            $error_msg = $mail->ErrorInfo ?: $e->getMessage();
            $mail_sent = false;
        }
    } else {
        // Fallback: standard mail() attempt
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
        $headers[] = 'From: Barangay Tabon <barangaytabon@gmail.com>';
        $headers[] = 'Reply-To: barangaytabon@gmail.com';
        $headers[] = 'X-Mailer: PHP/' . phpversion();

        $header_str = implode("\r\n", $headers);
        $mail_sent = @mail($to_email, $subject, $html_body, $header_str);
    }

    // Always log locally for development inspection
    $log_dir = __DIR__ . '/../Admin/uploads';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0777, true);
    }
    $status_label = $mail_sent ? 'SENT_VIA_SMTP' : ($error_msg ? 'SMTP_FAILED: ' . $error_msg : 'DEV_MODE_LOG');
    $log_entry = sprintf("[%s] TO: %s (%s) | PIN: %s | STATUS: %s\n",
        date('Y-m-d H:i:s'),
        $to_email,
        $to_name,
        $pin_code,
        $status_label
    );
    @file_put_contents($log_dir . '/email_pins_dev.log', $log_entry, FILE_APPEND);

    return [
        'success'   => true,
        'mail_sent' => $mail_sent,
        'smtp_used' => $smtp_used,
        'pin'       => $pin_code,
        'error'     => $error_msg
    ];
}
