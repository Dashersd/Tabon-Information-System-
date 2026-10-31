<?php
// Admin/feedback.php - Barangay Tabon Admin Feedback Chat & Resident Inquiry Management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/db_connect.php';

// Safe check: Ensure feedback_chats table exists with proper indexes
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Chat - Barangay Tabon Admin</title>
    
    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- jQuery for AJAX calls -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Stylesheets -->
    <link rel="stylesheet" href="Admin CSS/admin_common.css?v=<?= time() ?>">
    <link rel="stylesheet" href="Admin CSS/feedback.css?v=<?= time() ?>">
    <style>
/* Admin/Admin CSS/feedbackchat.css - Barangay Tabon Admin Feedback Chat & Resident Inquiry Management */

/* Page & Layout Reset: Enforces strict 100vh app view without page-level scrollbars */
body.feedback-chat-page {
    height: 100vh !important;
    max-height: 100vh !important;
    overflow: hidden !important;
    margin: 0 !important;
    padding: 0 !important;
    display: flex !important;
    background-color: #f0f2f5 !important;
    font-family: 'Montserrat', sans-serif !important;
}

body.feedback-chat-page .sidebar {
    flex-shrink: 0 !important;
    width: 250px !important;
    height: 100vh !important;
    z-index: 30 !important;
}

body.feedback-chat-page .main-content {
    flex: 1 !important;
    min-width: 0 !important;
    min-height: 0 !important;
    height: 100vh !important;
    max-height: 100vh !important;
    padding: 0 !important;
    margin: 0 !important;
    overflow: hidden !important;
    display: flex !important;
    flex-direction: column !important;
    background-color: #f0f2f5 !important;
}

/* ======================================================== */
/* 1. TOPBAR & ACTION CONTROLS                             */
/* ======================================================== */
.top-header {
    height: 70px;
    background: #ffffff;
    padding: 0 28px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    box-sizing: border-box;
    flex-shrink: 0;
    z-index: 25;
}

.topbar-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.topbar-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.topbar-title i {
    color: #137547;
}

.topbar-right-actions {
    display: flex;
    align-items: center;
    gap: 14px;
}

/* Audio Toggle Button */
.btn-topbar-tool {
    padding: 8px 14px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background-color: #f8fafc;
    color: #475569;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s ease;
}

.btn-topbar-tool:hover {
    background-color: #f1f5f9;
    color: #0f172a;
    border-color: #cbd5e1;
}

.btn-topbar-tool.audio-enabled {
    background-color: #e8f5ee;
    color: #137547;
    border-color: #bbf7d0;
}

/* Notification Bell with Badge & Dropdown */
.notif-bell-wrapper {
    position: relative;
}

.btn-notif-bell {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #475569;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    position: relative;
    transition: all 0.2s ease;
}

.btn-notif-bell:hover {
    background-color: #e8f5ee;
    color: #137547;
}

.notif-badge {
    position: absolute;
    top: -2px;
    right: -2px;
    background-color: #ef4444;
    color: #ffffff;
    font-size: 0.7rem;
    font-weight: 700;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #ffffff;
    box-sizing: border-box;
}

.notif-dropdown {
    display: none;
    position: absolute;
    top: 50px;
    right: 0;
    width: 340px;
    background-color: #ffffff;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    border: 1px solid #e5e7eb;
    z-index: 100;
    overflow: hidden;
    animation: dropdownPop 0.2s ease-out;
}

.notif-dropdown.active {
    display: block;
}

@keyframes dropdownPop {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}

