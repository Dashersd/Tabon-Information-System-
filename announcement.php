<?php
// announcement.php - Barangay Tabon Announcements Landing Page
require_once 'db_connect.php';

function get_announcement_date_parts($when_date, $created_at) {
    $ts = !empty($when_date) ? strtotime($when_date) : null;
    if (!$ts || $ts <= 0) {
        $ts = !empty($created_at) ? strtotime($created_at) : time();
    }
    return [
        'pill'     => strtoupper(date('M d', $ts)),
        'calendar' => date('M d, Y', $ts),
        'month'    => strtoupper(date('M', $ts)),
        'day'      => date('d', $ts)
    ];
}

// Fetch published announcements
$announcements = [];
$res = $conn->query("SELECT * FROM announcements WHERE status = 'published' ORDER BY created_at DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $announcements[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements & Advisories - Barangay Tabon</title>
    
    <!-- FontAwesome & Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600;1,700&display=swap" rel="stylesheet">
    
    <!-- Preloader & Stylesheets -->
    <link rel="stylesheet" href="css/preloader.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/announcement.css?v=<?php echo time(); ?>">
</head>
<body>

    <!-- Mobile Legends Inspired Animated Preloader -->
    <?php if (file_exists('preloader.php')) include 'preloader.php'; ?>

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
            <a href="index.php" class="nav-item">
                <span>Home</span>
            </a>
            <div class="nav-item dropdown">
                <a href="#" class="dropdown-toggle" style="text-decoration:none;">
                    <span>About Us <i class="fa-solid fa-chevron-down" style="font-size:10px; margin-left:3px;"></i></span>
                </a>
                <div class="dropdown-menu">
                    <a href="history.php" class="dropdown-item">History</a>
                    <a href="about.php#mission" class="dropdown-item">Mission</a>
                    <a href="about.php#vision" class="dropdown-item">Vision</a>
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
            <a href="announcement.php" class="nav-item active">
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

    <!-- Main Content Container -->
    <main class="announcement-page-wrapper">
        
        <!-- Hero Section -->
        <section class="announcement-hero">
            <span class="announcement-hero-pill">
                <i class="fa-solid fa-bullhorn"></i> Official Bulletins & News
            </span>
            <h1 class="announcement-hero-title">Barangay Announcements</h1>
            <p class="announcement-hero-sub">
                Stay updated with community assemblies, educational seminars, free medical missions, sports activities, and official notices from Barangay Tabon.
            </p>
        </section>

        <!-- Main Announcements Feed Container -->
        <div class="announcement-layout">
            
            <!-- Left: Announcements Feed -->
            <div class="announcement-list" id="announcementList">
                
                <?php if (empty($announcements)): ?>
                    <div style="text-align: center; padding: 60px 20px; background: #ffffff; border-radius: 20px; border: 1.5px dashed #cbd5e1; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
                        <i class="fa-solid fa-bullhorn" style="font-size: 3rem; color: #94a3b8; margin-bottom: 15px; display: block;"></i>
                        <h3 style="margin: 0 0 8px 0; color: #334155; font-size: 1.3rem;">No Active Announcements</h3>
                        <p style="margin: 0; color: #64748b;">Important updates and community notices will appear here as soon as they are announced by Barangay Tabon.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($announcements as $ann): ?>
                        <?php 
                        $dt = get_announcement_date_parts($ann['when_date'], $ann['created_at']);
                        
                        // Infer category for filter chips
                        $cat = 'all';
                        $haystack = strtolower(($ann['title'] ?? '') . ' ' . ($ann['what'] ?? '') . ' ' . ($ann['who'] ?? ''));
                        if (strpos($haystack, 'health') !== false || strpos($haystack, 'medical') !== false || strpos($haystack, 'dental') !== false) {
                            $cat = 'health';
                        } elseif (strpos($haystack, 'youth') !== false || strpos($haystack, 'sk') !== false || strpos($haystack, 'sport') !== false || strpos($haystack, 'basketball') !== false) {
                            $cat = 'youth';
                        } elseif (strpos($haystack, 'seminar') !== false || strpos($haystack, 'education') !== false || strpos($haystack, 'training') !== false || strpos($haystack, 'concept') !== false) {
                            $cat = 'education';
                        } elseif (strpos($haystack, 'assembly') !== false || strpos($haystack, 'meeting') !== false || strpos($haystack, 'session') !== false) {
                            $cat = 'assembly';
                        }

                        // Determine image path
                        $imgPath = null;
                        if (!empty($ann['cover_image'])) {
                            if (file_exists('Admin/' . $ann['cover_image'])) {
                                $imgPath = 'Admin/' . $ann['cover_image'];
                            } elseif (file_exists($ann['cover_image'])) {
                                $imgPath = $ann['cover_image'];
                            } elseif (file_exists('Images/announcements/' . basename($ann['cover_image']))) {
                                $imgPath = 'Images/announcements/' . basename($ann['cover_image']);
                            }
                        }
                        ?>
                        <article class="announcement-card-modern" id="announcement-<?= (int)$ann['id'] ?>" data-category="<?= htmlspecialchars($cat) ?>">
                            <div class="card-top-meta">
                                <span class="card-pill-date"><?= htmlspecialchars($dt['pill']) ?></span>
                                <span class="card-calendar-date"><i class="fa-regular fa-calendar"></i> <?= htmlspecialchars($dt['calendar']) ?></span>
                            </div>

                            <?php if (!empty($imgPath)): ?>
                                <div class="card-poster-container">
                                    <img src="<?= htmlspecialchars($imgPath) ?>" alt="<?= htmlspecialchars($ann['title']) ?>" class="card-poster-img" onerror="this.onerror=null; this.src='Barangay Logo/Logo.png';">
                                </div>
                            <?php endif; ?>

                            <h2 class="card-title"><?= htmlspecialchars($ann['title']) ?></h2>

                            <div class="card-details-box">
                                <?php if (!empty($ann['what'])): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">What:</span>
                                        <span class="detail-value"><?= htmlspecialchars($ann['what']) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($ann['when_date'])): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">When:</span>
                                        <span class="detail-value"><?= htmlspecialchars($ann['when_date']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($ann['where_place'])): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Where:</span>
                                        <span class="detail-value"><?= htmlspecialchars($ann['where_place']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($ann['who'])): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Who:</span>
                                        <span class="detail-value"><?= htmlspecialchars($ann['who']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($ann['why'])): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Why:</span>
                                        <span class="detail-value"><?= htmlspecialchars($ann['why']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($ann['content'])): ?>
                                <div style="margin-top: 18px; padding-top: 14px; border-top: 1px dashed #e2e8f0; color: #475569; font-size: 0.96rem; line-height: 1.65;">
                                    <?= nl2br(htmlspecialchars($ann['content'])) ?>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>

        </div>

    </main>

    <!-- Footer -->
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

    <!-- Deep-link and Highlight Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        let targetId = urlParams.get('id');
        if (!targetId && window.location.hash) {
            const hashMatch = window.location.hash.match(/announcement-(\d+)/);
            if (hashMatch) {
                targetId = hashMatch[1];
            }
        }

        if (targetId) {
            const targetEl = document.getElementById('announcement-' + targetId);
            if (targetEl) {
                setTimeout(function() {
                    targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    targetEl.classList.add('announcement-highlight');
                    setTimeout(function() {
                        targetEl.classList.remove('announcement-highlight');
                    }, 4500);
                }, 350);
            }
        }
    });
    </script>

    <!-- Preloader Script -->
    <script src="js/preloader.js?v=<?php echo time(); ?>"></script>
    <script src="js/main.js?v=<?= time() ?>"></script>
</body>
</html>
