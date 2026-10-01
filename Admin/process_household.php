<?php
if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];
    $returnPage = $_GET['return'] ?? 'controlpanel.php';
    
    $jsonFile = 'data/households.json';
    if (file_exists($jsonFile)) {
        $households = json_decode(file_get_contents($jsonFile), true) ?? [];
        $newHouseholds = array_filter($households, function($hh) use ($deleteId) {
            return $hh['id'] !== $deleteId;
        });
        
        file_put_contents($jsonFile, json_encode(array_values($newHouseholds), JSON_PRETTY_PRINT));
    }
    header("Location: " . $returnPage . "?deleted=1");
    exit();
}

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
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

    $jsonFile = 'data/households.json';
    $households = [];
    
    if (file_exists($jsonFile)) {
        $jsonData = file_get_contents($jsonFile);
        if ($jsonData) {
            $households = json_decode($jsonData, true) ?? [];
        }
    } else {
        if (!is_dir('data')) {
            mkdir('data', 0777, true);
        }
    }

    // Handle File Upload
    $markerImagePath = '';
    if (isset($_FILES['marker_image']) && $_FILES['marker_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = time() . '_' . basename($_FILES['marker_image']['name']);
        $targetFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['marker_image']['tmp_name'], $targetFile)) {
            $markerImagePath = $targetFile;
        }
    }

    if ($householdId) {
        // UPDATE Existing
        foreach ($households as &$hh) {
            if ($hh['id'] === $householdId) {
                $hh['houseNumber'] = $houseNumber;
                $hh['husbandName'] = $husbandName;
                $hh['spouseName'] = $spouseName;
                $hh['markerWidth'] = $markerWidth;
                $hh['markerHeight'] = $markerHeight;
                $hh['topPos'] = $topPos;
                $hh['leftPos'] = $leftPos;
                if ($markerImagePath !== '') {
                    $hh['markerImage'] = $markerImagePath; // Update image if new one uploaded
                }
                break;
            }
        }
    } else {
        // CREATE New
        $newHousehold = [
            'id' => uniqid(),
            'purok' => $purokName,
            'houseNumber' => $houseNumber,
            'husbandName' => $husbandName,
            'spouseName' => $spouseName,
            'markerWidth' => $markerWidth,
            'markerHeight' => $markerHeight,
            'topPos' => $topPos,
            'leftPos' => $leftPos,
            'markerImage' => $markerImagePath,
            'dateAdded' => date('Y-m-d H:i:s')
        ];
        $households[] = $newHousehold;
    }

    file_put_contents($jsonFile, json_encode($households, JSON_PRETTY_PRINT));

    // Redirect back
    $redirectUrl = 'controlpanel.php';
    if ($purokName === 'Purok 1') $redirectUrl = 'Purok 1.php';
    if ($purokName === 'Purok 2') $redirectUrl = 'Purok 2.php';
    if ($purokName === 'Purok 3') $redirectUrl = 'Purok 3.php';
    if ($purokName === 'Legend') $redirectUrl = 'Legend.php';

    header("Location: " . $redirectUrl . "?success=1");
    exit();
}
?>
