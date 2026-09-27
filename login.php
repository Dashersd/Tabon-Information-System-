<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if ($email === 'admin' && $password === '123') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: Admin/admindashboard.php");
        exit();
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Tabon - Login</title>
    <!-- Montserrat Font -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css?v=<?php echo time(); ?>">
</head>
<body class="login-page">
    <div class="bg-lines">
        <div class="line"></div>
        <div class="line"></div>
        <div class="line"></div>
        <div class="line"></div>
        <div class="line gold"></div>
        <div class="line gold"></div>
    </div>

    <div class="card-shell" id="cardShell">
        <div class="card-border"></div>
        <div class="card-cut-edge tl"></div>
        <div class="card-cut-edge br"></div>
        <div class="card-bg"></div>
        <div class="card-bracket top-left">
            <svg viewBox="0 0 100 100" width="100%" height="100%">
                <polygon points="20,20 70,20 20,70" fill="none" stroke="#F4C430" stroke-width="32" stroke-linejoin="round" />
                <polygon points="20,20 70,20 20,70" fill="#151516" stroke="#151516" stroke-width="27" stroke-linejoin="round" />
            </svg>
        </div>
        <div class="card-bracket bottom-right">
            <svg viewBox="0 0 100 100" width="100%" height="100%">
                <polygon points="80,80 30,80 80,30" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="32" stroke-linejoin="round" />
                <polygon points="80,80 30,80 80,30" fill="#151516" stroke="#151516" stroke-width="27" stroke-linejoin="round" />
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
            <!-- Content will be populated by JS initially -->
        </div>
    </div>

    <!-- Templates for the forms -->
    <template id="tpl-signin">
        <form id="signInForm" action="login.php" method="POST">
            <div class="header">
                <h1><span class="text-white">Welcome </span><span class="text-accent">Back</span></h1>
                <p class="subheading">Enter your credentials to access your secure account</p>
            </div>

            <?php if(isset($error)): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="input-group">
                <label>Email Address</label>
                <div class="input-wrapper">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    <input type="email" name="email" placeholder="name@example.com" required>
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
                    <input type="checkbox"> <span>Remember me</span>
                </label>
                <a href="#" class="forgot-link">Forgot password?</a>
            </div>

            <button type="submit" name="login" class="primary-btn">
                <span style="display:flex;align-items:center;justify-content:center;gap:8px;">Sign In 
                <svg class="arrow-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></span>
                <div class="sweep"></div>
            </button>

            <div class="divider">
                <span>or continue with</span>
            </div>

            <div class="social-row">
                <button type="button" class="social-btn">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.35 11.1h-9.17v2.73h6.51c-.33 3.81-3.5 5.44-6.5 5.44-3.79 0-7.06-2.75-7.06-7.27s3.27-7.27 7.06-7.27c1.78 0 3.39.63 4.59 1.63l2.09-2.09C17.15 2.65 14.85 1.5 12.18 1.5 6.3 1.5 1.5 6.3 1.5 12.18s4.8 10.68 10.68 10.68c5.8 0 10.02-4.04 10.02-9.98 0-.61-.07-1.2-.15-1.78z"/></svg>
                    Google
                </button>
                <button type="button" class="social-btn">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.05 13.57c-.02-2.12 1.74-3.15 1.82-3.2-1-1.46-2.56-1.65-3.12-1.68-1.33-.13-2.6.78-3.27.78-.68 0-1.71-.75-2.81-.73-1.43.02-2.75.83-3.48 2.11-1.5 2.6-.39 6.45 1.08 8.57.72 1.04 1.56 2.19 2.69 2.15 1.09-.04 1.52-.71 2.84-.71 1.3 0 1.7.71 2.84.69 1.16-.02 1.9-.1 2.58-1.12.82-1.2 1.16-2.37 1.17-2.43-.02-.01-2.27-.87-2.31-3.43zm-2.03-6.6c.59-.72 1-1.72.89-2.72-.86.04-1.92.58-2.52 1.29-.54.62-1.02 1.65-.89 2.63.97.08 1.93-.49 2.52-1.2z"/></svg>
                    Apple
                </button>
            </div>

            <div class="footer-text">
                Don't have an account? <button type="button" class="link-btn toggle-view-btn" data-target="signup">Sign Up</button>
            </div>
        </form>
    </template>

    <template id="tpl-signup">
        <form id="signUpForm" action="#" method="POST">
            <div class="header">
                <h1><span class="text-white">Create </span><span class="text-accent">Account</span></h1>
                <p class="subheading">Join us today to access your secure workspace.</p>
            </div>

            <div class="input-group">
                <label>Full Name</label>
                <div class="input-wrapper">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    <input type="text" name="name" placeholder="Full name (e.g. John Anderson)" required>
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
                        <input type="password" name="password" placeholder="Enter your password" required>
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
                        <input type="password" name="confirm_password" placeholder="Confirm your password" required>
                        <button type="button" class="toggle-password" tabindex="-1">
                            <svg class="icon eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            <svg class="icon eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="error-msg" id="signupError" style="display:none; color: #f472b6;"></div>

            <div class="row-opts">
                <label class="remember-me">
                    <input type="checkbox" required> <span>I agree to the Terms & Privacy Policy</span>
                </label>
            </div>

            <button type="submit" class="primary-btn">
                <span style="display:flex;align-items:center;justify-content:center;gap:8px;">Create Account 
                <svg class="arrow-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></span>
                <div class="sweep"></div>
            </button>

            <div class="divider">
                <span>OR CONTINUE WITH</span>
            </div>

            <div class="social-row">
                <button type="button" class="social-btn">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.35 11.1h-9.17v2.73h6.51c-.33 3.81-3.5 5.44-6.5 5.44-3.79 0-7.06-2.75-7.06-7.27s3.27-7.27 7.06-7.27c1.78 0 3.39.63 4.59 1.63l2.09-2.09C17.15 2.65 14.85 1.5 12.18 1.5 6.3 1.5 1.5 6.3 1.5 12.18s4.8 10.68 10.68 10.68c5.8 0 10.02-4.04 10.02-9.98 0-.61-.07-1.2-.15-1.78z"/></svg>
                    Google
                </button>
                <button type="button" class="social-btn">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.05 13.57c-.02-2.12 1.74-3.15 1.82-3.2-1-1.46-2.56-1.65-3.12-1.68-1.33-.13-2.6.78-3.27.78-.68 0-1.71-.75-2.81-.73-1.43.02-2.75.83-3.48 2.11-1.5 2.6-.39 6.45 1.08 8.57.72 1.04 1.56 2.19 2.69 2.15 1.09-.04 1.52-.71 2.84-.71 1.3 0 1.7.71 2.84.69 1.16-.02 1.9-.1 2.58-1.12.82-1.2 1.16-2.37 1.17-2.43-.02-.01-2.27-.87-2.31-3.43zm-2.03-6.6c.59-.72 1-1.72.89-2.72-.86.04-1.92.58-2.52 1.29-.54.62-1.02 1.65-.89 2.63.97.08 1.93-.49 2.52-1.2z"/></svg>
                    Apple
                </button>
            </div>

            <div class="footer-text">
                Already have an account? <button type="button" class="link-btn toggle-view-btn" data-target="signin">Sign In</button>
            </div>
        </form>
    </template>

    <script src="js/login.js"></script>
</body>
</html>
