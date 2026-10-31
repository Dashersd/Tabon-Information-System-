<?php
// Admin/barangayofficials.php - Barangay Officials Management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../db_connect.php';

// Helper to resolve admin-accessible photo URL
function get_official_admin_photo($path) {
    if (empty($path)) {
        return '../Officials/Captain.jpg';
    }
    if (strpos($path, 'uploads/') === 0) {
        return $path;
    }
    if (strpos($path, 'Officials/') === 0) {
        return '../' . $path;
    }
    return '../' . ltrim($path, '/');
}

// Helper to synchronize officials to JSON backup
function sync_officials_to_json($conn, $json_file_path) {
    try {
        $res = $conn->query("SELECT * FROM `officials` ORDER BY `id` ASC");
        if ($res) {
            $data = [];
            while ($row = $res->fetch_assoc()) {
                $data[] = $row;
            }
            $dir = dirname($json_file_path);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($json_file_path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    } catch (Exception $e) {
        // Silently catch json write errors so database operation isn't hindered
    }
}

$json_cache_file = __DIR__ . '/data/barangay_officials.json';

// --- 1. Database Schema & Auto-Migration ---
$conn->query("CREATE TABLE IF NOT EXISTS `officials` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `official_key` varchar(50) DEFAULT NULL,
    `name` varchar(100) NOT NULL,
    `position` varchar(100) NOT NULL,
    `photo` varchar(255) DEFAULT NULL,
    `message` text DEFAULT NULL,
    `status` enum('active','inactive') DEFAULT 'active',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

// Check if official_key column exists
$check_key = $conn->query("SHOW COLUMNS FROM `officials` LIKE 'official_key'");
if ($check_key && $check_key->num_rows === 0) {
    $conn->query("ALTER TABLE `officials` ADD COLUMN `official_key` varchar(50) DEFAULT NULL AFTER `id`");
}

// Check if message column exists
$check_msg = $conn->query("SHOW COLUMNS FROM `officials` LIKE 'message'");
if ($check_msg && $check_msg->num_rows === 0) {
    $conn->query("ALTER TABLE `officials` ADD COLUMN `message` text DEFAULT NULL AFTER `photo`");
}

// Default 10 officials configuration
$default_officials = [
    'captain' => [
        'name' => 'Hon. Reynaldo D. Santos',
        'position' => 'Barangay Captain',
        'photo' => 'Officials/Captain.jpg',
        'message' => 'To our residents, partners, and every member of our community, thank you for your trust, cooperation, and unwavering support. At Barangay Tabon, we remain committed to delivering honest and transparent public service that promotes safety, unity, and progress for all. Together, let us continue building a barangay where every family feels heard, respected, and supported.'
    ],
    'secretary' => [
        'name' => 'Hon. Maria Cristina S. Flores',
        'position' => 'Barangay Secretary',
        'photo' => 'Officials/Captain.jpg',
        'message' => null
    ],
    'treasurer' => [
        'name' => 'Hon. Joseph L. Reyes',
        'position' => 'Barangay Treasurer',
        'photo' => 'Officials/Captain.jpg',
        'message' => null
    ],
    'kagawad_1' => [
        'name' => 'Hon. Mark Anthony T. Cruz',
        'position' => 'Barangay Kagawad',
        'photo' => 'Officials/Captain.jpg',
        'message' => null
    ],
    'kagawad_2' => [
        'name' => 'Hon. Alma B. Navarro',
        'position' => 'Barangay Kagawad',
        'photo' => 'Officials/Captain.jpg',
        'message' => null
    ],
    'kagawad_3' => [
        'name' => 'Hon. Allan P. Santos',
        'position' => 'Barangay Kagawad',
        'photo' => 'Officials/Captain.jpg',
        'message' => null
    ],
    'kagawad_4' => [
        'name' => 'Hon. Liza M. Torres',
        'position' => 'Barangay Kagawad',
        'photo' => 'Officials/Captain.jpg',
        'message' => null
    ],
    'kagawad_5' => [
        'name' => 'Hon. Ramon C. Dela Cruz',
        'position' => 'Barangay Kagawad',
        'photo' => 'Officials/Captain.jpg',
        'message' => null
    ],
    'kagawad_6' => [
        'name' => 'Hon. Nelcy D. Ongub',
        'position' => 'Barangay Kagawad',
        'photo' => 'Officials/Captain.jpg',
        'message' => null
    ],
    'kagawad_7' => [
        'name' => 'Hon. Janito Lipa',
        'position' => 'Barangay Kagawad',
        'photo' => 'Officials/Captain.jpg',
        'message' => null
    ]
];

// Seed missing keys or initial table
$existing_keys_res = $conn->query("SELECT `official_key` FROM `officials` WHERE `official_key` IS NOT NULL");
$existing_keys = [];
if ($existing_keys_res) {
    while ($r = $existing_keys_res->fetch_assoc()) {
        $existing_keys[] = $r['official_key'];
    }
}

$needs_sync = false;
foreach ($default_officials as $k => $d) {
    if (!in_array($k, $existing_keys)) {
        $ins = $conn->prepare("INSERT INTO `officials` (`official_key`, `name`, `position`, `photo`, `message`, `status`) VALUES (?, ?, ?, ?, ?, 'active')");
        $ins->bind_param("sssss", $k, $d['name'], $d['position'], $d['photo'], $d['message']);
        $ins->execute();
        $ins->close();
        $needs_sync = true;
    }
}

if ($needs_sync || !file_exists($json_cache_file)) {
    sync_officials_to_json($conn, $json_cache_file);
}

// --- 2. Handle Backend POST Actions ---
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'save_official') {
    $official_id = trim($_POST['official_id'] ?? '');
    $name = trim($_POST['official_name'] ?? '');
    $role = trim($_POST['official_role'] ?? '');
    $message = trim($_POST['official_message'] ?? '');

    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || !empty($_POST['ajax']);

    if (empty($official_id) || empty($name) || empty($role)) {
        if ($is_ajax) {
            while (ob_get_level()) { ob_end_clean(); }
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Official ID, Name, and Role are required.']);
            exit();
        } else {
            header("Location: barangayofficials.php?error=missing_fields");
            exit();
        }
    }

    // Get current official photo
    $sel = $conn->prepare("SELECT * FROM `officials` WHERE `official_key` = ? LIMIT 1");
    $sel->bind_param("s", $official_id);
    $sel->execute();
    $curr_off = $sel->get_result()->fetch_assoc();
    $sel->close();

    $photo_path = $curr_off['photo'] ?? 'Officials/Captain.jpg';

    // Handle Photo Upload if present
    if (isset($_FILES['official_photo']) && $_FILES['official_photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $orig_name = $_FILES['official_photo']['name'];
        $tmp_name = $_FILES['official_photo']['tmp_name'];
        $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $upload_dir = __DIR__ . '/uploads/officials/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $clean_key = preg_replace('/[^a-zA-Z0-9_-]/', '', $official_id);
            $filename = 'official_' . $clean_key . '_' . time() . '.' . $ext;
            if (move_uploaded_file($tmp_name, $upload_dir . $filename)) {
                $photo_path = 'uploads/officials/' . $filename;
            }
        }
    }

    // Update Database
    if ($official_id === 'captain') {
        $up = $conn->prepare("UPDATE `officials` SET `name` = ?, `position` = ?, `photo` = ?, `message` = ? WHERE `official_key` = ?");
        $up->bind_param("sssss", $name, $role, $photo_path, $message, $official_id);
    } else {
        $up = $conn->prepare("UPDATE `officials` SET `name` = ?, `position` = ?, `photo` = ? WHERE `official_key` = ?");
        $up->bind_param("ssss", $name, $role, $photo_path, $official_id);
    }
    
    $success = $up->execute();
    $up->close();

    // Sync JSON backup
    sync_officials_to_json($conn, $json_cache_file);

    if ($is_ajax) {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => 'Official details updated successfully!',
            'official' => [
                'id' => $official_id,
                'name' => $name,
                'role' => $role,
                'photo' => $photo_path,
                'photo_url' => get_official_admin_photo($photo_path),
                'message' => ($official_id === 'captain' ? $message : null)
            ]
        ]);
        exit();
    } else {
        header("Location: barangayofficials.php?msg=updated");
        exit();
    }
}

