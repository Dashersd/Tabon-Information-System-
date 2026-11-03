<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once '../db_connect.php';

$module_name = "Purok Mesaya";
$target_file = "purok1";
$table_name = "purok1_locations";
$map_image_src = "../Images/Purok/Purok%201.jpg";
$map_page_file = "Purok 1.php";
$resident_page_file = "Resident 1.php";

$post_error = null;

// -------------------------------------------------------------
// SELF-HANDLING POST PROCESSOR (100% RELIABLE ON INFINITYFREE)
// Prevents cross-file redirect drops and InfinityFree anti-bot blocks
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. ACTION: DELETE
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $deleteId = (int)($_POST['delete_id'] ?? 0);
        $houseNo  = trim($_POST['house_number'] ?? '');

        if ($deleteId > 0 || !empty($houseNo)) {
            $deleted = false;
            if (isset($pdo) && $pdo instanceof PDO) {
                try {
                    $sel = null;
                    if ($deleteId > 0) {
                        $sel = $pdo->prepare("SELECT id, house_image, marker_image FROM `$table_name` WHERE id = ? LIMIT 1");
                        $sel->execute([$deleteId]);
                    } elseif (!empty($houseNo)) {
                        $sel = $pdo->prepare("SELECT id, house_image, marker_image FROM `$table_name` WHERE house_number = ? LIMIT 1");
                        $sel->execute([$houseNo]);
                    }
                    $row = $sel ? $sel->fetch(PDO::FETCH_ASSOC) : null;
                    $actualId = $row ? (int)$row['id'] : $deleteId;

                    if ($actualId > 0) {
                        $del = $pdo->prepare("DELETE FROM `$table_name` WHERE id = ?");
                        $del->execute([$actualId]);
                        $deleted = true;
                    } elseif (!empty($houseNo)) {
                        $del = $pdo->prepare("DELETE FROM `$table_name` WHERE house_number = ?");
                        $del->execute([$houseNo]);
                        $deleted = true;
                    }

                    if ($row) {
                        $imgPaths = [$row['house_image'] ?? '', $row['marker_image'] ?? ''];
                        foreach ($imgPaths as $img) {
                            if (!empty($img) && strpos($img, 'uploads/') === 0) {
                                if (file_exists(__DIR__ . '/' . $img)) @unlink(__DIR__ . '/' . $img);
                                elseif (file_exists($img)) @unlink($img);
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    $post_error = "Delete failed: " . $e->getMessage();
                }
            }
            if (!$deleted && isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
                if ($deleteId > 0) {
                    $conn->query("DELETE FROM `$table_name` WHERE id = " . (int)$deleteId);
                    $deleted = true;
                } elseif (!empty($houseNo)) {
                    $conn->query("DELETE FROM `$table_name` WHERE house_number = '" . $conn->real_escape_string($houseNo) . "'");
                    $deleted = true;
                }
            }
            if (!$post_error) {
                header("Location: " . $map_page_file . "?deleted=" . ($deleted ? '1' : '0'));
                exit();
            }
        }
    }

    // 2. ACTION: SAVE (INSERT OR UPDATE)
    if ((isset($_POST['action']) && $_POST['action'] === 'save') || isset($_POST['house_number'])) {
        $householdId = !empty($_POST['household_id']) ? (int)$_POST['household_id'] : null;
        $houseNumber = trim($_POST['house_number'] ?? '');
        $husbandName = trim($_POST['husband_name'] ?? '');
        $spouseName  = trim($_POST['spouse_name'] ?? '');
        $markerWidth = !empty($_POST['marker_width']) ? (int)$_POST['marker_width'] : 40;
        $markerHeight = !empty($_POST['marker_height']) ? (int)$_POST['marker_height'] : 40;

        // Fallback: If householdId is missing but houseNumber already exists, update that record
        if (!$householdId && !empty($houseNumber) && isset($pdo) && $pdo instanceof PDO) {
            try {
                $chkExisting = $pdo->prepare("SELECT id FROM `$table_name` WHERE house_number = ? LIMIT 1");
                $chkExisting->execute([$houseNumber]);
                $exId = $chkExisting->fetchColumn();
                if ($exId) {
                    $householdId = (int)$exId;
                }
            } catch (\Throwable $ce) {}
        }

        $topPos = isset($_POST['top_position']) ? $_POST['top_position'] : ($_POST['top_pos'] ?? '50.00');
        $leftPos = isset($_POST['left_position']) ? $_POST['left_position'] : ($_POST['left_pos'] ?? '50.00');
        $topPos = number_format((float)$topPos, 2, '.', '');
        $leftPos = number_format((float)$leftPos, 2, '.', '');

        // Children JSON Array
        $childrenList = [];
        if (isset($_POST['children'])) {
            if (is_array($_POST['children'])) {
                foreach ($_POST['children'] as $child) {
                    $tr = trim((string)$child);
                    if ($tr !== '') $childrenList[] = $tr;
                }
            } elseif (is_string($_POST['children'])) {
                $dec = json_decode($_POST['children'], true);
                if (is_array($dec)) $childrenList = array_values(array_filter(array_map('trim', $dec)));
            }
        }
        $childrenJson = json_encode($childrenList, JSON_UNESCAPED_UNICODE);

        // Upload handling
        $houseDir = __DIR__ . '/uploads/houses/';
        $markerDir = __DIR__ . '/uploads/markers/';
        if (!is_dir($houseDir)) @mkdir($houseDir, 0777, true);
        if (!is_dir($markerDir)) @mkdir($markerDir, 0777, true);

        $houseImagePath = '';
        $markerImagePath = '';

        if (isset($_FILES['house_image']) && $_FILES['house_image']['error'] === UPLOAD_ERR_OK) {
            $name = 'house_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES['house_image']['name']));
            if (move_uploaded_file($_FILES['house_image']['tmp_name'], $houseDir . $name)) {
                $houseImagePath = 'uploads/houses/' . $name;
            }
        }

        if (isset($_FILES['marker_image']) && $_FILES['marker_image']['error'] === UPLOAD_ERR_OK) {
            $name = 'marker_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES['marker_image']['name']));
            if (move_uploaded_file($_FILES['marker_image']['tmp_name'], $markerDir . $name)) {
                $markerImagePath = 'uploads/markers/' . $name;
            }
        }

        try {
            if ($householdId) {
                // UPDATE RECORD
                $updateFields = [
                    'house_number = :house_number',
                    'husband_name = :husband_name',
                    'spouse_name = :spouse_name',
                    'children = :children',
                    'marker_width = :marker_width',
                    'marker_height = :marker_height',
                    'coordinate_x = :coordinate_x',
                    'coordinate_y = :coordinate_y'
                ];
                $params = [
                    ':house_number' => $houseNumber,
                    ':husband_name' => $husbandName,
                    ':spouse_name'  => ($spouseName !== '' ? $spouseName : null),
                    ':children'     => $childrenJson,
                    ':marker_width' => $markerWidth,
                    ':marker_height'=> $markerHeight,
                    ':coordinate_x' => $leftPos,
                    ':coordinate_y' => $topPos,
                    ':id'           => $householdId
                ];
                if ($houseImagePath !== '') {
                    $updateFields[] = 'house_image = :house_image';
                    $params[':house_image'] = $houseImagePath;
                }
                if ($markerImagePath !== '') {
                    $updateFields[] = 'marker_image = :marker_image';
                    $params[':marker_image'] = $markerImagePath;
                }

                $stmt = $pdo->prepare("UPDATE `$table_name` SET " . implode(', ', $updateFields) . " WHERE id = :id");
                $stmt->execute($params);

                header("Location: " . $map_page_file . "?updated=1");
                exit();
            } else {
                // INSERT NEW RECORD
                $stmt = $pdo->prepare("INSERT INTO `$table_name` (house_number, husband_name, spouse_name, children, house_image, marker_image, marker_width, marker_height, coordinate_x, coordinate_y) VALUES (:house_number, :husband_name, :spouse_name, :children, :house_image, :marker_image, :marker_width, :marker_height, :coordinate_x, :coordinate_y)");
                $stmt->execute([
                    ':house_number' => $houseNumber,
                    ':husband_name' => $husbandName,
                    ':spouse_name'  => ($spouseName !== '' ? $spouseName : null),
                    ':children'     => $childrenJson,
                    ':house_image'  => $houseImagePath,
                    ':marker_image' => $markerImagePath,
                    ':marker_width' => $markerWidth,
                    ':marker_height'=> $markerHeight,
                    ':coordinate_x' => $leftPos,
                    ':coordinate_y' => $topPos
                ]);

                header("Location: " . $map_page_file . "?success=1");
                exit();
            }
        } catch (\PDOException $e) {
            $post_error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch existing markers for Purok 1
$existing_markers = [];
$db_debug_info = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM `$table_name` ORDER BY id ASC");
    $stmt->execute();
    $existing_markers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $db_debug_info['marker_ids'] = array_column($existing_markers, 'id');
} catch (PDOException $e) {
    $error_msg = $e->getMessage();
    $db_debug_info['error'] = $error_msg;
}

// Log status for diagnostics
$logLine = "[" . date('Y-m-d H:i:s') . "] PUROK1 VIEW: db=" . ($dbname ?? 'N/A') . " | count=" . count($existing_markers) . " | ids=" . implode(',', $db_debug_info['marker_ids'] ?? []) . " | err=" . ($error_msg ?? 'none') . "\n";
@file_put_contents(__DIR__ . '/infinity_debug.log', $logLine, FILE_APPEND);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Spot Map - <?= $module_name ?> | Brgy Tabon</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin Common & Page Stylesheet -->
    <link rel="stylesheet" href="Admin CSS/admin_common.css?v=<?= time() ?>">
    <style>
        .map-wrapper, .map-container {
            position: relative;
            width: 100%;
            height: 520px;
            min-height: 520px;
            background: #1e293b;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
            margin-bottom: 30px;
            border: 2px solid #cbd5e1;
            user-select: none;
            -webkit-user-select: none;
        }
        .spot-map-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            user-select: none;
            -webkit-user-select: none;
            -webkit-user-drag: none;
            pointer-events: none;
        }
        .form-card {
            background: #ffffff;
            padding: 35px 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            border-top: 4px solid #223c3b;
        }
        .form-title {
            margin: 0 0 20px 0;
            color: #223c3b;
            font-size: 1.3rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 0.88rem;
            margin-bottom: 8px;
            color: #334155;
        }
        .form-control {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-family: inherit;
            font-size: 0.92rem;
            outline: none;
            transition: all 0.2s;
            box-sizing: border-box;
        }
        .form-control:focus {
            border-color: #2c6e49;
            box-shadow: 0 0 0 3px rgba(44, 110, 73, 0.15);
        }
        .row {
            display: flex;
            gap: 16px;
            margin-bottom: 16px;
        }
        .row .col { flex: 1; }
        .btn-add-child {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
            border-radius: 6px;
            padding: 6px 14px;
            font-size: 0.82rem;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-add-child:hover {
            background: #bae6fd;
            color: #0284c7;
        }
        .btn-save {
            width: 100%;
            background: #2c6e49;
            color: #ffffff;
            padding: 14px;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.2s;
            box-shadow: 0 4px 12px rgba(44, 110, 73, 0.2);
        }
        .btn-save:hover { background: #1e4f34; }
        .alert-banner {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 500;
            font-size: 0.95rem;
        }
        .alert-success { background: #d1fae5; border-left: 5px solid #10b981; color: #065f46; }
        .alert-updated { background: #e0f2fe; border-left: 5px solid #0284c7; color: #075985; }
        .saved-marker-pin {
            position: absolute;
            transform: translate(-50%, -100%);
            z-index: 50;
            cursor: pointer;
            transition: transform 0.2s ease, filter 0.2s ease;
            user-select: none;
            -webkit-user-select: none;
            -webkit-user-drag: none;
        }
        .saved-marker-pin:hover {
            transform: translate(-50%, -115%) scale(1.15);
            filter: drop-shadow(0 6px 10px rgba(0,0,0,0.5));
            z-index: 100;
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
                <li><a href="controlpanel.php"><i class="fa-solid fa-gauge"></i> Control Panel</a></li>
                <li><a href="about.php"><i class="fa-solid fa-circle-info"></i> About</a></li>
                <li>
                    <a class="dropdown-btn active"><i class="fa-solid fa-map-location-dot"></i> SpotMap Manage <i class="fa-solid fa-caret-down" style="margin-left: auto;"></i></a>
                    <div class="dropdown-container" style="display: block;">
                        <a href="Legend.php">Legend</a>
                        <a href="Purok 1.php" class="active" style="color: #ffffff; font-weight: 600;">Purok Mesaya</a>
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

    <!-- Main Content Canvas -->
    <main class="main-content">
        <div class="top-bar">
            <div>
                <h1 class="page-title" style="margin: 0; color: #1e293b; font-size: 24px; font-weight: 700;">Manage <?= $module_name ?> Spot Map</h1>
                <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.9rem;">Plot household locations, manage heads, dependents, and pin coordinates.</p>
            </div>
            <div class="admin-profile" style="display: flex; align-items: center; gap: 15px;">
                <div class="admin-info" style="text-align: right;">
                    <span class="admin-name" style="font-weight: 700; color: #1e293b; font-size: 14px; display: block;">System Admin</span>
                    <span class="admin-role" style="color: #64748b; font-size: 12px;">Administrator</span>
                </div>
                <div class="admin-avatar" style="width: 40px; height: 40px; background-color: #223c3b; color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px;">A</div>
            </div>
        </div>

        <?php if (!empty($post_error)): ?>
            <div class="alert-banner" style="background: #fef2f2; border-left: 5px solid #dc2626; color: #991b1b;">
                <div><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($post_error) ?></div>
                <button onclick="this.parentElement.style.display='none'" style="border:none;background:none;font-size:1.2rem;cursor:pointer;color:#991b1b;">&times;</button>
            </div>
        <?php elseif (isset($_GET['success'])): ?>
            <div class="alert-banner alert-success">
                <div><i class="fa-solid fa-circle-check"></i> Household pin successfully plotted and saved to <?= $module_name ?>!</div>
                <button onclick="this.parentElement.style.display='none'" style="border:none;background:none;font-size:1.2rem;cursor:pointer;color:#065f46;">&times;</button>
            </div>
        <?php elseif (isset($_GET['updated'])): ?>
            <div class="alert-banner alert-updated">
                <div><i class="fa-solid fa-circle-check"></i> Household details and map coordinates successfully updated!</div>
                <button onclick="this.parentElement.style.display='none'" style="border:none;background:none;font-size:1.2rem;cursor:pointer;color:#075985;">&times;</button>
            </div>
        <?php elseif (isset($_GET['deleted']) && $_GET['deleted'] === '1'): ?>
            <div class="alert-banner" style="background: #fee2e2; border-left: 5px solid #ef4444; color: #991b1b;">
                <div><i class="fa-solid fa-circle-check"></i> Household pin successfully removed from <?= $module_name ?> map!</div>
                <button onclick="this.parentElement.style.display='none'" style="border:none;background:none;font-size:1.2rem;cursor:pointer;color:#991b1b;">&times;</button>
            </div>
        <?php elseif (isset($_GET['deleted']) && $_GET['deleted'] === '0'): ?>
            <div class="alert-banner" style="background: #fffbeb; border-left: 5px solid #f59e0b; color: #92400e;">
                <div><i class="fa-solid fa-triangle-exclamation"></i> Household record was already removed or not found.</div>
                <button onclick="this.parentElement.style.display='none'" style="border:none;background:none;font-size:1.2rem;cursor:pointer;color:#92400e;">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Spot Map Canvas with Saved Markers & Tooltips -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <h3 style="margin: 0; color: #223c3b; font-size: 1.15rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-map-location-dot" style="color: #2c6e49;"></i> <?= $module_name ?> Map Canvas
            </h3>
            <span style="font-size: 0.85rem; color: #64748b;">
                <i class="fa-solid fa-hand-pointer"></i> Drag preview pin to position or click saved pins to view dependents
            </span>
        </div>
        <div class="map-wrapper map-container" id="map-container">
            <img src="<?= $map_image_src ?>" alt="<?= $module_name ?> Map" class="spot-map-img" id="base-map-img" onerror="this.onerror=null; this.src='../Images/Purok/Purok 1.jpg';" draggable="false">
            
            <?php foreach ($existing_markers as $marker): ?>
                <?php if (!empty($marker['marker_image'])): ?>
                    <img src="<?= htmlspecialchars($marker['marker_image']) ?>" 
                         class="saved-marker-pin"
                         draggable="false"
                         data-id="<?= $marker['id'] ?>"
                         data-house-number="<?= htmlspecialchars($marker['house_number']) ?>"
                         data-husband="<?= htmlspecialchars($marker['husband_name']) ?>"
                         data-spouse="<?= htmlspecialchars($marker['spouse_name'] ?? '') ?>"
                         data-children="<?= htmlspecialchars($marker['children'] ?? '[]') ?>"
                         data-house-image="<?= htmlspecialchars($marker['house_image']) ?>"
                         data-top="<?= $marker['coordinate_y'] ?>"
                         data-left="<?= $marker['coordinate_x'] ?>"
                         data-width="<?= $marker['marker_width'] ?>"
                         data-height="<?= $marker['marker_height'] ?>"
                         style="top: <?= $marker['coordinate_y'] ?>%; left: <?= $marker['coordinate_x'] ?>%; width: <?= $marker['marker_width'] ?>px; height: <?= $marker['marker_height'] ?>px;">
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- Household Data Placement Form -->
        <div class="form-card">
            <h3 class="form-title"><i class="fa-solid fa-house-chimney-medical"></i> Plot Household & Dependents</h3>
            
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="target_file" value="<?= $target_file ?>">
                <input type="hidden" name="household_id" id="household_id" value="">

                <div class="form-group">
                    <label>House Number <span style="color:#e11d48;">*</span></label>
                    <input type="text" name="house_number" placeholder="e.g. 102-A" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Household Heads</label>
                    <div class="row">
                        <div class="col">
                            <input type="text" name="husband_name" placeholder="Name of Husband / Head (e.g. Juan Dela Cruz)" class="form-control" required>
                        </div>
                        <div class="col">
                            <input type="text" name="spouse_name" placeholder="Name of Spouse / Maiden (e.g. Maria Santos)" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Dynamic Children / Dependents Builder -->
                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label style="margin: 0;"><i class="fa-solid fa-children"></i> Children / Dependents (Optional)</label>
                        <button type="button" id="btn-add-child" class="btn-add-child">
                            <i class="fa-solid fa-plus-circle"></i> + Add Child
                        </button>
                    </div>
                    <div id="children-container" style="display: flex; flex-direction: column; gap: 8px;"></div>
                    <small style="color: #64748b; font-size: 0.8rem; margin-top: 4px; display: block;">Click "+ Add Child" if this household has children or dependents residing at this location.</small>
                </div>

                <div class="row">
                    <div class="col form-group">
                        <label>House Actual Photo (Viewed upon inspection) <span style="color:#e11d48;">*</span></label>
                        <input type="file" name="house_image" id="house_image" accept="image/*" class="form-control" required>
                    </div>
                    <div class="col form-group">
                        <label>Marker Icon (Graphic shown on map canvas) <span style="color:#e11d48;">*</span></label>
                        <input type="file" name="marker_image" id="marker_image" accept="image/*" class="form-control" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col form-group">
                        <label>Marker Width (px)</label>
                        <input type="number" name="marker_width" value="40" class="form-control">
                    </div>
                    <div class="col form-group">
                        <label>Marker Height (px)</label>
                        <input type="number" name="marker_height" value="40" class="form-control">
                    </div>
                </div>

                <div class="row">
                    <div class="col form-group">
                        <label>Top Position (%)</label>
                        <input type="number" step="0.01" name="top_position" value="50.00" class="form-control" required>
                    </div>
                    <div class="col form-group">
                        <label>Left Position (%)</label>
                        <input type="number" step="0.01" name="left_position" value="50.00" class="form-control" required>
                    </div>
                </div>

                <button type="submit" id="btn-save-record" class="btn-save">
                    <i class="fa-solid fa-map-pin"></i> Save to Map
                </button>
            </form>
        </div>
    </main>

    <!-- Interactive Map Script -->
    <script src="js/admin_spotmap.js?v=<?= time() ?>"></script>
</body>
</html>
