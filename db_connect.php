<?php
// db_connect.php - Universal Database Connection for Localhost (XAMPP) & Production (InfinityFree)

// 1. Detect Environment (Localhost vs InfinityFree / Live Server)
$http_host = $_SERVER['HTTP_HOST'] ?? '';
$is_cli    = (php_sapi_name() === 'cli');
$is_local  = $is_cli
             || in_array($http_host, ['localhost', '127.0.0.1', '::1']) 
             || strpos($http_host, 'localhost:') === 0 
             || strpos($http_host, '127.0.0.1:') === 0;

$is_api_request = (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) 
                  || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

if ($is_local) {
    // --- LOCAL DEVELOPMENT SETTINGS (XAMPP) ---
    $host     = 'localhost';
    $dbname   = 'tabon_db';
    $username = 'root';
    $password = '';
    $port     = 3306;
} else {
    // --- INFINITYFREE HOSTING CREDENTIALS ---
    $host     = getenv('DB_HOST') ?: 'sql208.infinityfree.com';
    $username = getenv('DB_USER') ?: 'if0_43319778';
    $password = getenv('DB_PASS') ?: 'yBxuFOPGrAq0FR';
    $port     = 3306;

    // Default database name (customizable via vPanel MySQL Databases)
    $dbname   = getenv('DB_NAME') ?: 'if0_43319778_tabon_db';

    // Auto-detect the exact database created under your account for Tabon
    try {
        $temp_conn = @new mysqli($host, $username, $password, '', $port);
        if ($temp_conn && !$temp_conn->connect_error) {
            $db_list = $temp_conn->query("SHOW DATABASES LIKE 'if0_43319778_%'");
            if ($db_list && $db_list->num_rows > 0) {
                $user_dbs = [];
                while ($row = $db_list->fetch_array()) {
                    $user_dbs[] = $row[0];
                }

                $selectedDb = null;
                // Prioritize explicit tabon databases
                foreach ($user_dbs as $udb) {
                    if ($udb === 'if0_43319778_tabon_db' || stripos($udb, 'tabon') !== false) {
                        $selectedDb = $udb;
                        break;
                    }
                }
                // Verify which database actually holds the purok1_locations table
                if (!$selectedDb) {
                    foreach ($user_dbs as $udb) {
                        $chkTable = @$temp_conn->query("SHOW TABLES FROM `$udb` LIKE 'purok1_locations'");
                        if ($chkTable && $chkTable->num_rows > 0) {
                            $selectedDb = $udb;
                            break;
                        }
                    }
                }
                if ($selectedDb) {
                    $dbname = $selectedDb;
                } elseif (!empty($user_dbs)) {
                    $dbname = $user_dbs[0];
                }
            }
            $temp_conn->close();
        }
    } catch (Exception $e) {}
}

