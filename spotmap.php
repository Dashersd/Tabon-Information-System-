<?php
require_once 'db_connect.php';
$purok1 = [];
$purok2 = [];
$purok3 = [];
$public_legends = [];
try {
    $stmt1 = $pdo->prepare("SELECT * FROM purok1_locations"); $stmt1->execute(); $purok1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    $stmt2 = $pdo->prepare("SELECT * FROM purok2_locations"); $stmt2->execute(); $purok2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    $stmt3 = $pdo->prepare("SELECT * FROM purok3_locations"); $stmt3->execute(); $purok3 = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt_leg = $conn->query("SELECT * FROM `sm_legends` WHERE `is_active` = 1 ORDER BY `display_order` ASC, `id` ASC");
    if ($stmt_leg && $stmt_leg->num_rows > 0) {
        while ($r = $stmt_leg->fetch_assoc()) {
            $public_legends[] = $r;
        }
    }
} catch (Exception $e) {}

// Helper to resolve landmark display photo URL
function get_public_legend_photo($path) {
    if (empty($path)) return "";
    if (strpos($path, "http://") === 0 || strpos($path, "https://") === 0) return $path;
    if (strpos($path, "uploads/") === 0) return "Admin/" . ltrim($path, "/");
    return ltrim($path, "/");
}

