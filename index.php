<?php
require_once 'db_connect.php';
date_default_timezone_set('Asia/Manila');

// Fetch gallery photos for 3D carousel preview (limited to latest 5-6 items)
$carousel_images = [];
$all_gallery_images = [];

try {
    // 1. All Media Library photos
    $g_query = "SELECT m.*, f.name as folder_name 
                FROM gallery_media m 
                JOIN gallery_folders f ON m.folder_id = f.id 
                ORDER BY m.id DESC";
    $c_res = $conn->query($g_query);
    if ($c_res) {
        while ($row = $c_res->fetch_assoc()) {
            $checkPath = (strpos($row['file_path'], 'Admin/') === 0) ? $row['file_path'] : 'Admin/' . $row['file_path'];
            if (file_exists($checkPath)) {
                $row['web_src'] = $checkPath;
                $row['source_type'] = 'gallery';
                $row['title'] = !empty($row['caption']) ? $row['caption'] : (!empty($row['file_name']) ? pathinfo($row['file_name'], PATHINFO_FILENAME) : 'Community Highlight');
                $all_gallery_images[] = $row;

                // Preview carousel gets at most 6 items
                if (count($carousel_images) < 6) {
                    $carousel_images[] = $row;
                }
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



// Fetch dynamic Homepage Hero Background Video from barangay_information
$home_hero_video = 'Video/Homepage.mp4';
$home_hero_title = 'Building a Stronger and United Community';

try {
    $v_check = $conn->query("SELECT `hero_video_path`, `hero_title` FROM `barangay_information` WHERE `id` = 1 LIMIT 1");
    if ($v_check && $v_row = $v_check->fetch_assoc()) {
        if (!empty($v_row['hero_video_path'])) {
            $raw_v = $v_row['hero_video_path'];
            $resolved_v = (strpos($raw_v, 'uploads/') === 0) ? 'Admin/' . $raw_v : $raw_v;
            if (file_exists($resolved_v)) {
                $home_hero_video = $resolved_v;
            }
        }
        if (!empty($v_row['hero_title'])) {
            $home_hero_title = $v_row['hero_title'];
        }
    }
} catch (Exception $e) {}
$home_hero_video_cache = file_exists($home_hero_video) ? filemtime($home_hero_video) : time();

function format_hero_title($text) {
    if (empty($text)) {
        return 'Building a Stronger<br>and United Community';
    }
    $sanitized = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $sanitized = preg_replace('/&lt;br\s*\/?&gt;/i', '<br>', $sanitized);
    $sanitized = nl2br($sanitized);
    if (strpos($sanitized, '<br>') === false && trim($text) === 'Building a Stronger and United Community') {
        $sanitized = 'Building a Stronger<br>and United Community';
    }
    return $sanitized;
}

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
                <div class="brand-sub"><?= htmlspecialchars($site_slogan) ?></div>
            </div>
            <img src="Barangay Logo/lapuyanlogo.jpg" alt="Municipality of Lapuyan Logo" class="brand-logo brand-logo-secondary" onerror="this.src='https://via.placeholder.com/65';">
        </div>

        <nav>
            <!-- Mobile Drawer Close Button & Brand Header -->
            <button class="mobile-nav-close" type="button" aria-label="Close menu">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="mobile-drawer-brand">
                <div class="drawer-brand-text">
                    <div class="drawer-brand-title">Barangay Tabon</div>
                    <div class="drawer-brand-sub"><?= htmlspecialchars($site_slogan) ?></div>
                </div>
            </div>
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
        </nav>

        <!-- Mobile Menu Hamburger Button -->
        <button class="mobile-menu-toggle" id="mobileMenuToggle" type="button" aria-label="Toggle navigation menu">
            <span class="bar bar-1"></span>
            <span class="bar bar-2"></span>
            <span class="bar bar-3"></span>
        </button>
    </header>

    <!-- Hero Section with Background Video -->
    <div class="hero-wrapper">
        <section class="hero-section">
            <!-- Background Video with Dark Overlay -->
            <div class="hero-video-bg">
                <video id="heroBgVideo" autoplay muted loop playsinline poster="Images/Tabon%20Barangay%20hall.jpg" onloadedmetadata="this.playbackRate = 0.65;">
                    <source src="<?= htmlspecialchars($home_hero_video) ?>?v=<?= $home_hero_video_cache ?>" type="video/mp4">
                </video>
                <div class="hero-video-overlay"></div>
            </div>
            <div class="hero-content">
                <h1 class="hero-title fade-in-left"><?= format_hero_title($home_hero_title) ?></h1>
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
            </div>
            <div class="about-image fade-in-right animate-on-scroll delay-2">
                <img src="<?= htmlspecialchars($home_about_img) ?>?v=<?= $home_about_cache ?>" alt="About Barangay Tabon" onerror="this.src='Images/Tabon Barangay hall.jpg';">
            </div>
        </section>
    </div>

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

        <!-- Gallery View More Controls -->
        <div class="gallery-view-more-container" style="text-align: center; margin-top: 25px; margin-bottom: 35px; display: flex; align-items: center; justify-content: center;">
            <a href="gallery.php" class="btn-gallery-link" style="background: #000000; color: #ffffff; font-weight: 700; font-size: 0.92rem; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; padding: 12px 26px; border-radius: 50px; border: 1.5px solid #000000; transition: all 0.25s ease; box-shadow: 0 4px 14px rgba(0,0,0,0.2);">
                <i class="fa-solid fa-book-open" style="color: #ffffff;"></i> Go to Gallery Page &rarr;
            </a>
        </div>
    </section>

    <!-- Full Gallery Expanded Modal (Displays ALL images, including those uploaded from the Map page) -->
    <div id="fullGalleryModal" class="full-gallery-modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15,23,42,0.92); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 99999; overflow-y: auto; padding: 30px 20px; box-sizing: border-box;">
        <div class="full-gallery-modal-content" style="max-width: 1200px; margin: 0 auto; background: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 25px 60px rgba(0,0,0,0.5); display: flex; flex-direction: column;">
            <!-- Modal Header -->
            <div style="padding: 22px 30px; background: #223c3b; color: #ffffff; display: flex; align-items: center; justify-content: space-between; border-bottom: 3px solid #f59e0b;">
                <div>
                    <h2 style="margin: 0; font-size: 1.55rem; font-weight: 800; display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-images" style="color: #f59e0b;"></i> Complete Community Photo Gallery
                    </h2>
                    <p style="margin: 4px 0 0 0; color: #cbd5e1; font-size: 0.88rem;">All official community media, event highlights, and spot map location photos.</p>
                </div>
                <button type="button" id="btnCloseFullGallery" style="background: none; border: none; color: #ffffff; font-size: 2.2rem; cursor: pointer; line-height: 1; padding: 2px 10px; border-radius: 8px; transition: color 0.2s;">&times;</button>
            </div>

            <!-- Category Filter Tabs -->
            <div style="padding: 14px 30px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                <button type="button" class="gallery-modal-filter-btn active" data-filter="all">All Photos (<span id="modalTotalCount"><?= count($all_gallery_images) ?></span>)</button>
            </div>

            <!-- Full Gallery Items Grid -->
            <div id="fullGalleryGrid" style="padding: 30px; display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 22px; max-height: calc(85vh - 160px); overflow-y: auto;">
                <?php if (!empty($all_gallery_images)): ?>
                    <?php foreach ($all_gallery_images as $item): ?>
                        <div class="full-gallery-card" 
                             data-category="<?= htmlspecialchars($item['source_type']) ?>" 
                             data-img="<?= htmlspecialchars($item['web_src']) ?>" 
                             data-title="<?= htmlspecialchars($item['title']) ?>" 
                             data-folder="<?= htmlspecialchars($item['folder_name']) ?>" 
                             data-date="<?= htmlspecialchars(!empty($item['created_at']) ? date('M d, Y • h:i A', strtotime($item['created_at'])) : 'Community Highlight') ?>" 
                             style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; cursor: pointer; transition: all 0.25s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.05); display: flex; flex-direction: column;">
                            <div style="position: relative; width: 100%; height: 185px; overflow: hidden; background: #0f172a;">
                                <img src="<?= htmlspecialchars($item['web_src']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;">
                                <div style="position: absolute; top: 10px; left: 10px; background: rgba(34,60,59,0.9); color: #ffffff; font-size: 0.72rem; font-weight: 700; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.4px;">
                                    <?= htmlspecialchars($item['folder_name']) ?>
                                </div>
                            </div>
                            <div style="padding: 14px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                                <div style="font-weight: 700; font-size: 0.92rem; color: #1e293b; margin-bottom: 6px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= htmlspecialchars($item['title']) ?>
                                </div>
                                <div style="font-size: 0.78rem; color: #64748b; display: flex; align-items: center; gap: 6px;">
                                    <i class="fa-regular fa-clock" style="color: #f59e0b;"></i>
                                    <?= htmlspecialchars(!empty($item['created_at']) ? date('M d, Y', strtotime($item['created_at'])) : 'Recent') ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: #64748b;">
                        <i class="fa-solid fa-images" style="font-size: 2.5rem; color: #cbd5e1; margin-bottom: 12px; display: block;"></i>
                        No gallery photos published yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

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
                <h4>Navigation</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="service.php">Services</a></li>
                    <li><a href="announcement.php">Announcements</a></li>
                    <li><a href="spotmap.php">Spot Map</a></li>
                    <li><a href="gallery.php">Gallery</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                </ul>
            </div>
            
            <div class="footer-links">
                <h4>About Us</h4>
                <ul>
                    <li><a href="history.php">History</a></li>
                    <li><a href="mission.php">Mission</a></li>
                    <li><a href="vision.php">Vision</a></li>
                    <li><a href="officials.php">Barangay Officials</a></li>
                    <li><a href="skofficials.php">SK Officials</a></li>
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
    <!-- Mobile Navigation Drawer Backdrop -->
    <div class="mobile-nav-backdrop"></div>
</body>
</html>

