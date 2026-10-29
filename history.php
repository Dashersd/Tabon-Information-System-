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
    <title>Barangay Tabon - History</title>
    
    <!-- FontAwesome CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- External Stylesheets (Uses style.css & about.css) -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/about.css?v=<?php echo time(); ?>">
    <style>
        .history-page .main-content {
            max-width: 1380px;
            padding: 20px 40px 60px 40px;
        }
        .history-wrapper {
            max-width: 1280px;
            margin: 10px auto 40px auto;
            width: 100%;
        }
        .history-page .about-text-scroll {
            max-height: 560px;
        }
        @media (max-width: 768px) {
            .history-page .main-content {
                padding: 15px 20px 40px 20px;
            }
        }
    </style>
</head>
<body class="history-page">

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
                <span>Home</span>
            </a>
            <div class="nav-item dropdown active">
                <a href="#" class="dropdown-toggle" style="text-decoration:none;">
                    <span>About Us <i class="fa-solid fa-chevron-down" style="font-size:10px; margin-left:3px;"></i></span>
                </a>
                <div class="dropdown-menu">
                    <a href="history.php" class="dropdown-item" style="color: #fbb117; font-weight: 600;">History</a>
                    <a href="mission.php" class="dropdown-item">Mission</a>
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

            <a href="login.php" class="btn-login">
                <i class="fa-regular fa-user"></i>
                <span>Sign In / Login</span>
            </a>
        </nav>
    </header>

    <!-- Main Content Layout -->
    <main class="main-content">
        
        <div class="history-wrapper">
            <!-- History Section -->
            <section class="container-about">
                <div class="about-info fade-in-left animate-on-scroll">
                    <h1 class="about-title">History</h1>
                    <div class="about-text-scroll" data-lenis-prevent="true">
                        <?php
                        $paras = preg_split('/\r\n\r\n|\n\n|\r\r/', $about_data['history'] ?? '');
                        if (!empty($paras)) {
                            foreach ($paras as $p) {
                                $trimmed = trim($p);
                                if (!empty($trimmed)) {
                                    echo '<p class="about-text">' . nl2br(htmlspecialchars($trimmed)) . '</p>';
                                }
                            }
                        } else {
                            echo '<p class="about-text">' . nl2br(htmlspecialchars($about_data['history'] ?? '')) . '</p>';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="polaroid-frame fade-in-right animate-on-scroll delay-1">
                    <img src="<?= htmlspecialchars($about_img_src) ?>?v=<?= $img_cache_bust ?>" alt="Barangay Tabon View" class="polaroid-img" onerror="this.src='Images/Tabon Barangay hall.jpg';">
                    <div class="polaroid-label">Barangay Tabon</div>
                </div>
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

    <script src="js/main.js?v=<?= time() ?>"></script>
</body>
</html>
