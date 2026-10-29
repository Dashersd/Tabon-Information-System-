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
    
    // Clean, modern HTML body with 100% INLINE styles for full compatibility with Gmail Web & Mobile
    $html_body = "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Barangay Tabon Verification Code</title>
        <style>
            body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; }
            table { border-collapse: collapse; }
        </style>
    </head>
    <body style='margin: 0; padding: 24px 12px; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; color: #1e293b;'>
        <table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0'>
            <tr>
                <td align='center'>
                    <table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='max-width: 520px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.08); border: 1px solid #e2e8f0;'>
                        
                        <!-- Header (Solid Black) -->
                        <tr>
                            <td align='center' style='background-color: #000000; background: #000000; padding: 26px 24px; color: #ffffff;'>
                                <h1 style='margin: 0; font-size: 22px; font-weight: 800; letter-spacing: 1px; color: #ffffff;'>BARANGAY TABON</h1>
                            </td>
                        </tr>

                        <!-- Body Content -->
                        <tr>
                            <td align='center' style='padding: 34px 28px;'>
                                <h2 style='font-size: 19px; color: #000000; margin: 0 0 14px 0; font-weight: 700;'>Identity Verification OTP</h2>
                                <p style='font-size: 14px; line-height: 1.6; color: #475569; margin: 0 0 16px 0; text-align: center;'>
                                    Hello <strong>{$safe_name}</strong>,
                                </p>
                                <p style='font-size: 14px; line-height: 1.6; color: #475569; margin: 0 0 24px 0; text-align: center;'>
                                    You recently submitted an inquiry or document request through the Barangay Tabon Information System. Please enter the single-use 6-digit OTP below to verify your request:
                                </p>
                                
                                <!-- OTP Code Box (Solid Black Border, Clean Background) -->
                                <table role='presentation' cellpadding='0' cellspacing='0' border='0' style='background-color: #f8fafc; border: 2px dashed #000000; border-radius: 12px; margin: 0 auto 24px auto; width: 290px;'>
                                    <tr>
                                        <td align='center' style='padding: 22px 16px;'>
                                            <div style='font-family: \"Courier New\", Courier, monospace; font-size: 38px; font-weight: 800; letter-spacing: 8px; color: #000000; margin: 0; line-height: 1;'>
                                                {$safe_pin}
                                            </div>
                                            <div style='display: inline-block; background-color: #000000; color: #ffffff; font-size: 11px; font-weight: 600; padding: 4px 12px; border-radius: 20px; margin-top: 14px; letter-spacing: 0.3px;'>
                                                &#9201; Expires in 3 Minutes
                                            </div>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Security Notice -->
                                <div style='font-size: 12px; color: #64748b; margin-top: 10px; line-height: 1.6; border-top: 1px solid #f1f5f9; padding-top: 18px; text-align: center;'>
                                    <strong style='color: #000000;'>Security Notice:</strong> Never share this OTP with anyone. Barangay Tabon personnel will never ask for your code. If you did not make this request, you can safely disregard this email.
                                </div>
                            </td>
                        </tr>

                        <!-- Footer -->
                        <tr>
                            <td align='center' style='background-color: #f8fafc; padding: 20px 24px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; line-height: 1.5;'>
                                <p style='margin: 0; font-weight: 700; color: #0f172a;'>Barangay Tabon Hall</p>
                                <p style='margin: 4px 0 0 0;'>Municipality of Lapuyan, Zamboanga del Sur</p>
                                <p style='margin: 8px 0 0 0; color: #94a3b8; font-size: 11px;'>This is an automated security transmission. Please do not reply directly to this email.</p>
                            </td>
                        </tr>

                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>
    ";

    // Matching rich Plain-Text AltBody (Prevents HTML-only spam penalties)
    $plain_body = "BARANGAY TABON - INFORMATION & RESIDENT SERVICES\n\n"
                . "Identity Verification OTP\n\n"
                . "Hello {$safe_name},\n\n"
                . "You recently submitted an inquiry or document request through the Barangay Tabon Information System.\n"
                . "Your 6-digit verification OTP is: {$pin_code}\n\n"
                . "This code will expire in 3 minutes.\n\n"
                . "Security Notice: Never share this OTP with anyone. If you did not initiate this request, you can safely ignore this email.\n\n"
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
    $log_entry = sprintf("[%s] TO: %s (%s) | OTP: %s | STATUS: %s\n",
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