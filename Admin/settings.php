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
    `police_hotline` VARCHAR(50) DEFAULT '0998-598-7241',
    `fire_hotline` VARCHAR(50) DEFAULT '0917-123-4567',
    `health_hotline` VARCHAR(50) DEFAULT '0920-987-6543',
    `tanod_hotline` VARCHAR(50) DEFAULT '0931-234-5678',
    `logo_path` VARCHAR(255) DEFAULT '../Barangay Logo/Logo.png',
    `municipality_logo_path` VARCHAR(255) DEFAULT '../Barangay Logo/lapuyanlogo.jpg',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

// Ensure record id = 1 exists in barangay_information
$check_info = $conn->query("SELECT * FROM `barangay_information` WHERE `id` = 1");
if ($check_info && $check_info->num_rows === 0) {
    $conn->query("INSERT INTO `barangay_information` (
        `id`, `barangay_name`, `municipality`, `province`, `slogan`, `office_hours`, 
        `hall_address`, `email`, `contact_number`,
        `police_hotline`, `fire_hotline`, `health_hotline`, `tanod_hotline`, `logo_path`
    ) VALUES (
        1, 'Barangay Tabon', 'Lapuyan', 'Zamboanga del Sur', 'Serbisyong Tapat, Para sa Lahat',
        'Monday - Friday, 8:00 AM - 5:00 PM', 'Barangay Hall, Tabon, Lapuyan, Zamboanga del Sur',
        'barangaytabon@gmail.com', '(+63) 912 345 6789',
        '0998-598-7241', '0917-123-4567', '0920-987-6543', '0931-234-5678', '../Barangay Logo/Logo.png'
    )");
}

