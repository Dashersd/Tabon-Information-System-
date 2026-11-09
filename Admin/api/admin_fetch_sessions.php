<?php
// Admin/api/admin_fetch_sessions.php - Session & Inbox Poller for Feedback Chat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../db_connect.php';

    // 0. Ensure table exists
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

    // Safe truncation helper
    if (!function_exists('safe_mb_truncate')) {
        function safe_mb_truncate($str, $len = 75) {
            if (function_exists('mb_strimwidth')) {
                return mb_strimwidth($str, 0, $len, '...');
            }
            return (strlen($str) > $len) ? substr($str, 0, $len) . '...' : $str;
        }
    }

    $scope = strtolower(trim($_GET['scope'] ?? $_GET['category'] ?? 'all'));

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

    $requests_unread_total = 0;
    $feedback_unread_total = 0;

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $thread_id = $row['thread_id'];

            // Earliest message for resident details & service tag
            $first_data = null;
            $first_stmt = $conn->prepare("SELECT sender_name, email, message, file_attachment, file_name, created_at FROM feedback_chats WHERE thread_id = ? ORDER BY created_at ASC, id ASC LIMIT 1");
            if ($first_stmt) {
                $first_stmt->bind_param("s", $thread_id);
                $first_stmt->execute();
                $first_data = $first_stmt->get_result()->fetch_assoc();
                $first_stmt->close();
            }

            // Latest message for preview snippet
            $last_data = null;
            $last_stmt = $conn->prepare("SELECT sender_name, sender_type, message, file_attachment, file_name, created_at FROM feedback_chats WHERE thread_id = ? ORDER BY created_at DESC, id DESC LIMIT 1");
            if ($last_stmt) {
                $last_stmt->bind_param("s", $thread_id);
                $last_stmt->execute();
                $last_data = $last_stmt->get_result()->fetch_assoc();
                $last_stmt->close();
            }

            $raw_initial_msg = $first_data['message'] ?? '';
            $service_type = 'General Inquiry';

            // Parse [Service: <ServiceName>]
            if (preg_match('/\[Service:\s*([^\]]+)\]/i', $raw_initial_msg, $matches)) {
                $service_type = trim($matches[1]);
            }

            $is_service_request = (
                !empty($service_type) && 
                !in_array(strtolower($service_type), ['contact inquiry', 'general inquiry', 'feedback', 'general', 'inquiry', 'contact', 'citizen inquiry', 'citizen feedback'])
            );

            $thread_unread = (int)$row['unread_count'];
            if ($is_service_request) {
                $requests_unread_total += $thread_unread;
            } else {
                $feedback_unread_total += $thread_unread;
            }

            // Scope filter: 'requests' vs 'feedback'
            if ($scope === 'requests' || $scope === 'services' || $scope === 'service_requests') {
                if (!$is_service_request) {
                    continue; // Skip general feedback/contact inquiry
                }
            } elseif ($scope === 'feedback' || $scope === 'contact') {
                if ($is_service_request) {
                    continue; // Skip document service requests
                }
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

            $has_id_card = !empty($first_data['file_attachment']) || !empty($last_data['file_attachment']);

            $sessions[] = [
                'thread_id' => $thread_id,
                'resident_name' => $resident_name,
                'email' => $email,
                'service_type' => $service_type,
                'is_service_request' => $is_service_request,
                'has_id_card' => $has_id_card,
                'is_expired' => (bool)$row['is_expired'],
                'unread_count' => $thread_unread,
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

    $active_scope_unread = ($scope === 'requests' || $scope === 'services' || $scope === 'service_requests') 
        ? $requests_unread_total 
        : (($scope === 'feedback' || $scope === 'contact') ? $feedback_unread_total : $total_unread);

    // 3. Query top 10 unread notifications for bell dropdown
    $notif_res = $conn->query("SELECT id, thread_id, sender_name, email, message, created_at 
                               FROM feedback_chats 
                               WHERE sender_type = 'user' AND is_read = 0 
                               ORDER BY created_at DESC 
                               LIMIT 15");
    $notifications = [];
    if ($notif_res) {
        while ($n = $notif_res->fetch_assoc()) {
            $n_msg = $n['message'];
            $n_svc = 'General Inquiry';
            if (preg_match('/\[Service:\s*([^\]]+)\]/i', $n_msg, $n_matches)) {
                $n_svc = trim($n_matches[1]);
            }
            $n_is_req = (!empty($n_svc) && !in_array(strtolower($n_svc), ['contact inquiry', 'general inquiry', 'feedback', 'general', 'inquiry', 'contact', 'citizen inquiry', 'citizen feedback']));

            // Filter notifications according to scope if requested
            if (($scope === 'requests' && !$n_is_req) || ($scope === 'feedback' && $n_is_req)) {
                continue;
            }

            $clean_msg = preg_replace('/\[Service:\s*[^\]]+\]\s*/i', '', $n_msg);
            $notifications[] = [
                'id' => (int)$n['id'],
                'thread_id' => $n['thread_id'],
                'sender_name' => $n['sender_name'],
                'email' => $n['email'],
                'service_type' => $n_svc,
                'is_service_request' => $n_is_req,
                'message' => safe_mb_truncate($clean_msg, 75),
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
    $latest_message = ($latest_res && $latest_res->num_rows > 0) ? $latest_res->fetch_assoc() : null;

    echo json_encode([
        'status' => 'success',
        'scope' => $scope,
        'sessions' => $sessions,
        'total_unread' => $total_unread,
        'active_scope_unread' => $active_scope_unread,
        'requests_unread_count' => $requests_unread_total,
        'feedback_unread_count' => $feedback_unread_total,
        'notifications' => $notifications,
        'latest_message' => $latest_message
    ]);
} catch (\Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
