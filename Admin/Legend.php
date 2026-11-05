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
    global $pdo;
    try {
        $export_data = [];
        $rows = [];
        if (isset($pdo) && $pdo instanceof PDO) {
            $stmt = $pdo->query("SELECT * FROM `sm_legends` WHERE `is_active` = 1 ORDER BY `display_order` ASC, `id` ASC");
            if ($stmt) $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
            $res = $conn->query("SELECT * FROM `sm_legends` WHERE `is_active` = 1 ORDER BY `display_order` ASC, `id` ASC");
            if ($res) {
                while ($r = $res->fetch_assoc()) $rows[] = $r;
            }
        }

        foreach ($rows as $row) {
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
                'purok'         => 'Legend',
                'houseNumber'   => $row['name'],
                'markerImage'   => $row['icon_path'],
                'topPos'        => (float)$row['top_position'],
                'leftPos'       => (float)$row['left_position'],
                'markerWidth'   => (int)$row['marker_width'],
                'markerHeight'  => (int)$row['marker_height'],
                'dateAdded'     => substr($row['created_at'] ?? date('Y-m-d'), 0, 10)
            ];
            $export_data[] = $export_row;

            $leg_id = (int)$row['id'];
            if (isset($pdo) && $pdo instanceof PDO) {
                $chk = $pdo->prepare("SELECT id FROM `sm_legend_spots` WHERE `legend_id` = ? LIMIT 1");
                $chk->execute([$leg_id]);
                $exists = $chk->fetchColumn();
                if ($exists) {
                    $up = $pdo->prepare("UPDATE `sm_legend_spots` SET `title` = ?, `top_position` = ?, `left_position` = ?, `marker_width` = ?, `marker_height` = ?, `marker_image` = ?, `house_image` = ? WHERE `legend_id` = ?");
                    $up->execute([$row['name'], $row['top_position'], $row['left_position'], $row['marker_width'], $row['marker_height'], $row['icon_path'], $row['image_path'], $leg_id]);
                } else {
                    $ins = $pdo->prepare("INSERT INTO `sm_legend_spots` (`legend_id`, `title`, `top_position`, `left_position`, `marker_width`, `marker_height`, `marker_image`, `house_image`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $ins->execute([$leg_id, $row['name'], $row['top_position'], $row['left_position'], $row['marker_width'], $row['marker_height'], $row['icon_path'], $row['image_path']]);
                }
            }
        }

        if (isset($pdo) && $pdo instanceof PDO) {
            $pdo->exec("DELETE FROM `sm_legend_spots` WHERE `legend_id` NOT IN (SELECT `id` FROM `sm_legends`)");
        }
        @file_put_contents($json_file_path, json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    } catch (\Throwable $e) {
        // Fail silently
    }
}