.notif-dropdown-header {
    padding: 12px 16px;
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notif-dropdown-header h4 {
    margin: 0;
    font-size: 0.88rem;
    font-weight: 700;
    color: #093d25;
}

.btn-mark-all-read {
    background: none;
    border: none;
    color: #137547;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    padding: 2px 6px;
}

.btn-mark-all-read:hover {
    text-decoration: underline;
}

.notif-items-list {
    max-height: 320px;
    overflow-y: auto;
}

.notif-item {
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    transition: background-color 0.15s;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.notif-item:hover {
    background-color: #f0fdf4;
}

.notif-item-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notif-name {
    font-size: 0.85rem;
    font-weight: 700;
    color: #1e293b;
}

.notif-time {
    font-size: 0.72rem;
    color: #94a3b8;
}

.notif-preview {
    font-size: 0.8rem;
    color: #475569;
    margin: 0;
    line-height: 1.4;
}

.notif-empty {
    padding: 25px 16px;
    text-align: center;
    color: #94a3b8;
    font-size: 0.85rem;
    font-style: italic;
}

/* User profile avatar */
.topbar-user {
    display: flex;
    align-items: center;
    gap: 10px;
    padding-left: 12px;
    border-left: 1px solid #e5e7eb;
}

.user-avatar-pill {
    width: 38px;
    height: 38px;
    background-color: #f1c40f;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: #000;
    font-size: 0.95rem;
}

/* ======================================================== */
/* 2. DESKTOP NOTIFICATION PROMPT BANNER                    */
/* ======================================================== */
.desktop-notif-banner {
    display: none;
    background: linear-gradient(90deg, #093d25 0%, #137547 100%);
    color: #ffffff;
    padding: 10px 24px;
    font-size: 0.84rem;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
    flex-shrink: 0;
    z-index: 22;
}

.desktop-notif-banner.show {
    display: flex;
}

.btn-enable-notif {
    background-color: #ffffff;
    color: #093d25;
    border: none;
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    margin-left: 12px;
    transition: all 0.2s;
}

.btn-enable-notif:hover {
    background-color: #f1f5f9;
}

.btn-dismiss-banner {
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.7);
    font-size: 1.1rem;
    cursor: pointer;
    margin-left: 8px;
    line-height: 1;
}

.btn-dismiss-banner:hover {
    color: #ffffff;
}

/* ======================================================== */
/* 3. APP SPLIT CONTAINER                                   */
/* ======================================================== */
.chat-app-wrapper {
    display: flex;
    flex: 1;
    min-width: 0;
    min-height: 0;
    height: calc(100vh - 70px);
    max-height: calc(100vh - 70px);
    background-color: #f0f2f5;
    overflow: hidden;
    position: relative;
    width: 100%;
}

/* ======================================================== */
/* 4. SIDEBAR CONVERSATIONS LIST                            */
/* ======================================================== */
.chat-sidebar {
    width: 360px;
    min-width: 320px;
    max-width: 400px;
    flex-shrink: 0;
    min-height: 0;
    background-color: #ffffff;
    border-right: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
    height: 100%;
    z-index: 10;
    overflow: hidden;
}

.chat-sidebar-header {
    padding: 16px 18px 12px 18px;
    border-bottom: 1px solid #f1f5f9;
    flex-shrink: 0;
}

.sidebar-title-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.sidebar-title-bar h3 {
    margin: 0;
    font-size: 1.18rem;
    font-weight: 800;
    color: #093d25;
    display: flex;
    align-items: center;
    gap: 8px;
}

.badge-inquiries-count {
    background-color: #e8f5ee;
    color: #137547;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 12px;
}

/* Live Search Box */
.chat-search-wrap {
    position: relative;
    margin-bottom: 10px;
}

.chat-search-wrap i.search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.9rem;
}

.chat-search-input {
    width: 100%;
    padding: 8px 32px 8px 36px;
    background-color: #f1f5f9;
    border: 1px solid transparent;
    border-radius: 20px;
    font-size: 0.85rem;
    outline: none;
    transition: all 0.2s;
    box-sizing: border-box;
}

.chat-search-input:focus {
    background-color: #ffffff;
    border-color: #137547;
    box-shadow: 0 0 0 3px rgba(19, 117, 71, 0.12);
}

.btn-clear-search {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 1.1rem;
    cursor: pointer;
    display: none;
    line-height: 1;
}

.btn-clear-search.visible {
    display: block;
}

/* Filter Tabs */
.chat-filter-tabs {
    display: flex;
    gap: 6px;
}

.filter-tab {
    flex: 1;
    padding: 6px 10px;
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 700;
    border: none;
    background-color: #f1f5f9;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;
}

.filter-tab:hover {
    background-color: #e2e8f0;
}

.filter-tab.active {
    background-color: #093d25;
    color: #ffffff;
}

/* Conversation Thread List */
.conversations-scroll-list {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 8px;
}

.conversation-item {
    display: flex;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.18s ease;
    margin-bottom: 5px;
    position: relative;
    border: 1px solid transparent;
}

.conversation-item:hover {
    background-color: #f8fafc;
    border-color: #e2e8f0;
}

.conversation-item.active {
    background-color: #e8f5ee;
    border-color: #bbf7d0;
}

.conv-avatar {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1rem;
    color: #ffffff;
    text-transform: uppercase;
    flex-shrink: 0;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
}

.conv-body {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.conv-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.conv-name {
    font-size: 0.9rem;
    font-weight: 700;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.conv-time {
    font-size: 0.72rem;
    color: #94a3b8;
    white-space: nowrap;
    margin-left: 6px;
}

.conv-service-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.72rem;
    font-weight: 600;
    color: #0369a1;
    background-color: #e0f2fe;
    padding: 2px 7px;
    border-radius: 4px;
    max-width: fit-content;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.conv-email-pill {
    font-size: 0.7rem;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.conv-footer-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    margin-top: 2px;
}

.conv-snippet {
    font-size: 0.8rem;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin: 0;
    flex: 1;
}

.conversation-item.unread .conv-name {
    color: #093d25;
}

.conversation-item.unread .conv-snippet {
    font-weight: 700;
    color: #0f172a;
}

.conv-badges-group {
    display: flex;
    align-items: center;
    gap: 5px;
    flex-shrink: 0;
}

.conv-unread-pill {
    background-color: #137547;
    color: #ffffff;
    font-size: 0.7rem;
    font-weight: 700;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.badge-expired-tag {
    background-color: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
    font-size: 0.68rem;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 4px;
}

.no-sessions-state {
    text-align: center;
    padding: 45px 20px;
    color: #94a3b8;
    font-size: 0.88rem;
}

/* ======================================================== */
/* 5. CHAT MAIN AREA & EMPTY STATE                         */
/* ======================================================== */
.chat-main-container {
    flex: 1;
    min-width: 0;
    min-height: 0;
    display: flex;
    flex-direction: column;
    height: 100%;
    background-color: #ffffff;
    position: relative;
    overflow: hidden;
}

/* Empty State */
.chat-empty-state {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #64748b;
    text-align: center;
    padding: 40px 20px;
    background-color: #f8fafc;
}

.chat-empty-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: #e8f5ee;
    color: #137547;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.6rem;
    margin-bottom: 18px;
    box-shadow: 0 4px 15px rgba(19, 117, 71, 0.12);
}

.chat-empty-state h3 {
    margin: 0 0 8px 0;
    font-size: 1.35rem;
    color: #093d25;
    font-weight: 800;
}

.chat-empty-state p {
    margin: 0 0 20px 0;
    font-size: 0.92rem;
    max-width: 380px;
    line-height: 1.5;
}

.empty-summary-badges {
    display: flex;
    gap: 12px;
}

.empty-pill-badge {
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.82rem;
    font-weight: 600;
    color: #334155;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.empty-pill-badge strong {
    color: #137547;
}

/* Chat Main Area (When a thread is selected) */
.chat-main-area {
    display: none;
    flex: 1;
    min-width: 0;
    min-height: 0;
    flex-direction: column;
    height: 100%;
}

.chat-main-area.active {
    display: flex;
}

/* Chat Header */
.chat-area-header {
    height: 70px;
    padding: 0 24px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background-color: #ffffff;
    z-index: 5;
    flex-shrink: 0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
}

.chat-header-resident-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.chat-header-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1rem;
    color: #ffffff;
    text-transform: uppercase;
    flex-shrink: 0;
}

.chat-header-info {
    min-width: 0;
}

.chat-header-info h4 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: #093d25;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.chat-header-badges-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 3px;
    flex-wrap: wrap;
}

.status-indicator-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.74rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 12px;
}

.status-indicator-tag.active {
    background-color: #dcfce7;
    color: #15803d;
}

.status-indicator-tag.expired {
    background-color: #fef3c7;
    color: #b45309;
}

.status-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
}

.status-indicator-tag.active .status-dot {
    background-color: #22c55e;
}

.status-indicator-tag.expired .status-dot {
    background-color: #f59e0b;
}

.header-service-chip {
    background-color: #e0f2fe;
    color: #0369a1;
    font-size: 0.74rem;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 6px;
}

.header-email-pill {
    background-color: #f1f5f9;
    color: #475569;
    font-size: 0.74rem;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 6px;
    text-decoration: none;
}

.chat-header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}

.btn-header-tool {
    padding: 7px 13px;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 600;
    border: 1px solid #e2e8f0;
    background-color: #f8fafc;
    color: #475569;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s ease;
    text-decoration: none;
}

.btn-header-tool:hover {
    background-color: #f1f5f9;
    color: #0f172a;
}

.btn-send-email-doc {
    background-color: #e8f5ee;
    color: #137547;
    border-color: #bbf7d0;
}

.btn-send-email-doc:hover {
    background-color: #137547;
    color: #ffffff;
    border-color: #137547;
}

/* ======================================================== */
/* 6. MESSAGES STREAM & BUBBLES                             */
/* ======================================================== */
.chat-messages-scroll {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 20px 24px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    background-color: #f8fafc;
    scroll-behavior: smooth;
}

.chat-messages-scroll > :first-child {
    margin-top: 6px;
}

/* Message Rows */
.msg-row {
    display: flex;
    gap: 10px;
    max-width: 72%;
}

.msg-row.user {
    align-self: flex-start;
}

.msg-row.admin {
    align-self: flex-end;
    flex-direction: row-reverse;
}

.msg-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 0.78rem;
    font-weight: 700;
    margin-top: auto;
}

.msg-body-wrapper {
    display: flex;
    flex-direction: column;
}

.msg-row.admin .msg-body-wrapper {
    align-items: flex-end;
}

.msg-sender-label {
    font-size: 0.75rem;
    color: #64748b;
    margin-bottom: 3px;
    margin-left: 4px;
    font-weight: 600;
}

/* Service Header Highlight Chip inside initial message */
.service-header-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background-color: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
    font-size: 0.78rem;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 6px;
    margin-bottom: 8px;
    width: fit-content;
}

