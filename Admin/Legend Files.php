<?php
// Admin/Legend Files.php - SpotMap Landmark & Legend File Management
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../db_connect.php';

// Helper directories
$landmarks_dir = __DIR__ . '/uploads/landmarks/';
$legends_dir   = __DIR__ . '/uploads/legends/';
if (!is_dir($landmarks_dir)) @mkdir($landmarks_dir, 0777, true);
if (!is_dir($legends_dir))   @mkdir($legends_dir, 0777, true);

$json_cache_file = __DIR__ . '/data/legend.json';
$json_dir = dirname($json_cache_file);
if (!is_dir($json_dir)) @mkdir($json_dir, 0777, true);

// Synchronize Database with legend.json
function syncLegendJsonFile($conn, $pdo, $json_file_path) {
    try {
        $export_data = [];
        if (isset($pdo) && $pdo instanceof PDO) {
            $stmt = $pdo->query("SELECT * FROM `sm_legends` WHERE `is_active` = 1 ORDER BY `display_order` ASC, `id` ASC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $export_data[] = [
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
                    'dateAdded'     => substr($row['created_at'], 0, 10)
                ];
            }
        } elseif (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
            $res = $conn->query("SELECT * FROM `sm_legends` WHERE `is_active` = 1 ORDER BY `display_order` ASC, `id` ASC");
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $export_data[] = [
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
                        'dateAdded'     => substr($row['created_at'], 0, 10)
                    ];
                }
            }
        }
        @file_put_contents($json_file_path, json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    } catch (\Throwable $e) {}
}

// -------------------------------------------------------------
// POST Request Actions (Delete, Edit, Add)
// -------------------------------------------------------------
$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

// Self-Handling Deletion (Supports POST and GET)
if ((isset($_POST['action']) && $_POST['action'] === 'delete_legend') || isset($_GET['delete_id'])) {
    $deleteId = intval($_POST['delete_id'] ?? $_GET['delete_id'] ?? 0);
    $legendName = trim($_POST['legend_name'] ?? $_GET['name'] ?? '');

    if ($deleteId > 0 || !empty($legendName)) {
        $deleted = false;
        $row_data = null;

        // Fetch image paths before deletion
        if (isset($pdo) && $pdo instanceof PDO) {
            try {
                if ($deleteId > 0) {
                    $sel = $pdo->prepare("SELECT id, icon_path, image_path FROM sm_legends WHERE id = ? LIMIT 1");
                    $sel->execute([$deleteId]);
                } else {
                    $sel = $pdo->prepare("SELECT id, icon_path, image_path FROM sm_legends WHERE name = ? LIMIT 1");
                    $sel->execute([$legendName]);
                }
                $row_data = $sel->fetch(PDO::FETCH_ASSOC);
                if ($row_data) $deleteId = (int)$row_data['id'];

                if ($deleteId > 0) {
                    $del1 = $pdo->prepare("DELETE FROM sm_legends WHERE id = ?");
                    $del1->execute([$deleteId]);
                    $del2 = $pdo->prepare("DELETE FROM sm_legend_spots WHERE legend_id = ?");
                    $del2->execute([$deleteId]);
                    $deleted = true;
                } elseif (!empty($legendName)) {
                    $del1 = $pdo->prepare("DELETE FROM sm_legends WHERE name = ?");
                    $del1->execute([$legendName]);
                    $del2 = $pdo->prepare("DELETE FROM sm_legend_spots WHERE title = ?");
                    $del2->execute([$legendName]);
                    $deleted = true;
                }
            } catch (\Throwable $e) {}
        }

        if (!$deleted && isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
            if ($deleteId > 0) {
                $conn->query("DELETE FROM sm_legends WHERE id = $deleteId");
                $conn->query("DELETE FROM sm_legend_spots WHERE legend_id = $deleteId");
                $deleted = true;
            } elseif (!empty($legendName)) {
                $escaped = $conn->real_escape_string($legendName);
                $conn->query("DELETE FROM sm_legends WHERE name = '$escaped'");
                $conn->query("DELETE FROM sm_legend_spots WHERE title = '$escaped'");
                $deleted = true;
            }
        }

        // Clean up disk files
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

        syncLegendJsonFile($conn ?? null, $pdo ?? null, $json_cache_file);
        header("Location: Legend Files.php?msg=deleted");
        exit();
    }
}

