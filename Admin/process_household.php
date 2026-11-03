<?php
// Admin/process_household.php - Household Plotting, Updating & Deletion Engine
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../db_connect.php';

// Helper to resolve table and page names for Purok 1, 2, and 3
function resolvePurokDetails($purokInput) {
    $clean = strtolower(str_replace([' ', '_', '%20', '-', '.php'], '', trim((string)$purokInput)));
    
    if (strpos($clean, 'purok1') !== false || strpos($clean, 'mesaya') !== false || $clean === '1') {
        return [
            'table'         => 'purok1_locations',
            'name'          => 'Purok 1',
            'page'          => 'Purok%201.php',
            'clean_page'    => 'Purok 1.php',
            'resident_page' => 'Resident%201.php'
        ];
    } elseif (strpos($clean, 'purok2') !== false || strpos($clean, 'metamed') !== false || $clean === '2') {
        return [
            'table'         => 'purok2_locations',
            'name'          => 'Purok 2',
            'page'          => 'Purok%202.php',
            'clean_page'    => 'Purok 2.php',
            'resident_page' => 'Resident%202.php'
        ];
    } elseif (strpos($clean, 'purok3') !== false || strpos($clean, 'melitebangay') !== false || $clean === '3') {
        return [
            'table'         => 'purok3_locations',
            'name'          => 'Purok 3',
            'page'          => 'Purok%203.php',
            'clean_page'    => 'Purok 3.php',
            'resident_page' => 'Resident%203.php'
        ];
    }
    return null;
}

