<?php
// Admin/Legend.php - Interactive Spot Map Legends & Aerial Marker Management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../db_connect.php';

// Helper for upload directories
$landmarks_dir = __DIR__ . '/uploads/landmarks/';
$legends_dir   = __DIR__ . '/uploads/legends/';
if (!is_dir($landmarks_dir)) mkdir($landmarks_dir, 0777, true);
if (!is_dir($legends_dir))   mkdir($legends_dir, 0777, true);

$json_cache_file = __DIR__ . '/data/legend.json';
$json_dir = dirname($json_cache_file);
if (!is_dir($json_dir)) mkdir($json_dir, 0777, true);

// --- 1. Database Architecture & Auto-Migration ---
$conn->query("CREATE TABLE IF NOT EXISTS `sm_legends` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(150) NOT NULL,
    `icon_path` varchar(255) NOT NULL,
    `color_hex` varchar(20) DEFAULT '#6366f1',
    `is_active` tinyint(1) DEFAULT 1,
    `display_order` int(11) DEFAULT 0,
    `top_position` decimal(6,2) DEFAULT 50.00,
    `left_position` decimal(6,2) DEFAULT 50.00,
    `marker_width` int(11) DEFAULT 40,
    `marker_height` int(11) DEFAULT 40,
    `image_path` varchar(255) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

// Cross-Table Compatibility: sm_legend_spots
$conn->query("CREATE TABLE IF NOT EXISTS `sm_legend_spots` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `legend_id` int(11) NOT NULL,
    `title` varchar(150) NOT NULL,
    `top_position` decimal(6,2) DEFAULT 50.00,
    `left_position` decimal(6,2) DEFAULT 50.00,
    `marker_width` int(11) DEFAULT 40,
    `marker_height` int(11) DEFAULT 40,
    `marker_image` varchar(255) DEFAULT NULL,
    `house_image` varchar(255) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_legend_id` (`legend_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

// Function: JSON Synchronization & Cross-Table Sync
function syncLegendJson($conn, $json_file_path) {
    try {
        $res = $conn->query("SELECT * FROM `sm_legends` WHERE `is_active` = 1 ORDER BY `display_order` ASC, `id` ASC");
        $export_data = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                // Compatible fields for spotmap & legacy Legend Files
                $export_row = [
                    'id'            => (int)$row['id'],
                    'name'          => $row['name'],
                    'icon_path'     => $row['icon_path'],
                    'color_hex'     => $row['color_hex'],
                    'is_active'     => (int)$row['is_active'],
                    'display_order' => (int)$row['display_order'],
                    'top_position'  => (float)$row['top_position'],
                    'left_position' => (float)$row['left_position'],
                    'marker_width'  => (int)$row['marker_width'],
                    'marker_height' => (int)$row['marker_height'],
                    'image_path'    => $row['image_path'],
                    // Legacy household-like keys for backward compatibility
                    'purok'         => 'Legend',
                    'houseNumber'   => $row['name'],
                    'markerImage'   => $row['icon_path'],
                    'topPos'        => (float)$row['top_position'],
                    'leftPos'       => (float)$row['left_position'],
                    'markerWidth'   => (int)$row['marker_width'],
                    'markerHeight'  => (int)$row['marker_height'],
                    'dateAdded'     => substr($row['created_at'], 0, 10)
                ];
                $export_data[] = $export_row;

                // Synchronize with sm_legend_spots
                $check = $conn->prepare("SELECT `id` FROM `sm_legend_spots` WHERE `legend_id` = ?");
                $leg_id = (int)$row['id'];
                $check->bind_param("i", $leg_id);
                $check->execute();
                $c_res = $check->get_result();
                if ($c_res && $c_res->num_rows > 0) {
                    $up = $conn->prepare("UPDATE `sm_legend_spots` SET `title` = ?, `top_position` = ?, `left_position` = ?, `marker_width` = ?, `marker_height` = ?, `marker_image` = ?, `house_image` = ? WHERE `legend_id` = ?");
                    $up->bind_param("sddiissi", $row['name'], $row['top_position'], $row['left_position'], $row['marker_width'], $row['marker_height'], $row['icon_path'], $row['image_path'], $leg_id);
                    $up->execute();
                } else {
                    $ins = $conn->prepare("INSERT INTO `sm_legend_spots` (`legend_id`, `title`, `top_position`, `left_position`, `marker_width`, `marker_height`, `marker_image`, `house_image`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $ins->bind_param("isddiiss", $leg_id, $row['name'], $row['top_position'], $row['left_position'], $row['marker_width'], $row['marker_height'], $row['icon_path'], $row['image_path']);
                    $ins->execute();
                }
            }
        }
        $conn->query("DELETE FROM `sm_legend_spots` WHERE `legend_id` NOT IN (SELECT `id` FROM `sm_legends`)");
        file_put_contents($json_file_path, json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    } catch (Exception $e) {
        // Fail silently
    }
}

// Auto-seed default landmarks ONLY once on initial system setup (never re-seed after manual deletion)
$conn->query("CREATE TABLE IF NOT EXISTS `sm_settings` (
    `setting_key` varchar(50) NOT NULL PRIMARY KEY,
    `setting_value` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$seed_check = $conn->query("SELECT `setting_value` FROM `sm_settings` WHERE `setting_key` = 'legends_seeded'");
if (!$seed_check || $seed_check->num_rows == 0) {
    $count_check = $conn->query("SELECT COUNT(*) AS total FROM `sm_legends`");
    $row_cnt = $count_check ? $count_check->fetch_assoc()['total'] : 0;
    if ($row_cnt == 0) {
        $defaults = [
            ['Barangay Tabon Hall', 'bi-building-fill', '#4f46e5', 48.20, 51.50, 40, 40, 1, 'Images/Tabon Barangay hall.jpg'],
            ['Tabon Elementary School', 'bi-book-half', '#10b981', 38.60, 42.10, 40, 40, 2, null],
            ['Barangay Gymnasium / Court', 'bi-dribbble', '#f59e0b', 54.10, 58.40, 40, 40, 3, null],
            ['Barangay Health Center', 'bi-heart-pulse-fill', '#ef4444', 46.80, 47.90, 40, 40, 4, null],
            ['Community Chapel', 'bi-bell-fill', '#8b5cf6', 61.30, 35.70, 40, 40, 5, null],
            ['Barangay Security Outpost', 'bi-shield-check', '#0284c7', 32.50, 59.80, 40, 40, 6, null]
        ];
        $stmt_seed = $conn->prepare("INSERT INTO `sm_legends` (`name`, `icon_path`, `color_hex`, `top_position`, `left_position`, `marker_width`, `marker_height`, `display_order`, `image_path`, `is_active`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        foreach ($defaults as $d) {
            $stmt_seed->bind_param("sssddiiss", $d[0], $d[1], $d[2], $d[3], $d[4], $d[5], $d[6], $d[7], $d[8]);
            $stmt_seed->execute();
        }
    }
    $conn->query("INSERT INTO `sm_settings` (`setting_key`, `setting_value`) VALUES ('legends_seeded', '1') ON DUPLICATE KEY UPDATE `setting_value` = '1'");
    syncLegendJson($conn, $json_cache_file);
}

// --- 2. Request Handling (POST / AJAX) ---
$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_POST['is_ajax']) && $_POST['is_ajax'] == '1');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    // Action: Add Legend
    if ($action === 'add_legend') {
        $name          = trim($_POST['name'] ?? '');
        $color_hex     = trim($_POST['color_hex'] ?? '#6366f1');
        $top_position  = floatval($_POST['top_position'] ?? 50.00);
        $left_position = floatval($_POST['left_position'] ?? 50.00);
        $marker_width  = intval($_POST['marker_width'] ?? 40);
        $marker_height = intval($_POST['marker_height'] ?? 40);
        $display_order = intval($_POST['display_order'] ?? 0);
        $is_active     = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;
        $icon_type     = $_POST['icon_type'] ?? 'preset';
        $icon_path     = trim($_POST['preset_icon'] ?? 'bi-building-fill');

        if (empty($name)) {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Landmark name is required.']);
                exit();
            }
            header("Location: Legend.php?error=name_required");
            exit();
        }

        // Handle Custom Marker Icon Upload
        if ($icon_type === 'upload' && isset($_FILES['custom_icon']) && $_FILES['custom_icon']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['custom_icon']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'webp', 'gif'])) {
                $icon_filename = 'icon_' . time() . '_' . uniqid() . '.' . $ext;
                $target_icon = $legends_dir . $icon_filename;
                if (move_uploaded_file($_FILES['custom_icon']['tmp_name'], $target_icon)) {
                    $icon_path = 'uploads/legends/' . $icon_filename;
                }
            }
        }

        // Handle Landmark Building Photo Upload
        $image_path = null;
        if (isset($_FILES['landmark_image']) && $_FILES['landmark_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['landmark_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                $photo_filename = 'landmark_' . time() . '_' . uniqid() . '.' . $ext;
                $target_photo = $landmarks_dir . $photo_filename;
                if (move_uploaded_file($_FILES['landmark_image']['tmp_name'], $target_photo)) {
                    $image_path = 'uploads/landmarks/' . $photo_filename;
                }
            }
        }

        $stmt = $conn->prepare("INSERT INTO `sm_legends` (`name`, `icon_path`, `color_hex`, `is_active`, `display_order`, `top_position`, `left_position`, `marker_width`, `marker_height`, `image_path`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiiddiis", $name, $icon_path, $color_hex, $is_active, $display_order, $top_position, $left_position, $marker_width, $marker_height, $image_path);
        $stmt->execute();
        $inserted_id = $stmt->insert_id;

        syncLegendJson($conn, $json_cache_file);

        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'status'  => 'success',
                'message' => 'Legend landmark saved successfully!',
                'id'      => $inserted_id
            ]);
            exit();
        }
        header("Location: Legend.php?msg=added");
        exit();
    }

    // Action: Edit Legend
    if ($action === 'edit_legend') {
        $id            = intval($_POST['legend_id'] ?? 0);
        $name          = trim($_POST['name'] ?? '');
        $color_hex     = trim($_POST['color_hex'] ?? '#6366f1');
        $top_position  = floatval($_POST['top_position'] ?? 50.00);
        $left_position = floatval($_POST['left_position'] ?? 50.00);
        $marker_width  = intval($_POST['marker_width'] ?? 40);
        $marker_height = intval($_POST['marker_height'] ?? 40);
        $display_order = intval($_POST['display_order'] ?? 0);
        $is_active     = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;
        $icon_type     = $_POST['icon_type'] ?? 'preset';
        $icon_path     = trim($_POST['preset_icon'] ?? 'bi-building-fill');

        if ($id <= 0 || empty($name)) {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Invalid ID or landmark name.']);
                exit();
            }
            header("Location: Legend.php?error=invalid_data");
            exit();
        }

        // Fetch current record
        $curr_stmt = $conn->prepare("SELECT * FROM `sm_legends` WHERE `id` = ?");
        $curr_stmt->bind_param("i", $id);
        $curr_stmt->execute();
        $curr = $curr_stmt->get_result()->fetch_assoc();
        if (!$curr) {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Legend not found.']);
                exit();
            }
            header("Location: Legend.php?error=not_found");
            exit();
        }

        // Handle icon
        if ($icon_type === 'upload' && isset($_FILES['custom_icon']) && $_FILES['custom_icon']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['custom_icon']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'webp', 'gif'])) {
                $icon_filename = 'icon_' . time() . '_' . uniqid() . '.' . $ext;
                $target_icon = $legends_dir . $icon_filename;
                if (move_uploaded_file($_FILES['custom_icon']['tmp_name'], $target_icon)) {
                    $icon_path = 'uploads/legends/' . $icon_filename;
                }
            }
        } elseif ($icon_type === 'retain') {
            $icon_path = $curr['icon_path'];
        }

        // Handle photo
        $image_path = $curr['image_path'];
        if (isset($_FILES['landmark_image']) && $_FILES['landmark_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['landmark_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                $photo_filename = 'landmark_' . time() . '_' . uniqid() . '.' . $ext;
                $target_photo = $landmarks_dir . $photo_filename;
                if (move_uploaded_file($_FILES['landmark_image']['tmp_name'], $target_photo)) {
                    $image_path = 'uploads/landmarks/' . $photo_filename;
                }
            }
        } elseif (!empty($_POST['remove_landmark_image']) && $_POST['remove_landmark_image'] == '1') {
            $image_path = null;
        }

        $up_stmt = $conn->prepare("UPDATE `sm_legends` SET `name` = ?, `icon_path` = ?, `color_hex` = ?, `is_active` = ?, `display_order` = ?, `top_position` = ?, `left_position` = ?, `marker_width` = ?, `marker_height` = ?, `image_path` = ? WHERE `id` = ?");
        $up_stmt->bind_param("sssiiddiisi", $name, $icon_path, $color_hex, $is_active, $display_order, $top_position, $left_position, $marker_width, $marker_height, $image_path, $id);
        $up_stmt->execute();

        syncLegendJson($conn, $json_cache_file);

        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'status'  => 'success',
                'message' => 'Legend updated successfully!',
                'id'      => $id
            ]);
            exit();
        }
        header("Location: Legend.php?msg=updated");
        exit();
    }

    // Action: Toggle Status
    if ($action === 'toggle_status') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $conn->query("UPDATE `sm_legends` SET `is_active` = IF(`is_active` = 1, 0, 1) WHERE `id` = $id");
            syncLegendJson($conn, $json_cache_file);
        }
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Visibility updated.']);
            exit();
        }
        header("Location: Legend.php?msg=status_updated");
        exit();
    }

            // Action: Delete Legend
    if ($action === 'delete_legend') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            // Delete associated custom uploaded files
            $fetch_row = $conn->query("SELECT `icon_path`, `image_path` FROM `sm_legends` WHERE `id` = $id");
            if ($fetch_row && $row_data = $fetch_row->fetch_assoc()) {
                if (!empty($row_data['image_path']) && strpos($row_data['image_path'], 'uploads/landmarks/') === 0) {
                    $img_full = __DIR__ . '/' . $row_data['image_path'];
                    if (file_exists($img_full)) @unlink($img_full);
                }
                if (!empty($row_data['icon_path']) && strpos($row_data['icon_path'], 'uploads/legends/') === 0) {
                    $ico_full = __DIR__ . '/' . $row_data['icon_path'];
                    if (file_exists($ico_full)) @unlink($ico_full);
                }
            }
            $conn->query("DELETE FROM `sm_legends` WHERE `id` = $id");
            $conn->query("DELETE FROM `sm_legend_spots` WHERE `legend_id` = $id");
            syncLegendJson($conn, $json_cache_file);
        }
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success', 
                'message' => 'Landmark completely deleted from map and system.',
                'id' => $id
            ]);
            exit();
        }
        header("Location: Legend.php?msg=deleted");
        exit();
    }
}