// Handle Edit / Update Legend
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_legend') {
    $id            = intval($_POST['legend_id'] ?? 0);
    $name          = trim($_POST['name'] ?? '');
    $color_hex     = trim($_POST['color_hex'] ?? '#6366f1');
    $top_position  = floatval($_POST['top_position'] ?? 50.00);
    $left_position = floatval($_POST['left_position'] ?? 50.00);
    $marker_width  = intval($_POST['marker_width'] ?? 40);
    $marker_height = intval($_POST['marker_height'] ?? 40);
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active     = isset($_POST['is_active']) ? 1 : 0;
    $icon_type     = $_POST['icon_type'] ?? 'preset';
    $icon_path     = trim($_POST['preset_icon'] ?? 'bi-building-fill');

    if ($id <= 0 && !empty($name)) {
        if (isset($pdo) && $pdo instanceof PDO) {
            $f_id = $pdo->prepare("SELECT id FROM sm_legends WHERE name = ? LIMIT 1");
            $f_id->execute([$name]);
            $id = (int)$f_id->fetchColumn();
        }
    }

    if ($id <= 0 || empty($name)) {
        header("Location: Legend Files.php?error=invalid_data");
        exit();
    }

    // Retrieve current values
    $curr = null;
    if (isset($pdo) && $pdo instanceof PDO) {
        $st = $pdo->prepare("SELECT * FROM sm_legends WHERE id = ? LIMIT 1");
        $st->execute([$id]);
        $curr = $st->fetch(PDO::FETCH_ASSOC);
    }
    if (!$curr && isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
        $res = $conn->query("SELECT * FROM sm_legends WHERE id = $id LIMIT 1");
        if ($res) $curr = $res->fetch_assoc();
    }

    if ($curr) {
        // Icon handling
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

        // Photo handling
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

        // Execute Update
        $updated = false;
        if (isset($pdo) && $pdo instanceof PDO) {
            try {
                $up = $pdo->prepare("UPDATE `sm_legends` SET `name` = ?, `icon_path` = ?, `color_hex` = ?, `is_active` = ?, `display_order` = ?, `top_position` = ?, `left_position` = ?, `marker_width` = ?, `marker_height` = ?, `image_path` = ? WHERE `id` = ?");
                $up->execute([$name, $icon_path, $color_hex, $is_active, $display_order, $top_position, $left_position, $marker_width, $marker_height, $image_path, $id]);

                $up_spot = $pdo->prepare("UPDATE `sm_legend_spots` SET `title` = ?, `top_position` = ?, `left_position` = ?, `marker_width` = ?, `marker_height` = ?, `marker_image` = ?, `house_image` = ? WHERE `legend_id` = ?");
                $up_spot->execute([$name, $top_position, $left_position, $marker_width, $marker_height, $icon_path, $image_path, $id]);
                $updated = true;
            } catch (\Throwable $pe) {}
        }
        if (!$updated && isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
            $stmt = $conn->prepare("UPDATE `sm_legends` SET `name` = ?, `icon_path` = ?, `color_hex` = ?, `is_active` = ?, `display_order` = ?, `top_position` = ?, `left_position` = ?, `marker_width` = ?, `marker_height` = ?, `image_path` = ? WHERE `id` = ?");
            if ($stmt) {
                $stmt->bind_param("sssiiddiisi", $name, $icon_path, $color_hex, $is_active, $display_order, $top_position, $left_position, $marker_width, $marker_height, $image_path, $id);
                $stmt->execute();
                $stmt->close();
            }
        }

        syncLegendJsonFile($conn ?? null, $pdo ?? null, $json_cache_file);
        header("Location: Legend Files.php?msg=updated");
        exit();
    }
}

// Handle Add Legend
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_legend') {
    $name          = trim($_POST['name'] ?? '');
    $color_hex     = trim($_POST['color_hex'] ?? '#6366f1');
    $top_position  = floatval($_POST['top_position'] ?? 50.00);
    $left_position = floatval($_POST['left_position'] ?? 50.00);
    $marker_width  = intval($_POST['marker_width'] ?? 40);
    $marker_height = intval($_POST['marker_height'] ?? 40);
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active     = isset($_POST['is_active']) ? 1 : 0;
    $icon_type     = $_POST['icon_type'] ?? 'preset';
    $icon_path     = trim($_POST['preset_icon'] ?? 'bi-building-fill');

    if (empty($name)) {
        header("Location: Legend Files.php?error=name_required");
        exit();
    }

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

    if (isset($pdo) && $pdo instanceof PDO) {
        try {
            $ins = $pdo->prepare("INSERT INTO `sm_legends` (`name`, `icon_path`, `color_hex`, `is_active`, `display_order`, `top_position`, `left_position`, `marker_width`, `marker_height`, `image_path`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([$name, $icon_path, $color_hex, $is_active, $display_order, $top_position, $left_position, $marker_width, $marker_height, $image_path]);
            $newId = (int)$pdo->lastInsertId();

            $insSpot = $pdo->prepare("INSERT INTO `sm_legend_spots` (`legend_id`, `title`, `top_position`, `left_position`, `marker_width`, `marker_height`, `marker_image`, `house_image`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $insSpot->execute([$newId, $name, $top_position, $left_position, $marker_width, $marker_height, $icon_path, $image_path]);
        } catch (\Throwable $pe) {}
    }

    syncLegendJsonFile($conn ?? null, $pdo ?? null, $json_cache_file);
    header("Location: Legend Files.php?msg=added");
    exit();
}

// -------------------------------------------------------------
// Fetch Records from Database
// -------------------------------------------------------------
$records = [];
try {
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->query("SELECT * FROM `sm_legends` ORDER BY `display_order` ASC, `id` ASC");
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
        $res = $conn->query("SELECT * FROM `sm_legends` ORDER BY `display_order` ASC, `id` ASC");
        if ($res) {
            while ($r = $res->fetch_assoc()) $records[] = $r;
        }
    }
} catch (\Throwable $e) {}

