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

// Generate secure 6-digit PIN code
$pin_code = sprintf("%06d", mt_rand(100000, 999999));

// 3 minutes expiration (180 seconds)
$expires_at = date('Y-m-d H:i:s', time() + 180);

// Invalidate any existing unverified codes for this email
$invalidate = $conn->prepare("UPDATE `email_verifications` SET `is_verified` = 2 WHERE `email` = ? AND `is_verified` = 0");
$invalidate->bind_param("s", $email);
$invalidate->execute();
$invalidate->close();

// Insert pending verification record
$stmt = $conn->prepare("INSERT INTO `email_verifications` 
    (`email`, `pin_code`, `full_name`, `phone`, `service_type`, `message`, `expires_at`, `is_verified`, `resend_count`) 
    VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0)");

$stmt->bind_param("sssssss", $email, $pin_code, $fullName, $phone, $service, $message, $expires_at);

if (!$stmt->execute()) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to initialize verification. Please try again.']);
    exit();
}
$stmt->close();

// Dispatch email
$mailResult = send_barangay_pin_email($email, $fullName, $pin_code);

echo json_encode([
    'status'     => 'success',
    'message'    => 'PIN Code has been sent to your Gmail.',
    'email'      => $email,
    'expires_in' => 180,
    'dev_pin'    => $pin_code // Available for local development testing
]);
