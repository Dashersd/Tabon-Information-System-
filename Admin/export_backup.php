<?php
// Admin/export_backup.php - Instant SQL Database Backup Downloader
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../db_connect.php';

$db_name = 'tabon_db';
$filename = 'tabon_db_backup_' . date('Y-m-d_His') . '.sql';

// Check if mysqldump is available via exec
$dump_output = '';
$mysqldump_paths = [
    'mysqldump',
    'C:/xampp/mysql/bin/mysqldump.exe',
    'C:\\xampp\\mysql\\bin\\mysqldump.exe',
    '/usr/bin/mysqldump',
    '/usr/local/bin/mysqldump'
];

$used_mysqldump = false;
if (function_exists('exec')) {
    foreach ($mysqldump_paths as $bin) {
        $test_cmd = "\"$bin\" --version 2>&1";
        @exec($test_cmd, $out, $code);
        if ($code === 0) {
            $cmd = "\"$bin\" -u root tabon_db";
            ob_start();
            passthru($cmd, $pcode);
            $content = ob_get_clean();
            if ($pcode === 0 && !empty($content)) {
                $dump_output = $content;
                $used_mysqldump = true;
                break;
            }
        }
    }
}

// Fallback: Pure PHP MySQLi Database Exporter (Works in all environments including shared hosting)
if (!$used_mysqldump || empty($dump_output)) {
    $out = "-- ========================================================\n";
    $out .= "-- Tabon Information System - Database Backup\n";
    $out .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $out .= "-- Server OS: " . php_uname() . "\n";
    $out .= "-- PHP Version: " . phpversion() . "\n";
    $out .= "-- ========================================================\n\n";
    $out .= "SET FOREIGN_KEY_CHECKS=0;\n";
    $out .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $out .= "START TRANSACTION;\n";
    $out .= "SET time_zone = \"+00:00\";\n\n";

    $tables_res = $conn->query("SHOW TABLES");
    if ($tables_res) {
        while ($t_row = $tables_res->fetch_array()) {
            $tbl = $t_row[0];
            $out .= "-- --------------------------------------------------------\n";
            $out .= "-- Structure for table `$tbl`\n";
            $out .= "-- --------------------------------------------------------\n";
            $out .= "DROP TABLE IF EXISTS `$tbl`;\n";

            $create_res = $conn->query("SHOW CREATE TABLE `$tbl`");
            if ($create_res && $c_row = $create_res->fetch_array()) {
                $out .= $c_row[1] . ";\n\n";
            }

            // Dump data
            $rows_res = $conn->query("SELECT * FROM `$tbl`");
            if ($rows_res && $rows_res->num_rows > 0) {
                $out .= "-- Dumping data for table `$tbl`\n";
                $fields_cnt = $rows_res->field_count;
                while ($row = $rows_res->fetch_row()) {
                    $out .= "INSERT INTO `$tbl` VALUES(";
                    for ($i = 0; $i < $fields_cnt; $i++) {
                        if (is_null($row[$i])) {
                            $out .= "NULL";
                        } elseif (is_numeric($row[$i])) {
                            $out .= $row[$i];
                        } else {
                            $out .= "'" . $conn->real_escape_string($row[$i]) . "'";
                        }
                        if ($i < ($fields_cnt - 1)) $out .= ", ";
                    }
                    $out .= ");\n";
                }
                $out .= "\n";
            }
        }
    }

    $out .= "SET FOREIGN_KEY_CHECKS=1;\n";
    $out .= "COMMIT;\n";
    $dump_output = $out;
}

// Log backup export
try {
    $user_id = $_SESSION['admin_id'] ?? 1;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $action = "Database Backup Exported";
    $details = "Full SQL backup file generated: $filename";
    $stmt = $conn->prepare("INSERT INTO `activity_logs` (`user_id`, `action`, `details`, `ip_address`) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isss", $user_id, $action, $details, $ip);
        $stmt->execute();
    }
} catch (Exception $e) {}

// Deliver attachment download headers
header('Content-Description: File Transfer');
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . strlen($dump_output));

echo $dump_output;
exit();
