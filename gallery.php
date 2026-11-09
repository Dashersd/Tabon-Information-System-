<?php
// gallery.php
require_once 'db_connect.php';

// Fetch Folders with photo counts
$folders = [];
$folders_query = "SELECT f.*, COUNT(m.id) as media_count 
                  FROM gallery_folders f 
                  LEFT JOIN gallery_media m ON f.id = m.folder_id 
                  GROUP BY f.id 
                  ORDER BY f.name ASC";
$folders_res = $conn->query($folders_query);
if ($folders_res) {
    while ($row = $folders_res->fetch_assoc()) {
        $folders[] = $row;
    }
}

// Fetch all media items
$media_items = [];
$media_query = "SELECT m.*, f.name as folder_name 
                FROM gallery_media m 
                JOIN gallery_folders f ON m.folder_id = f.id 
                ORDER BY m.id DESC";
$media_res = $conn->query($media_query);
if ($media_res) {
    while ($m = $media_res->fetch_assoc()) {
        $checkPath = (strpos($m['file_path'], 'Admin/') === 0) ? $m['file_path'] : 'Admin/' . $m['file_path'];
        if (file_exists($checkPath)) {
            $m['web_path'] = $checkPath;
            $media_items[] = $m;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Tabon - Photo Gallery</title>
    
    <!-- FontAwesome CSS CDN & Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600;1,700&display=swap" rel="stylesheet">
    
    <!-- Preloader & External Stylesheets -->
    <link rel="stylesheet" href="css/preloader.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/gallery.css?v=<?php echo time(); ?>">
</head>
<body class="gallery-page">

    <!-- Mobile Legends Inspired Animated Preloader -->
    <?php if (file_exists('preloader.php')) include 'preloader.php'; ?>

    <!-- Header / Navbar -->
    <header>
        <div class="brand-container">
            <img src="Barangay Logo/Logo.png" alt="Barangay Tabon Logo" class="brand-logo" style="width: 65px; height: 65px; object-fit: cover; border-radius: 50%;" onerror="this.src='https://via.placeholder.com/65';">
            <div>
                <div class="brand-title">Barangay Tabon</div>
                <div class="brand-sub"><?= htmlspecialchars($site_slogan) ?></div>
            </div>
            <img src="Barangay Logo/lapuyanlogo.jpg" alt="Municipality of Lapuyan Logo" class="brand-logo brand-logo-secondary" style="width: 65px; height: 65px; object-fit: contain; border-radius: 50%;" onerror="this.src='https://via.placeholder.com/65';">
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
            <a href="index.php" class="nav-item">
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
            <a href="gallery.php" class="nav-item active">
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

    <!-- Main Content -->
    <main class="gallery-main-wrapper">
        <div class="title-section">
            <h1>Community Media Gallery</h1>
            <p>Explore the moments, gatherings, community programs, and activities across Barangay Tabon.</p>
        </div>

        <!-- Category Filter Tabs -->
        <div class="filter-tabs-wrapper">
            <button type="button" class="filter-chip-btn active" onclick="filterGallery('all', this)">
                <i class="bi bi-images"></i> All Photos (<span id="galleryAllCount"><?= count($media_items) ?></span>)
            </button>
            <?php foreach ($folders as $folder): ?>
                <button type="button" class="filter-chip-btn" onclick="filterGallery('folder-<?= $folder['id'] ?>', this)">
                    <i class="bi bi-folder2"></i> <?= htmlspecialchars($folder['name']) ?> (<?= $folder['media_count'] ?>)
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Photos Grid -->
        <div class="public-grid" id="publicGalleryGrid">
            <?php if (count($media_items) > 0): ?>
                <?php foreach ($media_items as $media): 
                    $m_title = !empty($media['caption']) ? $media['caption'] : (!empty($media['file_name']) ? pathinfo($media['file_name'], PATHINFO_FILENAME) : 'Gallery Photo');
                    $m_folder = !empty($media['folder_name']) ? $media['folder_name'] : 'General';
                    $m_date = !empty($media['created_at']) ? date('M d, Y • h:i A', strtotime($media['created_at'])) : date('M d, Y • h:i A');
                ?>
                    <div class="grid-item folder-<?= $media['folder_id'] ?> <?= !empty($media['is_map']) ? 'is-map-item' : '' ?>" 
                         data-img="<?= htmlspecialchars($media['web_path']) ?>" 
                         data-title="<?= htmlspecialchars($m_title) ?>" 
                         data-folder="<?= htmlspecialchars($m_folder) ?>" 
                         data-date="<?= htmlspecialchars($m_date) ?>" 
                         onclick="openItemLightbox(this)">
                        <div class="grid-thumb-box">
                            <img src="<?= htmlspecialchars($media['web_path']) ?>" alt="<?= htmlspecialchars($m_title) ?>" loading="lazy">
                            <div class="card-overlay">
                                <div class="card-overlay-btn" title="View Fullscreen">
                                    <i class="fa-solid fa-expand"></i>
                                </div>
                                <div class="card-overlay-title"><?= htmlspecialchars($m_title) ?></div>
                                <div class="card-overlay-folder">
                                    <i class="fa-solid fa-folder"></i> <?= strtoupper(htmlspecialchars($m_folder)) ?>
                                </div>
                                <div class="card-overlay-date">
                                    <i class="fa-regular fa-clock"></i> <?= htmlspecialchars($m_date) ?>
                                </div>
                            </div>
                        </div>
                        <div class="item-info">
                            <span class="item-tag">
                                <?php if (!empty($media['is_map'])): ?>
                                    <i class="fa-solid fa-location-dot" style="color:#10b981; margin-right:3px;"></i>
                                <?php else: ?>
                                    <i class="bi bi-folder-fill" style="color:#d97706; margin-right:3px;"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars($m_folder) ?>
                            </span>
                            <div class="item-caption" title="<?= htmlspecialchars($m_title) ?>">
                                <?= htmlspecialchars($m_title) ?>
                            </div>
                            <div class="item-date">
                                <i class="fa-regular fa-clock" style="margin-right: 4px; color:#f59e0b;"></i> <?= htmlspecialchars($m_date) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-gallery-state">
                    <i class="bi bi-images"></i>
                    <h3>No photos published yet</h3>
                    <p>Check back soon for new updates, event highlights, and community photo albums.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Fullscreen Lightbox Modal -->
    <div id="lightboxModal" class="lightbox-modal">
        <button class="lightbox-nav-btn lightbox-prev" id="galleryLbPrev" aria-label="Previous image" title="Previous image">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
        <button class="lightbox-nav-btn lightbox-next" id="galleryLbNext" aria-label="Next image" title="Next image">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
        <span class="lightbox-close" id="galleryLbClose" aria-label="Close lightbox">&times;</span>
        <div class="lightbox-container" onclick="event.stopPropagation()">
            <img src="" alt="Fullscreen preview" id="lightboxImg" class="lightbox-img">
            <div id="lightboxCaption" class="lightbox-caption"></div>
            <div id="lightboxCounter" class="lightbox-counter" style="color: #cbd5e1; font-size: 0.85rem; margin-top: 8px; font-weight: 600; font-family: 'Montserrat', sans-serif; text-align: center;"></div>
        </div>
    </div>

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

    <!-- Interactive Scripts -->
    <script>
    let activeCategory = 'all';
    let activeLightboxList = [];
    let activeLightboxIndex = 0;

    const lightbox = document.getElementById('lightboxModal');
    const lightboxImg = document.getElementById('lightboxImg');
    const lightboxCaption = document.getElementById('lightboxCaption');
    const lightboxCounter = document.getElementById('lightboxCounter');
    const lightboxClose = document.getElementById('galleryLbClose');
    const lightboxPrev = document.getElementById('galleryLbPrev');
    const lightboxNext = document.getElementById('galleryLbNext');

    function filterGallery(category, btn) {
        activeCategory = category;
        document.querySelectorAll('.filter-chip-btn').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');

        const items = document.querySelectorAll('.public-grid .grid-item');
        let visibleCount = 0;
        items.forEach(item => {
            if (category === 'all' || item.classList.contains(category)) {
                item.style.display = 'flex';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });
    }

    function renderLightboxContent() {
        if (!activeLightboxList.length) return;
        const item = activeLightboxList[activeLightboxIndex];
        if (!item) return;

        lightboxImg.src = item.img;
        lightboxImg.alt = item.title || 'Preview';

        let captionHtml = `<div style="font-size:1.25rem; font-weight:700; color:#ffffff; margin-bottom:8px; font-family:'Montserrat',sans-serif; text-shadow:0 2px 5px rgba(0,0,0,0.85);">${item.title}</div>`;
        if (item.folder || item.date) {
            captionHtml += `<div style="display:inline-flex; align-items:center; gap:10px; justify-content:center; flex-wrap:wrap;">`;
            if (item.folder) {
                captionHtml += `<span style="display:inline-flex; align-items:center; gap:5px; padding:4px 12px; border-radius:50px; border:1.5px solid #f59e0b; background:rgba(245,158,11,0.18); color:#fbbf24; font-size:0.75rem; font-weight:800; letter-spacing:0.5px; text-transform:uppercase;"><i class="fa-solid fa-folder"></i> ${item.folder}</span>`;
            }
            if (item.date) {
                captionHtml += `<span style="display:inline-flex; align-items:center; gap:6px; padding:5px 12px; border-radius:50px; background:rgba(15,23,42,0.88); border:1px solid rgba(255,255,255,0.15); color:#f8fafc; font-size:0.75rem; font-weight:600;"><i class="fa-regular fa-clock" style="color:#fbbf24;"></i> ${item.date}</span>`;
            }
            captionHtml += `</div>`;
        }
        lightboxCaption.innerHTML = captionHtml;

        if (lightboxCounter) {
            lightboxCounter.textContent = `${activeLightboxIndex + 1} of ${activeLightboxList.length}`;
        }
    }

    function openItemLightbox(el) {
        // Collect all currently visible grid items
        const visibleItems = Array.from(document.querySelectorAll('.public-grid .grid-item')).filter(it => it.style.display !== 'none');
        activeLightboxList = visibleItems.map(it => ({
            img: it.getAttribute('data-img') || (it.querySelector('img') ? it.querySelector('img').src : ''),
            title: it.getAttribute('data-title') || '',
            folder: it.getAttribute('data-folder') || '',
            date: it.getAttribute('data-date') || ''
        }));

        activeLightboxIndex = visibleItems.indexOf(el);
        if (activeLightboxIndex < 0) activeLightboxIndex = 0;

        renderLightboxContent();
        lightbox.style.display = 'flex';
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function stepGalleryLightbox(dir) {
        if (!activeLightboxList.length) return;
        activeLightboxIndex = (activeLightboxIndex + dir + activeLightboxList.length) % activeLightboxList.length;
        renderLightboxContent();
    }

    function closeLightbox() {
        if (lightbox) {
            lightbox.style.display = 'none';
            lightbox.classList.remove('active');
        }
        document.body.style.overflow = '';
    }

    if (lightboxPrev) {
        lightboxPrev.addEventListener('click', (e) => {
            e.stopPropagation();
            stepGalleryLightbox(-1);
        });
    }
    if (lightboxNext) {
        lightboxNext.addEventListener('click', (e) => {
            e.stopPropagation();
            stepGalleryLightbox(1);
        });
    }
    if (lightboxClose) {
        lightboxClose.addEventListener('click', (e) => {
            e.stopPropagation();
            closeLightbox();
        });
    }
    if (lightbox) {
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) closeLightbox();
        });
    }

    document.addEventListener('keydown', function(e) {
        if (!lightbox || !lightbox.classList.contains('active')) return;
        if (e.key === 'ArrowLeft') stepGalleryLightbox(-1);
        else if (e.key === 'ArrowRight') stepGalleryLightbox(1);
        else if (e.key === 'Escape') closeLightbox();
    });

    // Sync any custom dropped pins from localStorage into the gallery
    document.addEventListener('DOMContentLoaded', function() {
        try {
            const rawCustom = localStorage.getItem('brgy_tabon_custom_pins');
            if (!rawCustom) return;
            const pins = JSON.parse(rawCustom);
            if (!Array.isArray(pins) || !pins.length) return;

            const grid = document.getElementById('publicGalleryGrid');
            if (!grid) return;

            let addedCount = 0;
            pins.forEach((p, idx) => {
                if (!p || !p.image_path) return;
                const dateStr = p.created_at ? new Date(p.created_at).toLocaleDateString() : 'Dropped Pin';
                const div = document.createElement('div');
                div.className = 'grid-item folder-map is-map-item custom-pin-item';
                div.setAttribute('data-img', p.image_path);
                div.setAttribute('data-title', p.title || `Custom Pin #${idx + 1}`);
                div.setAttribute('data-folder', 'Spot Map Pin');
                div.setAttribute('data-date', dateStr);
                div.onclick = function() { openItemLightbox(this); };
                div.innerHTML = `
                    <div class="grid-thumb-box">
                        <img src="${p.image_path}" alt="${p.title || 'Custom Pin'}" loading="lazy">
                        <div class="card-overlay">
                            <div class="card-overlay-btn" title="View Fullscreen"><i class="fa-solid fa-expand"></i></div>
                            <div class="card-overlay-title">${p.title || 'Custom Pin'}</div>
                            <div class="card-overlay-folder"><i class="fa-solid fa-folder"></i> SPOT MAP PIN</div>
                            <div class="card-overlay-date"><i class="fa-regular fa-clock"></i> ${dateStr}</div>
                        </div>
                    </div>
                    <div class="item-info">
                        <span class="item-tag"><i class="fa-solid fa-location-dot" style="color:#10b981; margin-right:3px;"></i> Spot Map Pin</span>
                        <div class="item-caption" title="${p.title || 'Custom Pin'}">${p.title || 'Custom Pin'}</div>
                        <div class="item-date"><i class="fa-regular fa-clock" style="margin-right: 4px; color:#f59e0b;"></i> ${dateStr}</div>
                    </div>
                `;
                grid.prepend(div);
                addedCount++;
            });

            if (addedCount > 0) {
                const allSpan = document.getElementById('galleryAllCount');
                if (allSpan) allSpan.textContent = parseInt(allSpan.textContent || '0', 10) + addedCount;
                const mapSpan = document.getElementById('galleryMapCount');
                if (mapSpan) mapSpan.textContent = parseInt(mapSpan.textContent || '0', 10) + addedCount;
            }
        } catch (e) {
            console.warn('Could not inject custom pins into gallery:', e);
        }
    });
    </script>
    <!-- Preloader Script -->
    <script src="js/preloader.js?v=<?php echo time(); ?>"></script>
    <script src="js/main.js?v=<?= time() ?>"></script>
    <!-- Mobile Navigation Drawer Backdrop -->
    <div class="mobile-nav-backdrop"></div>
</body>
</html>
