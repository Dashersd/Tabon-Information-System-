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

// Resolve Purok Map Background Images (Custom or Default)
$purok_map_images = [
    'purok1' => 'Images/Purok/Purok 1.jpg',
    'purok2' => 'Images/Purok/Purok 2.jpg',
    'purok3' => 'Images/Purok/Purok 3.jpg'
];
$legend_map_image = 'public/image/Maruing Map/Aerial View.png';
$zoom_pins = [
    0 => ['top' => '74%', 'left' => '60%', 'label' => 'Mindanao', 'image' => 'Images/Map/Philippines.png'],
    1 => ['top' => '57%', 'left' => '30.5%', 'label' => 'Zamboanga del Sur', 'image' => 'Images/Map/mindanao.jpg'],
    2 => ['top' => '46.5%', 'left' => '51.2%', 'label' => 'Lapuyan', 'image' => 'Images/Map/Zamboanga del Sur.jpg'],
    3 => ['top' => '45.8%', 'left' => '33.8%', 'label' => 'Barangay Tabon', 'image' => 'Images/Map/Lapuyan.jpg']
];
try {
    $res_zoom = $conn->query("SELECT setting_key, setting_value FROM sm_settings WHERE setting_key LIKE 'zoom_%'");
    if ($res_zoom) {
        while ($zr = $res_zoom->fetch_assoc()) {
            $zk = $zr['setting_key'];
            $zv = trim($zr['setting_value']);
            foreach ([0, 1, 2, 3] as $idx) {
                if ($zk === "zoom_pin_step{$idx}_top" && $zv !== '') $zoom_pins[$idx]['top'] = $zv . (strpos($zv, '%') === false ? '%' : '');
                if ($zk === "zoom_pin_step{$idx}_left" && $zv !== '') $zoom_pins[$idx]['left'] = $zv . (strpos($zv, '%') === false ? '%' : '');
                if ($zk === "zoom_pin_step{$idx}_label" && $zv !== '') $zoom_pins[$idx]['label'] = $zv;
                if ($zk === "zoom_map_step{$idx}_image" && !empty($zv) && file_exists(__DIR__ . '/' . ltrim($zv, '/'))) {
                    $zoom_pins[$idx]['image'] = ltrim($zv, '/');
                }
            }
        }
    }
} catch (Exception $e) {}
try {
    $res_sm = $conn->query("SELECT setting_key, setting_value FROM sm_settings WHERE setting_key IN ('purok1_map_image', 'purok2_map_image', 'purok3_map_image', 'legend_map_image')");
    if ($res_sm) {
        while ($row_sm = $res_sm->fetch_assoc()) {
            $sKey = $row_sm['setting_key'];
            $sVal = trim($row_sm['setting_value']);
            if (!empty($sVal) && file_exists(__DIR__ . '/' . ltrim($sVal, '/'))) {
                if ($sKey === 'purok1_map_image') $purok_map_images['purok1'] = ltrim($sVal, '/');
                if ($sKey === 'purok2_map_image') $purok_map_images['purok2'] = ltrim($sVal, '/');
                if ($sKey === 'purok3_map_image') $purok_map_images['purok3'] = ltrim($sVal, '/');
                if ($sKey === 'legend_map_image') $legend_map_image = ltrim($sVal, '/');
            }
        }
    }
} catch (Exception $e) {}

// Helpers to resolve public display URLs for images & icons
function get_public_legend_photo($path) {
    if (empty($path)) return "";
    if (strpos($path, "http://") === 0 || strpos($path, "https://") === 0) return $path;
    if (strpos($path, "Admin/") === 0) return $path;
    return "Admin/" . ltrim($path, "/");
}

function get_public_house_photo($path) {
    if (empty($path)) return "";
    if (strpos($path, "http://") === 0 || strpos($path, "https://") === 0) return $path;
    if (strpos($path, "Admin/") === 0) return $path;
    return "Admin/" . ltrim($path, "/");
}