// --- 3. Fetch Officials for Display ---
$officials = [];
$res = $conn->query("SELECT * FROM `officials` WHERE `status` = 'active' ORDER BY `id` ASC");
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $k = !empty($row['official_key']) ? $row['official_key'] : ('off_' . $row['id']);
        $officials[$k] = $row;
    }
} elseif (file_exists($json_cache_file)) {
    // Failover from JSON cache
    $json_content = file_get_contents($json_cache_file);
    $arr = json_decode($json_content, true);
    if (!empty($arr)) {
        foreach ($arr as $row) {
            $k = !empty($row['official_key']) ? $row['official_key'] : ('off_' . $row['id']);
            $officials[$k] = $row;
        }
    }
}

// Ensure default fallback items if missing in array
foreach ($default_officials as $k => $d) {
    if (!isset($officials[$k])) {
        $officials[$k] = [
            'id' => 0,
            'official_key' => $k,
            'name' => $d['name'],
            'position' => $d['position'],
            'photo' => $d['photo'],
            'message' => $d['message']
        ];
    }
}

// Tiers Definition for 4-Tier Pyramid Layout
$tier1_keys = ['captain'];
$tier2_keys = ['secretary', 'treasurer'];
$tier3_keys = ['kagawad_1', 'kagawad_2', 'kagawad_3'];
$tier4_keys = ['kagawad_4', 'kagawad_5', 'kagawad_6', 'kagawad_7'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Barangay Officials - Brgy Tabon</title>
    
    <!-- Bootstrap Icons v1.11.3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- FontAwesome CSS CDN for Sidebar -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin Stylesheets -->
    <link rel="stylesheet" href="Admin CSS/admin_common.css?v=<?= time() ?>">
    <link rel="stylesheet" href="Admin CSS/barangayofficials.css?v=<?= time() ?>">
    <style>
/* admin/Admin CSS/barangayofficials.css */

.officials-header-container {
    margin-bottom: 25px;
}

.officials-header-container h2 {
    font-size: 20px;
    font-weight: 700;
    color: #2b323c;
    margin: 0 0 5px 0;
}

.officials-header-container p {
    font-size: 13px;
    color: #6b7280;
    margin: 0;
}

/* Pyramid Org Chart Wrapper */
.org-chart-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 28px;
    padding: 20px 10px 60px 10px;
    max-width: 1000px;
    margin: 0 auto;
}

