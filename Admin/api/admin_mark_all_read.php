<?php
// Admin/api/admin_mark_all_read.php - Marks all user inquiries as read
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_connect.php';

try {
    $conn->query("UPDATE feedback_chats SET is_read = 1 WHERE sender_type = 'user' AND is_read = 0");
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

