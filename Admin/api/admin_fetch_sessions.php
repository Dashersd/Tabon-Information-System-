<?php
// Admin/api/admin_fetch_sessions.php - Session & Inbox Poller for Feedback Chat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_connect.php';

try {
    // 1. Query distinct thread sessions grouped by thread_id
    $sql = "SELECT 
                c.thread_id,
                MAX(c.created_at) AS last_activity,
                MIN(c.created_at) AS first_activity,
                (TIMESTAMPDIFF(SECOND, MIN(c.created_at), NOW()) > 86400) AS is_expired,
                SUM(CASE WHEN c.sender_type = 'user' AND c.is_read = 0 THEN 1 ELSE 0 END) AS unread_count,
                COUNT(c.id) AS total_messages
            FROM feedback_chats c
            GROUP BY c.thread_id
            ORDER BY last_activity DESC";

    $res = $conn->query($sql);
    $sessions = [];

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $thread_id = $row['thread_id'];

            // Earliest message for resident details & service tag
            $first_stmt = $conn->prepare("SELECT sender_name, email, message, created_at FROM feedback_chats WHERE thread_id = ? ORDER BY created_at ASC, id ASC LIMIT 1");
            $first_stmt->bind_param("s", $thread_id);
            $first_stmt->execute();
            $first_data = $first_stmt->get_result()->fetch_assoc();
            $first_stmt->close();

            // Latest message for preview snippet
            $last_stmt = $conn->prepare("SELECT sender_name, sender_type, message, file_attachment, file_name, created_at FROM feedback_chats WHERE thread_id = ? ORDER BY created_at DESC, id DESC LIMIT 1");
            $last_stmt->bind_param("s", $thread_id);
            $last_stmt->execute();
            $last_data = $last_stmt->get_result()->fetch_assoc();
            $last_stmt->close();

            $raw_initial_msg = $first_data['message'] ?? '';
            $service_type = 'General Inquiry';

            // Parse [Service: <ServiceName>]
            if (preg_match('/\[Service:\s*([^\]]+)\]/i', $raw_initial_msg, $matches)) {
                $service_type = trim($matches[1]);
            }

            $resident_name = !empty($first_data['sender_name']) ? $first_data['sender_name'] : 'Resident User';
            $email = !empty($first_data['email']) ? $first_data['email'] : null;

            $preview_msg = $last_data['message'] ?? '';
            if (empty($preview_msg) && !empty($last_data['file_attachment'])) {
                $preview_msg = '📎 Attachment: ' . ($last_data['file_name'] ?: 'File');
            } else {
                // Remove [Service: ...] prefix for cleaner snippet
                $preview_msg = preg_replace('/\[Service:\s*[^\]]+\]\s*/i', '', $preview_msg);
            }

            $sessions[] = [
                'thread_id' => $thread_id,
                'resident_name' => $resident_name,
                'email' => $email,
                'service_type' => $service_type,
                'is_expired' => (bool)$row['is_expired'],
                'unread_count' => (int)$row['unread_count'],
                'total_messages' => (int)$row['total_messages'],
                'last_activity' => $row['last_activity'],
                'last_activity_formatted' => date('h:i A', strtotime($row['last_activity'])),
                'last_sender_type' => $last_data['sender_type'] ?? 'user',
                'last_message_preview' => $preview_msg
            ];
        }
    }

    // 2. Query total unread count for user messages
    $unread_res = $conn->query("SELECT COUNT(*) AS total_unread FROM feedback_chats WHERE sender_type = 'user' AND is_read = 0");
    $total_unread = $unread_res ? (int)$unread_res->fetch_assoc()['total_unread'] : 0;

    // 3. Query top 10 unread notifications for bell dropdown
    $notif_res = $conn->query("SELECT id, thread_id, sender_name, email, message, created_at 
                               FROM feedback_chats 
                               WHERE sender_type = 'user' AND is_read = 0 
                               ORDER BY created_at DESC 
                               LIMIT 10");
    $notifications = [];
    if ($notif_res) {
        while ($n = $notif_res->fetch_assoc()) {
            $clean_msg = preg_replace('/\[Service:\s*[^\]]+\]\s*/i', '', $n['message']);
            $notifications[] = [
                'id' => (int)$n['id'],
                'thread_id' => $n['thread_id'],
                'sender_name' => $n['sender_name'],
                'email' => $n['email'],
                'message' => mb_strimwidth($clean_msg, 0, 75, '...'),
                'created_at' => $n['created_at'],
                'time_ago' => date('g:i A', strtotime($n['created_at']))
            ];
        }
    }

    // 4. Query latest message across system to trigger alerts
    $latest_res = $conn->query("SELECT id, thread_id, sender_name, message, created_at 
                                FROM feedback_chats 
                                WHERE sender_type = 'user' 
                                ORDER BY id DESC 
                                LIMIT 1");
    $latest_message = $latest_res && $latest_res->num_rows > 0 ? $latest_res->fetch_assoc() : null;

    echo json_encode([
        'status' => 'success',
        'sessions' => $sessions,
        'total_unread' => $total_unread,
        'notifications' => $notifications,
        'latest_message' => $latest_message
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