.msg-bubble {
    padding: 12px 16px;
    font-size: 0.91rem;
    line-height: 1.5;
    word-break: break-word;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.msg-row.user .msg-bubble {
    background-color: #ffffff;
    color: #1e293b;
    border: 1px solid #e2e8f0;
    border-radius: 18px 18px 18px 4px;
}

.msg-row.admin .msg-bubble {
    background: linear-gradient(135deg, #15803d 0%, #093d25 100%);
    color: #ffffff;
    border-radius: 18px 18px 4px 18px;
}

/* Attachment Card inside Message */
.msg-attachment-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    background-color: rgba(255, 255, 255, 0.95);
    border-radius: 8px;
    margin-top: 8px;
    border: 1px solid #cbd5e1;
    text-decoration: none;
    color: #1e293b;
    font-size: 0.82rem;
    font-weight: 600;
    transition: all 0.2s;
}

.msg-row.admin .msg-attachment-card {
    background-color: #ffffff;
    color: #093d25;
}

.msg-attachment-card:hover {
    background-color: #f1f5f9;
    transform: translateY(-1px);
}

.msg-attachment-card i {
    font-size: 1.25rem;
    color: #137547;
}

.msg-time-status {
    font-size: 0.7rem;
    color: #94a3b8;
    margin-top: 4px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.msg-row.user .msg-time-status {
    margin-left: 4px;
}

.msg-row.admin .msg-time-status {
    margin-right: 4px;
}

/* ======================================================== */
/* 7. QUICK REPLIES BAR                                     */
/* ======================================================== */
.quick-replies-bar {
    padding: 8px 24px;
    background-color: #ffffff;
    border-top: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    gap: 8px;
    overflow-x: auto;
    flex-shrink: 0;
}

.quick-reply-chip {
    padding: 5px 12px;
    border-radius: 16px;
    background-color: #f1f5f9;
    border: 1px solid #e2e8f0;
    color: #334155;
    font-size: 0.77rem;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.18s ease;
}

.quick-reply-chip:hover {
    background-color: #e8f5ee;
    color: #137547;
    border-color: #bbf7d0;
}

/* ======================================================== */
/* 8. FILE ATTACHMENT PREVIEW BAR                           */
/* ======================================================== */
.admin-file-preview-bar {
    display: none;
    align-items: center;
    justify-content: space-between;
    padding: 8px 24px;
    background-color: #f0fdf4;
    border-top: 1px dashed #86efac;
    font-size: 0.82rem;
    color: #15803d;
    font-weight: 600;
}

.admin-file-preview-bar.active {
    display: flex;
}

.btn-remove-preview-file {
    background: none;
    border: none;
    color: #ef4444;
    font-size: 1.2rem;
    cursor: pointer;
    line-height: 1;
}

/* ======================================================== */
/* 9. CHAT INPUT BAR & 1-DAY EXPIRATION NOTICE             */
/* ======================================================== */
.chat-input-area {
    padding: 12px 24px;
    background-color: #ffffff;
    border-top: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
    z-index: 5;
}

.btn-attach-file {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    transition: all 0.2s;
    flex-shrink: 0;
}

.btn-attach-file:hover {
    background-color: #e8f5ee;
    color: #137547;
    border-color: #bbf7d0;
}

.chat-input-box {
    flex: 1;
    position: relative;
}

.chat-input-field {
    width: 100%;
    padding: 11px 18px;
    background-color: #f1f5f9;
    border: 1px solid transparent;
    border-radius: 24px;
    font-size: 0.91rem;
    outline: none;
    font-family: inherit;
    box-sizing: border-box;
    transition: all 0.2s;
}

.chat-input-field:focus {
    background-color: #ffffff;
    border-color: #137547;
    box-shadow: 0 0 0 3px rgba(19, 117, 71, 0.12);
}

.btn-send-reply {
    padding: 10px 20px;
    border-radius: 24px;
    background: linear-gradient(135deg, #15803d 0%, #093d25 100%);
    color: #ffffff;
    border: none;
    cursor: pointer;
    font-size: 0.88rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s ease;
    flex-shrink: 0;
    box-shadow: 0 3px 8px rgba(9, 61, 37, 0.2);
}

.btn-send-reply:hover {
    background: #062b1a;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(9, 61, 37, 0.25);
}

/* 1-Day Expiration Notice Banner */
.admin-expired-chat-notice {
    display: none;
    padding: 16px 24px;
    background-color: #fffbeb;
    border-top: 1px solid #fef3c7;
    color: #92400e;
    font-size: 0.86rem;
    line-height: 1.5;
    align-items: center;
    gap: 12px;
}

.admin-expired-chat-notice.active {
    display: flex;
}

.admin-expired-chat-notice i {
    font-size: 1.5rem;
    color: #d97706;
    flex-shrink: 0;
}

/* ======================================================== */
/* 10. FLOATING TOAST NOTIFICATION CONTAINER                */
/* ======================================================== */
.chat-toast-container {
    position: fixed;
    bottom: 25px;
    right: 25px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 10px;
    pointer-events: none;
}

.chat-toast-card {
    pointer-events: auto;
    width: 340px;
    background-color: #ffffff;
    border-left: 5px solid #137547;
    border-radius: 10px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    animation: toastSlideIn 0.3s ease-out;
}

@keyframes toastSlideIn {
    from { opacity: 0; transform: translateX(50px); }
    to { opacity: 1; transform: translateX(0); }
}

.toast-top-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.toast-resident-name {
    font-weight: 700;
    color: #093d25;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 6px;
}

.toast-close-btn {
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 1.1rem;
    cursor: pointer;
    line-height: 1;
}

.toast-message-body {
    font-size: 0.82rem;
    color: #475569;
    line-height: 1.4;
    margin: 0;
}

.toast-actions-row {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 4px;
}

.btn-toast-reply {
    padding: 4px 10px;
    background-color: #137547;
    color: #ffffff;
    border: none;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    cursor: pointer;
}

.btn-toast-dismiss {
    padding: 4px 8px;
    background: none;
    border: 1px solid #cbd5e1;
    color: #64748b;
    border-radius: 6px;
    font-size: 0.75rem;
    cursor: pointer;
}

/* ======================================================== */
/* 11. SLIM SCROLLBARS                                      */
/* ======================================================== */
.chat-messages-scroll,
.conversations-scroll-list,
.notif-items-list {
    scrollbar-width: thin;
    scrollbar-color: rgba(0, 0, 0, 0.16) transparent;
}

.chat-messages-scroll::-webkit-scrollbar,
.conversations-scroll-list::-webkit-scrollbar,
.notif-items-list::-webkit-scrollbar {
    width: 5px;
}

.chat-messages-scroll::-webkit-scrollbar-track,
.conversations-scroll-list::-webkit-scrollbar-track,
.notif-items-list::-webkit-scrollbar-track {
    background: transparent;
}

.chat-messages-scroll::-webkit-scrollbar-thumb,
.conversations-scroll-list::-webkit-scrollbar-thumb,
.notif-items-list::-webkit-scrollbar-thumb {
    background-color: rgba(0, 0, 0, 0.16);
    border-radius: 10px;
}

.chat-messages-scroll::-webkit-scrollbar-thumb:hover,
.conversations-scroll-list::-webkit-scrollbar-thumb:hover,
.notif-items-list::-webkit-scrollbar-thumb:hover {
    background-color: rgba(0, 0, 0, 0.32);
}

/* Responsive adjustments */
@media (max-width: 900px) {
    .chat-sidebar {
        width: 100%;
        min-width: 100%;
        max-width: 100%;
    }
    .chat-main-container {
        display: none;
    }
    .chat-app-wrapper.chat-open .chat-sidebar {
        display: none;
    }
    .chat-app-wrapper.chat-open .chat-main-container {
        display: flex;
    }
}



/* ======================================================== */
/* EMAIL RESPONSE ACTION BAR (Replaces Obsolete Chat Input) */
/* ======================================================== */
.btn-send-email-doc {
    background-color: #000000 !important;
    color: #ffffff !important;
    border-color: #000000 !important;
}

.btn-send-email-doc:hover {
    background-color: #222222 !important;
    color: #ffffff !important;
    border-color: #222222 !important;
}

.chat-email-reply-bar {
    padding: 16px 24px;
    background-color: #ffffff;
    border-top: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-shrink: 0;
    z-index: 5;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.03);
}

.email-reply-bar-meta {
    display: flex;
    align-items: center;
    gap: 12px;
}

.email-reply-bar-icon {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background-color: #f1f5f9;
    color: #000000;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
    border: 1px solid #e2e8f0;
}

.email-reply-bar-title {
    font-weight: 700;
    font-size: 0.92rem;
    color: #0f172a;
}

.email-reply-bar-sub {
    font-size: 0.8rem;
    color: #64748b;
    margin-top: 2px;
}

.btn-email-reply-action {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 30px;
    background-color: #000000 !important;
    color: #ffffff !important;
    font-size: 0.88rem;
    font-weight: 600;
    text-decoration: none !important;
    transition: all 0.2s ease;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
    flex-shrink: 0;
}

.btn-email-reply-action:hover {
    background-color: #222222 !important;
    color: #ffffff !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.3);
}

@media (max-width: 768px) {
    .chat-email-reply-bar {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    .btn-email-reply-action {
        width: 100%;
        justify-content: center;
    }
}
    </style>
</head>
<body class="feedback-chat-page">

    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../Barangay Logo/Logo.png" alt="Barangay Logo" width="70" height="70" style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover; display: inline-block;" onerror="this.src='https://via.placeholder.com/70'">
            <h3>Brgy Tabon</h3>
            <p>Info SpotMap</p>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="controlpanel.php"><i class="fa-solid fa-desktop"></i> Control Panel</a></li>
                <li><a href="about.php"><i class="fa-solid fa-circle-info"></i> About</a></li>
                <li>
                    <a class="dropdown-btn"><i class="fa-solid fa-map-location-dot"></i> SpotMap Manage <i class="fa-solid fa-caret-down" style="margin-left: auto;"></i></a>
                    <div class="dropdown-container">
                        <a href="Legend.php">Legend</a>
                        <a href="Purok 1.php">Purok Mesaya</a>
                        <a href="Purok 2.php">Purok Metamed</a>
                        <a href="Purok 3.php">Purok Melitebangay</a>
                    </div>
                </li>
                <li>
                    <a class="dropdown-btn"><i class="fa-solid fa-users"></i> Household <i class="fa-solid fa-caret-down" style="margin-left: auto;"></i></a>
                    <div class="dropdown-container">
                        <a href="Legend Files.php">Legend Files</a>
                        <a href="Resident 1.php">Resident 1</a>
                        <a href="Resident 2.php">Resident 2</a>
                        <a href="Resident 3.php">Resident 3</a>
                    </div>
                </li>
                <li><a href="medialibrary.php"><i class="fa-regular fa-images"></i> Media Library</a></li>
                <li>
                    <a class="dropdown-btn"><i class="fa-solid fa-user-tie"></i> Barangay Board <i class="fa-solid fa-caret-down" style="margin-left: auto;"></i></a>
                    <div class="dropdown-container">
                        <a href="barangayofficial.php">Barangay Official</a>
                        <a href="skofficial.php">SK Official</a>
                    </div>
                </li>
                <li><a href="services.php"><i class="fa-solid fa-bell-concierge"></i> Services</a></li>
                <li><a href="announcements.php"><i class="fa-solid fa-bullhorn"></i> Announcement</a></li>
                <li>
                    <a href="feedback.php" class="active">
                        <i class="fa-solid fa-comments"></i> Feedback
                    </a>
                </li>
                <li><a href="settings.php"><i class="fa-solid fa-gear"></i> Admin Settings</a></li>
                
                <br>
                <li><a href="../login.php?logout=1"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
            </ul>
        </nav>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var dropdowns = document.getElementsByClassName("dropdown-btn");
                for (var i = 0; i < dropdowns.length; i++) {
                    dropdowns[i].addEventListener("click", function() {
                        this.classList.toggle("active");
                        var dropdownContent = this.nextElementSibling;
                        if (dropdownContent.style.display === "block" || dropdownContent.classList.contains("active")) {
                            dropdownContent.style.display = "none";
                            dropdownContent.classList.remove("active");
                        } else {
                            dropdownContent.style.display = "block";
                            dropdownContent.classList.add("active");
                        }
                    });
                }
            });
        </script>
    </aside>

    <!-- Main Content Area -->
    <div class="main-content">
        
        <!-- Top Navbar Header -->
        <div class="top-header">
            <div class="topbar-left">
                <h1 class="topbar-title">
                    <i class="bi bi-chat-dots-fill"></i> Feedback & Resident Inquiries
                </h1>
            </div>
            
            <div class="topbar-right-actions">
                <!-- Audio Alert Toggle Button -->
                <button type="button" class="btn-topbar-tool audio-enabled" id="btn-audio-toggle" title="Toggle audio chime alert for incoming messages">
                    <i class="bi bi-volume-up-fill" id="audio-toggle-icon"></i>
                    <span id="audio-toggle-text">Sound On</span>
                </button>

                <!-- Notifications Bell with Dropdown -->
                <div class="notif-bell-wrapper">
                    <button type="button" class="btn-notif-bell" id="btn-notif-bell" title="Unread Inquiries Notification">
                        <i class="bi bi-bell-fill"></i>
                        <span class="notif-badge" id="notif-badge-count" style="display: none;">0</span>
                    </button>

                    <!-- Notifications Dropdown -->
                    <div class="notif-dropdown" id="notif-dropdown-menu">
                        <div class="notif-dropdown-header">
                            <h4>Unread Inquiries</h4>
                            <button type="button" class="btn-mark-all-read" id="btn-mark-all-read">Mark all as read</button>
                        </div>
                        <div class="notif-items-list" id="notif-items-container">
                            <div class="notif-empty">No unread inquiries.</div>
                        </div>
                    </div>
                </div>

                <!-- Admin User Profile -->
                <div class="topbar-user">
                    <div style="text-align: right;">
                        <span style="font-size: 13px; font-weight: 700; color: #1f2937; display: block;">Barangay Secretary</span>
                        <p style="font-size: 11px; color: #64748b; margin: 0;">Administrator</p>
                    </div>
                    <div class="user-avatar-pill">A</div>
                </div>
            </div>
        </div>

        <!-- Desktop Notification Permission Request Banner -->
        <div class="desktop-notif-banner" id="desktop-notif-banner">
            <div>
                <i class="bi bi-info-circle-fill" style="margin-right: 6px;"></i>
                <span>Enable desktop notifications to receive immediate alerts when residents submit feedback or inquiry messages.</span>
                <button type="button" class="btn-enable-notif" id="btn-enable-desktop-notif">Enable Notifications</button>
            </div>
            <button type="button" class="btn-dismiss-banner" id="btn-dismiss-desktop-banner">&times;</button>
        </div>

        <!-- Messenger UI Layout Container -->
        <div class="chat-app-wrapper" id="chatAppWrapper">
            
            <!-- 1. SIDEBAR (CONVERSATIONS LIST) -->
            <div class="chat-sidebar">
                <div class="chat-sidebar-header">
                    <div class="sidebar-title-bar">
                        <h3><i class="bi bi-inbox-fill"></i> Inquiries</h3>
                        <span class="badge-inquiries-count" id="total-inquiries-pill">0 Total</span>
                    </div>

                    <!-- Live Search Input -->
                    <div class="chat-search-wrap">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" id="chat-search-input" class="chat-search-input" placeholder="Search by name, email, service..." autocomplete="off">
                        <button type="button" class="btn-clear-search" id="btn-clear-search">&times;</button>
                    </div>

                    <!-- Filter Tabs -->
                    <div class="chat-filter-tabs">
                        <button type="button" class="filter-tab active" data-filter="all">All Inquiries</button>
                        <button type="button" class="filter-tab" data-filter="unread">Unread (<span id="tab-unread-count">0</span>)</button>
                    </div>
                </div>

                <!-- Scrollable Conversations List -->
                <div class="conversations-scroll-list" id="conversations-list-container">
                    <div class="no-sessions-state">
                        <i class="bi bi-hourglass-split" style="font-size: 1.8rem; display: block; margin-bottom: 8px;"></i>
                        Loading conversations...
                    </div>
                </div>
            </div>

            <!-- 2. MAIN CHAT AREA & EMPTY STATE -->
            <div class="chat-main-container">
                
                <!-- EMPTY STATE (No conversation selected) -->
                <div class="chat-empty-state" id="chat-empty-state">
                    <div class="chat-empty-icon">
                        <i class="bi bi-chat-square-quote-fill"></i>
                    </div>
                    <h3>Select an Inquiry Thread</h3>
                    <p>Choose a resident inquiry from the list on the left to read their message history, review attachments, and send official replies.</p>
                    <div class="empty-summary-badges">
                        <span class="empty-pill-badge"><i class="bi bi-chat-dots"></i> Total: <strong id="empty-total-count">0</strong></span>
                        <span class="empty-pill-badge"><i class="bi bi-envelope-exclamation"></i> Unread: <strong id="empty-unread-count">0</strong></span>
                    </div>
                </div>

                <!-- ACTIVE CHAT VIEW -->
                <div class="chat-main-area" id="chat-main-area">
                    
                    <!-- Chat Header -->
                    <div class="chat-area-header">
                        <div class="chat-header-resident-meta">
                            <div class="chat-header-avatar" id="chat-header-avatar" style="background-color: #10b981;">U</div>
                            <div class="chat-header-info">
                                <h4 id="chat-header-name">Resident Name</h4>
                                <div class="chat-header-badges-row">
                                    <span class="status-indicator-tag active" id="chat-header-status-badge">
                                        <span class="status-dot"></span> <span id="chat-header-status-text">Citizen Inquirer</span>
                                    </span>
                                    <span class="header-service-chip" id="chat-header-service">General Inquiry</span>
                                    <a href="#" class="header-email-pill" id="chat-header-email" style="display: none;">
                                        <i class="bi bi-envelope"></i> <span id="chat-header-email-text">email@domain.com</span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="chat-header-actions">
                            <!-- Send Document via Gmail Action Button -->
                            <a href="#" class="btn-header-tool btn-send-email-doc" id="btn-send-doc-email" target="_blank" style="display: none; background: #000000; color: #ffffff; border-color: #000000;" title="Open Gmail composer to send response/documents">
                                <i class="bi bi-envelope-check-fill"></i> <span>Send Document via Gmail</span>
                            </a>

                            <!-- Manual Refresh Button -->
                            <button type="button" class="btn-header-tool" id="btn-refresh-chat" title="Refresh messages in this thread">
                                <i class="bi bi-arrow-clockwise"></i> <span>Refresh</span>
                            </button>
                        </div>
                    </div>

                    <!-- Scrollable Message Stream -->
                    <div class="chat-messages-scroll" id="chat-messages">
                        <!-- Messages dynamically populated by JS -->
                    </div>

                    <!-- Email Response Action Footer (Direct Gmail Reply) -->
                    <div class="chat-email-reply-bar" id="chat-email-reply-bar" style="display: none;">
                        <div class="email-reply-bar-meta">
                            <div class="email-reply-bar-icon">
                                <i class="bi bi-envelope-check-fill"></i>
                            </div>
                            <div class="email-reply-bar-text">
                                <div class="email-reply-bar-title">Official Resident Response</div>
                                <div class="email-reply-bar-sub">Resident inquiries and document requests are responded to directly via verified Gmail.</div>
                            </div>
                        </div>
                        <a href="#" class="btn-email-reply-action" id="btn-footer-send-email" target="_blank">
                            <i class="bi bi-envelope-paper-fill"></i> <span>Send Response via Gmail</span>
                        </a>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- Floating Toast Notification Container -->
    <div class="chat-toast-container" id="chat-toast-container"></div>

    <!-- ======================================================== -->
    <!-- CLIENT-SIDE JAVASCRIPT LOGIC & REAL-TIME POLLING         -->
    <!-- ======================================================== -->
    <script>
    (function($) {
        'use strict';

        // State Variables
        let currentThreadId = null;
        let currentThreadData = null;
        let currentFilter = 'all';
        let lastSeenMaxMsgId = 0;
        let lastRenderedSessionsJson = '';
        let lastRenderedMessagesJson = '';
        let titleBlinkInterval = null;
        let originalPageTitle = document.title;
        let soundEnabled = (localStorage.getItem('tabon_chat_sound') !== '0' && localStorage.getItem('maruing_chat_sound') !== '0');
        let selectedFile = null;

        // Initialize on DOM Ready
        $(document).ready(function() {
            initAudioToggle();
            initDesktopNotifications();
            bindEventHandlers();
            
            // Initial poll
            fetchSessions(true);

            // Background Polling every 4 seconds
            setInterval(function() {
                fetchSessions(false);
                if (currentThreadId) {
                    fetchMessages(currentThreadId, false);
                }
            }, 4000);
        });

        // 1. Audio Synthesis Chime using Web Audio API (D5: 587Hz + A5: 880Hz)
        function playChimeSound() {
            if (!soundEnabled) return;
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;
                const ctx = new AudioContext();

                // Tone 1: D5 (587.33 Hz)
                const osc1 = ctx.createOscillator();
                const gain1 = ctx.createGain();
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(587.33, ctx.currentTime);
                gain1.gain.setValueAtTime(0.12, ctx.currentTime);
                gain1.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
                osc1.connect(gain1);
                gain1.connect(ctx.destination);
                osc1.start(ctx.currentTime);
                osc1.stop(ctx.currentTime + 0.35);

                // Tone 2: A5 (880.00 Hz) slightly staggered
                const osc2 = ctx.createOscillator();
                const gain2 = ctx.createGain();
                osc2.type = 'sine';
                osc2.frequency.setValueAtTime(880.00, ctx.currentTime + 0.12);
                gain2.gain.setValueAtTime(0.15, ctx.currentTime + 0.12);
                gain2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.55);
                osc2.connect(gain2);
                gain2.connect(ctx.destination);
                osc2.start(ctx.currentTime + 0.12);
                osc2.stop(ctx.currentTime + 0.55);
            } catch (e) {
                console.warn('Web Audio chime alert error:', e);
            }
        }

        function initAudioToggle() {
            updateAudioButtonUI();
            $('#btn-audio-toggle').on('click', function() {
                soundEnabled = !soundEnabled;
                localStorage.setItem('tabon_chat_sound', soundEnabled ? '1' : '0');
                localStorage.setItem('maruing_chat_sound', soundEnabled ? '1' : '0');
                updateAudioButtonUI();
                if (soundEnabled) playChimeSound();
            });
        }

        function updateAudioButtonUI() {
            const btn = $('#btn-audio-toggle');
            const icon = $('#audio-toggle-icon');
            const text = $('#audio-toggle-text');
            if (soundEnabled) {
                btn.addClass('audio-enabled');
                icon.attr('class', 'bi bi-volume-up-fill');
                text.text('Sound On');
            } else {
                btn.removeClass('audio-enabled');
                icon.attr('class', 'bi bi-volume-mute-fill');
                text.text('Muted');
            }
        }

        // 2. Desktop Notifications Setup
        function initDesktopNotifications() {
            if ('Notification' in window) {
                if (Notification.permission === 'default') {
                    $('#desktop-notif-banner').addClass('show');
                }
            }

            $('#btn-enable-desktop-notif').on('click', function() {
                if ('Notification' in window) {
                    Notification.requestPermission().then(function(permission) {
                        if (permission === 'granted') {
                            $('#desktop-notif-banner').removeClass('show');
                        }
                    });
                }
            });

            $('#btn-dismiss-desktop-banner').on('click', function() {
                $('#desktop-notif-banner').removeClass('show');
            });
        }

        function showDesktopNotification(title, body, threadId) {
            if ('Notification' in window && Notification.permission === 'granted' && document.hidden) {
                const notif = new Notification(title, {
                    body: body,
                    icon: '../Barangay Logo/Logo.png'
                });
                notif.onclick = function() {
                    window.focus();
                    if (threadId) selectSession(threadId);
                    this.close();
                };
            }
        }

        // 3. Tab Title Alert & Floating Toast
        function startTitleBlink(senderName) {
            if (titleBlinkInterval) clearInterval(titleBlinkInterval);
            let toggle = false;
            titleBlinkInterval = setInterval(function() {
                document.title = toggle ? `🔔 New message from ${senderName}!` : originalPageTitle;
                toggle = !toggle;
            }, 1200);
        }

        function stopTitleBlink() {
            if (titleBlinkInterval) {
                clearInterval(titleBlinkInterval);
                titleBlinkInterval = null;
                document.title = originalPageTitle;
            }
        }

        $(window).on('focus click', function() {
            stopTitleBlink();
        });

        function showFloatingToast(threadId, senderName, message) {
            const toastId = 'toast-' + Date.now();
            const cleanMsg = escapeHtml(message.replace(/\[Service:\s*[^\]]+\]\s*/i, ''));
            const html = `
                <div class="chat-toast-card" id="${toastId}">
                    <div class="toast-top-row">
                        <span class="toast-resident-name"><i class="bi bi-chat-quote-fill" style="color:#137547;"></i> ${escapeHtml(senderName)}</span>
                        <button type="button" class="toast-close-btn" onclick="$('#${toastId}').remove()">&times;</button>
                    </div>
                    <p class="toast-message-body">${cleanMsg.substring(0, 85)}${cleanMsg.length > 85 ? '...' : ''}</p>
                    <div class="toast-actions-row">
                        <button type="button" class="btn-toast-dismiss" onclick="$('#${toastId}').remove()">Dismiss</button>
                        <button type="button" class="btn-toast-reply" data-thread="${threadId}">Reply Now</button>
                    </div>
                </div>
            `;
            const $toast = $(html);
            $('#chat-toast-container').append($toast);

            $toast.find('.btn-toast-reply').on('click', function() {
                selectSession(threadId);
                $toast.remove();
            });

            setTimeout(function() {
                $toast.fadeOut(300, function() { $(this).remove(); });
            }, 7000);
        }

        // 4. Fetch Sessions & Poll Inbox
        function fetchSessions(isInitialLoad) {
            $.ajax({
                url: 'api/admin_fetch_sessions.php',
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        updateSessionsUI(res.sessions, isInitialLoad);
                        updateNotificationsUI(res.notifications, res.total_unread);

                        // Detect incoming newest message across system
                        if (res.latest_message && res.latest_message.id) {
                            const latestId = parseInt(res.latest_message.id);
                            if (lastSeenMaxMsgId > 0 && latestId > lastSeenMaxMsgId) {
                                // New message arrived!
                                playChimeSound();
                                showFloatingToast(res.latest_message.thread_id, res.latest_message.sender_name, res.latest_message.message);
                                startTitleBlink(res.latest_message.sender_name);
                                showDesktopNotification(
                                    `New message from ${res.latest_message.sender_name}`,
                                    res.latest_message.message,
                                    res.latest_message.thread_id
                                );
                            }
                            lastSeenMaxMsgId = latestId;
                        }
                    }
                }
            });
        }

        function updateSessionsUI(sessions, isInitialLoad) {
            $('#total-inquiries-pill').text(`${sessions.length} Total`);
            $('#empty-total-count').text(sessions.length);

            let unreadCount = 0;
            sessions.forEach(s => { unreadCount += s.unread_count; });
            $('#tab-unread-count').text(unreadCount);
            $('#empty-unread-count').text(unreadCount);

            // Compare with cached JSON to avoid DOM rebuilding if unchanged
            const sessionsJson = JSON.stringify(sessions);
            if (sessionsJson === lastRenderedSessionsJson) return;
            lastRenderedSessionsJson = sessionsJson;

            const container = $('#conversations-list-container');
            container.empty();

            if (!sessions || sessions.length === 0) {
                container.html('<div class="no-sessions-state"><i class="bi bi-chat-dots" style="font-size:2rem; display:block; margin-bottom:8px;"></i>No inquiries found.</div>');
                return;
            }

            sessions.forEach(function(s) {
                const isActive = (s.thread_id === currentThreadId);
                const isUnread = (s.unread_count > 0);
                const avatarColor = getAvatarColor(s.resident_name);
                const initial = getInitials(s.resident_name);

                let previewText = escapeHtml(s.last_message_preview || 'Inquiry started');
                if (s.last_sender_type === 'admin') {
                    previewText = `<strong>You:</strong> ${previewText}`;
                }

                const expiredBadge = s.is_expired ? '<span class="badge-expired-tag">Expired</span>' : '';
                const unreadBadge = isUnread ? `<span class="conv-unread-pill">${s.unread_count}</span>` : '';
                const emailBadge = s.email ? `<span class="conv-email-pill"><i class="bi bi-envelope"></i> ${escapeHtml(s.email)}</span>` : '';

                const itemHtml = `
                    <div class="conversation-item ${isActive ? 'active' : ''} ${isUnread ? 'unread' : ''}" 
                         data-thread-id="${s.thread_id}" 
                         data-name="${escapeHtml(s.resident_name.toLowerCase())}" 
                         data-email="${escapeHtml((s.email || '').toLowerCase())}" 
                         data-service="${escapeHtml((s.service_type || '').toLowerCase())}" 
                         data-msg="${escapeHtml(previewText.toLowerCase())}" 
                         data-unread="${s.unread_count}">
                        
                        <div class="conv-avatar" style="background-color: ${avatarColor};">
                            ${initial}
                        </div>

                        <div class="conv-body">
                            <div class="conv-header-row">
                                <span class="conv-name">${escapeHtml(s.resident_name)}</span>
                                <span class="conv-time">${s.last_activity_formatted}</span>
                            </div>
                            
                            <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                <span class="conv-service-tag"><i class="bi bi-tag-fill"></i> ${escapeHtml(s.service_type)}</span>
                                ${emailBadge}
                            </div>

                            <div class="conv-footer-row">
                                <p class="conv-snippet">${previewText}</p>
                                <div class="conv-badges-group">
                                    ${expiredBadge}
                                    ${unreadBadge}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                container.append(itemHtml);
            });

            applySearchFilter();

            // Auto-select first thread if nothing is selected and this is initial load
            if (isInitialLoad && !currentThreadId && sessions.length > 0) {
                selectSession(sessions[0].thread_id);
            }
        }

        function updateNotificationsUI(notifications, totalUnread) {
            const badge = $('#notif-badge-count');
            if (totalUnread > 0) {
                badge.text(totalUnread > 99 ? '99+' : totalUnread).show();
            } else {
                badge.hide();
            }

            const container = $('#notif-items-container');
            container.empty();

            if (!notifications || notifications.length === 0) {
                container.html('<div class="notif-empty">No unread inquiries.</div>');
                return;
            }

            notifications.forEach(function(n) {
                const itemHtml = `
                    <div class="notif-item" data-thread="${n.thread_id}">
                        <div class="notif-item-top">
                            <span class="notif-name">${escapeHtml(n.sender_name)}</span>
                            <span class="notif-time">${n.time_ago}</span>
                        </div>
                        <p class="notif-preview">${escapeHtml(n.message)}</p>
                    </div>
                `;
                container.append(itemHtml);
            });
        }

        // 5. Select & Fetch Thread Messages
        function selectSession(threadId) {
            currentThreadId = threadId;

            // Highlight in sidebar
            $('.conversation-item').removeClass('active');
            $(`.conversation-item[data-thread-id="${threadId}"]`).addClass('active').removeClass('unread');

            // Hide empty state, show main chat area
            $('#chat-empty-state').hide();
            $('#chat-main-area').addClass('active');

            // Reset cache & fetch messages
            lastRenderedMessagesJson = '';
            fetchMessages(threadId, true);
        }

        function fetchMessages(threadId, forceScrollBottom) {
            $.ajax({
                url: 'api/admin_fetch_messages.php',
                type: 'GET',
                data: { session_id: threadId },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        // Compare with cached JSON
                        const jsonStr = JSON.stringify(res.messages);
                        if (jsonStr !== lastRenderedMessagesJson) {
                            lastRenderedMessagesJson = jsonStr;

                            const wasNearBottom = isNearBottom();
                            renderMessagesStream(res.messages);
                            updateChatExpirationUI(res.is_expired);

                            if (forceScrollBottom || wasNearBottom) {
                                scrollChatToBottom(forceScrollBottom);
                            }
                        }
                    }
                }
            });
        }

        function renderMessagesStream(messages) {
            const stream = $('#chat-messages');
            stream.empty();

            if (!messages || messages.length === 0) {
                stream.html('<div style="text-align:center; padding:40px; color:#94a3b8; font-style:italic;">No messages found in this thread.</div>');
                return;
            }

            // Extract earliest resident data for the header
            const firstMsg = messages[0];
            const residentName = firstMsg.sender_name || 'Resident Inquirer';
            const residentEmail = firstMsg.email || '';
            let serviceTag = 'General Inquiry';

            if (firstMsg.message && firstMsg.message.match(/\[Service:\s*([^\]]+)\]/i)) {
                serviceTag = firstMsg.message.match(/\[Service:\s*([^\]]+)\]/i)[1].trim();
            }

            // Update Header Information
            $('#chat-header-name').text(residentName);
            $('#chat-header-avatar').text(getInitials(residentName)).css('background-color', getAvatarColor(residentName));
            $('#chat-header-service').text(serviceTag);

            if (residentEmail) {
                $('#chat-header-email-text').html(`${escapeHtml(residentEmail)} <span style="color:#137547; font-weight:700;"><i class="bi bi-shield-check"></i> Verified</span>`);
                $('#chat-header-email').attr('href', `mailto:${residentEmail}`).show();

                // Setup "Send Document via Gmail" action button (direct Gmail web composer)
                const emailSubject = encodeURIComponent(`[Barangay Tabon] Official Response to your request: ${serviceTag}`);
                const emailBody = encodeURIComponent(`Dear ${residentName},\n\nGood day!\n\nThis is an official response from Barangay Tabon regarding your request for "${serviceTag}".\n\nAttached is your requested document/form. Please review the details and let us know if you need any further assistance.\n\nWarm regards,\nBarangay Secretary\nBarangay Tabon Information System`);
                
                const gmailComposerUrl = `https://mail.google.com/mail/?view=cm&fs=1&to=${encodeURIComponent(residentEmail)}&su=${emailSubject}&body=${emailBody}`;
                $('#btn-send-doc-email').attr('href', gmailComposerUrl).show();
                $('#btn-footer-send-email').attr('href', gmailComposerUrl);
                $('#chat-email-reply-bar').show();
            } else {
                $('#chat-header-email').hide();
                $('#btn-send-doc-email').hide();
                $('#chat-email-reply-bar').hide();
            }

            // Render Messages
            messages.forEach(function(msg) {
                const isAdmin = (msg.sender_type === 'admin');
                const avatarColor = isAdmin ? '#137547' : getAvatarColor(residentName);
                const avatarInit = isAdmin ? 'A' : getInitials(residentName);

                // Parse [Service: ...] chip
                let content = msg.message;
                let serviceChipHtml = '';
                if (content.match(/\[Service:\s*([^\]]+)\]/i)) {
                    const tag = content.match(/\[Service:\s*([^\]]+)\]/i)[1].trim();
                    serviceChipHtml = `<div class="service-header-chip"><i class="bi bi-tag-fill"></i> Service: ${escapeHtml(tag)}</div>`;
                    content = content.replace(/\[Service:\s*[^\]]+\]\s*/i, '');
                }

                // Parse Attachment card if present
                let attachmentHtml = '';
                if (msg.file_attachment) {
                    const fileName = msg.file_name || 'Attached Document';
                    const fileExt = fileName.split('.').pop().toLowerCase();
                    let iconClass = 'bi-file-earmark-arrow-down-fill';
                    if (['jpg','jpeg','png'].includes(fileExt)) iconClass = 'bi-file-earmark-image-fill';
                    if (['pdf'].includes(fileExt)) iconClass = 'bi-file-earmark-pdf-fill';
                    if (['doc','docx'].includes(fileExt)) iconClass = 'bi-file-earmark-word-fill';
                    if (['zip'].includes(fileExt)) iconClass = 'bi-file-earmark-zip-fill';
                    if (['xls','xlsx'].includes(fileExt)) iconClass = 'bi-file-earmark-excel-fill';

                    attachmentHtml = `
                        <a href="${msg.file_attachment}" target="_blank" download class="msg-attachment-card">
                            <i class="bi ${iconClass}"></i>
                            <div>
                                <span>${escapeHtml(fileName)}</span>
                                <small style="display:block; font-size:0.7rem; color:#64748b; font-weight:normal;">Click to download</small>
                            </div>
                        </a>
                    `;
                }

                const senderLabel = !isAdmin ? `<span class="msg-sender-label">${escapeHtml(msg.sender_name)}</span>` : '';
                const checkTick = isAdmin ? '<i class="bi bi-check2-all" style="color:#ffffff; margin-left:3px;"></i>' : '';

                const rowHtml = `
                    <div class="msg-row ${isAdmin ? 'admin' : 'user'}">
                        <div class="msg-avatar" style="background-color: ${avatarColor};">
                            ${avatarInit}
                        </div>
                        <div class="msg-body-wrapper">
                            ${senderLabel}
                            <div class="msg-bubble">
                                ${serviceChipHtml}
                                ${content ? escapeHtml(content).replace(/\n/g, '<br>') : ''}
                                ${attachmentHtml}
                            </div>
                            <div class="msg-time-status">
                                <span>${msg.time_formatted}</span>
                                ${checkTick}
                            </div>
                        </div>
                    </div>
                `;
                stream.append(rowHtml);
            });
        }

        // 6. Dynamic Expiration Lock Handler
        function updateChatExpirationUI(isExpired) {
            if (isExpired) {
                $('#chat-header-status-badge')
                    .removeClass('active')
                    .addClass('expired');
                $('#chat-header-status-text').text('Session Archived');
            } else {
                $('#chat-header-status-badge')
                    .removeClass('expired')
                    .addClass('active');
                $('#chat-header-status-text').text('Citizen Inquirer');
            }
        }

        // 7. Send Message & Handle Attachments
        function sendAdminReply() {
            if (!currentThreadId) return;

            const messageText = $('#admin-reply-input').val().trim();
            if (!messageText && !selectedFile) {
                $('#admin-reply-input').focus();
                return;
            }

            const formData = new FormData();
            formData.append('session_id', currentThreadId);
            formData.append('message', messageText);
            if (selectedFile) {
                formData.append('attachment', selectedFile);
            }

            const sendBtn = $('#btn-send-reply');
            sendBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Sending...');

            $.ajax({
                url: 'api/admin_send_message.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(res) {
                    sendBtn.prop('disabled', false).html('<i class="bi bi-send-fill"></i> <span>Send Reply</span>');
                    if (res.status === 'success') {
                        // Reset input & preview
                        $('#admin-reply-input').val('');
                        clearFileSelection();

                        // Immediate reload
                        fetchMessages(currentThreadId, true);
                        fetchSessions(false);
                    } else {
                        alert(res.message || 'Error sending reply');
                    }
                },
                error: function() {
                    sendBtn.prop('disabled', false).html('<i class="bi bi-send-fill"></i> <span>Send Reply</span>');
                    alert('Server connection error. Please try again.');
                }
            });
        }

        function clearFileSelection() {
            selectedFile = null;
            $('#admin-file-input').val('');
            $('#admin-file-preview-bar').removeClass('active');
        }

        // 8. Event Handlers
        function bindEventHandlers() {
            // Select thread from sidebar click
            $(document).on('click', '.conversation-item', function() {
                const threadId = $(this).data('thread-id');
                selectSession(threadId);
            });

            // Select thread from notification click
            $(document).on('click', '.notif-item', function() {
                const threadId = $(this).data('thread');
                $('#notif-dropdown-menu').removeClass('active');
                selectSession(threadId);
            });

            // Toggle Notification Dropdown
            $('#btn-notif-bell').on('click', function(e) {
                e.stopPropagation();
                $('#notif-dropdown-menu').toggleClass('active');
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('.notif-bell-wrapper').length) {
                    $('#notif-dropdown-menu').removeClass('active');
                }
            });

            // Mark All as Read button
            $('#btn-mark-all-read').on('click', function() {
                $.post('api/admin_mark_all_read.php', function(res) {
                    fetchSessions(false);
                    $('#notif-dropdown-menu').removeClass('active');
                }, 'json');
            });

            // Manual Refresh Chat Button
            $('#btn-refresh-chat').on('click', function() {
                if (currentThreadId) {
                    const icon = $(this).find('i');
                    icon.addClass('fa-spin');
                    fetchMessages(currentThreadId, true);
                    setTimeout(() => icon.removeClass('fa-spin'), 600);
                }
            });

            // Quick Reply Chips Click
            $('.quick-reply-chip').on('click', function() {
                const text = $(this).data('reply');
                $('#admin-reply-input').val(text).focus();
            });

            // Enter key to send reply
            $('#admin-reply-input').on('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendAdminReply();
                }
            });

            // Send Reply button click
            $('#btn-send-reply').on('click', function() {
                sendAdminReply();
            });

            // Attach File Button click
            $('#btn-attach-file').on('click', function() {
                $('#admin-file-input').click();
            });

            // File Selected
            $('#admin-file-input').on('change', function() {
                if (this.files && this.files[0]) {
                    selectedFile = this.files[0];
                    $('#preview-file-name').text(selectedFile.name);
                    $('#admin-file-preview-bar').addClass('active');
                }
            });

            // Remove File Preview
            $('#btn-remove-file').on('click', function() {
                clearFileSelection();
            });

            // Live Search Filter
            $('#chat-search-input').on('input keyup', function() {
                const val = $(this).val();
                if (val.length > 0) {
                    $('#btn-clear-search').addClass('visible');
                } else {
                    $('#btn-clear-search').removeClass('visible');
                }
                applySearchFilter();
            });

            $('#btn-clear-search').on('click', function() {
                $('#chat-search-input').val('').trigger('input').focus();
            });

            // Filter Tabs (All vs Unread)
            $('.filter-tab').on('click', function() {
                $('.filter-tab').removeClass('active');
                $(this).addClass('active');
                currentFilter = $(this).data('filter');
                applySearchFilter();
            });
        }

        // 9. Search and Tab Filter Helper
        function applySearchFilter() {
            const query = $('#chat-search-input').val().toLowerCase().trim();
            $('.conversation-item').each(function() {
                const item = $(this);
                const name = item.data('name') || '';
                const email = item.data('email') || '';
                const service = item.data('service') || '';
                const msg = item.data('msg') || '';
                const unread = parseInt(item.data('unread') || 0);

                let textMatch = (name.includes(query) || email.includes(query) || service.includes(query) || msg.includes(query));
                let tabMatch = true;

                if (currentFilter === 'unread') {
                    tabMatch = (unread > 0);
                }

                if (textMatch && tabMatch) {
                    item.show();
                } else {
                    item.hide();
                }
            });
        }

        // 10. Scroll Helpers
        function scrollChatToBottom(forceInstant) {
            const el = document.getElementById('chat-messages');
            if (!el) return;
            if (forceInstant) {
                el.scrollTop = el.scrollHeight;
            } else {
                el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' });
            }
        }

        function isNearBottom() {
            const el = document.getElementById('chat-messages');
            if (!el) return true;
            return (el.scrollHeight - el.scrollTop - el.clientHeight < 120);
        }

        // 11. Helper formatting functions
        function getInitials(name) {
            if (!name) return 'U';
            const parts = name.trim().split(' ');
            let inits = '';
            for (let p of parts) {
                if (p) inits += p[0].toUpperCase();
                if (inits.length >= 2) break;
            }
            return inits || 'U';
        }

        function getAvatarColor(name) {
            const colors = ['#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#06b6d4', '#6366f1', '#14b8a6'];
            let hash = 0;
            for (let i = 0; i < (name || '').length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
            return colors[Math.abs(hash) % colors.length];
        }

        function escapeHtml(text) {
            if (!text) return '';
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

    })(jQuery);
    </script>
</body>
</html>
