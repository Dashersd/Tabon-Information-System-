<?php
// diag.php - Temporary diagnostic tool to check InfinityFree DB & Session configuration
// Delete this file after checking
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Barangay Tabon System Diagnostics (InfinityFree)</h2>";

echo "<h3>1. PHP & Session Environment</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "session.save_path: " . session_save_path() . "<br>";
echo "session.save_path writable: " . (is_writable(session_save_path()) ? "<span style='color:green'>YES</span>" : "<span style='color:red'>NO</span>") . "<br>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['diag_test'] = time();
echo "Session ID: " . session_id() . "<br>";
echo "Session active: " . (isset($_SESSION['diag_test']) ? "<span style='color:green'>YES</span>" : "<span style='color:red'>NO</span>") . "<br>";

echo "<h3>2. Database Connection Check</h3>";
if (!file_exists(__DIR__ . '/db_connect.php')) {
    echo "<span style='color:red'>db_connect.php NOT found in current directory!</span><br>";
} else {
    require_once __DIR__ . '/db_connect.php';
    if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
        echo "MySQLi connected to database: <strong>" . htmlspecialchars($dbname ?? 'unknown') . "</strong><br>";
        
        // Check users table
        $res = $conn->query("SHOW TABLES LIKE 'users'");
        if ($res && $res->num_rows > 0) {
            echo "Users table: <span style='color:green'>FOUND</span><br>";
            $u_res = $conn->query("SELECT id, username, email, password FROM users");
            echo "Users in table: <strong>" . ($u_res ? $u_res->num_rows : 0) . "</strong><br>";
            if ($u_res) {
                echo "<table border='1' cellpadding='6' style='margin-top:10px; border-collapse:collapse;'>";
                echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Password Matches '123'</th><th>Password Matches 'admin123'</th></tr>";
                while ($u = $u_res->fetch_assoc()) {
                    $m123 = (password_verify('123', $u['password']) || $u['password'] === '123') ? "<strong style='color:green'>YES</strong>" : "NO";
                    $m_admin = (password_verify('admin123', $u['password']) || $u['password'] === 'admin123') ? "<strong style='color:green'>YES</strong>" : "NO";
                    echo "<tr><td>{$u['id']}</td><td>" . htmlspecialchars($u['username']) . "</td><td>" . htmlspecialchars($u['email']) . "</td><td>{$m123}</td><td>{$m_admin}</td></tr>";
                }
                echo "</table>";
            }
        } else {
            echo "Users table: <span style='color:red; font-weight:bold;'>NOT FOUND!</span> (The database tables need to be created or tabon_db.sql imported).<br>";
        }
    } else {
        echo "<span style='color:red; font-weight:bold;'>Database connection failed!</span><br>";
    }
}

echo "<h3>3. Fix Administrator Password</h3>";
if (isset($_GET['reset_admin']) && isset($conn)) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    
    // Ensure name and role columns exist
    $n_chk = $conn->query("SHOW COLUMNS FROM users LIKE 'name'");
    if ($n_chk && $n_chk->num_rows === 0) {
        $conn->query("ALTER TABLE users ADD COLUMN name VARCHAR(150) NOT NULL DEFAULT 'System Administrator' AFTER username");
    }
    $r_chk = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($r_chk && $r_chk->num_rows === 0) {
        $conn->query("ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'admin' AFTER password");
    }

    $conn->query("UPDATE users SET password = '$hash' WHERE id = 1");
    echo "<div style='color:green; font-weight:bold; padding:10px; background:#e6fffa; border:1px solid #38b2ac;'>Admin password successfully set to: <strong>admin123</strong> (and <strong>123</strong>)!</div>";
} else {
    echo "<a href='diag.php?reset_admin=1' style='display:inline-block; padding:8px 16px; background:#093d25; color:#fff; text-decoration:none; border-radius:6px; font-weight:bold;'>Click Here to Force Sync Admin Password</a>";
}

