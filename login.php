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
    
    <!-- FontAwesome CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- External Stylesheet -->
    <link rel="stylesheet" href="css/login.css?v=<?php echo time(); ?>">
</head>
<body class="login-page">

    <a href="index.php" class="back-to-home">
        <i class="fa-solid fa-arrow-left"></i> Back to Home
    </a>

    <div class="container" id="container">
        
        <!-- Sign Up Form (Right Side originally, slides in) -->
        <div class="form-container sign-up-container">
            <form action="#">
                <h1>Create Account</h1>
                <div class="social-container">
                    <a href="#" class="social"><i class="fa-brands fa-google"></i></a>
                    <a href="#" class="social"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="social"><i class="fa-brands fa-github"></i></a>
                    <a href="#" class="social"><i class="fa-brands fa-linkedin-in"></i></a>
                </div>
                <span>Register with E-mail</span>
                <input type="text" placeholder="Name" required />
                <input type="email" placeholder="Enter E-mail" required />
                <input type="password" placeholder="Enter Password" required />
                <button type="submit">SIGN UP</button>
            </form>
        </div>

        <!-- Sign In Form (Left Side originally) -->
        <div class="form-container sign-in-container">
            <form action="login.php" method="POST">
                <h1>Sign In</h1>
                <div class="social-container">
                    <a href="#" class="social"><i class="fa-brands fa-google"></i></a>
                    <a href="#" class="social"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="social"><i class="fa-brands fa-github"></i></a>
                    <a href="#" class="social"><i class="fa-brands fa-linkedin-in"></i></a>
                </div>
                <span>Sign in With Email & Password</span>
                <?php if(isset($error)): ?>
                    <span style="color: red; margin-bottom: 10px; display: block;"><?php echo $error; ?></span>
                <?php endif; ?>
                <input type="text" name="email" placeholder="Enter E-mail or Admin" required />
                <input type="password" name="password" placeholder="Enter Password" required />
                <a href="#">Forget Password?</a>
                <button type="submit" name="login">SIGN IN</button>
            </form>
        </div>

        <!-- Sliding Red Overlay Panel -->
        <div class="overlay-container">
            <div class="overlay">
                <!-- Left Panel of Overlay (Visible when Signed In sliding to Sign Up) -->
                <div class="overlay-panel overlay-left">
                    <h1>Welcome To Tabon IS</h1>
                    <p>Sign in With Email & Password</p>
                    <button class="ghost" id="signIn">SIGN IN</button>
                </div>
                <!-- Right Panel of Overlay (Visible originally) -->
                <div class="overlay-panel overlay-right">
                    <h1>Hello World</h1>
                    <p>Sign up now and enjoy our site</p>
                    <button class="ghost" id="signUp">SIGN UP</button>
                </div>
            </div>
        </div>
        
    </div>

    <script>
        const signUpButton = document.getElementById('signUp');
        const signInButton = document.getElementById('signIn');
        const container = document.getElementById('container');

        signUpButton.addEventListener('click', () => {
            container.classList.add('right-panel-active');
        });

        signInButton.addEventListener('click', () => {
            container.classList.remove('right-panel-active');
        });
    </script>
    <script src="js/main.js"></script>
</body>
</html>