/* Tiers */
.org-tier {
    display: flex;
    justify-content: center;
    align-items: stretch;
    gap: 22px;
    flex-wrap: wrap;
    width: 100%;
}

.tier-1 {
    margin-bottom: 5px;
}

/* Card Component */
.org-card {
    width: 170px;
    background: #ffffff;
    border-radius: 12px;
    padding: 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(0, 0, 0, 0.04);
    transition: transform 0.25s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow 0.25s ease;
    position: relative;
}

.org-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 22px rgba(0, 0, 0, 0.12);
    border-color: rgba(26, 77, 51, 0.2);
}

/* Photo Container */
.org-photo-container {
    position: relative;
    width: 150px;
    height: 150px;
    border-radius: 10px;
    overflow: hidden;
    background-color: #f1f5f9;
    box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.05);
}

.org-photo-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.3s ease;
}

.org-card:hover .org-photo-container img {
    transform: scale(1.03);
}

/* Name Badge (Dark Blue Pill) */
.org-name-badge {
    position: absolute;
    bottom: 8px;
    left: 50%;
    transform: translateX(-50%);
    background-color: #1e3a8a;
    color: #ffffff;
    padding: 4px 10px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
    max-width: 90%;
    overflow: hidden;
    text-overflow: ellipsis;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.25);
    letter-spacing: 0.2px;
    z-index: 2;
}

/* Role Title */
.org-role {
    color: #64748b;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    margin: 10px 0 8px 0;
    letter-spacing: 0.5px;
    line-height: 1.3;
    min-height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    word-break: break-word;
}

/* Edit Button */
.btn-edit-official {
    background-color: #1a4d33;
    color: #ffffff;
    border: none;
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background-color 0.2s ease, transform 0.1s ease;
    width: 100%;
    justify-content: center;
}

.btn-edit-official:hover {
    background-color: #113622;
}

.btn-edit-official:active {
    transform: scale(0.97);
}

/* Captain Card Accent */
.org-card[data-id="captain"] {
    border-top: 3px solid #1a4d33;
}
.org-card[data-id="captain"] .org-name-badge {
    background-color: #0f172a;
    border-color: #f1c40f;
}

/* Modal Styles */
.modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    z-index: 10000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    opacity: 0;
    transition: opacity 0.25s ease;
}

.modal-backdrop.show {
    display: flex;
    opacity: 1;
}

