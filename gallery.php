<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Tabon - Gallery</title>
    
    <!-- FontAwesome CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600;1,700&display=swap" rel="stylesheet">
    
    <!-- External Stylesheets -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/gallery.css?v=<?php echo time(); ?>">
</head>
<body class="gallery-page">

    <!-- Header / Navbar -->
    <header>
        <div class="brand-container">
            <img src="Barangay Logo/Logo.png" alt="Logo" class="brand-logo">
            <div>
                <div class="brand-title">Barangay Tabon</div>
                <div class="brand-sub">Together for a Stronger Community</div>
            </div>
        </div>

        <nav>
            <a href="index.php" class="nav-item ">
                <i class="fa-solid fa-house"></i>
                <span>Home</span>
            </a>
            <a href="about.php" class="nav-item ">
                <i class="fa-solid fa-circle-info"></i>
                <span>About Us</span>
            </a>
            <a href="gallery.php" class="nav-item active">
                <i class="fa-regular fa-image"></i>
                <span>Gallery</span>
            </a>
            <div class="nav-item dropdown  ">
                <a href="#" class="dropdown-toggle" style="text-decoration:none;">
                    <i class="fa-solid fa-user-tie"></i>
                    <span>Officials <i class="fa-solid fa-chevron-down" style="font-size:10px; margin-left:3px;"></i></span>
                </a>
                <div class="dropdown-menu">
                    <a href="officials.php" class="dropdown-item">Barangay Officials</a>
                    <a href="skofficials.php" class="dropdown-item">SK Officials</a>
                </div>
            </div>
            <a href="spotmap.php" class="nav-item ">
                <i class="fa-solid fa-map"></i>
                <span>Spot Map</span>
            </a>
            <a href="contact.php" class="nav-item ">
                <i class="fa-regular fa-envelope"></i>
                <span>Contact Us</span>
            </a>

            <a href="login.php" class="btn-login">
                <i class="fa-regular fa-user"></i>
                <span>Sign In / Login</span>
            </a>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="gallery-main fade-in-up animate-on-scroll">
        <div class="page-title-section">
            <h1 class="page-title">Media Gallery</h1>
            <p class="page-subtitle">Glimpses of Barangay Tabon's events and community life.</p>
        </div>

        <div class="gallery-container">
            <!-- Sample Gallery Grid -->
            <div class="gallery-grid">
                <div class="gallery-item">
                    <img src="https://via.placeholder.com/400x300?text=Event+1" alt="Event 1">
                    <div class="gallery-overlay">
                        <h3>Community Meeting</h3>
                    </div>
                </div>
                <div class="gallery-item">
                    <img src="https://via.placeholder.com/400x300?text=Event+2" alt="Event 2">
                    <div class="gallery-overlay">
                        <h3>Medical Mission</h3>
                    </div>
                </div>
                <div class="gallery-item">
                    <img src="https://via.placeholder.com/400x300?text=Event+3" alt="Event 3">
                    <div class="gallery-overlay">
                        <h3>Sports Fest</h3>
                    </div>
                </div>
                <div class="gallery-item">
                    <img src="https://via.placeholder.com/400x300?text=Event+4" alt="Event 4">
                    <div class="gallery-overlay">
                        <h3>Clean-up Drive</h3>
                    </div>
                </div>
                <div class="gallery-item">
                    <img src="https://via.placeholder.com/400x300?text=Event+5" alt="Event 5">
                    <div class="gallery-overlay">
                        <h3>Youth Assembly</h3>
                    </div>
                </div>
                <div class="gallery-item">
                    <img src="https://via.placeholder.com/400x300?text=Event+6" alt="Event 6">
                    <div class="gallery-overlay">
                        <h3>Barangay Fiesta</h3>
                    </div>
                </div>
            </div>
        </div>
    </main>

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
                    <li><a href="about.php">About</a></li>
                    <li><a href="officials.php">Officials</a></li>
                    <li><a href="gallery.php">Gallery</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>
            
            <div class="footer-links">
                <h4>Utilities</h4>
                <ul>
                    <li><a href="login.php">Admin Login</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms and Condition</a></li>
                </ul>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>
