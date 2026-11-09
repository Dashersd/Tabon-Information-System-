<?php
// api/resend_email_pin.php - Resends fresh 6-digit PIN code with reset 3-minute timer
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/mail_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit();
}

$email = trim($_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Please provide a valid email address.']);
    exit();
}

try {
    // Locate pending record
    $stmt = $conn->prepare("SELECT `id`, `full_name`, `resend_count`, `created_at` 
        FROM `email_verifications` 
        WHERE `email` = ? AND `is_verified` = 0 
        ORDER BY `id` DESC LIMIT 1");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $record = $res->fetch_assoc();
    $stmt->close();

    if (!$record) {
        echo json_encode(['status' => 'error', 'message' => 'No active verification session found. Please submit the form again.']);
        exit();
    }

    // Generate new 6-digit PIN and reset timer to 3 minutes
    $new_pin    = sprintf("%06d", mt_rand(100000, 999999));
    $expires_at = date('Y-m-d H:i:s', time() + 180);

    $update = $conn->prepare("UPDATE `email_verifications` 
        SET `pin_code` = ?, `expires_at` = ?, `resend_count` = `resend_count` + 1 
        WHERE `id` = ?");
    if (!$update) {
        throw new Exception("Database update error: " . $conn->error);
    }
    $update->bind_param("ssi", $new_pin, $expires_at, $record['id']);
    $update->execute();
    $update->close();

    // Dispatch email
    $mailResult = send_barangay_pin_email($email, $record['full_name'], $new_pin);

    if (empty($mailResult['mail_sent'])) {
        // Fallback for environments where outbound SMTP is restricted (e.g. Free Hosting)
        echo json_encode([
            'status'     => 'success',
            'mail_sent'  => false,
            'dev_pin'    => $new_pin,
            'message'    => 'A fresh OTP code has been generated. (Hosting notice: Outbound SMTP blocked, code provided below).',
            'email'      => $email,
            'expires_in' => 180
        ]);
        exit();
    }

    echo json_encode([
        'status'     => 'success',
        'mail_sent'  => true,
        'message'    => 'A fresh OTP Code has been sent to your Gmail. Please check your Inbox or Spam folder.',
        'email'      => $email,
        'expires_in' => 180
    ]);

} catch (\Throwable $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Failed to resend OTP: ' . $e->getMessage()
    ]);
}
