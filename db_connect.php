<?php
// db_connect.php - Universal Database Connection for Localhost (XAMPP) & Production (InfinityFree)

// 1. Detect Environment (Localhost vs InfinityFree / Live Server)
$http_host = $_SERVER['HTTP_HOST'] ?? '';
$is_local  = in_array($http_host, ['localhost', '127.0.0.1', '::1']) 
             || strpos($http_host, 'localhost:') === 0 
             || strpos($http_host, '127.0.0.1:') === 0;

if ($is_local) {
    // --- LOCAL DEVELOPMENT SETTINGS (XAMPP) ---
    $host     = 'localhost';
    $dbname   = 'tabon_db';
    $username = 'root';
    $password = '';
} else {
    // --- INFINITYFREE HOSTING CREDENTIALS ---
    // When hosting on InfinityFree, fill in the 4 values from your vPanel -> "MySQL Databases":
    $host     = getenv('DB_HOST') ?: 'sqlXXX.infinityfree.com'; // e.g. sql100.infinityfree.com or sql200.epizy.com
    $dbname   = getenv('DB_NAME') ?: 'if0_XXXXXXXX_tabon_db';   // e.g. if0_38000000_tabon_db
    $username = getenv('DB_USER') ?: 'if0_XXXXXXXX';            // e.g. if0_38000000
    $password = getenv('DB_PASS') ?: 'YOUR_VPANEL_PASSWORD';    // Your InfinityFree vPanel password
}

// 2. Establish PDO Connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // If local database does not exist, attempt auto-creation
    if ($is_local && $e->getCode() == 1049) {
        try {
            $tempPdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
            $tempPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
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
                    <strong>How to configure for InfinityFree:</strong>
                    <ol>
                        <li>Open your InfinityFree Control Panel (vPanel) and go to <strong>MySQL Databases</strong>.</li>
                        <li>Create a database (e.g. <code>if0_XXXXXXXX_tabon_db</code>).</li>
                        <li>Open <strong>phpMyAdmin</strong> from your vPanel and import <code>tabon_db.sql</code>.</li>
                        <li>Edit <code>db_connect.php</code> with your host (e.g. <code>sqlXXX.infinityfree.com</code>), database name, user, and vPanel password.</li>
                    </ol>
                </div>
            </div>
        </body>
        </html>
        ");
    }
}

// 3. Establish MySQLi Connection
$conn = @new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("MySQLi connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>