function get_public_marker_image($path) {
    if (empty($path)) return "Images/Icons/marker-default.png";
    if (strpos($path, "http://") === 0 || strpos($path, "https://") === 0) return $path;
    if (strpos($path, "Admin/") === 0) return $path;
    return "Admin/" . ltrim($path, "/");
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
        </nav>

        <!-- Mobile Menu Hamburger Button -->
        <button class="mobile-menu-toggle" id="mobileMenuToggle" type="button" aria-label="Toggle navigation menu">
            <span class="bar bar-1"></span>
            <span class="bar bar-2"></span>
            <span class="bar bar-3"></span>
        </button>
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
                                <div class="purok-pill" onclick="changeMapImage('<?= htmlspecialchars($purok_map_images['purok1']) ?>', 'purok1')">Purok Mesaya</div>
                                <div class="purok-pill" onclick="changeMapImage('<?= htmlspecialchars($purok_map_images['purok2']) ?>', 'purok2')">Purok Metamed</div>
                                <div class="purok-pill" onclick="changeMapImage('<?= htmlspecialchars($purok_map_images['purok3']) ?>', 'purok3')">Purok Melitebangay</div>
                                <div class="purok-pill aerial-btn-pill" style="background-color: #000000; color: #ffffff; border-color: #000000;" onclick="showAerialSpotMap()"><i class="fa-solid fa-map-pin"></i> Aerial Spot Map</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Spot Map Image (Now on Right) -->
                <div class="spotmap-main">
                    <div class="map-container" id="publicMapContainer" style="position: relative; width: 100%; max-height: calc(100vh - 140px); display: flex; justify-content: center; overflow: hidden; border-radius: 28px;">
                        <img src="Images/Map/Philippines.png" alt="Barangay Tabon Spot Map Full View" class="full-spot-map-img" id="mainSpotMapImage" style="cursor: default; width: 100%; object-fit: cover;">
                        
                        <!-- Interactive Zoom-In Pin Overlay -->
                        <div id="barangay-pin" class="interactive-map-pin" style="top: <?= htmlspecialchars($zoom_pins[0]['top']) ?>; left: <?= htmlspecialchars($zoom_pins[0]['left']) ?>; display: block;" title="Click pin to zoom into <?= htmlspecialchars($zoom_pins[0]['label']) ?>">
                            <div class="pin-badge"><i class="fa-solid fa-magnifying-glass-plus"></i> <span id="pin-badge-text"><?= htmlspecialchars($zoom_pins[0]['label']) ?></span></div>
                            <div class="pin-marker-pulse"></div>
                            <i class="fa-solid fa-location-dot pin-icon"></i>
                        </div>

                        <!-- Custom User Dropped Pins Layer -->
                        <div id="custom-pins-layer" class="custom-pins-layer" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 25;"></div>

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
                                <div class="saved-legend-marker public-legend-marker draggable-map-pin" 
                                     id="public-marker-<?= (int)$leg['id'] ?>"
                                     style="top: <?= $top ?>%; left: <?= $left ?>%;"
                                     data-id="<?= (int)$leg['id'] ?>"
                                     data-pin-key="legend_<?= (int)$leg['id'] ?>"
                                     data-initial-top="<?= $top ?>"
                                     data-initial-left="<?= $left ?>"
                                     data-top="<?= $top ?>"
                                     data-left="<?= $left ?>"
                                     data-name="<?= $name_safe ?>"
                                     data-title="<?= $name_safe ?>"
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
                                <div class="saved-marker draggable-map-pin" 
                                     data-pin-key="purok1_house_<?= htmlspecialchars($m['house_number']) ?>"
                                     data-initial-top="<?= htmlspecialchars($m['coordinate_y']) ?>"
                                     data-initial-left="<?= htmlspecialchars($m['coordinate_x']) ?>"
                                     data-top="<?= htmlspecialchars($m['coordinate_y']) ?>"
                                     data-left="<?= htmlspecialchars($m['coordinate_x']) ?>"
                                     data-title="House #<?= htmlspecialchars($m['house_number']) ?>"
                                     style="position: absolute; left: <?= htmlspecialchars($m['coordinate_x']) ?>%; top: <?= htmlspecialchars($m['coordinate_y']) ?>%; transform: translate(-50%, -100%); z-index: 10;">
                                    <img src="<?= htmlspecialchars(get_public_marker_image($m['marker_image'] ?? '')) ?>" alt="Marker" draggable="false" style="width: <?= htmlspecialchars($m['marker_width']) ?>px; height: <?= htmlspecialchars($m['marker_height']) ?>px; cursor: grab; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3)); user-select: none; -webkit-user-drag: none;" title="House #<?= htmlspecialchars($m['house_number']) ?>" data-image="<?= htmlspecialchars(get_public_house_photo($m['house_image'] ?? '')) ?>" data-house="<?= htmlspecialchars($m['house_number']) ?>" data-husband="<?= htmlspecialchars($m['husband_name']) ?>" data-spouse="<?= htmlspecialchars($m['spouse_name'] ?? '') ?>" data-children="<?= htmlspecialchars($m['children'] ?? '[]') ?>" onclick="openPublicHouseholdModal(this)">
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Purok 2 Markers -->
                        <div id="purok2-markers" style="display: none;">
                            <?php foreach($purok2 as $m): ?>
                                <div class="saved-marker draggable-map-pin" 
                                     data-pin-key="purok2_house_<?= htmlspecialchars($m['house_number']) ?>"
                                     data-initial-top="<?= htmlspecialchars($m['coordinate_y']) ?>"
                                     data-initial-left="<?= htmlspecialchars($m['coordinate_x']) ?>"
                                     data-top="<?= htmlspecialchars($m['coordinate_y']) ?>"
                                     data-left="<?= htmlspecialchars($m['coordinate_x']) ?>"
                                     data-title="House #<?= htmlspecialchars($m['house_number']) ?>"
                                     style="position: absolute; left: <?= htmlspecialchars($m['coordinate_x']) ?>%; top: <?= htmlspecialchars($m['coordinate_y']) ?>%; transform: translate(-50%, -100%); z-index: 10;">
                                    <img src="<?= htmlspecialchars(get_public_marker_image($m['marker_image'] ?? '')) ?>" alt="Marker" draggable="false" style="width: <?= htmlspecialchars($m['marker_width']) ?>px; height: <?= htmlspecialchars($m['marker_height']) ?>px; cursor: grab; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3)); user-select: none; -webkit-user-drag: none;" title="House #<?= htmlspecialchars($m['house_number']) ?>" data-image="<?= htmlspecialchars(get_public_house_photo($m['house_image'] ?? '')) ?>" data-house="<?= htmlspecialchars($m['house_number']) ?>" data-husband="<?= htmlspecialchars($m['husband_name']) ?>" data-spouse="<?= htmlspecialchars($m['spouse_name'] ?? '') ?>" data-children="<?= htmlspecialchars($m['children'] ?? '[]') ?>" onclick="openPublicHouseholdModal(this)">
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Purok 3 Markers -->
                        <div id="purok3-markers" style="display: none;">
                            <?php foreach($purok3 as $m): ?>
                                <div class="saved-marker draggable-map-pin" 
                                     data-pin-key="purok3_house_<?= htmlspecialchars($m['house_number']) ?>"
                                     data-initial-top="<?= htmlspecialchars($m['coordinate_y']) ?>"
                                     data-initial-left="<?= htmlspecialchars($m['coordinate_x']) ?>"
                                     data-top="<?= htmlspecialchars($m['coordinate_y']) ?>"
                                     data-left="<?= htmlspecialchars($m['coordinate_x']) ?>"
                                     data-title="House #<?= htmlspecialchars($m['house_number']) ?>"
                                     style="position: absolute; left: <?= htmlspecialchars($m['coordinate_x']) ?>%; top: <?= htmlspecialchars($m['coordinate_y']) ?>%; transform: translate(-50%, -100%); z-index: 10;">
                                    <img src="<?= htmlspecialchars(get_public_marker_image($m['marker_image'] ?? '')) ?>" alt="Marker" draggable="false" style="width: <?= htmlspecialchars($m['marker_width']) ?>px; height: <?= htmlspecialchars($m['marker_height']) ?>px; cursor: grab; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3)); user-select: none; -webkit-user-drag: none;" title="House #<?= htmlspecialchars($m['house_number']) ?>" data-image="<?= htmlspecialchars(get_public_house_photo($m['house_image'] ?? '')) ?>" data-house="<?= htmlspecialchars($m['house_number']) ?>" data-husband="<?= htmlspecialchars($m['husband_name']) ?>" data-spouse="<?= htmlspecialchars($m['spouse_name'] ?? '') ?>" data-children="<?= htmlspecialchars($m['children'] ?? '[]') ?>" onclick="openPublicHouseholdModal(this)">
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

    <!-- Image Toast Modal -->
    <div id="legendToast" class="legend-toast-overlay" onclick="closeLegendToast()">
        <div class="legend-toast-content" onclick="event.stopPropagation()">
            <span class="close-toast" onclick="closeLegendToast()">&times;</span>
            <h3 id="toastTitle" style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">Location Image</h3>
            <div id="toastCoordinates" style="font-size:0.82rem; color:#2c6e49; font-weight:700; font-family:monospace; margin-bottom:12px; display:none;"></div>
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
                <div class="info-row">
                    <span class="info-label">Coordinates</span>
                    <span class="info-value" id="modalCoordinates" style="font-family: monospace; color: #2c6e49; font-weight: 700;"></span>
                </div>
                <div class="info-row" style="flex-direction: column; align-items: flex-start; gap: 8px;">
                    <span class="info-label" style="font-weight: 600; color: #475569; font-size: 15px;">Children / Dependents</span>
                    <div class="info-value" id="modalChildrenList" style="width: 100%; text-align: left;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Drop New Pin Modal (Optional Map Feature) -->
    <div id="dropPinModal" class="household-modal-overlay" style="display: none; z-index: 10000;" onclick="closeDropPinModal()">
        <div class="household-modal-content" style="max-width: 480px; text-align: left;" onclick="event.stopPropagation()">
            <span class="close-toast" onclick="closeDropPinModal()">&times;</span>
            <div class="modal-header" style="text-align: left; margin-bottom: 16px;">
                <h2 style="font-size: 1.35rem; color: #223c3b; margin: 0 0 4px 0;"><i class="fa-solid fa-map-pin" style="color: #2c6e49;"></i> Drop Pin & Attach Image</h2>
                <p style="margin: 0; color: #64748b; font-size: 0.88rem;">Plot a custom location pin onto this map view</p>
            </div>
            
            <form id="dropPinForm" onsubmit="handleSaveCustomPin(event)">
                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-weight: 600; font-size: 0.88rem; color: #334155; margin-bottom: 6px;">Pin Title / Label <span style="color:#e11d48;">*</span></label>
                    <input type="text" id="customPinTitle" placeholder="e.g. Barangay Health Station, Resident Unit" required style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.92rem; box-sizing: border-box; outline: none;">
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-weight: 600; font-size: 0.88rem; color: #334155; margin-bottom: 6px;"><i class="fa-solid fa-camera"></i> Attach Image (Location Photo) <span style="color:#e11d48;">*</span></label>
                    <input type="file" id="customPinImage" accept="image/jpeg,image/png,image/webp" required style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.88rem; box-sizing: border-box;">
                    <small style="color: #64748b; font-size: 0.78rem; display: block; margin-top: 4px;">Accepts JPG, PNG, WEBP (Max 5 MB). Appears in pin card and public gallery.</small>
                    
                    <!-- Thumbnail Preview Before Saving -->
                    <div id="customPinThumbBox" style="display: none; margin-top: 10px; align-items: center; gap: 12px; padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                        <img id="customPinThumbImg" src="" alt="Thumbnail preview" style="width: 72px; height: 72px; object-fit: cover; border-radius: 6px; border: 2px solid #223c3b; box-shadow: 0 2px 6px rgba(0,0,0,0.12);">
                        <div style="flex: 1; min-width: 0;">
                            <div id="customPinFileName" style="font-size: 0.84rem; font-weight: 600; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></div>
                            <div id="customPinFileSize" style="font-size: 0.78rem; color: #64748b; margin-top: 2px;"></div>
                            <button type="button" id="btnClearCustomPinFile" style="margin-top: 4px; background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; border-radius: 4px; padding: 2px 8px; font-size: 0.72rem; font-weight: 600; cursor: pointer;">Remove file</button>
                        </div>
                    </div>
                    <div id="customPinFileError" style="display: none; color: #dc2626; font-size: 0.82rem; margin-top: 6px; font-weight: 600; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 6px 10px;"></div>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-weight: 600; font-size: 0.88rem; color: #334155; margin-bottom: 6px;">Notes / Description (Optional)</label>
                    <textarea id="customPinNotes" rows="2" placeholder="Details about this location or point of interest..." style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.88rem; box-sizing: border-box; resize: vertical; outline: none;"></textarea>
                </div>

                <div style="background: #f1f5f9; padding: 10px 14px; border-radius: 8px; margin-bottom: 18px; font-size: 0.84rem; color: #475569; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 600;">Pin Coordinates:</span>
                    <span id="customPinCoordsText" style="font-weight: 700; color: #2c6e49; font-family: monospace;">Top: 50.00%, Left: 50.00%</span>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="button" onclick="closeDropPinModal()" style="flex: 1; background: #64748b; color: #ffffff; padding: 11px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.2s;">Cancel</button>
                    <button type="submit" style="flex: 2; background: #2c6e49; color: #ffffff; padding: 11px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; transition: background 0.2s; box-shadow: 0 2px 8px rgba(44,110,73,0.25);">
                        <i class="fa-solid fa-check"></i> Save Pin to Map
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Custom Pin Detail Modal -->
    <div id="customPinDetailModal" class="household-modal-overlay" style="display: none; z-index: 10000;" onclick="closeCustomPinDetailModal()">
        <div class="household-modal-content" style="max-width: 440px; text-align: left;" onclick="event.stopPropagation()">
            <span class="close-toast" onclick="closeCustomPinDetailModal()">&times;</span>
            <div class="modal-header" style="text-align: left; margin-bottom: 14px;">
                <h2 id="viewCustomPinTitle" style="font-size: 1.3rem; color: #223c3b; margin: 0 0 4px 0;">Pin Details</h2>
                <p id="viewCustomPinMapName" style="margin: 0; color: #64748b; font-size: 0.88rem;">Barangay Tabon Spot Map</p>
            </div>
            
            <div style="margin-bottom: 14px; border-radius: 10px; overflow: hidden; border: 1px solid #cbd5e1; max-height: 220px; background: #0f172a;">
                <img id="viewCustomPinImg" src="" alt="Pin Location" style="width: 100%; height: 220px; object-fit: cover; display: block;">
            </div>

            <div class="modal-info-card" style="margin-top: 0;">
                <div class="info-row">
                    <span class="info-label">Coordinates</span>
                    <span class="info-value" id="viewCustomPinCoords" style="font-family: monospace; color: #2c6e49; font-weight: 700;"></span>
                </div>
                <div class="info-row" id="viewCustomPinNotesRow">
                    <span class="info-label">Notes</span>
                    <span class="info-value" id="viewCustomPinNotes"></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date Added</span>
                    <span class="info-value" id="viewCustomPinDate"></span>
                </div>
            </div>

            <div style="margin-top: 16px; display: flex; gap: 10px;">
                <button type="button" id="btnDeleteCustomPin" style="flex: 1; background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; padding: 10px; border-radius: 8px; font-weight: 600; font-size: 0.88rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;">
                    <i class="fa-solid fa-trash"></i> Delete Pin
                </button>
                <button type="button" onclick="closeCustomPinDetailModal()" style="flex: 1; background: #223c3b; color: #ffffff; border: none; padding: 10px; border-radius: 8px; font-weight: 600; font-size: 0.88rem; cursor: pointer;">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div id="spotmapToast" class="spotmap-toast-notification"></div>

    <script>
        // =========================================================================
        // SPOT MAP CORE INTERACTION & DRAGGABLE PINS ENGINE
        // =========================================================================
        let currentStepIndex = 0;
        let isDropPinModeActive = false;
        let pendingDropCoords = { top: '50.00%', left: '50.00%' };
        let customPinImageDataUrl = '';

        function getCurrentMapKey() {
            const mapImg = document.getElementById('mainSpotMapImage');
            const src = (mapImg ? mapImg.src : '').toLowerCase();
            if (src.includes('purok1') || src.includes('purok 1') || src.includes('purok%201') || src.includes('mesaya')) return 'purok1';
            if (src.includes('purok2') || src.includes('purok 2') || src.includes('purok%202') || src.includes('metamed')) return 'purok2';
            if (src.includes('purok3') || src.includes('purok 3') || src.includes('purok%203') || src.includes('melitebangay')) return 'purok3';
            if (currentStepIndex === 4 || src.includes('aerial') || src.includes('legend') || src.includes('maruing')) return 'aerial';
            return 'philippines_' + currentStepIndex;
        }

        // --- Toast Notification Helper ---
        function showSpotMapToast(message, type = 'success') {
            const toast = document.getElementById('spotmapToast');
            if (!toast) return;
            toast.className = 'spotmap-toast-notification' + (type === 'error' ? ' error' : '');
            toast.innerHTML = `<i class="fa-solid ${type === 'error' ? 'fa-triangle-exclamation' : 'fa-circle-check'}"></i> <span>${message}</span>`;
            toast.classList.add('active');
            clearTimeout(toast._timer);
            toast._timer = setTimeout(() => {
                toast.classList.remove('active');
            }, 3500);
        }

        // --- Household Details Modal ---
        function openPublicHouseholdModal(el) {
            const markerDiv = el.closest('.saved-marker') || el;
            const img = el.getAttribute('data-image');
            const house = el.getAttribute('data-house');
            const husband = el.getAttribute('data-husband');
            const spouse = el.getAttribute('data-spouse');
            const rawChildren = el.getAttribute('data-children') || '[]';

            let childrenArr = [];
            try { childrenArr = JSON.parse(rawChildren); } catch(e) {}
            if (!Array.isArray(childrenArr)) childrenArr = [];

            const modalImg = document.getElementById('modalHouseImage');
            if (modalImg) modalImg.src = img || 'Images/Icons/house-placeholder.png';
            document.getElementById('modalHouseNumber').innerText = house || 'N/A';
            document.getElementById('modalHusbandName').innerText = husband || 'N/A';
            document.getElementById('modalSpouseName').innerText = spouse || 'N/A';

            // Coordinates update
            const coordsEl = document.getElementById('modalCoordinates');
            if (coordsEl) {
                const topPos = markerDiv.style.top || markerDiv.getAttribute('data-top') || '50%';
                const leftPos = markerDiv.style.left || markerDiv.getAttribute('data-left') || '50%';
                coordsEl.innerText = `Top: ${topPos}, Left: ${leftPos}`;
            }

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

        function closeHouseholdDetails() {
            document.getElementById('householdModal').classList.remove('active');
        }

        // --- Landmark Toast & Details ---
        function showLegendToast(name, photo, icon, color, topCoord, leftCoord) {
            const titleEl = document.getElementById('toastTitle');
            const imgEl = document.getElementById('toastImage');
            const fallbackEl = document.getElementById('toastFallback');
            const fallbackIcon = document.getElementById('toastFallbackIcon');
            const coordsEl = document.getElementById('toastCoordinates');
            const modal = document.getElementById('legendToast');

            if (titleEl) titleEl.innerText = name || 'Landmark Location';

            if (coordsEl) {
                if (topCoord && leftCoord) {
                    coordsEl.innerText = `Coordinates: Top: ${topCoord}, Left: ${leftCoord}`;
                    coordsEl.style.display = 'block';
                } else {
                    coordsEl.style.display = 'none';
                }
            }

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
            if (currentStepIndex !== 4) {
                applyMapStep(4);
            }
            const pinEl = document.getElementById('public-marker-' + id);
            let topCoord = pinEl ? pinEl.style.top : '';
            let leftCoord = pinEl ? pinEl.style.left : '';
            if (pinEl) {
                document.querySelectorAll('.saved-legend-marker').forEach(m => m.classList.remove('highlight-ping'));
                pinEl.classList.add('highlight-ping');
                setTimeout(() => { pinEl.classList.remove('highlight-ping'); }, 3500);
            }
            showLegendToast(name, photo, icon, color, topCoord, leftCoord);
        }

        function openPublicLandmarkModal(el) {
            const name = el.getAttribute('data-name') || 'Landmark';
            const photo = el.getAttribute('data-photo') || '';
            const icon = el.getAttribute('data-icon') || '';
            const color = el.getAttribute('data-color') || '#4f46e5';
            const topCoord = el.style.top || el.getAttribute('data-top') || '';
            const leftCoord = el.style.left || el.getAttribute('data-left') || '';

            document.querySelectorAll('.saved-legend-marker').forEach(m => m.classList.remove('highlight-ping'));
            el.classList.add('highlight-ping');
            setTimeout(() => { el.classList.remove('highlight-ping'); }, 3000);

            showLegendToast(name, photo, icon, color, topCoord, leftCoord);

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

        // =========================================================================
        // PERSISTENCE ENGINE (LOCALSTORAGE)
        // =========================================================================
        const STORAGE_KEY_COORDS = 'brgy_tabon_adjusted_pin_coords';
        const STORAGE_KEY_CUSTOM_PINS = 'brgy_tabon_custom_pins';

        function getSavedCoordinatesMap() {
            try {
                const data = localStorage.getItem(STORAGE_KEY_COORDS);
                return data ? JSON.parse(data) : {};
            } catch(e) {
                return {};
            }
        }

        function savePinCoordinate(pinKey, topPercent, leftPercent) {
            if (!pinKey) return;
            const map = getSavedCoordinatesMap();
            map[pinKey] = {
                top: topPercent.toFixed(2) + '%',
                left: leftPercent.toFixed(2) + '%'
            };
            try {
                localStorage.setItem(STORAGE_KEY_COORDS, JSON.stringify(map));
            } catch(e) {}
        }

        function restorePersistedPinPositions() {
            const savedMap = getSavedCoordinatesMap();
            if (!savedMap || Object.keys(savedMap).length === 0) return;

            document.querySelectorAll('.draggable-map-pin').forEach(pin => {
                const key = pin.getAttribute('data-pin-key');
                if (key && savedMap[key]) {
                    pin.style.top = savedMap[key].top;
                    pin.style.left = savedMap[key].left;
                    pin.setAttribute('data-top', savedMap[key].top);
                    pin.setAttribute('data-left', savedMap[key].left);
                }
            });
        }

        function resetAllPinPositions() {
            if (confirm('Reset all pin coordinates back to their default database positions?')) {
                localStorage.removeItem(STORAGE_KEY_COORDS);
                document.querySelectorAll('.draggable-map-pin').forEach(pin => {
                    const initTop = pin.getAttribute('data-initial-top');
                    const initLeft = pin.getAttribute('data-initial-left');
                    if (initTop !== null && initLeft !== null) {
                        pin.style.top = initTop + (initTop.includes('%') ? '' : '%');
                        pin.style.left = initLeft + (initLeft.includes('%') ? '' : '%');
                        pin.setAttribute('data-top', pin.style.top);
                        pin.setAttribute('data-left', pin.style.left);
                    }
                });
                showSpotMapToast('Pin positions reset to default coordinates.', 'success');
            }
        }

        // =========================================================================
        // DRAGGABLE PINS ENGINE (MOUSE & TOUCH WITH LIFT EFFECT)
        // =========================================================================
        function makePinDraggable(el, onDragEndCallback) {
            if (el._hasDraggableAttached) return;
            el._hasDraggableAttached = true;

            const mapContainer = document.getElementById('publicMapContainer') || document.querySelector('.map-container');
            let isDragging = false;
            let startX = 0, startY = 0;
            let movedDistance = 0;

            function onStart(e) {
                // If user is clicking in drop pin mode, ignore drag
                if (isDropPinModeActive) return;

                const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                const clientY = e.touches ? e.touches[0].clientY : e.clientY;

                startX = clientX;
                startY = clientY;
                movedDistance = 0;
                isDragging = false;

                window.addEventListener('mousemove', onMove, { passive: false });
                window.addEventListener('touchmove', onMove, { passive: false });
                window.addEventListener('mouseup', onEnd);
                window.addEventListener('touchend', onEnd);
            }

            function onMove(e) {
                if (!mapContainer) return;
                const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                const dx = clientX - startX;
                const dy = clientY - startY;
                movedDistance = Math.hypot(dx, dy);

                if (movedDistance > 6) {
                    if (e.cancelable) e.preventDefault();
                    if (!isDragging) {
                        isDragging = true;
                        el.classList.add('pin-is-dragging');
                    }

                    const rect = mapContainer.getBoundingClientRect();
                    let leftPercent = ((clientX - rect.left) / rect.width) * 100;
                    let topPercent = ((clientY - rect.top) / rect.height) * 100;

                    // Clamping within bounds
                    leftPercent = Math.max(1, Math.min(99, leftPercent));
                    topPercent = Math.max(2, Math.min(99, topPercent));

                    el.style.left = leftPercent.toFixed(2) + '%';
                    el.style.top = topPercent.toFixed(2) + '%';
                }
            }

            function onEnd(e) {
                window.removeEventListener('mousemove', onMove);
                window.removeEventListener('touchmove', onMove);
                window.removeEventListener('mouseup', onEnd);
                window.removeEventListener('touchend', onEnd);

                if (isDragging) {
                    el.classList.remove('pin-is-dragging');
                    isDragging = false;

                    const finalTop = parseFloat(el.style.top);
                    const finalLeft = parseFloat(el.style.left);
                    const pinKey = el.getAttribute('data-pin-key');
                    const pinTitle = el.getAttribute('data-title') || el.getAttribute('data-name') || 'Pin';

                    el.setAttribute('data-top', finalTop.toFixed(2) + '%');
                    el.setAttribute('data-left', finalLeft.toFixed(2) + '%');

                    if (typeof onDragEndCallback === 'function') {
                        onDragEndCallback(el, finalTop, finalLeft);
                    } else if (pinKey) {
                        savePinCoordinate(pinKey, finalTop, finalLeft);
                        showSpotMapToast(`Moved ${pinTitle} to Top: ${finalTop.toFixed(1)}%, Left: ${finalLeft.toFixed(1)}%`);
                    }

                    // Intercept any click event triggered right after drag
                    el._preventNextClick = true;
                    setTimeout(() => { el._preventNextClick = false; }, 300);
                }
            }

            el.addEventListener('mousedown', onStart);
            el.addEventListener('touchstart', onStart, { passive: true });

            // Block click if it was a drag
            el.addEventListener('click', function(e) {
                if (el._preventNextClick) {
                    e.stopImmediatePropagation();
                    e.preventDefault();
                }
            }, true);
        }

        function initDraggablePins() {
            document.querySelectorAll('.draggable-map-pin').forEach(pin => {
                makePinDraggable(pin);
            });
        }

        // =========================================================================
        // CUSTOM PINS DROP & IMAGE ATTACHMENT SYSTEM (OPTIONAL FEATURE)
        // =========================================================================
        function getCustomPins() {
            try {
                const raw = localStorage.getItem(STORAGE_KEY_CUSTOM_PINS);
                return raw ? JSON.parse(raw) : [];
            } catch(e) {
                return [];
            }
        }

        function saveCustomPinsToStorage(list) {
            try {
                localStorage.setItem(STORAGE_KEY_CUSTOM_PINS, JSON.stringify(list));
            } catch(e) {}
        }

        function toggleDropPinMode() {
            const mapContainer = document.getElementById('publicMapContainer');
            const btn = document.getElementById('btnToggleDropPin');
            const label = document.getElementById('dropPinBtnLabel');
            if (!mapContainer || !btn) return;

            isDropPinModeActive = !isDropPinModeActive;

            if (isDropPinModeActive) {
                mapContainer.classList.add('dropping-pin-mode');
                btn.classList.add('is-active');
                if (label) label.textContent = 'Click map to place pin (Cancel)';
                showSpotMapToast('Drop Pin Mode Active: Click anywhere on the map to place a pin.', 'success');
            } else {
                mapContainer.classList.remove('dropping-pin-mode');
                btn.classList.remove('is-active');
                if (label) label.textContent = '+ Drop New Pin';
            }
        }

        function openDropPinModal(topP, leftP) {
            pendingDropCoords = { top: topP.toFixed(2) + '%', left: leftP.toFixed(2) + '%' };
            const modal = document.getElementById('dropPinModal');
            const coordsText = document.getElementById('customPinCoordsText');
            const form = document.getElementById('dropPinForm');
            const thumbBox = document.getElementById('customPinThumbBox');
            const errBox = document.getElementById('customPinFileError');

            if (form) form.reset();
            if (thumbBox) thumbBox.style.display = 'none';
            if (errBox) { errBox.style.display = 'none'; errBox.textContent = ''; }
            customPinImageDataUrl = '';

            if (coordsText) coordsText.textContent = `Top: ${pendingDropCoords.top}, Left: ${pendingDropCoords.left}`;
            if (modal) modal.style.display = 'flex';
        }

        function closeDropPinModal() {
            const modal = document.getElementById('dropPinModal');
            if (modal) modal.style.display = 'none';
            if (isDropPinModeActive) toggleDropPinMode();
        }

        // Image validation for custom pin
        const customPinImgInput = document.getElementById('customPinImage');
        if (customPinImgInput) {
            customPinImgInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                const thumbBox = document.getElementById('customPinThumbBox');
                const thumbImg = document.getElementById('customPinThumbImg');
                const nameEl = document.getElementById('customPinFileName');
                const sizeEl = document.getElementById('customPinFileSize');
                const errBox = document.getElementById('customPinFileError');

                if (!file) {
                    if (thumbBox) thumbBox.style.display = 'none';
                    return;
                }

                const maxBytes = 5 * 1024 * 1024;
                const ext = file.name.split('.').pop().toLowerCase();
                const validExt = ['jpg', 'jpeg', 'png', 'webp'].includes(ext);
                const validType = ['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || validExt;

                if (!validType) {
                    if (errBox) {
                        errBox.textContent = 'Invalid format. Please select a JPG, PNG, or WEBP image.';
                        errBox.style.display = 'block';
                    }
                    customPinImgInput.value = '';
                    if (thumbBox) thumbBox.style.display = 'none';
                    return;
                }

                if (file.size > maxBytes) {
                    const mb = (file.size / (1024 * 1024)).toFixed(1);
                    if (errBox) {
                        errBox.textContent = `Image size is ${mb} MB. Maximum allowed is 5 MB.`;
                        errBox.style.display = 'block';
                    }
                    customPinImgInput.value = '';
                    if (thumbBox) thumbBox.style.display = 'none';
                    return;
                }

                if (errBox) {
                    errBox.style.display = 'none';
                    errBox.textContent = '';
                }

                const reader = new FileReader();
                reader.onload = function(ev) {
                    customPinImageDataUrl = ev.target.result;
                    if (thumbImg) thumbImg.src = customPinImageDataUrl;
                    if (nameEl) nameEl.textContent = file.name;
                    if (sizeEl) sizeEl.textContent = (file.size / 1024).toFixed(1) + ' KB';
                    if (thumbBox) thumbBox.style.display = 'flex';
                };
                reader.readAsDataURL(file);
            });

            const btnClearCustomFile = document.getElementById('btnClearCustomPinFile');
            if (btnClearCustomFile) {
                btnClearCustomFile.addEventListener('click', function() {
                    customPinImgInput.value = '';
                    customPinImageDataUrl = '';
                    const thumbBox = document.getElementById('customPinThumbBox');
                    if (thumbBox) thumbBox.style.display = 'none';
                });
            }
        }

        function handleSaveCustomPin(e) {
            e.preventDefault();
            const titleInput = document.getElementById('customPinTitle');
            const notesInput = document.getElementById('customPinNotes');
            const title = titleInput ? titleInput.value.trim() : '';
            const notes = notesInput ? notesInput.value.trim() : '';

            if (!title) {
                alert('Please enter a pin title.');
                return;
            }
            if (!customPinImageDataUrl) {
                alert('Please attach an image for this pin location.');
                return;
            }

            const currentKey = getCurrentMapKey();
            const newPin = {
                id: 'custom_' + Date.now(),
                mapKey: currentKey,
                title: title,
                image: customPinImageDataUrl,
                notes: notes,
                top: pendingDropCoords.top,
                left: pendingDropCoords.left,
                date: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
            };

            const pins = getCustomPins();
            pins.push(newPin);
            saveCustomPinsToStorage(pins);

            closeDropPinModal();
            renderCustomPins();
            showSpotMapToast(`Custom pin "${title}" added to map!`, 'success');
        }

        let selectedCustomPinId = null;

        function openCustomPinDetailModal(pin) {
            selectedCustomPinId = pin.id;
            document.getElementById('viewCustomPinTitle').textContent = pin.title || 'Pin Location';
            document.getElementById('viewCustomPinImg').src = pin.image || '';
            document.getElementById('viewCustomPinCoords').textContent = `Top: ${pin.top}, Left: ${pin.left}`;
            document.getElementById('viewCustomPinDate').textContent = pin.date || 'Recent';

            const notesEl = document.getElementById('viewCustomPinNotes');
            const notesRow = document.getElementById('viewCustomPinNotesRow');
            if (pin.notes && pin.notes.trim() !== '') {
                if (notesEl) notesEl.textContent = pin.notes;
                if (notesRow) notesRow.style.display = 'flex';
            } else {
                if (notesRow) notesRow.style.display = 'none';
            }

            const modal = document.getElementById('customPinDetailModal');
            if (modal) modal.style.display = 'flex';
        }

        function closeCustomPinDetailModal() {
            const modal = document.getElementById('customPinDetailModal');
            if (modal) modal.style.display = 'none';
            selectedCustomPinId = null;
        }

        const btnDeleteCustom = document.getElementById('btnDeleteCustomPin');
        if (btnDeleteCustom) {
            btnDeleteCustom.addEventListener('click', function() {
                if (!selectedCustomPinId) return;
                if (confirm('Permanently delete this custom pin from the map?')) {
                    let pins = getCustomPins();
                    pins = pins.filter(p => p.id !== selectedCustomPinId);
                    saveCustomPinsToStorage(pins);
                    closeCustomPinDetailModal();
                    renderCustomPins();
                    showSpotMapToast('Custom pin removed from map.', 'success');
                }
            });
        }

        function renderCustomPins() {
            const layer = document.getElementById('custom-pins-layer');
            if (!layer) return;
            layer.innerHTML = '';

            const currentKey = getCurrentMapKey();
            const pins = getCustomPins();

            pins.forEach(pin => {
                // Display pins matching current view, or if viewing whole map
                if (pin.mapKey && pin.mapKey !== currentKey) return;

                const pinEl = document.createElement('div');
                pinEl.className = 'custom-dropped-marker draggable-map-pin';
                pinEl.id = pin.id;
                pinEl.style.left = pin.left;
                pinEl.style.top = pin.top;
                pinEl.setAttribute('data-pin-key', pin.id);
                pinEl.setAttribute('data-title', pin.title);
                pinEl.setAttribute('data-top', pin.top);
                pinEl.setAttribute('data-left', pin.left);
                pinEl.style.pointerEvents = 'auto';

                pinEl.innerHTML = `
                    <div class="custom-pin-bubble">
                        <i class="fa-solid fa-camera" style="font-size: 0.65rem; color: #f59e0b;"></i>
                        <span>${pin.title}</span>
                    </div>
                    <div class="custom-pin-head">
                        <img src="${pin.image}" alt="${pin.title}">
                    </div>
                    <div class="custom-pin-point"></div>
                `;

                pinEl.addEventListener('click', function(e) {
                    if (pinEl._preventNextClick) return;
                    openCustomPinDetailModal(pin);
                });

                // Attach draggable engine with position update to localStorage
                makePinDraggable(pinEl, function(el, finalTop, finalLeft) {
                    pin.top = finalTop.toFixed(2) + '%';
                    pin.left = finalLeft.toFixed(2) + '%';
                    el.setAttribute('data-top', pin.top);
                    el.setAttribute('data-left', pin.left);
                    
                    const allPins = getCustomPins();
                    const idx = allPins.findIndex(p => p.id === pin.id);
                    if (idx !== -1) {
                        allPins[idx].top = pin.top;
                        allPins[idx].left = pin.left;
                        saveCustomPinsToStorage(allPins);
                    }
                    showSpotMapToast(`Moved ${pin.title} (Top: ${pin.top}, Left: ${pin.left})`);
                });

                layer.appendChild(pinEl);
            });
        }

        // Click on map container to drop pin
        const publicMapContainer = document.getElementById('publicMapContainer');
        if (publicMapContainer) {
            publicMapContainer.addEventListener('click', function(e) {
                if (!isDropPinModeActive) return;
                // Ignore clicks on markers themselves
                if (e.target.closest('.saved-marker') || e.target.closest('.saved-legend-marker') || e.target.closest('.custom-dropped-marker')) {
                    return;
                }

                const rect = publicMapContainer.getBoundingClientRect();
                const leftP = Math.max(2, Math.min(98, ((e.clientX - rect.left) / rect.width) * 100));
                const topP = Math.max(2, Math.min(98, ((e.clientY - rect.top) / rect.height) * 100));

                openDropPinModal(topP, leftP);
            });
        }

        // Attach Toolbar buttons
        const btnToggleDrop = document.getElementById('btnToggleDropPin');
        if (btnToggleDrop) {
            btnToggleDrop.addEventListener('click', function(e) {
                e.preventDefault();
                toggleDropPinMode();
            });
        }

        const btnResetPositions = document.getElementById('btnResetPinPositions');
        if (btnResetPositions) {
            btnResetPositions.addEventListener('click', function(e) {
                e.preventDefault();
                resetAllPinPositions();
            });
        }

        // =========================================================================
        // STEP-BY-STEP MAP ZOOM SEQUENCE CONFIGURATION
        // =========================================================================
        const mapSteps = [
            {
                image: '<?= htmlspecialchars($zoom_pins[0]['image']) ?>',
                stepLabel: 'Philippines &rarr; Click pin on <?= htmlspecialchars($zoom_pins[0]['label']) ?>',
                pin: {
                    show: true,
                    left: '<?= htmlspecialchars($zoom_pins[0]['left']) ?>',
                    top: '<?= htmlspecialchars($zoom_pins[0]['top']) ?>',
                    title: 'Click pin to zoom into <?= htmlspecialchars($zoom_pins[0]['label']) ?>',
                    label: '<?= htmlspecialchars($zoom_pins[0]['label']) ?>'
                }
            },
            {
                image: '<?= htmlspecialchars($zoom_pins[1]['image']) ?>',
                stepLabel: 'Mindanao &rarr; Click pin on <?= htmlspecialchars($zoom_pins[1]['label']) ?>',
                pin: {
                    show: true,
                    left: '<?= htmlspecialchars($zoom_pins[1]['left']) ?>',
                    top: '<?= htmlspecialchars($zoom_pins[1]['top']) ?>',
                    title: 'Click pin to zoom into <?= htmlspecialchars($zoom_pins[1]['label']) ?>',
                    label: '<?= htmlspecialchars($zoom_pins[1]['label']) ?>'
                }
            },
            {
                image: '<?= htmlspecialchars($zoom_pins[2]['image']) ?>',
                stepLabel: 'Zamboanga del Sur &rarr; Click pin on <?= htmlspecialchars($zoom_pins[2]['label']) ?>',
                pin: {
                    show: true,
                    left: '<?= htmlspecialchars($zoom_pins[2]['left']) ?>',
                    top: '<?= htmlspecialchars($zoom_pins[2]['top']) ?>',
                    title: 'Click pin to zoom into <?= htmlspecialchars($zoom_pins[2]['label']) ?>',
                    label: '<?= htmlspecialchars($zoom_pins[2]['label']) ?>'
                }
            },
            {
                image: '<?= htmlspecialchars($zoom_pins[3]['image']) ?>',
                stepLabel: 'Lapuyan &rarr; Click pin on <?= htmlspecialchars($zoom_pins[3]['label']) ?>',
                pin: {
                    show: true,
                    left: '<?= htmlspecialchars($zoom_pins[3]['left']) ?>',
                    top: '<?= htmlspecialchars($zoom_pins[3]['top']) ?>',
                    title: 'Click pin to view Aerial Spot Map',
                    label: '<?= htmlspecialchars($zoom_pins[3]['label']) ?>'
                }
            },
            {
                image: '<?= htmlspecialchars($legend_map_image) ?>',
                stepLabel: 'Barangay Tabon Aerial Spot Map',
                pin: {
                    show: false
                }
            }
        ];

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

            restorePersistedPinPositions();
            renderCustomPins();
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

        function resetToFullMap() {
            applyMapStep(0);
        }

        function showAerialSpotMap() {
            applyMapStep(4);
        }

        function changeMapImage(imageUrl, purokType) {
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

            const urlLower = (imageUrl || '').toLowerCase();
            if (purokType === 'purok1' || urlLower.includes('purok 1') || urlLower.includes('purok1')) {
                if (p1) p1.style.display = 'block';
            } else if (purokType === 'purok2' || urlLower.includes('purok 2') || urlLower.includes('purok2')) {
                if (p2) p2.style.display = 'block';
            } else if (purokType === 'purok3' || urlLower.includes('purok 3') || urlLower.includes('purok3')) {
                if (p3) p3.style.display = 'block';
            }

            restorePersistedPinPositions();
            renderCustomPins();
        }

        // Initialize engine on DOM load
        document.addEventListener('DOMContentLoaded', function() {
            initDraggablePins();
            restorePersistedPinPositions();
            renderCustomPins();

            const params = new URLSearchParams(window.location.search);
            const purokParam = (params.get('purok') || '').toLowerCase();
            if (purokParam === '1' || purokParam === 'mesaya' || purokParam === 'purok1') {
                changeMapImage('<?= htmlspecialchars($purok_map_images['purok1']) ?>', 'purok1');
            } else if (purokParam === '2' || purokParam === 'metamed' || purokParam === 'purok2') {
                changeMapImage('<?= htmlspecialchars($purok_map_images['purok2']) ?>', 'purok2');
            } else if (purokParam === '3' || purokParam === 'melitebangay' || purokParam === 'purok3') {
                changeMapImage('<?= htmlspecialchars($purok_map_images['purok3']) ?>', 'purok3');
            } else if (params.get('view') === 'aerial' || window.location.hash === '#aerial' || params.get('view') === 'legend') {
                showAerialSpotMap();
            }
        });
    </script>
    <script src="js/main.js?v=<?= time() ?>"></script>
    <!-- Mobile Navigation Drawer Backdrop -->
    <div class="mobile-nav-backdrop"></div>
</body>
</html>
