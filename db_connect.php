<?php
$host = 'localhost';
$dbname = 'tabon_db'; // Ensure this matches your created MySQL database
$username = 'root'; // Change if using a different MySQL user
$password = ''; // Change if your MySQL user has a password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // If database does not exist (Error code 1049), attempt auto-creation & initialization
    if ($e->getCode() == 1049) {
        try {
            $tempPdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
            $tempPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // Connect to newly created database
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // If SQL schema file exists, auto-import tables
            $sqlFile = __DIR__ . '/tabon_db.sql';
            if (file_exists($sqlFile)) {
                $sqlContent = file_get_contents($sqlFile);
                if (!empty($sqlContent)) {
                    $pdo->exec($sqlContent);
                }
            }
        } catch (Exception $initEx) {
            die("Database connection failed: " . $e->getMessage() . " (Auto-setup attempt failed: " . $initEx->getMessage() . ")");
        }
    } else {
        die("Database connection failed: " . $e->getMessage());
    }
}

// MySQLi connection for scripts utilizing mysqli statements
$conn = @new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("MySQLi connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>
