<?php
// api/mail_helper.php - Email Dispatch Helper for Barangay Tabon with High Deliverability & Anti-Spam Optimization

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function send_barangay_pin_email($to_email, $to_name, $pin_code) {
    // Trusted, clean subject line format (recognized by Google/Yahoo as transactional OTP)
    $subject = "Your Barangay Tabon Verification Code (OTP): " . $pin_code;
    
    $safe_name = htmlspecialchars($to_name ?: 'Resident', ENT_QUOTES, 'UTF-8');
    $safe_pin  = htmlspecialchars($pin_code, ENT_QUOTES, 'UTF-8');
    
    // Clean, professional HTML body designed to pass modern SpamAssassin & Google AI filters
    $html_body = "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Barangay Tabon Verification Code</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px 12px; color: #1e293b; }
            .email-container { max-width: 520px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; }
            .email-header { background: linear-gradient(135deg, #0f172a 0%, #1a4971 100%); padding: 28px 24px; text-align: center; color: #ffffff; }
            .email-header h1 { margin: 0; font-size: 22px; font-weight: 700; letter-spacing: 0.5px; }
            .email-header p { margin: 6px 0 0 0; font-size: 13px; opacity: 0.92; }
            .email-body { padding: 32px 28px; text-align: center; }
            .email-body h2 { font-size: 18px; color: #1a4971; margin-top: 0; margin-bottom: 12px; font-weight: 700; }
            .email-body p { font-size: 14px; line-height: 1.6; color: #475569; margin: 0 0 20px 0; }
            .pin-display-box { background-color: #dcecf7; border: 2px dashed #1a4971; border-radius: 12px; padding: 20px; margin: 24px auto; max-width: 290px; }
            .pin-code { font-family: 'Courier New', Courier, monospace; font-size: 36px; font-weight: 800; letter-spacing: 8px; color: #0f172a; margin: 0; }
            .expiry-badge { display: inline-block; background-color: #fef3c7; color: #92400e; font-size: 12px; font-weight: 700; padding: 5px 12px; border-radius: 20px; margin-top: 12px; }
            .security-note { font-size: 12px; color: #64748b; margin-top: 24px; line-height: 1.5; border-top: 1px solid #f1f5f9; padding-top: 16px; }
            .email-footer { background-color: #f8fafc; padding: 20px 24px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; line-height: 1.5; }
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
                <h2>Identity Verification OTP</h2>
                <p>Hello <strong>{$safe_name}</strong>,</p>
                <p>You recently submitted an inquiry or document request through the Barangay Tabon Information System. Please use the single-use 6-digit verification code below to confirm your request:</p>
                
                <div class='pin-display-box'>
                    <div class='pin-code'>{$safe_pin}</div>
                    <div class='expiry-badge'>⏱ Expires in 3 Minutes</div>
                </div>

                <div class='security-note'>
                    <strong>Security Notice:</strong> Never share this verification code with anyone. Barangay Tabon personnel will never ask for your OTP. If you did not make this request, you can safely disregard this email.
                </div>
            </div>
            <div class='email-footer'>
                <p><strong>Barangay Tabon Hall</strong></p>
                <p>Municipality of Lapuyan, Zamboanga del Sur</p>
                <p style='color: #94a3b8; font-size: 11px; margin-top: 8px;'>This is an automated security transmission. Please do not reply directly to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    // Matching rich Plain-Text AltBody (Prevents HTML-only spam penalties)
    $plain_body = "BARANGAY TABON - INFORMATION & RESIDENT SERVICES\n\n"
                . "Identity Verification PIN\n\n"
                . "Hello {$safe_name},\n\n"
                . "You recently submitted an inquiry or document request through the Barangay Tabon Information System.\n"
                . "Your 6-digit verification PIN is: {$pin_code}\n\n"
                . "This code will expire in 3 minutes.\n\n"
                . "Security Notice: Never share this code with anyone. If you did not initiate this request, you can safely ignore this email.\n\n"
                . "Barangay Tabon Hall\nMunicipality of Lapuyan, Zamboanga del Sur";

    // Load SMTP Configuration
    $smtp_config = [];
    if (file_exists(__DIR__ . '/smtp_config.php')) {
        $smtp_config = require __DIR__ . '/smtp_config.php';
    }

    $mail_sent = false;
    $smtp_used = false;
    $error_msg = '';

    // 1. Send via PHPMailer using Gmail SMTP with deliverability headers
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
            $mail->Encoding   = 'base64';

            // CRITICAL FIX FOR SPAM:
            // Prevent using local PC name (e.g. LAPTOP-EDQQ093G) in Message-ID.
            // Using gmail.com ensures Message-ID matches the authenticated Gmail sender domain!
            $mail->Hostname = 'gmail.com';

            // Remove generic X-Mailer header that spam filters trigger on
            $mail->XMailer = ' ';

            $fromEmail = !empty($smtp_config['from_email']) ? $smtp_config['from_email'] : $smtp_config['smtp_username'];
            $fromName  = !empty($smtp_config['from_name']) ? $smtp_config['from_name'] : 'Barangay Tabon';

            $mail->setFrom($fromEmail, $fromName);
            $mail->Sender = $fromEmail; // Ensures Return-Path matches From:
            $mail->addReplyTo($fromEmail, $fromName);
            $mail->addAddress($to_email, $to_name);

            // Add anti-spam transactional headers recognized by email providers
            $mail->addCustomHeader('Auto-Submitted', 'auto-generated');
            $mail->addCustomHeader('X-Auto-Response-Suppress', 'All');
            $mail->addCustomHeader('X-Entity-Ref-ID', uniqid('tabon_otp_'));

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html_body;
            $mail->AltBody = $plain_body;

            $mail->send();
            $mail_sent = true;
            $smtp_used = true;
        } catch (Exception $e) {
            $error_msg = $mail->ErrorInfo ?: $e->getMessage();
            $mail_sent = false;
        }
    } else {
        // Fallback: standard mail()
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
        $headers[] = 'From: Barangay Tabon <barangaytabon@gmail.com>';
        $headers[] = 'Reply-To: barangaytabon@gmail.com';

        $header_str = implode("\r\n", $headers);
        $mail_sent = @mail($to_email, $subject, $html_body, $header_str);
    }

    // Local logging for monitoring
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
