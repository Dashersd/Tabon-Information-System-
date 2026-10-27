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
    die("Database connection failed: " . $e->getMessage());
}

// MySQLi connection for scripts utilizing mysqli statements
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("MySQLi connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>
