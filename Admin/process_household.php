<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once '../db_connect.php';

// Handle Delete (Optional: You can add delete logic here if needed later)
if (isset($_GET['delete_id']) && isset($_GET['purok_name'])) {
    $deleteId = $_GET['delete_id'];
    $purokName = $_GET['purok_name'];
    $returnPage = $_GET['return'] ?? 'controlpanel.php';
    
    $tableName = '';
    if ($purokName === 'Purok 1') $tableName = 'purok1_locations';
    elseif ($purokName === 'Purok 2') $tableName = 'purok2_locations';
    elseif ($purokName === 'Purok 3') $tableName = 'purok3_locations';

    if ($tableName) {
        $stmt = $pdo->prepare("DELETE FROM $tableName WHERE id = ?");
        $stmt->execute([$deleteId]);
    }
    header("Location: " . $returnPage . "?deleted=1");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $householdId = $_POST['household_id'] ?? null;
    $houseNumber = $_POST['house_number'] ?? '';
    $husbandName = $_POST['husband_name'] ?? '';
    $spouseName = $_POST['spouse_name'] ?? '';
    $markerWidth = $_POST['marker_width'] ?? '40';
    $markerHeight = $_POST['marker_height'] ?? '40';
    $topPos = $_POST['top_pos'] ?? '50.00';
    $leftPos = $_POST['left_pos'] ?? '50.00';
    $purokName = $_POST['purok_name'] ?? 'Unknown';

    // Determine the correct table and redirect page based on purok_name
    $tableName = '';
    $redirectUrl = 'controlpanel.php';
    if ($purokName === 'Purok 1') {
        $tableName = 'purok1_locations';
        $redirectUrl = 'Purok 1.php';
    } elseif ($purokName === 'Purok 2') {
        $tableName = 'purok2_locations';
        $redirectUrl = 'Purok 2.php';
    } elseif ($purokName === 'Purok 3') {
        $tableName = 'purok3_locations';
        $redirectUrl = 'Purok 3.php';
    } else {
        die("Invalid Purok selected.");
    }

    // Handle File Uploads
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $houseImagePath = '';
    $markerImagePath = '';

    // Upload House Image
    if (isset($_FILES['house_image']) && $_FILES['house_image']['error'] === UPLOAD_ERR_OK) {
        $houseFileName = 'house_' . time() . '_' . basename($_FILES['house_image']['name']);
        $targetHouseFile = $uploadDir . $houseFileName;
        if (move_uploaded_file($_FILES['house_image']['tmp_name'], $targetHouseFile)) {
            $houseImagePath = $targetHouseFile;
        }
    }

    // Upload Marker Image
    if (isset($_FILES['marker_image']) && $_FILES['marker_image']['error'] === UPLOAD_ERR_OK) {
        $markerFileName = 'marker_' . time() . '_' . basename($_FILES['marker_image']['name']);
        $targetMarkerFile = $uploadDir . $markerFileName;
        if (move_uploaded_file($_FILES['marker_image']['tmp_name'], $targetMarkerFile)) {
            $markerImagePath = $targetMarkerFile;
        }
    }

    try {
        if ($householdId) {
            // UPDATE Existing
            $updateFields = [
                'house_number = :house_number',
                'husband_name = :husband_name',
                'spouse_name = :spouse_name',
                'marker_width = :marker_width',
                'marker_height = :marker_height',
                'coordinate_x = :coordinate_x',
                'coordinate_y = :coordinate_y'
            ];
            
            $params = [
                ':house_number' => $houseNumber,
                ':husband_name' => $husbandName,
                ':spouse_name' => $spouseName,
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

            $sql = "UPDATE $tableName SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

        } else {
            // CREATE New
            $sql = "INSERT INTO $tableName (house_number, husband_name, spouse_name, house_image, marker_image, marker_width, marker_height, coordinate_x, coordinate_y) 
                    VALUES (:house_number, :husband_name, :spouse_name, :house_image, :marker_image, :marker_width, :marker_height, :coordinate_x, :coordinate_y)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':house_number' => $houseNumber,
                ':husband_name' => $husbandName,
                ':spouse_name' => $spouseName,
                ':house_image' => $houseImagePath,
                ':marker_image' => $markerImagePath,
                ':marker_width' => $markerWidth,
                ':marker_height' => $markerHeight,
                ':coordinate_x' => $leftPos,
                ':coordinate_y' => $topPos
            ]);
        }

        header("Location: " . $redirectUrl . "?success=1");
        exit();

    } catch (PDOException $e) {
        die("Error saving household: " . $e->getMessage());
    }
} else {
    header("Location: controlpanel.php");
    exit();
}
?>