// Auto-seed default landmarks ONLY once on initial system setup (never re-seed after manual deletion)
$conn->query("CREATE TABLE IF NOT EXISTS `sm_settings` (
    `setting_key` varchar(50) NOT NULL PRIMARY KEY,
    `setting_value` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

// Mark legends as permanently initialized so dummy records never auto-seed
$conn->query("INSERT INTO `sm_settings` (`setting_key`, `setting_value`) VALUES ('legends_seeded', '1') ON DUPLICATE KEY UPDATE `setting_value` = '1'");

// Regional Zoom Sequence Step Settings
$zoom_step_defaults = [
    0 => ['name' => 'Philippines Map', 'target' => 'Mindanao', 'image' => '../Images/Map/Philippines.png', 'top' => 74.00, 'left' => 60.00, 'label' => 'Mindanao'],
    1 => ['name' => 'Mindanao Map', 'target' => 'Zamboanga del Sur', 'image' => '../Images/Map/mindanao.jpg', 'top' => 57.00, 'left' => 30.50, 'label' => 'Zamboanga del Sur'],
    2 => ['name' => 'Zamboanga del Sur Map', 'target' => 'Lapuyan', 'image' => '../Images/Map/Zamboanga del Sur.jpg', 'top' => 46.50, 'left' => 51.20, 'label' => 'Lapuyan'],
    3 => ['name' => 'Lapuyan Map', 'target' => 'Barangay Tabon', 'image' => '../Images/Map/Lapuyan.jpg', 'top' => 45.80, 'left' => 33.80, 'label' => 'Barangay Tabon'],
];
$zoom_steps = $zoom_step_defaults;
try {
    $q_zoom = $conn->query("SELECT setting_key, setting_value FROM sm_settings WHERE setting_key LIKE 'zoom_%'");
    if ($q_zoom) {
        while ($zr = $q_zoom->fetch_assoc()) {
            $zk = $zr['setting_key'];
            $zv = trim($zr['setting_value']);
            foreach ([0, 1, 2, 3] as $idx) {
                if ($zk === "zoom_pin_step{$idx}_top" && $zv !== '') $zoom_steps[$idx]['top'] = (float)$zv;
                if ($zk === "zoom_pin_step{$idx}_left" && $zv !== '') $zoom_steps[$idx]['left'] = (float)$zv;
                if ($zk === "zoom_pin_step{$idx}_label" && $zv !== '') $zoom_steps[$idx]['label'] = $zv;
                if ($zk === "zoom_map_step{$idx}_image" && !empty($zv) && file_exists(__DIR__ . '/../' . ltrim($zv, '/'))) {
                    $zoom_steps[$idx]['image'] = '../' . ltrim($zv, '/');
                }
            }
        }
    }
} catch (\Throwable $e) {}

// Aerial Legend Map Settings
$settingKey = 'legend_map_image';
$custom_legend_map = null;
$default_legend_map = "../public/image/Maruing Map/Aerial View.png";
$legend_map_src = $default_legend_map;
try {
    $q_map = $conn->query("SELECT setting_value FROM sm_settings WHERE setting_key = '" . $conn->real_escape_string($settingKey) . "' LIMIT 1");
    if ($q_map && $r_map = $q_map->fetch_assoc()) {
        $cVal = trim($r_map['setting_value']);
        $chk = __DIR__ . '/../' . ltrim($cVal, '/');
        if (!empty($cVal) && file_exists($chk)) {
            $custom_legend_map = $cVal;
            $legend_map_src = '../' . ltrim($cVal, '/');
        }
    }
} catch (\Throwable $e) {}

// --- 2. Request Handling (POST / AJAX) ---
$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_POST['is_ajax']) && $_POST['is_ajax'] == '1');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    // Action: Update Legend Aerial Map Image
        // Action: Save Regional Zoom Sequence Pins
    if ($action === 'save_zoom_pins') {
        foreach ([0, 1, 2, 3] as $idx) {
            $topVal = isset($_POST["step_{$idx}_top"]) ? floatval($_POST["step_{$idx}_top"]) : null;
            $leftVal = isset($_POST["step_{$idx}_left"]) ? floatval($_POST["step_{$idx}_left"]) : null;
            $labelVal = isset($_POST["step_{$idx}_label"]) ? trim($_POST["step_{$idx}_label"]) : null;
            if ($topVal !== null) {
                $topVal = max(0, min(100, $topVal));
                $k = "zoom_pin_step{$idx}_top";
                $v = sprintf("%.2f", $topVal);
                $stmt = $conn->prepare("INSERT INTO sm_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->bind_param("sss", $k, $v, $v);
                $stmt->execute();
            }
            if ($leftVal !== null) {
                $leftVal = max(0, min(100, $leftVal));
                $k = "zoom_pin_step{$idx}_left";
                $v = sprintf("%.2f", $leftVal);
                $stmt = $conn->prepare("INSERT INTO sm_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->bind_param("sss", $k, $v, $v);
                $stmt->execute();
            }
            if (!empty($labelVal)) {
                $k = "zoom_pin_step{$idx}_label";
                $stmt = $conn->prepare("INSERT INTO sm_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->bind_param("sss", $k, $labelVal, $labelVal);
                $stmt->execute();
            }
        }
        header("Location: Legend.php?zoom_pins_saved=1");
        exit();
    }

    // Action: Reset Regional Zoom Sequence Pins to Default
    // Action: Save a Single Zoom Pin (Instant Save per Stage)
    if ($action === 'save_single_zoom_pin') {
        $idx = isset($_POST['stage_index']) ? intval($_POST['stage_index']) : 0;
        $idx = max(0, min(3, $idx));

        $topVal = isset($_POST['top']) ? floatval($_POST['top']) : null;
        $leftVal = isset($_POST['left']) ? floatval($_POST['left']) : null;
        $labelVal = isset($_POST['label']) ? trim($_POST['label']) : null;

        if ($topVal !== null) {
            $topVal = max(0, min(100, $topVal));
            $k = "zoom_pin_step{$idx}_top";
            $v = sprintf("%.2f", $topVal);
            $stmt = $conn->prepare("INSERT INTO sm_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("sss", $k, $v, $v);
            $stmt->execute();
        }
        if ($leftVal !== null) {
            $leftVal = max(0, min(100, $leftVal));
            $k = "zoom_pin_step{$idx}_left";
            $v = sprintf("%.2f", $leftVal);
            $stmt = $conn->prepare("INSERT INTO sm_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("sss", $k, $v, $v);
            $stmt->execute();
        }
        if (!empty($labelVal)) {
            $k = "zoom_pin_step{$idx}_label";
            $stmt = $conn->prepare("INSERT INTO sm_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("sss", $k, $labelVal, $labelVal);
            $stmt->execute();
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'success',
            'message' => 'Pin saved successfully!',
            'stage_index' => $idx,
            'top' => $topVal,
            'left' => $leftVal,
            'label' => $labelVal
        ]);
        exit();
    }

    if ($action === 'reset_zoom_pins') {
        $conn->query("DELETE FROM sm_settings WHERE setting_key LIKE 'zoom_%'");
        header("Location: Legend.php?zoom_pins_reset=1");
        exit();
    }

if ($action === 'update_legend_map') {
        if (!empty($_FILES['legend_map_image']['name']) && $_FILES['legend_map_image']['error'] === UPLOAD_ERR_OK) {
            $f = $_FILES['legend_map_image'];
            $maxBytes = 5 * 1024 * 1024;
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $f['tmp_name']);
            finfo_close($finfo);
            if ($f['size'] > $maxBytes) {
                header("Location: Legend.php?error=file_too_large");
                exit();
            } elseif (!in_array($ext, $allowedExts) || !in_array($mime, $allowedMimes)) {
                header("Location: Legend.php?error=invalid_file_type");
                exit();
            } else {
                $uploadDir = __DIR__ . '/uploads/legend_maps/';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
                $newFileName = 'map_legend_' . time() . '.' . $ext;
                $destPath = $uploadDir . $newFileName;
                if (move_uploaded_file($f['tmp_name'], $destPath)) {
                    $dbRelPath = 'Admin/uploads/legend_maps/' . $newFileName;
                    $stmt = $conn->prepare("INSERT INTO sm_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                    $stmt->bind_param("sss", $settingKey, $dbRelPath, $dbRelPath);
                    $stmt->execute();
                    header("Location: Legend.php?map_updated=1");
                    exit();
                } else {
                    header("Location: Legend.php?error=upload_failed");
                    exit();
                }
            }
        } else {
            header("Location: Legend.php?error=no_file");
            exit();
        }
    }

    // Action: Reset Legend Aerial Map Image
    if ($action === 'reset_legend_map') {
        $sel = $conn->query("SELECT setting_value FROM sm_settings WHERE setting_key = '" . $conn->real_escape_string($settingKey) . "' LIMIT 1");
        if ($sel && $row = $sel->fetch_assoc()) {
            $oldPath = __DIR__ . '/../' . ltrim($row['setting_value'], '/');
            if (file_exists($oldPath)) @unlink($oldPath);
        }
        $conn->query("DELETE FROM sm_settings WHERE setting_key = '" . $conn->real_escape_string($settingKey) . "'");
        header("Location: Legend.php?map_reset=1");
        exit();
    }

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

        $inserted_id = 0;
        if (isset($pdo) && $pdo instanceof PDO) {
            $stmt = $pdo->prepare("INSERT INTO `sm_legends` (`name`, `icon_path`, `color_hex`, `is_active`, `display_order`, `top_position`, `left_position`, `marker_width`, `marker_height`, `image_path`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $icon_path, $color_hex, $is_active, $display_order, $top_position, $left_position, $marker_width, $marker_height, $image_path]);
            $inserted_id = (int)$pdo->lastInsertId();
        } elseif (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
            $stmt = $conn->prepare("INSERT INTO `sm_legends` (`name`, `icon_path`, `color_hex`, `is_active`, `display_order`, `top_position`, `left_position`, `marker_width`, `marker_height`, `image_path`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssiiddiis", $name, $icon_path, $color_hex, $is_active, $display_order, $top_position, $left_position, $marker_width, $marker_height, $image_path);
            $stmt->execute();
            $inserted_id = $stmt->insert_id;
        }

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

        if (empty($name)) {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Landmark name cannot be empty.']);
                exit();
            }
            header("Location: Legend.php?error=invalid_data");
            exit();
        }

        // Fetch current record safely via PDO or MySQLi
        $curr = null;
        if (isset($pdo) && $pdo instanceof PDO) {
            if ($id > 0) {
                $st = $pdo->prepare("SELECT * FROM `sm_legends` WHERE `id` = ? LIMIT 1");
                $st->execute([$id]);
                $curr = $st->fetch(PDO::FETCH_ASSOC);
            }
            if (!$curr) {
                $st = $pdo->prepare("SELECT * FROM `sm_legends` WHERE `name` = ? LIMIT 1");
                $st->execute([$name]);
                $curr = $st->fetch(PDO::FETCH_ASSOC);
                if ($curr) $id = (int)$curr['id'];
            }
        }
        if (!$curr && isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
            if ($id > 0) {
                $res = $conn->query("SELECT * FROM `sm_legends` WHERE `id` = $id LIMIT 1");
                if ($res) $curr = $res->fetch_assoc();
            }
            if (!$curr) {
                $esc = $conn->real_escape_string($name);
                $res = $conn->query("SELECT * FROM `sm_legends` WHERE `name` = '$esc' LIMIT 1");
                if ($res) {
                    $curr = $res->fetch_assoc();
                    if ($curr) $id = (int)$curr['id'];
                }
            }
        }

        if (!$curr && $id <= 0) {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Legend record not found.']);
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
        } elseif ($icon_type === 'retain' && $curr) {
            $icon_path = $curr['icon_path'];
        }

        // Handle photo
        $image_path = $curr ? $curr['image_path'] : null;
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

        // Execute update
        if (isset($pdo) && $pdo instanceof PDO) {
            if ($id > 0) {
                $up = $pdo->prepare("UPDATE `sm_legends` SET `name` = ?, `icon_path` = ?, `color_hex` = ?, `is_active` = ?, `display_order` = ?, `top_position` = ?, `left_position` = ?, `marker_width` = ?, `marker_height` = ?, `image_path` = ? WHERE `id` = ?");
                $up->execute([$name, $icon_path, $color_hex, $is_active, $display_order, $top_position, $left_position, $marker_width, $marker_height, $image_path, $id]);
            }
        }
        if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
            if ($id > 0) {
                $esc_name = $conn->real_escape_string($name);
                $esc_icon = $conn->real_escape_string($icon_path);
                $esc_color = $conn->real_escape_string($color_hex);
                $esc_img = $image_path !== null ? "'" . $conn->real_escape_string($image_path) . "'" : "NULL";
                $conn->query("UPDATE `sm_legends` SET `name` = '$esc_name', `icon_path` = '$esc_icon', `color_hex` = '$esc_color', `is_active` = $is_active, `display_order` = $display_order, `top_position` = $top_position, `left_position` = $left_position, `marker_width` = $marker_width, `marker_height` = $marker_height, `image_path` = $esc_img WHERE `id` = $id");
            }
        }

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
            if (isset($pdo) && $pdo instanceof PDO) {
                $pdo->prepare("UPDATE `sm_legends` SET `is_active` = IF(`is_active` = 1, 0, 1) WHERE `id` = ?")->execute([$id]);
            }
            if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
                $conn->query("UPDATE `sm_legends` SET `is_active` = IF(`is_active` = 1, 0, 1) WHERE `id` = $id");
            }
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
        $name = trim(urldecode($_POST['name'] ?? ''));

        if ($id <= 0 && !empty($name)) {
            if (isset($pdo) && $pdo instanceof PDO) {
                $st = $pdo->prepare("SELECT id FROM sm_legends WHERE name = ? LIMIT 1");
                $st->execute([$name]);
                $id = (int)$st->fetchColumn();
            }
        }

        if ($id > 0 || !empty($name)) {
            // Delete associated custom uploaded files
            $row_data = null;
            if (isset($pdo) && $pdo instanceof PDO) {
                $st = $pdo->prepare("SELECT icon_path, image_path FROM sm_legends WHERE id = ? OR name = ? LIMIT 1");
                $st->execute([$id, $name]);
                $row_data = $st->fetch(PDO::FETCH_ASSOC);
            }
            if (!$row_data && isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
                $fetch_row = $conn->query("SELECT icon_path, image_path FROM sm_legends WHERE id = $id LIMIT 1");
                if ($fetch_row) $row_data = $fetch_row->fetch_assoc();
            }

            if ($row_data) {
                if (!empty($row_data['image_path']) && strpos($row_data['image_path'], 'uploads/landmarks/') === 0) {
                    $img_full = __DIR__ . '/' . $row_data['image_path'];
                    if (file_exists($img_full)) @unlink($img_full);
                }
                if (!empty($row_data['icon_path']) && strpos($row_data['icon_path'], 'uploads/legends/') === 0) {
                    $ico_full = __DIR__ . '/' . $row_data['icon_path'];
                    if (file_exists($ico_full)) @unlink($ico_full);
                }
            }

            if (isset($pdo) && $pdo instanceof PDO) {
                if ($id > 0) {
                    $pdo->prepare("DELETE FROM sm_legends WHERE id = ?")->execute([$id]);
                    $pdo->prepare("DELETE FROM sm_legend_spots WHERE legend_id = ?")->execute([$id]);
                } elseif (!empty($name)) {
                    $pdo->prepare("DELETE FROM sm_legends WHERE name = ?")->execute([$name]);
                    $pdo->prepare("DELETE FROM sm_legend_spots WHERE title = ?")->execute([$name]);
                }
            }
            if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
                if ($id > 0) {
                    $conn->query("DELETE FROM `sm_legends` WHERE `id` = $id");
                    $conn->query("DELETE FROM `sm_legend_spots` WHERE `legend_id` = $id");
                } elseif (!empty($name)) {
                    $esc = $conn->real_escape_string($name);
                    $conn->query("DELETE FROM `sm_legends` WHERE `name` = '$esc'");
                    $conn->query("DELETE FROM `sm_legend_spots` WHERE `title` = '$esc'");
                }
            }
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
$all_legends = [];
$active_count = 0;
$hidden_count = 0;

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $st = $pdo->query("SELECT * FROM `sm_legends` ORDER BY `display_order` ASC, `id` ASC");
        if ($st) {
            $all_legends = $st->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (\Throwable $e) {}
}

if (empty($all_legends) && isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $legends_res = $conn->query("SELECT * FROM `sm_legends` ORDER BY `display_order` ASC, `id` ASC");
    if ($legends_res) {
        while ($row = $legends_res->fetch_assoc()) {
            $all_legends[] = $row;
        }
    }
}

foreach ($all_legends as $row) {
    if ((int)($row['is_active'] ?? 1) == 1) $active_count++;
    else $hidden_count++;
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

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.spin {
    display: inline-block;
    animation: spin 0.8s linear infinite;
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
    <link rel="stylesheet" href="Admin CSS/admin_common.css?v=<?= time() ?>">
    <script src="js/admin_mobile.js?v=<?= time() ?>" defer></script>
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

            <?php if (isset($_GET['zoom_pins_saved'])): ?>
            <div style="background: #eff6ff; border: 1.5px solid #93c5fd; border-radius: 12px; padding: 14px 20px; margin-bottom: 24px; color: #1e40af; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 12px rgba(59,130,246,0.1);">
                <div style="display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 0.95rem;">
                    <i class="bi bi-check-circle-fill" style="color: #3b82f6; font-size: 1.3rem;"></i>
                    <span>Regional zoom sequence pins updated! Visitors on the public Spot Map will now see the pin at the new coordinates on each map.</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; color:#1e40af; font-size:1.4rem; cursor:pointer; line-height:1;">&times;</button>
            </div>
        <?php elseif (isset($_GET['zoom_pins_reset'])): ?>
            <div style="background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 12px; padding: 14px 20px; margin-bottom: 24px; color: #166534; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 12px rgba(34,197,94,0.1);">
                <div style="display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 0.95rem;">
                    <i class="bi bi-arrow-counterclockwise" style="color: #16a34a; font-size: 1.3rem;"></i>
                    <span>All regional zoom sequence pins have been reset back to default official coordinates.</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; color:#166534; font-size:1.4rem; cursor:pointer; line-height:1;">&times;</button>
            </div>
        <?php elseif (isset($_GET['map_updated'])): ?>
            <div style="background: #ecfdf5; border: 1.5px solid #6ee7b7; border-radius: 12px; padding: 14px 20px; margin-bottom: 24px; color: #065f46; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 12px rgba(16,185,129,0.1);">
                <div style="display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 0.95rem;">
                    <i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 1.3rem;"></i>
                    <span>Aerial Map image updated successfully! Both the Admin canvas and public Spot Map now show the new background map.</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; color:#065f46; font-size:1.4rem; cursor:pointer; line-height:1;">&times;</button>
            </div>
        <?php elseif (isset($_GET['map_reset'])): ?>
            <div style="background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 12px; padding: 14px 20px; margin-bottom: 24px; color: #166534; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 12px rgba(34,197,94,0.1);">
                <div style="display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 0.95rem;">
                    <i class="bi bi-arrow-counterclockwise" style="color: #16a34a; font-size: 1.3rem;"></i>
                    <span>Aerial Map image has been reset back to the default background map.</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; color:#166534; font-size:1.4rem; cursor:pointer; line-height:1;">&times;</button>
            </div>
        <?php elseif (isset($_GET['error']) && in_array($_GET['error'], ['file_too_large', 'invalid_file_type', 'upload_failed', 'no_file'])): ?>
            <div style="background: #fef2f2; border: 1.5px solid #fca5a5; border-radius: 12px; padding: 14px 20px; margin-bottom: 24px; color: #991b1b; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 12px rgba(239,68,68,0.1);">
                <div style="display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 0.95rem;">
                    <i class="bi bi-exclamation-triangle-fill" style="color: #ef4444; font-size: 1.3rem;"></i>
                    <span>
                        <?php
                        $err = $_GET['error'];
                        if ($err === 'file_too_large') echo 'The uploaded map image exceeds the maximum 5 MB file size limit.';
                        elseif ($err === 'invalid_file_type') echo 'Invalid image format. Only JPG, PNG, and WEBP formats are accepted.';
                        elseif ($err === 'upload_failed') echo 'Failed to upload map image to server. Please check directory permissions.';
                        elseif ($err === 'no_file') echo 'Please select a valid image file before submitting.';
                        else echo 'An error occurred while updating the map.';
                        ?>
                    </span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; color:#991b1b; font-size:1.4rem; cursor:pointer; line-height:1;">&times;</button>
            </div>
        <?php endif; ?>

        <!-- 2. Interactive Aerial Spot Map Interface (#aerialMapContainer) -->
            <div class="aerial-map-card">
                <div class="map-card-toolbar">
                    <div class="map-toolbar-info">
                        <span class="map-toolbar-title"><i class="bi bi-map-fill" style="color: #4f46e5;"></i> Aerial Viewport Preview</span>
                        <span class="map-coordinate-live" id="liveCoordBadge">Active Pin: Top 50.00%, Left 50.00%</span>
                    </div>
                    <div class="map-toolbar-actions">
                        <button type="button" class="btn-map-action" onclick="openZoomPinsModal()" title="Adjust Overview & Regional Zoom Pins" style="background: #1e3a8a; color: #ffffff; border-color: #1e3a8a; font-weight: 600;">
                            <i class="bi bi-geo-alt-fill" style="color: #60a5fa;"></i> Adjust Overview Pins
                        </button>
                        <button type="button" class="btn-map-action" onclick="openChangeMapModal()" title="Change Aerial Map Image" style="background: #223c3b; color: #ffffff; border-color: #223c3b; font-weight: 600;">
                            <i class="bi bi-image" style="color: #f59e0b;"></i> Change Map Image
                        </button>
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
                    <img src="<?= htmlspecialchars($legend_map_src) ?>" 
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
                                                    onclick="openEditModal(<?= (int)$leg['id'] ?>)"
                                                    title="Edit Legend">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </button>

                                            <!-- Toggle Visibility -->
                                            <form method="POST" action="Legend.php" style="display:inline;">
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

    <!-- 8. Adjust Regional Zoom Pins Modal (#zoomPinsModal) -->
    <div class="modal-backdrop" id="zoomPinsModal" onclick="handleBackdropClick(event, 'zoomPinsModal')">
        <div class="modal-dialog" style="max-width: 780px; width: 95%; border-radius: 16px; overflow: hidden;">
            <!-- Header -->
            <div class="modal-header" style="background: #1e3a8a; color: #ffffff; border-bottom: 3px solid #3b82f6; padding: 16px 22px;">
                <div>
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #ffffff; display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-geo-alt-fill" style="color: #60a5fa;"></i> Adjust Overview Zoom Sequence Pins
                    </h3>
                    <p style="margin: 4px 0 0; font-size: 0.8rem; color: #bfdbfe;">Select a map stage, then click or drag the red pin to set where visitors click to zoom deeper.</p>
                </div>
                <button type="button" class="btn-close-modal" onclick="closeZoomPinsModal()" style="background: none; border: none; color: #ffffff; font-size: 1.5rem; cursor: pointer; line-height: 1;">&times;</button>
            </div>

            <!-- Form -->
            <form method="post" action="Legend.php" style="padding: 20px;">
                <input type="hidden" name="action" value="save_zoom_pins">

                <!-- Stage Tabs Navigation -->
                <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
                    <button type="button" class="zoom-tab-btn active" onclick="switchZoomPinTab(0)" id="zoomTabBtn_0" style="padding: 8px 14px; border-radius: 8px; border: 1.5px solid #3b82f6; background: #3b82f6; color: #ffffff; font-weight: 700; font-size: 0.82rem; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                        <span>1. Philippines &rarr; Mindanao</span>
                    </button>
                    <button type="button" class="zoom-tab-btn" onclick="switchZoomPinTab(1)" id="zoomTabBtn_1" style="padding: 8px 14px; border-radius: 8px; border: 1.5px solid #cbd5e1; background: #f8fafc; color: #475569; font-weight: 600; font-size: 0.82rem; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                        <span>2. Mindanao &rarr; Zamboanga</span>
                    </button>
                    <button type="button" class="zoom-tab-btn" onclick="switchZoomPinTab(2)" id="zoomTabBtn_2" style="padding: 8px 14px; border-radius: 8px; border: 1.5px solid #cbd5e1; background: #f8fafc; color: #475569; font-weight: 600; font-size: 0.82rem; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                        <span>3. Zamboanga &rarr; Lapuyan</span>
                    </button>
                    <button type="button" class="zoom-tab-btn" onclick="switchZoomPinTab(3)" id="zoomTabBtn_3" style="padding: 8px 14px; border-radius: 8px; border: 1.5px solid #cbd5e1; background: #f8fafc; color: #475569; font-weight: 600; font-size: 0.82rem; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                        <span>4. Lapuyan &rarr; Tabon</span>
                    </button>
                </div>

                <!-- Interactive Canvas Window -->
                <div id="zoomPinCanvasContainer" style="position: relative; width: 100%; height: 380px; background: #0f172a; border-radius: 12px; overflow: hidden; border: 2px solid #334155; box-shadow: inset 0 2px 10px rgba(0,0,0,0.5); cursor: crosshair;">
                    <!-- Background Map Image -->
                    <img id="zoomPinCanvasImg" src="<?= htmlspecialchars($zoom_steps[0]['image']) ?>" alt="Map Preview" style="width: 100%; height: 100%; object-fit: contain; pointer-events: none; user-select: none;">

                    <!-- Helper Badge -->
                    <div style="position: absolute; top: 12px; left: 12px; background: rgba(15,23,42,0.85); color: #93c5fd; padding: 5px 12px; border-radius: 20px; font-size: 0.76rem; font-weight: 600; backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.1); pointer-events: none;">
                        <i class="bi bi-arrows-move"></i> Drag red pin or click map to reposition
                    </div>

                    <!-- Draggable Red Pin -->
                    <div id="zoomDraggablePin" style="position: absolute; top: <?= htmlspecialchars($zoom_steps[0]['top']) ?>%; left: <?= htmlspecialchars($zoom_steps[0]['left']) ?>%; transform: translate(-50%, -100%); cursor: grab; z-index: 50; user-select: none; touch-action: none;">
                        <!-- Badge -->
                        <div id="zoomPinBadge" style="background: rgba(15, 23, 42, 0.9); color: #ffffff; padding: 3px 10px; border-radius: 14px; font-size: 11px; font-weight: 700; white-space: nowrap; margin-bottom: 3px; display: inline-flex; align-items: center; gap: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.2);">
                            <i class="bi bi-search" style="font-size: 9px; color: #f59e0b;"></i> <span id="zoomPinBadgeLabel"><?= htmlspecialchars($zoom_steps[0]['label']) ?></span>
                        </div>
                        <div style="text-align: center; line-height: 1;">
                            <i class="bi bi-geo-alt-fill" style="color: #ef4444; font-size: 32px; filter: drop-shadow(0 3px 5px rgba(0,0,0,0.45));"></i>
                        </div>
                    </div>
                </div>

                <!-- Step Specific Hidden Coordinates & Inputs -->
                <?php foreach ([0, 1, 2, 3] as $idx): ?>
                    <input type="hidden" name="step_<?= $idx ?>_top" id="input_step_<?= $idx ?>_top" value="<?= htmlspecialchars($zoom_steps[$idx]['top']) ?>">
                    <input type="hidden" name="step_<?= $idx ?>_left" id="input_step_<?= $idx ?>_left" value="<?= htmlspecialchars($zoom_steps[$idx]['left']) ?>">
                    <input type="hidden" name="step_<?= $idx ?>_label" id="input_step_<?= $idx ?>_label" value="<?= htmlspecialchars($zoom_steps[$idx]['label']) ?>">
                <?php endforeach; ?>

                <!-- Current Stage Fine-Tuning Controls -->
                <div style="margin-top: 14px; padding: 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                    <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Top Position (%)</label>
                            <input type="number" step="0.1" min="0" max="100" id="current_step_top_display" onchange="manualZoomCoordChange()" style="width: 85px; padding: 6px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 700; color: #1e293b;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Left Position (%)</label>
                            <input type="number" step="0.1" min="0" max="100" id="current_step_left_display" onchange="manualZoomCoordChange()" style="width: 85px; padding: 6px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 700; color: #1e293b;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Pin Label Text</label>
                            <input type="text" id="current_step_label_display" oninput="manualZoomLabelChange(this.value)" style="width: 140px; padding: 6px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 600; color: #1e293b;">
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <button type="button" onclick="resetCurrentStepPinToDefault()" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 7px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;" title="Reset this pin to default position">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset Pin
                        </button>
                        <button type="button" id="btnSaveCurrentZoomPin" onclick="saveCurrentStepPin()" style="background: #16a34a; color: #ffffff; border: 1px solid #15803d; padding: 7px 16px; border-radius: 6px; font-size: 0.82rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(22,163,74,0.3); transition: all 0.2s ease;" title="Save current pin coordinates immediately">
                            <i class="bi bi-floppy-fill" id="btnSaveCurrentZoomPinIcon"></i> <span id="btnSaveCurrentZoomPinText">Save Pin</span>
                        </button>
                    </div>
                </div>

                <!-- Submit Actions -->
                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 18px; flex-wrap: wrap; gap: 10px;">
                    <button type="submit" form="formResetAllZoomPins" style="background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; padding: 8px 14px; border-radius: 8px; font-size: 0.82rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;">
                        <i class="bi bi-trash"></i> Reset All 4 Steps to Defaults
                    </button>
                    <div style="display: flex; gap: 10px;">
                        <button type="button" onclick="closeZoomPinsModal()" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 9px 18px; border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer;">
                            Cancel
                        </button>
                        <button type="submit" style="background: #1e3a8a; color: #ffffff; border: none; padding: 9px 24px; border-radius: 8px; font-size: 0.88rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(30,58,138,0.3);">
                            <i class="bi bi-check-lg"></i> Save All Zoom Pins
                        </button>
                    </div>
                </div>
            </form>

            <form id="formResetAllZoomPins" method="post" action="Legend.php" onsubmit="return confirm('Reset all 4 regional zoom sequence pins back to official defaults?');">
                <input type="hidden" name="action" value="reset_zoom_pins">
            </form>
        </div>
    </div>

<!-- 7. Change Aerial Map Image Modal (#changeMapModal) -->
    <div class="modal-backdrop" id="changeMapModal" onclick="handleBackdropClick(event, 'changeMapModal')">
        <div class="modal-dialog" style="max-width: 540px; border-radius: 16px; overflow: hidden;">
            <div class="modal-header" style="background: #223c3b; color: #ffffff; border-bottom: 3px solid #2c6e49;">
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #ffffff; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-image-fill" style="color: #f59e0b;"></i> Change Aerial Map Image
                </h3>
                <button type="button" class="btn-close-modal" onclick="closeChangeMapModal()" style="background: none; border: none; color: #ffffff; font-size: 1.5rem; cursor: pointer; line-height: 1;">&times;</button>
            </div>

            <form method="post" enctype="multipart/form-data" action="Legend.php" style="padding: 24px;" onsubmit="return validateLegendMapUpload(this);">
                <input type="hidden" name="action" value="update_legend_map">

                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
                        Current Aerial Map Image
                    </label>
                    <div style="display: flex; align-items: center; gap: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px;">
                        <img src="<?= htmlspecialchars($legend_map_src) ?>" alt="Current Aerial Map" style="width: 80px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1;">
                        <div style="font-size: 0.85rem; color: #334155; line-height: 1.4;">
                            <strong><?= $custom_legend_map ? 'Custom Uploaded Aerial Map' : 'Default Official Aerial Map' ?></strong>
                            <div style="color: #64748b; font-size: 0.75rem; word-break: break-all;"><?= htmlspecialchars(basename($legend_map_src)) ?></div>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 0.88rem; font-weight: 700; color: #1e293b; margin-bottom: 6px;">
                        <i class="bi bi-cloud-arrow-up-fill" style="color: #2c6e49;"></i> Select New Map Image <span style="color:#e11d48;">*</span>
                    </label>
                    <input type="file" name="legend_map_image" id="legend_map_image" accept="image/jpeg,image/png,image/webp" class="form-control" required onchange="previewLegendMapImage(this)" style="width: 100%; padding: 9px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 0.88rem; outline: none; box-sizing: border-box;">
                    <small style="color: #64748b; font-size: 0.78rem; display: block; margin-top: 5px;">
                        Accepts JPG, PNG, or WEBP (Max 5 MB). This updates the background map in both the Admin canvas and the public Spot Map aerial view.
                    </small>

                    <div id="legend_map_preview_box" style="display: none; margin-top: 12px; padding: 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px;">
                        <div style="font-size: 0.8rem; font-weight: 700; color: #166534; margin-bottom: 6px;">New Map Preview:</div>
                        <img id="legend_map_preview_img" src="" alt="New Map Preview" style="width: 100%; max-height: 180px; object-fit: contain; border-radius: 6px; border: 1px solid #86efac; background: #ffffff;">
                    </div>

                    <div id="legend_map_error_msg" style="display: none; margin-top: 8px; color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 8px 12px; font-size: 0.82rem; font-weight: 600;"></div>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 24px; flex-wrap: wrap;">
                    <?php if ($custom_legend_map): ?>
                        <button type="submit" form="formResetLegendMap" style="background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; padding: 9px 16px; border-radius: 8px; font-size: 0.85rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset to Default
                        </button>
                    <?php else: ?>
                        <div></div>
                    <?php endif; ?>

                    <div style="display: flex; gap: 10px;">
                        <button type="button" onclick="closeChangeMapModal()" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 9px 18px; border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer;">
                            Cancel
                        </button>
                        <button type="submit" style="background: #2c6e49; color: #ffffff; border: none; padding: 9px 22px; border-radius: 8px; font-size: 0.88rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(44,110,73,0.3);">
                            <i class="bi bi-check-lg"></i> Upload & Apply Map
                        </button>
                    </div>
                </div>
            </form>

            <?php if ($custom_legend_map): ?>
                <form id="formResetLegendMap" method="post" action="Legend.php" onsubmit="return confirm('Reset aerial map image back to the original default map?');">
                    <input type="hidden" name="action" value="reset_legend_map">
                </form>
            <?php endif; ?>
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
                const found = ALL_LEGENDS.find(l => l.id == leg);
                if (found) leg = found;
            }
            if (!leg || !leg.id) {
                console.warn('Legend item not found', leg);
                return;
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

            // Direct native form POST submission (100% immune to InfinityFree aes.js/AJAX blocks)
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'Legend.php';
            
            const act = document.createElement('input');
            act.type = 'hidden';
            act.name = 'action';
            act.value = 'delete_legend';
            form.appendChild(act);

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = id;
            form.appendChild(idInput);

            const nameInput = document.createElement('input');
            nameInput.type = 'hidden';
            nameInput.name = 'name';
            nameInput.value = name;
            form.appendChild(nameInput);

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
        // --- Regional Zoom Pins Modal System ---
        const ZOOM_STEPS_CONFIG = <?= json_encode($zoom_steps, JSON_UNESCAPED_SLASHES) ?>;
        const ZOOM_DEFAULTS_CONFIG = <?= json_encode($zoom_step_defaults, JSON_UNESCAPED_SLASHES) ?>;
        let currentZoomStageIndex = 0;
        let isDraggingZoomPin = false;

        function openZoomPinsModal() {
            const m = document.getElementById('zoomPinsModal');
            if (m) {
                m.classList.add('show');
                switchZoomPinTab(0);
            }
        }

        function closeZoomPinsModal() {
            const m = document.getElementById('zoomPinsModal');
            if (m) m.classList.remove('show');
        }

        function switchZoomPinTab(idx) {
            currentZoomStageIndex = idx;
            // Update tab styles
            for (let i = 0; i < 4; i++) {
                const btn = document.getElementById('zoomTabBtn_' + i);
                if (btn) {
                    if (i === idx) {
                        btn.style.background = '#3b82f6';
                        btn.style.borderColor = '#3b82f6';
                        btn.style.color = '#ffffff';
                    } else {
                        btn.style.background = '#f8fafc';
                        btn.style.borderColor = '#cbd5e1';
                        btn.style.color = '#475569';
                    }
                }
            }

            // Reset Save Pin button state on tab switch
            const saveBtn = document.getElementById("btnSaveCurrentZoomPin");
            const saveIcon = document.getElementById("btnSaveCurrentZoomPinIcon");
            const saveTxt = document.getElementById("btnSaveCurrentZoomPinText");
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.style.opacity = "1";
                saveBtn.style.background = "#16a34a";
            }
            if (saveIcon) saveIcon.className = "bi bi-floppy-fill";
            if (saveTxt) saveTxt.textContent = "Save Pin";

            // Update image and pin
            const cfg = ZOOM_STEPS_CONFIG[idx];
            const imgEl = document.getElementById('zoomPinCanvasImg');
            const pinEl = document.getElementById('zoomDraggablePin');
            const badgeLabel = document.getElementById('zoomPinBadgeLabel');
            const topInput = document.getElementById('input_step_' + idx + '_top');
            const leftInput = document.getElementById('input_step_' + idx + '_left');
            const labelInput = document.getElementById('input_step_' + idx + '_label');
            const topDisp = document.getElementById('current_step_top_display');
            const leftDisp = document.getElementById('current_step_left_display');
            const labelDisp = document.getElementById('current_step_label_display');

            const curTop = parseFloat(topInput.value) || cfg.top;
            const curLeft = parseFloat(leftInput.value) || cfg.left;
            const curLabel = labelInput.value || cfg.label;

            if (imgEl) imgEl.src = cfg.image;
            if (pinEl) {
                pinEl.style.top = curTop.toFixed(2) + '%';
                pinEl.style.left = curLeft.toFixed(2) + '%';
            }
            if (badgeLabel) badgeLabel.textContent = curLabel;
            if (topDisp) topDisp.value = curTop.toFixed(1);
            if (leftDisp) leftDisp.value = curLeft.toFixed(1);
            if (labelDisp) labelDisp.value = curLabel;
        }

        function setZoomPinPosition(topPct, leftPct) {
            topPct = Math.max(2, Math.min(98, parseFloat(topPct) || 0));
            leftPct = Math.max(2, Math.min(98, parseFloat(leftPct) || 0));
            const pinEl = document.getElementById('zoomDraggablePin');
            const topInput = document.getElementById('input_step_' + currentZoomStageIndex + '_top');
            const leftInput = document.getElementById('input_step_' + currentZoomStageIndex + '_left');
            const topDisp = document.getElementById('current_step_top_display');
            const leftDisp = document.getElementById('current_step_left_display');

            if (pinEl) {
                pinEl.style.top = topPct.toFixed(2) + '%';
                pinEl.style.left = leftPct.toFixed(2) + '%';
            }
            if (topInput) topInput.value = topPct.toFixed(2);
            if (leftInput) leftInput.value = leftPct.toFixed(2);
            if (topDisp) topDisp.value = topPct.toFixed(1);
            if (leftDisp) leftDisp.value = leftPct.toFixed(1);
        }

        function manualZoomCoordChange() {
            const topDisp = document.getElementById('current_step_top_display');
            const leftDisp = document.getElementById('current_step_left_display');
            if (topDisp && leftDisp) {
                setZoomPinPosition(topDisp.value, leftDisp.value);
            }
        }

        function manualZoomLabelChange(val) {
            const labelInput = document.getElementById('input_step_' + currentZoomStageIndex + '_label');
            const badgeLabel = document.getElementById('zoomPinBadgeLabel');
            if (labelInput) labelInput.value = val;
            if (badgeLabel) badgeLabel.textContent = val || 'Target';
        }

        function saveCurrentStepPin() {
            const idx = currentZoomStageIndex;
            const topDisp = document.getElementById('current_step_top_display');
            const leftDisp = document.getElementById('current_step_left_display');
            const labelDisp = document.getElementById('current_step_label_display');
            const topInput = document.getElementById('input_step_' + idx + '_top');
            const leftInput = document.getElementById('input_step_' + idx + '_left');
            const labelInput = document.getElementById('input_step_' + idx + '_label');

            const topVal = topDisp ? topDisp.value : (topInput ? topInput.value : '50');
            const leftVal = leftDisp ? leftDisp.value : (leftInput ? leftInput.value : '50');
            const labelVal = labelDisp ? labelDisp.value : (labelInput ? labelInput.value : 'Target');

            // Sync hidden inputs & current in-memory config
            if (topInput) topInput.value = topVal;
            if (leftInput) leftInput.value = leftVal;
            if (labelInput) labelInput.value = labelVal;
            if (typeof ZOOM_STEPS_CONFIG !== 'undefined' && ZOOM_STEPS_CONFIG[idx]) {
                ZOOM_STEPS_CONFIG[idx].top = parseFloat(topVal);
                ZOOM_STEPS_CONFIG[idx].left = parseFloat(leftVal);
                ZOOM_STEPS_CONFIG[idx].label = labelVal;
            }

            const btn = document.getElementById('btnSaveCurrentZoomPin');
            const icon = document.getElementById('btnSaveCurrentZoomPinIcon');
            const txt = document.getElementById('btnSaveCurrentZoomPinText');

            if (btn) {
                btn.disabled = true;
                btn.style.opacity = '0.75';
            }
            if (icon) icon.className = 'bi bi-arrow-repeat spin';
            if (txt) txt.textContent = 'Saving...';

            const formData = new FormData();
            formData.append('action', 'save_single_zoom_pin');
            formData.append('stage_index', idx);
            formData.append('top', topVal);
            formData.append('left', leftVal);
            formData.append('label', labelVal);

            fetch('Legend.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (btn) {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    btn.style.background = '#059669';
                }
                if (icon) icon.className = 'bi bi-check-circle-fill';
                if (txt) txt.textContent = 'Saved! ✓';

                const stageNames = [
                    'Philippines → Mindanao',
                    'Mindanao → Zamboanga',
                    'Zamboanga → Lapuyan',
                    'Lapuyan → Tabon'
                ];
                const stageName = stageNames[idx] || ('Stage ' + (idx + 1));
                if (typeof showToast === 'function') {
                    showToast('✓ Pin for ' + stageName + ' saved successfully! (' + topVal + '%, ' + leftVal + '%)');
                }

                setTimeout(() => {
                    if (btn) btn.style.background = '#16a34a';
                    if (icon) icon.className = 'bi bi-floppy-fill';
                    if (txt) txt.textContent = 'Save Pin';
                }, 2500);
            })
            .catch(err => {
                console.error('Error saving zoom pin:', err);
                if (btn) {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    btn.style.background = '#dc2626';
                }
                if (icon) icon.className = 'bi bi-exclamation-triangle-fill';
                if (txt) txt.textContent = 'Error!';
                if (typeof showToast === 'function') {
                    showToast('Failed to save pin coordinates. Please try again.', true);
                }

                setTimeout(() => {
                    if (btn) btn.style.background = '#16a34a';
                    if (icon) icon.className = 'bi bi-floppy-fill';
                    if (txt) txt.textContent = 'Save Pin';
                }, 3000);
            });
        }

        function resetCurrentStepPinToDefault() {
            const def = ZOOM_DEFAULTS_CONFIG[currentZoomStageIndex];
            if (def) {
                setZoomPinPosition(def.top, def.left);
                manualZoomLabelChange(def.label);
                const labelDisp = document.getElementById('current_step_label_display');
                if (labelDisp) labelDisp.value = def.label;
            }
        }

        // Attach drag & click events to Zoom Canvas
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('zoomPinCanvasContainer');
            const pin = document.getElementById('zoomDraggablePin');

            if (canvas && pin) {
                function updateCoordsFromEvent(e) {
                    const rect = canvas.getBoundingClientRect();
                    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                    const leftPct = ((clientX - rect.left) / rect.width) * 100;
                    const topPct = ((clientY - rect.top) / rect.height) * 100;
                    setZoomPinPosition(topPct, leftPct);
                }

                pin.addEventListener('mousedown', function(e) {
                    isDraggingZoomPin = true;
                    pin.style.cursor = 'grabbing';
                    e.stopPropagation();
                });

                window.addEventListener('mousemove', function(e) {
                    if (!isDraggingZoomPin) return;
                    updateCoordsFromEvent(e);
                });

                window.addEventListener('mouseup', function() {
                    if (isDraggingZoomPin) {
                        isDraggingZoomPin = false;
                        if (pin) pin.style.cursor = 'grab';
                    }
                });

                pin.addEventListener('touchstart', function(e) { isDraggingZoomPin = true; e.stopPropagation(); }, { passive: true });
                window.addEventListener('touchmove', function(e) { if (!isDraggingZoomPin) return; updateCoordsFromEvent(e); }, { passive: true });
                window.addEventListener('touchend', function() { isDraggingZoomPin = false; });

                canvas.addEventListener('click', function(e) {
                    if (e.target.closest('#zoomDraggablePin')) return;
                    updateCoordsFromEvent(e);
                });
            }
        });

// --- Change Map Image Modal Functions ---
        function openChangeMapModal() {
            const m = document.getElementById('changeMapModal');
            if (m) m.classList.add('show');
        }
        function closeChangeMapModal() {
            const m = document.getElementById('changeMapModal');
            if (m) m.classList.remove('show');
        }
        function previewLegendMapImage(input) {
            const previewBox = document.getElementById('legend_map_preview_box');
            const previewImg = document.getElementById('legend_map_preview_img');
            const errorBox = document.getElementById('legend_map_error_msg');
            if (!input || !input.files || !input.files[0]) {
                if (previewBox) previewBox.style.display = 'none';
                return;
            }
            const file = input.files[0];
            const maxBytes = 5 * 1024 * 1024;
            const allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (errorBox) errorBox.style.display = 'none';
            if (file.size > maxBytes) {
                if (errorBox) {
                    errorBox.textContent = 'File too large (' + (file.size / 1024 / 1024).toFixed(1) + ' MB). Maximum allowed size is 5 MB.';
                    errorBox.style.display = 'block';
                }
                input.value = '';
                if (previewBox) previewBox.style.display = 'none';
                return;
            }
            if (!allowed.includes(file.type)) {
                if (errorBox) {
                    errorBox.textContent = 'Invalid file type. Please upload a JPG, PNG, or WEBP image.';
                    errorBox.style.display = 'block';
                }
                input.value = '';
                if (previewBox) previewBox.style.display = 'none';
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                if (previewImg) previewImg.src = e.target.result;
                if (previewBox) previewBox.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
        function validateLegendMapUpload(form) {
            const fileInput = form.querySelector('#legend_map_image');
            const errorBox = document.getElementById('legend_map_error_msg');
            if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                if (errorBox) {
                    errorBox.textContent = 'Please choose an image file to upload.';
                    errorBox.style.display = 'block';
                }
                return false;
            }
            const file = fileInput.files[0];
            if (file.size > 5 * 1024 * 1024) {
                if (errorBox) {
                    errorBox.textContent = 'File exceeds 5 MB. Please select a smaller image.';
                    errorBox.style.display = 'block';
                }
                return false;
            }
            return true;
        }

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
            const editIdParam = urlParams.get('edit_id');
            if (editIdParam) {
                setTimeout(function() {
                    openEditModal(parseInt(editIdParam, 10));
                }, 400);
            }
            if (urlParams.get('zoom_pins_saved') === '1') {
                showToast('Regional zoom sequence pins updated successfully!');
            } else if (urlParams.get('zoom_pins_reset') === '1') {
                showToast('Regional zoom pins reset to default.');
            } else if (urlParams.get('map_updated') === '1') {
                showToast('Aerial Map image updated successfully!');
            } else if (urlParams.get('map_reset') === '1') {
                showToast('Aerial Map image reset to default.');
            } else if (urlParams.get('msg') === 'added') {
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
