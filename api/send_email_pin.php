<?php
// api/send_email_pin.php - Dispatches 6-digit PIN code to user's Gmail
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/mail_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit();
}

$fullName = trim($_POST['fullName'] ?? '');
$email    = trim($_POST['email'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$message  = trim($_POST['message'] ?? '');
$service  = trim($_POST['service_type'] ?? 'Contact Inquiry');

if (empty($fullName)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter your Full Name.']);
    exit();
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid Gmail or email address.']);
    exit();
}

if (empty($message)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter your message or request.']);
    exit();
}

try {
    // 1. Ensure email_verifications table exists
    $conn->query("CREATE TABLE IF NOT EXISTS `email_verifications` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `email` varchar(150) NOT NULL,
      `pin_code` varchar(10) NOT NULL,
      `full_name` varchar(150) NOT NULL,
      `phone` varchar(50) DEFAULT NULL,
      `service_type` varchar(150) DEFAULT 'Contact Inquiry',
      `message` text NOT NULL,
      `expires_at` datetime NOT NULL,
      `is_verified` tinyint(1) DEFAULT 0,
      `resend_count` int(11) DEFAULT 0,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `email` (`email`),
      KEY `expires_at` (`expires_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 2. Self-healing: Fix any zero ID and ensure AUTO_INCREMENT is active
    try {
        $zeroCheck = $conn->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM `email_verifications`");
        $fix_next = ($zeroCheck && $zRow = $zeroCheck->fetch_assoc()) ? (int)$zRow['next_id'] : 1;
        $conn->query("UPDATE `email_verifications` SET `id` = $fix_next WHERE `id` = 0");
        $conn->query("ALTER TABLE `email_verifications` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT");
    } catch (\Throwable $fixEx) {}

    // 3. Generate secure 6-digit PIN code
    $pin_code = sprintf("%06d", mt_rand(100000, 999999));

    // 3 minutes expiration (180 seconds)
    $expires_at = date('Y-m-d H:i:s', time() + 180);

    // 4. Invalidate any existing unverified codes for this email
    $invalidate = $conn->prepare("UPDATE `email_verifications` SET `is_verified` = 2 WHERE `email` = ? AND `is_verified` = 0");
    if ($invalidate) {
        $invalidate->bind_param("s", $email);
        $invalidate->execute();
        $invalidate->close();
    }

    // 5. Determine next safe ID to guarantee no Duplicate entry '0' error
    $maxRes = $conn->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM `email_verifications`");
    $assigned_id = ($maxRes && $mRow = $maxRes->fetch_assoc()) ? (int)$mRow['next_id'] : 1;

    // 6. Insert pending verification record with explicit safe ID
    $stmt = $conn->prepare("INSERT INTO `email_verifications` 
        (`id`, `email`, `pin_code`, `full_name`, `phone`, `service_type`, `message`, `expires_at`, `is_verified`, `resend_count`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 0)");

    if (!$stmt) {
        throw new Exception("Database prepare statement failed: " . $conn->error);
    }

    $stmt->bind_param("isssssss", $assigned_id, $email, $pin_code, $fullName, $phone, $service, $message, $expires_at);

    if (!$stmt->execute()) {
        throw new Exception("Database execution failed: " . $stmt->error);
    }
    $stmt->close();

    // 7. Dispatch email
    $mailResult = send_barangay_pin_email($email, $fullName, $pin_code);

    echo json_encode([
        'status'     => 'success',
        'message'    => !empty($mailResult['mail_sent']) 
            ? 'OTP Code has been sent to your Gmail. Please check your Inbox or Spam folder.' 
            : 'OTP Code generated. (Testing OTP: ' . $pin_code . ')',
        'mail_sent'  => !empty($mailResult['mail_sent']),
        'email'      => $email,
        'expires_in' => 180,
        'dev_pin'    => $pin_code
    ]);

} catch (\Throwable $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Unable to send OTP at this moment: ' . $e->getMessage()
    ]);
}