// Fallback to JSON if database is empty
if (empty($records) && file_exists($json_cache_file)) {
    $rawJson = json_decode(file_get_contents($json_cache_file), true);
    if (is_array($rawJson)) {
        foreach ($rawJson as $item) {
            $records[] = [
                'id'            => $item['id'] ?? 0,
                'name'          => $item['name'] ?? $item['houseNumber'] ?? 'Landmark',
                'icon_path'     => $item['icon_path'] ?? $item['markerImage'] ?? 'bi-building-fill',
                'color_hex'     => $item['color_hex'] ?? '#6366f1',
                'is_active'     => $item['is_active'] ?? 1,
                'display_order' => $item['display_order'] ?? 0,
                'top_position'  => $item['top_position'] ?? $item['topPos'] ?? 50.00,
                'left_position' => $item['left_position'] ?? $item['leftPos'] ?? 50.00,
                'marker_width'  => $item['marker_width'] ?? 40,
                'marker_height' => $item['marker_height'] ?? 40,
                'image_path'    => $item['image_path'] ?? null,
                'created_at'    => $item['dateAdded'] ?? date('Y-m-d')
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Legend Files - SpotMap Landmarks | Brgy Tabon</title>
    <!-- Google Fonts & FontAwesome -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Admin Styles -->
    <link rel="stylesheet" href="Admin CSS/admin_common.css?v=<?= time() ?>">
    <link rel="stylesheet" href="Admin CSS/Legend Files.css?v=<?= time() ?>">
    <script src="js/admin_mobile.js?v=<?= time() ?>" defer></script>
    <style>
        .page-header-card {
            background: linear-gradient(135deg, #1b3d36 0%, #0d221e 100%);
            border-radius: 14px;
            padding: 24px 28px;
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
            box-shadow: 0 8px 24px rgba(13, 34, 30, 0.15);
        }
        .header-title-box h2 {
            margin: 0 0 6px 0;
            font-size: 1.45rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .header-title-box p {
            margin: 0;
            font-size: 0.9rem;
            color: #a7f3d0;
        }
        .header-btn-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-header-primary {
            background-color: #10b981;
            color: #ffffff;
            padding: 9px 18px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.88rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-header-primary:hover { background-color: #059669; transform: translateY(-1px); }
        .btn-header-secondary {
            background-color: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            padding: 9px 18px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.88rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            transition: all 0.2s;
        }
        .btn-header-secondary:hover { background-color: rgba(255, 255, 255, 0.22); color: #fff; }

        .table-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        .legend-table {
            width: 100%;
            border-collapse: collapse;
        }
        .legend-table th {
            background-color: #f8fafc;
            color: #334155;
            font-weight: 700;
            font-size: 0.84rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 16px;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
        }
        .legend-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            font-size: 0.92rem;
            color: #1e293b;
        }
        .legend-table tr:hover {
            background-color: #f8fafc;
        }
        .badge-preview {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.25rem;
            box-shadow: 0 3px 8px rgba(0,0,0,0.12);
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-pill.active { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .status-pill.hidden { background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }

        .action-btn-group {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .btn-table-edit {
            background-color: #2563eb;
            color: #ffffff;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.82rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        .btn-table-edit:hover { background-color: #1d4ed8; }
        .btn-table-map {
            background-color: #0f766e;
            color: #ffffff;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.82rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        .btn-table-map:hover { background-color: #0d9488; color: #fff; }
        .btn-table-delete {
            background-color: #ef4444;
            color: #ffffff;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.82rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        .btn-table-delete:hover { background-color: #dc2626; }

        /* Alert Banners */
        .alert-banner {
            padding: 14px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-weight: 600;
            font-size: 0.92rem;
        }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        /* Modals */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: #ffffff;
            border-radius: 14px;
            width: 92%;
            max-width: 620px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .modal-header {
            padding: 18px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-header h3 { margin: 0; font-size: 1.2rem; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .close-modal { font-size: 1.5rem; color: #94a3b8; cursor: pointer; border: none; background: none; }
        .modal-body { padding: 22px 24px; }
        .modal-footer {
            padding: 14px 24px;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            border-radius: 0 0 14px 14px;
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-weight: 700; font-size: 0.86rem; color: #334155; margin-bottom: 6px; }
        .form-control {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 0.92rem;
            font-family: inherit;
            box-sizing: border-box;
        }
        .preset-grid {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 8px;
            margin-top: 8px;
        }
        .preset-chip {
            aspect-ratio: 1;
            border-radius: 8px;
            border: 1.5px solid #cbd5e1;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.15rem;
            color: #475569;
            transition: all 0.15s;
        }
        .preset-chip:hover { border-color: #2563eb; background: #eff6ff; color: #1d4ed8; }
        .preset-chip.selected { border-color: #2563eb; background: #2563eb; color: #ffffff; box-shadow: 0 2px 6px rgba(37,99,235,0.3); }
        .preset-chip span { font-size: 0.62rem; font-weight: 600; margin-top: 2px; }
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
                <li><a href="controlpanel.php"><i class="fa-solid fa-gauge"></i> Control Panel</a></li>
                <li><a href="about.php"><i class="fa-solid fa-circle-info"></i> About</a></li>
                <li>
                    <a class="dropdown-btn active"><i class="fa-solid fa-map-location-dot"></i> SpotMap Manage <i class="fa-solid fa-caret-down" style="margin-left: auto;"></i></a>
                    <div class="dropdown-container" style="display: block;">
                        <a href="Legend.php">Legend</a>
                        <a href="Purok 1.php">Purok Mesaya</a>
                        <a href="Purok 2.php">Purok Metamed</a>
                        <a href="Purok 3.php">Purok Melitebangay</a>
                    </div>
                </li>
                <li>
                    <a class="dropdown-btn active"><i class="fa-solid fa-users"></i> Household <i class="fa-solid fa-caret-down" style="margin-left: auto;"></i></a>
                    <div class="dropdown-container" style="display: block;">
                        <a href="Legend Files.php" class="active" style="color: #2c6e49; font-weight: 700;">Legend Files</a>
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
                <li><a href="request_services.php"><i class="fa-solid fa-file-signature"></i> Request Services</a></li>
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
                        if (dropdownContent) {
                            if (dropdownContent.style.display === "block") {
                                dropdownContent.style.display = "none";
                            } else {
                                dropdownContent.style.display = "block";
                            }
                        }
                    });
                }
            });
        </script>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="top-bar">
            <div class="top-bar-left">
                <h1>Legend Files</h1>
            </div>
            <div class="top-bar-right">
                <div class="admin-profile">
                    <div class="admin-info">
                        <span class="admin-name">System Admin</span>
                        <span class="admin-role">Administrator</span>
                    </div>
                    <div class="admin-avatar">A</div>
                </div>
            </div>
        </div>

        <!-- Flash Message Banners -->
        <?php if ($msg === 'deleted'): ?>
            <div class="alert-banner alert-success">
                <span><i class="bi bi-check-circle-fill"></i> Landmark record permanently deleted from the SpotMap and system.</span>
                <button type="button" class="close-modal" onclick="this.parentElement.remove();">&times;</button>
            </div>
        <?php elseif ($msg === 'updated'): ?>
            <div class="alert-banner alert-success">
                <span><i class="bi bi-check-circle-fill"></i> Landmark details updated successfully!</span>
                <button type="button" class="close-modal" onclick="this.parentElement.remove();">&times;</button>
            </div>
        <?php elseif ($msg === 'added'): ?>
            <div class="alert-banner alert-success">
                <span><i class="bi bi-check-circle-fill"></i> New landmark created and pinned to SpotMap successfully!</span>
                <button type="button" class="close-modal" onclick="this.parentElement.remove();">&times;</button>
            </div>
        <?php elseif ($error): ?>
            <div class="alert-banner alert-error">
                <span><i class="bi bi-exclamation-triangle-fill"></i> Error: <?= htmlspecialchars($error) ?></span>
                <button type="button" class="close-modal" onclick="this.parentElement.remove();">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Page Header Card -->
        <div class="page-header-card">
            <div class="header-title-box">
                <h2><i class="bi bi-geo-alt-fill"></i> SpotMap Legend Landmarks</h2>
                <p>Manage, edit coordinates, rename, and remove community facilities and icons plotted on the aerial map.</p>
            </div>
            <div class="header-btn-group">
                <button type="button" class="btn-header-primary" onclick="openAddModal()">
                    <i class="bi bi-plus-circle-fill"></i> + Add Landmark
                </button>
                <a href="Legend.php" class="btn-header-secondary">
                    <i class="bi bi-map-fill"></i> Interactive SpotMap
                </a>
            </div>
        </div>

        <!-- Table Container -->
        <div class="table-card">
            <table class="legend-table">
                <thead>
                    <tr>
                        <th style="width: 70px;">Marker</th>
                        <th>Landmark / Facility Name</th>
                        <th style="width: 100px;">Photo</th>
                        <th>Position (Top, Left)</th>
                        <th>Size</th>
                        <th>Status</th>
                        <th style="text-align: center; width: 220px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #64748b;">
                                <i class="bi bi-geo-alt" style="font-size: 2.2rem; color: #94a3b8; display: block; margin-bottom: 8px;"></i>
                                No legend landmarks found. Click <strong>"+ Add Landmark"</strong> to add one.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($records as $record): 
                            $recJson = htmlspecialchars(json_encode($record), ENT_QUOTES, 'UTF-8');
                            $color = $record['color_hex'] ?? '#6366f1';
                            $icon = $record['icon_path'] ?? 'bi-building-fill';
                            $isPreset = strpos($icon, 'bi-') === 0;
                        ?>
                            <tr>
                                <td>
                                    <div class="badge-preview" style="background-color: <?= htmlspecialchars($color) ?>;">
                                        <?php if ($isPreset): ?>
                                            <i class="bi <?= htmlspecialchars($icon) ?>"></i>
                                        <?php else: ?>
                                            <img src="<?= htmlspecialchars($icon) ?>" style="width: 24px; height: 24px; object-fit: contain;">
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <strong style="color: #0f172a; font-size: 0.98rem;"><?= htmlspecialchars($record['name']) ?></strong>
                                    <div style="font-size: 0.78rem; color: #64748b; margin-top: 2px;">
                                        Order #<?= (int)($record['display_order'] ?? 0) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($record['image_path'])): ?>
                                        <img src="<?= htmlspecialchars($record['image_path']) ?>" alt="Photo" style="width: 48px; height: 48px; object-fit: cover; border-radius: 8px; border: 1px solid #cbd5e1;">
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-size: 0.8rem; font-style: italic;">No photo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-weight: 600; color: #334155;">
                                        <i class="bi bi-crosshair" style="color:#0f766e;"></i>
                                        <?= number_format((float)($record['top_position'] ?? 50), 2) ?>%, 
                                        <?= number_format((float)($record['left_position'] ?? 50), 2) ?>%
                                    </span>
                                </td>
                                <td>
                                    <span style="font-size: 0.85rem; color: #64748b;">
                                        <?= (int)($record['marker_width'] ?? 40) ?> &times; <?= (int)($record['marker_height'] ?? 40) ?> px
                                    </span>
                                </td>
                                <td>
                                    <?php if ((int)($record['is_active'] ?? 1) === 1): ?>
                                        <span class="status-pill active"><i class="bi bi-eye-fill"></i> Active</span>
                                    <?php else: ?>
                                        <span class="status-pill hidden"><i class="bi bi-eye-slash-fill"></i> Hidden</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <div class="action-btn-group" style="justify-content: center;">
                                        <button type="button" class="btn-table-edit" onclick="openEditModal(<?= $recJson ?>)" title="Edit Name, Color, Icon & Details">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <a href="Legend.php?edit_id=<?= (int)$record['id'] ?>" class="btn-table-map" title="Reposition Visually on Interactive SpotMap">
                                            <i class="bi bi-geo-alt"></i> On Map
                                        </a>
                                        <button type="button" class="btn-table-delete" onclick="confirmDelete(<?= (int)$record['id'] ?>, '<?= htmlspecialchars(addslashes($record['name'])) ?>')" title="Delete Landmark">
                                            <i class="bi bi-trash"></i> Remove
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- ======================================================== -->
    <!-- 1. EDIT LANDMARK MODAL                                   -->
    <!-- ======================================================== -->
    <div id="editLegendModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="bi bi-pencil-square" style="color: #2563eb;"></i> Edit Landmark Details</h3>
                <button type="button" class="close-modal" onclick="closeModal('editLegendModal')">&times;</button>
            </div>
            <form action="Legend Files.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_legend">
                <input type="hidden" name="legend_id" id="editLegendId" value="">
                <input type="hidden" name="icon_type" id="editIconType" value="preset">
                <input type="hidden" name="preset_icon" id="editPresetIcon" value="bi-building-fill">
                <input type="hidden" name="remove_landmark_image" id="editRemoveLandmarkImage" value="0">

                <div class="modal-body">
                    <div class="form-group">
                        <label>Landmark Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="name" id="editName" class="form-control" required placeholder="e.g. Barangay Tabon Hall">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Pin Badge Color</label>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <input type="color" id="editColorPicker" value="#6366f1" style="width: 44px; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer; padding: 2px;" onchange="syncColorInput(this.value, 'editColorHex')">
                                <input type="text" name="color_hex" id="editColorHex" class="form-control" value="#6366f1" style="font-family: monospace;" onkeyup="syncColorInput(this.value, 'editColorPicker')">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Display Order</label>
                            <input type="number" name="display_order" id="editOrder" class="form-control" value="0" min="0">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Top Position (%)</label>
                            <input type="number" step="0.01" name="top_position" id="editTop" class="form-control" value="50.00" min="0" max="100">
                        </div>
                        <div class="form-group">
                            <label>Left Position (%)</label>
                            <input type="number" step="0.01" name="left_position" id="editLeft" class="form-control" value="50.00" min="0" max="100">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Marker Width (px)</label>
                            <input type="number" name="marker_width" id="editWidth" class="form-control" value="40" min="20" max="120">
                        </div>
                        <div class="form-group">
                            <label>Marker Height (px)</label>
                            <input type="number" name="marker_height" id="editHeight" class="form-control" value="40" min="20" max="120">
                        </div>
                    </div>

                    <!-- Icon Chooser -->
                    <div class="form-group">
                        <label>Choose Preset Icon</label>
                        <div class="preset-grid" id="editPresetGrid">
                            <div class="preset-chip selected" data-icon="bi-building-fill" onclick="selectEditPreset('bi-building-fill', this)"><i class="bi bi-building-fill"></i><span>Hall</span></div>
                            <div class="preset-chip" data-icon="bi-book-half" onclick="selectEditPreset('bi-book-half', this)"><i class="bi bi-book-half"></i><span>School</span></div>
                            <div class="preset-chip" data-icon="bi-dribbble" onclick="selectEditPreset('bi-dribbble', this)"><i class="bi bi-dribbble"></i><span>Court</span></div>
                            <div class="preset-chip" data-icon="bi-heart-pulse-fill" onclick="selectEditPreset('bi-heart-pulse-fill', this)"><i class="bi bi-heart-pulse-fill"></i><span>Health</span></div>
                            <div class="preset-chip" data-icon="bi-bell-fill" onclick="selectEditPreset('bi-bell-fill', this)"><i class="bi bi-bell-fill"></i><span>Chapel</span></div>
                            <div class="preset-chip" data-icon="bi-shield-check" onclick="selectEditPreset('bi-shield-check', this)"><i class="bi bi-shield-check"></i><span>Outpost</span></div>
                            <div class="preset-chip" data-icon="bi-hospital-fill" onclick="selectEditPreset('bi-hospital-fill', this)"><i class="bi bi-hospital-fill"></i><span>Hospital</span></div>
                            <div class="preset-chip" data-icon="bi-tree-fill" onclick="selectEditPreset('bi-tree-fill', this)"><i class="bi bi-tree-fill"></i><span>Park</span></div>
                            <div class="preset-chip" data-icon="bi-fire" onclick="selectEditPreset('bi-fire', this)"><i class="bi bi-fire"></i><span>Fire</span></div>
                            <div class="preset-chip" data-icon="bi-shop" onclick="selectEditPreset('bi-shop', this)"><i class="bi bi-shop"></i><span>Market</span></div>
                            <div class="preset-chip" data-icon="bi-water" onclick="selectEditPreset('bi-water', this)"><i class="bi bi-water"></i><span>Water</span></div>
                            <div class="preset-chip" data-icon="bi-houses-fill" onclick="selectEditPreset('bi-houses-fill', this)"><i class="bi bi-houses-fill"></i><span>Zone</span></div>
                            <div class="preset-chip" data-icon="bi-geo-alt-fill" onclick="selectEditPreset('bi-geo-alt-fill', this)"><i class="bi bi-geo-alt-fill"></i><span>Pin</span></div>
                            <div class="preset-chip" data-icon="bi-info-circle-fill" onclick="selectEditPreset('bi-info-circle-fill', this)"><i class="bi bi-info-circle-fill"></i><span>Info</span></div>
                            <div class="preset-chip" data-icon="bi-car-front-fill" onclick="selectEditPreset('bi-car-front-fill', this)"><i class="bi bi-car-front-fill"></i><span>Terminal</span></div>
                            <div class="preset-chip" data-icon="bi-broadcast-pin" onclick="selectEditPreset('bi-broadcast-pin', this)"><i class="bi bi-broadcast-pin"></i><span>Post</span></div>
                        </div>
                    </div>

                    <!-- Custom Icon Upload Option -->
                    <div class="form-group" style="margin-top: 10px;">
                        <label style="font-size:0.8rem; color:#64748b;">Or Upload Custom Icon Image (Optional):</label>
                        <input type="file" name="custom_icon" class="form-control" accept="image/*" onchange="document.getElementById('editIconType').value='upload';">
                    </div>

                    <!-- Landmark Photo -->
                    <div class="form-group" style="margin-top: 14px; padding-top: 14px; border-top: 1px dashed #cbd5e1;">
                        <label>Landmark Photo (Building Image)</label>
                        <div id="editCurrentPhotoWrap" style="display: none; margin-bottom: 8px; align-items: center; gap: 10px;">
                            <img id="editCurrentPhoto" src="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1;">
                            <label style="font-weight: 500; font-size: 0.82rem; color: #ef4444; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                <input type="checkbox" id="chkRemovePhoto" onchange="document.getElementById('editRemoveLandmarkImage').value = this.checked ? '1' : '0';"> Remove current photo
                            </label>
                        </div>
                        <input type="file" name="landmark_image" class="form-control" accept="image/*">
                    </div>

                    <!-- Active Toggle -->
                    <div class="form-group" style="margin-top: 10px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600;">
                            <input type="checkbox" name="is_active" id="editIsActive" value="1" checked>
                            Show marker on SpotMap (Active)
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-table-edit" style="background:#64748b;" onclick="closeModal('editLegendModal')">Cancel</button>
                    <button type="submit" class="btn-table-edit" style="background:#10b981; padding: 9px 18px;"><i class="bi bi-check-circle-fill"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- 2. ADD LANDMARK MODAL                                    -->
    <!-- ======================================================== -->
    <div id="addLegendModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="bi bi-plus-circle-fill" style="color: #10b981;"></i> Add New SpotMap Landmark</h3>
                <button type="button" class="close-modal" onclick="closeModal('addLegendModal')">&times;</button>
            </div>
            <form action="Legend Files.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_legend">
                <input type="hidden" name="icon_type" id="addIconType" value="preset">
                <input type="hidden" name="preset_icon" id="addPresetIcon" value="bi-building-fill">

                <div class="modal-body">
                    <div class="form-group">
                        <label>Landmark Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Barangay Tabon Hall">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Pin Badge Color</label>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <input type="color" value="#4f46e5" style="width: 44px; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer; padding: 2px;" onchange="syncColorInput(this.value, 'addColorHex')">
                                <input type="text" name="color_hex" id="addColorHex" class="form-control" value="#4f46e5" style="font-family: monospace;">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Display Order</label>
                            <input type="number" name="display_order" class="form-control" value="0" min="0">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Top Position (%)</label>
                            <input type="number" step="0.01" name="top_position" class="form-control" value="50.00" min="0" max="100">
                        </div>
                        <div class="form-group">
                            <label>Left Position (%)</label>
                            <input type="number" step="0.01" name="left_position" class="form-control" value="50.00" min="0" max="100">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Marker Width (px)</label>
                            <input type="number" name="marker_width" class="form-control" value="40" min="20" max="120">
                        </div>
                        <div class="form-group">
                            <label>Marker Height (px)</label>
                            <input type="number" name="marker_height" class="form-control" value="40" min="20" max="120">
                        </div>
                    </div>

                    <!-- Preset Icon Grid -->
                    <div class="form-group">
                        <label>Choose Preset Icon</label>
                        <div class="preset-grid" id="addPresetGrid">
                            <div class="preset-chip selected" data-icon="bi-building-fill" onclick="selectAddPreset('bi-building-fill', this)"><i class="bi bi-building-fill"></i><span>Hall</span></div>
                            <div class="preset-chip" data-icon="bi-book-half" onclick="selectAddPreset('bi-book-half', this)"><i class="bi bi-book-half"></i><span>School</span></div>
                            <div class="preset-chip" data-icon="bi-dribbble" onclick="selectAddPreset('bi-dribbble', this)"><i class="bi bi-dribbble"></i><span>Court</span></div>
                            <div class="preset-chip" data-icon="bi-heart-pulse-fill" onclick="selectAddPreset('bi-heart-pulse-fill', this)"><i class="bi bi-heart-pulse-fill"></i><span>Health</span></div>
                            <div class="preset-chip" data-icon="bi-bell-fill" onclick="selectAddPreset('bi-bell-fill', this)"><i class="bi bi-bell-fill"></i><span>Chapel</span></div>
                            <div class="preset-chip" data-icon="bi-shield-check" onclick="selectAddPreset('bi-shield-check', this)"><i class="bi bi-shield-check"></i><span>Outpost</span></div>
                            <div class="preset-chip" data-icon="bi-hospital-fill" onclick="selectAddPreset('bi-hospital-fill', this)"><i class="bi bi-hospital-fill"></i><span>Hospital</span></div>
                            <div class="preset-chip" data-icon="bi-tree-fill" onclick="selectAddPreset('bi-tree-fill', this)"><i class="bi bi-tree-fill"></i><span>Park</span></div>
                            <div class="preset-chip" data-icon="bi-fire" onclick="selectAddPreset('bi-fire', this)"><i class="bi bi-fire"></i><span>Fire</span></div>
                            <div class="preset-chip" data-icon="bi-shop" onclick="selectAddPreset('bi-shop', this)"><i class="bi bi-shop"></i><span>Market</span></div>
                            <div class="preset-chip" data-icon="bi-water" onclick="selectAddPreset('bi-water', this)"><i class="bi bi-water"></i><span>Water</span></div>
                            <div class="preset-chip" data-icon="bi-houses-fill" onclick="selectAddPreset('bi-houses-fill', this)"><i class="bi bi-houses-fill"></i><span>Zone</span></div>
                            <div class="preset-chip" data-icon="bi-geo-alt-fill" onclick="selectAddPreset('bi-geo-alt-fill', this)"><i class="bi bi-geo-alt-fill"></i><span>Pin</span></div>
                            <div class="preset-chip" data-icon="bi-info-circle-fill" onclick="selectAddPreset('bi-info-circle-fill', this)"><i class="bi bi-info-circle-fill"></i><span>Info</span></div>
                            <div class="preset-chip" data-icon="bi-car-front-fill" onclick="selectAddPreset('bi-car-front-fill', this)"><i class="bi bi-car-front-fill"></i><span>Terminal</span></div>
                            <div class="preset-chip" data-icon="bi-broadcast-pin" onclick="selectAddPreset('bi-broadcast-pin', this)"><i class="bi bi-broadcast-pin"></i><span>Post</span></div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 10px;">
                        <label style="font-size:0.8rem; color:#64748b;">Or Upload Custom Icon Image:</label>
                        <input type="file" name="custom_icon" class="form-control" accept="image/*" onchange="document.getElementById('addIconType').value='upload';">
                    </div>

                    <div class="form-group" style="margin-top: 14px; padding-top: 14px; border-top: 1px dashed #cbd5e1;">
                        <label>Landmark Photo (Optional)</label>
                        <input type="file" name="landmark_image" class="form-control" accept="image/*">
                    </div>

                    <div class="form-group" style="margin-top: 10px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600;">
                            <input type="checkbox" name="is_active" value="1" checked>
                            Show marker on SpotMap (Active)
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-table-edit" style="background:#64748b;" onclick="closeModal('addLegendModal')">Cancel</button>
                    <button type="submit" class="btn-table-edit" style="background:#10b981; padding: 9px 18px;"><i class="bi bi-plus-circle"></i> Create Landmark</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- 3. DELETE CONFIRMATION MODAL                             -->
    <!-- ======================================================== -->
    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content" style="max-width: 440px;">
            <div class="modal-header">
                <h3><i class="bi bi-exclamation-triangle-fill" style="color: #ef4444;"></i> Confirm Deletion</h3>
                <button type="button" class="close-modal" onclick="closeModal('deleteConfirmModal')">&times;</button>
            </div>
            <form action="Legend Files.php" method="POST">
                <input type="hidden" name="action" value="delete_legend">
                <input type="hidden" name="delete_id" id="deleteLegendId" value="">
                <input type="hidden" name="legend_name" id="deleteLegendName" value="">

                <div class="modal-body">
                    <p style="margin: 0; font-size: 0.95rem; line-height: 1.5; color: #334155;">
                        Are you sure you want to delete <strong id="deleteTargetTitle" style="color: #0f172a;">this landmark</strong>?
                    </p>
                    <p style="margin: 8px 0 0 0; font-size: 0.82rem; color: #ef4444;">
                        This will permanently remove the marker and its details from the interactive SpotMap and database.
                    </p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-table-edit" style="background:#64748b;" onclick="closeModal('deleteConfirmModal')">Cancel</button>
                    <button type="submit" class="btn-table-delete" style="padding: 9px 18px;"><i class="bi bi-trash"></i> Yes, Delete</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script>
    function openModal(id) { const m = document.getElementById(id); if (m) m.style.display = 'flex'; }
    function closeModal(id) { const m = document.getElementById(id); if (m) m.style.display = 'none'; }
    window.onclick = function(e) { if (e.target.classList.contains('modal')) e.target.style.display = 'none'; };

    function syncColorInput(val, targetId) {
        const t = document.getElementById(targetId);
        if (t) t.value = val;
    }

    function openAddModal() {
        openModal('addLegendModal');
    }

    function openEditModal(rec) {
        if (!rec) return;
        document.getElementById('editLegendId').value = rec.id || '';
        document.getElementById('editName').value = rec.name || rec.houseNumber || '';
        const color = rec.color_hex || '#6366f1';
        document.getElementById('editColorPicker').value = color;
        document.getElementById('editColorHex').value = color;
        document.getElementById('editOrder').value = rec.display_order || 0;
        document.getElementById('editTop').value = parseFloat(rec.top_position || rec.topPos || 50).toFixed(2);
        document.getElementById('editLeft').value = parseFloat(rec.left_position || rec.leftPos || 50).toFixed(2);
        document.getElementById('editWidth').value = rec.marker_width || 40;
        document.getElementById('editHeight').value = rec.marker_height || 40;
        document.getElementById('editIsActive').checked = parseInt(rec.is_active ?? 1, 10) === 1;

        // Preset icon selection
        const icon = rec.icon_path || rec.markerImage || 'bi-building-fill';
        const isPreset = icon.indexOf('bi-') === 0;
        if (isPreset) {
            document.getElementById('editIconType').value = 'preset';
            document.getElementById('editPresetIcon').value = icon;
            const chips = document.querySelectorAll('#editPresetGrid .preset-chip');
            chips.forEach(c => {
                if (c.getAttribute('data-icon') === icon) c.classList.add('selected');
                else c.classList.remove('selected');
            });
        } else {
            document.getElementById('editIconType').value = 'retain';
        }

        // Photo preview
        const photoWrap = document.getElementById('editCurrentPhotoWrap');
        const photoImg = document.getElementById('editCurrentPhoto');
        const chkRemove = document.getElementById('chkRemovePhoto');
        document.getElementById('editRemoveLandmarkImage').value = '0';
        if (chkRemove) chkRemove.checked = false;

        if (rec.image_path) {
            photoImg.src = rec.image_path;
            photoWrap.style.display = 'flex';
        } else {
            photoWrap.style.display = 'none';
        }

        openModal('editLegendModal');
    }

    function selectEditPreset(icon, el) {
        document.getElementById('editIconType').value = 'preset';
        document.getElementById('editPresetIcon').value = icon;
        document.querySelectorAll('#editPresetGrid .preset-chip').forEach(c => c.classList.remove('selected'));
        if (el) el.classList.add('selected');
    }

    function selectAddPreset(icon, el) {
        document.getElementById('addIconType').value = 'preset';
        document.getElementById('addPresetIcon').value = icon;
        document.querySelectorAll('#addPresetGrid .preset-chip').forEach(c => c.classList.remove('selected'));
        if (el) el.classList.add('selected');
    }

    function confirmDelete(id, name) {
        document.getElementById('deleteLegendId').value = id || '';
        document.getElementById('deleteLegendName').value = name || '';
        document.getElementById('deleteTargetTitle').innerText = '"' + name + '"';
        openModal('deleteConfirmModal');
    }
    </script>
</body>
</html>