// 1.3 users Table for administrator security
$conn->query("CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100) NULL,
    `name` VARCHAR(150) NOT NULL DEFAULT 'System Administrator',
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(50) DEFAULT 'admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

ensureColumnExists($conn, 'users', 'username', "VARCHAR(100) NULL AFTER `id`");
$conn->query("UPDATE `users` SET `username` = 'admin' WHERE `id` = 1 AND (`username` IS NULL OR `username` = '')");

// Seed default admin in users table if empty
$check_user = $conn->query("SELECT * FROM `users` WHERE `role` = 'admin' LIMIT 1");
if ($check_user && $check_user->num_rows === 0) {
    $default_pw_hash = password_hash('123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO `users` (`username`, `name`, `email`, `password`, `role`) VALUES ('admin', 'System Administrator', 'admin@example.com', '$default_pw_hash', 'admin')");
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

        // Fetch current branding assets
        $curr_logo = '../Barangay Logo/Logo.png';
        $curr_muni_logo = '../Barangay Logo/lapuyanlogo.jpg';
        $curr_hero_video = 'Video/Homepage.mp4';
        
        $l_res = $conn->query("SELECT `logo_path`, `municipality_logo_path`, `hero_video_path` FROM `barangay_information` WHERE `id` = 1");
        if ($l_res && $row = $l_res->fetch_assoc()) {
            if (!empty($row['logo_path'])) $curr_logo = $row['logo_path'];
            if (!empty($row['municipality_logo_path'])) $curr_muni_logo = $row['municipality_logo_path'];
            if (!empty($row['hero_video_path'])) $curr_hero_video = $row['hero_video_path'];
        }

        // 1. Process uploaded Barangay Seal if present
        if (isset($_FILES['brgy_logo']) && $_FILES['brgy_logo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['brgy_logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg'])) {
                $new_logo_name = 'logo_' . time() . '.' . $ext;
                $target_file = $branding_dir . $new_logo_name;
                if (move_uploaded_file($_FILES['brgy_logo']['tmp_name'], $target_file)) {
                    $curr_logo = 'uploads/branding/' . $new_logo_name;
                    @copy($target_file, __DIR__ . '/../Barangay Logo/Logo.png');
                }
            }
        }

        // 2. Process uploaded Municipal Seal if present
        if (isset($_FILES['muni_logo']) && $_FILES['muni_logo']['error'] === UPLOAD_ERR_OK) {
            $muni_ext = strtolower(pathinfo($_FILES['muni_logo']['name'], PATHINFO_EXTENSION));
            if (in_array($muni_ext, ['png', 'jpg', 'jpeg', 'webp', 'svg'])) {
                $new_muni_name = 'muni_logo_' . time() . '.' . $muni_ext;
                $muni_target_file = $branding_dir . $new_muni_name;
                if (move_uploaded_file($_FILES['muni_logo']['tmp_name'], $muni_target_file)) {
                    $curr_muni_logo = 'uploads/branding/' . $new_muni_name;
                    @copy($muni_target_file, __DIR__ . '/../Barangay Logo/lapuyanlogo.jpg');
                    @copy($muni_target_file, __DIR__ . '/../Barangay Logo/municipality_logo.jpg');
                }
            }
        }

        // 3. Process uploaded Homepage Hero Video if present
        if (isset($_FILES['hero_video']) && $_FILES['hero_video']['error'] === UPLOAD_ERR_OK) {
            $v_ext = strtolower(pathinfo($_FILES['hero_video']['name'], PATHINFO_EXTENSION));
            if (in_array($v_ext, ['mp4', 'webm', 'ogg', 'mov', 'mkv'])) {
                $new_video_name = 'hero_video_' . time() . '.' . $v_ext;
                $video_target = $branding_dir . $new_video_name;
                if (move_uploaded_file($_FILES['hero_video']['tmp_name'], $video_target)) {
                    $curr_hero_video = 'uploads/branding/' . $new_video_name;
                    // Also copy to root Video/Homepage.mp4 for fallback
                    $v_dir = __DIR__ . '/../Video/';
                    if (!is_dir($v_dir)) @mkdir($v_dir, 0777, true);
                    @copy($video_target, $v_dir . 'Homepage.mp4');
                }
            }
        }

        $stmt = $conn->prepare("UPDATE `barangay_information` SET 
            `barangay_name` = ?, `municipality` = ?, `province` = ?, `slogan` = ?, 
            `office_hours` = ?, `hall_address` = ?, `mission_vision` = ?, 
            `logo_path` = ?, `municipality_logo_path` = ?, `hero_video_path` = ? 
            WHERE `id` = 1");
        $stmt->bind_param("ssssssssss", $barangay_name, $municipality, $province, $slogan, $office_hours, $hall_address, $mission_vision, $curr_logo, $curr_muni_logo, $curr_hero_video);
        $stmt->execute();

        logActivity($conn, 'Profile Updated', 'Barangay profile details, branding seals, and hero video updated');
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
        $stmt = $conn->prepare("UPDATE `barangay_information` SET 
            `email` = ?, `contact_number` = ?, `police_hotline` = ?, `fire_hotline` = ?, 
            `health_hotline` = ?, `tanod_hotline` = ? 
            WHERE `id` = 1");
        $stmt->bind_param("ssssss", $email, $contact_number, $police_hotline, $fire_hotline, $health_hotline, $tanod_hotline);
        $stmt->execute();

        logActivity($conn, 'Contacts Updated', 'Emergency desk hotlines and public contact numbers updated');
        header("Location: settings.php?msg=contacts_saved&tab=contacts");
        exit();
    }

    // Action 3: Update Active Administrator Security Credentials
    if ($action === 'update_admin_security') {
        $admin_name       = trim($_POST['admin_name'] ?? '');
        $admin_username   = trim($_POST['admin_username'] ?? '');
        $admin_email      = trim($_POST['admin_email'] ?? '');
        $new_password     = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($admin_name) || empty($admin_email)) {
            header("Location: settings.php?error=empty_fields&tab=security");
            exit();
        }

        if (empty($admin_username)) {
            $admin_username = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', explode('@', $admin_email)[0]));
        }

        // Check duplicate email or username (excluding current admin ID)
        $admin_id = $_SESSION['admin_id'] ?? 1;
        $dup_stmt = $conn->prepare("SELECT `id` FROM `users` WHERE (`email` = ? OR `username` = ?) AND `id` != ?");
        $dup_stmt->bind_param("ssi", $admin_email, $admin_username, $admin_id);
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
            $stmt = $conn->prepare("UPDATE `users` SET `name` = ?, `username` = ?, `email` = ?, `password` = ? WHERE `id` = ?");
            $stmt->bind_param("ssssi", $admin_name, $admin_username, $admin_email, $hash, $admin_id);
        } else {
            $stmt = $conn->prepare("UPDATE `users` SET `name` = ?, `username` = ?, `email` = ? WHERE `id` = ?");
            $stmt->bind_param("sssi", $admin_name, $admin_username, $admin_email, $admin_id);
        }

        $stmt->execute();

        $_SESSION['admin_name'] = $admin_name;
        $_SESSION['admin_username'] = $admin_username;
        $_SESSION['admin_email'] = $admin_email;

        logActivity($conn, 'Admin Profile Updated', "Credentials modified for $admin_name ($admin_email)");
        header("Location: settings.php?msg=security_saved&tab=security");
        exit();
    }

    // Action 4: Create New Administrator / Staff Account
    if ($action === 'create_admin_user') {
        $new_name     = trim($_POST['new_admin_name'] ?? '');
        $new_username = trim($_POST['new_admin_username'] ?? '');
        $new_email    = trim($_POST['new_admin_email'] ?? '');
        $new_role     = in_array($_POST['new_admin_role'] ?? '', ['admin', 'staff', 'moderator']) ? $_POST['new_admin_role'] : 'admin';
        $new_password = $_POST['new_admin_password'] ?? '';

        if (empty($new_name) || empty($new_email) || empty($new_password)) {
            header("Location: settings.php?error=empty_fields&tab=security");
            exit();
        }

        if (empty($new_username)) {
            $new_username = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', explode('@', $new_email)[0]));
        }

        // Check duplicates
        $chk = $conn->prepare("SELECT id FROM `users` WHERE email = ? OR username = ? LIMIT 1");
        $chk->bind_param("ss", $new_email, $new_username);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            header("Location: settings.php?error=duplicate_email&tab=security");
            exit();
        }

        if (strlen($new_password) < 6) {
            header("Location: settings.php?error=password_too_short&tab=security");
            exit();
        }

        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO `users` (`name`, `username`, `email`, `password`, `role`) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $new_name, $new_username, $new_email, $hash, $new_role);
        $stmt->execute();

        logActivity($conn, 'Admin Account Created', "Created new account: $new_name ($new_email) as $new_role");
        header("Location: settings.php?msg=user_created&tab=security");
        exit();
    }

    // Action 5: Delete Administrator Account
    if ($action === 'delete_admin_user') {
        $del_id = (int)($_POST['user_id'] ?? 0);
        $curr_id = (int)($_SESSION['admin_id'] ?? 1);

        if ($del_id > 0 && $del_id !== $curr_id) {
            $stmt = $conn->prepare("DELETE FROM `users` WHERE `id` = ?");
            $stmt->bind_param("i", $del_id);
            $stmt->execute();

            logActivity($conn, 'Admin Account Deleted', "Removed administrator account #$del_id");
            header("Location: settings.php?msg=user_deleted&tab=security");
            exit();
        } else {
            header("Location: settings.php?error=cannot_delete_self&tab=security");
            exit();
        }
    }

    // Action 6: Clear Audit Logs
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
    $u_res = $conn->query("SELECT * FROM `users` ORDER BY `id` ASC LIMIT 1");
    $admin_user = ($u_res && $u_res->num_rows > 0) ? $u_res->fetch_assoc() : [
        'id' => 1,
        'name' => 'System Administrator',
        'username' => 'admin',
        'email' => 'admin@example.com',
        'role' => 'admin'
    ];
}

