<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once '../db_connect.php';

// Helper to resolve table and page
function resolvePurokDetails($purokInput) {
    $input = strtolower(str_replace([' ', '_'], '', trim($purokInput)));
    if ($input === 'purok1') {
        return ['table' => 'purok1_locations', 'name' => 'Purok 1', 'page' => 'Purok 1.php', 'resident_page' => 'Resident 1.php'];
    } elseif ($input === 'purok2') {
        return ['table' => 'purok2_locations', 'name' => 'Purok 2', 'page' => 'Purok 2.php', 'resident_page' => 'Resident 2.php'];
    } elseif ($input === 'purok3') {
        return ['table' => 'purok3_locations', 'name' => 'Purok 3', 'page' => 'Purok 3.php', 'resident_page' => 'Resident 3.php'];
    }
    return null;
}

// 1. Handle Delete Request
if (isset($_GET['delete_id']) && (isset($_GET['purok_name']) || isset($_GET['target_file']))) {
    $deleteId = (int)$_GET['delete_id'];
    $purokInput = $_GET['purok_name'] ?? $_GET['target_file'];
    $details = resolvePurokDetails($purokInput);
    $returnPage = $_GET['return'] ?? ($details ? $details['resident_page'] : 'controlpanel.php');

    if ($details && $deleteId > 0) {
        $tableName = $details['table'];
        // Optional: fetch image paths to delete files if needed
        $stmt = $pdo->prepare("DELETE FROM `$tableName` WHERE id = ?");
        $stmt->execute([$deleteId]);
    }
    header("Location: " . $returnPage . "?deleted=1");
    exit();
}

// 2. Handle POST Request (Insert / Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $householdId = !empty($_POST['household_id']) ? (int)$_POST['household_id'] : null;
    $purokInput = $_POST['target_file'] ?? $_POST['purok_name'] ?? '';
    $details = resolvePurokDetails($purokInput);

    if (!$details) {
        die("Invalid Purok selected.");
    }

    $tableName = $details['table'];
    $redirectUrl = $details['page'];

    $houseNumber = trim($_POST['house_number'] ?? '');
    $husbandName = trim($_POST['husband_name'] ?? '');
    $spouseName = trim($_POST['spouse_name'] ?? '');
    $markerWidth = !empty($_POST['marker_width']) ? (int)$_POST['marker_width'] : 40;
    $markerHeight = !empty($_POST['marker_height']) ? (int)$_POST['marker_height'] : 40;
    
    // Accept either top_pos/left_pos or top_position/left_position
    $topPos = isset($_POST['top_position']) ? $_POST['top_position'] : ($_POST['top_pos'] ?? '50.00');
    $leftPos = isset($_POST['left_position']) ? $_POST['left_position'] : ($_POST['left_pos'] ?? '50.00');
    $topPos = number_format((float)$topPos, 2, '.', '');
    $leftPos = number_format((float)$leftPos, 2, '.', '');

    // Process Children JSON Array
    $childrenList = [];
    if (isset($_POST['children'])) {
        if (is_array($_POST['children'])) {
            foreach ($_POST['children'] as $child) {
                $trimmed = trim($child);
                if ($trimmed !== '') {
                    $childrenList[] = $trimmed;
                }
            }
        } elseif (is_string($_POST['children'])) {
            $decoded = json_decode($_POST['children'], true);
            if (is_array($decoded)) {
                $childrenList = $decoded;
            }
        }
    }
    $childrenJson = json_encode($childrenList, JSON_UNESCAPED_UNICODE);

    // Setup Upload Directories
    $houseDir = 'uploads/houses/';
    $markerDir = 'uploads/markers/';
    if (!is_dir($houseDir)) mkdir($houseDir, 0777, true);
    if (!is_dir($markerDir)) mkdir($markerDir, 0777, true);

    $houseImagePath = '';
    $markerImagePath = '';

    // Upload House Photo
    if (isset($_FILES['house_image']) && $_FILES['house_image']['error'] === UPLOAD_ERR_OK) {
        $safeName = 'house_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES['house_image']['name']));
        $dest = $houseDir . $safeName;
        if (move_uploaded_file($_FILES['house_image']['tmp_name'], $dest)) {
            $houseImagePath = $dest;
        }
    }

    // Upload Marker Icon
    if (isset($_FILES['marker_image']) && $_FILES['marker_image']['error'] === UPLOAD_ERR_OK) {
        $safeName = 'marker_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES['marker_image']['name']));
        $dest = $markerDir . $safeName;
        if (move_uploaded_file($_FILES['marker_image']['tmp_name'], $dest)) {
            $markerImagePath = $dest;
        }
    }

    try {
        if ($householdId) {
            // UPDATE EXISTING RECORD
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
                ':spouse_name' => $spouseName,
                ':children' => $childrenJson,
                ':marker_width' => $markerWidth,
                ':marker_height' => $markerHeight,
                ':coordinate_x' => $leftPos,
                ':coordinate_y' => $topPos,
                ':id' => $householdId
            ];

            if ($houseImagePath !== '') {
                $updateFields[] = 'house_image = :house_image';
                $params[':house_image'] = $houseImagePath;
            }
            if ($markerImagePath !== '') {
                $updateFields[] = 'marker_image = :marker_image';
                $params[':marker_image'] = $markerImagePath;
            }

            $sql = "UPDATE `$tableName` SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            header("Location: " . $redirectUrl . "?updated=1");
            exit();

        } else {
            // CREATE NEW RECORD
            $sql = "INSERT INTO `$tableName` 
                    (house_number, husband_name, spouse_name, children, house_image, marker_image, marker_width, marker_height, coordinate_x, coordinate_y) 
                    VALUES 
                    (:house_number, :husband_name, :spouse_name, :children, :house_image, :marker_image, :marker_width, :marker_height, :coordinate_x, :coordinate_y)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':house_number' => $houseNumber,
                ':husband_name' => $husbandName,
                ':spouse_name' => $spouseName,
                ':children' => $childrenJson,
                ':house_image' => $houseImagePath,
                ':marker_image' => $markerImagePath,
                ':marker_width' => $markerWidth,
                ':marker_height' => $markerHeight,
                ':coordinate_x' => $leftPos,
                ':coordinate_y' => $topPos
            ]);

            header("Location: " . $redirectUrl . "?success=1");
            exit();
        }

    } catch (PDOException $e) {
        die("Database Error saving household: " . htmlspecialchars($e->getMessage()));
    }
} else {
    header("Location: controlpanel.php");
    exit();
}
