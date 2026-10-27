<?php
// Admin/api/admin_fetch_messages.php - Thread Message Fetcher for Feedback Chat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_connect.php';

$thread_id = trim($_GET['session_id'] ?? $_POST['session_id'] ?? '');

if (empty($thread_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Thread session ID is required.']);
    exit();
}

try {
    // 1. Mark all user messages in this thread as read
    $mark_stmt = $conn->prepare("UPDATE feedback_chats SET is_read = 1 WHERE thread_id = ? AND sender_type = 'user' AND is_read = 0");
    $mark_stmt->bind_param("s", $thread_id);
    $mark_stmt->execute();
    $mark_stmt->close();

    // 2. Check if the thread is expired (> 24 hours / 86,400 seconds)
    $exp_stmt = $conn->prepare("SELECT (TIMESTAMPDIFF(SECOND, MIN(created_at), NOW()) > 86400) AS is_expired FROM feedback_chats WHERE thread_id = ?");
    $exp_stmt->bind_param("s", $thread_id);
    $exp_stmt->execute();
    $exp_res = $exp_stmt->get_result();
    $exp_row = $exp_res->fetch_assoc();
    $is_expired = $exp_row ? (bool)$exp_row['is_expired'] : false;
    $exp_stmt->close();

    // 3. Fetch all messages in chronological order
    $msg_stmt = $conn->prepare("SELECT id, thread_id, sender_name, email, sender_type, message, file_attachment, file_name, is_read, created_at FROM feedback_chats WHERE thread_id = ? ORDER BY created_at ASC, id ASC");
    $msg_stmt->bind_param("s", $thread_id);
    $msg_stmt->execute();
    $msg_res = $msg_stmt->get_result();

    $messages = [];
    while ($row = $msg_res->fetch_assoc()) {
        $messages[] = [
            'id' => (int)$row['id'],
            'thread_id' => $row['thread_id'],
            'sender_name' => $row['sender_name'],
            'email' => $row['email'],
            'sender_type' => $row['sender_type'],
            'message' => $row['message'],
            'file_attachment' => $row['file_attachment'],
            'file_name' => $row['file_name'],
            'is_read' => (int)$row['is_read'],
            'created_at' => $row['created_at'],
            'time_formatted' => date('h:i A', strtotime($row['created_at'])),
            'date_group' => date('Y-m-d', strtotime($row['created_at']))
        ];
    }
    $msg_stmt->close();

    echo json_encode([
        'status' => 'success',
        'is_expired' => $is_expired,
        'messages' => $messages
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

