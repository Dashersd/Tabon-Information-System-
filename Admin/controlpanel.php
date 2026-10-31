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
                    <a href="feedbackchat.php">
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
                        <a href="feedbackchat.php" class="widget-header-link">Open Feedback Messenger &rarr;</a>
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
                                            <a href="feedbackchat.php?thread=<?= urlencode($inq['thread_id']) ?>" class="btn-reply-chat">
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
