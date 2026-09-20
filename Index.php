<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Tabon - Home</title>
    
    <!-- FontAwesome CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- External Stylesheet -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body>

    <!-- Header / Navbar -->
    <header>
        <div class="brand-container">
            <!-- Replace src with your logo path e.g. "images/logo.png" -->
            <img src="Barangay Logo/Logo.png" alt="Logo" class="brand-logo">
            <div>
                <div class="brand-title">Barangay Tabon</div>
                <div class="brand-sub">Together for a Stronger Community</div>
            </div>
        </div>

        <nav>
            <a href="index.php" class="nav-item active">
                <i class="fa-solid fa-house"></i>
                <span>Home</span>
            </a>
            <a href="about.php" class="nav-item ">
                <i class="fa-solid fa-circle-info"></i>
                <span>About Us</span>
            </a>
                        <div class="nav-item dropdown  ">
                <a href="#" class="dropdown-toggle" style="text-decoration:none;">
                    <i class="fa-regular fa-image"></i>
                    <span>Gallery & Officials <i class="fa-solid fa-chevron-down" style="font-size:10px; margin-left:3px;"></i></span>
                </a>
                <div class="dropdown-menu">
                    <a href="officials.php" class="dropdown-item">Barangay Officials</a>
                    <a href="skofficials.php" class="dropdown-item">SK Officials</a>
                </div>
            </div>
            <a href="#" class="nav-item ">
                <i class="fa-solid fa-location-dot"></i>
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

    <!-- Hero Section with Cutout Layout -->
    <div class="hero-wrapper">
        <section class="hero-section">
            <div class="hero-content">
                <h1 class="hero-title">Building a Stronger<br>and United Community</h1>
                <p class="hero-subtitle">
                    The Barangay Tabon is committed to<br>providing quality public service, transparent governance,<br>and a better future for every Tabonon.
                </p>
            </div>
            
            <div class="hero-cutout">
                <a href="#" class="btn-explore">
                    <i class="fa-solid fa-building"></i>
                    <span>Explore Our Services</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </section>
    </div>

    <!-- About Section based on Mockup -->
    <div class="about-wrapper">
        <section class="about-section">
            <div class="about-content">
                <h2 class="about-title">Discover the Heart<br>of Barangay Tabon.</h2>
                <p class="about-desc">
                    Our barangay is dedicated to providing an inclusive, safe, and dynamic environment for all residents. With a deep commitment to community building, we actively embrace innovation and preserve our cultural heritage.
                </p>
                <p class="about-desc">
                    Learn how our programs and services uplift families, empower the youth, and create lasting opportunities for everyone.
                </p>
                <div class="about-actions">
                    <a href="about.php" class="btn-read-more">Read More</a>
                </div>
            </div>
            <div class="about-image">
                <img src="Images/Barangay Tabon Hall.png" alt="About Barangay Tabon">
            </div>
        </section>
    </div>

    <!-- Officials Section -->
    <div class="main-content" style="padding-bottom: 0;">
        <h2 class="gallery-title" style="margin-top: 40px;">Meet the Officials</h2>
        <section class="captain-card">
            <!-- Replace src with your photo path e.g. "images/captain.jpg" -->
            <img src="Officials/Captain.jpg" alt="Barangay Captain" class="captain-avatar">
            
            <div class="captain-content">
                <h4>Message from the Barangay Captain</h4>
                <blockquote>
                    <p>As the Barangay Captain of Tabon, I extend my profound gratitude and pride to our dedicated team for the successful completion of our Barangay Development Plan.</p>
                    <p>Through the invaluable support of our partner agencies and the unwavering commitment of our local officials, I am highly confident in our ability to realize these proposed programs and projects. Let us remain steadfast in our collaborative efforts to deliver tangible, meaningful results for the continuous progress and betterment of our entire community.</p>
                </blockquote>
                <div class="captain-signature">
                    <div class="captain-name">Hon. Nancy D. Ungos</div>
                    <div class="captain-title">Punong Barangay, Barangay Tabon</div>
                </div>
            </div>
        </section>
    </div>

    <!-- 3D Image Carousel Gallery -->
    <section class="gallery-wrapper" style="padding-top: 0;">
        <div class="carousel">
            <div class="track">
                <!-- 8 placeholder images from picsum.photos -->
                <div class="card"><img src="https://picsum.photos/id/1015/400/520" alt="Gallery 1"></div>
                <div class="card"><img src="https://picsum.photos/id/1016/400/520" alt="Gallery 2"></div>
                <div class="card"><img src="https://picsum.photos/id/1018/400/520" alt="Gallery 3"></div>
                <div class="card"><img src="https://picsum.photos/id/1019/400/520" alt="Gallery 4"></div>
                <div class="card"><img src="https://picsum.photos/id/1020/400/520" alt="Gallery 5"></div>
                <div class="card"><img src="https://picsum.photos/id/1021/400/520" alt="Gallery 6"></div>
                <div class="card"><img src="https://picsum.photos/id/1022/400/520" alt="Gallery 7"></div>
                <div class="card"><img src="https://picsum.photos/id/1023/400/520" alt="Gallery 8"></div>
            </div>
        </div>
    </section>

    <!-- Main Content Area -->
    <main class="main-content">
        <h2 class="gallery-title" style="margin-top: 20px;">The Services</h2>
        <!-- Services Quick Access Grid -->
        <section class="services-container">
            <a href="#" class="service-card">
                <i class="fa-solid fa-address-card"></i>
                <span>Resident Registry & Profiles</span>
            </a>
            <a href="#" class="service-card">
                <i class="fa-solid fa-building-columns"></i>
                <span>Barangay Clearance Issuance</span>
            </a>
            <a href="#" class="service-card">
                <i class="fa-solid fa-hand-holding-heart"></i>
                <span>Community Assistance Programs</span>
            </a>
            <a href="#" class="service-card">
                <i class="fa-regular fa-calendar-check"></i>
                <span>Official Events & Activities</span>
            </a>
            <a href="#" class="service-card">
                <i class="fa-solid fa-bullhorn"></i>
                <span>Public Announcements</span>
            </a>
            <a href="#" class="service-card">
                <i class="fa-solid fa-location-dot"></i>
                <span>Location & Directory</span>
            </a>
        </section>

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
                        <li><a href="about.php">About</a></li>
                        <li><a href="officials.php">Officials</a></li>
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