// Fetch all admin accounts for directory
$all_users_res = $conn->query("SELECT * FROM `users` ORDER BY `id` ASC");
$all_admin_users = [];
if ($all_users_res) {
    while ($u = $all_users_res->fetch_assoc()) {
        $all_admin_users[] = $u;
    }
}

// Helper to resolve admin logo image path
function get_settings_logo_src($path, $fallback = '../Barangay Logo/Logo.png') {
    if (empty($path) || strpos($path, 'Maruing') !== false) {
        return $fallback;
    }
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        return $path;
    }
    if (strpos($path, 'uploads/') === 0 && file_exists(__DIR__ . '/' . $path)) {
        return $path;
    }
    $clean_root = ltrim(preg_replace('/^\.\.\//', '', $path), '/');
    if (file_exists(__DIR__ . '/../' . $clean_root)) {
        return '../' . $clean_root;
    }
    return $fallback;
}

$active_logo_src = get_settings_logo_src($info['logo_path'] ?? '', '../Barangay Logo/Logo.png');
$active_muni_logo_src = get_settings_logo_src($info['municipality_logo_path'] ?? '', '../Barangay Logo/lapuyanlogo.jpg');

// Helper to resolve hero video src
function get_settings_video_src($path) {
    if (empty($path)) {
        return '../Video/Homepage.mp4';
    }
    if (strpos($path, 'uploads/') === 0 && file_exists(__DIR__ . '/' . $path)) {
        return $path;
    }
    $clean = ltrim(preg_replace('/^\.\.\//', '', $path), '/');
    if (file_exists(__DIR__ . '/../' . $clean)) {
        return '../' . $clean;
    }
    return '../Video/Homepage.mp4';
}

