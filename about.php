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
            <a href="index.php" class="nav-item ">
                <i class="fa-solid fa-house"></i>
                <span>Home</span>
            </a>
            <a href="about.php" class="nav-item active">
                <i class="fa-solid fa-users"></i>
                <span>About Us</span>
            </a>
            <div class="nav-item dropdown">
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

    <!-- Main Content Layout -->
    <main class="main-content">
        
        <!-- Separate About Us Container -->
        <section class="container-about">
            <div class="about-info fade-in-left animate-on-scroll">
                <h1 class="about-title">About Us</h1>
                <div class="about-text-scroll">
                    <p class="about-text">Tabon means "to cover". It is derived to the characteristics of a bird name in Subanen dialect “MANGAWAG”, because when this kind of bird will lay eggs, they will dig a hole in the ground and lay eggs therein, after that it will covered with earth/soil.</p>
                    <p class="about-text">Tabon was inhabited by Subanen who made their living by picking a fruit trees and hunting wild animals in the jungle. These Lumads made houses of “NIBONG” and “SUSAY” leaves for roofing and tying rattan for the light local materials, instead of using nails. As the Lumad hunters looked for food, he roamed around to hunt, until he saw something covered in the ground with dry leaves, branches and roots of trees with mixed soil. The hunter removed the cover and dig the hole, while digging he found out it was an eggs of a wild fowl called “MANGAWAG”. This kind of wild fowls, dig the ground to lay eggs and made it a nests. The word “TABON” came from the word “TABONAN” means “TO COVER”.</p>
                    <p class="about-text">Barangay Tabon is the home of the Lumad Subanen, one of the 18 major Lumad tribes in Mindanao Island. The first Subanen families who inhabited the place when it was not yet a Barangay are only the families of “MAMULO, UNGOB, & MANGEDLOG”.</p>
                    <p class="about-text">When the Barangay was not yet created it was a sitio of “BARANGAY MARWING”, and when Barangay Marwing was created into a Municipality in the year 1964, Tabon was created into Barangay by the Municipal Councilors of Marwing. That when Municipality of Marwing was dissolved in the year 1965, the municipal Government of Lapuyan still recognized Tabon as Barangay. That during those prior years a sitio will be created into a Barangay to not less than 500 inhabitants & in the year 1972, there was a Circular from the Office of the President that all Barangay should be approved by the House of Representatives and Senate and to be signed by the President before it will become a Regular Barangay. On “AUGUST 28, 1972”, by Virtue of “REPUBLIC ACT 3590” Tabon was created a regular Barangay.</p>
                    <p class="about-text">The first leader of the Barangay was “SAGUILE LIPA”, because it was created into Barangay when Marwing was created into Municipality and until Marwing was dissolved, the municipality of Lapuyan still recognized it as a Barangay. That during that time said SAGUILE LIPA was a municipal councilor.</p>
                    <p class="about-text">The first appointed Barangay Captain when Maruing was a Municipality was “BENTIA MALINTANG”, and when Maruing was dissolved being SAGUILE LIPA is no longer a Municipal Councilor, he was then appointed as Barangay Captain until he was again elected. Thereafter, it was again “PAPAO GALS, then RUBIO TILABAD, then Barangay Captain JANITO LIPA”, and the new elected Punong Barangay Nelcy D. Ongub.</p>
                </div>
            </div>
            
            <div class="polaroid-frame fade-in-right animate-on-scroll delay-1">
                <img src="images/barangay-photo.jpg" alt="Barangay Tabon View" class="polaroid-img" onerror="this.src='https://via.placeholder.com/300x200';">
                <div class="polaroid-label">Barangay Tabon</div>
            </div>
        </section>

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
