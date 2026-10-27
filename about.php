<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Tabon - About Us</title>
    
    <!-- FontAwesome CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- External Stylesheets (Uses style.css & about.css) -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/about.css?v=<?php echo time(); ?>">
</head>
<body>

    <!-- Header / Navbar -->
    <header>
        <div class="brand-container">
            <img src="Barangay Logo/Logo.png" alt="Barangay Logo" class="brand-logo" onerror="this.src='https://via.placeholder.com/65';">
            <div>
                <div class="brand-title">Barangay Tabon</div>
                <div class="brand-sub">Together for a Stronger Community</div>
            </div>
        </div>

        <nav>
            <a href="index.php" class="nav-item">
                <i class="fa-solid fa-house"></i>
                <span>Home</span>
            </a>
            <div class="nav-item dropdown">
                <a href="#" class="dropdown-toggle" style="text-decoration:none;">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>About Us <i class="fa-solid fa-chevron-down" style="font-size:10px; margin-left:3px;"></i></span>
                </a>
                <div class="dropdown-menu">
                    <a href="history.php" class="dropdown-item">History</a>
                    <a href="about.php" class="dropdown-item" style="color: #fbb117; font-weight: 600;">Mission & Vision</a>
                </div>
            </div>
            <div class="nav-item dropdown">
                <a href="#" class="dropdown-toggle" style="text-decoration:none;">
                    <i class="fa-solid fa-user-tie"></i>
                    <span>Officials <i class="fa-solid fa-chevron-down" style="font-size:10px; margin-left:3px;"></i></span>
                </a>
                <div class="dropdown-menu">
                    <a href="officials.php" class="dropdown-item">Barangay Officials</a>
                    <a href="skofficials.php" class="dropdown-item">SK Officials</a>
                </div>
            </div>
            <a href="gallery.php" class="nav-item">
                <i class="fa-regular fa-image"></i>
                <span>Gallery</span>
            </a>
            <a href="service.php" class="nav-item">
                <i class="fa-solid fa-bell-concierge"></i>
                <span>Service</span>
            </a>
            <a href="announcement.php" class="nav-item">
                <i class="fa-solid fa-bullhorn"></i>
                <span>Announcement</span>
            </a>
            <a href="spotmap.php" class="nav-item">
                <i class="fa-solid fa-map"></i>
                <span>Spot Map</span>
            </a>
            <a href="contact.php" class="nav-item">
                <i class="fa-regular fa-envelope"></i>
                <span>Contact Us</span>
            </a>

            <a href="login.php" class="btn-login">
                <i class="fa-regular fa-user"></i>
                <span>Sign In / Login</span>
            </a>
        </nav>
    </header>

    <!-- Main Content Layout -->
    <main class="main-content">
        
        <div class="about-wrapper" style="max-width: 1100px; margin: 0 auto; padding-top: 10px;">
            <div style="text-align: center; margin-bottom: 40px;">
                <h1 class="about-title" style="margin-bottom: 10px;">Mission &amp; Vision</h1>
                <p style="color: var(--text-muted); font-size: 15px;">Guiding principles and future aspirations of Barangay Tabon</p>
            </div>

            <!-- Mission & Vision Containers -->
            <div class="mv-grid">
                
                <!-- Mission Container -->
                <section class="container-mission fade-in-up animate-on-scroll delay-1">
                    <div class="mv-header">
                        <i class="fa-solid fa-bullseye mv-icon"></i>
                        <span class="mv-divider">|</span>
                        <h2 class="mv-title">Mission</h2>
                    </div>
                    <blockquote class="mv-quote">
                        The Barangay Officials and the people in the Barangay united in working for the attainment of sufficient and effective services, increased income through developed agriculture and create activities that would foster greater unity and strong relationships among settlers, under the strong and good overnment administration and continually living and enjoying a balanced environment.
                    </blockquote>
                </section>

                <!-- Vision Container -->
                <section class="container-vision fade-in-up animate-on-scroll delay-2">
                    <div class="mv-header">
                        <i class="fa-regular fa-eye mv-icon"></i>
                        <span class="mv-divider">|</span>
                        <h2 class="mv-title">Vision</h2>
                    </div>
                    <blockquote class="mv-quote">
                        "The Barangay Tabon envisions a dynamic Barangay whose united, God-loving and empowered people. The Subanens and settlers will live harmoniously and peacefully, having a progressive and decent living in a balanced environment under a strong and good government administration."
                    </blockquote>
                </section>
            </div>
        </div>

    </main>

    <!-- Footer Accent -->
    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-brand">
                <div class="footer-logo">
                    <img src="Barangay Logo/Logo.png" alt="Barangay Tabon Logo">
                    <h2>Barangay Tabon</h2>
                </div>
                <p>The Barangay Tabon information system aims to provide better services, transparent governance, and a vibrant community experience for all residents.</p>
            </div>
            
            <div class="footer-links">
                <h4>Menu</h4>
                <ul>
                    <li><a href="Index.php">Home</a></li>
                    <li><a href="history.php">History</a></li>
                    <li><a href="about.php">Mission & Vision</a></li>
                    <li><a href="officials.php">Officials</a></li>
                    <li><a href="gallery.php">Gallery</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>
            
            <div class="footer-links footer-partner-col">
                <h4>Partnership by</h4>
                <p class="footer-partner-name">Municipality of Lapuyan</p>
                <div class="footer-partner-wrap">
                    <img src="Barangay Logo/lapuyanlogo.jpg" alt="Municipality of Lapuyan Logo" class="footer-partner-logo">
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Barangay Tabon Information System. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>
