<?php
// Admin/api/admin_send_message.php - Reply & File Attachment Handler
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../db_connect.php';

    $thread_id = trim($_POST['session_id'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($thread_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Thread session ID is required.']);
        exit();
    }

    // Ensure feedback_chats table exists
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

    // 1. Expiration check: Verify if thread is over 24 hours (86,400s) old
    if ($exp_stmt = $conn->prepare("SELECT (TIMESTAMPDIFF(SECOND, MIN(created_at), NOW()) > 86400) AS is_expired FROM feedback_chats WHERE thread_id = ?")) {
        $exp_stmt->bind_param("s", $thread_id);
        $exp_stmt->execute();
        $exp_res = $exp_stmt->get_result();
        $exp_row = $exp_res ? $exp_res->fetch_assoc() : null;
        $exp_stmt->close();

        if ($exp_row && (bool)$exp_row['is_expired']) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Cannot send reply: This chat session has expired after 1 day.'
            ]);
            exit();
        }
    }

    // 2. Handle File Upload (Optional)
    $file_attachment = null;
    $file_name = null;

    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/chat_files/';
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0777, true);
        }

        $orig_name = basename($_FILES['attachment']['name']);
        $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip', 'xls', 'xlsx'];

        if (!in_array($ext, $allowed, true)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid file format. Allowed: PDF, DOC, DOCX, JPG, PNG, ZIP, XLS, XLSX.']);
            exit();
        }

        $clean_ext = preg_replace("/[^a-zA-Z0-9]/", "", $ext);
        $safe_filename = time() . '_' . uniqid() . '.' . $clean_ext;
        $target_path = $upload_dir . $safe_filename;

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_path)) {
            $file_attachment = 'uploads/chat_files/' . $safe_filename;
            $file_name = $orig_name;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save attachment file.']);
            exit();
        }
    }

    if (empty($message) && empty($file_attachment)) {
        echo json_encode(['status' => 'error', 'message' => 'Please provide a message or an attachment to send.']);
        exit();
    }

    // 3. Insert Admin Message
    $admin_sender = 'Admin';
    $maxChatRes = $conn->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM `feedback_chats`");
    $safe_chat_id = ($maxChatRes && $cr = $maxChatRes->fetch_assoc()) ? (int)$cr['next_id'] : 1;

    $insert_stmt = $conn->prepare("INSERT INTO feedback_chats (id, thread_id, sender_name, email, sender_type, message, file_attachment, file_name, is_read) VALUES (?, ?, ?, NULL, 'admin', ?, ?, ?, 0)");
    if (!$insert_stmt) {
        throw new Exception("Insert prepare failed: " . $conn->error);
    }
    $insert_stmt->bind_param("isssss", $safe_chat_id, $thread_id, $admin_sender, $message, $file_attachment, $file_name);
    
    if ($insert_stmt->execute()) {
        $new_id = $insert_stmt->insert_id ?: $safe_chat_id;
        $insert_stmt->close();

        echo json_encode([
            'status' => 'success',
            'message' => 'Message sent successfully.',
            'data' => [
                'id' => $new_id,
                'thread_id' => $thread_id,
                'sender_type' => 'admin',
                'sender_name' => $admin_sender,
                'message' => $message,
                'file_attachment' => $file_attachment,
                'file_name' => $file_name,
                'time_formatted' => date('h:i A')
            ]
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $insert_stmt->error]);
    }
} catch (\Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
