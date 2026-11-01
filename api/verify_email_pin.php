<?php
// api/verify_email_pin.php - Validates OTP, enforces 3-min expiry, and saves to feedback_chats
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit();
}

$email    = trim($_POST['email'] ?? '');
$pin_code = trim($_POST['pin_code'] ?? '');

if (empty($email) || empty($pin_code)) {
    echo json_encode(['status' => 'error', 'message' => 'Please provide both your email and the 6-digit OTP code.']);
    exit();
}

try {
    // 0. Ensure feedback_chats table exists
    $conn->query("CREATE TABLE IF NOT EXISTS `feedback_chats` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `thread_id` varchar(100) NOT NULL,
      `sender_name` varchar(255) NOT NULL,
      `email` varchar(150) DEFAULT NULL,
      `sender_type` enum('user','admin') NOT NULL DEFAULT 'user',
      `message` text NOT NULL,
      `file_attachment` varchar(255) DEFAULT NULL,
      `file_name` varchar(255) DEFAULT NULL,
      `is_read` tinyint(1) DEFAULT 0,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `idx_thread_id` (`thread_id`),
      KEY `idx_sender_type_read` (`sender_type`, `is_read`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Find pending verification record
    $stmt = $conn->prepare("SELECT `id`, `pin_code`, `full_name`, `phone`, `service_type`, `message`, `expires_at` 
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
        echo json_encode([
            'status'  => 'error', 
            'code'    => 'NOT_FOUND', 
            'message' => 'No active verification session found. Please submit your request again.'
        ]);
        exit();
    }

    // 1. Check expiration (3 minutes)
    $now = time();
    $expiry_timestamp = strtotime($record['expires_at']);

    if ($now > $expiry_timestamp) {
        echo json_encode([
            'status'  => 'error', 
            'code'    => 'EXPIRED', 
            'message' => 'Your OTP Code has expired after 3 minutes. Please click "Resend Code" to receive a fresh OTP.'
        ]);
        exit();
    }

    // 2. Check PIN code match
    if (trim($record['pin_code']) !== trim($pin_code)) {
        echo json_encode([
            'status'  => 'error', 
            'code'    => 'INVALID', 
            'message' => 'Invalid OTP Code. Please verify the 6-digit code received in your Gmail (check Inbox and Spam folder).'
        ]);
        exit();
    }

    // 3. PIN is Valid! Mark as verified
    $mark = $conn->prepare("UPDATE `email_verifications` SET `is_verified` = 1 WHERE `id` = ?");
    if ($mark) {
        $mark->bind_param("i", $record['id']);
        $mark->execute();
        $mark->close();
    }

    // 4. Insert into primary feedback_chats table for Admin
    $thread_id = 'thread_' . bin2hex(random_bytes(16));
    $service_tag = !empty($record['service_type']) ? $record['service_type'] : 'Contact Inquiry';
    $full_message = "[Service: " . $service_tag . "]\n\n" . $record['message'] . (!empty($record['phone']) ? "\n\nContact Phone: " . $record['phone'] : "");

    $chat_stmt = $conn->prepare("INSERT INTO `feedback_chats` 
        (`thread_id`, `sender_name`, `email`, `sender_type`, `message`, `is_read`, `created_at`) 
        VALUES (?, ?, ?, 'user', ?, 0, NOW())");

    if (!$chat_stmt) {
        throw new Exception("Chat prepare statement failed: " . $conn->error);
    }

    $chat_stmt->bind_param("ssss", $thread_id, $record['full_name'], $email, $full_message);

    if (!$chat_stmt->execute()) {
        throw new Exception("Chat execution failed: " . $chat_stmt->error);
    }
    $chat_stmt->close();

    // 5. Return official success message requested by user
    echo json_encode([
        'status'  => 'success',
        'message' => 'The Admin has received your request and wait for 3 to 5 business days to be finished and sent to your Gmail.'
    ]);

} catch (\Throwable $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Verification failed: ' . $e->getMessage()
    ]);
}
