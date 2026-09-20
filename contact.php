<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Tabon - Contact Us</title>
    
    <!-- FontAwesome CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600;1,700&display=swap" rel="stylesheet">
    
    <!-- External Stylesheets -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/contact.css?v=<?php echo time(); ?>">
</head>
<body class="contact-page">

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
            <a href="contact.php" class="nav-item active">
                <i class="fa-regular fa-envelope"></i>
                <span>Contact Us</span>
            </a>

            <a href="login.php" class="btn-login">
                <i class="fa-regular fa-user"></i>
                <span>Sign In / Login</span>
            </a>
        </nav>
    </header>

    <!-- Main Content Split Layout -->
    <main class="contact-content">
        
        <!-- Left Column: Info -->
        <div class="contact-left">
            <h1 class="contact-title">Contact Us <i class="fa-solid fa-leaf" style="font-size: 40px; color: #1a4971; opacity: 0.6;"></i></h1>
            <h3 class="contact-subtitle">We are here to help!</h3>
            <p class="contact-desc">
                Feel free to reach out to us for any inquiries, feedback, or concerns. Your message matters to us, and we will get back to you as soon as possible.
            </p>

            <div class="info-cards-grid">
                <!-- Location -->
                <div class="info-card">
                    <div class="info-icon"><i class="fa-solid fa-location-dot"></i></div>
                    <div class="info-text">
                        <h4>Our Location</h4>
                        <p>Barangay Tabon<br>Municipality of Lapuyan<br>Zamboanga del Sur</p>
                    </div>
                </div>
                
                <!-- Office Hours -->
                <div class="info-card">
                    <div class="info-icon"><i class="fa-regular fa-clock"></i></div>
                    <div class="info-text">
                        <h4>Office Hours</h4>
                        <p>Monday – Friday<br>8:00 AM – 5:00 PM<br>(Except Holidays)</p>
                    </div>
                </div>

                <!-- Phone -->
                <div class="info-card">
                    <div class="info-icon"><i class="fa-solid fa-phone"></i></div>
                    <div class="info-text">
                        <h4>Phone Number</h4>
                        <p>+63 XXX XXX XXXX<br>(Barangay Office)</p>
                    </div>
                </div>
            </div> <!-- Closes info-cards-grid -->


        </div>

        <!-- Right Column: Form -->
        <div class="contact-right">
            <div class="form-container">
                <div class="form-header">
                    <i class="fa-regular fa-paper-plane"></i>
                    <h2>Send Us a Message</h2>
                </div>
                <p class="form-desc">Fill out the form below and we'll get back to you shortly.</p>
                
                <form action="#" method="POST">
                    <div class="input-group">
                        <i class="fa-regular fa-user"></i>
                        <input type="text" class="form-input" placeholder="Full Name" required>
                    </div>
                    
                    <div class="input-group">
                        <i class="fa-regular fa-envelope"></i>
                        <input type="email" class="form-input" placeholder="Email Address" required>
                    </div>
                    
                    <div class="input-group">
                        <i class="fa-solid fa-phone"></i>
                        <input type="tel" class="form-input" placeholder="Phone Number">
                    </div>
                    
                    <div class="input-group textarea-group">
                        <i class="fa-regular fa-message"></i>
                        <textarea class="form-input" placeholder="Message" required></textarea>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fa-regular fa-paper-plane"></i> Send Message &rarr;
                    </button>
                </form>
            </div>
        </div>
        
    </main>

    <div class="contact-quote" style="margin: 20px auto 60px auto; max-width: 600px; padding: 0 20px;">
        "Tayo ang Bayanihan, Tayo ang Barangay Tabon." 
        <br>
        <i class="fa-solid fa-heart" style="font-size: 16px;"></i><i class="fa-solid fa-heart" style="font-size: 12px; margin-left: 5px;"></i>
    </div>
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


