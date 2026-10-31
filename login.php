<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db_connect.php';

// Ensure username column exists in users table
$col_chk = $conn->query("SHOW COLUMNS FROM `users` LIKE 'username'");
if ($col_chk && $col_chk->num_rows === 0) {
    $conn->query("ALTER TABLE `users` ADD COLUMN `username` VARCHAR(100) NULL AFTER `id`");
    $conn->query("UPDATE `users` SET `username` = 'admin' WHERE `id` = 1");
}

// Seed default admin in users table if table is completely empty
$chk_admin = $conn->query("SELECT id FROM `users` LIMIT 1");
if ($chk_admin && $chk_admin->num_rows === 0) {
    $default_pw_hash = password_hash('123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO `users` (`id`, `username`, `name`, `email`, `password`, `role`) 
                  VALUES (1, 'admin', 'System Administrator', 'admin@example.com', '$default_pw_hash', 'admin')");
}

// -------------------------------------------------------------
// 1. LOGOUT HANDLER
// -------------------------------------------------------------
if (isset($_GET['logout'])) {
    if (isset($_SESSION['admin_id'])) {
        $uid = (int)$_SESSION['admin_id'];
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $log_stmt = $conn->prepare("INSERT INTO `activity_logs` (`user_id`, `action`, `details`, `ip_address`) VALUES (?, 'Admin Logout', 'Administrator signed out of session', ?)");
        if ($log_stmt) {
            $log_stmt->bind_param("is", $uid, $ip);
            $log_stmt->execute();
        }
    }
    $_SESSION = [];
    if (session_id() != "" || isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 2592000, '/');
    }
    session_destroy();
    header("Location: login.php?msg=logged_out");
    exit();
}

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: Admin/controlpanel.php");
    exit();
}

$error = null;
$signup_error = null;
$success_msg = null;
$entered_login = '';

if (isset($_GET['msg']) && $_GET['msg'] === 'logged_out') {
    $success_msg = "You have been logged out successfully.";
}

// -------------------------------------------------------------
// 2. SIGN IN / LOGIN PROCESSING
// -------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && (!isset($_POST['action_type']) || $_POST['action_type'] === 'signin')) {
    $entered_login = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($entered_login) || empty($password)) {
        $error = "Please enter both username/email and password.";
    } else {
        $authenticated = false;

        // Query user by email, username, or name
        try {
            $stmt = $conn->prepare("SELECT * FROM `users` WHERE `email` = ? OR `username` = ? OR `name` = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("sss", $entered_login, $entered_login, $entered_login);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $user = $res->fetch_assoc()) {
                    if (password_verify($password, $user['password']) || $password === $user['password']) {
                        // Automatically rehash plain text password if legacy
                        if ($password === $user['password'] && !password_get_info($user['password'])['algo']) {
                            $new_hash = password_hash($password, PASSWORD_DEFAULT);
                            $up = $conn->prepare("UPDATE `users` SET `password` = ? WHERE `id` = ?");
                            $up->bind_param("si", $new_hash, $user['id']);
                            $up->execute();
                        }

                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['admin_id'] = (int)$user['id'];
                        $_SESSION['admin_name'] = $user['name'];
                        $_SESSION['admin_username'] = $user['username'] ?? 'admin';
                        $_SESSION['admin_email'] = $user['email'];
                        $_SESSION['admin_role'] = $user['role'] ?? 'admin';

                        // Log Activity
                        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                        $act = $conn->prepare("INSERT INTO `activity_logs` (`user_id`, `action`, `details`, `ip_address`) VALUES (?, 'Admin Login', 'Administrator authenticated successfully', ?)");
                        if ($act) {
                            $act->bind_param("is", $user['id'], $ip);
                            $act->execute();
                        }

                        $authenticated = true;
                    }
                }
            }
        } catch (Exception $e) {}

        if ($authenticated) {
            header("Location: Admin/controlpanel.php");
            exit();
        } else {
            $error = "Invalid username/email or password.";
        }
    }
}