// 2. Establish PDO Connection
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // If local database does not exist, attempt auto-creation
    if ($is_local && $e->getCode() == 1049) {
        try {
            $tempPdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $username, $password);
            $tempPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
            $sqlFile = __DIR__ . '/tabon_db.sql';
            if (file_exists($sqlFile)) {
                $sqlContent = file_get_contents($sqlFile);
                if (!empty($sqlContent)) {
                    $pdo->exec($sqlContent);
                }
            }
        } catch (Exception $initEx) {
            die("Database connection failed: " . $e->getMessage());
        }
    } else {
        if ($is_api_request) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status'  => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ]);
            exit();
        }

        // Friendly notice for hosting setup
        die("
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Database Setup - Barangay Tabon</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8fafc; padding: 40px 16px; margin: 0; color: #1e293b; }
                .db-card { max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
                .db-title { color: #dc2626; font-size: 20px; font-weight: 700; margin-top: 0; display: flex; align-items: center; gap: 10px; }
                .db-info { background: #f1f5f9; border-radius: 8px; padding: 16px; font-size: 14px; margin: 18px 0; line-height: 1.6; }
                .db-code { font-family: monospace; background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-weight: 600; color: #0f172a; }
                .db-steps { font-size: 14px; line-height: 1.6; color: #475569; }
                .db-steps ol { padding-left: 20px; }
            </style>
        </head>
        <body>
            <div class='db-card'>
                <h2 class='db-title'>⚠️ Database Connection Required</h2>
                <p>The application could not connect to your MySQL database with the credentials in <span class='db-code'>db_connect.php</span>.</p>
                
                <div class='db-info'>
                    <strong>Attempted Host:</strong> " . htmlspecialchars($host) . "<br>
                    <strong>Database:</strong> " . htmlspecialchars($dbname) . "<br>
                    <strong>Username:</strong> " . htmlspecialchars($username) . "<br>
                    <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "
                </div>

                <div class='db-steps'>
                    <strong>Quick Fix:</strong>
                    <ol>
                        <li>In your InfinityFree vPanel under <strong>MySQL Databases</strong>, ensure you created a database (e.g. <code>if0_43319778_tabon_db</code>).</li>
                        <li>Open <strong>phpMyAdmin</strong> from vPanel and import <code>tabon_db.sql</code>.</li>
                    </ol>
                </div>
            </div>
        </body>
        </html>
        ");
    }
}

// 3. Establish MySQLi Connection
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($host, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    if ($is_api_request) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'  => 'error',
            'message' => 'MySQLi connection failed: ' . $conn->connect_error
        ]);
        exit();
    }
    die("MySQLi connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// 4. Global Branding & Slogan Configuration (for public header, navbar, & footer)
$global_barangay_info = [];
try {
    if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
        $gb_res = $conn->query("SELECT `barangay_name`, `municipality`, `province`, `slogan`, `hero_title`, `logo_path`, `municipality_logo_path` FROM `barangay_information` WHERE `id` = 1 LIMIT 1");
        if ($gb_res && $gb_row = $gb_res->fetch_assoc()) {
            $global_barangay_info = $gb_row;
        }
    }
} catch (\Throwable $e) {}

$site_slogan = !empty($global_barangay_info['slogan']) ? $global_barangay_info['slogan'] : 'Serbisyong Tapat, Para sa Lahat';
$site_barangay_name = !empty($global_barangay_info['barangay_name']) ? $global_barangay_info['barangay_name'] : 'Barangay Tabon';

// 5. Automatic Primary Key & Zero-ID Repair for Purok Location Tables
function ensurePurokTableIntegrity($pdo, $tableName) {
    if (!$pdo || !($pdo instanceof PDO)) return;
    try {
        // Quick check: Are there any rows with id = 0 or NULL?
        $chk = $pdo->query("SELECT COUNT(*) FROM `$tableName` WHERE `id` = 0 OR `id` IS NULL");
        $zeroCount = $chk ? (int)$chk->fetchColumn() : 0;

        // Also check if PRIMARY KEY exists
        $hasPk = false;
        try {
            $keys = $pdo->query("SHOW KEYS FROM `$tableName` WHERE Key_name = 'PRIMARY'");
            if ($keys && $keys->fetch()) {
                $hasPk = true;
            }
        } catch (\Throwable $ke) {}

        if ($zeroCount > 0 || !$hasPk) {
            // Fetch all current rows to preserve all data
            $rows = $pdo->query("SELECT * FROM `$tableName`")->fetchAll(PDO::FETCH_ASSOC);

            // Create temporary table with strict AUTO_INCREMENT PRIMARY KEY
            $tempTable = "{$tableName}_fix_" . time();
            $pdo->exec("DROP TABLE IF EXISTS `$tempTable`");
            $pdo->exec("CREATE TABLE `$tempTable` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `house_number` varchar(50) NOT NULL,
                `husband_name` varchar(150) NOT NULL,
                `spouse_name` varchar(150) DEFAULT NULL,
                `children` text DEFAULT NULL,
                `house_image` varchar(255) NOT NULL,
                `marker_image` varchar(255) NOT NULL,
                `marker_width` int(11) DEFAULT 40,
                `marker_height` int(11) DEFAULT 40,
                `coordinate_x` decimal(5,2) NOT NULL,
                `coordinate_y` decimal(5,2) NOT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

            if (!empty($rows)) {
                $ins = $pdo->prepare("INSERT INTO `$tempTable` (house_number, husband_name, spouse_name, children, house_image, marker_image, marker_width, marker_height, coordinate_x, coordinate_y) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($rows as $r) {
                    $ins->execute([
                        $r['house_number'] ?? 'N/A',
                        $r['husband_name'] ?? 'N/A',
                        $r['spouse_name'] ?? null,
                        $r['children'] ?? '[]',
                        $r['house_image'] ?? '',
                        $r['marker_image'] ?? '',
                        $r['marker_width'] ?? 40,
                        $r['marker_height'] ?? 40,
                        $r['coordinate_x'] ?? 50.00,
                        $r['coordinate_y'] ?? 50.00
                    ]);
                }
            }

            // Safely swap tables
            $pdo->exec("DROP TABLE `$tableName`");
            $pdo->exec("RENAME TABLE `$tempTable` TO `$tableName`");

            // Log fix
            $debugLog = __DIR__ . '/Admin/infinity_debug.log';
            @file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] AUTO-REPAIRED $tableName: Fixed primary key & re-assigned " . count($rows) . " rows to unique AUTO_INCREMENT IDs.\n", FILE_APPEND);
        }
    } catch (\Throwable $ex) {
        $debugLog = __DIR__ . '/Admin/infinity_debug.log';
        @file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] AUTO-REPAIR ERROR on $tableName: " . $ex->getMessage() . "\n", FILE_APPEND);
    }
}

// 6. Automatic Primary Key & Zero-ID Repair for Gallery Tables
function ensureGalleryTableIntegrity($pdo) {
    if (!$pdo || !($pdo instanceof PDO)) return;
    try {
        // 1. Check gallery_folders
        $chkF = $pdo->query("SELECT COUNT(*) FROM `gallery_folders` WHERE `id` = 0 OR `id` IS NULL");
        $zeroCountF = $chkF ? (int)$chkF->fetchColumn() : 0;

        $hasPkF = false;
        try {
            $kf = $pdo->query("SHOW KEYS FROM `gallery_folders` WHERE Key_name = 'PRIMARY'");
            if ($kf && $kf->fetch()) $hasPkF = true;
        } catch (\Throwable $e) {}

        if ($zeroCountF > 0 || !$hasPkF) {
            $folders = $pdo->query("SELECT * FROM `gallery_folders`")->fetchAll(PDO::FETCH_ASSOC);
            $tempF = "gallery_folders_fix_" . time();
            $pdo->exec("DROP TABLE IF EXISTS `$tempF`");
            $pdo->exec("CREATE TABLE `$tempF` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(150) NOT NULL,
                `description` text DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            if (!empty($folders)) {
                $insF = $pdo->prepare("INSERT INTO `$tempF` (name, description, created_at) VALUES (?, ?, ?)");
                foreach ($folders as $f) {
                    $insF->execute([
                        $f['name'] ?? 'General',
                        $f['description'] ?? null,
                        $f['created_at'] ?? date('Y-m-d H:i:s')
                    ]);
                }
            }

            $pdo->exec("DROP TABLE `gallery_folders`");
            $pdo->exec("RENAME TABLE `$tempF` TO `gallery_folders`");

            $debugLog = __DIR__ . '/Admin/infinity_debug.log';
            @file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] AUTO-REPAIRED gallery_folders: Fixed primary key & re-assigned " . count($folders) . " rows to unique AUTO_INCREMENT IDs.\n", FILE_APPEND);
        }

        // 2. Check gallery_media
        $chkM = $pdo->query("SELECT COUNT(*) FROM `gallery_media` WHERE `id` = 0 OR `id` IS NULL");
        $zeroCountM = $chkM ? (int)$chkM->fetchColumn() : 0;

        $hasPkM = false;
        try {
            $km = $pdo->query("SHOW KEYS FROM `gallery_media` WHERE Key_name = 'PRIMARY'");
            if ($km && $km->fetch()) $hasPkM = true;
        } catch (\Throwable $e) {}

        if ($zeroCountM > 0 || !$hasPkM) {
            $mediaRows = $pdo->query("SELECT * FROM `gallery_media`")->fetchAll(PDO::FETCH_ASSOC);
            $tempM = "gallery_media_fix_" . time();
            $pdo->exec("DROP TABLE IF EXISTS `$tempM`");
            $pdo->exec("CREATE TABLE `$tempM` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `folder_id` int(11) NOT NULL,
                `file_path` varchar(255) NOT NULL,
                `file_name` varchar(255) NOT NULL,
                `caption` varchar(255) DEFAULT NULL,
                `file_size` int(11) DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `folder_id` (`folder_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            if (!empty($mediaRows)) {
                $fallbackFolderId = 1;
                $chkFirstFolder = $pdo->query("SELECT id FROM `gallery_folders` ORDER BY id ASC LIMIT 1")->fetchColumn();
                if ($chkFirstFolder) $fallbackFolderId = (int)$chkFirstFolder;

                $insM = $pdo->prepare("INSERT INTO `$tempM` (folder_id, file_path, file_name, caption, file_size, created_at) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($mediaRows as $m) {
                    $mFolderId = (int)($m['folder_id'] ?? 0);
                    if ($mFolderId <= 0) $mFolderId = $fallbackFolderId;

                    $insM->execute([
                        $mFolderId,
                        $m['file_path'] ?? '',
                        $m['file_name'] ?? 'image.jpg',
                        $m['caption'] ?? '',
                        $m['file_size'] ?? 0,
                        $m['created_at'] ?? date('Y-m-d H:i:s')
                    ]);
                }
            }

            $pdo->exec("DROP TABLE `gallery_media`");
            $pdo->exec("RENAME TABLE `$tempM` TO `gallery_media`");

            $debugLog = __DIR__ . '/Admin/infinity_debug.log';
            @file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] AUTO-REPAIRED gallery_media: Fixed primary key & re-assigned " . count($mediaRows) . " rows to unique AUTO_INCREMENT IDs.\n", FILE_APPEND);
        }
    } catch (\Throwable $ex) {
        $debugLog = __DIR__ . '/Admin/infinity_debug.log';
        @file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] GALLERY REPAIR ERROR: " . $ex->getMessage() . "\n", FILE_APPEND);
    }
}

// 7. Automatic Primary Key & Zero-ID Repair for Legend Tables
function ensureLegendTableIntegrity($pdo) {
    if (!$pdo || !($pdo instanceof PDO)) return;
    try {
        // 1. Check sm_legends
        $chkL = $pdo->query("SHOW TABLES LIKE 'sm_legends'");
        if ($chkL && $chkL->fetch()) {
            $zeroChk = $pdo->query("SELECT COUNT(*) FROM `sm_legends` WHERE `id` = 0 OR `id` IS NULL");
            $zeroCountL = $zeroChk ? (int)$zeroChk->fetchColumn() : 0;

            $hasPkL = false;
            try {
                $kl = $pdo->query("SHOW KEYS FROM `sm_legends` WHERE Key_name = 'PRIMARY'");
                if ($kl && $kl->fetch()) $hasPkL = true;
            } catch (\Throwable $e) {}

            if ($zeroCountL > 0 || !$hasPkL) {
                $legends = $pdo->query("SELECT * FROM `sm_legends`")->fetchAll(PDO::FETCH_ASSOC);
                $tempL = "sm_legends_fix_" . time();
                $pdo->exec("DROP TABLE IF EXISTS `$tempL`");
                $pdo->exec("CREATE TABLE `$tempL` (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                if (!empty($legends)) {
                    $insL = $pdo->prepare("INSERT INTO `$tempL` (name, icon_path, color_hex, is_active, display_order, top_position, left_position, marker_width, marker_height, image_path, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    foreach ($legends as $l) {
                        $insL->execute([
                            $l['name'] ?? 'Landmark',
                            $l['icon_path'] ?? 'bi-building-fill',
                            $l['color_hex'] ?? '#6366f1',
                            isset($l['is_active']) ? (int)$l['is_active'] : 1,
                            isset($l['display_order']) ? (int)$l['display_order'] : 0,
                            isset($l['top_position']) ? (float)$l['top_position'] : 50.00,
                            isset($l['left_position']) ? (float)$l['left_position'] : 50.00,
                            isset($l['marker_width']) ? (int)$l['marker_width'] : 40,
                            isset($l['marker_height']) ? (int)$l['marker_height'] : 40,
                            $l['image_path'] ?? null,
                            $l['created_at'] ?? date('Y-m-d H:i:s')
                        ]);
                    }
                }

                $pdo->exec("DROP TABLE `sm_legends`");
                $pdo->exec("RENAME TABLE `$tempL` TO `sm_legends`");

                $debugLog = __DIR__ . '/Admin/infinity_debug.log';
                @file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] AUTO-REPAIRED sm_legends: Fixed primary key & re-assigned " . count($legends) . " rows to unique AUTO_INCREMENT IDs.\n", FILE_APPEND);
            }
        }

        // 2. Check sm_legend_spots
        $chkS = $pdo->query("SHOW TABLES LIKE 'sm_legend_spots'");
        if ($chkS && $chkS->fetch()) {
            $zeroChkS = $pdo->query("SELECT COUNT(*) FROM `sm_legend_spots` WHERE `id` = 0 OR `id` IS NULL");
            $zeroCountS = $zeroChkS ? (int)$zeroChkS->fetchColumn() : 0;

            $hasPkS = false;
            try {
                $ks = $pdo->query("SHOW KEYS FROM `sm_legend_spots` WHERE Key_name = 'PRIMARY'");
                if ($ks && $ks->fetch()) $hasPkS = true;
            } catch (\Throwable $e) {}

            if ($zeroCountS > 0 || !$hasPkS) {
                $spots = $pdo->query("SELECT * FROM `sm_legend_spots`")->fetchAll(PDO::FETCH_ASSOC);
                $tempS = "sm_legend_spots_fix_" . time();
                $pdo->exec("DROP TABLE IF EXISTS `$tempS`");
                $pdo->exec("CREATE TABLE `$tempS` (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                if (!empty($spots)) {
                    $insS = $pdo->prepare("INSERT INTO `$tempS` (legend_id, title, top_position, left_position, marker_width, marker_height, marker_image, house_image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    foreach ($spots as $s) {
                        $insS->execute([
                            (int)($s['legend_id'] ?? 1),
                            $s['title'] ?? 'Spot',
                            isset($s['top_position']) ? (float)$s['top_position'] : 50.00,
                            isset($s['left_position']) ? (float)$s['left_position'] : 50.00,
                            isset($s['marker_width']) ? (int)$s['marker_width'] : 40,
                            isset($s['marker_height']) ? (int)$s['marker_height'] : 40,
                            $s['marker_image'] ?? null,
                            $s['house_image'] ?? null,
                            $s['created_at'] ?? date('Y-m-d H:i:s')
                        ]);
                    }
                }

                $pdo->exec("DROP TABLE `sm_legend_spots`");
                $pdo->exec("RENAME TABLE `$tempS` TO `sm_legend_spots`");

                $debugLog = __DIR__ . '/Admin/infinity_debug.log';
                @file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] AUTO-REPAIRED sm_legend_spots: Fixed primary key & re-assigned " . count($spots) . " rows to unique AUTO_INCREMENT IDs.\n", FILE_APPEND);
            }
        }
    } catch (\Throwable $ex) {
        $debugLog = __DIR__ . '/Admin/infinity_debug.log';
        @file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] LEGEND REPAIR ERROR: " . $ex->getMessage() . "\n", FILE_APPEND);
    }
}

// Auto-run integrity check across purok, gallery, and legend tables
if (isset($pdo) && $pdo instanceof PDO) {
    ensurePurokTableIntegrity($pdo, 'purok1_locations');
    ensurePurokTableIntegrity($pdo, 'purok2_locations');
    ensurePurokTableIntegrity($pdo, 'purok3_locations');
    ensureGalleryTableIntegrity($pdo);
    ensureLegendTableIntegrity($pdo);

    // One-time cleanup of all static / sample legend items
    $cleanFlag = __DIR__ . '/Admin/clean_legends_once.flag';
    if (!file_exists($cleanFlag)) {
        try {
            $pdo->exec("DELETE FROM `sm_legends`");
            $pdo->exec("DELETE FROM `sm_legend_spots`");
            $pdo->exec("CREATE TABLE IF NOT EXISTS `sm_settings` (`setting_key` varchar(50) NOT NULL PRIMARY KEY, `setting_value` varchar(255) DEFAULT NULL)");
            $pdo->exec("INSERT INTO `sm_settings` (`setting_key`, `setting_value`) VALUES ('legends_seeded', '1') ON DUPLICATE KEY UPDATE `setting_value` = '1'");
            @file_put_contents(__DIR__ . '/Admin/data/legend.json', "[]");
            @file_put_contents($cleanFlag, "CLEANED_AT=" . date('Y-m-d H:i:s'));
            $debugLog = __DIR__ . '/Admin/infinity_debug.log';
            @file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] CLEANED ALL STATIC LEGENDS FROM DATABASE & MAP.\n", FILE_APPEND);
        } catch (\Throwable $ce) {}
    }
}
?>