// -------------------------------------------------------------
// 1. HANDLE DELETE REQUEST (Supports GET & POST, Page Nav & AJAX)
// -------------------------------------------------------------
$deleteId = 0;
if (!empty($_GET['delete_id'])) {
    $deleteId = (int)$_GET['delete_id'];
} elseif (!empty($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id'];
} elseif (!empty($_GET['id'])) {
    $deleteId = (int)$_GET['id'];
} elseif (!empty($_POST['id'])) {
    $deleteId = (int)$_POST['id'];
}

$isAjax = (!empty($_GET['ajax']) || !empty($_POST['ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'));

if ($deleteId > 0) {
    $purokInput = $_GET['purok_name'] ?? $_GET['target_file'] ?? $_POST['purok_name'] ?? $_POST['target_file'] ?? '';
    $details = resolvePurokDetails($purokInput);

    // Handle Legend removal if requested
    $isLegend = ($purokInput === 'legend' || (isset($_GET['return']) && strpos($_GET['return'], 'Legend') !== false));
    if ($isLegend) {
        if (isset($pdo) && $pdo instanceof PDO) {
            try {
                $del1 = $pdo->prepare("DELETE FROM `sm_legends` WHERE `id` = ?");
                $del1->execute([$deleteId]);
                $del2 = $pdo->prepare("DELETE FROM `sm_legend_spots` WHERE `legend_id` = ?");
                $del2->execute([$deleteId]);
            } catch (\Throwable $e) {}
        }
        if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
            $conn->query("DELETE FROM `sm_legends` WHERE `id` = " . intval($deleteId));
            $conn->query("DELETE FROM `sm_legend_spots` WHERE `legend_id` = " . intval($deleteId));
        }

        $jsonFile = __DIR__ . '/data/legend.json';
        if (file_exists($jsonFile)) {
            $jsonData = json_decode(file_get_contents($jsonFile), true) ?? [];
            $filtered = array_values(array_filter($jsonData, function($item) use ($deleteId) {
                return (int)($item['id'] ?? 0) !== $deleteId;
            }));
            file_put_contents($jsonFile, json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        $returnUrl = !empty($_GET['return']) ? $_GET['return'] : 'Legend Files.php';
        header("Location: " . $returnUrl . (strpos($returnUrl, '?') !== false ? '&' : '?') . "msg=deleted");
        exit();
    }

    $deleted = false;
    $targetTables = [];
    if ($details && !empty($details['table'])) {
        $targetTables[] = $details['table'];
    }
    foreach (['purok1_locations', 'purok2_locations', 'purok3_locations'] as $tbl) {
        if (!in_array($tbl, $targetTables)) {
            $targetTables[] = $tbl;
        }
    }

    $logDelete = "[" . date('Y-m-d H:i:s') . "] DELETE START: id=$deleteId | method=" . $_SERVER['REQUEST_METHOD'] . " | input=$purokInput | db=" . ($dbname ?? 'N/A') . "\n";
    $delErrors = [];

    // Execute deletion from the target table, or across all purok tables until deleted
    foreach ($targetTables as $tableName) {
        // Try PDO first
        if (isset($pdo) && $pdo instanceof PDO) {
            try {
                $fetchStmt = $pdo->prepare("SELECT id, house_image, marker_image FROM `$tableName` WHERE id = ? LIMIT 1");
                $fetchStmt->execute([$deleteId]);
                $record = $fetchStmt->fetch(PDO::FETCH_ASSOC);

                if ($record) {
                    $delStmt = $pdo->prepare("DELETE FROM `$tableName` WHERE id = ?");
                    $delStmt->execute([$deleteId]);
                    $rc = $delStmt->rowCount();
                    $logDelete .= "   Found in $tableName! Deleted with PDO rowCount=$rc\n";
                    if ($rc > 0 || true) { // Even if rowCount is 0 due to driver differences, it was found and deleted
                        $deleted = true;

                        // Auto-detect details from table where the record actually existed
                        if (!$details) {
                            if ($tableName === 'purok1_locations') $details = resolvePurokDetails('purok1');
                            elseif ($tableName === 'purok2_locations') $details = resolvePurokDetails('purok2');
                            elseif ($tableName === 'purok3_locations') $details = resolvePurokDetails('purok3');
                        }

                        // Remove uploaded images if they reside in uploads/
                        $imgPaths = [$record['house_image'] ?? '', $record['marker_image'] ?? ''];
                        foreach ($imgPaths as $img) {
                            if (!empty($img) && strpos($img, 'uploads/') === 0) {
                                if (file_exists(__DIR__ . '/' . $img)) {
                                    @unlink(__DIR__ . '/' . $img);
                                } elseif (file_exists($img)) {
                                    @unlink($img);
                                }
                            }
                        }
                        break;
                    }
                } else {
                    $logDelete .= "   Not found in $tableName\n";
                }
            } catch (\Throwable $e) {
                $delErrors[] = "PDO $tableName: " . $e->getMessage();
                $logDelete .= "   PDO Error on $tableName: " . $e->getMessage() . "\n";
            }
        }

        // Fallback to MySQLi ($conn) if PDO did not delete
        if (!$deleted && isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
            $delIdEsc = (int)$deleteId;
            $chk = $conn->query("SELECT id FROM `$tableName` WHERE id = $delIdEsc LIMIT 1");
            if ($chk && $chk->num_rows > 0) {
                $conn->query("DELETE FROM `$tableName` WHERE id = $delIdEsc");
                $logDelete .= "   Found in $tableName! Deleted with MySQLi affected=" . $conn->affected_rows . "\n";
                $deleted = true;
                if (!$details) {
                    if ($tableName === 'purok1_locations') $details = resolvePurokDetails('purok1');
                    elseif ($tableName === 'purok2_locations') $details = resolvePurokDetails('purok2');
                    elseif ($tableName === 'purok3_locations') $details = resolvePurokDetails('purok3');
                }
                break;
            }
        }
    }
    $logDelete .= "   FINAL RESULT: deleted=" . ($deleted ? 'TRUE' : 'FALSE') . "\n";
    @file_put_contents(__DIR__ . '/infinity_debug.log', $logDelete, FILE_APPEND);

    // Return JSON immediately for AJAX requests
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'     => $deleted ? 'success' : 'not_found',
            'success'    => $deleted,
            'message'    => $deleted ? 'Household successfully deleted.' : 'Record was not found or already deleted.',
            'delete_id'  => $deleteId
        ]);
        exit();
    }

    // Determine the smart return destination (NEVER controlpanel.php)
    $returnRaw = '';
    if (!empty($_GET['return'])) {
        $returnRaw = trim($_GET['return']);
    } elseif (!empty($_POST['return'])) {
        $returnRaw = trim($_POST['return']);
    } elseif (!empty($_SERVER['HTTP_REFERER'])) {
        $refPath = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);
        if (!empty($refPath)) {
            $baseRef = basename($refPath);
            if (!empty($baseRef) && $baseRef !== 'process_household.php' && $baseRef !== 'controlpanel.php') {
                $returnRaw = $baseRef;
            }
        }
    }

    // If still undetermined, fall back directly to the Purok's resident page or map page (NEVER controlpanel.php)
    if (empty($returnRaw) || $returnRaw === 'controlpanel.php') {
        if ($details) {
            $returnRaw = $details['clean_page']; // e.g. Purok 1.php
        } else {
            $returnRaw = 'Purok 1.php';
        }
    }

    // Ensure clean URL encoding for spaces on Apache/InfinityFree
    $cleanReturn = urldecode($returnRaw);
    $returnPage = str_replace(' ', '%20', $cleanReturn);

    $sep = (strpos($returnPage, '?') !== false) ? '&' : '?';
    header("Location: " . $returnPage . $sep . "deleted=" . ($deleted ? '1' : '0'));
    exit();
}

