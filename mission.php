<?php
require_once 'db_connect.php';

// Fetch dynamic About Content (Image, History, Mission, Vision)
$about_data = [
    'history' => '',
    'mission' => '',
    'vision' => '',
    'image_path' => 'Images/Tabon Barangay hall.jpg',
    'updated_at' => null
];

try {
    $ab_query = $conn->query("SELECT * FROM `about_content` WHERE `id` = 1 LIMIT 1");
    if ($ab_query && $ab_query->num_rows > 0) {
        $about_data = $ab_query->fetch_assoc();
    }
} catch (Exception $e) {}

// Resolve image URL
$about_img_src = 'Images/Tabon Barangay hall.jpg';
if (!empty($about_data['image_path'])) {
    if (strpos($about_data['image_path'], 'uploads/') === 0) {
        $about_img_src = 'Admin/' . $about_data['image_path'];
    } else {
        $about_img_src = $about_data['image_path'];
    }
}
$img_cache_bust = !empty($about_data['updated_at']) ? strtotime($about_data['updated_at']) : time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Tabon - Mission</title>
    
    <!-- FontAwesome CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- External Stylesheets (Uses style.css & about.css) -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/about.css?v=<?php echo time(); ?>">
</head>
<body class="mission-page">

    <!-- Header / Navbar -->
    <header>
        <div class="brand-container">
            <img src="Barangay Logo/Logo.png" alt="Barangay Logo" class="brand-logo" onerror="this.src='https://via.placeholder.com/65';">
            <div>
                <div class="brand-title">Barangay Tabon</div>
                <div class="brand-sub">Together for a Stronger Community</div>
            </div>
            <img src="Barangay Logo/lapuyanlogo.jpg" alt="Municipality of Lapuyan Logo" class="brand-logo brand-logo-secondary" onerror="this.src='https://via.placeholder.com/65';">
        </div>

        <nav>
            <a href="index.php" class="nav-item">
                <span>Home</span>
            </a>
            <div class="nav-item dropdown active">
                <a href="#" class="dropdown-toggle" style="text-decoration:none;">
                    <span>About Us <i class="fa-solid fa-chevron-down" style="font-size:10px; margin-left:3px;"></i></span>
                </a>
                <div class="dropdown-menu">
                    <a href="history.php" class="dropdown-item">History</a>
                    <a href="mission.php" class="dropdown-item" style="color: #fbb117; font-weight: 600;">Mission</a>
                    <a href="vision.php" class="dropdown-item">Vision</a>
                    <a href="officials.php" class="dropdown-item">Barangay Officials</a>
                    <a href="skofficials.php" class="dropdown-item">SK Officials</a>
                </div>
            </div>
            <a href="gallery.php" class="nav-item">
                <span>Gallery</span>
            </a>
            <a href="service.php" class="nav-item">
                <span>Service</span>
            </a>
            <a href="announcement.php" class="nav-item">
                <span>Announcement</span>
            </a>
            <a href="spotmap.php" class="nav-item">
                <span>Spot Map</span>
            </a>
            <a href="contact.php" class="nav-item">
                <span>Contact Us</span>
            </a>
        </nav>
    </header>

    <!-- Main Content Layout -->
    <main class="main-content">
        
        <div class="about-wrapper mission-wrapper" style="max-width: 900px; margin: 0 auto; padding-top: 25px;">
            
            <!-- Mission Header -->
            <div class="mission-page-header fade-in-down animate-on-scroll" style="text-align: center; margin-bottom: 35px;">
                <div class="mission-badge" style="display: inline-flex; align-items: center; gap: 8px; background: #e0f2fe; color: #0369a1; padding: 6px 18px; border-radius: 20px; font-size: 13px; font-weight: 600; margin-bottom: 15px;">
                    <i class="fa-solid fa-bullseye"></i> Our Purpose &amp; Commitment
                </div>
                <h1 style="font-family: var(--font-heading); font-style: italic; font-size: 52px; color: var(--secondary-black); margin-bottom: 10px; line-height: 1.1;">Barangay Mission</h1>
                <p style="color: var(--text-muted); font-size: 15px; max-width: 620px; margin: 0 auto;">Guiding principles, dedicated public service, and the core purpose of Barangay Tabon.</p>
            </div>

            <!-- Single Dedicated Mission Container -->
            <section class="container-mission solo-mission fade-in-up animate-on-scroll delay-1" id="mission" style="max-width: 820px; margin: 0 auto 50px auto; padding: 45px 50px; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); border-radius: 24px; border: 1px solid rgba(255, 255, 255, 0.95); box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05);">
                <div class="mv-header" style="justify-content: center; margin-bottom: 25px;">
                    <i class="fa-solid fa-bullseye mv-icon" style="font-size: 34px; color: #0284c7;"></i>
                    <span class="mv-divider" style="font-size: 26px;">|</span>
                    <h2 class="mv-title" style="font-size: 34px;">Mission Statement</h2>
                </div>
                <blockquote class="mv-quote solo-quote" style="font-size: 16.5px; line-height: 1.85; color: #2d3748; text-align: center; max-width: 700px; margin: 0 auto; padding: 10px 30px;">
                    <?= nl2br(htmlspecialchars($about_data['mission'] ?? '')) ?>
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
                    <li><a href="index.php">Home</a></li>
                    <li><a href="history.php">History</a></li>
                    <li><a href="mission.php">Mission</a></li>
                    <li><a href="vision.php">Vision</a></li>
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

    <script src="js/main.js?v=<?= time() ?>"></script>
</body>
</html>
