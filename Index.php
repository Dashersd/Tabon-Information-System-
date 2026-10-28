<?php
require_once 'db_connect.php';

// Fetch gallery photos for 3D carousel
$carousel_images = [];
try {
    $carousel_query = "SELECT m.*, f.name as folder_name 
                       FROM gallery_media m 
                       JOIN gallery_folders f ON m.folder_id = f.id 
                       ORDER BY m.id DESC LIMIT 16";
    $c_res = $conn->query($carousel_query);
    if ($c_res) {
        while ($row = $c_res->fetch_assoc()) {
            $checkPath = (strpos($row['file_path'], 'Admin/') === 0) ? $row['file_path'] : 'Admin/' . $row['file_path'];
            if (file_exists($checkPath)) {
                $row['web_src'] = $checkPath;
                $carousel_images[] = $row;
            }
        }
    }
} catch (Exception $e) {}

// If gallery has uploaded photos, ensure at least 8 cards for a seamless 3D curved loop
$carousel_display_list = [];
if (!empty($carousel_images)) {
    $carousel_display_list = $carousel_images;
    while (count($carousel_display_list) < 8) {
        $carousel_display_list = array_merge($carousel_display_list, $carousel_images);
    }
}

// Fetch active services for the Services quick access grid
$home_services = [];
try {
    $s_res = $conn->query("SELECT * FROM services WHERE status = 'active' ORDER BY display_order ASC, id ASC LIMIT 6");
    if ($s_res) {
        while ($s_row = $s_res->fetch_assoc()) {
            $home_services[] = $s_row;
        }
    }
} catch (Exception $e) {}

// Fetch published announcements for the Announcements section
$home_announcements = [];
try {
    $a_res = $conn->query("SELECT * FROM announcements WHERE status = 'published' ORDER BY created_at DESC LIMIT 3");
    if ($a_res) {
        while ($a_row = $a_res->fetch_assoc()) {
            $home_announcements[] = $a_row;
        }
    }
} catch (Exception $e) {}

// Fetch dynamic About Content for the Home page About Section
$home_about = [
    'history' => '',
    'mission' => '',
    'vision' => '',
    'image_path' => 'Images/Tabon Barangay hall.jpg',
    'updated_at' => null
];
try {
    $ab_query = $conn->query("SELECT * FROM `about_content` WHERE `id` = 1 LIMIT 1");
    if ($ab_query && $ab_query->num_rows > 0) {
        $home_about = $ab_query->fetch_assoc();
    }
} catch (Exception $e) {}

$home_about_img = 'Images/Tabon Barangay hall.jpg';
if (!empty($home_about['image_path'])) {
    if (strpos($home_about['image_path'], 'uploads/') === 0) {
        $home_about_img = 'Admin/' . $home_about['image_path'];
    } else {
        $home_about_img = $home_about['image_path'];
    }
}
$home_about_cache = !empty($home_about['updated_at']) ? strtotime($home_about['updated_at']) : time();

// Fetch captain details for Captain Spotlight
$home_captain = [
    'name' => 'Hon. Reynaldo D. Santos',
    'position' => 'BARANGAY CAPTAIN',
    'photo' => 'Officials/Captain.jpg',
    'message' => 'To our residents, partners, and every member of our community, thank you for your trust, cooperation, and unwavering support. At Barangay Tabon, we remain committed to delivering honest and transparent public service that promotes safety, unity, and progress for all. Together, let us continue building a barangay where every family feels heard, respected, and supported.'
];
try {
    $c_stmt = $conn->query("SELECT * FROM `officials` WHERE `official_key` = 'captain' LIMIT 1");
    if ($c_stmt && $c_stmt->num_rows > 0) {
        $c_row = $c_stmt->fetch_assoc();
        if (!empty($c_row['name'])) $home_captain['name'] = $c_row['name'];
        if (!empty($c_row['position'])) $home_captain['position'] = $c_row['position'];
        if (!empty($c_row['message'])) $home_captain['message'] = $c_row['message'];
        if (!empty($c_row['photo'])) {
            $home_captain['photo'] = (strpos($c_row['photo'], 'uploads/') === 0 ? 'Admin/' . $c_row['photo'] : $c_row['photo']);
        }
    }
} catch (Exception $e) {}