// Fetch all legends for map & table display
$legends_res = $conn->query("SELECT * FROM `sm_legends` ORDER BY `display_order` ASC, `id` ASC");
$all_legends = [];
$active_count = 0;
$hidden_count = 0;
if ($legends_res) {
    while ($row = $legends_res->fetch_assoc()) {
        $all_legends[] = $row;
        if ($row['is_active'] == 1) $active_count++;
        else $hidden_count++;
    }
}

// Helper to resolve landmark display photo URL
function get_legend_photo_url($path) {
    if (empty($path)) return '';
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) return $path;
    if (strpos($path, 'uploads/') === 0) return $path;
    return '../' . ltrim($path, '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spot Map Legends & Aerial Markers - Brgy Tabon</title>
    
    <!-- Bootstrap Icons v1.11.3 CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- FontAwesome CSS CDN for Sidebar -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin Common & Dedicated Legend Stylesheets -->
    <link rel="stylesheet" href="Admin CSS/admin_common.css?v=<?= time() ?>">
    <link rel="stylesheet" href="Admin CSS/legend.css?v=<?= time() ?>">
    <style>
/* Admin/Admin CSS/legend.css - Interactive Spot Map Legends & Aerial Marker Management */

:root {
    --primary-indigo: #4f46e5;
    --primary-indigo-hover: #4338ca;
    --primary-emerald: #10b981;
    --primary-emerald-hover: #059669;
    --primary-danger: #ef4444;
    --primary-amber: #f59e0b;
    --dark-bg: #0f172a;
    --card-bg: #ffffff;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --border-color: #e2e8f0;
    --border-subtle: #f1f5f9;
    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 18px;
    --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 15px rgba(0, 0, 0, 0.08);
    --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.12);
    --shadow-glow: 0 0 20px rgba(79, 70, 229, 0.35);
}

/* =========================================================
   Layout & Container Headers
   ========================================================= */
.legend-management-wrapper {
    display: flex;
    flex-direction: column;
    gap: 28px;
    padding-bottom: 50px;
}

.legend-page-header {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: #ffffff;
    padding: 24px 30px;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    position: relative;
    overflow: hidden;
}

.legend-page-header::before {
    content: '';
    position: absolute;
    top: -50px;
    right: -50px;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(99, 102, 241, 0.25) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

.legend-header-title h2 {
    margin: 0 0 6px 0;
    font-size: 22px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
    letter-spacing: -0.02em;
}

.legend-header-title p {
    margin: 0;
    font-size: 13.5px;
    color: #94a3b8;
    line-height: 1.5;
}

.legend-badge {
    background: rgba(99, 102, 241, 0.2);
    color: #a5b4fc;
    border: 1px solid rgba(165, 180, 252, 0.3);
    font-size: 11px;
    padding: 3px 10px;
    border-radius: 20px;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.legend-header-stats {
    display: flex;
    align-items: center;
    gap: 14px;
}

.stat-pill {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.12);
    padding: 8px 16px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #e2e8f0;
}

.stat-pill strong {
    color: #38bdf8;
    font-size: 16px;
}

/* =========================================================
   Interactive Aerial Map Card (#aerialMapContainer)
   ========================================================= */
.aerial-map-card {
    background: var(--card-bg);
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-md);
    overflow: hidden;
}

.map-card-toolbar {
    padding: 16px 24px;
    border-bottom: 1px solid var(--border-color);
    background: #f8fafc;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
}

.map-toolbar-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.map-toolbar-title {
    font-weight: 700;
    color: var(--text-primary);
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.map-coordinate-live {
    font-family: monospace;
    font-size: 12.5px;
    background: #e0e7ff;
    color: #4338ca;
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 600;
}

.map-toolbar-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn-map-action {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #475569;
    padding: 6px 14px;
    border-radius: var(--radius-sm);
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s ease;
}

.btn-map-action:hover {
    background: #f1f5f9;
    color: #1e293b;
    border-color: #94a3b8;
}

.map-viewport-wrapper {
    position: relative;
    width: 100%;
    height: 520px;
    background: #020617;
    overflow: hidden;
    user-select: none;
    cursor: crosshair;
}

.map-viewport-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    display: block;
    pointer-events: none;
    transition: transform 0.3s ease;
}

.map-hint-banner {
    position: absolute;
    top: 14px;
    left: 14px;
    background: rgba(15, 23, 42, 0.85);
    backdrop-filter: blur(8px);
    color: #f1f5f9;
    font-size: 12px;
    padding: 6px 14px;
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    display: flex;
    align-items: center;
    gap: 8px;
    pointer-events: none;
    z-index: 10;
}

.map-hint-banner i {
    color: #38bdf8;
    animation: bounceRight 1.5s infinite alternate;
}

@keyframes bounceRight {
    from { transform: translateX(0); }
    to { transform: translateX(4px); }
}

/* =========================================================
   Markers on Aerial Map
   ========================================================= */

/* Base Pin Structure */
.saved-legend-marker {
    position: absolute;
    transform: translate(-50%, -100%);
    cursor: pointer;
    z-index: 20;
    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), z-index 0.1s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.saved-legend-marker:hover {
    transform: translate(-50%, -100%) scale(1.22);
    z-index: 50;
}

.saved-marker-pin {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 16px;
    border: 2.5px solid #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35);
    position: relative;
    background-color: #6366f1;
    overflow: hidden;
}

.saved-marker-pin img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.saved-marker-pointer {
    width: 0;
    height: 0;
    border-left: 6px solid transparent;
    border-right: 6px solid transparent;
    border-top: 8px solid #6366f1;
    margin-top: -1px;
}

/* Marker Tooltip */
.saved-marker-tooltip {
    position: absolute;
    bottom: calc(100% + 6px);
    left: 50%;
    transform: translateX(-50%) translateY(4px);
    background: rgba(15, 23, 42, 0.92);
    backdrop-filter: blur(8px);
    color: #ffffff;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 600;
    white-space: nowrap;
    pointer-events: none;
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.25);
    border: 1px solid rgba(255, 255, 255, 0.15);
    display: flex;
    align-items: center;
    gap: 6px;
}

.saved-marker-tooltip::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border-width: 4px;
    border-style: solid;
    border-color: rgba(15, 23, 42, 0.92) transparent transparent transparent;
}

.saved-legend-marker:hover .saved-marker-tooltip {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(0);
}

.saved-marker-tooltip .photo-pill {
    background: rgba(56, 189, 248, 0.25);
    color: #38bdf8;
    padding: 1px 5px;
    border-radius: 4px;
    font-size: 10px;
}

/* Marker Highlighting animation */
.saved-legend-marker.highlight-ping {
    animation: markerHighlight 2s ease forwards;
    z-index: 100 !important;
}