if (empty($public_legends)) {
    $json_l = __DIR__ . '/Admin/data/legend.json';
    if (file_exists($json_l)) {
        $public_legends = json_decode(file_get_contents($json_l), true) ?? [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Tabon - Spot Map</title>
    
    <!-- Bootstrap Icons v1.11.3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- FontAwesome CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600;1,700&display=swap" rel="stylesheet">
    
    <!-- External Stylesheets -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/spotmap.css?v=<?php echo time(); ?>">
</head>
<body class="spotmap-page">

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
            <a href="spotmap.php" class="nav-item active">
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
    <main class="spotmap-main-wrapper">
        <section class="full-map-section">
            <div class="spotmap-layout-container fade-in-up animate-on-scroll delay-1">
                
                <!-- Side Navigation -->
                <div class="spotmap-sidebar">
                    <div style="text-align: center; margin-bottom: 30px;">
                        <span class="badge-title" style="margin-bottom: 0;">SPOT MAP</span>
                    </div>
                    
                    <div class="cards-wrapper">
                        <!-- Map Legend Box -->
                        <div class="info-card">
                            <h3 class="card-title"><i class="fa-regular fa-map" style="color: #fca311;"></i> Map Legend</h3>
                            <div class="legend-grid">
                                <?php if (!empty($public_legends)): ?>
                                    <?php foreach ($public_legends as $leg): 
                                        $is_custom = (strpos($leg['icon_path'] ?? '', 'uploads/') === 0);
                                        $icon_color = !empty($leg['color_hex']) ? $leg['color_hex'] : '#4f46e5';
                                        $leg_photo = get_public_legend_photo($leg['image_path'] ?? '');
                                    ?>
                                        <div class="legend-item" data-id="<?= (int)$leg['id'] ?>" title="Click to view & locate on aerial map" onclick="focusPublicLandmark(<?= (int)$leg['id'] ?>, '<?= htmlspecialchars(addslashes($leg['name'])) ?>', '<?= htmlspecialchars(addslashes($leg_photo)) ?>', '<?= htmlspecialchars(addslashes($leg['icon_path'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($icon_color)) ?>')">
                                            <?php if ($is_custom): ?>
                                                <img src="Admin/<?= htmlspecialchars($leg['icon_path']) ?>" style="width:18px; height:18px; object-fit:contain; vertical-align:middle; margin-right:6px;">
                                            <?php else: ?>
                                                <i class="bi <?= htmlspecialchars($leg['icon_path']) ?>" style="color:<?= htmlspecialchars($icon_color) ?>; margin-right:6px;"></i>
                                            <?php endif; ?>
                                            <span><?= htmlspecialchars($leg['name']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div style="grid-column: 1 / -1; color: #94a3b8; font-size: 13.5px; font-style: italic; padding: 12px 4px;">No landmarks currently added.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Puroks Box -->
                        <div class="info-card">
                            <h3 class="card-title"><i class="fa-solid fa-location-dot" style="color: #fca311;"></i> 3 Puroks of Tabon</h3>
                            <div class="purok-list">
                                <div class="purok-pill" onclick="changeMapImage('Images/Purok/Purok 1.jpg')">Purok Mesaya</div>
                                <div class="purok-pill" onclick="changeMapImage('Images/Purok/Purok 2.jpg')">Purok Metamed</div>
                                <div class="purok-pill" onclick="changeMapImage('Images/Purok/Purok 3.jpg')">Purok Melitebangay</div>
                                <div class="purok-pill aerial-btn-pill" style="background-color: #000000; color: #ffffff; border-color: #000000;" onclick="showAerialSpotMap()"><i class="fa-solid fa-map-pin"></i> Aerial Spot Map</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Spot Map Image (Now on Right) -->
                <div class="spotmap-main">
                    <div class="map-container" style="position: relative; width: 100%; max-height: calc(100vh - 140px); display: flex; justify-content: center; overflow: hidden; border-radius: 28px;">
                        <img src="Images/Map/Philippines.png" alt="Barangay Tabon Spot Map Full View" class="full-spot-map-img" id="mainSpotMapImage" style="cursor: default; width: 100%; object-fit: cover;">
                        
                        <!-- Interactive Zoom-In Pin Overlay -->
                        <div id="barangay-pin" class="interactive-map-pin" style="top: 74%; left: 60%; display: block;" title="Click pin to zoom into Mindanao">
                            <div class="pin-badge"><i class="fa-solid fa-magnifying-glass-plus"></i> <span id="pin-badge-text">Mindanao</span></div>
                            <div class="pin-marker-pulse"></div>
                            <i class="fa-solid fa-location-dot pin-icon"></i>
                        </div>

                        <!-- Aerial Legend Landmarks Markers Layer -->
                        <div id="legend-markers" class="legend-markers-layer" style="display: none;">
                            <?php foreach ($public_legends as $leg): 
                                $leg_photo = get_public_legend_photo($leg['image_path'] ?? '');
                                $is_custom = (strpos($leg['icon_path'] ?? '', 'uploads/') === 0);
                                $icon_color = !empty($leg['color_hex']) ? $leg['color_hex'] : '#4f46e5';
                                $w = !empty($leg['marker_width']) ? (int)$leg['marker_width'] : 40;
                                $h = !empty($leg['marker_height']) ? (int)$leg['marker_height'] : 40;
                                $top = (float)($leg['top_position'] ?? 50);
                                $left = (float)($leg['left_position'] ?? 50);
                                $name_safe = htmlspecialchars($leg['name'] ?? 'Landmark');
                            ?>
                                <div class="saved-legend-marker public-legend-marker" 
                                     id="public-marker-<?= (int)$leg['id'] ?>"
                                     style="top: <?= $top ?>%; left: <?= $left ?>%;"
                                     data-id="<?= (int)$leg['id'] ?>"
                                     data-name="<?= $name_safe ?>"
                                     data-icon="<?= htmlspecialchars($leg['icon_path'] ?? '') ?>"
                                     data-color="<?= htmlspecialchars($icon_color) ?>"
                                     data-photo="<?= htmlspecialchars($leg_photo) ?>"
                                     onclick="openPublicLandmarkModal(this)"
                                     title="<?= $name_safe ?>">
                                    
                                    <!-- Tooltip Label -->
                                    <div class="saved-marker-tooltip">
                                        <span><?= $name_safe ?></span>
                                        <?php if (!empty($leg_photo)): ?>
                                            <span class="photo-pill"><i class="bi bi-image"></i> Photo</span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Stylized Map Pin -->
                                    <div class="saved-marker-pin" style="background-color: <?= $icon_color ?>; width: <?= $w ?>px; height: <?= $h ?>px;">
                                        <?php if ($is_custom): ?>
                                            <img src="Admin/<?= htmlspecialchars($leg['icon_path']) ?>" alt="<?= $name_safe ?>">
                                        <?php else: ?>
                                            <i class="bi <?= htmlspecialchars($leg['icon_path']) ?>"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="saved-marker-pointer" style="border-top-color: <?= $icon_color ?>;"></div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Purok 1 Markers -->
                        <div id="purok1-markers" style="display: none;">
                            <?php foreach($purok1 as $m): ?>
                                <div class="saved-marker" style="position: absolute; left: <?= htmlspecialchars($m['coordinate_x']) ?>%; top: <?= htmlspecialchars($m['coordinate_y']) ?>%; transform: translate(-50%, -100%); z-index: 10;">
                                    <img src="Admin/<?= htmlspecialchars($m['marker_image']) ?>" alt="Marker" draggable="false" style="width: <?= htmlspecialchars($m['marker_width']) ?>px; height: <?= htmlspecialchars($m['marker_height']) ?>px; cursor: pointer; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3)); user-select: none; -webkit-user-drag: none;" title="House #<?= htmlspecialchars($m['house_number']) ?>" data-image="Admin/<?= htmlspecialchars($m['house_image']) ?>" data-house="<?= htmlspecialchars($m['house_number']) ?>" data-husband="<?= htmlspecialchars($m['husband_name']) ?>" data-spouse="<?= htmlspecialchars($m['spouse_name'] ?? '') ?>" data-children="<?= htmlspecialchars($m['children'] ?? '[]') ?>" onclick="openPublicHouseholdModal(this)">
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Purok 2 Markers -->
                        <div id="purok2-markers" style="display: none;">
                            <?php foreach($purok2 as $m): ?>
                                <div class="saved-marker" style="position: absolute; left: <?= htmlspecialchars($m['coordinate_x']) ?>%; top: <?= htmlspecialchars($m['coordinate_y']) ?>%; transform: translate(-50%, -100%); z-index: 10;">
                                    <img src="Admin/<?= htmlspecialchars($m['marker_image']) ?>" alt="Marker" draggable="false" style="width: <?= htmlspecialchars($m['marker_width']) ?>px; height: <?= htmlspecialchars($m['marker_height']) ?>px; cursor: pointer; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3)); user-select: none; -webkit-user-drag: none;" title="House #<?= htmlspecialchars($m['house_number']) ?>" data-image="Admin/<?= htmlspecialchars($m['house_image']) ?>" data-house="<?= htmlspecialchars($m['house_number']) ?>" data-husband="<?= htmlspecialchars($m['husband_name']) ?>" data-spouse="<?= htmlspecialchars($m['spouse_name'] ?? '') ?>" data-children="<?= htmlspecialchars($m['children'] ?? '[]') ?>" onclick="openPublicHouseholdModal(this)">
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Purok 3 Markers -->
                        <div id="purok3-markers" style="display: none;">
                            <?php foreach($purok3 as $m): ?>
                                <div class="saved-marker" style="position: absolute; left: <?= htmlspecialchars($m['coordinate_x']) ?>%; top: <?= htmlspecialchars($m['coordinate_y']) ?>%; transform: translate(-50%, -100%); z-index: 10;">
                                    <img src="Admin/<?= htmlspecialchars($m['marker_image']) ?>" alt="Marker" draggable="false" style="width: <?= htmlspecialchars($m['marker_width']) ?>px; height: <?= htmlspecialchars($m['marker_height']) ?>px; cursor: pointer; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3)); user-select: none; -webkit-user-drag: none;" title="House #<?= htmlspecialchars($m['house_number']) ?>" data-image="Admin/<?= htmlspecialchars($m['house_image']) ?>" data-house="<?= htmlspecialchars($m['house_number']) ?>" data-husband="<?= htmlspecialchars($m['husband_name']) ?>" data-spouse="<?= htmlspecialchars($m['spouse_name'] ?? '') ?>" data-children="<?= htmlspecialchars($m['children'] ?? '[]') ?>" onclick="openPublicHouseholdModal(this)">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
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
            <p>&copy; 2026 Barangay Tabon Information System. All rights reserved.</p>
        </div>
    </footer>

    <!-- Image Toast Modal -->
    <div id="legendToast" class="legend-toast-overlay" onclick="closeLegendToast()">
        <div class="legend-toast-content" onclick="event.stopPropagation()">
            <span class="close-toast" onclick="closeLegendToast()">&times;</span>
            <h3 id="toastTitle" style="display:flex; align-items:center; gap:10px; margin-bottom:15px;">Location Image</h3>
            <div class="toast-image-container">
                <img id="toastImage" src="" alt="Landmark Image" style="display:none; width:100%; height:100%; object-fit:cover; border-radius:12px;">
                <div id="toastFallback" style="display:none; flex-direction:column; align-items:center; justify-content:center; width:100%; min-height:360px; color:#64748b; background:#f8fafc; border-radius:12px; text-align:center; padding:30px;">
                    <div id="toastFallbackIcon" style="width:72px; height:72px; border-radius:50%; background:#e0e7ff; color:#4f46e5; display:flex; align-items:center; justify-content:center; font-size:32px; margin-bottom:14px;">
                        <i class="bi bi-geo-alt-fill"></i>
                    </div>
                    <h4 id="toastFallbackTitle" style="margin:0 0 6px; color:#1e293b; font-size:18px; font-weight:700;">Landmark Photo Coming Soon</h4>
                    <p style="margin:0; font-size:14px; color:#64748b;">Barangay Tabon Official Aerial Landmark</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Household Details Modal -->
    <div id="householdModal" class="household-modal-overlay" onclick="closeHouseholdDetails()">
        <div class="household-modal-content" onclick="event.stopPropagation()">
            <span class="close-toast" onclick="closeHouseholdDetails()">&times;</span>
            
            <div class="modal-header">
                <h2>Household Details</h2>
                <p>Barangay Tabon</p>
            </div>
            
            <div class="modal-image-circle">
                <img id="modalHouseImage" src="" alt="Household Image">
            </div>
            
            <div class="modal-info-card">
                <div class="info-row">
                    <span class="info-label">House Number</span>
                    <span class="info-value" id="modalHouseNumber"></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Husband/Head</span>
                    <span class="info-value" id="modalHusbandName"></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Spouse Name</span>
                    <span class="info-value" id="modalSpouseName"></span>
                </div>
                <div class="info-row" style="flex-direction: column; align-items: flex-start; gap: 8px;">
                    <span class="info-label" style="font-weight: 600; color: #475569; font-size: 15px;">Children / Dependents</span>
                    <div class="info-value" id="modalChildrenList" style="width: 100%; text-align: left;"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Household Details functions
        function openPublicHouseholdModal(el) {
            const img = el.getAttribute('data-image');
            const house = el.getAttribute('data-house');
            const husband = el.getAttribute('data-husband');
            const spouse = el.getAttribute('data-spouse');
            const rawChildren = el.getAttribute('data-children') || '[]';

            let childrenArr = [];
            try { 
                childrenArr = JSON.parse(rawChildren); 
            } catch(e) {}
            if (!Array.isArray(childrenArr)) childrenArr = [];

            document.getElementById('modalHouseImage').src = img;
            document.getElementById('modalHouseNumber').innerText = house;
            document.getElementById('modalHusbandName').innerText = husband || 'N/A';
            document.getElementById('modalSpouseName').innerText = spouse || 'N/A';

            const childrenListEl = document.getElementById('modalChildrenList');
            if (childrenListEl) {
                if (childrenArr.length > 0) {
                    childrenListEl.innerHTML = `
                        <ul style="margin: 0; padding-left: 20px; list-style-type: disc; display: flex; flex-direction: column; gap: 6px; text-align: left;">
                            ${childrenArr.map(c => `
                                <li style="color: #0284c7; font-size: 1rem;">
                                    <span style="color: #1e293b; font-size: 0.95rem; font-weight: 600;">${c}</span>
                                </li>
                            `).join('')}
                        </ul>
                    `;
                } else {
                    childrenListEl.innerHTML = '<span style="color:#94a3b8; font-style:italic; font-weight: normal; font-size: 0.88rem;">No dependents listed</span>';
                }
            }
            document.getElementById('householdModal').classList.add('active');
        }

        function showHouseholdDetails(imageSrc, houseNumber, husbandName, spouseName) {
            document.getElementById('modalHouseImage').src = imageSrc;
            document.getElementById('modalHouseNumber').innerText = houseNumber;
            document.getElementById('modalHusbandName').innerText = husbandName || 'N/A';
            document.getElementById('modalSpouseName').innerText = spouseName || 'N/A';
            const childrenListEl = document.getElementById('modalChildrenList');
            if (childrenListEl) {
                childrenListEl.innerHTML = '<span style="color:#94a3b8; font-style:italic; font-weight: normal; font-size: 0.88rem;">No dependents listed</span>';
            }
            document.getElementById('householdModal').classList.add('active');
        }

        function closeHouseholdDetails() {
            document.getElementById('householdModal').classList.remove('active');
        }
        function showLegendToast(name, photo, icon, color) {
            const titleEl = document.getElementById('toastTitle');
            const imgEl = document.getElementById('toastImage');
            const fallbackEl = document.getElementById('toastFallback');
            const fallbackIcon = document.getElementById('toastFallbackIcon');
            const modal = document.getElementById('legendToast');

            if (titleEl) titleEl.innerText = name || 'Landmark Location';

            if (photo && photo.trim() !== '') {
                if (imgEl) {
                    imgEl.src = photo;
                    imgEl.style.display = 'block';
                }
                if (fallbackEl) fallbackEl.style.display = 'none';
            } else {
                if (imgEl) {
                    imgEl.src = '';
                    imgEl.style.display = 'none';
                }
                if (fallbackEl) {
                    fallbackEl.style.display = 'flex';
                    if (fallbackIcon) {
                        fallbackIcon.style.backgroundColor = (color || '#4f46e5') + '22';
                        fallbackIcon.style.color = color || '#4f46e5';
                        if (icon && icon.startsWith('bi-')) {
                            fallbackIcon.innerHTML = `<i class="bi ${icon}"></i>`;
                        } else if (icon && icon.startsWith('uploads/')) {
                            fallbackIcon.innerHTML = `<img src="Admin/${icon}" style="width:36px; height:36px; object-fit:contain;">`;
                        } else {
                            fallbackIcon.innerHTML = `<i class="bi bi-geo-alt-fill"></i>`;
                        }
                    }
                }
            }
            if (modal) modal.classList.add('active');
        }

        function closeLegendToast() {
            const modal = document.getElementById('legendToast');
            if (modal) modal.classList.remove('active');
        }

        function focusPublicLandmark(id, name, photo, icon, color) {
            // Ensure Aerial view is displayed
            if (currentStepIndex !== 4) {
                applyMapStep(4);
            }
            
            // Highlight the clicked pin with ping animation
            const pinEl = document.getElementById('public-marker-' + id);
            if (pinEl) {
                document.querySelectorAll('.saved-legend-marker').forEach(m => m.classList.remove('highlight-ping'));
                pinEl.classList.add('highlight-ping');
                setTimeout(() => {
                    pinEl.classList.remove('highlight-ping');
                }, 3500);
            }

            showLegendToast(name, photo, icon, color);
        }

        function openPublicLandmarkModal(el) {
            const name = el.getAttribute('data-name') || 'Landmark';
            const photo = el.getAttribute('data-photo') || '';
            const icon = el.getAttribute('data-icon') || '';
            const color = el.getAttribute('data-color') || '#4f46e5';

            document.querySelectorAll('.saved-legend-marker').forEach(m => m.classList.remove('highlight-ping'));
            el.classList.add('highlight-ping');
            setTimeout(() => {
                el.classList.remove('highlight-ping');
            }, 3000);

            showLegendToast(name, photo, icon, color);

            const id = el.getAttribute('data-id');
            if (id) {
                const sidebarItem = document.querySelector(`.legend-item[data-id="${id}"]`);
                if (sidebarItem) {
                    document.querySelectorAll('.legend-item').forEach(li => li.classList.remove('active-legend-item'));
                    sidebarItem.classList.add('active-legend-item');
                    sidebarItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    setTimeout(() => sidebarItem.classList.remove('active-legend-item'), 3000);
                }
            }
        }

        // Step-by-Step Interactive Zoom Configuration (Pins only)
        const mapSteps = [
            {
                image: 'Images/Map/Philippines.png',
                stepLabel: 'Philippines &rarr; Click pin on Mindanao',
                pin: {
                    show: true,
                    left: '60%',
                    top: '74%',
                    title: 'Click pin to zoom into Mindanao',
                    label: 'Mindanao'
                }
            },
            {
                image: 'Images/Map/mindanao.jpg',
                stepLabel: 'Mindanao &rarr; Click pin on Zamboanga del Sur',
                pin: {
                    show: true,
                    left: '30.5%',
                    top: '57%',
                    title: 'Click pin to zoom into Zamboanga del Sur',
                    label: 'Zamboanga del Sur'
                }
            },
            {
                image: 'Images/Map/Zamboanga del Sur.jpg?v=<?= filemtime(__DIR__ . "/Images/Map/Zamboanga del Sur.jpg") ?>',
                stepLabel: 'Zamboanga del Sur &rarr; Click pin on Lapuyan',
                pin: {
                    show: true,
                    left: '51.2%',
                    top: '46.5%',
                    title: 'Click pin to zoom into Lapuyan',
                    label: 'Lapuyan'
                }
            },
            {
                image: 'Images/Map/Lapuyan.jpg?v=<?= filemtime(__DIR__ . "/Images/Map/Lapuyan.jpg") ?>',
                stepLabel: 'Lapuyan &rarr; Click pin on Barangay Tabon',
                pin: {
                    show: true,
                    left: '33.8%',
                    top: '45.8%',
                    title: 'Click pin to view Aerial Spot Map',
                    label: 'Barangay Tabon'
                }
            },
            {
                image: 'public/image/Maruing Map/Aerial View.png',
                stepLabel: 'Barangay Tabon Aerial Spot Map',
                pin: {
                    show: false
                }
            }
        ];

        let currentStepIndex = 0;

        function applyMapStep(index) {
            currentStepIndex = index;
            const step = mapSteps[index];
            const mapImg = document.getElementById('mainSpotMapImage');
            const pin = document.getElementById('barangay-pin');
            const pinText = document.getElementById('pin-badge-text');

            if (mapImg && step) {
                mapImg.src = step.image;
                mapImg.style.cursor = 'default';
                mapImg.onerror = function() {
                    if (this.src.includes('Aerial%20View') || this.src.includes('Aerial View')) {
                        this.src = 'Images/ChatGPT Image Oct 1, 2026, 07_50_28 AM.png';
                    }
                };
            }

            if (pin) {
                if (step.pin && step.pin.show) {
                    pin.style.display = 'block';
                    pin.style.left = step.pin.left;
                    pin.style.top = step.pin.top;
                    pin.title = step.pin.title;
                    if (pinText) {
                        pinText.textContent = step.pin.label;
                    }
                } else {
                    pin.style.display = 'none';
                }
            }

            // Hide all purok markers when zooming in
            const p1 = document.getElementById('purok1-markers');
            const p2 = document.getElementById('purok2-markers');
            const p3 = document.getElementById('purok3-markers');
            const legMarkers = document.getElementById('legend-markers');
            if (p1) p1.style.display = 'none';
            if (p2) p2.style.display = 'none';
            if (p3) p3.style.display = 'none';

            // Show legend markers ONLY when on the Aerial Spot Map step (index 4)
            if (legMarkers) {
                if (index === 4) {
                    legMarkers.style.display = 'block';
                } else {
                    legMarkers.style.display = 'none';
                }
            }
        }

        // ONLY clicking the PIN advances the sequence
        const interactivePin = document.getElementById('barangay-pin');
        if (interactivePin) {
            interactivePin.addEventListener('click', function(e) {
                e.stopPropagation();
                if (currentStepIndex < mapSteps.length - 1) {
                    applyMapStep(currentStepIndex + 1);
                }
            });
        }

        // Reset to initial Philippines map
        function resetToFullMap() {
            applyMapStep(0);
        }

        function showAerialSpotMap() {
            applyMapStep(4);
        }

        function changeMapImage(imageUrl) {
            const mapImg = document.getElementById('mainSpotMapImage');
            const pin = document.getElementById('barangay-pin');
            const p1 = document.getElementById('purok1-markers');
            const p2 = document.getElementById('purok2-markers');
            const p3 = document.getElementById('purok3-markers');
            const legMarkers = document.getElementById('legend-markers');

            if (p1) p1.style.display = 'none';
            if (p2) p2.style.display = 'none';
            if (p3) p3.style.display = 'none';
            if (legMarkers) legMarkers.style.display = 'none';

            if (imageUrl === 'Images/Map/Philippines.png') {
                applyMapStep(0);
                return;
            }

            mapImg.src = imageUrl;
            mapImg.style.cursor = 'default';
            if (pin) pin.style.display = 'none';

            if (imageUrl.includes('Purok 1')) {
                if (p1) p1.style.display = 'block';
            } else if (imageUrl.includes('Purok 2')) {
                if (p2) p2.style.display = 'block';
            } else if (imageUrl.includes('Purok 3')) {
                if (p3) p3.style.display = 'block';
            }
        }

        // Automatically load aerial map if requested via query or hash
        document.addEventListener('DOMContentLoaded', function() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('view') === 'aerial' || window.location.hash === '#aerial') {
                showAerialSpotMap();
            }
        });
    </script>
    <script src="js/main.js?v=<?= time() ?>"></script>
</body>
</html>