function get_home_announcement_date($when_date, $created_at) {
    $ts = !empty($when_date) ? strtotime($when_date) : null;
    if (!$ts || $ts <= 0) {
        $ts = !empty($created_at) ? strtotime($created_at) : time();
    }
    return [
        'month' => strtoupper(date('M', $ts)),
        'day'   => date('d', $ts)
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Tabon - Home</title>
    
    <!-- FontAwesome & Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Preloader Stylesheet -->
    <link rel="stylesheet" href="css/preloader.css?v=<?php echo time(); ?>">

    <!-- External Stylesheet -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body>

    <!-- Mobile Legends Inspired Animated Preloader -->
    <?php include 'preloader.php'; ?>

    <!-- Header / Navbar -->
    <header>
        <div class="brand-container">
            <img src="Barangay Logo/Logo.png" alt="Barangay Tabon Logo" class="brand-logo" onerror="this.src='https://via.placeholder.com/65';">
            <div>
                <div class="brand-title">Barangay Tabon</div>
                <div class="brand-sub">Together for a Stronger Community</div>
            </div>
        </div>

        <nav>
            <a href="index.php" class="nav-item active">
                <span>Home</span>
            </a>
            <div class="nav-item dropdown">
                <a href="#" class="dropdown-toggle" style="text-decoration:none;">
                    <span>About Us <i class="fa-solid fa-chevron-down" style="font-size:10px; margin-left:3px;"></i></span>
                </a>
                <div class="dropdown-menu">
                    <a href="history.php" class="dropdown-item">History</a>
                    <a href="about.php" class="dropdown-item">Mission & Vision</a>
                </div>
            </div>
            <div class="nav-item dropdown">
                <a href="#" class="dropdown-toggle" style="text-decoration:none;">
                    <span>Officials <i class="fa-solid fa-chevron-down" style="font-size:10px; margin-left:3px;"></i></span>
                </a>
                <div class="dropdown-menu">
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

    <!-- Hero Section -->
    <div class="hero-wrapper">
        <section class="hero-section">
            <div class="hero-content">
                <h1 class="hero-title fade-in-left">Building a Stronger<br>and United Community</h1>
                <p class="hero-subtitle fade-in-left delay-1">
                    The Barangay Tabon is committed to<br>providing quality public service, transparent governance,<br>and a better future for every Tabonon.
                </p>
                <a href="#services-section" class="btn-explore fade-in-up delay-2">
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
                <h2 class="about-title fade-in-left animate-on-scroll">Discover the Heart<br>of Barangay Tabon.</h2>
                <p class="about-desc fade-in-left animate-on-scroll delay-1">
                    Our barangay is dedicated to providing an inclusive, safe, and dynamic environment for all residents. With a deep commitment to community building, we actively embrace innovation and preserve our cultural heritage.
                </p>
                <?php if (!empty($home_about['mission']) || !empty($home_about['vision'])): ?>
                <div class="about-mv-highlights fade-in-left animate-on-scroll delay-2" style="display: flex; gap: 15px; margin: 10px 0 20px 0; flex-wrap: wrap;">
                    <?php if (!empty($home_about['mission'])): ?>
                    <div style="flex: 1; min-width: 200px; background: rgba(0,0,0,0.03); border-left: 3px solid #1a4d33; padding: 10px 14px; border-radius: 6px;">
                        <strong style="display:block; font-size: 11px; color: #1a4d33; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px;"><i class="fa-solid fa-bullseye"></i> Mission</strong>
                        <p style="font-size: 12.5px; color: var(--text-muted); line-height: 1.45; margin: 0;"><?= htmlspecialchars(mb_strimwidth($home_about['mission'], 0, 110, '...')) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($home_about['vision'])): ?>
                    <div style="flex: 1; min-width: 200px; background: rgba(0,0,0,0.03); border-left: 3px solid #fbb117; padding: 10px 14px; border-radius: 6px;">
                        <strong style="display:block; font-size: 11px; color: #b7791f; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px;"><i class="fa-regular fa-eye"></i> Vision</strong>
                        <p style="font-size: 12.5px; color: var(--text-muted); line-height: 1.45; margin: 0;"><?= htmlspecialchars(mb_strimwidth($home_about['vision'], 0, 110, '...')) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <p class="about-desc fade-in-left animate-on-scroll delay-2">
                    Learn how our programs and services uplift families, empower the youth, and create lasting opportunities for everyone.
                </p>
                <?php endif; ?>
                <div class="about-actions fade-in-left animate-on-scroll delay-3">
                    <a href="about.php" class="btn-read-more">Read More</a>
                </div>
            </div>
            <div class="about-image fade-in-right animate-on-scroll delay-2">
                <img src="<?= htmlspecialchars($home_about_img) ?>?v=<?= $home_about_cache ?>" alt="About Barangay Tabon" onerror="this.src='Images/Tabon Barangay hall.jpg';">
            </div>
        </section>
    </div>

    <!-- Officials Section -->
    <section class="officials-spotlight-section">
        <div class="captain-spotlight-card fade-in-up animate-on-scroll">
            <div class="captain-spotlight-img-box">
                <img src="<?= htmlspecialchars($home_captain['photo']) ?>?v=<?= time() ?>" alt="Barangay Captain" class="captain-spotlight-photo" onerror="this.src='Officials/Captain.jpg';">
            </div>
            <div class="captain-spotlight-details">
                <span class="captain-badge-title"><?= htmlspecialchars(strtoupper($home_captain['position'])) ?></span>
                <h2 class="captain-fullname"><?= htmlspecialchars($home_captain['name']) ?></h2>
                <p class="captain-quote-text">
                    "<?= htmlspecialchars($home_captain['message']) ?>"
                </p>
                <a href="officials.php" class="btn-captain-view-officials">
                    <span>View Barangay Officials</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- 3D Image Carousel Gallery (Fetches Images from Gallery) -->
    <section class="gallery-wrapper" style="padding-top: 0;">
        <div class="carousel">
            <div class="track">
                <?php if (!empty($carousel_display_list)): ?>
                    <?php foreach ($carousel_display_list as $c_img): 
                        $c_title = !empty($c_img['caption']) ? $c_img['caption'] : (!empty($c_img['file_name']) ? pathinfo($c_img['file_name'], PATHINFO_FILENAME) : 'Community Highlight');
                        $c_folder = !empty($c_img['folder_name']) ? $c_img['folder_name'] : 'General';
                        $c_date = !empty($c_img['created_at']) ? date('M d, Y • h:i A', strtotime($c_img['created_at'])) : date('M d, Y • h:i A');
                    ?>
                        <div class="card" data-title="<?= htmlspecialchars($c_title) ?>" data-folder="<?= htmlspecialchars($c_folder) ?>" data-date="<?= htmlspecialchars($c_date) ?>" data-img="<?= htmlspecialchars($c_img['web_src']) ?>">
                            <img src="<?= htmlspecialchars($c_img['web_src']) ?>" alt="<?= htmlspecialchars($c_title) ?>" loading="lazy">
                            <div class="card-overlay">
                                <div class="card-overlay-btn" title="View Fullscreen">
                                    <i class="fa-solid fa-expand"></i>
                                </div>
                                <div class="card-overlay-title"><?= htmlspecialchars($c_title) ?></div>
                                <div class="card-overlay-folder">
                                    <i class="fa-solid fa-folder"></i> <?= strtoupper(htmlspecialchars($c_folder)) ?>
                                </div>
                                <div class="card-overlay-date">
                                    <i class="fa-regular fa-clock"></i> <?= htmlspecialchars($c_date) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Fallback placeholder images if no gallery photos uploaded yet -->
                    <?php 
                    $fallbacks = [
                        ['id' => 1015, 'title' => 'Community Service', 'folder' => 'Community Services', 'date' => 'Oct 13, 2026 • 09:52 AM'],
                        ['id' => 1016, 'title' => 'Barangay Assembly', 'folder' => 'Meetings', 'date' => 'Oct 14, 2026 • 02:30 PM'],
                        ['id' => 1018, 'title' => 'Sports Festival', 'folder' => 'Sports', 'date' => 'Oct 16, 2026 • 08:00 AM'],
                        ['id' => 1019, 'title' => 'Health & Wellness', 'folder' => 'Health Care', 'date' => 'Oct 18, 2026 • 10:15 AM'],
                        ['id' => 1020, 'title' => 'Youth Development', 'folder' => 'SK Programs', 'date' => 'Oct 20, 2026 • 01:45 PM'],
                        ['id' => 1021, 'title' => 'Clean & Green Drive', 'folder' => 'Environmental', 'date' => 'Oct 21, 2026 • 07:30 AM'],
                        ['id' => 1022, 'title' => 'Fiesta Celebrations', 'folder' => 'Cultural Events', 'date' => 'Oct 23, 2026 • 06:00 PM'],
                        ['id' => 1023, 'title' => 'Relief Distribution', 'folder' => 'Social Welfare', 'date' => 'Oct 25, 2026 • 09:00 AM'],
                    ];
                    foreach ($fallbacks as $fb): ?>
                        <div class="card" data-title="<?= $fb['title'] ?>" data-folder="<?= $fb['folder'] ?>" data-date="<?= $fb['date'] ?>" data-img="https://picsum.photos/id/<?= $fb['id'] ?>/400/520">
                            <img src="https://picsum.photos/id/<?= $fb['id'] ?>/400/520" alt="<?= $fb['title'] ?>">
                            <div class="card-overlay">
                                <div class="card-overlay-btn" title="View Fullscreen">
                                    <i class="fa-solid fa-expand"></i>
                                </div>
                                <div class="card-overlay-title"><?= $fb['title'] ?></div>
                                <div class="card-overlay-folder">
                                    <i class="fa-solid fa-folder"></i> <?= strtoupper($fb['folder']) ?>
                                </div>
                                <div class="card-overlay-date">
                                    <i class="fa-regular fa-clock"></i> <?= $fb['date'] ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Main Content Area -->
    <main class="main-content" id="main-content">

        <!-- Services Section -->
        <section class="home-services-wrapper" id="services-section">
            <div class="home-services-header">
                <div class="home-services-header-text">
                    <span class="home-services-badge fade-in-up animate-on-scroll">
                        <i class="fa-solid fa-hand-holding-hand"></i> Frontline Citizen Services
                    </span>
                    <h2 class="home-services-title fade-in-up animate-on-scroll delay-1">Barangay Tabon Services</h2>
                    <p class="home-services-subtitle fade-in-up animate-on-scroll delay-2">
                        Fast, accessible, and transparent frontline public assistance, community clearances, and certifications.
                    </p>
                </div>
                <div class="home-services-header-action fade-in-up animate-on-scroll delay-2">
                    <a href="service.php" class="btn-view-all-services">
                        <span>View All Services</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            <!-- Services Grid -->
            <div class="home-services-grid">
                <?php if (!empty($home_services)): ?>
                    <?php $delay = 0.1; foreach ($home_services as $hs): ?>
                        <?php 
                            $rawIcon = trim($hs['icon'] ?? '');
                            if (empty($rawIcon)) {
                                $iconClass = 'fa-solid fa-file-lines';
                            } elseif (strpos($rawIcon, 'bi-') === 0) {
                                $iconClass = 'bi ' . $rawIcon;
                            } elseif (strpos($rawIcon, 'fa-') !== false) {
                                $iconClass = $rawIcon;
                            } else {
                                $iconClass = 'bi bi-' . $rawIcon;
                            }
                            $serviceFee = !empty($hs['fee']) ? $hs['fee'] : 'Free';
                            $serviceProcTime = !empty($hs['processing_time']) ? $hs['processing_time'] : 'Same Day';
                            $serviceDesc = !empty($hs['description']) ? $hs['description'] : 'Official barangay document and citizen service provided by Barangay Tabon.';
                        ?>
                        <div class="home-service-card fade-in-up animate-on-scroll" style="animation-delay: <?= number_format($delay, 1) ?>s;" onclick="window.location.href='service.php?id=<?= (int)$hs['id'] ?>#service-<?= (int)$hs['id'] ?>'" role="button" tabindex="0">
                            <div class="home-service-card-top">
                                <div class="home-service-icon-box">
                                    <i class="<?= htmlspecialchars($iconClass) ?>"></i>
                                </div>
                                <div class="home-service-badges">
                                    <span class="service-pill fee"><i class="fa-solid fa-peso-sign"></i> <?= htmlspecialchars($serviceFee) ?></span>
                                    <span class="service-pill time"><i class="fa-regular fa-clock"></i> <?= htmlspecialchars($serviceProcTime) ?></span>
                                </div>
                            </div>
                            <h3 class="home-service-name"><?= htmlspecialchars($hs['name']) ?></h3>
                            <p class="home-service-desc"><?= htmlspecialchars($serviceDesc) ?></p>
                            <div class="home-service-card-footer">
                                <a href="service.php?id=<?= (int)$hs['id'] ?>#service-<?= (int)$hs['id'] ?>" class="btn-card-action" onclick="event.stopPropagation();">
                                    <span><?= htmlspecialchars(!empty($hs['button_text']) ? $hs['button_text'] : 'Request Document') ?></span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php $delay += 0.08; endforeach; ?>
                <?php else: ?>
                    <div class="home-service-card fade-in-up animate-on-scroll" style="animation-delay: 0.1s;" onclick="window.location.href='service.php'">
                        <div class="home-service-card-top">
                            <div class="home-service-icon-box"><i class="fa-solid fa-stamp"></i></div>
                            <div class="home-service-badges">
                                <span class="service-pill fee"><i class="fa-solid fa-peso-sign"></i> ₱50.00</span>
                                <span class="service-pill time"><i class="fa-regular fa-clock"></i> 10-15 Mins</span>
                            </div>
                        </div>
                        <h3 class="home-service-name">Barangay Clearance</h3>
                        <p class="home-service-desc">Official certification required for job applications, postal ID, and general legal verification.</p>
                        <div class="home-service-card-footer">
                            <a href="service.php" class="btn-card-action" onclick="event.stopPropagation();"><span>Request Document</span> <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                    </div>
                    <div class="home-service-card fade-in-up animate-on-scroll" style="animation-delay: 0.2s;" onclick="window.location.href='service.php'">
                        <div class="home-service-card-top">
                            <div class="home-service-icon-box"><i class="fa-solid fa-hands-holding-child"></i></div>
                            <div class="home-service-badges">
                                <span class="service-pill fee"><i class="fa-solid fa-peso-sign"></i> Free</span>
                                <span class="service-pill time"><i class="fa-regular fa-clock"></i> Same Day</span>
                            </div>
                        </div>
                        <h3 class="home-service-name">Certificate of Indigency</h3>
                        <p class="home-service-desc">Issued to low-income residents for medical, educational, burial, or legal assistance from DSWD.</p>
                        <div class="home-service-card-footer">
                            <a href="service.php" class="btn-card-action" onclick="event.stopPropagation();"><span>Request Assistance</span> <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                    </div>
                    <div class="home-service-card fade-in-up animate-on-scroll" style="animation-delay: 0.3s;" onclick="window.location.href='service.php'">
                        <div class="home-service-card-top">
                            <div class="home-service-icon-box"><i class="fa-solid fa-address-card"></i></div>
                            <div class="home-service-badges">
                                <span class="service-pill fee"><i class="fa-solid fa-peso-sign"></i> Free</span>
                                <span class="service-pill time"><i class="fa-regular fa-clock"></i> Same Day</span>
                            </div>
                        </div>
                        <h3 class="home-service-name">Certificate of Residency</h3>
                        <p class="home-service-desc">Formal verification confirming continuous bonafide residency within Barangay Tabon.</p>
                        <div class="home-service-card-footer">
                            <a href="service.php" class="btn-card-action" onclick="event.stopPropagation();"><span>Request Document</span> <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                    </div>
                    <div class="home-service-card fade-in-up animate-on-scroll" style="animation-delay: 0.4s;" onclick="window.location.href='service.php'">
                        <div class="home-service-card-top">
                            <div class="home-service-icon-box"><i class="fa-solid fa-briefcase"></i></div>
                            <div class="home-service-badges">
                                <span class="service-pill fee"><i class="fa-solid fa-peso-sign"></i> ₱100.00</span>
                                <span class="service-pill time"><i class="fa-regular fa-clock"></i> 1-2 Days</span>
                            </div>
                        </div>
                        <h3 class="home-service-name">Barangay Business Clearance</h3>
                        <p class="home-service-desc">Permit clearance mandatory for commercial enterprises and local stores operating in Tabon.</p>
                        <div class="home-service-card-footer">
                            <a href="service.php" class="btn-card-action" onclick="event.stopPropagation();"><span>Apply Clearance</span> <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Latest Announcements Section -->
        <div class="announcements-wrapper" id="announcements">
            <h2 class="announcements-title fade-in-up animate-on-scroll" style="animation-delay: 0.1s;">Latest Announcements</h2>
            <div class="announcements-divider fade-in-up animate-on-scroll" style="animation-delay: 0.2s;"></div>
            <p class="announcements-subtitle fade-in-up animate-on-scroll" style="animation-delay: 0.3s;">
                Keep track of upcoming events, meetings, and important notices in our barangay. 
                <a href="announcement.php" style="color:var(--secondary-black); font-weight:700; text-decoration:underline; margin-left:6px;">View All Announcements &rarr;</a>
            </p>
            
            <div class="announcements-grid">
                <?php if (!empty($home_announcements)): ?>
                    <?php 
                    $delay = 0.4;
                    foreach ($home_announcements as $ha): 
                        $hdate = get_home_announcement_date($ha['when_date'] ?? '', $ha['created_at'] ?? '');
                        $annId = (int)$ha['id'];
                    ?>
                        <div class="announcement-card fade-in-up animate-on-scroll" 
                             style="animation-delay: <?= $delay ?>s; cursor: pointer;"
                             onclick="window.location.href='announcement.php?id=<?= $annId ?>#announcement-<?= $annId ?>'">
                            <div class="announcement-date">
                                <span class="month"><?= htmlspecialchars($hdate['month']) ?></span>
                                <span class="day"><?= htmlspecialchars($hdate['day']) ?></span>
                            </div>
                            <div class="announcement-details">
                                <h3 class="announcement-card-title"><?= htmlspecialchars($ha['title']) ?></h3>
                                <?php if (!empty($ha['what'])): ?>
                                    <p><strong>What:</strong> <?= htmlspecialchars($ha['what']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($ha['when_date'])): ?>
                                    <p><strong>When:</strong> <?= htmlspecialchars($ha['when_date']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($ha['where_place'])): ?>
                                    <p><strong>Where:</strong> <?= htmlspecialchars($ha['where_place']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($ha['who'])): ?>
                                    <p><strong>Who:</strong> <?= htmlspecialchars($ha['who']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($ha['why'])): ?>
                                    <p><strong>Why:</strong> <?= htmlspecialchars($ha['why']) ?></p>
                                <?php endif; ?>
                                <div class="announcement-card-action">
                                    <a href="announcement.php?id=<?= $annId ?>#announcement-<?= $annId ?>" class="btn-announcement-link" onclick="event.stopPropagation();">
                                        <span>Read Announcement</span> <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php 
                        $delay += 0.1;
                    endforeach; 
                    ?>
                <?php else: ?>
                    <!-- Announcement Card 1 -->
                    <div class="announcement-card fade-in-up animate-on-scroll" style="animation-delay: 0.4s; cursor: pointer;" onclick="window.location.href='announcement.php'">
                        <div class="announcement-date">
                            <span class="month">OCT</span>
                            <span class="day">15</span>
                        </div>
                        <div class="announcement-details">
                            <h3 class="announcement-card-title">General Assembly Meeting</h3>
                            <p><strong>What:</strong> General Assembly Meeting</p>
                            <p><strong>When:</strong> 9:00 AM, October 15, 2026</p>
                            <p><strong>Where:</strong> Barangay Covered Gymnasium</p>
                            <p><strong>Who:</strong> All Barangay Residents</p>
                            <p><strong>Why:</strong> Discuss infrastructure projects & budgets.</p>
                            <div class="announcement-card-action">
                                <a href="announcement.php" class="btn-announcement-link" onclick="event.stopPropagation();">
                                    <span>Read Announcement</span> <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Announcement Card 2 -->
                    <div class="announcement-card fade-in-up animate-on-scroll" style="animation-delay: 0.5s; cursor: pointer;" onclick="window.location.href='announcement.php'">
                        <div class="announcement-date">
                            <span class="month">OCT</span>
                            <span class="day">22</span>
                        </div>
                        <div class="announcement-details">
                            <h3 class="announcement-card-title">Free Medical & Dental Mission</h3>
                            <p><strong>What:</strong> Free Medical & Dental Mission</p>
                            <p><strong>When:</strong> 8:00 AM - 3:00 PM, October 22, 2026</p>
                            <p><strong>Where:</strong> Barangay Tabon Health Center</p>
                            <p><strong>Who:</strong> Senior Citizens and Children</p>
                            <p><strong>Why:</strong> Offer free checkups and medicines.</p>
                            <div class="announcement-card-action">
                                <a href="announcement.php" class="btn-announcement-link" onclick="event.stopPropagation();">
                                    <span>Read Announcement</span> <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Announcement Card 3 -->
                    <div class="announcement-card fade-in-up animate-on-scroll" style="animation-delay: 0.6s; cursor: pointer;" onclick="window.location.href='announcement.php'">
                        <div class="announcement-date">
                            <span class="month">NOV</span>
                            <span class="day">01</span>
                        </div>
                        <div class="announcement-details">
                            <h3 class="announcement-card-title">Undas 2026 Traffic Advisory</h3>
                            <p><strong>What:</strong> Traffic & Security Advisory</p>
                            <p><strong>When:</strong> All Day, November 01, 2026</p>
                            <p><strong>Where:</strong> Main Barangay Access Road & Cemetery Perimeter</p>
                            <p><strong>Who:</strong> All Motorists and Residents</p>
                            <p><strong>Why:</strong> Road closures and rerouting for peaceful Undas.</p>
                            <div class="announcement-card-action">
                                <a href="announcement.php" class="btn-announcement-link" onclick="event.stopPropagation();">
                                    <span>Read Announcement</span> <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Spot Map Section -->
        <div class="spot-map-wrapper">
            <div class="spot-map-header">
                <h2 class="spot-map-main-title fade-in-up animate-on-scroll">Spot Map</h2>
                <p class="spot-map-subtitle fade-in-up animate-on-scroll delay-1">Geographical overview and key locations in Barangay Tabon</p>
            </div>
            
            <div class="spot-map-container fade-in-up animate-on-scroll delay-2">
                <div class="spot-map-info">
                    <div class="spot-map-tag"><i class="fa-solid fa-location-dot"></i> EXPLORE</div>
                    <h3 class="spot-map-title">Barangay Spot Map</h3>
                    <p class="spot-map-desc">Navigate our community with ease. View important landmarks, territorial boundaries, purok zones, and key public facilities throughout Barangay Tabon.</p>
                    <a href="spotmap.php" class="btn-view-map">View Full Map <i class="fa-solid fa-map"></i></a>
                </div>
                <div class="spot-map-image-container">
                    <img src="Images/ChatGPT Image Oct 1, 2026, 07_50_28 AM.png" alt="Barangay Tabon Spot Map" class="spot-map-image">
                </div>
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
                        <li><a href="about.php">About</a></li>
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

    <!-- Preloader Script -->
    <script src="js/preloader.js"></script>
    <!-- Lenis Smooth Scroll -->
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
    <script src="js/main.js?v=<?= time() ?>"></script>
</body>
</html>

