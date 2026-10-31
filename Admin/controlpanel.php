<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../db_connect.php';

// -------------------------------------------------------------
// 1. DYNAMIC DEMOGRAPHIC & HOUSEHOLD CALCULATIONS
// -------------------------------------------------------------
$purok_stats = [
    'purok1' => [
        'name' => 'Purok Mesaya (Purok 1)',
        'short' => 'Mesaya',
        'table' => 'purok1_locations',
        'spotmap_page' => 'Purok 1.php',
        'resident_page' => 'Resident 1.php',
        'class' => 'purok-1',
        'households' => 0,
        'population' => 0
    ],
    'purok2' => [
        'name' => 'Purok Metamed (Purok 2)',
        'short' => 'Metamed',
        'table' => 'purok2_locations',
        'spotmap_page' => 'Purok 2.php',
        'resident_page' => 'Resident 2.php',
        'class' => 'purok-2',
        'households' => 0,
        'population' => 0
    ],
    'purok3' => [
        'name' => 'Purok Melitebangay (Purok 3)',
        'short' => 'Melitebangay',
        'table' => 'purok3_locations',
        'spotmap_page' => 'Purok 3.php',
        'resident_page' => 'Resident 3.php',
        'class' => 'purok-3',
        'households' => 0,
        'population' => 0
    ]
];

$total_households = 0;
$total_population = 0;

foreach ($purok_stats as $key => &$pData) {
    $tbl = $pData['table'];
    $chk = $conn->query("SHOW TABLES LIKE '$tbl'");
    if ($chk && $chk->num_rows > 0) {
        $res = $conn->query("SELECT husband_name, spouse_name, children FROM `$tbl`");
        if ($res) {
            $pData['households'] = $res->num_rows;
            $total_households += $res->num_rows;
            $pop = 0;
            while ($row = $res->fetch_assoc()) {
                if (!empty(trim($row['husband_name'] ?? ''))) $pop++;
                if (!empty(trim($row['spouse_name'] ?? ''))) $pop++;
                if (!empty($row['children'])) {
                    $decoded = json_decode($row['children'], true);
                    if (is_array($decoded)) {
                        $pop += count(array_filter(array_map('trim', $decoded)));
                    }
                }
            }
            $pData['population'] = $pop;
            $total_population += $pop;
        }
    }
}
unset($pData);

// -------------------------------------------------------------
// 2. GOVERNANCE & OFFICIALS COUNT
// -------------------------------------------------------------
$total_officials = 0;
$res_off = $conn->query("SELECT COUNT(*) FROM `officials` WHERE `status` = 'active'");
if ($res_off) $total_officials += (int)$res_off->fetch_row()[0];
$res_sk = $conn->query("SELECT COUNT(*) FROM `sk_officials` WHERE `status` = 'active'");
if ($res_sk) $total_officials += (int)$res_sk->fetch_row()[0];

// Fallback if officials table empty
if ($total_officials === 0) {
    $res_all_off = $conn->query("SELECT (SELECT COUNT(*) FROM `officials`) + (SELECT COUNT(*) FROM `sk_officials`) AS total");
    if ($res_all_off) $total_officials = (int)$res_all_off->fetch_row()[0];
}

// -------------------------------------------------------------
// 3. RESIDENT FEEDBACK & UNREAD INQUIRIES
// -------------------------------------------------------------
$unread_feedback_count = 0;
$total_feedback_count = 0;
$res_fb_unread = $conn->query("SELECT COUNT(*) FROM `feedback_chats` WHERE `sender_type` = 'user' AND `is_read` = 0");
if ($res_fb_unread) $unread_feedback_count = (int)$res_fb_unread->fetch_row()[0];

$res_fb_total = $conn->query("SELECT COUNT(DISTINCT `thread_id`) FROM `feedback_chats`");
if ($res_fb_total) $total_feedback_count = (int)$res_fb_total->fetch_row()[0];