$active_video_src = get_settings_video_src($info['hero_video_path'] ?? 'Video/Homepage.mp4');

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
    <link rel="stylesheet" href="Admin CSS/admin_common.css?v=<?= time() ?>">
    <link rel="stylesheet" href="Admin CSS/settings.css?v=<?= time() ?>">
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="<?= htmlspecialchars($active_logo_src) ?>" alt="Barangay Logo" width="70" height="70" style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover; display: inline-block;" onerror="this.src='../Barangay Logo/Logo.png'" id="sidebarLogoImg">
            <h3>Brgy Tabon</h3>
            <p>Info SpotMap</p>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="controlpanel.php"><i class="fa-solid fa-gauge"></i> Control Panel</a></li>
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
                <li><a href="feedbackchat.php"><i class="fa-solid fa-comments"></i> Feedback Chat</a></li>
                <li><a href="settings.php" class="active"><i class="fa-solid fa-gear"></i> Admin Settings</a></li>
                
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
            <h1 class="page-title" style="font-size: 20px; font-weight: 700; color: #1e293b;">
                System Configuration Center
            </h1>
            <div class="user-profile" style="display: flex; align-items: center; gap: 10px; text-align: right;">
                <div class="user-info">
                    <h4 style="font-size: 14px; font-weight: 700; color: #2b323c; margin: 0;"><?= htmlspecialchars($admin_user['name']) ?></h4>
                    <p style="font-size: 12px; color: #6b7280; margin: 0;">@<?= htmlspecialchars($admin_user['username'] ?? 'admin') ?> &bull; <?= htmlspecialchars($admin_user['email']) ?></p>
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
                            <span>Administrator credentials and password updated successfully! You can now log in with your updated credentials.</span>
                        </div>
                        <button type="button" class="alert-close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
                    </div>
                <?php elseif ($_GET['msg'] === 'user_created'): ?>
                    <div class="alert-banner alert-success">
                        <div class="alert-banner-content">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>New administrator account created successfully!</span>
                        </div>
                        <button type="button" class="alert-close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
                    </div>
                <?php elseif ($_GET['msg'] === 'user_deleted'): ?>
                    <div class="alert-banner alert-success">
                        <div class="alert-banner-content">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Administrator account removed successfully.</span>
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
                                if ($_GET['error'] === 'duplicate_email') echo 'This email address or username is already in use by another user account.';
                                elseif ($_GET['error'] === 'password_mismatch') echo 'New password and confirmation password do not match.';
                                elseif ($_GET['error'] === 'password_too_short') echo 'New password must be at least 6 characters long.';
                                elseif ($_GET['error'] === 'cannot_delete_self') echo 'You cannot delete your own active administrator account.';
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
                            
                            <!-- Dual Seals Upload Grid: Barangay Seal (Left) & Municipal Seal (Right) -->
                            <div class="logo-upload-grid">
                                <!-- 1. Official Barangay Seal -->
                                <div class="form-group logo-card">
                                    <label class="form-label"><i class="bi bi-shield-check" style="color: #10b981;"></i> Official Barangay Seal (Header Left)</label>
                                    <div class="logo-upload-wrapper">
                                        <div class="logo-preview-box">
                                            <img id="logoPreviewImg" src="<?= htmlspecialchars($active_logo_src) ?>" alt="Barangay Logo" onerror="this.src='../Barangay Logo/Logo.png'">
                                        </div>
                                        <div class="logo-upload-actions">
                                            <input type="file" name="brgy_logo" id="logoFileInput" accept="image/*" style="display: none;" onchange="previewLogo(this)">
                                            <button type="button" class="btn-upload-file" onclick="document.getElementById('logoFileInput').click()">
                                                <i class="bi bi-cloud-arrow-up"></i> Change Barangay Seal
                                            </button>
                                            <span class="logo-upload-hint">Positioned at the left side of the header. Format: PNG, SVG, JPG (Max: 5MB).</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2. Municipality of Lapuyan Seal -->
                                <div class="form-group logo-card">
                                    <label class="form-label"><i class="bi bi-bank2" style="color: #0284c7;"></i> Municipality of Lapuyan Seal (Header Right)</label>
                                    <div class="logo-upload-wrapper">
                                        <div class="logo-preview-box">
                                            <img id="muniLogoPreviewImg" src="<?= htmlspecialchars($active_muni_logo_src) ?>" alt="Municipality Seal" onerror="this.src='../Barangay Logo/lapuyanlogo.jpg'">
                                        </div>
                                        <div class="logo-upload-actions">
                                            <input type="file" name="muni_logo" id="muniLogoFileInput" accept="image/*" style="display: none;" onchange="previewMuniLogo(this)">
                                            <button type="button" class="btn-upload-file" onclick="document.getElementById('muniLogoFileInput').click()">
                                                <i class="bi bi-cloud-arrow-up"></i> Change Municipal Seal
                                            </button>
                                            <span class="logo-upload-hint">Positioned at the right side of the header. Format: PNG, JPG, WebP (Max: 5MB).</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Homepage Hero Background Video (Replaceable with >= 30s minimum check) -->
                            <div class="video-upload-card">
                                <label class="form-label">
                                    <i class="bi bi-camera-video-fill" style="color: #6366f1;"></i> 
                                    Homepage Hero Background Video
                                </label>
                                <div class="video-upload-wrapper">
                                    <div class="video-preview-box">
                                        <video id="heroVideoPreview" controls playsinline muted loop>
                                            <source id="heroVideoPreviewSrc" src="<?= htmlspecialchars($active_video_src) ?>?v=<?= time() ?>" type="video/mp4">
                                            Your browser does not support HTML5 video preview.
                                        </video>
                                    </div>
                                    <div class="video-upload-details">
                                        <input type="file" name="hero_video" id="heroVideoFileInput" accept="video/mp4,video/webm,video/ogg,video/quicktime,video/x-matroska" style="display: none;" onchange="previewHeroVideo(this)">
                                        <div class="video-actions-row">
                                            <button type="button" class="btn-upload-file" onclick="document.getElementById('heroVideoFileInput').click()">
                                                <i class="bi bi-cloud-arrow-up"></i> Choose New Hero Video
                                            </button>
                                            <span class="video-current-badge"><i class="bi bi-check2-circle"></i> Active on Homepage Hero</span>
                                        </div>
                                        <div class="video-duration-notice" id="videoDurationNotice">
                                            <i class="bi bi-info-circle-fill" style="color: #6366f1;"></i> 
                                            <strong>Video Requirements:</strong> Upload a looping video for the Homepage Hero background. Minimum duration: <strong>30 seconds</strong> (MP4 / WebM / MOV, Max 128MB).
                                        </div>
                                        <div id="videoFileStats" class="video-file-stats" style="display: none;"></div>
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
                                    <label class="form-label" for="sloganInput">Official Barangay Slogan</label>
                                    <input type="text" name="slogan" id="sloganInput" class="form-control" value="<?= htmlspecialchars($info['slogan'] ?? 'Serbisyong Tapat, Para sa Lahat') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="officeHoursInput">Office Operating Hours</label>
                                    <input type="text" name="office_hours" id="officeHoursInput" class="form-control" value="<?= htmlspecialchars($info['office_hours'] ?? 'Monday - Friday, 8:00 AM - 5:00 PM') ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="hallAddressInput">Barangay Hall Physical Address <span class="required">*</span></label>
                                <input type="text" name="hall_address" id="hallAddressInput" class="form-control" value="<?= htmlspecialchars($info['hall_address'] ?? 'Barangay Hall, Tabon, Lapuyan, Zamboanga del Sur') ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="missionVisionInput">Barangay Mission, Vision & Core Values Statement</label>
                                <textarea name="mission_vision" id="missionVisionInput" class="form-control" rows="4"><?= htmlspecialchars($info['mission_vision'] ?? '') ?></textarea>
                            </div>

                        </div>

                        <div class="settings-card-footer">
                            <button type="submit" class="btn-save-settings">
                                <i class="bi bi-check-circle"></i> Save Profile Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tab 2: Hotlines & Contacts (#tab-contacts) -->
            <div class="settings-tab-pane <?= ($active_tab === 'contacts') ? 'active' : '' ?>" id="tab-contacts">
                <div class="settings-card">
                    <div class="settings-card-header">
                        <div>
                            <h3><i class="bi bi-telephone-outbound" style="color: #0284c7;"></i> Emergency Desk & Community Hotlines</h3>
                            <p>Configure official inquiry emails and emergency dispatch contact numbers published to citizens.</p>
                        </div>
                    </div>
                    <form method="POST" action="settings.php">
                        <input type="hidden" name="action" value="save_contacts">

                        <div class="settings-card-body">
                            
                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label class="form-label" for="contactEmailInput"><i class="bi bi-envelope"></i> Official Inquiry Email Address <span class="required">*</span></label>
                                    <div class="input-icon-group">
                                        <i class="bi bi-envelope input-icon-prefix" style="color: #0284c7;"></i>
                                        <input type="email" name="email" id="contactEmailInput" class="form-control" value="<?= htmlspecialchars($info['email'] ?? 'barangaytabon@gmail.com') ?>" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="contactNumberInput"><i class="bi bi-telephone"></i> Barangay Hall Office Hotline <span class="required">*</span></label>
                                    <div class="input-icon-group">
                                        <i class="bi bi-telephone input-icon-prefix" style="color: #10b981;"></i>
                                        <input type="text" name="contact_number" id="contactNumberInput" class="form-control" value="<?= htmlspecialchars($info['contact_number'] ?? '(+63) 912 345 6789') ?>" required>
                                    </div>
                                </div>
                            </div>

                            <h4 style="font-size: 14px; font-weight: 700; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; margin: 24px 0 16px 0; border-bottom: 2px solid #f1f5f9; padding-bottom: 6px;">
                                <i class="bi bi-shield-exclamation" style="color: #ef4444;"></i> Emergency Dispatch & Response Hotlines
                            </h4>

                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label class="form-label" for="policeHotlineInput"><i class="bi bi-shield"></i> PNP Police Assistance Desk</label>
                                    <div class="input-icon-group">
                                        <i class="bi bi-shield input-icon-prefix" style="color: #0284c7;"></i>
                                        <input type="text" name="police_hotline" id="policeHotlineInput" class="form-control" value="<?= htmlspecialchars($info['police_hotline'] ?? '0998-598-7241') ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="fireHotlineInput"><i class="bi bi-fire"></i> BFP Bureau of Fire Protection</label>
                                    <div class="input-icon-group">
                                        <i class="bi bi-fire input-icon-prefix" style="color: #ef4444;"></i>
                                        <input type="text" name="fire_hotline" id="fireHotlineInput" class="form-control" value="<?= htmlspecialchars($info['fire_hotline'] ?? '0917-123-4567') ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="healthHotlineInput"><i class="bi bi-heart-pulse"></i> Barangay Health Center / Ambulance</label>
                                    <div class="input-icon-group">
                                        <i class="bi bi-heart-pulse input-icon-prefix" style="color: #10b981;"></i>
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
                
                <!-- Active Administrator Credentials Card -->
                <div class="settings-card" style="margin-bottom: 24px;">
                    <div class="settings-card-header">
                        <div>
                            <h3><i class="bi bi-shield-lock" style="color: #6366f1;"></i> My Administrator Credentials</h3>
                            <p>Update your personal account credentials, login username, and authentication password used on the login portal.</p>
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
                                        <p><i class="bi bi-person-badge"></i> Username: <strong><?= htmlspecialchars($admin_user['username'] ?? 'admin') ?></strong> &bull; <i class="bi bi-envelope"></i> <?= htmlspecialchars($admin_user['email'] ?? 'admin@example.com') ?></p>
                                    </div>
                                </div>
                                <div class="super-admin-tag">
                                    <i class="bi bi-patch-check-fill"></i> Active Session
                                </div>
                            </div>


                            <div class="form-grid-3">
                                <div class="form-group">
                                    <label class="form-label" for="adminNameInput"><i class="bi bi-person"></i> Administrator Full Name <span class="required">*</span></label>
                                    <input type="text" name="admin_name" id="adminNameInput" class="form-control" value="<?= htmlspecialchars($admin_user['name'] ?? '') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="adminUsernameInput"><i class="bi bi-person-badge"></i> Login Username <span class="required">*</span></label>
                                    <input type="text" name="admin_username" id="adminUsernameInput" class="form-control" value="<?= htmlspecialchars($admin_user['username'] ?? 'admin') ?>" required>
                                    <span style="font-size: 11px; color: #64748b; margin-top: 4px; display: block;">You can log in using either this username or your email address.</span>
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

    <!-- Custom Client-Side JavaScript -->
    <script>
        // 1. Tab Switching Functionality
        function switchTab(tabId, btn) {
            const panes = document.querySelectorAll('.settings-tab-pane');
            panes.forEach(pane => pane.classList.remove('active'));

            const buttons = document.querySelectorAll('.settings-tab-btn');
            buttons.forEach(b => b.classList.remove('active'));

            const targetPane = document.getElementById('tab-' + tabId);
            if (targetPane) targetPane.classList.add('active');

            if (btn) {
                btn.classList.add('active');
            } else {
                const autoBtn = document.getElementById('tabBtn-' + tabId);
                if (autoBtn) autoBtn.classList.add('active');
            }

            try {
                const url = new URL(window.location);
                url.searchParams.set('tab', tabId);
                window.history.replaceState({ tab: tabId }, '', url.toString());
            } catch (e) {}
        }

        // 2. Toggle New Account Form Box
        function toggleNewAccountBox() {
            const box = document.getElementById('newAccountBox');
            if (box) {
                box.style.display = (box.style.display === 'block') ? 'none' : 'block';
            }
        }

        // 3. Live Logo Image Preview
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

        function previewMuniLogo(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('muniLogoPreviewImg');
                    if (preview) preview.src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Live preview and duration check for Homepage Hero Video (min 30 seconds)
        function previewHeroVideo(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            const videoPlayer = document.getElementById('heroVideoPreview');
            const statsBox = document.getElementById('videoFileStats');
            const noticeBox = document.getElementById('videoDurationNotice');

            // Format size in MB
            const sizeMB = (file.size / (1024 * 1024)).toFixed(1);

            // Object URL for instant client-side preview
            const objectUrl = URL.createObjectURL(file);
            videoPlayer.src = objectUrl;
            videoPlayer.load();

            // When metadata is loaded, check the duration
            videoPlayer.onloadedmetadata = function() {
                const duration = Math.round(videoPlayer.duration);
                const minutes = Math.floor(duration / 60);
                const seconds = duration % 60;
                const formattedTime = (minutes > 0 ? minutes + 'm ' : '') + seconds + 's';

                statsBox.style.display = 'flex';
                
                if (duration < 30) {
                    statsBox.innerHTML = `
                        <span class="video-status-pill warning"><i class="bi bi-exclamation-triangle-fill"></i> Duration: ${formattedTime}</span>
                        <span>Size: ${sizeMB} MB • File: <strong>${file.name}</strong></span>
                    `;
                    noticeBox.innerHTML = `
                        <i class="bi bi-exclamation-circle-fill" style="color: #d97706;"></i> 
                        <strong>Notice:</strong> This video is <strong>${formattedTime}</strong> long. The requirement recommends a <strong>minimum 30 seconds</strong> for optimal background looping on the homepage. You can still save it or select a longer clip.
                    `;
                    noticeBox.style.borderLeftColor = '#d97706';
                } else {
                    statsBox.innerHTML = `
                        <span class="video-status-pill valid"><i class="bi bi-check-circle-fill"></i> Duration: ${formattedTime} (Meets Requirement)</span>
                        <span>Size: ${sizeMB} MB • File: <strong>${file.name}</strong></span>
                    `;
                    noticeBox.innerHTML = `
                        <i class="bi bi-check-circle-fill" style="color: #16a34a;"></i> 
                        <strong>Ready:</strong> Video duration is <strong>${formattedTime}</strong> (${sizeMB} MB). Perfectly meets the 30-second minimum requirement for the homepage background!
                    `;
                    noticeBox.style.borderLeftColor = '#16a34a';
                }
            };
        }

        // 4. Show/Hide Password Toggles
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
