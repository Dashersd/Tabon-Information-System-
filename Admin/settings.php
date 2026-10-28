<?php
// Admin/settings.php - Admin Settings & System Configuration Center
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../db_connect.php';

// --- 1. Database Schema & Auto-Migration Routines ---

// Helper: Ensure column exists in table
function ensureColumnExists($conn, $table, $column, $definition) {
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($res && $res->num_rows === 0) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

// 1.1 activity_logs Table
$conn->query("CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `action` VARCHAR(255) NOT NULL,
    `details` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

// 1.2 barangay_information Table
$conn->query("CREATE TABLE IF NOT EXISTS `barangay_information` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `barangay_name` VARCHAR(150) NOT NULL DEFAULT 'Barangay Tabon',
    `municipality` VARCHAR(150) NOT NULL DEFAULT 'Lapuyan',
    `province` VARCHAR(150) NOT NULL DEFAULT 'Zamboanga del Sur',
    `slogan` VARCHAR(255) DEFAULT 'Serbisyong Tapat, Para sa Lahat',
    `office_hours` VARCHAR(150) DEFAULT 'Monday - Friday, 8:00 AM - 5:00 PM',
    `hall_address` VARCHAR(255) DEFAULT 'Barangay Hall, Tabon, Lapuyan, Zamboanga del Sur',
    `mission_vision` TEXT NULL,
    `email` VARCHAR(150) DEFAULT 'barangaytabon@gmail.com',
    `contact_number` VARCHAR(50) DEFAULT '(+63) 912 345 6789',
    `facebook_url` VARCHAR(255) DEFAULT 'https://www.facebook.com/',
    `police_hotline` VARCHAR(50) DEFAULT '0998-598-7241',
    `fire_hotline` VARCHAR(50) DEFAULT '0917-123-4567',
    `health_hotline` VARCHAR(50) DEFAULT '0920-987-6543',
    `tanod_hotline` VARCHAR(50) DEFAULT '0931-234-5678',
    `logo_path` VARCHAR(255) DEFAULT '../public/image/Maruing Logo 2.png',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

// Run schema expansions on barangay_information
ensureColumnExists($conn, 'barangay_information', 'slogan', "VARCHAR(255) DEFAULT 'Serbisyong Tapat, Para sa Lahat'");
ensureColumnExists($conn, 'barangay_information', 'office_hours', "VARCHAR(150) DEFAULT 'Monday - Friday, 8:00 AM - 5:00 PM'");
ensureColumnExists($conn, 'barangay_information', 'facebook_url', "VARCHAR(255) DEFAULT 'https://www.facebook.com/'");
ensureColumnExists($conn, 'barangay_information', 'police_hotline', "VARCHAR(50) DEFAULT '0998-598-7241'");
ensureColumnExists($conn, 'barangay_information', 'fire_hotline', "VARCHAR(50) DEFAULT '0917-123-4567'");
ensureColumnExists($conn, 'barangay_information', 'health_hotline', "VARCHAR(50) DEFAULT '0920-987-6543'");
ensureColumnExists($conn, 'barangay_information', 'tanod_hotline', "VARCHAR(50) DEFAULT '0931-234-5678'");
ensureColumnExists($conn, 'barangay_information', 'logo_path', "VARCHAR(255) DEFAULT '../public/image/Maruing Logo 2.png'");

// Ensure record id = 1 exists in barangay_information
$check_info = $conn->query("SELECT * FROM `barangay_information` WHERE `id` = 1");
if ($check_info && $check_info->num_rows === 0) {
    $conn->query("INSERT INTO `barangay_information` (
        `id`, `barangay_name`, `municipality`, `province`, `slogan`, `office_hours`, 
        `hall_address`, `email`, `contact_number`, `facebook_url`, 
        `police_hotline`, `fire_hotline`, `health_hotline`, `tanod_hotline`, `logo_path`
    ) VALUES (
        1, 'Barangay Tabon', 'Lapuyan', 'Zamboanga del Sur', 'Serbisyong Tapat, Para sa Lahat',
        'Monday - Friday, 8:00 AM - 5:00 PM', 'Barangay Hall, Tabon, Lapuyan, Zamboanga del Sur',
        'barangaytabon@gmail.com', '(+63) 912 345 6789', 'https://www.facebook.com/',
        '0998-598-7241', '0917-123-4567', '0920-987-6543', '0931-234-5678', '../public/image/Maruing Logo 2.png'
    )");
}

// 1.3 users Table for administrator security
$conn->query("CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL DEFAULT 'System Administrator',
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(50) DEFAULT 'admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

// Seed default admin in users table if empty
$check_user = $conn->query("SELECT * FROM `users` WHERE `role` = 'admin' LIMIT 1");
if ($check_user && $check_user->num_rows === 0) {
    $default_pw_hash = password_hash('123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES ('System Administrator', 'admin@example.com', '$default_pw_hash', 'admin')");
}

// 1.4 Activity Logging Helper
function logActivity($conn, $action, $details = null) {
    try {
        $user_id = $_SESSION['admin_id'] ?? 1;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $conn->prepare("INSERT INTO `activity_logs` (`user_id`, `action`, `details`, `ip_address`) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isss", $user_id, $action, $details, $ip);
            $stmt->execute();
        }
    } catch (Exception $e) {}
}

// Branding logo directory
$branding_dir = __DIR__ . '/uploads/branding/';
if (!is_dir($branding_dir)) {
    mkdir($branding_dir, 0777, true);
}

// --- 2. Backend POST Request Handlers ---
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    // Action 1: Save Barangay Profile (General)
    if ($action === 'save_general') {
        $barangay_name  = trim($_POST['barangay_name'] ?? 'Barangay Tabon');
        $municipality   = trim($_POST['municipality'] ?? 'Lapuyan');
        $province       = trim($_POST['province'] ?? 'Zamboanga del Sur');
        $slogan         = trim($_POST['slogan'] ?? '');
        $office_hours   = trim($_POST['office_hours'] ?? '');
        $hall_address   = trim($_POST['hall_address'] ?? '');
        $mission_vision = trim($_POST['mission_vision'] ?? '');

        // Fetch current logo
        $curr_logo = '../public/image/Maruing Logo 2.png';
        $l_res = $conn->query("SELECT `logo_path` FROM `barangay_information` WHERE `id` = 1");
        if ($l_res && $row = $l_res->fetch_assoc()) {
            if (!empty($row['logo_path'])) $curr_logo = $row['logo_path'];
        }

        // Process uploaded logo if present
        if (isset($_FILES['brgy_logo']) && $_FILES['brgy_logo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['brgy_logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg'])) {
                $new_logo_name = 'logo_' . time() . '.' . $ext;
                $target_file = $branding_dir . $new_logo_name;
                if (move_uploaded_file($_FILES['brgy_logo']['tmp_name'], $target_file)) {
                    $curr_logo = 'uploads/branding/' . $new_logo_name;
                    // Also update legacy Barangay Logo/Logo.png for site-wide compatibility
                    @copy($target_file, __DIR__ . '/../Barangay Logo/Logo.png');
                }
            }
        }

        $stmt = $conn->prepare("UPDATE `barangay_information` SET 
            `barangay_name` = ?, `municipality` = ?, `province` = ?, `slogan` = ?, 
            `office_hours` = ?, `hall_address` = ?, `mission_vision` = ?, `logo_path` = ? 
            WHERE `id` = 1");
        $stmt->bind_param("ssssssss", $barangay_name, $municipality, $province, $slogan, $office_hours, $hall_address, $mission_vision, $curr_logo);
        $stmt->execute();

        logActivity($conn, 'Profile Updated', 'Barangay profile details and slogan updated');
        header("Location: settings.php?msg=general_saved&tab=general");
        exit();
    }

    // Action 2: Save Contacts & Emergency Hotlines
    if ($action === 'save_contacts') {
        $email          = trim($_POST['email'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        $police_hotline = trim($_POST['police_hotline'] ?? '');
        $fire_hotline   = trim($_POST['fire_hotline'] ?? '');
        $health_hotline = trim($_POST['health_hotline'] ?? '');
        $tanod_hotline  = trim($_POST['tanod_hotline'] ?? '');
        $facebook_url   = trim($_POST['facebook_url'] ?? '');

        $stmt = $conn->prepare("UPDATE `barangay_information` SET 
            `email` = ?, `contact_number` = ?, `police_hotline` = ?, `fire_hotline` = ?, 
            `health_hotline` = ?, `tanod_hotline` = ?, `facebook_url` = ? 
            WHERE `id` = 1");
        $stmt->bind_param("sssssss", $email, $contact_number, $police_hotline, $fire_hotline, $health_hotline, $tanod_hotline, $facebook_url);
        $stmt->execute();

        logActivity($conn, 'Contacts Updated', 'Emergency desk hotlines and public contact numbers updated');
        header("Location: settings.php?msg=contacts_saved&tab=contacts");
        exit();
    }

    // Action 3: Update Admin Security & Account
    if ($action === 'update_admin_security') {
        $admin_name       = trim($_POST['admin_name'] ?? '');
        $admin_email      = trim($_POST['admin_email'] ?? '');
        $new_password     = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($admin_name) || empty($admin_email)) {
            header("Location: settings.php?error=empty_fields&tab=security");
            exit();
        }

        // Check duplicate email (excluding current admin ID)
        $admin_id = $_SESSION['admin_id'] ?? 1;
        $dup_stmt = $conn->prepare("SELECT `id` FROM `users` WHERE `email` = ? AND `id` != ?");
        $dup_stmt->bind_param("si", $admin_email, $admin_id);
        $dup_stmt->execute();
        if ($dup_stmt->get_result()->num_rows > 0) {
            header("Location: settings.php?error=duplicate_email&tab=security");
            exit();
        }

        if (!empty($new_password)) {
            if ($new_password !== $confirm_password) {
                header("Location: settings.php?error=password_mismatch&tab=security");
                exit();
            }
            if (strlen($new_password) < 6) {
                header("Location: settings.php?error=password_too_short&tab=security");
                exit();
            }
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE `users` SET `name` = ?, `email` = ?, `password` = ? WHERE `id` = ?");
            $stmt->bind_param("sssi", $admin_name, $admin_email, $hash, $admin_id);
        } else {
            $stmt = $conn->prepare("UPDATE `users` SET `name` = ?, `email` = ? WHERE `id` = ?");
            $stmt->bind_param("ssi", $admin_name, $admin_email, $admin_id);
        }

        $stmt->execute();

        $_SESSION['admin_name'] = $admin_name;
        $_SESSION['admin_email'] = $admin_email;

        logActivity($conn, 'Admin Profile Updated', 'Admin account credentials modified');
        header("Location: settings.php?msg=security_saved&tab=security");
        exit();
    }

    // Action 4: Clear Audit Logs
    if ($action === 'clear_logs') {
        $conn->query("TRUNCATE TABLE `activity_logs`");
        logActivity($conn, 'Logs Cleared', 'Audit trail cleared by administrator');
        header("Location: settings.php?msg=logs_cleared&tab=logs");
        exit();
    }
}

// Fetch current Barangay information
$info_res = $conn->query("SELECT * FROM `barangay_information` WHERE `id` = 1");
$info = ($info_res && $info_res->num_rows > 0) ? $info_res->fetch_assoc() : [];

// Fetch current Admin account from users table
$admin_id = $_SESSION['admin_id'] ?? 1;
$u_stmt = $conn->prepare("SELECT * FROM `users` WHERE `id` = ? LIMIT 1");
$u_stmt->bind_param("i", $admin_id);
$u_stmt->execute();
$admin_user = $u_stmt->get_result()->fetch_assoc();

if (!$admin_user) {
    // Fallback if id not matched
    $u_res = $conn->query("SELECT * FROM `users` WHERE `role` = 'admin' LIMIT 1");
    $admin_user = ($u_res && $u_res->num_rows > 0) ? $u_res->fetch_assoc() : [
        'name' => 'System Administrator',
        'email' => 'admin@example.com'
    ];
}

// Helper to resolve admin logo image path
function get_settings_logo_src($path) {
    if (empty($path)) return '../Barangay Logo/Logo.png';
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) return $path;
    if (strpos($path, 'uploads/') === 0) return $path;
    if (strpos($path, '../') === 0) return $path;
    return '../' . ltrim($path, '/');
}

$active_logo_src = get_settings_logo_src($info['logo_path'] ?? '');

// Fetch recent 50 activity logs
$logs_res = $conn->query("SELECT * FROM `activity_logs` ORDER BY `id` DESC LIMIT 50");
$recent_logs = [];
if ($logs_res) {
    while ($l = $logs_res->fetch_assoc()) {
        $recent_logs[] = $l;
    }
}

// Active tab from GET parameter
$active_tab = $_GET['tab'] ?? 'general';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings & System Configuration - Brgy Tabon</title>
    
    <!-- Bootstrap Icons v1.11.3 CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- FontAwesome CSS CDN for Sidebar -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="css/admin_common.css">
    <link rel="stylesheet" href="Admin CSS/settings.css?v=<?= time() ?>">
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="<?= htmlspecialchars($active_logo_src) ?>" alt="Barangay Logo" onerror="this.src='../Barangay Logo/Logo.png'" id="sidebarLogoImg">
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
                        <a href="barangayofficials.php">Barangay Official</a>
                        <a href="SKofficials.php">SK Official</a>
                    </div>
                </li>
                <li><a href="services.php"><i class="fa-solid fa-bell-concierge"></i> Services</a></li>
                <li><a href="announcements.php"><i class="fa-solid fa-bullhorn"></i> Announcement</a></li>
                <li><a href="feedbackchat.php"><i class="fa-solid fa-comments"></i> Feedback Chat</a></li>
                <li><a href="settings.php" class="active"><i class="fa-solid fa-gear"></i> Admin Settings</a></li>
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
            <h1 class="page-title" style="font-size: 20px; font-weight: 700; color: #1e293b;">
                System Configuration Center
            </h1>
            <div class="user-profile" style="display: flex; align-items: center; gap: 10px; text-align: right;">
                <div class="user-info">
                    <h4 style="font-size: 14px; font-weight: 700; color: #2b323c; margin: 0;"><?= htmlspecialchars($admin_user['name']) ?></h4>
                    <p style="font-size: 12px; color: #6b7280; margin: 0;"><?= htmlspecialchars($admin_user['email']) ?></p>
                </div>
                <div class="user-avatar" style="width: 40px; height: 40px; background-color: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #ffffff;">
                    <?= strtoupper(substr($admin_user['name'] ?? 'A', 0, 1)) ?>
                </div>
            </div>
        </div>

        <div class="settings-page-wrapper">

            <!-- Banner Header -->
            <div class="settings-banner-header">
                <div class="settings-banner-title">
                    <h2><i class="bi bi-sliders"></i> Admin Settings & Configuration <span class="badge-version">v2.4 LTS</span></h2>
                    <p>Manage Barangay Tabon identity, emergency desk contacts, administrative security, database health, and activity audit trails.</p>
                </div>
                <div class="settings-banner-stats">
                    <div class="banner-stat-pill">
                        <i class="bi bi-shield-check"></i> Status: <strong style="color: #4ade80;">Operational</strong>
                    </div>
                    <div class="banner-stat-pill">
                        <i class="bi bi-clock-history"></i> Logs: <strong><?= count($recent_logs) ?></strong>
                    </div>
                </div>
            </div>

            <!-- Feedback Alert Banners -->
            <?php if (isset($_GET['msg'])): ?>
                <?php if ($_GET['msg'] === 'general_saved'): ?>
                    <div class="alert-banner alert-success">
                        <div class="alert-banner-content">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Barangay Profile details and official logo updated successfully!</span>
                        </div>
                        <button type="button" class="alert-close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
                    </div>
                <?php elseif ($_GET['msg'] === 'contacts_saved'): ?>
                    <div class="alert-banner alert-success">
                        <div class="alert-banner-content">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Emergency hotlines and public contact numbers updated successfully!</span>
                        </div>
                        <button type="button" class="alert-close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
                    </div>
                <?php elseif ($_GET['msg'] === 'security_saved'): ?>
                    <div class="alert-banner alert-success">
                        <div class="alert-banner-content">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Administrator credentials and password updated successfully!</span>
                        </div>
                        <button type="button" class="alert-close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
                    </div>
                <?php elseif ($_GET['msg'] === 'logs_cleared'): ?>
                    <div class="alert-banner alert-success">
                        <div class="alert-banner-content">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Audit activity logs have been cleared successfully.</span>
                        </div>
                        <button type="button" class="alert-close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert-banner alert-danger">
                    <div class="alert-banner-content">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span>
                            <?php 
                                if ($_GET['error'] === 'duplicate_email') echo 'This email address is already in use by another user account.';
                                elseif ($_GET['error'] === 'password_mismatch') echo 'New password and confirmation password do not match.';
                                elseif ($_GET['error'] === 'password_too_short') echo 'New password must be at least 6 characters long.';
                                else echo 'Please check all required fields and try again.';
                            ?>
                        </span>
                    </div>
                    <button type="button" class="alert-close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
                </div>
            <?php endif; ?>

            <!-- 2. Tab Navigation System (5 Control Tabs) -->
            <div class="settings-nav-tabs">
                <button type="button" class="settings-tab-btn <?= ($active_tab === 'general') ? 'active' : '' ?>" id="tabBtn-general" onclick="switchTab('general', this)">
                    <i class="bi bi-building"></i> Barangay Profile
                </button>
                <button type="button" class="settings-tab-btn <?= ($active_tab === 'contacts') ? 'active' : '' ?>" id="tabBtn-contacts" onclick="switchTab('contacts', this)">
                    <i class="bi bi-telephone-outbound"></i> Hotlines & Contacts
                </button>
                <button type="button" class="settings-tab-btn <?= ($active_tab === 'security') ? 'active' : '' ?>" id="tabBtn-security" onclick="switchTab('security', this)">
                    <i class="bi bi-shield-lock"></i> Security & Password
                </button>
                <button type="button" class="settings-tab-btn <?= ($active_tab === 'backup') ? 'active' : '' ?>" id="tabBtn-backup" onclick="switchTab('backup', this)">
                    <i class="bi bi-database-check"></i> System & Backup
                </button>
                <button type="button" class="settings-tab-btn <?= ($active_tab === 'logs') ? 'active' : '' ?>" id="tabBtn-logs" onclick="switchTab('logs', this)">
                    <i class="bi bi-clock-history"></i> Activity Logs
                </button>
            </div>

            <!-- Tab 1: Barangay Profile (#tab-general) -->
            <div class="settings-tab-pane <?= ($active_tab === 'general') ? 'active' : '' ?>" id="tab-general">
                <div class="settings-card">
                    <div class="settings-card-header">
                        <div>
                            <h3><i class="bi bi-building" style="color: #10b981;"></i> Barangay Profile & Official Identity</h3>
                            <p>Configure official branding seal, local government jurisdiction, slogan, and operating schedules.</p>
                        </div>
                    </div>
                    <form method="POST" action="settings.php" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="save_general">
                        
                        <div class="settings-card-body">
                            
                            <!-- Logo Upload Box with Instant Preview -->
                            <div class="form-group">
                                <label class="form-label"><i class="bi bi-image"></i> Official Barangay Seal / Logo</label>
                                <div class="logo-upload-wrapper">
                                    <div class="logo-preview-box">
                                        <img id="logoPreviewImg" src="<?= htmlspecialchars($active_logo_src) ?>" alt="Barangay Logo">
                                    </div>
                                    <div class="logo-upload-actions">
                                        <input type="file" name="brgy_logo" id="logoFileInput" accept="image/*" style="display: none;" onchange="previewLogo(this)">
                                        <button type="button" class="btn-upload-file" onclick="document.getElementById('logoFileInput').click()">
                                            <i class="bi bi-cloud-arrow-up"></i> Choose New Logo Seal
                                        </button>
                                        <span class="logo-upload-hint">Recommended format: PNG, SVG, or high-res WebP with transparent background (Max: 5MB).</span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-grid-3">
                                <div class="form-group">
                                    <label class="form-label" for="barangayNameInput">Barangay Name <span class="required">*</span></label>
                                    <input type="text" name="barangay_name" id="barangayNameInput" class="form-control" value="<?= htmlspecialchars($info['barangay_name'] ?? 'Barangay Tabon') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="municipalityInput">Municipality <span class="required">*</span></label>
                                    <input type="text" name="municipality" id="municipalityInput" class="form-control" value="<?= htmlspecialchars($info['municipality'] ?? 'Lapuyan') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="provinceInput">Province <span class="required">*</span></label>
                                    <input type="text" name="province" id="provinceInput" class="form-control" value="<?= htmlspecialchars($info['province'] ?? 'Zamboanga del Sur') ?>" required>
                                </div>
                            </div>

                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label class="form-label" for="sloganInput"><i class="bi bi-chat-quote"></i> Official Slogan</label>
                                    <input type="text" name="slogan" id="sloganInput" class="form-control" value="<?= htmlspecialchars($info['slogan'] ?? 'Serbisyong Tapat, Para sa Lahat') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="officeHoursInput"><i class="bi bi-clock"></i> Office Schedule / Hours</label>
                                    <input type="text" name="office_hours" id="officeHoursInput" class="form-control" value="<?= htmlspecialchars($info['office_hours'] ?? 'Monday - Friday, 8:00 AM - 5:00 PM') ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="hallAddressInput"><i class="bi bi-geo-alt"></i> Physical Hall Address</label>
                                <input type="text" name="hall_address" id="hallAddressInput" class="form-control" value="<?= htmlspecialchars($info['hall_address'] ?? 'Barangay Hall, Tabon, Lapuyan, Zamboanga del Sur') ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="missionVisionInput"><i class="bi bi-compass"></i> Mission & Vision Summary</label>
                                <textarea name="mission_vision" id="missionVisionInput" class="form-control" placeholder="A peaceful, progressive, and resilient community guided by transparent governance..."><?= htmlspecialchars($info['mission_vision'] ?? '') ?></textarea>
                            </div>

                        </div>

                        <div class="settings-card-footer">
                            <button type="submit" class="btn-save-settings">
                                <i class="bi bi-check-circle"></i> Save Barangay Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tab 2: Hotlines & Emergency Contacts (#tab-contacts) -->
            <div class="settings-tab-pane <?= ($active_tab === 'contacts') ? 'active' : '' ?>" id="tab-contacts">
                <div class="settings-card">
                    <div class="settings-card-header">
                        <div>
                            <h3><i class="bi bi-telephone-outbound" style="color: #ef4444;"></i> Public Contacts & Emergency Desk</h3>
                            <p>Configure official emergency telephone lines, ambulance dispatch, PNP station, and social media links.</p>
                        </div>
                    </div>
                    <form method="POST" action="settings.php">
                        <input type="hidden" name="action" value="save_contacts">

                        <div class="settings-card-body">
                            
                            <!-- General Public Inquiries -->
                            <h4 style="font-size: 14px; font-weight: 700; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 16px 0; border-bottom: 2px solid #f1f5f9; padding-bottom: 6px;">
                                <i class="bi bi-info-circle" style="color: #0284c7;"></i> General Public Contacts
                            </h4>
                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label class="form-label" for="contactEmailInput"><i class="bi bi-envelope"></i> Official Barangay Email</label>
                                    <div class="input-icon-group">
                                        <i class="bi bi-envelope input-icon-prefix"></i>
                                        <input type="email" name="email" id="contactEmailInput" class="form-control" value="<?= htmlspecialchars($info['email'] ?? 'barangaytabon@gmail.com') ?>" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="contactPhoneInput"><i class="bi bi-telephone"></i> Main Hall Contact Number</label>
                                    <div class="input-icon-group">
                                        <i class="bi bi-telephone input-icon-prefix"></i>
                                        <input type="text" name="contact_number" id="contactPhoneInput" class="form-control" value="<?= htmlspecialchars($info['contact_number'] ?? '(+63) 912 345 6789') ?>" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Emergency Dispatch Desk -->
                            <h4 style="font-size: 14px; font-weight: 700; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; margin: 24px 0 16px 0; border-bottom: 2px solid #f1f5f9; padding-bottom: 6px;">
                                <i class="bi bi-shield-exclamation" style="color: #ef4444;"></i> 24/7 Emergency Dispatch Hotlines
                            </h4>
                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label class="form-label" for="policeHotlineInput"><i class="bi bi-shield-fill"></i> Lapuyan PNP Police Station</label>
                                    <div class="input-icon-group">
                                        <i class="bi bi-shield-fill input-icon-prefix" style="color: #2563eb;"></i>
                                        <input type="text" name="police_hotline" id="policeHotlineInput" class="form-control" value="<?= htmlspecialchars($info['police_hotline'] ?? '0998-598-7241') ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="fireHotlineInput"><i class="bi bi-fire"></i> Lapuyan Fire Department (BFP)</label>
                                    <div class="input-icon-group">
                                        <i class="bi bi-fire input-icon-prefix" style="color: #dc2626;"></i>
                                        <input type="text" name="fire_hotline" id="fireHotlineInput" class="form-control" value="<?= htmlspecialchars($info['fire_hotline'] ?? '0917-123-4567') ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label class="form-label" for="healthHotlineInput"><i class="bi bi-heart-pulse-fill"></i> Health Center / Ambulance Hotline</label>
                                    <div class="input-icon-group">
                                        <i class="bi bi-heart-pulse-fill input-icon-prefix" style="color: #10b981;"></i>
                                        <input type="text" name="health_hotline" id="healthHotlineInput" class="form-control" value="<?= htmlspecialchars($info['health_hotline'] ?? '0920-987-6543') ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="tanodHotlineInput"><i class="bi bi-person-badge"></i> Barangay Tanod / Peace & Order</label>
                                    <div class="input-icon-group">
                                        <i class="bi bi-person-badge input-icon-prefix" style="color: #f59e0b;"></i>
                                        <input type="text" name="tanod_hotline" id="tanodHotlineInput" class="form-control" value="<?= htmlspecialchars($info['tanod_hotline'] ?? '0931-234-5678') ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Social Media Links -->
                            <h4 style="font-size: 14px; font-weight: 700; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; margin: 24px 0 16px 0; border-bottom: 2px solid #f1f5f9; padding-bottom: 6px;">
                                <i class="bi bi-share" style="color: #6366f1;"></i> Social Media Presence
                            </h4>
                            <div class="form-group">
                                <label class="form-label" for="facebookUrlInput"><i class="bi bi-facebook"></i> Official Facebook Page URL</label>
                                <div class="input-icon-group">
                                    <i class="bi bi-facebook input-icon-prefix" style="color: #1877f2;"></i>
                                    <input type="url" name="facebook_url" id="facebookUrlInput" class="form-control" value="<?= htmlspecialchars($info['facebook_url'] ?? 'https://www.facebook.com/') ?>">
                                </div>
                            </div>

                        </div>

                        <div class="settings-card-footer">
                            <button type="submit" class="btn-save-settings">
                                <i class="bi bi-check-circle"></i> Save Contacts & Hotlines
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tab 3: Security & Password (#tab-security) -->
            <div class="settings-tab-pane <?= ($active_tab === 'security') ? 'active' : '' ?>" id="tab-security">
                <div class="settings-card">
                    <div class="settings-card-header">
                        <div>
                            <h3><i class="bi bi-shield-lock" style="color: #6366f1;"></i> Administrator Account & Security</h3>
                            <p>Update administrator full name, login email address, and change master authentication password.</p>
                        </div>
                    </div>

                    <form method="POST" action="settings.php">
                        <input type="hidden" name="action" value="update_admin_security">

                        <div class="settings-card-body">
                            
                            <!-- Active Admin Profile Card -->
                            <div class="admin-badge-card">
                                <div class="admin-badge-info">
                                    <div class="admin-badge-avatar">
                                        <?= strtoupper(substr($admin_user['name'] ?? 'A', 0, 1)) ?>
                                    </div>
                                    <div class="admin-badge-title">
                                        <h4><?= htmlspecialchars($admin_user['name'] ?? 'System Administrator') ?></h4>
                                        <p><i class="bi bi-envelope"></i> <?= htmlspecialchars($admin_user['email'] ?? 'admin@example.com') ?></p>
                                    </div>
                                </div>
                                <div class="super-admin-tag">
                                    <i class="bi bi-patch-check-fill"></i> Super Administrator
                                </div>
                            </div>

                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label class="form-label" for="adminNameInput"><i class="bi bi-person"></i> Administrator Full Name <span class="required">*</span></label>
                                    <input type="text" name="admin_name" id="adminNameInput" class="form-control" value="<?= htmlspecialchars($admin_user['name'] ?? '') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="adminEmailInput"><i class="bi bi-envelope-at"></i> Login Email Address <span class="required">*</span></label>
                                    <input type="email" name="admin_email" id="adminEmailInput" class="form-control" value="<?= htmlspecialchars($admin_user['email'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div style="background: #f8fafc; border-radius: 10px; padding: 20px; border: 1px solid #e2e8f0; margin-top: 10px;">
                                <h4 style="margin: 0 0 6px 0; font-size: 14px; font-weight: 700; color: #1e293b;">
                                    <i class="bi bi-key" style="color: #f59e0b;"></i> Change Password (Optional)
                                </h4>
                                <p style="margin: 0 0 16px 0; font-size: 12.5px; color: #64748b;">
                                    Leave blank if you do not wish to change your current administrator password.
                                </p>

                                <div class="form-grid-2">
                                    <div class="form-group">
                                        <label class="form-label" for="newPasswordInput">New Password (min. 6 characters)</label>
                                        <div class="password-toggle-group">
                                            <input type="password" name="new_password" id="newPasswordInput" class="form-control" minlength="6" placeholder="Enter new password">
                                            <button type="button" class="btn-password-toggle" onclick="togglePasswordVisibility('newPasswordInput', this)" title="Show/Hide Password">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="confirmPasswordInput">Confirm New Password</label>
                                        <div class="password-toggle-group">
                                            <input type="password" name="confirm_password" id="confirmPasswordInput" class="form-control" minlength="6" placeholder="Repeat new password">
                                            <button type="button" class="btn-password-toggle" onclick="togglePasswordVisibility('confirmPasswordInput', this)" title="Show/Hide Password">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="settings-card-footer">
                            <button type="submit" class="btn-save-settings">
                                <i class="bi bi-shield-check"></i> Update Security Credentials
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tab 4: Database & System Health (#tab-backup) -->
            <div class="settings-tab-pane <?= ($active_tab === 'backup') ? 'active' : '' ?>" id="tab-backup">
                <div class="settings-card">
                    <div class="settings-card-header">
                        <div>
                            <h3><i class="bi bi-database-check" style="color: #0284c7;"></i> Database & Server Diagnostics</h3>
                            <p>Real-time environment metrics, PHP runtime configurations, and one-click database snapshot exporter.</p>
                        </div>
                    </div>

                    <div class="settings-card-body">
                        
                        <!-- System Diagnostics Grid (.system-info-grid) -->
                        <div class="system-info-grid">
                            <div class="system-stat-card">
                                <span class="stat-card-label"><i class="bi bi-code-slash" style="color: #4f46e5;"></i> PHP Runtime</span>
                                <span class="stat-card-value">v<?= phpversion() ?></span>
                                <span class="stat-card-desc">Zend Engine <?= zend_version() ?></span>
                            </div>
                            <div class="system-stat-card">
                                <span class="stat-card-label"><i class="bi bi-database" style="color: #10b981;"></i> MySQL Engine</span>
                                <span class="stat-card-value"><?= substr($conn->server_info ?? '5.7+', 0, 10) ?></span>
                                <span class="stat-card-desc">utf8mb4_unicode_ci</span>
                            </div>
                            <div class="system-stat-card">
                                <span class="stat-card-label"><i class="bi bi-cloud-arrow-up" style="color: #0284c7;"></i> Max Upload</span>
                                <span class="stat-card-value"><?= ini_get('upload_max_filesize') ?></span>
                                <span class="stat-card-desc">Post: <?= ini_get('post_max_size') ?></span>
                            </div>
                            <div class="system-stat-card">
                                <span class="stat-card-label"><i class="bi bi-hdd-network" style="color: #f59e0b;"></i> Server OS</span>
                                <span class="stat-card-value"><?= php_uname('s') ?></span>
                                <span class="stat-card-desc"><?= php_uname('m') ?> Architecture</span>
                            </div>
                        </div>

                        <!-- One-Click Instant SQL Backup Downloader -->
                        <div class="backup-download-box">
                            <div class="backup-download-info">
                                <div class="backup-icon-circle">
                                    <i class="bi bi-download"></i>
                                </div>
                                <div class="backup-download-text">
                                    <h4>Download Complete SQL Database Backup</h4>
                                    <p>Generates an instant `.sql` dump of all tables, schema definitions, and system records for offline archiving or disaster recovery.</p>
                                </div>
                            </div>
                            <a href="export_backup.php" class="btn-backup-download">
                                <i class="bi bi-file-earmark-arrow-down-fill"></i> Download SQL Snapshot
                            </a>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Tab 5: Activity Logs & Audit Trail (#tab-logs) -->
            <div class="settings-tab-pane <?= ($active_tab === 'logs') ? 'active' : '' ?>" id="tab-logs">
                <div class="settings-card">
                    <div class="settings-card-header">
                        <div>
                            <h3><i class="bi bi-clock-history" style="color: #f59e0b;"></i> System Activity Logs & Audit Trail</h3>
                            <p>Showing the 50 most recent administrative actions, system events, and client IP records.</p>
                        </div>
                        <?php if (!empty($recent_logs)): ?>
                        <form method="POST" action="settings.php" onsubmit="return confirm('Are you sure you want to permanently clear all audit activity logs?');">
                            <input type="hidden" name="action" value="clear_logs">
                            <button type="submit" class="btn-clear-logs">
                                <i class="bi bi-trash"></i> Clear Logs
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>

                    <div class="settings-card-body" style="padding: 0;">
                        <div class="activity-table-container">
                            <table class="activity-table">
                                <thead>
                                    <tr>
                                        <th style="width: 180px;">Timestamp</th>
                                        <th style="width: 170px;">Action Tag</th>
                                        <th>Details</th>
                                        <th style="width: 140px;">IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_logs)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; color: #94a3b8; padding: 40px;">
                                            <i class="bi bi-journal-x" style="font-size: 28px; display: block; margin-bottom: 8px;"></i>
                                            No activity recorded yet. System administrative actions will appear here automatically.
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_logs as $log): 
                                            $action_class = 'general';
                                            $act_lower = strtolower($log['action']);
                                            if (strpos($act_lower, 'profile') !== false) $action_class = 'profile';
                                            elseif (strpos($act_lower, 'contact') !== false) $action_class = 'contacts';
                                            elseif (strpos($act_lower, 'security') !== false || strpos($act_lower, 'admin') !== false) $action_class = 'security';
                                            elseif (strpos($act_lower, 'clear') !== false) $action_class = 'logs';
                                        ?>
                                        <tr>
                                            <td>
                                                <i class="bi bi-calendar3" style="color: #94a3b8; margin-right: 4px;"></i>
                                                <?= date('M d, Y h:i A', strtotime($log['created_at'])) ?>
                                            </td>
                                            <td>
                                                <span class="action-tag <?= $action_class ?>">
                                                    <i class="bi bi-dot" style="font-size: 16px;"></i>
                                                    <?= htmlspecialchars($log['action']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($log['details'] ?? 'N/A') ?>
                                            </td>
                                            <td>
                                                <span class="ip-pill"><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?></span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </main>

    <!-- Floating Toast Notification (#adminToast) -->
    <div class="admin-toast" id="adminToast" style="display:none;">
        <i class="bi bi-check-circle-fill" id="adminToastIcon"></i>
        <span id="adminToastMsg">Settings updated successfully!</span>
    </div>

    <!-- JavaScript Navigation & Interactivity -->
    <script>
        // 1. Tab Navigation System with URL Sync
        function switchTab(tabId, btn) {
            // Remove active classes
            document.querySelectorAll('.settings-tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.settings-tab-pane').forEach(p => p.classList.remove('active'));

            // Activate tab and pane
            if (btn) btn.classList.add('active');
            const targetPane = document.getElementById('tab-' + tabId);
            if (targetPane) {
                targetPane.classList.add('active');
            }

            // Sync URL parameter without reload
            try {
                const url = new URL(window.location);
                url.searchParams.set('tab', tabId);
                window.history.replaceState({ tab: tabId }, '', url.toString());
            } catch (e) {}
        }

        // 2. Live Logo Image Preview
        function previewLogo(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('logoPreviewImg');
                    if (preview) preview.src = e.target.result;
                    const sidebarLogo = document.getElementById('sidebarLogoImg');
                    if (sidebarLogo) sidebarLogo.src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // 3. Show/Hide Password Toggles
        function togglePasswordVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const icon = btn.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                if (icon) {
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                }
            } else {
                input.type = 'password';
                if (icon) {
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            }
        }

        // Restore active tab on page load
        document.addEventListener('DOMContentLoaded', function() {
            const params = new URLSearchParams(window.location.search);
            const activeTab = params.get('tab');
            if (activeTab) {
                const btn = document.getElementById('tabBtn-' + activeTab);
                if (btn) switchTab(activeTab, btn);
            }
        });
    </script>
</body>
</html>