@keyframes markerHighlight {
    0%, 100% {
        transform: translate(-50%, -100%) scale(1);
    }
    30% {
        transform: translate(-50%, -100%) scale(1.6);
        filter: drop-shadow(0 0 25px #f59e0b);
    }
    60% {
        transform: translate(-50%, -100%) scale(1.4);
        filter: drop-shadow(0 0 20px #f59e0b);
    }
}

/* =========================================================
   Active Draggable Pin (#activeDraggableMarker)
   ========================================================= */
.active-draggable-marker {
    position: absolute;
    transform: translate(-50%, -100%);
    cursor: grab;
    z-index: 40;
    display: flex;
    flex-direction: column;
    align-items: center;
    user-select: none;
    touch-action: none;
}

.active-draggable-marker:active {
    cursor: grabbing;
}

.active-draggable-pin {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 20px;
    border: 3px solid #ffffff;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.45);
    position: relative;
    background-color: #6366f1;
    z-index: 2;
    overflow: hidden;
}

.active-draggable-pin img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.active-draggable-pointer {
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 10px solid #6366f1;
    margin-top: -1px;
    z-index: 2;
}

/* Pulsing animated ring */
.draggable-pulse-ring {
    position: absolute;
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: rgba(99, 102, 241, 0.35);
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    animation: markerPulse 2s infinite ease-out;
    pointer-events: none;
    z-index: 1;
}

@keyframes markerPulse {
    0% {
        transform: translate(-50%, -50%) scale(0.6);
        opacity: 0.9;
    }
    100% {
        transform: translate(-50%, -50%) scale(1.5);
        opacity: 0;
    }
}

.active-marker-badge {
    position: absolute;
    bottom: calc(100% + 8px);
    background: #1e293b;
    color: #ffffff;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
    white-space: nowrap;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    gap: 4px;
}

.active-marker-badge i {
    color: #f59e0b;
}

/* =========================================================
   3-Column Horizontal Add Legend Form (#addLegendForm)
   ========================================================= */
.legend-form-card {
    background: var(--card-bg);
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-md);
    overflow: hidden;
}

.form-card-header {
    padding: 18px 24px;
    border-bottom: 1px solid var(--border-color);
    background: #f8fafc;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.form-card-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-card-header p {
    margin: 0;
    font-size: 12.5px;
    color: var(--text-secondary);
}

.form-three-columns {
    display: grid;
    grid-template-columns: 1fr 1.15fr 1fr;
    gap: 24px;
    padding: 24px;
}

.form-col {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.form-col-title {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--text-primary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--border-subtle);
    padding-bottom: 6px;
    margin: 0 0 2px 0;
    display: flex;
    align-items: center;
    gap: 6px;
}

.form-col-title i {
    color: var(--primary-indigo);
}

.form-field-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-field-group label {
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.form-field-group label .required-asterisk {
    color: var(--primary-danger);
}

.form-input {
    width: 100%;
    padding: 9px 13px;
    border: 1.5px solid var(--border-color);
    border-radius: var(--radius-sm);
    font-size: 13.5px;
    color: var(--text-primary);
    background: #ffffff;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    box-sizing: border-box;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary-indigo);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
}

/* Color Picker Container */
.color-picker-wrapper {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.color-input-combo {
    display: flex;
    align-items: center;
    gap: 10px;
}

.color-native-input {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    width: 44px;
    height: 38px;
    border: 1.5px solid var(--border-color);
    border-radius: var(--radius-sm);
    padding: 0;
    cursor: pointer;
    background: none;
}

.color-native-input::-webkit-color-swatch-wrapper {
    padding: 3px;
}

.color-native-input::-webkit-color-swatch {
    border: none;
    border-radius: 4px;
}

.color-hex-input {
    flex: 1;
    font-family: monospace;
    font-weight: 600;
    text-transform: uppercase;
}

/* Quick Palette Chips */
.quick-palette-container {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.palette-chip {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    cursor: pointer;
    border: 2px solid #ffffff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    position: relative;
}

.palette-chip:hover {
    transform: scale(1.2);
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.25);
    z-index: 2;
}

.palette-chip.active {
    outline: 2px solid #1e293b;
    outline-offset: 1.5px;
    transform: scale(1.15);
}

/* Landmark Photo Upload Box */
.photo-upload-area {
    border: 1.5px dashed #cbd5e1;
    border-radius: var(--radius-sm);
    padding: 12px;
    background: #f8fafc;
    text-align: center;
    position: relative;
    cursor: pointer;
    transition: border-color 0.2s ease, background 0.2s ease;
}

.photo-upload-area:hover {
    border-color: var(--primary-indigo);
    background: #f1f5f9;
}

.photo-upload-hint {
    font-size: 12px;
    color: var(--text-secondary);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
}

.photo-upload-hint i {
    font-size: 24px;
    color: #94a3b8;
}

.photo-preview-wrap {
    position: relative;
    width: 100%;
    height: 120px;
    border-radius: 6px;
    overflow: hidden;
    margin-top: 8px;
    display: none;
}

.photo-preview-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.btn-remove-photo {
    position: absolute;
    top: 6px;
    right: 6px;
    background: rgba(15, 23, 42, 0.75);
    color: #ffffff;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 11px;
}

/* =========================================================
   Column 2: Icon Tabs & 16-Preset Grid
   ========================================================= */
.icon-selection-box {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.icon-tabs-nav {
    display: flex;
    background: #f1f5f9;
    padding: 3px;
    border-radius: var(--radius-sm);
    gap: 4px;
}

.icon-tab-btn {
    flex: 1;
    padding: 6px 12px;
    background: transparent;
    border: none;
    border-radius: 6px;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--text-secondary);
    cursor: pointer;
    transition: all 0.2s ease;
}

.icon-tab-btn.active {
    background: #ffffff;
    color: var(--primary-indigo);
    box-shadow: var(--shadow-sm);
}

.preset-icons-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    max-height: 240px;
    overflow-y: auto;
    padding-right: 4px;
}

.preset-icon-chip {
    background: #f8fafc;
    border: 1.5px solid var(--border-color);
    border-radius: var(--radius-sm);
    padding: 8px 4px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    transition: all 0.15s ease;
    text-align: center;
}

.preset-icon-chip i {
    font-size: 18px;
    color: #475569;
    transition: color 0.15s ease;
}

.preset-icon-chip span {
    font-size: 10px;
    font-weight: 600;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

.preset-icon-chip:hover {
    background: #eef2ff;
    border-color: #a5b4fc;
}

.preset-icon-chip.selected {
    background: #e0e7ff;
    border-color: var(--primary-indigo);
    box-shadow: 0 0 0 1px var(--primary-indigo);
}

.preset-icon-chip.selected i {
    color: var(--primary-indigo);
}

.preset-icon-chip.selected span {
    color: #312e81;
    font-weight: 700;
}

/* Upload Tab Content */
.upload-icon-tab-content {
    display: none;
    flex-direction: column;
    gap: 10px;
}

/* Live Preview Card */
.live-preview-box {
    background: #f8fafc;
    border: 1.5px dashed #cbd5e1;
    border-radius: var(--radius-md);
    padding: 14px;
    display: flex;
    align-items: center;
    gap: 14px;
    margin-top: 4px;
}

.preview-marker-badge {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 20px;
    border: 2px solid #ffffff;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
    flex-shrink: 0;
    overflow: hidden;
}

.preview-marker-badge img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.preview-legend-info {
    flex: 1;
    overflow: hidden;
}

.preview-legend-name {
    font-weight: 700;
    font-size: 14px;
    color: var(--text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.preview-legend-meta {
    font-size: 11.5px;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 2px;
}

.preview-photo-badge {
    font-size: 10.5px;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 4px;
}

.preview-photo-badge.has-photo {
    background: #dcfce7;
    color: #15803d;
}

.preview-photo-badge.no-photo {
    background: #f1f5f9;
    color: #64748b;
}

/* =========================================================
   Column 3: Coordinate Inputs & Submit Button
   ========================================================= */
.form-row-dual {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.btn-save-legend {
    background: linear-gradient(135deg, var(--primary-emerald) 0%, var(--primary-emerald-hover) 100%);
    color: #ffffff;
    border: none;
    border-radius: var(--radius-sm);
    padding: 13px 20px;
    font-size: 14.5px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    transition: all 0.2s ease;
    margin-top: auto;
}

.btn-save-legend:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
}

.btn-save-legend:active {
    transform: translateY(0);
}

/* =========================================================
   Existing Legends Table (.legends-table)
   ========================================================= */
.legends-table-card {
    background: var(--card-bg);
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-md);
    overflow: hidden;
}

.table-card-header {
    padding: 18px 24px;
    border-bottom: 1px solid var(--border-color);
    background: #f8fafc;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
}

.table-card-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 8px;
}

.table-search-input {
    max-width: 260px;
    padding: 7px 12px;
    border: 1.5px solid var(--border-color);
    border-radius: var(--radius-sm);
    font-size: 13px;
}

.table-responsive-wrapper {
    width: 100%;
    overflow-x: auto;
}

.legends-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    font-size: 13.5px;
}

.legends-table thead tr {
    background: #f8fafc;
    border-bottom: 1px solid var(--border-color);
}

.legends-table th {
    padding: 12px 16px;
    font-weight: 700;
    color: #475569;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.legends-table tbody tr {
    border-bottom: 1px solid var(--border-color);
    transition: background 0.15s ease;
}

.legends-table tbody tr:hover {
    background: #f8fafc;
}

.legends-table td {
    padding: 12px 16px;
    vertical-align: middle;
}

/* Table Elements */
.table-marker-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 16px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    overflow: hidden;
}

.table-marker-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.table-photo-thumb {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    object-fit: cover;
    border: 1.5px solid var(--border-color);
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.table-photo-thumb:hover {
    transform: scale(1.1);
    box-shadow: var(--shadow-sm);
}

.table-no-photo-badge {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    background: #f1f5f9;
    color: #94a3b8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    border: 1px dashed #cbd5e1;
}

.table-legend-title {
    font-weight: 700;
    color: var(--text-primary);
}

.table-legend-sub {
    font-size: 11px;
    color: var(--text-secondary);
    font-family: monospace;
}

.coord-pill {
    background: #f1f5f9;
    color: #475569;
    font-size: 11.5px;
    font-family: monospace;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 4px;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11.5px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 20px;
}

.status-badge.active {
    background: #dcfce7;
    color: #15803d;
}

.status-badge.hidden {
    background: #fee2e2;
    color: #b91c1c;
}

.status-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: currentColor;
}

/* Action Buttons */
.table-actions {
    display: flex;
    align-items: center;
    gap: 6px;
}

.btn-table-action {
    background: #ffffff;
    border: 1px solid var(--border-color);
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.15s ease;
}

.btn-table-action:hover {
    background: #f8fafc;
    color: #1e293b;
    border-color: #94a3b8;
}

.btn-table-action.locate:hover {
    background: #eef2ff;
    color: var(--primary-indigo);
    border-color: #a5b4fc;
}

.btn-table-action.edit:hover {
    background: #fef3c7;
    color: #b45309;
    border-color: #fde68a;
}

.btn-table-action.toggle:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.btn-table-action.delete:hover {
    background: #fee2e2;
    color: #b91c1c;
    border-color: #fca5a5;
}

/* =========================================================
   Modals
   ========================================================= */
.modal-backdrop {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.7);
    backdrop-filter: blur(8px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.25s ease;
}

.modal-backdrop.show {
    display: flex;
    opacity: 1;
}

.modal-dialog {
    background: #ffffff;
    border-radius: var(--radius-lg);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
    width: 90%;
    max-width: 650px;
    max-height: 90vh;
    overflow-y: auto;
    transform: scale(0.95);
    transition: transform 0.25s ease;
    border: 1px solid var(--border-color);
}

.modal-backdrop.show .modal-dialog {
    transform: scale(1);
}

.modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
}

.modal-header h3 {
    margin: 0;
    font-size: 17px;
    font-weight: 700;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-close-modal {
    background: none;
    border: none;
    font-size: 22px;
    color: #94a3b8;
    cursor: pointer;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    transition: color 0.15s ease;
}

.btn-close-modal:hover {
    color: #ef4444;
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    padding: 16px 24px;
    border-top: 1px solid var(--border-color);
    background: #f8fafc;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.btn-modal-cancel {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #475569;
    padding: 9px 18px;
    border-radius: var(--radius-sm);
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
}

.btn-modal-save {
    background: var(--primary-indigo);
    border: none;
    color: #ffffff;
    padding: 9px 20px;
    border-radius: var(--radius-sm);
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(79, 70, 229, 0.3);
}

/* Landmark Photo Lightbox Modal (#landmarkPhotoModal) */
.lightbox-dialog {
    max-width: 800px;
    background: #0f172a;
    color: #ffffff;
    border-color: rgba(255, 255, 255, 0.1);
}

.lightbox-dialog .modal-header {
    background: #1e293b;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.lightbox-dialog .modal-header h3 {
    color: #ffffff;
}

.lightbox-dialog .btn-close-modal {
    color: #cbd5e1;
}

.lightbox-body {
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 380px;
}

.lightbox-image-container {
    max-width: 100%;
    max-height: 520px;
    overflow: hidden;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
}

.lightbox-image-container img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
}

.lightbox-empty-state {
    text-align: center;
    color: #94a3b8;
    padding: 40px 20px;
}

