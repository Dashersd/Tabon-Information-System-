<?php
// Admin/feedback_chat.php - Barangay Tabon Admin Feedback Chat & Resident Inquiry Management
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
    <link rel="stylesheet" href="css/admin_common.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Admin CSS/feedbackchat.css?v=<?php echo time(); ?>">
</head>
<body class="feedback-chat-page">

    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../Barangay Logo/Logo.png" alt="Barangay Logo" onerror="this.src='https://via.placeholder.com/70'">
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
                        <a href="Purok 1.php">Purok 1</a>
                        <a href="Purok 2.php">Purok 2</a>
                        <a href="Purok 3.php">Purok 3</a>
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
                    <a href="feedback_chat.php" class="active">
                        <i class="fa-solid fa-comments"></i> Feedback Chat
                    </a>
                </li>
                <li><a href="adminsettings.php"><i class="fa-solid fa-gear"></i> Admin Settings</a></li>
                
                <br>
                <li><a href="../login.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
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