.modal-container {
    background: #ffffff;
    width: 100%;
    max-width: 520px;
    border-radius: 14px;
    box-shadow: 0 20px 45px rgba(0, 0, 0, 0.25);
    overflow: hidden;
    transform: translateY(20px) scale(0.98);
    transition: transform 0.25s cubic-bezier(0.2, 0.8, 0.2, 1);
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}

.modal-backdrop.show .modal-container {
    transform: translateY(0) scale(1);
}

.modal-header {
    padding: 18px 24px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-title-wrap h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 8px;
}

.modal-title-wrap p {
    margin: 2px 0 0 0;
    font-size: 12px;
    color: #64748b;
}

.modal-close-btn {
    background: none;
    border: none;
    font-size: 20px;
    color: #94a3b8;
    cursor: pointer;
    line-height: 1;
    padding: 4px;
    border-radius: 6px;
    transition: color 0.2s ease, background 0.2s ease;
}

.modal-close-btn:hover {
    color: #0f172a;
    background: #e2e8f0;
}

.modal-body {
    padding: 24px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 18px;
}

/* Photo Preview in Modal */
.modal-photo-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}

.photo-preview-wrap {
    width: 120px;
    height: 120px;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
    border: 3px solid #e2e8f0;
    background-color: #f1f5f9;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
}

.photo-preview-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.btn-change-photo {
    background-color: #f1f5f9;
    border: 1px solid #cbd5e1;
    color: #334155;
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s ease;
}

.btn-change-photo:hover {
    background-color: #e2e8f0;
    color: #0f172a;
    border-color: #94a3b8;
}

/* Form Controls */
.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-label {
    font-size: 12px;
    font-weight: 700;
    color: #334155;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.form-control {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 13.5px;
    color: #1e293b;
    background-color: #ffffff;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    box-sizing: border-box;
}

.form-control:focus {
    outline: none;
    border-color: #1a4d33;
    box-shadow: 0 0 0 3px rgba(26, 77, 51, 0.15);
}

textarea.form-control {
    resize: vertical;
    min-height: 90px;
    line-height: 1.5;
}