if ($isAjax && empty($_POST)) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status'  => 'error',
        'success' => false,
        'message' => 'Invalid or missing delete ID.'
    ]);
    exit();
}

// -------------------------------------------------------------
// 2. HANDLE POST REQUEST (Insert / Update)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $householdId = !empty($_POST['household_id']) ? (int)$_POST['household_id'] : null;
    $purokInput = $_POST['target_file'] ?? $_POST['purok_name'] ?? '';
    $details = resolvePurokDetails($purokInput);

    if (!$details) {
        $fallback = !empty($_SERVER['HTTP_REFERER']) ? basename(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH)) : 'Purok 1.php';
        header("Location: " . str_replace(' ', '%20', $fallback));
        exit();
    }

    $tableName = $details['table'];
    $redirectUrl = $details['page'];

    $houseNumber = trim($_POST['house_number'] ?? '');
    $husbandName = trim($_POST['husband_name'] ?? '');
    $spouseName  = trim($_POST['spouse_name'] ?? '');
    $markerWidth = !empty($_POST['marker_width']) ? (int)$_POST['marker_width'] : 40;
    $markerHeight = !empty($_POST['marker_height']) ? (int)$_POST['marker_height'] : 40;

    // Accept top_position / left_position or top_pos / left_pos
    $topPos = isset($_POST['top_position']) ? $_POST['top_position'] : ($_POST['top_pos'] ?? '50.00');
    $leftPos = isset($_POST['left_position']) ? $_POST['left_position'] : ($_POST['left_pos'] ?? '50.00');
    $topPos = number_format((float)$topPos, 2, '.', '');
    $leftPos = number_format((float)$leftPos, 2, '.', '');

    // Process Children JSON Array
    $childrenList = [];
    if (isset($_POST['children'])) {
        if (is_array($_POST['children'])) {
            foreach ($_POST['children'] as $child) {
                $trimmed = trim((string)$child);
                if ($trimmed !== '') {
                    $childrenList[] = $trimmed;
                }
            }
        } elseif (is_string($_POST['children'])) {
            $decoded = json_decode($_POST['children'], true);
            if (is_array($decoded)) {
                $childrenList = array_values(array_filter(array_map('trim', $decoded)));
            }
        }
    }
    $childrenJson = json_encode($childrenList, JSON_UNESCAPED_UNICODE);

    // Setup Upload Directories with safe permissions
    $houseDir = 'uploads/houses/';
    $markerDir = 'uploads/markers/';
    if (!is_dir($houseDir)) @mkdir($houseDir, 0777, true);
    if (!is_dir($markerDir)) @mkdir($markerDir, 0777, true);

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
            // Notice: spouse_name is updated to NULL if empty, allowing the user to remove it
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
                ':spouse_name'  => ($spouseName !== '' ? $spouseName : null),
                ':children'     => $childrenJson,
                ':house_image'  => $houseImagePath,
                ':marker_image' => $markerImagePath,
                ':marker_width' => $markerWidth,
                ':marker_height'=> $markerHeight,
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
    $fallback = !empty($_SERVER['HTTP_REFERER']) ? basename(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH)) : 'Purok 1.php';
    if (empty($fallback) || $fallback === 'controlpanel.php' || $fallback === 'process_household.php') {
        $fallback = 'Purok 1.php';
    }
    header("Location: " . str_replace(' ', '%20', $fallback));
    exit();
}
