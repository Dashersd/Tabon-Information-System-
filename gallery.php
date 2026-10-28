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
    
    <!-- External Stylesheets -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/gallery.css?v=<?php echo time(); ?>">

    <style>
        .gallery-main-wrapper {
            max-width: 1280px;
            margin: 0 auto;
            padding: 40px 24px 60px 24px;
        }
        .title-section {
            text-align: center;
            margin-bottom: 35px;
        }
        .title-section h1 {
            font-size: 2.3rem;
            color: #223c3b;
            font-weight: 800;
            margin: 0 0 10px 0;
            font-family: 'Playfair Display', serif;
        }
        .title-section p {
            color: #64748b;
            font-size: 1.05rem;
            max-width: 650px;
            margin: 0 auto;
        }

        /* Category Filter Chips */
        .filter-tabs-wrapper {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 40px;
        }
        .filter-chip-btn {
            background: #ffffff;
            color: #475569;
            border: 1.5px solid #cbd5e1;
            border-radius: 30px;
            padding: 9px 22px;
            font-weight: 600;
            font-size: 0.92rem;
            cursor: pointer;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.03);
            font-family: 'Montserrat', sans-serif;
        }
        .filter-chip-btn:hover {
            background: #2c6e49;
            color: #ffffff;
            border-color: #2c6e49;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(44, 110, 73, 0.2);
        }
        .filter-chip-btn.active {
            background: #223c3b;
            color: #ffffff;
            border-color: #223c3b;
            box-shadow: 0 4px 12px rgba(34, 60, 59, 0.25);
        }

        /* Public Gallery Grid */
        .public-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }
        .grid-item {
            border-radius: 16px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 4px 18px rgba(0,0,0,0.06);
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
        }
        .grid-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.12);
        }
        .grid-thumb-box {
            position: relative;
            width: 100%;
            height: 220px;
            overflow: hidden;
            background: #f1f5f9;
        }
        .grid-thumb-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.4s ease;
        }
        .grid-item:hover .grid-thumb-box img {
            transform: scale(1.08);
        }
        .grid-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(34, 60, 59, 0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .grid-item:hover .grid-overlay {
            opacity: 1;
        }
        .grid-overlay i {
            color: #ffffff;
            font-size: 2rem;
            filter: drop-shadow(0 2px 6px rgba(0,0,0,0.4));
        }
        .item-info {
            padding: 16px 18px;
            background: #ffffff;
            margin-top: auto;
        }
        .item-tag {
            font-size: 0.75rem;
            font-weight: 700;
            color: #0f766e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            margin-bottom: 4px;
        }
        .item-caption {
            font-size: 0.98rem;
            font-weight: 700;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .item-date {
            font-size: 0.8rem;
            color: #94a3b8;
            margin-top: 6px;
        }

        /* Empty State */
        .empty-gallery-state {
            text-align: center;
            padding: 60px 20px;
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            grid-column: 1 / -1;
        }
        .empty-gallery-state i {
            font-size: 3.5rem;
            color: #cbd5e1;
            margin-bottom: 14px;
            display: block;
        }
        .empty-gallery-state h3 {
            margin: 0 0 6px 0;
            color: #1e293b;
            font-size: 1.25rem;
        }
        .empty-gallery-state p {
            margin: 0;
            color: #64748b;
        }

        /* Lightbox Modal */
        .lightbox-modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.94);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .lightbox-container {
            max-width: 92vw;
            max-height: 90vh;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .lightbox-img {
            max-width: 100%;
            max-height: 80vh;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.6);
            object-fit: contain;
        }
        .lightbox-caption {
            color: #ffffff;
            font-size: 1.1rem;
            margin-top: 14px;
            font-weight: 600;
            font-family: 'Montserrat', sans-serif;
            text-shadow: 0 2px 4px rgba(0,0,0,0.6);
        }
        .lightbox-close {
            position: absolute;
            top: 25px;
            right: 35px;
            font-size: 2.8rem;
            color: #ffffff;
            cursor: pointer;
            line-height: 1;
            opacity: 0.85;
            transition: opacity 0.2s;
        }
        .lightbox-close:hover {
            opacity: 1;
            color: #ef4444;
        }
    </style>
</head>
<body class="gallery-page">

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

            <a href="login.php" class="btn-login">
                <i class="fa-regular fa-user"></i>
                <span>Sign In / Login</span>
            </a>
        </nav>
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
                <i class="bi bi-images"></i> All Photos (<?= count($media_items) ?>)
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
                    <div class="grid-item folder-<?= $media['folder_id'] ?>" onclick="viewLightbox('<?= htmlspecialchars($media['web_path']) ?>', '<?= htmlspecialchars(addslashes($m_title)) ?>', '<?= htmlspecialchars(addslashes($m_folder)) ?>', '<?= htmlspecialchars(addslashes($m_date)) ?>')">
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
                            <span class="item-tag"><i class="bi bi-folder-fill" style="color:#d97706; margin-right:3px;"></i> <?= htmlspecialchars($m_folder) ?></span>
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
    <div id="lightboxModal" class="lightbox-modal" onclick="closeLightbox()">
        <span class="lightbox-close">&times;</span>
        <div class="lightbox-container" onclick="event.stopPropagation()">
            <img src="" alt="Fullscreen preview" id="lightboxImg" class="lightbox-img">
            <div id="lightboxCaption" class="lightbox-caption"></div>
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

    <!-- Interactive Scripts -->
    <script>
    function filterGallery(category, btn) {
        document.querySelectorAll('.filter-chip-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

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

    function viewLightbox(src, caption, folder, date) {
        const lightbox = document.getElementById('lightboxModal');
        const img = document.getElementById('lightboxImg');
        const cap = document.getElementById('lightboxCaption');
        if (lightbox && img) {
            img.src = src;
            let captionHtml = `<div style="font-size:1.25rem; font-weight:700; color:#ffffff; margin-bottom:8px; font-family:'Montserrat',sans-serif; text-shadow:0 2px 5px rgba(0,0,0,0.85);">${caption}</div>`;
            if (folder || date) {
                captionHtml += `<div style="display:inline-flex; align-items:center; gap:10px; justify-content:center; flex-wrap:wrap;">`;
                if (folder) {
                    captionHtml += `<span style="display:inline-flex; align-items:center; gap:5px; padding:4px 12px; border-radius:50px; border:1.5px solid #f59e0b; background:rgba(245,158,11,0.18); color:#fbbf24; font-size:0.75rem; font-weight:800; letter-spacing:0.5px; text-transform:uppercase;"><i class="fa-solid fa-folder"></i> ${folder}</span>`;
                }
                if (date) {
                    captionHtml += `<span style="display:inline-flex; align-items:center; gap:6px; padding:5px 12px; border-radius:50px; background:rgba(15,23,42,0.88); border:1px solid rgba(255,255,255,0.15); color:#f8fafc; font-size:0.75rem; font-weight:600;"><i class="fa-regular fa-clock" style="color:#fbbf24;"></i> ${date}</span>`;
                }
                captionHtml += `</div>`;
            }
            cap.innerHTML = captionHtml;
            lightbox.style.display = 'flex';
        }
    }

    function closeLightbox() {
        const lightbox = document.getElementById('lightboxModal');
        if (lightbox) lightbox.style.display = 'none';
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeLightbox();
        }
    });
    </script>
    <script src="js/main.js?v=<?= time() ?>"></script>
</body>
</html>