/* Modal Footer */
.modal-footer {
    padding: 16px 24px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.btn-modal-cancel {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #475569;
    padding: 8px 18px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-modal-cancel:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.btn-modal-save {
    background-color: #1a4d33;
    color: #ffffff;
    border: none;
    padding: 8px 20px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: background-color 0.2s ease;
}

.btn-modal-save:hover {
    background-color: #113622;
}

.btn-modal-save:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

/* Floating Toast Notification */
.admin-toast {
    position: fixed;
    top: 25px;
    right: 25px;
    z-index: 99999;
    background-color: #093d25;
    color: #ffffff;
    padding: 12px 20px;
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 13.5px;
    font-weight: 600;
    transform: translateX(130%);
    transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    border-left: 4px solid #4cd137;
}

.admin-toast.show {
    transform: translateX(0);
}

.admin-toast .toast-icon {
    color: #4cd137;
    font-size: 18px;
    line-height: 1;
}

/* Responsive adjustments */
@media (max-width: 820px) {
    .org-tier {
        gap: 15px;
    }
    .org-card {
        width: 150px;
    }
    .org-photo-container {
        width: 130px;
        height: 130px;
    }
    .org-name-badge {
        font-size: 10px;
        padding: 3px 8px;
    }
}

    </style>
</head>
<body>

    <!-- Sidebar -->
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
                    <a class="dropdown-btn active"><i class="fa-solid fa-user-tie"></i> Barangay Board <i class="fa-solid fa-caret-down" style="margin-left: auto;"></i></a>
                    <div class="dropdown-container" style="display: block;">
                        <a href="barangayofficials.php" class="active">Barangay Official</a>
                        <a href="skofficial.php">SK Official</a>
                    </div>
                </li>
                <li><a href="services.php"><i class="fa-solid fa-bell-concierge"></i> Services</a></li>
                <li><a href="announcements.php"><i class="fa-solid fa-bullhorn"></i> Announcement</a></li>
                <li><a href="feedback.php"><i class="fa-solid fa-comments"></i> Feedback Chat</a></li>
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
        
        <div class="top-bar">
            <h1 class="page-title" style="font-size: 20px;">Manage Barangay Officials</h1>
            <div class="user-profile" style="display: flex; align-items: center; gap: 10px; text-align: right;">
                <div class="user-info">
                    <h4 style="font-size: 14px; font-weight: 700; color: #2b323c; margin: 0;">System Admin</h4>
                    <p style="font-size: 12px; color: #6b7280; margin: 0;">Administrator</p>
                </div>
                <div class="user-avatar" style="width: 40px; height: 40px; background-color: #f1c40f; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #000;">
                    A
                </div>
            </div>
        </div>

        <div class="officials-header-container">
            <h2>Barangay Officials Organizational Hierarchy</h2>
            <p>Update leadership names, roles, executive statements, and portrait photos displayed on public pages.</p>
        </div>

        <!-- 4-Tier Pyramid Layout Org Chart -->
        <div class="org-chart-wrapper">
            
            <!-- Tier 1: Captain -->
            <div class="org-tier tier-1">
                <?php foreach ($tier1_keys as $key): 
                    $off = $officials[$key];
                    $photo_src = get_official_admin_photo($off['photo']);
                ?>
                <div class="org-card" 
                     data-id="<?= htmlspecialchars($key) ?>"
                     data-name="<?= htmlspecialchars($off['name']) ?>"
                     data-role="<?= htmlspecialchars($off['position']) ?>"
                     data-photo="<?= htmlspecialchars($photo_src) ?>"
                     data-message="<?= htmlspecialchars($off['message'] ?? '') ?>">
                    <div class="org-photo-container">
                        <img src="<?= htmlspecialchars($photo_src) ?>?t=<?= time() ?>" alt="<?= htmlspecialchars($off['name']) ?>" onerror="this.src='../Officials/Captain.jpg';">
                        <div class="org-name-badge"><?= htmlspecialchars($off['name']) ?></div>
                    </div>
                    <div class="org-role"><?= htmlspecialchars($off['position']) ?></div>
                    <button type="button" class="btn-edit-official" onclick="openEditOfficialModal('<?= $key ?>')">
                        <i class="bi bi-pencil-square"></i> Edit
                    </button>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Tier 2: Secretary & Treasurer -->
            <div class="org-tier tier-2">
                <?php foreach ($tier2_keys as $key): 
                    $off = $officials[$key];
                    $photo_src = get_official_admin_photo($off['photo']);
                ?>
                <div class="org-card" 
                     data-id="<?= htmlspecialchars($key) ?>"
                     data-name="<?= htmlspecialchars($off['name']) ?>"
                     data-role="<?= htmlspecialchars($off['position']) ?>"
                     data-photo="<?= htmlspecialchars($photo_src) ?>"
                     data-message="<?= htmlspecialchars($off['message'] ?? '') ?>">
                    <div class="org-photo-container">
                        <img src="<?= htmlspecialchars($photo_src) ?>?t=<?= time() ?>" alt="<?= htmlspecialchars($off['name']) ?>" onerror="this.src='../Officials/Captain.jpg';">
                        <div class="org-name-badge"><?= htmlspecialchars($off['name']) ?></div>
                    </div>
                    <div class="org-role"><?= htmlspecialchars($off['position']) ?></div>
                    <button type="button" class="btn-edit-official" onclick="openEditOfficialModal('<?= $key ?>')">
                        <i class="bi bi-pencil-square"></i> Edit
                    </button>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Tier 3: Kagawad 1 to 3 -->
            <div class="org-tier tier-3">
                <?php foreach ($tier3_keys as $key): 
                    $off = $officials[$key];
                    $photo_src = get_official_admin_photo($off['photo']);
                ?>
                <div class="org-card" 
                     data-id="<?= htmlspecialchars($key) ?>"
                     data-name="<?= htmlspecialchars($off['name']) ?>"
                     data-role="<?= htmlspecialchars($off['position']) ?>"
                     data-photo="<?= htmlspecialchars($photo_src) ?>"
                     data-message="<?= htmlspecialchars($off['message'] ?? '') ?>">
                    <div class="org-photo-container">
                        <img src="<?= htmlspecialchars($photo_src) ?>?t=<?= time() ?>" alt="<?= htmlspecialchars($off['name']) ?>" onerror="this.src='../Officials/Captain.jpg';">
                        <div class="org-name-badge"><?= htmlspecialchars($off['name']) ?></div>
                    </div>
                    <div class="org-role"><?= htmlspecialchars($off['position']) ?></div>
                    <button type="button" class="btn-edit-official" onclick="openEditOfficialModal('<?= $key ?>')">
                        <i class="bi bi-pencil-square"></i> Edit
                    </button>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Tier 4: Kagawad 4 to 7 -->
            <div class="org-tier tier-4">
                <?php foreach ($tier4_keys as $key): 
                    $off = $officials[$key];
                    $photo_src = get_official_admin_photo($off['photo']);
                ?>
                <div class="org-card" 
                     data-id="<?= htmlspecialchars($key) ?>"
                     data-name="<?= htmlspecialchars($off['name']) ?>"
                     data-role="<?= htmlspecialchars($off['position']) ?>"
                     data-photo="<?= htmlspecialchars($photo_src) ?>"
                     data-message="<?= htmlspecialchars($off['message'] ?? '') ?>">
                    <div class="org-photo-container">
                        <img src="<?= htmlspecialchars($photo_src) ?>?t=<?= time() ?>" alt="<?= htmlspecialchars($off['name']) ?>" onerror="this.src='../Officials/Captain.jpg';">
                        <div class="org-name-badge"><?= htmlspecialchars($off['name']) ?></div>
                    </div>
                    <div class="org-role"><?= htmlspecialchars($off['position']) ?></div>
                    <button type="button" class="btn-edit-official" onclick="openEditOfficialModal('<?= $key ?>')">
                        <i class="bi bi-pencil-square"></i> Edit
                    </button>
                </div>
                <?php endforeach; ?>
            </div>

        </div>

    </main>

    <!-- Interactive Edit Official Modal -->
    <div class="modal-backdrop" id="editOfficialModal">
        <div class="modal-container">
            <div class="modal-header">
                <div class="modal-title-wrap">
                    <h3><i class="bi bi-person-gear"></i> Edit Barangay Official</h3>
                    <p>Modify name, position title, photo, or statement</p>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeEditOfficialModal()" title="Close">&times;</button>
            </div>
            
            <form id="editOfficialForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_official">
                <input type="hidden" name="official_id" id="modalOfficialId">

                <div class="modal-body">
                    <!-- Photo Preview & Upload -->
                    <div class="modal-photo-section">
                        <div class="photo-preview-wrap">
                            <img id="modalPhotoPreview" src="../Officials/Captain.jpg" alt="Official Photo" onerror="this.src='../Officials/Captain.jpg';">
                        </div>
                        <input type="file" name="official_photo" id="modalPhotoInput" accept="image/png, image/jpeg, image/jpg, image/webp, image/gif" style="display: none;">
                        <button type="button" class="btn-change-photo" onclick="document.getElementById('modalPhotoInput').click()">
                            <i class="bi bi-camera"></i> Change Photo
                        </button>
                    </div>

                    <!-- Full Name -->
                    <div class="form-group">
                        <label class="form-label" for="modalOfficialName"><i class="bi bi-person-badge"></i> Official Full Name</label>
                        <input type="text" class="form-control" name="official_name" id="modalOfficialName" required placeholder="e.g. Hon. Reynaldo D. Santos">
                    </div>

                    <!-- Role / Position -->
                    <div class="form-group">
                        <label class="form-label" for="modalOfficialRole"><i class="bi bi-award"></i> Role / Position</label>
                        <input type="text" class="form-control" name="official_role" id="modalOfficialRole" required placeholder="e.g. Barangay Captain / Barangay Kagawad">
                    </div>

                    <!-- Barangay Captain's Message (Conditionally Displayed) -->
                    <div class="form-group" id="captainMessageGroup" style="display: none;">
                        <label class="form-label" for="modalCaptainMessage"><i class="bi bi-quote"></i> Barangay Captain's Message</label>
                        <textarea class="form-control" name="official_message" id="modalCaptainMessage" rows="4" placeholder="Enter executive message or community address..."></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" onclick="closeEditOfficialModal()">Cancel</button>
                    <button type="submit" class="btn-modal-save" id="modalSaveBtn">
                        <i class="bi bi-check2-circle"></i> <span id="modalSaveText">Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Floating Toast Notification -->
    <div class="admin-toast" id="adminToast">
        <i class="bi bi-check-circle-fill toast-icon"></i>
        <span id="toastMessage">Official details updated successfully!</span>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const modal = document.getElementById("editOfficialModal");
        const form = document.getElementById("editOfficialForm");
        const modalId = document.getElementById("modalOfficialId");
        const modalName = document.getElementById("modalOfficialName");
        const modalRole = document.getElementById("modalOfficialRole");
        const modalPhoto = document.getElementById("modalPhotoPreview");
        const modalInput = document.getElementById("modalPhotoInput");
        const captainGroup = document.getElementById("captainMessageGroup");
        const modalMsg = document.getElementById("modalCaptainMessage");
        const saveBtn = document.getElementById("modalSaveBtn");
        const saveText = document.getElementById("modalSaveText");
        const toast = document.getElementById("adminToast");
        const toastMsg = document.getElementById("toastMessage");

        let toastTimeout = null;

        // Open Modal Function
        window.openEditOfficialModal = function(id) {
            const card = document.querySelector(`.org-card[data-id="${id}"]`);
            if (!card) return;

            modalId.value = id;
            modalName.value = card.dataset.name || '';
            modalRole.value = card.dataset.role || '';
            modalPhoto.src = card.dataset.photo || '../Officials/Captain.jpg';
            modalInput.value = '';

            if (id === 'captain') {
                captainGroup.style.display = 'flex';
                modalMsg.value = card.dataset.message || '';
            } else {
                captainGroup.style.display = 'none';
                modalMsg.value = '';
            }

            modal.classList.add("show");
            modalName.focus();
        };

        // Close Modal Function
        window.closeEditOfficialModal = function() {
            modal.classList.remove("show");
        };

        // Close on backdrop click
        modal.addEventListener("click", function(e) {
            if (e.target === modal) {
                closeEditOfficialModal();
            }
        });

        // Close on Escape key
        document.addEventListener("keydown", function(e) {
            if (e.key === "Escape" && modal.classList.contains("show")) {
                closeEditOfficialModal();
            }
        });

        // Instant Photo Preview via FileReader
        modalInput.addEventListener("change", function() {
            const file = this.files[0];
            if (file) {
                if (!file.type.match('image.*')) {
                    alert('Please select a valid image file (JPG, PNG, WEBP).');
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    modalPhoto.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // Toast Trigger Helper
        window.showToast = function(msg) {
            toastMsg.textContent = msg || 'Official details updated successfully!';
            toast.classList.add("show");
            if (toastTimeout) clearTimeout(toastTimeout);
            toastTimeout = setTimeout(() => {
                toast.classList.remove("show");
            }, 3500);
        };

        // Check if query params have msg=updated
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('msg') === 'updated') {
            showToast('Official details updated successfully!');
            // Clean URL without refresh
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        // Asynchronous Form Submission
        form.addEventListener("submit", function(e) {
            e.preventDefault();

            saveBtn.disabled = true;
            saveText.textContent = "Saving...";
            const origIcon = saveBtn.querySelector("i").className;
            saveBtn.querySelector("i").className = "bi bi-arrow-repeat spin-icon";

            const formData = new FormData(form);
            formData.append("ajax", "1");

            fetch("barangayofficials.php", {
                method: "POST",
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(res => {
                if (!res.ok) throw new Error('HTTP error ' + res.status);
                return res.json();
            })
            .then(data => {
                saveBtn.disabled = false;
                saveText.textContent = "Save Changes";
                saveBtn.querySelector("i").className = origIcon;

                if (data.success) {
                    const off = data.official;
                    const card = document.querySelector(`.org-card[data-id="${off.id}"]`);
                    if (card) {
                        card.dataset.name = off.name;
                        card.dataset.role = off.role;
                        if (off.message !== undefined && off.message !== null) {
                            card.dataset.message = off.message;
                        }
                        if (off.photo_url) {
                            card.dataset.photo = off.photo_url;
                            const img = card.querySelector(".org-photo-container img");
                            if (img) {
                                img.src = off.photo_url + '?t=' + new Date().getTime();
                            }
                        }
                        const nameBadge = card.querySelector(".org-name-badge");
                        if (nameBadge) nameBadge.textContent = off.name;
                        const roleTitle = card.querySelector(".org-role");
                        if (roleTitle) roleTitle.textContent = off.role;
                    }

                    closeEditOfficialModal();
                    showToast(data.message || 'Official details updated successfully!');
                } else {
                    alert(data.message || 'Error saving official details.');
                }
            })
            .catch(err => {
                console.warn("AJAX save failed, falling back to standard submit:", err);
                form.submit();
            });
        });
    });
    </script>

</body>
</html>
