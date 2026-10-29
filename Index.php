<?php
require_once 'db_connect.php';
date_default_timezone_set('Asia/Manila');

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
    <!-- The Art of Detail (Service Section) Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
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

    <!-- Hero Section with Background Video -->
    <div class="hero-wrapper">
        <section class="hero-section">
            <!-- Background Video with Dark Overlay -->
            <div class="hero-video-bg">
                <video id="heroBgVideo" autoplay muted loop playsinline poster="Images/Tabon%20Barangay%20hall.jpg" onloadedmetadata="this.playbackRate = 0.65;">
                    <source src="Video/Homepage.mp4" type="video/mp4">
                </video>
                <div class="hero-video-overlay"></div>
            </div>
            <div class="hero-content">
                <h1 class="hero-title fade-in-left">Building a Stronger<br>and United Community</h1>
                <!-- Live Official Timestamp Widget -->
                <div class="hero-timestamp fade-in-left delay-1" id="heroTimestampBox">
                    <span class="hero-timestamp-badge">
                        <i class="fa-solid fa-clock"></i> Philippine Standard Time
                    </span>
                    <div class="hero-timestamp-display" id="heroLiveClock">
                        <span class="hero-live-date"><?= date('l, F d, Y') ?></span>
                        <span class="hero-live-sep">&bull;</span>
                        <span class="hero-live-time"><?= date('h:i:s A') ?></span>
                    </div>
                </div>
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

    <!-- Services Section (Expanded) -->
        <?php 
        $art_data = file_exists(__DIR__ . '/includes/data.php') ? require __DIR__ . '/includes/data.php' : [];
        if (!empty($art_data)): 
        ?>
        <section class="services-wrapper" id="services-section">
            <h2 class="announcements-title fade-in-up animate-on-scroll" style="animation-delay: 0.1s;">Our Services</h2>
            <div class="announcements-divider fade-in-up animate-on-scroll" style="animation-delay: 0.2s;"></div>
            <p class="announcements-subtitle fade-in-up animate-on-scroll" style="animation-delay: 0.3s;">
                Explore the frontline services, certificates, and assistance offered in our barangay.
            </p>
            <section class="art-of-detail-section" aria-label="Services">
            <div class="art-container">
                <!-- Left: Content Column & Accordion -->
                <div class="art-content-col">
                    <span class="art-eyebrow"><?= htmlspecialchars($art_data['eyebrow'] ?? '') ?></span>
                    <h2 class="art-headline">
                        <?= htmlspecialchars($art_data['headline']['line1'] ?? 'Prompt, honest') ?><br>
                        <?= htmlspecialchars($art_data['headline']['accent_prefix'] ?? 'public ') ?><em><?= htmlspecialchars($art_data['headline']['accent_word'] ?? 'service') ?></em><?= htmlspecialchars($art_data['headline']['accent_suffix'] ?? '.') ?>
                    </h2>
                    <p class="art-intro-p">
                        <?= htmlspecialchars($art_data['intro'] ?? '') ?>
                    </p>

                    <!-- Services Accordion -->
                    <div class="art-accordion" role="region" aria-label="Services Accordion">
                        <?php foreach ($art_data['items'] as $index => $item): ?>
                            <?php 
                                $isFirst = ($index === 0);
                                $panelId = 'art-panel-' . htmlspecialchars($item['number']);
                                $btnId = 'art-btn-' . htmlspecialchars($item['number']);
                            ?>
                            <div class="art-accordion-item <?= $isFirst ? 'is-active' : '' ?>" data-item-index="<?= $index ?>">
                                <button type="button" 
                                        id="<?= $btnId ?>" 
                                        class="art-accordion-trigger" 
                                        aria-expanded="<?= $isFirst ? 'true' : 'false' ?>" 
                                        aria-controls="<?= $panelId ?>" 
                                        data-caption="<?= htmlspecialchars($item['caption']) ?>">
                                    <div class="art-trigger-start">
                                        <span class="art-item-number"><?= htmlspecialchars($item['number']) ?></span>
                                        <span class="art-item-title"><?= htmlspecialchars($item['title']) ?></span>
                                    </div>
                                    <svg class="art-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <polyline points="6 9 12 15 18 9"></polyline>
                                    </svg>
                                </button>
                                <div id="<?= $panelId ?>" 
                                     class="art-accordion-body" 
                                     role="region" 
                                     aria-labelledby="<?= $btnId ?>">
                                    <div class="art-accordion-inner">
                                        <p><?= htmlspecialchars($item['description']) ?></p>
                                        <div class="art-service-meta">
                                            <div class="art-meta-tags">
                                                <?php if (!empty($item['fee'])): ?>
                                                    <span class="art-pill-badge fee"><i class="fa-solid fa-coins"></i> <?= htmlspecialchars($item['fee']) ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($item['processing_time'])): ?>
                                                    <span class="art-pill-badge time"><i class="fa-regular fa-clock"></i> <?= htmlspecialchars($item['processing_time']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <a href="service.php" class="art-view-req-btn">
                                                <span>View Requirements</span> <i class="fa-solid fa-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Right: Media Column with Tall Image & Gradient Overlay -->
                <div class="art-media-col">
                    <div class="art-image-frame">
                        <div class="art-slides-container">
                            <?php foreach ($art_data['items'] as $index => $item): ?>
                                <img src="<?= htmlspecialchars($item['image']) ?>" 
                                     alt="<?= htmlspecialchars($item['alt']) ?>" 
                                     class="art-image-slide <?= $index === 0 ? 'is-active' : '' ?>" 
                                     data-slide-index="<?= $index ?>" 
                                     loading="<?= $index === 0 ? 'eager' : 'lazy' ?>" 
                                     draggable="false" 
                                     oncontextmenu="return false;">
                            <?php endforeach; ?>
                        </div>

                        <!-- Clean Status Bar at bottom, not blocking document -->
                        <div class="art-image-statusbar">
                            <span class="art-caption-left"><?= htmlspecialchars($art_data['items'][0]['caption'] ?? '') ?></span>
                            <span class="art-caption-badge"><i class="fa-solid fa-shield-halved"></i> VIEW ONLY</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        </section>
    <?php endif; ?>

    <!-- Main Content Area -->
    <main class="main-content" id="main-content">

        <!-- Latest Announcements Section -->
        <div class="announcements-wrapper" id="announcements">
            <h2 class="announcements-title fade-in-up animate-on-scroll" style="animation-delay: 0.1s;">Latest Announcements</h2>
            <div class="announcements-divider fade-in-up animate-on-scroll" style="animation-delay: 0.2s;"></div>
            <p class="announcements-subtitle fade-in-up animate-on-scroll" style="animation-delay: 0.3s;">
                Keep track of upcoming events, meetings, and important notices in our barangay.

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
        
            <!-- View All Announcements Button Action -->
            <div class="announcements-bottom-action fade-in-up animate-on-scroll" style="animation-delay: 0.5s;">
                <a href="announcement.php" class="btn-view-all-announcements">
                    <span>View All Announcements</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
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
    <!-- The Art of Detail Interactive Script -->
    <script src="assets/js/main.js?v=<?= time() ?>"></script>
    <!-- Normal speed controller for Hero Background Video -->
    <script>
    (function() {
        var v = document.getElementById('heroBgVideo');
        if (v) {
            var setNormalSpeed = function() { v.playbackRate = 0.65; };
            setNormalSpeed();
            v.addEventListener('play', setNormalSpeed);
            v.addEventListener('loadeddata', setNormalSpeed);
            v.addEventListener('canplay', setNormalSpeed);
        }
    })();
    </script>
    <!-- Live Clock Script for Philippine Standard Time -->
    <script>
    (function() {
        function updateHeroLiveClock() {
            var clockEl = document.getElementById('heroLiveClock');
            if (!clockEl) return;
            var now = new Date();
            try {
                var dateStr = now.toLocaleDateString('en-US', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: '2-digit',
                    timeZone: 'Asia/Manila'
                });
                var timeStr = now.toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: true,
                    timeZone: 'Asia/Manila'
                });
                clockEl.innerHTML = '<span class="hero-live-date">' + dateStr + '</span>' +
                                   '<span class="hero-live-sep">&bull;</span>' +
                                   '<span class="hero-live-time">' + timeStr + '</span>';
            } catch (err) {}
        }
        setInterval(updateHeroLiveClock, 1000);
        updateHeroLiveClock();
    })();
    </script>
</body>
</html>