.lightbox-empty-state i {
    font-size: 48px;
    margin-bottom: 12px;
    opacity: 0.5;
}

/* Toast Notification (#adminToast) */
.admin-toast {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: #10b981;
    color: #ffffff;
    padding: 14px 22px;
    border-radius: var(--radius-md);
    box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    font-weight: 600;
    z-index: 9999;
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.admin-toast.show {
    transform: translateY(0);
    opacity: 1;
}

/* =========================================================
   Responsive Breakpoints
   ========================================================= */
@media (max-width: 1080px) {
    .form-three-columns {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .map-viewport-wrapper {
        height: 440px;
    }
}

@media (max-width: 768px) {
    .legend-page-header {
        padding: 18px 20px;
    }

    .map-viewport-wrapper {
        height: 360px;
    }

    .preset-icons-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (max-width: 480px) {
    .preset-icons-grid {
        grid-template-columns: repeat(3, 1fr);
    }

    .map-card-toolbar {
        flex-direction: column;
        align-items: flex-start;
    }
}

    </style>
</head>
<body>

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
                    <a class="dropdown-btn active"><i class="fa-solid fa-map-location-dot"></i> SpotMap Manage <i class="fa-solid fa-caret-down" style="margin-left: auto;"></i></a>
                    <div class="dropdown-container" style="display: block;">
                        <a href="Legend.php" class="active" style="color: #ffffff; font-weight: 600;">Legend</a>
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
                        <a href="barangayofficials.php">Barangay Official</a>
                        <a href="SKofficials.php">SK Official</a>
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

    <!-- Main Content Area -->
    <main class="main-content">
        
        <!-- Top Bar -->
        <div class="top-bar">
            <h1 class="page-title" style="font-size: 20px; font-weight: 700; color: #1e293b;">
                Spot Map Legends & Markers
            </h1>
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

        <div class="legend-management-wrapper">

            <!-- Banner Header -->
            <div class="legend-page-header">
                <div class="legend-header-title">
                    <h2><i class="bi bi-geo-alt-fill" style="color: #818cf8;"></i> Aerial Map Legends & Landmarks <span class="legend-badge">Aerial GIS</span></h2>
                    <p>Manage aerial landmark markers, coordinates, pin styling, and building photos displayed across the Spot Map.</p>
                </div>
                <div class="legend-header-stats">
                    <div class="stat-pill">
                        <i class="bi bi-pin-map-fill"></i> Total: <strong><?= count($all_legends) ?></strong>
                    </div>
                    <div class="stat-pill">
                        <i class="bi bi-eye-fill"></i> Active: <strong style="color: #4ade80;"><?= $active_count ?></strong>
                    </div>
                    <?php if ($hidden_count > 0): ?>
                    <div class="stat-pill">
                        <i class="bi bi-eye-slash-fill"></i> Hidden: <strong style="color: #f87171;"><?= $hidden_count ?></strong>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 2. Interactive Aerial Spot Map Interface (#aerialMapContainer) -->
            <div class="aerial-map-card">
                <div class="map-card-toolbar">
                    <div class="map-toolbar-info">
                        <span class="map-toolbar-title"><i class="bi bi-map-fill" style="color: #4f46e5;"></i> Aerial Viewport Preview</span>
                        <span class="map-coordinate-live" id="liveCoordBadge">Active Pin: Top 50.00%, Left 50.00%</span>
                    </div>
                    <div class="map-toolbar-actions">
                        <button type="button" class="btn-map-action" onclick="resetActivePinToCenter()" title="Center Pin">
                            <i class="bi bi-arrows-collapse"></i> Center Pin
                        </button>
                        <button type="button" class="btn-map-action" onclick="toggleMapFullscreen()" title="Fit View">
                            <i class="bi bi-aspect-ratio"></i> Toggle View
                        </button>
                    </div>
                </div>

                <div class="map-viewport-wrapper" id="aerialMapContainer">
                    <div class="map-hint-banner">
                        <i class="bi bi-hand-index-thumb"></i> Click map or drag the glowing pin to reposition
                    </div>

                    <!-- High-Resolution Aerial Map Background -->
                    <img src="../public/image/Maruing Map/Aerial View.png" 
                         alt="Aerial Spot Map" 
                         class="map-viewport-image" 
                         id="aerialMapImage"
                         onerror="this.src='../Images/Make_this_16;9_and_4k_20261001084446.jpg';">

                    <!-- Saved Map Markers -->
                    <?php foreach ($all_legends as $leg): 
                        if ($leg['is_active'] != 1) continue;
                        $leg_photo = get_legend_photo_url($leg['image_path']);
                        $is_custom_icon = (strpos($leg['icon_path'], 'uploads/') === 0);
                        $bg_color = !empty($leg['color_hex']) ? $leg['color_hex'] : '#6366f1';
                        $w = !empty($leg['marker_width']) ? (int)$leg['marker_width'] : 40;
                        $h = !empty($leg['marker_height']) ? (int)$leg['marker_height'] : 40;
                    ?>
                    <div class="saved-legend-marker" 
                         id="marker-<?= $leg['id'] ?>"
                         style="top: <?= (float)$leg['top_position'] ?>%; left: <?= (float)$leg['left_position'] ?>%;"
                         data-id="<?= $leg['id'] ?>"
                         data-name="<?= htmlspecialchars($leg['name']) ?>"
                         data-icon="<?= htmlspecialchars($leg['icon_path']) ?>"
                         data-color="<?= htmlspecialchars($bg_color) ?>"
                         data-photo="<?= htmlspecialchars($leg_photo) ?>"
                         data-top="<?= (float)$leg['top_position'] ?>"
                         data-left="<?= (float)$leg['left_position'] ?>"
                         onclick="viewLandmarkImage('<?= $leg['id'] ?>')">
                        
                        <!-- Tooltip Label -->
                        <div class="saved-marker-tooltip">
                            <span><?= htmlspecialchars($leg['name']) ?></span>
                            <?php if (!empty($leg_photo)): ?>
                                <span class="photo-pill"><i class="bi bi-image"></i> Photo</span>
                            <?php endif; ?>
                            <span class="delete-pin-btn" onclick="event.stopPropagation(); deleteLegendItem(<?= $leg['id'] ?>, '<?= htmlspecialchars(addslashes($leg['name'])) ?>')" title="Delete landmark from map">
                                <i class="bi bi-x-circle-fill" style="color: #f87171; font-size: 13px; margin-left: 4px;"></i>
                            </span>
                        </div>

                        <!-- Stylized Map Pin -->
                        <div class="saved-marker-pin" style="background-color: <?= $bg_color ?>; width: <?= $w ?>px; height: <?= $h ?>px;">
                            <?php if ($is_custom_icon): ?>
                                <img src="<?= htmlspecialchars($leg['icon_path']) ?>" alt="<?= htmlspecialchars($leg['name']) ?>">
                            <?php else: ?>
                                <i class="bi <?= htmlspecialchars($leg['icon_path']) ?>"></i>
                            <?php endif; ?>
                        </div>
                        <div class="saved-marker-pointer" style="border-top-color: <?= $bg_color ?>;"></div>
                    </div>
                    <?php endforeach; ?>

                    <!-- Active Draggable Pin (#activeDraggableMarker) -->
                    <div class="active-draggable-marker" id="activeDraggableMarker" style="top: 50.00%; left: 50.00%;">
                        <div class="active-marker-badge">
                            <i class="bi bi-crosshair"></i> <span id="activeMarkerBadgeText">New Pin</span>
                        </div>
                        <div class="draggable-pulse-ring" id="activeMarkerPulseRing"></div>
                        <div class="active-draggable-pin" id="activeMarkerPin">
                            <i class="bi bi-building-fill" id="activeMarkerIcon"></i>
                        </div>
                        <div class="active-draggable-pointer" id="activeMarkerPointer"></div>
                    </div>
                </div>
            </div>

            <!-- 3. Horizontal 3-Column Add Legend Form (#addLegendForm) -->
            <div class="legend-form-card">
                <div class="form-card-header">
                    <div>
                        <h3><i class="bi bi-plus-circle-fill" style="color: #10b981;"></i> Add New Spot Map Legend</h3>
                        <p>Configure landmark details, pick or upload custom icons, and specify aerial coordinates.</p>
                    </div>
                </div>

                <form id="addLegendForm" method="POST" enctype="multipart/form-data" action="Legend.php">
                    <input type="hidden" name="action" value="add_legend">
                    <input type="hidden" name="icon_type" id="addIconType" value="preset">
                    <input type="hidden" name="preset_icon" id="addPresetIconInput" value="bi-building-fill">

                    <div class="form-three-columns">
                        
                        <!-- Column 1: Landmark Details -->
                        <div class="form-col">
                            <div class="form-col-title">
                                <i class="bi bi-card-heading"></i> 1. Landmark Details
                            </div>

                            <div class="form-field-group">
                                <label for="addNameInput">Landmark / Legend Name <span class="required-asterisk">*</span></label>
                                <input type="text" 
                                       name="name" 
                                       id="addNameInput" 
                                       class="form-input" 
                                       placeholder="e.g. Barangay Tabon Hall" 
                                       required
                                       oninput="updateLivePreview()">
                            </div>

                            <div class="form-field-group">
                                <label>Landmark Photo Upload</label>
                                <div class="photo-upload-area" onclick="document.getElementById('addLandmarkPhotoInput').click()">
                                    <input type="file" 
                                           name="landmark_image" 
                                           id="addLandmarkPhotoInput" 
                                           accept="image/*" 
                                           style="display:none;" 
                                           onchange="handleAddPhotoChange(this)">
                                    <div class="photo-upload-hint" id="addPhotoHint">
                                        <i class="bi bi-cloud-arrow-up"></i>
                                        <span>Click to attach building photo (JPG, PNG)</span>
                                    </div>
                                    <div class="photo-preview-wrap" id="addPhotoPreviewWrap">
                                        <img id="addPhotoPreviewImg" src="" alt="Thumbnail Preview">
                                        <button type="button" class="btn-remove-photo" onclick="removeAddPhoto(event)" title="Remove Photo">&times;</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-field-group">
                                <label>Icon & Pin Color Picker</label>
                                <div class="color-picker-wrapper">
                                    <div class="color-input-combo">
                                        <input type="color" 
                                               id="addColorNative" 
                                               class="color-native-input" 
                                               value="#6366f1"
                                               oninput="syncAddColorFromNative(this.value)">
                                        <input type="text" 
                                               name="color_hex" 
                                               id="addColorHexText" 
                                               class="form-input color-hex-input" 
                                               value="#6366F1"
                                               oninput="syncAddColorFromText(this.value)">
                                    </div>
                                    <!-- 10 Quick-Palette Color Chips -->
                                    <div class="quick-palette-container">
                                        <span class="palette-chip active" style="background:#6366f1;" data-color="#6366f1" title="Indigo" onclick="selectPaletteColor('#6366f1')"></span>
                                        <span class="palette-chip" style="background:#10b981;" data-color="#10b981" title="Emerald" onclick="selectPaletteColor('#10b981')"></span>
                                        <span class="palette-chip" style="background:#f59e0b;" data-color="#f59e0b" title="Amber" onclick="selectPaletteColor('#f59e0b')"></span>
                                        <span class="palette-chip" style="background:#f43f5e;" data-color="#f43f5e" title="Rose" onclick="selectPaletteColor('#f43f5e')"></span>
                                        <span class="palette-chip" style="background:#64748b;" data-color="#64748b" title="Slate" onclick="selectPaletteColor('#64748b')"></span>
                                        <span class="palette-chip" style="background:#0284c7;" data-color="#0284c7" title="Sky Blue" onclick="selectPaletteColor('#0284c7')"></span>
                                        <span class="palette-chip" style="background:#8b5cf6;" data-color="#8b5cf6" title="Purple" onclick="selectPaletteColor('#8b5cf6')"></span>
                                        <span class="palette-chip" style="background:#15803d;" data-color="#15803d" title="Barangay Green" onclick="selectPaletteColor('#15803d')"></span>
                                        <span class="palette-chip" style="background:#ea580c;" data-color="#ea580c" title="Orange" onclick="selectPaletteColor('#ea580c')"></span>
                                        <span class="palette-chip" style="background:#0d9488;" data-color="#0d9488" title="Teal" onclick="selectPaletteColor('#0d9488')"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Column 2: Icon Selection & Live Preview -->
                        <div class="form-col">
                            <div class="form-col-title">
                                <i class="bi bi-app-indicator"></i> 2. Icon & Live Preview
                            </div>

                            <div class="icon-selection-box">
                                <div class="icon-tabs-nav">
                                    <button type="button" class="icon-tab-btn active" id="tabPresetBtn" onclick="switchAddIconTab('preset')">
                                        <i class="bi bi-grid-3x3-gap"></i> Presets
                                    </button>
                                    <button type="button" class="icon-tab-btn" id="tabUploadBtn" onclick="switchAddIconTab('upload')">
                                        <i class="bi bi-upload"></i> Upload Icon
                                    </button>
                                </div>

                                <!-- 16 Selectable Preset Icons Grid -->
                                <div class="preset-icons-grid" id="addPresetGrid">
                                    <div class="preset-icon-chip selected" data-icon="bi-building-fill" onclick="selectPresetIcon('bi-building-fill', this)">
                                        <i class="bi bi-building-fill"></i><span>Hall</span>
                                    </div>
                                    <div class="preset-icon-chip" data-icon="bi-book-half" onclick="selectPresetIcon('bi-book-half', this)">
                                        <i class="bi bi-book-half"></i><span>School</span>
                                    </div>
                                    <div class="preset-icon-chip" data-icon="bi-dribbble" onclick="selectPresetIcon('bi-dribbble', this)">
                                        <i class="bi bi-dribbble"></i><span>Court</span>
                                    </div>
                                    <div class="preset-icon-chip" data-icon="bi-heart-pulse-fill" onclick="selectPresetIcon('bi-heart-pulse-fill', this)">
                                        <i class="bi bi-heart-pulse-fill"></i><span>Health</span>
                                    </div>
                                    <div class="preset-icon-chip" data-icon="bi-geo-fill" onclick="selectPresetIcon('bi-geo-fill', this)">
                                        <i class="bi bi-geo-fill"></i><span>Pin</span>
                                    </div>
                                    <div class="preset-icon-chip" data-icon="bi-house-heart-fill" onclick="selectPresetIcon('bi-house-heart-fill', this)">
                                        <i class="bi bi-house-heart-fill"></i><span>House</span>
                                    </div>
                                    <div class="preset-icon-chip" data-icon="bi-hospital" onclick="selectPresetIcon('bi-hospital', this)">
                                        <i class="bi bi-hospital"></i><span>Hospital</span>
                                    </div>
                                    <div class="preset-icon-chip" data-icon="bi-shop" onclick="selectPresetIcon('bi-shop', this)">
                                        <i class="bi bi-shop"></i><span>Store</span>
                                    </div>
                                    <div class="preset-icon-chip" data-icon="bi-bell-fill" onclick="selectPresetIcon('bi-bell-fill', this)">
                                        <i class="bi bi-bell-fill"></i><span>Chapel</span>
                                    </div>
                                    <div class="preset-icon-chip" data-icon="bi-shield-check" onclick="selectPresetIcon('bi-shield-check', this)">
                                        <i class="bi bi-shield-check"></i><span>Security</span>
                                    </div>
                                    <div class="preset-icon-chip" data-icon="bi-water" onclick="selectPresetIcon('bi-water', this)">
                                        <i class="bi bi-water"></i><span>Water</span>
                                    </div>
                                    <div class="preset-icon-chip" data-icon="bi-tree-fill" onclick="selectPresetIcon('bi-tree-fill', this)">
                                        <i class="bi bi-tree-fill"></i><span>Park</span>
                                    </div>
                                    <div class="preset-icon-chip" data-icon="bi-mortarboard-fill" onclick="selectPresetIcon('bi-mortarboard-fill', this)">
                                        <i class="bi bi-mortarboard-fill"></i><span>Daycare</span>
                                    </div>
                                    <div class="preset-icon-chip" data-icon="bi-flag-fill" onclick="selectPresetIcon('bi-flag-fill', this)">
                                        <i class="bi bi-flag-fill"></i><span>Plaza</span>
                                    </div>
                                    <div class="preset-icon-chip" data-icon="bi-lightning-charge-fill" onclick="selectPresetIcon('bi-lightning-charge-fill', this)">
                                        <i class="bi bi-lightning-charge-fill"></i><span>Power</span>
                                    </div>
                                    <div class="preset-icon-chip" data-icon="bi-pin-map-fill" onclick="selectPresetIcon('bi-pin-map-fill', this)">
                                        <i class="bi bi-pin-map-fill"></i><span>Evacuation</span>
                                    </div>
                                </div>

                                <!-- Upload Icon Tab Content -->
                                <div class="upload-icon-tab-content" id="addUploadTabContent">
                                    <div class="photo-upload-area" onclick="document.getElementById('addCustomIconInput').click()">
                                        <input type="file" 
                                               name="custom_icon" 
                                               id="addCustomIconInput" 
                                               accept=".png,.svg,.webp,.jpg,.jpeg" 
                                               style="display:none;" 
                                               onchange="handleCustomIconChange(this)">
                                        <div class="photo-upload-hint">
                                            <i class="bi bi-file-earmark-image"></i>
                                            <span>Upload Custom PNG or SVG Marker Icon</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Live Preview Card -->
                            <div class="live-preview-box">
                                <div class="preview-marker-badge" id="livePreviewBadge" style="background:#6366f1;">
                                    <i class="bi bi-building-fill" id="livePreviewIcon"></i>
                                </div>
                                <div class="preview-legend-info">
                                    <div class="preview-legend-name" id="livePreviewName">New Landmark</div>
                                    <div class="preview-legend-meta">
                                        <span id="livePreviewCoords">Top: 50.00% | Left: 50.00%</span>
                                        <span class="preview-photo-badge no-photo" id="livePreviewPhotoBadge">No Photo</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Column 3: Placement & Save -->
                        <div class="form-col">
                            <div class="form-col-title">
                                <i class="bi bi-sliders"></i> 3. Placement & Save
                            </div>

                            <div class="form-row-dual">
                                <div class="form-field-group">
                                    <label for="topPositionInput">Top Position (%)</label>
                                    <input type="number" 
                                           step="0.01" 
                                           name="top_position" 
                                           id="topPositionInput" 
                                           class="form-input" 
                                           value="50.00" 
                                           oninput="handleCoordinateInput()">
                                </div>
                                <div class="form-field-group">
                                    <label for="leftPositionInput">Left Position (%)</label>
                                    <input type="number" 
                                           step="0.01" 
                                           name="left_position" 
                                           id="leftPositionInput" 
                                           class="form-input" 
                                           value="50.00" 
                                           oninput="handleCoordinateInput()">
                                </div>
                            </div>

                            <div class="form-row-dual">
                                <div class="form-field-group">
                                    <label for="markerWidthInput">Width (px)</label>
                                    <input type="number" 
                                           name="marker_width" 
                                           id="markerWidthInput" 
                                           class="form-input" 
                                           value="40" 
                                           min="20" 
                                           max="100"
                                           oninput="updateActiveMarkerDimensions()">
                                </div>
                                <div class="form-field-group">
                                    <label for="markerHeightInput">Height (px)</label>
                                    <input type="number" 
                                           name="marker_height" 
                                           id="markerHeightInput" 
                                           class="form-input" 
                                           value="40" 
                                           min="20" 
                                           max="100"
                                           oninput="updateActiveMarkerDimensions()">
                                </div>
                            </div>

                            <div class="form-row-dual">
                                <div class="form-field-group">
                                    <label for="displayOrderInput">Display Order</label>
                                    <input type="number" 
                                           name="display_order" 
                                           id="displayOrderInput" 
                                           class="form-input" 
                                           value="<?= count($all_legends) + 1 ?>" 
                                           min="0">
                                </div>
                                <div class="form-field-group">
                                    <label for="isActiveInput">Status</label>
                                    <select name="is_active" id="isActiveInput" class="form-input">
                                        <option value="1">Active / Visible</option>
                                        <option value="0">Inactive / Hidden</option>
                                    </select>
                                </div>
                            </div>

                            <button type="submit" class="btn-save-legend" id="btnSaveLegend">
                                <i class="bi bi-geo-alt-fill"></i> Save Legend & Pin to Map
                            </button>
                        </div>

                    </div>
                </form>
            </div>

            <!-- 4. Existing Legends Table (.legends-table) -->
            <div class="legends-table-card">
                <div class="table-card-header">
                    <h3><i class="bi bi-list-stars" style="color: #4f46e5;"></i> Existing Spot Map Legends</h3>
                    <input type="text" 
                           id="tableSearchInput" 
                           class="table-search-input" 
                           placeholder="Filter landmarks..." 
                           oninput="filterLegendsTable(this.value)">
                </div>

                <div class="table-responsive-wrapper">
                    <table class="legends-table" id="legendsTable">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th style="width: 70px;">Icon</th>
                                <th style="width: 70px;">Photo</th>
                                <th>Legend / Landmark</th>
                                <th>Position (%)</th>
                                <th>Status</th>
                                <th style="text-align: right; width: 260px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($all_legends)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: #94a3b8; padding: 30px;">
                                    <i class="bi bi-geo" style="font-size: 24px; display: block; margin-bottom: 6px;"></i>
                                    No spot map legends created yet. Use the form above to add your first landmark.
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($all_legends as $leg): 
                                    $photo_src = get_legend_photo_url($leg['image_path']);
                                    $is_custom = (strpos($leg['icon_path'], 'uploads/') === 0);
                                    $bg_col = !empty($leg['color_hex']) ? $leg['color_hex'] : '#6366f1';
                                    $leg_json = htmlspecialchars(json_encode($leg), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr id="table-row-<?= $leg['id'] ?>" data-id="<?= $leg['id'] ?>" data-name="<?= htmlspecialchars(strtolower($leg['name'])) ?>">
                                    <td><strong><?= (int)$leg['display_order'] ?></strong></td>
                                    <td>
                                        <div class="table-marker-icon" style="background-color: <?= $bg_col ?>;">
                                            <?php if ($is_custom): ?>
                                                <img src="<?= htmlspecialchars($leg['icon_path']) ?>" alt="Icon">
                                            <?php else: ?>
                                                <i class="bi <?= htmlspecialchars($leg['icon_path']) ?>"></i>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($photo_src)): ?>
                                            <img src="<?= htmlspecialchars($photo_src) ?>" 
                                                 alt="<?= htmlspecialchars($leg['name']) ?>" 
                                                 class="table-photo-thumb"
                                                 onclick="viewLandmarkImage('<?= $leg['id'] ?>')"
                                                 title="View full landmark photo">
                                        <?php else: ?>
                                            <div class="table-no-photo-badge" title="No photo uploaded">
                                                <i class="bi bi-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="table-legend-title"><?= htmlspecialchars($leg['name']) ?></div>
                                        <div class="table-legend-sub"><?= htmlspecialchars($leg['icon_path']) ?></div>
                                    </td>
                                    <td>
                                        <span class="coord-pill">
                                            Top: <?= (float)$leg['top_position'] ?>% &bull; Left: <?= (float)$leg['left_position'] ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($leg['is_active'] == 1): ?>
                                            <span class="status-badge active"><span class="status-dot"></span> Active</span>
                                        <?php else: ?>
                                            <span class="status-badge hidden"><span class="status-dot"></span> Hidden</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <div class="table-actions" style="justify-content: flex-end;">
                                            <!-- Locate Pin on Map -->
                                            <button type="button" 
                                                    class="btn-table-action locate" 
                                                    onclick="highlightMapPin(<?= $leg['id'] ?>, <?= (float)$leg['top_position'] ?>, <?= (float)$leg['left_position'] ?>)"
                                                    title="Locate on Aerial Map">
                                                <i class="bi bi-crosshair"></i> Locate
                                            </button>

                                            <!-- Edit Modal Button -->
                                            <button type="button" 
                                                    class="btn-table-action edit" 
                                                    onclick="openEditModal(<?= $leg_json ?>)"
                                                    title="Edit Legend">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </button>

                                            <!-- Toggle Visibility -->
                                            <form method="POST" action="Legend.php" style="display:inline;" onsubmit="return handleToggleSubmit(event, this)">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="id" value="<?= $leg['id'] ?>">
                                                <button type="submit" class="btn-table-action toggle" title="<?= $leg['is_active'] == 1 ? 'Hide Landmark' : 'Make Visible' ?>">
                                                    <i class="bi <?= $leg['is_active'] == 1 ? 'bi-eye-slash' : 'bi-eye' ?>"></i>
                                                </button>
                                            </form>

                                            <!-- Delete Button -->
                                            <button type="button" class="btn-table-action delete" title="Delete Landmark" onclick="deleteLegendItem(<?= $leg['id'] ?>, '<?= htmlspecialchars(addslashes($leg['name'])) ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </main>

    <!-- 5. Edit Legend Modal (#editLegendModal) -->
    <div class="modal-backdrop" id="editLegendModal" onclick="handleBackdropClick(event, 'editLegendModal')">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3><i class="bi bi-pencil-square" style="color: #f59e0b;"></i> Edit Spot Map Legend</h3>
                <button type="button" class="btn-close-modal" onclick="closeEditModal()">&times;</button>
            </div>
            
            <form id="editLegendForm" method="POST" enctype="multipart/form-data" action="Legend.php">
                <input type="hidden" name="action" value="edit_legend">
                <input type="hidden" name="legend_id" id="editLegendId">
                <input type="hidden" name="icon_type" id="editIconType" value="preset">
                <input type="hidden" name="preset_icon" id="editPresetIconInput" value="bi-building-fill">
                <input type="hidden" name="remove_landmark_image" id="editRemoveLandmarkImage" value="0">

                <div class="modal-body">
                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        
                        <div class="form-field-group">
                            <label for="editNameInput">Landmark / Legend Name <span class="required-asterisk">*</span></label>
                            <input type="text" name="name" id="editNameInput" class="form-input" required>
                        </div>

                        <!-- Color Picker in Modal -->
                        <div class="form-field-group">
                            <label>Icon & Pin Color Picker</label>
                            <div class="color-picker-wrapper">
                                <div class="color-input-combo">
                                    <input type="color" id="editColorNative" class="color-native-input" oninput="syncEditColorFromNative(this.value)">
                                    <input type="text" name="color_hex" id="editColorHexText" class="form-input color-hex-input" oninput="syncEditColorFromText(this.value)">
                                </div>
                                <div class="quick-palette-container" id="editPaletteContainer">
                                    <span class="palette-chip" style="background:#6366f1;" data-color="#6366f1" onclick="selectEditPaletteColor('#6366f1')"></span>
                                    <span class="palette-chip" style="background:#10b981;" data-color="#10b981" onclick="selectEditPaletteColor('#10b981')"></span>
                                    <span class="palette-chip" style="background:#f59e0b;" data-color="#f59e0b" onclick="selectEditPaletteColor('#f59e0b')"></span>
                                    <span class="palette-chip" style="background:#f43f5e;" data-color="#f43f5e" onclick="selectEditPaletteColor('#f43f5e')"></span>
                                    <span class="palette-chip" style="background:#64748b;" data-color="#64748b" onclick="selectEditPaletteColor('#64748b')"></span>
                                    <span class="palette-chip" style="background:#0284c7;" data-color="#0284c7" onclick="selectEditPaletteColor('#0284c7')"></span>
                                    <span class="palette-chip" style="background:#8b5cf6;" data-color="#8b5cf6" onclick="selectEditPaletteColor('#8b5cf6')"></span>
                                    <span class="palette-chip" style="background:#15803d;" data-color="#15803d" onclick="selectEditPaletteColor('#15803d')"></span>
                                    <span class="palette-chip" style="background:#ea580c;" data-color="#ea580c" onclick="selectEditPaletteColor('#ea580c')"></span>
                                    <span class="palette-chip" style="background:#0d9488;" data-color="#0d9488" onclick="selectEditPaletteColor('#0d9488')"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Icon selection inside modal -->
                        <div class="form-field-group">
                            <label>Icon Selection</label>
                            <div class="icon-tabs-nav">
                                <button type="button" class="icon-tab-btn active" id="modalTabPresetBtn" onclick="switchModalIconTab('preset')">
                                    <i class="bi bi-grid-3x3-gap"></i> Presets
                                </button>
                                <button type="button" class="icon-tab-btn" id="modalTabUploadBtn" onclick="switchModalIconTab('upload')">
                                    <i class="bi bi-upload"></i> Upload Icon
                                </button>
                            </div>

                            <div class="preset-icons-grid" id="modalPresetGrid" style="margin-top: 10px;">
                                <div class="preset-icon-chip" data-icon="bi-building-fill" onclick="selectModalPresetIcon('bi-building-fill', this)"><i class="bi bi-building-fill"></i><span>Hall</span></div>
                                <div class="preset-icon-chip" data-icon="bi-book-half" onclick="selectModalPresetIcon('bi-book-half', this)"><i class="bi bi-book-half"></i><span>School</span></div>
                                <div class="preset-icon-chip" data-icon="bi-dribbble" onclick="selectModalPresetIcon('bi-dribbble', this)"><i class="bi bi-dribbble"></i><span>Court</span></div>
                                <div class="preset-icon-chip" data-icon="bi-heart-pulse-fill" onclick="selectModalPresetIcon('bi-heart-pulse-fill', this)"><i class="bi bi-heart-pulse-fill"></i><span>Health</span></div>
                                <div class="preset-icon-chip" data-icon="bi-geo-fill" onclick="selectModalPresetIcon('bi-geo-fill', this)"><i class="bi bi-geo-fill"></i><span>Pin</span></div>
                                <div class="preset-icon-chip" data-icon="bi-house-heart-fill" onclick="selectModalPresetIcon('bi-house-heart-fill', this)"><i class="bi bi-house-heart-fill"></i><span>House</span></div>
                                <div class="preset-icon-chip" data-icon="bi-hospital" onclick="selectModalPresetIcon('bi-hospital', this)"><i class="bi bi-hospital"></i><span>Hospital</span></div>
                                <div class="preset-icon-chip" data-icon="bi-shop" onclick="selectModalPresetIcon('bi-shop', this)"><i class="bi bi-shop"></i><span>Store</span></div>
                                <div class="preset-icon-chip" data-icon="bi-bell-fill" onclick="selectModalPresetIcon('bi-bell-fill', this)"><i class="bi bi-bell-fill"></i><span>Chapel</span></div>
                                <div class="preset-icon-chip" data-icon="bi-shield-check" onclick="selectModalPresetIcon('bi-shield-check', this)"><i class="bi bi-shield-check"></i><span>Security</span></div>
                                <div class="preset-icon-chip" data-icon="bi-water" onclick="selectModalPresetIcon('bi-water', this)"><i class="bi bi-water"></i><span>Water</span></div>
                                <div class="preset-icon-chip" data-icon="bi-tree-fill" onclick="selectModalPresetIcon('bi-tree-fill', this)"><i class="bi bi-tree-fill"></i><span>Park</span></div>
                                <div class="preset-icon-chip" data-icon="bi-mortarboard-fill" onclick="selectModalPresetIcon('bi-mortarboard-fill', this)"><i class="bi bi-mortarboard-fill"></i><span>Daycare</span></div>
                                <div class="preset-icon-chip" data-icon="bi-flag-fill" onclick="selectModalPresetIcon('bi-flag-fill', this)"><i class="bi bi-flag-fill"></i><span>Plaza</span></div>
                                <div class="preset-icon-chip" data-icon="bi-lightning-charge-fill" onclick="selectModalPresetIcon('bi-lightning-charge-fill', this)"><i class="bi bi-lightning-charge-fill"></i><span>Power</span></div>
                                <div class="preset-icon-chip" data-icon="bi-pin-map-fill" onclick="selectModalPresetIcon('bi-pin-map-fill', this)"><i class="bi bi-pin-map-fill"></i><span>Evacuation</span></div>
                            </div>

                            <div class="upload-icon-tab-content" id="modalUploadTabContent" style="margin-top: 10px;">
                                <div class="photo-upload-area" onclick="document.getElementById('modalCustomIconInput').click()">
                                    <input type="file" name="custom_icon" id="modalCustomIconInput" accept=".png,.svg,.webp,.jpg,.jpeg" style="display:none;">
                                    <div class="photo-upload-hint">
                                        <i class="bi bi-file-earmark-image"></i>
                                        <span>Upload New Custom Icon (PNG/SVG)</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Photo Replacement in Modal -->
                        <div class="form-field-group">
                            <label>Landmark Photo</label>
                            <div class="photo-upload-area" onclick="document.getElementById('modalLandmarkPhotoInput').click()">
                                <input type="file" name="landmark_image" id="modalLandmarkPhotoInput" accept="image/*" style="display:none;" onchange="handleModalPhotoChange(this)">
                                <div class="photo-upload-hint" id="modalPhotoHint">
                                    <i class="bi bi-cloud-arrow-up"></i>
                                    <span>Click to replace photo</span>
                                </div>
                                <div class="photo-preview-wrap" id="modalPhotoPreviewWrap">
                                    <img id="modalPhotoPreviewImg" src="" alt="Landmark Photo">
                                    <button type="button" class="btn-remove-photo" onclick="removeModalPhoto(event)" title="Remove Photo">&times;</button>
                                </div>
                            </div>
                        </div>

                        <!-- Coordinates & Dimensions -->
                        <div class="form-row-dual">
                            <div class="form-field-group">
                                <label for="editTopPositionInput">Top Position (%)</label>
                                <input type="number" step="0.01" name="top_position" id="editTopPositionInput" class="form-input" required>
                            </div>
                            <div class="form-field-group">
                                <label for="editLeftPositionInput">Left Position (%)</label>
                                <input type="number" step="0.01" name="left_position" id="editLeftPositionInput" class="form-input" required>
                            </div>
                        </div>

                        <div class="form-row-dual">
                            <div class="form-field-group">
                                <label for="editMarkerWidthInput">Width (px)</label>
                                <input type="number" name="marker_width" id="editMarkerWidthInput" class="form-input" min="20" max="100">
                            </div>
                            <div class="form-field-group">
                                <label for="editMarkerHeightInput">Height (px)</label>
                                <input type="number" name="marker_height" id="editMarkerHeightInput" class="form-input" min="20" max="100">
                            </div>
                        </div>

                        <div class="form-row-dual">
                            <div class="form-field-group">
                                <label for="editDisplayOrderInput">Display Order</label>
                                <input type="number" name="display_order" id="editDisplayOrderInput" class="form-input">
                            </div>
                            <div class="form-field-group">
                                <label for="editIsActiveInput">Visibility Status</label>
                                <select name="is_active" id="editIsActiveInput" class="form-input">
                                    <option value="1">Active / Visible</option>
                                    <option value="0">Inactive / Hidden</option>
                                </select>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-modal-save"><i class="bi bi-check-circle"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 6. Landmark Photo Lightbox Modal (#landmarkPhotoModal) -->
    <div class="modal-backdrop" id="landmarkPhotoModal" onclick="handleBackdropClick(event, 'landmarkPhotoModal')">
        <div class="modal-dialog lightbox-dialog">
            <div class="modal-header">
                <h3>
                    <span id="lightboxBadge" class="table-marker-icon" style="width: 28px; height: 28px; font-size: 14px; margin-right: 8px;"></span>
                    <span id="lightboxTitle">Landmark Photo</span>
                </h3>
                <button type="button" class="btn-close-modal" onclick="closeLandmarkPhotoModal()">&times;</button>
            </div>
            <div class="lightbox-body">
                <div class="lightbox-image-container" id="lightboxImageContainer" style="display:none;">
                    <img id="lightboxImg" src="" alt="Landmark Building">
                </div>
                <div class="lightbox-empty-state" id="lightboxEmptyState">
                    <i class="bi bi-camera"></i>
                    <h4>No Photo Attached</h4>
                    <p>There is no building photo uploaded for this landmark yet.</p>
                </div>
            </div>
                        <div class="modal-footer" style="background:#1e293b; border-color: rgba(255,255,255,0.1); justify-content: space-between;">
                <span id="lightboxMetaCoords" style="color: #94a3b8; font-family: monospace; font-size: 12px; display: flex; align-items: center;"></span>
                <div style="display: flex; gap: 8px;">
                    <button type="button" id="lightboxEditBtn" class="btn-modal-save" style="background:#4f46e5; padding: 6px 14px; font-size: 13px;">
                        <i class="bi bi-pencil-square"></i> Edit
                    </button>
                    <button type="button" id="lightboxDeleteBtn" class="btn-modal-cancel" style="background: #ef4444; color: #ffffff; border-color: #ef4444; padding: 6px 14px; font-size: 13px;">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                    <button type="button" class="btn-modal-cancel" style="background: rgba(255,255,255,0.1); color: #ffffff; border-color: transparent;" onclick="closeLandmarkPhotoModal()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Toast Notification (#adminToast) -->
    <div class="admin-toast" id="adminToast">
        <i class="bi bi-check-circle-fill" id="adminToastIcon" style="font-size: 20px;"></i>
        <span id="adminToastMsg">Changes saved successfully!</span>
    </div>

    <!-- Interactive Scripting -->
    <script>
        // Data store
        const ALL_LEGENDS = <?= json_encode($all_legends, JSON_UNESCAPED_UNICODE) ?>;
        
        // Map elements
        const mapContainer       = document.getElementById('aerialMapContainer');
        const activeMarker       = document.getElementById('activeDraggableMarker');
        const activeMarkerPin    = document.getElementById('activeMarkerPin');
        const activeMarkerIcon   = document.getElementById('activeMarkerIcon');
        const activeMarkerPointer= document.getElementById('activeMarkerPointer');
        const activeMarkerPulse  = document.getElementById('activeMarkerPulseRing');
        const activeMarkerBadge  = document.getElementById('activeMarkerBadgeText');
        const liveCoordBadge     = document.getElementById('liveCoordBadge');
        
        // Add form inputs
        const topInput           = document.getElementById('topPositionInput');
        const leftInput          = document.getElementById('leftPositionInput');
        const nameInput          = document.getElementById('addNameInput');
        const colorNative        = document.getElementById('addColorNative');
        const colorHex           = document.getElementById('addColorHexText');
        const widthInput         = document.getElementById('markerWidthInput');
        const heightInput        = document.getElementById('markerHeightInput');

        // State variables
        let isDraggingActivePin  = false;
        let activeTop            = 50.00;
        let activeLeft           = 50.00;

        // --- Active Marker Dragging on Map ---
        function setPinPosition(topPct, leftPct) {
            topPct = Math.max(0, Math.min(100, parseFloat(topPct) || 0));
            leftPct = Math.max(0, Math.min(100, parseFloat(leftPct) || 0));
            activeTop = topPct;
            activeLeft = leftPct;

            activeMarker.style.top = topPct.toFixed(2) + '%';
            activeMarker.style.left = leftPct.toFixed(2) + '%';

            topInput.value = topPct.toFixed(2);
            leftInput.value = leftPct.toFixed(2);

            const coordText = `Top: ${topPct.toFixed(2)}%, Left: ${leftPct.toFixed(2)}%`;
            liveCoordBadge.textContent = 'Active Pin: ' + coordText;
            document.getElementById('livePreviewCoords').textContent = coordText;
        }

        // Mouse & Touch events for Draggable Marker
        activeMarker.addEventListener('mousedown', function(e) {
            isDraggingActivePin = true;
            e.preventDefault();
            e.stopPropagation();
        });

        mapContainer.addEventListener('mousedown', function(e) {
            if (e.target.closest('.saved-legend-marker')) return;
            // Snap to click
            const rect = mapContainer.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            setPinPosition((y / rect.height) * 100, (x / rect.width) * 100);
            isDraggingActivePin = true;
        });

        window.addEventListener('mousemove', function(e) {
            if (!isDraggingActivePin) return;
            const rect = mapContainer.getBoundingClientRect();
            let x = e.clientX - rect.left;
            let y = e.clientY - rect.top;
            x = Math.max(0, Math.min(rect.width, x));
            y = Math.max(0, Math.min(rect.height, y));
            setPinPosition((y / rect.height) * 100, (x / rect.width) * 100);
        });

        window.addEventListener('mouseup', function() {
            if (isDraggingActivePin) {
                isDraggingActivePin = false;
            }
        });

        // Touch support
        activeMarker.addEventListener('touchstart', function(e) {
            isDraggingActivePin = true;
            e.stopPropagation();
        }, { passive: false });

        mapContainer.addEventListener('touchmove', function(e) {
            if (!isDraggingActivePin || !e.touches[0]) return;
            const rect = mapContainer.getBoundingClientRect();
            let x = e.touches[0].clientX - rect.left;
            let y = e.touches[0].clientY - rect.top;
            x = Math.max(0, Math.min(rect.width, x));
            y = Math.max(0, Math.min(rect.height, y));
            setPinPosition((y / rect.height) * 100, (x / rect.width) * 100);
            e.preventDefault();
        }, { passive: false });

        window.addEventListener('touchend', function() {
            isDraggingActivePin = false;
        });

        function handleCoordinateInput() {
            const t = parseFloat(topInput.value) || 0;
            const l = parseFloat(leftInput.value) || 0;
            setPinPosition(t, l);
        }

        function resetActivePinToCenter() {
            setPinPosition(50.00, 50.00);
            showToast('Active pin centered on aerial map');
        }

        function toggleMapFullscreen() {
            const wrap = document.getElementById('aerialMapContainer');
            if (wrap.style.height === '80vh') {
                wrap.style.height = '520px';
            } else {
                wrap.style.height = '80vh';
            }
        }

        // --- Color Synchronization ---
        function applyColor(hex) {
            hex = hex.toUpperCase();
            colorNative.value = hex;
            colorHex.value = hex;

            activeMarkerPin.style.backgroundColor = hex;
            activeMarkerPointer.style.borderTopColor = hex;
            activeMarkerPulse.style.backgroundColor = hex + '4D';

            document.getElementById('livePreviewBadge').style.backgroundColor = hex;

            // Highlight chip
            document.querySelectorAll('.quick-palette-container .palette-chip').forEach(chip => {
                chip.classList.toggle('active', chip.getAttribute('data-color').toUpperCase() === hex);
            });
        }

        function syncAddColorFromNative(val) {
            applyColor(val);
        }

        function syncAddColorFromText(val) {
            if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                applyColor(val);
            }
        }

        function selectPaletteColor(hex) {
            applyColor(hex);
        }

        // --- Icon Selection ---
        function switchAddIconTab(tab) {
            document.getElementById('addIconType').value = tab;
            const presetBtn = document.getElementById('tabPresetBtn');
            const uploadBtn = document.getElementById('tabUploadBtn');
            const presetGrid = document.getElementById('addPresetGrid');
            const uploadTab = document.getElementById('addUploadTabContent');

            if (tab === 'preset') {
                presetBtn.classList.add('active');
                uploadBtn.classList.remove('active');
                presetGrid.style.display = 'grid';
                uploadTab.style.display = 'none';
                
                const sel = presetGrid.querySelector('.preset-icon-chip.selected');
                if (sel) selectPresetIcon(sel.getAttribute('data-icon'), sel);
            } else {
                uploadBtn.classList.add('active');
                presetBtn.classList.remove('active');
                presetGrid.style.display = 'none';
                uploadTab.style.display = 'flex';
            }
        }

        function selectPresetIcon(iconClass, el) {
            document.querySelectorAll('#addPresetGrid .preset-icon-chip').forEach(c => c.classList.remove('selected'));
            if (el) el.classList.add('selected');

            document.getElementById('addPresetIconInput').value = iconClass;
            activeMarkerPin.innerHTML = `<i class="bi ${iconClass}" id="activeMarkerIcon"></i>`;
            document.getElementById('livePreviewBadge').innerHTML = `<i class="bi ${iconClass}"></i>`;
        }

        function handleCustomIconChange(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    activeMarkerPin.innerHTML = `<img src="${e.target.result}" alt="Icon">`;
                    document.getElementById('livePreviewBadge').innerHTML = `<img src="${e.target.result}" alt="Icon">`;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // --- Photo Handling ---
        function handleAddPhotoChange(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('addPhotoHint').style.display = 'none';
                    document.getElementById('addPhotoPreviewWrap').style.display = 'block';
                    document.getElementById('addPhotoPreviewImg').src = e.target.result;
                    
                    const badge = document.getElementById('livePreviewPhotoBadge');
                    badge.textContent = 'Photo Attached';
                    badge.classList.remove('no-photo');
                    badge.classList.add('has-photo');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function removeAddPhoto(e) {
            e.stopPropagation();
            document.getElementById('addLandmarkPhotoInput').value = '';
            document.getElementById('addPhotoPreviewWrap').style.display = 'none';
            document.getElementById('addPhotoHint').style.display = 'flex';
            
            const badge = document.getElementById('livePreviewPhotoBadge');
            badge.textContent = 'No Photo';
            badge.classList.remove('has-photo');
            badge.classList.add('no-photo');
        }

        // --- Live Preview & Dimensions ---
        function updateLivePreview() {
            const nameVal = nameInput.value.trim() || 'New Landmark';
            document.getElementById('livePreviewName').textContent = nameVal;
            activeMarkerBadge.textContent = nameVal;
        }

        function updateActiveMarkerDimensions() {
            const w = parseInt(widthInput.value) || 40;
            const h = parseInt(heightInput.value) || 40;
            activeMarkerPin.style.width = w + 'px';
            activeMarkerPin.style.height = h + 'px';
        }

        // --- Locate on Map Action ---
        function highlightMapPin(id, top, left) {
            const marker = document.getElementById('marker-' + id);
            if (!marker) {
                showToast('Landmark pin is currently hidden/inactive.', true);
                return;
            }

            // Scroll map into view smoothly
            mapContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });

            // Trigger highlight bounce animation
            marker.classList.remove('highlight-ping');
            void marker.offsetWidth; // trigger reflow
            marker.classList.add('highlight-ping');

            setTimeout(() => {
                marker.classList.remove('highlight-ping');
            }, 2500);

            showToast(`Pin located at Top: ${top}%, Left: ${left}%`);
        }

        // --- Edit Modal Logic ---
        function openEditModal(leg) {
            if (typeof leg === 'number' || typeof leg === 'string') {
                leg = ALL_LEGENDS.find(l => l.id == leg) || leg;
            }
            document.getElementById('editLegendId').value = leg.id;
            document.getElementById('editNameInput').value = leg.name;
            document.getElementById('editTopPositionInput').value = leg.top_position;
            document.getElementById('editLeftPositionInput').value = leg.left_position;
            document.getElementById('editMarkerWidthInput').value = leg.marker_width || 40;
            document.getElementById('editMarkerHeightInput').value = leg.marker_height || 40;
            document.getElementById('editDisplayOrderInput').value = leg.display_order;
            document.getElementById('editIsActiveInput').value = leg.is_active;
            document.getElementById('editRemoveLandmarkImage').value = '0';

            // Color
            const col = (leg.color_hex || '#6366f1').toUpperCase();
            document.getElementById('editColorNative').value = col;
            document.getElementById('editColorHexText').value = col;

            // Highlight palette in modal
            document.querySelectorAll('#editPaletteContainer .palette-chip').forEach(c => {
                c.classList.toggle('active', c.getAttribute('data-color').toUpperCase() === col);
            });

            // Icon
            const isCustom = (leg.icon_path.indexOf('uploads/') === 0);
            if (isCustom) {
                switchModalIconTab('upload');
            } else {
                switchModalIconTab('preset');
                document.querySelectorAll('#modalPresetGrid .preset-icon-chip').forEach(c => {
                    c.classList.toggle('selected', c.getAttribute('data-icon') === leg.icon_path);
                });
                document.getElementById('editPresetIconInput').value = leg.icon_path;
            }

            // Photo preview
            if (leg.image_path) {
                const photoUrl = (leg.image_path.indexOf('uploads/') === 0 ? leg.image_path : '../' + leg.image_path.replace(/^\/+/, ''));
                document.getElementById('modalPhotoPreviewImg').src = photoUrl;
                document.getElementById('modalPhotoPreviewWrap').style.display = 'block';
                document.getElementById('modalPhotoHint').style.display = 'none';
            } else {
                document.getElementById('modalPhotoPreviewWrap').style.display = 'none';
                document.getElementById('modalPhotoHint').style.display = 'flex';
            }

            document.getElementById('editLegendModal').classList.add('show');
        }

        function closeEditModal() {
            document.getElementById('editLegendModal').classList.remove('show');
        }

        function switchModalIconTab(tab) {
            document.getElementById('editIconType').value = tab;
            const pBtn = document.getElementById('modalTabPresetBtn');
            const uBtn = document.getElementById('modalTabUploadBtn');
            const pGrid = document.getElementById('modalPresetGrid');
            const uTab = document.getElementById('modalUploadTabContent');

            if (tab === 'preset') {
                pBtn.classList.add('active');
                uBtn.classList.remove('active');
                pGrid.style.display = 'grid';
                uTab.style.display = 'none';
            } else {
                uBtn.classList.add('active');
                pBtn.classList.remove('active');
                pGrid.style.display = 'none';
                uTab.style.display = 'flex';
            }
        }

        function selectModalPresetIcon(iconClass, el) {
            document.querySelectorAll('#modalPresetGrid .preset-icon-chip').forEach(c => c.classList.remove('selected'));
            if (el) el.classList.add('selected');
            document.getElementById('editPresetIconInput').value = iconClass;
        }

        function syncEditColorFromNative(val) {
            document.getElementById('editColorHexText').value = val.toUpperCase();
        }

        function syncEditColorFromText(val) {
            if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                document.getElementById('editColorNative').value = val.toUpperCase();
            }
        }

        function selectEditPaletteColor(hex) {
            document.getElementById('editColorNative').value = hex.toUpperCase();
            document.getElementById('editColorHexText').value = hex.toUpperCase();
            document.querySelectorAll('#editPaletteContainer .palette-chip').forEach(c => {
                c.classList.toggle('active', c.getAttribute('data-color').toUpperCase() === hex.toUpperCase());
            });
        }

        function handleModalPhotoChange(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('modalPhotoHint').style.display = 'none';
                    document.getElementById('modalPhotoPreviewWrap').style.display = 'block';
                    document.getElementById('modalPhotoPreviewImg').src = e.target.result;
                    document.getElementById('editRemoveLandmarkImage').value = '0';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function removeModalPhoto(e) {
            e.stopPropagation();
            document.getElementById('modalLandmarkPhotoInput').value = '';
            document.getElementById('modalPhotoPreviewWrap').style.display = 'none';
            document.getElementById('modalPhotoHint').style.display = 'flex';
            document.getElementById('editRemoveLandmarkImage').value = '1';
        }

        // --- Lightbox Modal Logic ---
        function viewLandmarkImage(id) {
            const leg = ALL_LEGENDS.find(l => l.id == id);
            if (!leg) return;

            document.getElementById('lightboxTitle').textContent = leg.name;
            const badge = document.getElementById('lightboxBadge');
            badge.style.backgroundColor = leg.color_hex || '#6366f1';

            if (leg.icon_path.indexOf('uploads/') === 0) {
                badge.innerHTML = `<img src="${leg.icon_path}" alt="Icon">`;
            } else {
                badge.innerHTML = `<i class="bi ${leg.icon_path}"></i>`;
            }

            document.getElementById('lightboxMetaCoords').textContent = `Top: ${leg.top_position}% | Left: ${leg.left_position}% | Order #${leg.display_order}`;

            const imgContainer = document.getElementById('lightboxImageContainer');
            const emptyState   = document.getElementById('lightboxEmptyState');
            const imgTag       = document.getElementById('lightboxImg');

            if (leg.image_path) {
                const src = (leg.image_path.indexOf('uploads/') === 0 ? leg.image_path : '../' + leg.image_path.replace(/^\/+/, ''));
                imgTag.src = src;
                imgContainer.style.display = 'block';
                emptyState.style.display = 'none';
            } else {
                imgContainer.style.display = 'none';
                emptyState.style.display = 'block';
            }

                        // Connect modal Edit & Delete buttons
            const editBtn = document.getElementById('lightboxEditBtn');
            const delBtn = document.getElementById('lightboxDeleteBtn');
            if (editBtn) {
                editBtn.onclick = function() {
                    closeLandmarkPhotoModal();
                    openEditModal(id);
                };
            }
            if (delBtn) {
                delBtn.onclick = function() {
                    deleteLegendItem(id, leg.name);
                };
            }

            document.getElementById('landmarkPhotoModal').classList.add('show');
        }

        function closeLandmarkPhotoModal() {
            document.getElementById('landmarkPhotoModal').classList.remove('show');
        }

        function handleBackdropClick(e, modalId) {
            if (e.target.id === modalId) {
                document.getElementById(modalId).classList.remove('show');
            }
        }

        // Keyboard navigation (Esc key to close modals)
        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEditModal();
                closeLandmarkPhotoModal();
            }
        });

        // --- Table Filter ---
        function filterLegendsTable(query) {
            query = query.toLowerCase().trim();
            document.querySelectorAll('#legendsTable tbody tr[data-name]').forEach(row => {
                const name = row.getAttribute('data-name');
                row.style.display = (name.indexOf(query) !== -1) ? '' : 'none';
            });
        }

                        // --- Complete Landmark Deletion (Map Canvas & Table Sync) ---
        function deleteLegendItem(id, name) {
            if (!confirm(`Are you sure you want to completely delete landmark "${name}" from the spot map?\n\nThis will remove the pin from the Admin aerial map, the table, and the public spot map.`)) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete_legend');
            formData.append('id', id);
            formData.append('is_ajax', '1');

            fetch('Legend.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // 1. Remove marker pin from aerial map in Admin/Legend.php
                    const markers = document.querySelectorAll('#marker-' + id + ', .saved-legend-marker[data-id="' + id + '"]');
                    markers.forEach(m => {
                        m.style.transition = 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease';
                        m.style.transform = 'translate(-50%, -100%) scale(0)';
                        m.style.opacity = '0';
                        setTimeout(() => m.remove(), 300);
                    });

                    // 2. Remove row from Existing Spot Map Legends table
                    const rows = document.querySelectorAll('#table-row-' + id + ', #legend-row-' + id + ', tr[data-id="' + id + '"]');
                    rows.forEach(r => {
                        r.style.transition = 'opacity 0.3s ease';
                        r.style.opacity = '0';
                        setTimeout(() => {
                            r.remove();
                            const tbody = document.querySelector('#legendsTable tbody');
                            if (tbody && tbody.querySelectorAll('tr[id^="table-row-"], tr[data-id]').length === 0) {
                                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 35px 20px; color:#94a3b8; font-style: italic;">No landmarks currently added. Click on the map canvas to place a new landmark.</td></tr>';
                            }
                        }, 300);
                    });

                    // 3. Close photo modal if open
                    closeLandmarkPhotoModal();

                    // 4. Update ALL_LEGENDS array
                    const idx = ALL_LEGENDS.findIndex(l => l.id == id);
                    if (idx !== -1) ALL_LEGENDS.splice(idx, 1);

                    // 5. Update stat pills
                    updateStatsDisplay();

                    showToast(`Landmark "${name}" was completely deleted from the map and table.`);
                } else {
                    submitDeleteFallback(id);
                }
            })
            .catch(err => {
                submitDeleteFallback(id);
            });
        }

        function submitDeleteFallback(id) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'Legend.php';
            const act = document.createElement('input');
            act.type = 'hidden'; act.name = 'action'; act.value = 'delete_legend';
            const idInput = document.createElement('input');
            idInput.type = 'hidden'; idInput.name = 'id'; idInput.value = id;
            form.appendChild(act);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }

        function updateStatsDisplay() {
            const totalCount = ALL_LEGENDS.length;
            const activeCount = ALL_LEGENDS.filter(l => l.is_active == 1).length;

            const statPills = document.querySelectorAll('.legend-header-stats .stat-pill');
            if (statPills.length >= 2) {
                const totalStrong = statPills[0].querySelector('strong');
                if (totalStrong) totalStrong.textContent = totalCount;
                const activeStrong = statPills[1].querySelector('strong');
                if (activeStrong) activeStrong.textContent = activeCount;
            }
        }

        // --- Toast System ---
        function showToast(msg, isError = false) {
            const toast = document.getElementById('adminToast');
            const icon = document.getElementById('adminToastIcon');
            const text = document.getElementById('adminToastMsg');

            toast.style.background = isError ? '#ef4444' : '#10b981';
            icon.className = isError ? 'bi bi-exclamation-triangle-fill' : 'bi bi-check-circle-fill';
            text.textContent = msg;

            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3500);
        }

        // Check URL parameters for toast notification
        document.addEventListener('DOMContentLoaded', function() {
            setPinPosition(50.00, 50.00);
            updateLivePreview();

            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('msg') === 'added') {
                showToast('Legend landmark successfully pinned to Spot Map!');
            } else if (urlParams.get('msg') === 'updated') {
                showToast('Legend landmark details updated successfully!');
            } else if (urlParams.get('msg') === 'deleted') {
                showToast('Legend landmark removed from Spot Map.');
            } else if (urlParams.get('msg') === 'status_updated') {
                showToast('Landmark visibility status updated.');
            } else if (urlParams.get('error')) {
                showToast('An error occurred. Please check input values.', true);
            }
        });
    </script>
</body>
</html>