// Fetch 5 most recent resident inquiries
$recent_inquiries = [];
$res_inq = $conn->query("
    SELECT id, thread_id, sender_name, email, message, is_read, created_at 
    FROM `feedback_chats` 
    WHERE `sender_type` = 'user' 
    ORDER BY `id` DESC 
    LIMIT 5
");
if ($res_inq) {
    while ($row = $res_inq->fetch_assoc()) {
        $recent_inquiries[] = $row;
    }
}

// -------------------------------------------------------------
// 4. ANNOUNCEMENTS COUNT
// -------------------------------------------------------------
$total_announcements = 0;
$res_ann = $conn->query("SELECT COUNT(*) FROM `announcements` WHERE `status` = 'published'");
if ($res_ann) $total_announcements = (int)$res_ann->fetch_row()[0];

$recent_announcements = [];
$res_recent_ann = $conn->query("SELECT id, title, when_date, status, created_at FROM `announcements` ORDER BY `id` DESC LIMIT 4");
if ($res_recent_ann) {
    while ($row = $res_recent_ann->fetch_assoc()) {
        $recent_announcements[] = $row;
    }
}

// -------------------------------------------------------------
// 5. ACTIVITY LOGS & AUDIT TRAIL
// -------------------------------------------------------------
$recent_activities = [];
$res_act = $conn->query("
    SELECT a.id, a.action, a.details, a.ip_address, a.created_at, u.name as user_name 
    FROM `activity_logs` a 
    LEFT JOIN `users` u ON a.user_id = u.id 
    ORDER BY a.id DESC 
    LIMIT 5
");
if ($res_act && $res_act->num_rows > 0) {
    while ($row = $res_act->fetch_assoc()) {
        $recent_activities[] = $row;
    }
}

// -------------------------------------------------------------
// 6. ADMINISTRATOR ACCOUNTS
// -------------------------------------------------------------
$admin_users = [];
$res_usr = $conn->query("SELECT id, name, email, role, created_at FROM `users` ORDER BY `id` ASC LIMIT 5");
if ($res_usr) {
    while ($row = $res_usr->fetch_assoc()) {
        $admin_users[] = $row;
    }
}

// -------------------------------------------------------------
// 7. BARANGAY SERVICES OVERVIEW
// -------------------------------------------------------------
$services_list = [];
$total_services = 0;
$active_services_count = 0;

$services_table_check = $conn->query("SHOW TABLES LIKE 'services'");
if ($services_table_check && $services_table_check->num_rows > 0) {
    $res_srv = $conn->query("SELECT id, name, icon, status, fee, processing_time, description FROM `services` ORDER BY display_order ASC, id ASC LIMIT 5");
    if ($res_srv) {
        while ($row = $res_srv->fetch_assoc()) {
            $services_list[] = $row;
            if (($row['status'] ?? '') === 'active') {
                $active_services_count++;
            }
        }
    }
    $cnt_all = $conn->query("SELECT COUNT(*) FROM `services`");
    if ($cnt_all) {
        $total_services = (int)$cnt_all->fetch_row()[0];
    }
}

// -------------------------------------------------------------
// 8. MEDIA GALLERY & PHOTO ARCHIVES
// -------------------------------------------------------------
$gallery_items = [];
$total_albums = 0;
$total_photos = 0;

$chk_gallery_m = $conn->query("SHOW TABLES LIKE 'gallery_media'");
if ($chk_gallery_m && $chk_gallery_m->num_rows > 0) {
    $res_gal = $conn->query("
        SELECT m.id, m.folder_id, m.file_path, m.file_name, m.caption, m.created_at, f.name AS folder_name 
        FROM `gallery_media` m
        LEFT JOIN `gallery_folders` f ON m.folder_id = f.id
        ORDER BY m.id DESC 
        LIMIT 6
    ");
    if ($res_gal) {
        while ($row = $res_gal->fetch_assoc()) {
            $gallery_items[] = $row;
        }
    }

    $c_photos = $conn->query("SELECT COUNT(*) FROM `gallery_media`");
    if ($c_photos) $total_photos = (int)$c_photos->fetch_row()[0];

    $c_albums = $conn->query("SELECT COUNT(*) FROM `gallery_folders`");
    if ($c_albums) $total_albums = (int)$c_albums->fetch_row()[0];
}

// Helper: Human readable time ago
if (!function_exists('format_time_ago')) {
    function format_time_ago($timestamp) {
        if (!$timestamp) return 'Just now';
        $time = strtotime($timestamp);
        if (!$time) return 'Recently';
        $diff = time() - $time;
        if ($diff < 60) return 'Just now';
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        if ($diff < 172800) return 'Yesterday';
        return date('M d, Y', $time);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Panel - Brgy Tabon Information & SpotMap System</title>
    
    <!-- Google Fonts, FontAwesome & Bootstrap Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Admin Stylesheets -->
    <link rel="stylesheet" href="Admin CSS/admin_common.css?v=<?= time() ?>">
    <link rel="stylesheet" href="Admin CSS/controlpanel.css?v=<?= time() ?>">
    <style>
/* Control Panel Dashboard Styles */
.dashboard-grid {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

/* Page Header */
.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 25px;
}

.page-title {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 4px;
}

.page-subtitle {
    font-size: 13px;
    color: #64748b;
    margin: 0;
}

.top-bar-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.badge-date {
    background-color: #ffffff;
    border: 1px solid #cbd5e1;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

.btn-portal-link {
    background-color: #223c3b;
    color: #ffffff !important;
    text-decoration: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(34, 60, 59, 0.2);
}

.btn-portal-link:hover {
    background-color: #2d504f;
    transform: translateY(-1px);
}

/* Quick Action Launchpad */
.quick-launchpad {
    background: #ffffff;
    border-radius: 12px;
    padding: 18px 22px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.launchpad-title {
    font-size: 14px;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.launchpad-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 12px;
}

.launch-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 14px 10px;
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    text-decoration: none;
    color: #334155;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.2s ease;
    position: relative;
}

.launch-btn i {
    font-size: 20px;
    margin-bottom: 8px;
    color: #223c3b;
    transition: transform 0.2s ease;
}

.launch-btn:hover {
    background-color: #edf5f4;
    border-color: #223c3b;
    color: #223c3b;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(34, 60, 59, 0.08);
}

.launch-btn:hover i {
    transform: scale(1.15);
}

.launch-badge {
    position: absolute;
    top: 6px;
    right: 8px;
    background-color: #f59e0b;
    color: #ffffff;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 10px;
    box-shadow: 0 1px 3px rgba(245, 158, 11, 0.4);
}

/* KPI Cards */
.kpi-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 18px;
}

.kpi-card {
    background-color: #ffffff;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 18px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border: 1px solid #e2e8f0;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.07);
}

.kpi-icon {
    width: 54px;
    height: 54px;
    border-radius: 12px;
    background-color: #e6f4f2;
    color: #223c3b;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 22px;
    flex-shrink: 0;
}

.kpi-icon.kpi-orange {
    background-color: #fef3c7;
    color: #d97706;
}

.kpi-icon.kpi-blue {
    background-color: #e0f2fe;
    color: #0284c7;
}

.kpi-icon.kpi-green {
    background-color: #dcfce7;
    color: #16a34a;
}

.kpi-icon.kpi-purple {
    background-color: #f3e8ff;
    color: #9333ea;
}

.kpi-icon.kpi-red, .kpi-icon.kpi-amber {
    background-color: #fef3c7;
    color: #b45309;
}

.kpi-info h3 {
    font-size: 13px;
    color: #64748b;
    margin-bottom: 4px;
    font-weight: 600;
}

.kpi-info p {
    font-size: 24px;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
    line-height: 1.2;
}

.kpi-subtext {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 3px;
    display: block;
}

/* Purok Demographic Breakdown */
.purok-breakdown-section {
    background-color: #ffffff;
    border-radius: 12px;
    padding: 22px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.section-header-flex {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 10px;
}

.section-header-flex h2 {
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
}

.purok-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px;
}

.purok-summary-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: all 0.2s ease;
    border-top: 4px solid #223c3b;
}

.purok-summary-card:hover {
    background: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.06);
}

.purok-summary-card.purok-1 { border-top-color: #10b981; }
.purok-summary-card.purok-2 { border-top-color: #3b82f6; }
.purok-summary-card.purok-3 { border-top-color: #f59e0b; }

.purok-card-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}

.purok-card-top h4 {
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 4px 0;
}

.purok-badge {
    font-size: 11px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 12px;
    background: #e2e8f0;
    color: #475569;
}

.purok-stats-row {
    display: flex;
    gap: 15px;
    margin-bottom: 14px;
}

.purok-stat-item {
    flex: 1;
    background: #ffffff;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.purok-stat-item span {
    font-size: 11px;
    color: #64748b;
    display: block;
    font-weight: 500;
}

.purok-stat-item strong {
    font-size: 18px;
    color: #1e293b;
    font-weight: 800;
}

.purok-card-actions {
    display: flex;
    gap: 8px;
}

.purok-action-btn {
    flex: 1;
    text-align: center;
    padding: 7px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

.purok-action-btn.btn-map {
    background-color: #223c3b;
    color: #ffffff;
}

.purok-action-btn.btn-map:hover {
    background-color: #2e514f;
}

.purok-action-btn.btn-residents {
    background-color: #e2e8f0;
    color: #334155;
}

.purok-action-btn.btn-residents:hover {
    background-color: #cbd5e1;
    color: #0f172a;
}

/* Dashboard Widgets */
.dashboard-widgets {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 20px;
}

.widget-panel {
    background-color: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border: 1px solid #e2e8f0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.widget-header {
    background-color: #f8fafc;
    padding: 14px 20px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.widget-header h2 {
    font-size: 15px;
    color: #1e293b;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.widget-header-link {
    font-size: 12px;
    color: #2563eb;
    text-decoration: none;
    font-weight: 600;
}

.widget-header-link:hover {
    text-decoration: underline;
}

.widget-body {
    padding: 18px 20px;
    flex: 1;
}

/* Inquiries Feed */
.inquiry-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.inquiry-item {
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    flex-direction: column;
    gap: 6px;
    transition: background 0.15s ease;
}

.inquiry-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.inquiry-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.inquiry-sender {
    font-weight: 700;
    font-size: 13px;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 6px;
}

.inquiry-time {
    color: #94a3b8;
    font-size: 11px;
    font-weight: 500;
}

.inquiry-snippet {
    color: #475569;
    font-size: 12px;
    line-height: 1.4;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.inquiry-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 2px;
}

.btn-reply-chat {
    font-size: 11px;
    font-weight: 600;
    color: #223c3b;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.btn-reply-chat:hover {
    text-decoration: underline;
}

/* System Status List */
.notification-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.notification-list li {
    padding: 12px 14px;
    margin-bottom: 10px;
    border-radius: 8px;
    font-size: 12px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    line-height: 1.4;
}

.notification-list li:last-child {
    margin-bottom: 0;
}

.notification-list li i {
    font-size: 15px;
    margin-top: 2px;
    flex-shrink: 0;
}

.notification-list li.alert {
    background-color: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
}

.notification-list li.info {
    background-color: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
}

.notification-list li.success {
    background-color: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
}

/* Management Widgets Layout */
.management-widgets {
    grid-template-columns: 3fr 2fr;
}

@media (max-width: 1024px) {
    .management-widgets {
        grid-template-columns: 1fr;
    }
}

/* Data Table */
.table-responsive {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th, .data-table td {
    padding: 12px 14px;
    text-align: left;
    border-bottom: 1px solid #f1f5f9;
    font-size: 13px;
    vertical-align: middle;
}

.data-table th {
    background-color: #f8fafc;
    color: #475569;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.data-table tbody tr:hover {
    background-color: #f8fafc;
}

/* Badges */
.badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
}

.badge.active, .badge.badge-published {
    background-color: #dcfce7;
    color: #166534;
}

.badge.inactive, .badge.badge-draft {
    background-color: #f1f5f9;
    color: #64748b;
}

.badge.badge-role {
    background-color: #e0f2fe;
    color: #0369a1;
}

.badge.badge-unread {
    background-color: #fef3c7;
    color: #b45309;
    font-weight: 700;
}

.badge.badge-read {
    background-color: #f1f5f9;
    color: #64748b;
}

/* Small Buttons */
.btn-edit-sm {
    background-color: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
    padding: 5px 9px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: 0.2s;
}

.btn-edit-sm:hover {
    background-color: #e2e8f0;
    color: #0f172a;
}

/* Pages & Announcements List */
.pages-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.page-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 12px;
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    transition: background-color 0.2s ease;
}

.page-item:hover {
    background-color: #ffffff;
    border-color: #cbd5e1;
}

.page-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.page-icon {
    background-color: #ffffff;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    justify-content: center;
    align-items: center;
    color: #223c3b;
    font-size: 15px;
    border: 1px solid #e2e8f0;
    flex-shrink: 0;
}

.page-info h4 {
    margin: 0 0 2px 0;
    font-size: 13px;
    color: #1e293b;
    font-weight: 600;
}

.page-info p {
    margin: 0;
    font-size: 11px;
    color: #64748b;
}

.btn-edit-page {
    font-size: 12px;
    color: #2563eb;
    text-decoration: none;
    font-weight: 600;
    white-space: nowrap;
    padding: 4px 8px;
}

.btn-edit-page:hover {
    text-decoration: underline;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 24px 10px;
    color: #94a3b8;
    font-size: 13px;
}

.empty-state i {
    font-size: 28px;
    margin-bottom: 8px;
    color: #cbd5e1;
    display: block;
}


/* ==========================================================================
   Barangay Public Services Widget (.services-widget-*)
   ========================================================================== */

.services-widget-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 12px;
    color: #475569;
    margin-bottom: 12px;
}

.btn-add-service-shortcut {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #0284c7;
    color: #ffffff;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 6px;
    text-decoration: none;
    transition: background 0.15s ease;
}

.btn-add-service-shortcut:hover {
    background: #0369a1;
    color: #ffffff;
}

.services-widget-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.service-widget-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 14px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.service-widget-item:hover {
    border-color: #0284c7;
    box-shadow: 0 2px 8px rgba(2, 132, 199, 0.08);
}

.service-widget-icon {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    background: #e0f2fe;
    color: #0284c7;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}

.service-widget-info {
    flex: 1;
    min-width: 0;
}

.service-widget-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 4px;
}

.service-widget-name {
    margin: 0;
    font-size: 13.5px;
    font-weight: 700;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.badge-service-status {
    font-size: 10px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 10px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    flex-shrink: 0;
}

.badge-srv-active {
    background: #dcfce7;
    color: #15803d;
}

.badge-srv-inactive {
    background: #f1f5f9;
    color: #64748b;
}

.service-widget-badges {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
    flex-wrap: wrap;
}

.service-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 6px;
}

.service-tag.tag-fee {
    background: #ecfdf5;
    color: #047857;
}

.service-tag.tag-time {
    background: #f8fafc;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.service-widget-desc {
    margin: 0;
    font-size: 11.5px;
    color: #64748b;
    line-height: 1.4;
}

.service-widget-actions {
    display: flex;
    align-items: center;
    align-self: center;
}

.btn-service-action {
    width: 30px;
    height: 30px;
    border-radius: 6px;
    background: #f1f5f9;
    color: #475569;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-size: 12px;
    transition: background 0.15s ease, color 0.15s ease;
}

.btn-service-action:hover {
    background: #0284c7;
    color: #ffffff;
}

.btn-create-service-sm {
    display: inline-block;
    margin-top: 10px;
    padding: 6px 14px;
    background: #0284c7;
    color: #ffffff;
    font-size: 12px;
    font-weight: 600;
    border-radius: 6px;
    text-decoration: none;
    transition: background 0.15s ease;
}

.btn-create-service-sm:hover {
    background: #0369a1;
    color: #ffffff;
}


/* ==========================================================================
   Community Media Gallery Widget (.gallery-widget-*)
   ========================================================================== */

.gallery-widget-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 12px;
    color: #475569;
    margin-bottom: 12px;
}

.btn-gallery-shortcut {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #0284c7;
    color: #ffffff;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 6px;
    text-decoration: none;
    transition: background 0.15s ease;
}

.btn-gallery-shortcut:hover {
    background: #0369a1;
    color: #ffffff;
}

.gallery-widget-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 10px;
}

.gallery-widget-card {
    display: flex;
    flex-direction: column;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

.gallery-widget-card:hover {
    border-color: #0284c7;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(2, 132, 199, 0.12);
}

.gallery-widget-img-wrapper {
    position: relative;
    width: 100%;
    height: 95px;
    background: #f1f5f9;
    overflow: hidden;
}

.gallery-widget-img-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.3s ease;
}

.gallery-widget-card:hover .gallery-widget-img-wrapper img {
    transform: scale(1.06);
}

.gallery-widget-overlay {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 16px;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.gallery-widget-card:hover .gallery-widget-overlay {
    opacity: 1;
}

.gallery-widget-details {
    padding: 6px 8px 8px 8px;
    background: #ffffff;
}

.gallery-widget-caption {
    margin: 0 0 2px 0;
    font-size: 11px;
    font-weight: 700;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.gallery-widget-album {
    font-size: 9.5px;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
}

    </style>
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../Barangay Logo/Logo.png" alt="Barangay Logo" style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover; display: inline-block;" onerror="this.src='https://via.placeholder.com/70'">
            <h3>Brgy Tabon</h3>
            <p>Info SpotMap</p>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="controlpanel.php" class="active"><i class="fa-solid fa-gauge"></i> Control Panel</a></li>
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
                        <a href="Resident 1.php">Resident 1 (Mesaya)</a>
                        <a href="Resident 2.php">Resident 2 (Metamed)</a>
                        <a href="Resident 3.php">Resident 3 (Melitebangay)</a>
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
                    <a href="feedback.php">
                        <i class="fa-solid fa-comments"></i> Feedback Chat
                        <?php if ($unread_feedback_count > 0): ?>
                            <span style="margin-left: auto; background: #f59e0b; color: #fff; font-size: 10px; padding: 2px 7px; border-radius: 10px; font-weight: 700;">
                                <?= $unread_feedback_count ?>
                            </span>
                        <?php endif; ?>
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
                            dropdownContent.classList.remove("active");
                            dropdownContent.style.display = "none";
                        } else {
                            dropdownContent.classList.add("active");
                            dropdownContent.style.display = "block";
                        }
                    });
                }
            });
        </script>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div>
                <h1 class="page-title">Control Panel Overview</h1>
                <p class="page-subtitle">Barangay Tabon Management & Geographic Information System</p>
            </div>
            <div class="top-bar-actions">
                <span class="badge-date">
                    <i class="fa-regular fa-calendar-days text-primary"></i> <?= date('l, F j, Y') ?>
                </span>
                <a href="../index.php" target="_blank" class="btn-portal-link">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> View Public Website
                </a>
            </div>
        </div>

        <div class="dashboard-grid">
            
            <!-- Dynamic KPIs -->
            <div class="kpi-cards">
                <div class="kpi-card">
                    <div class="kpi-icon kpi-green"><i class="fa-solid fa-users"></i></div>
                    <div class="kpi-info">
                        <h3>Total Population</h3>
                        <p><?= number_format($total_population) ?></p>
                        <span class="kpi-subtext">Across 3 Mapped Puroks</span>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon kpi-blue"><i class="fa-solid fa-house-chimney"></i></div>
                    <div class="kpi-info">
                        <h3>Registered Households</h3>
                        <p><?= number_format($total_households) ?></p>
                        <span class="kpi-subtext">Family Units Pinned</span>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon kpi-purple"><i class="fa-solid fa-user-shield"></i></div>
                    <div class="kpi-info">
                        <h3>Barangay & SK Board</h3>
                        <p><?= number_format($total_officials) ?></p>
                        <span class="kpi-subtext">Active Elected Leaders</span>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon <?= $unread_feedback_count > 0 ? 'kpi-amber' : '' ?>">
                        <i class="fa-solid fa-comments"></i>
                    </div>
                    <div class="kpi-info">
                        <h3>Resident Inquiries</h3>
                        <p><?= number_format($total_feedback_count) ?></p>
                        <span class="kpi-subtext" style="<?= $unread_feedback_count > 0 ? 'color: #b45309; font-weight:700;' : '' ?>">
                            <?= $unread_feedback_count ?> Unread Messages
                        </span>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon"><i class="fa-solid fa-bullhorn"></i></div>
                    <div class="kpi-info">
                        <h3>Bulletins & Services</h3>
                        <p><?= number_format($total_announcements + $total_services) ?></p>
                        <span class="kpi-subtext"><?= $total_announcements ?> News / <?= $total_services ?> Services</span>
                    </div>
                </div>
            </div>

            <!-- SpotMap Purok Demographic Breakdown -->
            <div class="purok-breakdown-section">
                <div class="section-header-flex">
                    <h2><i class="fa-solid fa-map-location-dot" style="color: #223c3b;"></i> SpotMap Purok Demographics & Mapping Status</h2>
                    <a href="Legend.php" class="widget-header-link"><i class="fa-solid fa-layer-group"></i> Manage SpotMap Legends</a>
                </div>
                <div class="purok-cards-grid">
                    <?php foreach ($purok_stats as $pKey => $p): ?>
                        <div class="purok-summary-card <?= $p['class'] ?>">
                            <div class="purok-card-top">
                                <div>
                                    <h4><?= htmlspecialchars($p['name']) ?></h4>
                                    <span class="purok-badge">Zone ID: <?= strtoupper($pKey) ?></span>
                                </div>
                                <div style="font-size: 24px; color: #64748b;">
                                    <i class="fa-solid fa-location-dot"></i>
                                </div>
                            </div>
                            <div class="purok-stats-row">
                                <div class="purok-stat-item">
                                    <span>Households</span>
                                    <strong><?= number_format($p['households']) ?></strong>
                                </div>
                                <div class="purok-stat-item">
                                    <span>Population</span>
                                    <strong><?= number_format($p['population']) ?></strong>
                                </div>
                            </div>
                            <div class="purok-card-actions">
                                <a href="<?= htmlspecialchars($p['spotmap_page']) ?>" class="purok-action-btn btn-map">
                                    <i class="fa-solid fa-crosshairs"></i> SpotMap
                                </a>
                                <a href="<?= htmlspecialchars($p['resident_page']) ?>" class="purok-action-btn btn-residents">
                                    <i class="fa-solid fa-list-check"></i> Residents
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Inquiries & Barangay Public Services -->
            <div class="dashboard-widgets">
                <!-- Live Resident Concerns & Inquiries -->
                <div class="widget-panel">
                    <div class="widget-header">
                        <h2><i class="fa-solid fa-comments"></i> Recent Resident Inquiries</h2>
                        <a href="feedback.php" class="widget-header-link">Open Feedback Messenger &rarr;</a>
                    </div>
                    <div class="widget-body">
                        <?php if (empty($recent_inquiries)): ?>
                            <div class="empty-state">
                                <i class="fa-regular fa-comment-dots"></i>
                                <p>No resident feedback or inquiries yet.</p>
                            </div>
                        <?php else: ?>
                            <ul class="inquiry-list">
                                <?php foreach ($recent_inquiries as $inq): ?>
                                    <li class="inquiry-item">
                                        <div class="inquiry-meta">
                                            <span class="inquiry-sender">
                                                <i class="fa-solid fa-circle-user" style="color: #64748b;"></i>
                                                <?= htmlspecialchars($inq['sender_name']) ?>
                                            </span>
                                            <span class="inquiry-time"><?= format_time_ago($inq['created_at']) ?></span>
                                        </div>
                                        <div class="inquiry-snippet" title="<?= htmlspecialchars($inq['message']) ?>">
                                            <?= htmlspecialchars(mb_strimwidth($inq['message'], 0, 95, '...')) ?>
                                        </div>
                                        <div class="inquiry-footer">
                                            <span class="badge <?= $inq['is_read'] ? 'badge-read' : 'badge-unread' ?>">
                                                <?= $inq['is_read'] ? 'Resolved / Read' : 'New Unread' ?>
                                            </span>
                                            <a href="feedback.php?thread=<?= urlencode($inq['thread_id']) ?>" class="btn-reply-chat">
                                                Reply in Chat <i class="fa-solid fa-chevron-right"></i>
                                            </a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Barangay Public Services & Documents -->
                <div class="widget-panel">
                    <div class="widget-header">
                        <h2><i class="fa-solid fa-bell-concierge"></i> Barangay Public Services</h2>
                        <a href="services.php" class="widget-header-link">Manage Services &rarr;</a>
                    </div>
                    <div class="widget-body">
                        <?php if (empty($services_list)): ?>
                            <div class="empty-state">
                                <i class="fa-solid fa-file-circle-plus"></i>
                                <p>No barangay services created yet.</p>
                                <a href="services.php" class="btn-create-service-sm">+ Create First Service</a>
                            </div>
                        <?php else: ?>
                            <div class="services-widget-summary">
                                <span><i class="fa-solid fa-circle-check" style="color: #10b981;"></i> <strong><?= $active_services_count ?> Active Services</strong> available to citizens</span>
                                <a href="services.php" class="btn-add-service-shortcut"><i class="fa-solid fa-plus"></i> Add Service</a>
                            </div>
                            <ul class="services-widget-list">
                                <?php foreach ($services_list as $srv): 
                                    $is_active = (($srv['status'] ?? 'active') === 'active');
                                    $srv_icon = !empty($srv['icon']) ? htmlspecialchars($srv['icon']) : 'fa-solid fa-file-lines';
                                    if (strpos($srv_icon, 'bi-') === 0) {
                                        $srv_icon = 'bi ' . $srv_icon;
                                    } elseif (strpos($srv_icon, 'fa-') !== false && strpos($srv_icon, 'fa-solid') === false && strpos($srv_icon, 'fa-regular') === false) {
                                        $srv_icon = 'fa-solid ' . $srv_icon;
                                    }
                                ?>
                                    <li class="service-widget-item <?= $is_active ? 'active-service' : 'inactive-service' ?>">
                                        <div class="service-widget-icon">
                                            <i class="<?= $srv_icon ?>"></i>
                                        </div>
                                        <div class="service-widget-info">
                                            <div class="service-widget-title-row">
                                                <h4 class="service-widget-name"><?= htmlspecialchars($srv['name']) ?></h4>
                                                <span class="badge-service-status <?= $is_active ? 'badge-srv-active' : 'badge-srv-inactive' ?>">
                                                    <?= $is_active ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </div>
                                            <div class="service-widget-badges">
                                                <span class="service-tag tag-fee">
                                                    <i class="fa-solid fa-receipt"></i> <?= htmlspecialchars($srv['fee'] ?? 'Free') ?>
                                                </span>
                                                <span class="service-tag tag-time">
                                                    <i class="fa-regular fa-clock"></i> <?= htmlspecialchars($srv['processing_time'] ?? 'Same Day') ?>
                                                </span>
                                            </div>
                                            <?php if (!empty($srv['description'])): ?>
                                                <p class="service-widget-desc">
                                                    <?= htmlspecialchars(mb_strimwidth($srv['description'], 0, 85, '...')) ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="service-widget-actions">
                                            <a href="services.php" class="btn-service-action" title="Manage in Services">
                                                <i class="fa-solid fa-arrow-right"></i>
                                            </a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Management Widgets -->
            <div class="dashboard-widgets management-widgets">
                <!-- Community Media Gallery -->
                <div class="widget-panel">
                    <div class="widget-header">
                        <h2><i class="fa-regular fa-images"></i> Community Media Gallery</h2>
                        <a href="medialibrary.php" class="widget-header-link">Open Media Library &rarr;</a>
                    </div>
                    <div class="widget-body">
                        <?php if (empty($gallery_items)): ?>
                            <div class="empty-state">
                                <i class="fa-regular fa-images"></i>
                                <p>No photos uploaded to the gallery yet.</p>
                                <a href="medialibrary.php" class="btn-create-service-sm"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Photos</a>
                            </div>
                        <?php else: ?>
                            <div class="gallery-widget-summary">
                                <span>
                                    <i class="fa-solid fa-folder-open" style="color: #0284c7;"></i>
                                    <strong><?= $total_albums ?> Album<?= $total_albums !== 1 ? 's' : '' ?></strong> &bull; 
                                    <strong><?= $total_photos ?> Photo<?= $total_photos !== 1 ? 's' : '' ?></strong> in Library
                                </span>
                                <a href="medialibrary.php" class="btn-gallery-shortcut">
                                    <i class="fa-solid fa-plus"></i> Upload Photos
                                </a>
                            </div>

                            <div class="gallery-widget-grid">
                                <?php foreach ($gallery_items as $item): ?>
                                    <a href="medialibrary.php?folder_id=<?= (int)$item['folder_id'] ?>" class="gallery-widget-card" title="<?= htmlspecialchars($item['caption'] ?: $item['file_name']) ?>">
                                        <div class="gallery-widget-img-wrapper">
                                            <img src="<?= htmlspecialchars($item['file_path']) ?>" alt="<?= htmlspecialchars($item['caption'] ?: 'Gallery Photo') ?>" loading="lazy" onerror="this.src='../Barangay Logo/Logo.png'">
                                            <div class="gallery-widget-overlay">
                                                <i class="fa-solid fa-magnifying-glass-plus"></i>
                                            </div>
                                        </div>
                                        <div class="gallery-widget-details">
                                            <h5 class="gallery-widget-caption"><?= htmlspecialchars(mb_strimwidth($item['caption'] ?: $item['file_name'], 0, 22, '...')) ?></h5>
                                            <span class="gallery-widget-album"><?= htmlspecialchars($item['folder_name'] ?? 'General') ?></span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Announcements & Portal Management -->
                <div class="widget-panel">
                    <div class="widget-header">
                        <h2><i class="fa-solid fa-bullhorn"></i> Latest Bulletins & News</h2>
                        <a href="announcements.php" class="widget-header-link">+ Add New &rarr;</a>
                    </div>
                    <div class="widget-body">
                        <?php if (empty($recent_announcements)): ?>
                            <div class="empty-state">
                                <i class="fa-regular fa-newspaper"></i>
                                <p>No announcements published yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="pages-list">
                                <?php foreach ($recent_announcements as $ann): ?>
                                    <div class="page-item">
                                        <div class="page-info">
                                            <div class="page-icon"><i class="fa-solid fa-newspaper"></i></div>
                                            <div>
                                                <h4><?= htmlspecialchars(mb_strimwidth($ann['title'], 0, 32, '...')) ?></h4>
                                                <p>
                                                    <i class="fa-regular fa-calendar"></i> 
                                                    <?= htmlspecialchars($ann['when_date'] ?: date('M d, Y', strtotime($ann['created_at']))) ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span class="badge badge-<?= $ann['status'] === 'published' ? 'published' : 'draft' ?>">
                                                <?= ucfirst($ann['status']) ?>
                                            </span>
                                            <a href="announcements.php" class="btn-edit-page">Manage</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </main>

</body>
</html>
