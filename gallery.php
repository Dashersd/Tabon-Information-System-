<?php
// gallery.php - Community Media Gallery (Folder & Album Viewer)
require_once 'db_connect.php';

// Fetch Folders with photo counts
$folders = [];
$folders_query = "SELECT f.*, COUNT(m.id) as media_count 
                  FROM gallery_folders f 
                  LEFT JOIN gallery_media m ON f.id = m.folder_id 
                  GROUP BY f.id 
                  ORDER BY f.created_at DESC, f.id DESC";
$folders_res = $conn->query($folders_query);
if ($folders_res) {
    while ($row = $folders_res->fetch_assoc()) {
        $folders[$row['id']] = $row;
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

// Check if a specific folder was requested via URL (e.g. gallery.php?folder_id=1)
$requested_folder_id = isset($_GET['folder_id']) ? (int)$_GET['folder_id'] : 0;
$initial_folder = ($requested_folder_id > 0 && isset($folders[$requested_folder_id])) ? $folders[$requested_folder_id] : null;
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

        <!-- ======================================================== -->
        <!-- VIEW 1: ALBUMS & FOLDER CARDS (ROOT VIEW)               -->
        <!-- ======================================================== -->
        <div id="galleryFoldersView" style="<?= $initial_folder ? 'display: none;' : 'display: block;' ?>">
            <div class="title-section">
                <h1>Community Media Albums</h1>
                <p>Explore the official event highlights, community programs, and photo collections across Barangay Tabon.</p>
            </div>

            <?php if (!empty($folders)): ?>
                <div class="category-cards-grid">
                    <?php foreach ($folders as $f_id => $f): 
                        $f_name = $f['name'];
                        $f_desc = !empty($f['description']) ? $f['description'] : 'Official community photo collection of Barangay Tabon.';
                        $f_count = (int)$f['media_count'];
                        $f_date = !empty($f['created_at']) ? date('M d, Y', strtotime($f['created_at'])) : date('M d, Y');
                    ?>
                        <div class="category-folder-card" onclick="openGalleryFolder(<?= $f_id ?>)" role="button" tabindex="0">
                            <div class="folder-card-top">
                                <div class="folder-icon-badge">
                                    <i class="bi bi-folder-fill"></i>
                                </div>
                                <span class="photo-count-pill">
                                    <i class="bi bi-image"></i> <?= $f_count ?> <?= ($f_count === 1) ? 'Photo' : 'Photos' ?>
                                </span>
                            </div>

                            <h3 class="folder-card-title"><?= htmlspecialchars($f_name) ?></h3>
                            <p class="folder-card-desc"><?= htmlspecialchars($f_desc) ?></p>

                            <div class="card-dashed-divider"></div>

                            <div class="folder-card-footer">
                                <span class="folder-created-date">
                                    <i class="fa-regular fa-calendar"></i> <?= $f_date ?>
                                </span>
                                <span class="btn-open-album">
                                    Open Album <i class="bi bi-chevron-right"></i>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-gallery-state">
                    <i class="bi bi-folder2-open"></i>
                    <h3>No photo albums available yet</h3>
                    <p>Check back soon for new community updates and published photo albums.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- ======================================================== -->
        <!-- VIEW 2: ALBUM PHOTOS (REVEALED IMAGES VIEW)             -->
        <!-- ======================================================== -->
        <div id="galleryAlbumView" style="<?= $initial_folder ? 'display: block;' : 'display: none;' ?>">
            
            <!-- Breadcrumb Navigation Bar -->
            <div class="gallery-nav-bar">
                <div class="breadcrumbs">
                    <a href="gallery.php" class="breadcrumb-item" onclick="closeFolderView(event)">
                        <i class="bi bi-folder-fill"></i> Media Albums
                    </a>
                    <span class="breadcrumb-sep"><i class="bi bi-chevron-right"></i></span>
                    <span class="breadcrumb-current" id="activeFolderBreadcrumbName">
                        <?= $initial_folder ? htmlspecialchars($initial_folder['name']) : 'Album' ?>
                    </span>
                </div>
                <a href="gallery.php" class="btn-back-folders" onclick="closeFolderView(event)">
                    <i class="bi bi-arrow-left"></i> Back to Albums
                </a>
            </div>

            <!-- Folder Header Card -->
            <div class="category-header-card folder-detail-header-card">
                <div class="category-header-info">
                    <div class="folder-title-wrap">
                        <h2 class="category-main-title">
                            <i class="bi bi-folder2-open folder-title-icon"></i> 
                            <span id="activeFolderTitle"><?= $initial_folder ? htmlspecialchars($initial_folder['name']) : 'Album' ?></span>
                        </h2>
                        <span class="badge-count" id="activeFolderBadgeCount">
                            <?= $initial_folder ? (int)$initial_folder['media_count'] . ' Photos' : '0 Photos' ?>
                        </span>
                    </div>
                    <p class="category-sub-title" id="activeFolderDescription">
                        <?= $initial_folder ? htmlspecialchars($initial_folder['description'] ?: 'Official community photo collection of Barangay Tabon.') : '' ?>
                    </p>
                </div>
            </div>

            <!-- Media Photos Grid -->
            <div class="media-container-white">
                <div class="public-grid" id="albumPhotosGrid">
                    <?php 
                    $matching_photos_count = 0;
                    foreach ($media_items as $media): 
                        $m_title = !empty($media['caption']) ? $media['caption'] : (!empty($media['file_name']) ? pathinfo($media['file_name'], PATHINFO_FILENAME) : 'Gallery Photo');
                        $m_folder = !empty($media['folder_name']) ? $media['folder_name'] : 'General';
                        $m_date = !empty($media['created_at']) ? date('M d, Y • h:i A', strtotime($media['created_at'])) : date('M d, Y • h:i A');
                        $m_folder_id = (int)$media['folder_id'];
                        $is_initially_visible = ($initial_folder && $m_folder_id === (int)$initial_folder['id']);
                        if ($is_initially_visible) $matching_photos_count++;
                    ?>
                        <div class="grid-item album-item album-folder-<?= $m_folder_id ?>" 
                             data-folder-id="<?= $m_folder_id ?>"
                             data-img="<?= htmlspecialchars($media['web_path']) ?>" 
                             data-title="<?= htmlspecialchars($m_title) ?>" 
                             data-folder="<?= htmlspecialchars($m_folder) ?>" 
                             data-date="<?= htmlspecialchars($m_date) ?>" 
                             style="<?= $is_initially_visible ? 'display: flex;' : 'display: none;' ?>"
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
                                    <i class="bi bi-folder-fill" style="color:#d97706; margin-right:3px;"></i>
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

                    <!-- Dynamic Empty State inside Album -->
                    <div id="albumEmptyState" class="empty-gallery-state" style="<?= ($initial_folder && $matching_photos_count === 0) ? 'display: block;' : 'display: none;' ?>">
                        <i class="bi bi-images"></i>
                        <h3>No photos in this album yet</h3>
                        <p>Photos for this album will appear here once published.</p>
                    </div>
                </div>
            </div>

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
    const foldersData = <?= json_encode($folders, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    let activeFolderId = <?= $initial_folder ? (int)$initial_folder['id'] : 'null' ?>;

    let activeLightboxList = [];
    let activeLightboxIndex = 0;

    const lightbox = document.getElementById('lightboxModal');
    const lightboxImg = document.getElementById('lightboxImg');
    const lightboxCaption = document.getElementById('lightboxCaption');
    const lightboxCounter = document.getElementById('lightboxCounter');
    const lightboxClose = document.getElementById('galleryLbClose');
    const lightboxPrev = document.getElementById('galleryLbPrev');
    const lightboxNext = document.getElementById('galleryLbNext');

    // 1. Open a Folder / Album
    function openGalleryFolder(folderId) {
        const folder = foldersData[folderId];
        if (!folder) return;

        activeFolderId = folderId;

        // Update Folder Header and Breadcrumb
        document.getElementById('activeFolderBreadcrumbName').textContent = folder.name || 'Album';
        document.getElementById('activeFolderTitle').textContent = folder.name || 'Album';
        
        const count = parseInt(folder.media_count || '0', 10);
        document.getElementById('activeFolderBadgeCount').textContent = count + (count === 1 ? ' Photo' : ' Photos');
        document.getElementById('activeFolderDescription').textContent = folder.description || 'Official community photo collection of Barangay Tabon.';

        // Filter Grid Photos
        const allItems = document.querySelectorAll('#albumPhotosGrid .album-item');
        let visibleCount = 0;
        allItems.forEach(item => {
            if (parseInt(item.getAttribute('data-folder-id'), 10) === parseInt(folderId, 10)) {
                item.style.display = 'flex';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        // Show/hide empty state
        const emptyBox = document.getElementById('albumEmptyState');
        if (emptyBox) {
            emptyBox.style.display = (visibleCount === 0) ? 'block' : 'none';
        }

        // Switch View
        document.getElementById('galleryFoldersView').style.display = 'none';
        document.getElementById('galleryAlbumView').style.display = 'block';

        // Update URL state (pushState allows browser Back button to work)
        const newUrl = window.location.pathname + '?folder_id=' + encodeURIComponent(folderId);
        window.history.pushState({ folder_id: folderId }, '', newUrl);

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // 2. Close Folder View & Return to Folders Grid
    function closeFolderView(e) {
        if (e) e.preventDefault();

        activeFolderId = null;
        document.getElementById('galleryAlbumView').style.display = 'none';
        document.getElementById('galleryFoldersView').style.display = 'block';

        const rootUrl = window.location.pathname;
        window.history.pushState({}, '', rootUrl);

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Handle Browser Back / Forward Buttons
    window.addEventListener('popstate', function (e) {
        if (e.state && e.state.folder_id) {
            openGalleryFolder(e.state.folder_id);
        } else {
            activeFolderId = null;
            document.getElementById('galleryAlbumView').style.display = 'none';
            document.getElementById('galleryFoldersView').style.display = 'block';
        }
    });

    // 3. Fullscreen Lightbox Logic
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
        // Collect all currently visible grid items in active album
        const visibleItems = Array.from(document.querySelectorAll('#albumPhotosGrid .album-item')).filter(it => it.style.display !== 'none');
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
    </script>
    
    <!-- Preloader Script -->
    <script src="js/preloader.js?v=<?php echo time(); ?>"></script>
    <script src="js/main.js?v=<?= time() ?>"></script>
    <!-- Mobile Navigation Drawer Backdrop -->
    <div class="mobile-nav-backdrop"></div>
</body>
</html>