// -------------------------------------------------------------
// 3. SIGN UP / REGISTRATION PROCESSING
// -------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action_type']) && $_POST['action_type'] === 'signup') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $signup_error = "Please fill in all required registration fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $signup_error = "Please provide a valid email address.";
    } elseif ($password !== $confirm_password) {
        $signup_error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $signup_error = "Password must be at least 6 characters long.";
    } else {
        // Check if email already registered
        $chk = $conn->prepare("SELECT id FROM `users` WHERE `email` = ? LIMIT 1");
        $chk->bind_param("s", $email);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $signup_error = "This email address is already registered.";
        } else {
            $username = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', explode('@', $email)[0]));
            if (empty($username)) $username = 'admin_' . time();
            
            // Check username uniqueness
            $u_chk = $conn->prepare("SELECT id FROM `users` WHERE `username` = ? LIMIT 1");
            $u_chk->bind_param("s", $username);
            $u_chk->execute();
            if ($u_chk->get_result()->num_rows > 0) {
                $username .= rand(10, 99);
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO `users` (`name`, `username`, `email`, `password`, `role`) VALUES (?, ?, ?, ?, 'admin')");
            $ins->bind_param("ssss", $name, $username, $email, $hash);

            if ($ins->execute()) {
                $new_id = $ins->insert_id;
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $new_id;
                $_SESSION['admin_name'] = $name;
                $_SESSION['admin_username'] = $username;
                $_SESSION['admin_email'] = $email;
                $_SESSION['admin_role'] = 'admin';

                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $act = $conn->prepare("INSERT INTO `activity_logs` (`user_id`, `action`, `details`, `ip_address`) VALUES (?, 'Account Registered', 'New administrator account created via portal', ?)");
                if ($act) {
                    $act->bind_param("is", $new_id, $ip);
                    $act->execute();
                }

                header("Location: Admin/controlpanel.php");
                exit();
            } else {
                $signup_error = "Registration error: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Tabon - Login Portal</title>
    <!-- Montserrat Font -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css?v=<?= time() ?>">
</head>
<body class="login-page">
    <div class="card-shell" id="cardShell">
        <div class="card-border"></div>
        <div class="card-cut-edge tl"></div>
        <div class="card-cut-edge br"></div>
        <div class="card-bg"></div>
        <div class="card-bracket top-left">
            <svg viewBox="0 0 100 100" width="100%" height="100%">
                <polygon points="20,20 70,20 20,70" fill="none" stroke="#ffffff" stroke-width="32" stroke-linejoin="round" />
                <polygon points="20,20 70,20 20,70" fill="#121214" stroke="#121214" stroke-width="27" stroke-linejoin="round" />
            </svg>
        </div>
        <div class="card-bracket bottom-right">
            <svg viewBox="0 0 100 100" width="100%" height="100%">
                <polygon points="80,80 30,80 80,30" fill="none" stroke="#000000" stroke-width="32" stroke-linejoin="round" />
                <polygon points="80,80 30,80 80,30" fill="#121214" stroke="#121214" stroke-width="27" stroke-linejoin="round" />
            </svg>
        </div>
        <div class="transition-glyph" id="transitionGlyph">
            <div class="glow"></div>
            <div class="logo">
                <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="6" y="6" width="36" height="36" rx="10" stroke="#ffffff" stroke-width="2" opacity="0.8"/>
                    <path d="M0 48 L48 0" stroke="#0a0a0a" stroke-width="6"/>
                    <path d="M0 48 L48 0" stroke="#ffffff" stroke-width="2" opacity="0.6"/>
                </svg>
            </div>
        </div>

        <div class="form-content" id="formContent">
            <!-- Content populated by JS -->
        </div>
    </div>

    <!-- Template for Sign In Form -->
    <template id="tpl-signin">
        <form id="signInForm" action="login.php" method="POST">
            <input type="hidden" name="action_type" value="signin">
            <div class="header">
                <h1><span class="text-white">Welcome </span><span class="text-accent">Back</span></h1>
                <p class="subheading">Enter your administrator credentials to access your portal</p>
            </div>

            <?php if (!empty($success_msg)): ?>
                <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #6ee7b7; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; text-align: center;">
                    <?= htmlspecialchars($success_msg) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="error-msg" style="display: block; margin-bottom: 16px;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="input-group">
                <label>Email or Username</label>
                <div class="input-wrapper">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    <input type="text" name="email" placeholder="admin or name@example.com" value="<?= htmlspecialchars($entered_login) ?>" required autofocus>
                </div>
            </div>

            <div class="input-group">
                <label>Password</label>
                <div class="input-wrapper">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    <input type="password" name="password" placeholder="Enter your password" required>
                    <button type="button" class="toggle-password" tabindex="-1">
                        <svg class="icon eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                        <svg class="icon eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
            </div>

            <div class="row-opts">
                <label class="remember-me">
                    <input type="checkbox" checked> <span>Keep me signed in</span>
                </label>
                <a href="../index.php" class="forgot-link">Back to Website</a>
            </div>

            <button type="submit" name="login" class="primary-btn">
                <span style="display:flex;align-items:center;justify-content:center;gap:8px;">Sign In 
                <svg class="arrow-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></span>
                <div class="sweep"></div>
            </button>

            <div class="footer-text">
                Need to register an admin account? <button type="button" class="link-btn toggle-view-btn" data-target="signup">Create Account</button>
            </div>
        </form>
    </template>

    <!-- Template for Sign Up Form -->
    <template id="tpl-signup">
        <form id="signUpForm" action="login.php" method="POST">
            <input type="hidden" name="action_type" value="signup">
            <div class="header">
                <h1><span class="text-white">Create </span><span class="text-accent">Account</span></h1>
                <p class="subheading">Register a new administrator account for Barangay Tabon</p>
            </div>

            <?php if (!empty($signup_error)): ?>
                <div class="error-msg" id="signupError" style="display: block; margin-bottom: 16px;">
                    <?= htmlspecialchars($signup_error) ?>
                </div>
            <?php else: ?>
                <div class="error-msg" id="signupError" style="display:none;"></div>
            <?php endif; ?>

            <div class="input-group">
                <label>Full Name</label>
                <div class="input-wrapper">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    <input type="text" name="name" placeholder="Full name (e.g. John Dela Cruz)" required>
                </div>
            </div>

            <div class="input-group">
                <label>Email Address</label>
                <div class="input-wrapper">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    <input type="email" name="email" placeholder="name@example.com" required>
                </div>
            </div>

            <div class="row-fields">
                <div class="input-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        <input type="password" name="password" placeholder="Min. 6 characters" minlength="6" required>
                        <button type="button" class="toggle-password" tabindex="-1">
                            <svg class="icon eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            <svg class="icon eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                </div>
                <div class="input-group">
                    <label>Confirm Password</label>
                    <div class="input-wrapper">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        <input type="password" name="confirm_password" placeholder="Repeat password" minlength="6" required>
                        <button type="button" class="toggle-password" tabindex="-1">
                            <svg class="icon eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            <svg class="icon eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                </div>
            </div>

            <button type="submit" class="primary-btn">
                <span style="display:flex;align-items:center;justify-content:center;gap:8px;">Create Administrator Account 
                <svg class="arrow-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></span>
                <div class="sweep"></div>
            </button>

            <div class="footer-text">
                Already have an administrator account? <button type="button" class="link-btn toggle-view-btn" data-target="signin">Sign In</button>
            </div>
        </form>
    </template>

    <script>
        window.INITIAL_VIEW = '<?= !empty($signup_error) ? "signup" : "signin" ?>';
    </script>
    <script src="js/login.js?v=<?= time() ?>"></script>
</body>
</html>
