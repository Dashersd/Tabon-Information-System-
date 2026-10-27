<?php
require_once 'db_connect.php';
$purok1 = [];
$purok2 = [];
$purok3 = [];
try {
    $stmt1 = $pdo->prepare("SELECT * FROM purok1_locations"); $stmt1->execute(); $purok1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    $stmt2 = $pdo->prepare("SELECT * FROM purok2_locations"); $stmt2->execute(); $purok2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    $stmt3 = $pdo->prepare("SELECT * FROM purok3_locations"); $stmt3->execute(); $purok3 = $stmt3->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Tabon - Spot Map</title>
    
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
            <img src="Barangay Logo/Logo.png" alt="Logo" class="brand-logo">
            <div>
                <div class="brand-title">Barangay Tabon</div>
                <div class="brand-sub">Together for a Stronger Community</div>
            </div>
        </div>

        <nav>
            <a href="index.php" class="nav-item">
                <i class="fa-solid fa-house"></i>
                <span>Home</span>
            </a>
            <a href="about.php" class="nav-item">
                <i class="fa-solid fa-circle-info"></i>
                <span>About Us</span>
            </a>
            <div class="nav-item dropdown">
                <a href="#" class="dropdown-toggle" style="text-decoration:none;">
                    <i class="fa-solid fa-user-tie"></i>
                    <span>Officials <i class="fa-solid fa-chevron-down" style="font-size:10px; margin-left:3px;"></i></span>
                </a>
                <div class="dropdown-menu">
                    <a href="officials.php" class="dropdown-item">Barangay Officials</a>
                    <a href="skofficials.php" class="dropdown-item">SK Officials</a>
                </div>
            </div>
            <a href="gallery.php" class="nav-item">
                <i class="fa-regular fa-image"></i>
                <span>Gallery</span>
            </a>
            <a href="service.php" class="nav-item">
                <i class="fa-solid fa-bell-concierge"></i>
                <span>Service</span>
            </a>
            <a href="announcement.php" class="nav-item">
                <i class="fa-solid fa-bullhorn"></i>
                <span>Announcement</span>
            </a>
            <a href="spotmap.php" class="nav-item active">
                <i class="fa-solid fa-map"></i>
                <span>Spot Map</span>
            </a>
            <a href="contact.php" class="nav-item">
                <i class="fa-regular fa-envelope"></i>
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
                                <div class="legend-item" onclick="showLegendToast('Barangay Hall')"><i class="fa-solid fa-building" style="color:#4a6cf7;"></i> Barangay Hall</div>
                                <div class="legend-item" onclick="showLegendToast('School')"><i class="fa-solid fa-book-open" style="color:#28a745;"></i> School</div>
                                <div class="legend-item" onclick="showLegendToast('Court')"><i class="fa-solid fa-basketball" style="color:#fca311;"></i> Court</div>
                            </div>
                        </div>

                        <!-- Puroks Box -->
                        <div class="info-card">
                            <h3 class="card-title"><i class="fa-solid fa-location-dot" style="color: #fca311;"></i> 3 Puroks of Tabon</h3>
                            <div class="purok-list">
                                <div class="purok-pill" onclick="changeMapImage('Images/Purok/Purok 1.jpg')">Purok 1</div>
                                <div class="purok-pill" onclick="changeMapImage('Images/Purok/Purok 2.jpg')">Purok 2</div>
                                <div class="purok-pill" onclick="changeMapImage('Images/Purok/Purok 3.jpg')">Purok 3</div>
                                <div class="purok-pill" style="background-color: #f1f5f9; border-color: #cbd5e1; color: #475569;" onclick="changeMapImage('Images/Map/Philippines.png')">Full Map</div>
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
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="officials.php">Officials</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                </ul>
            </div>
            
            <div class="footer-contact">
                <h3>Contact Info</h3>
                <ul>
                    <li><i class="fa-solid fa-location-dot"></i> Barangay Tabon, Town, Province</li>
                    <li><i class="fa-solid fa-phone"></i> (123) 456-7890</li>
                    <li><i class="fa-solid fa-envelope"></i> tabon@example.com</li>
                </ul>
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
            <h3 id="toastTitle">Location Image</h3>
            <div class="toast-image-container">
                <img id="toastImage" src="" alt="Random Location Image">
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
        function showLegendToast(title) {
            document.getElementById('toastTitle').innerText = title;
            // Generate a random image using picsum for demonstration
            const randomId = Math.floor(Math.random() * 1000);
            document.getElementById('toastImage').src = `https://picsum.photos/id/${randomId}/600/400`;
            document.getElementById('legendToast').classList.add('active');
        }

        function closeLegendToast() {
            document.getElementById('legendToast').classList.remove('active');
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
                image: 'Images/Map/Zamboanga del Sur.jpg',
                stepLabel: 'Zamboanga del Sur &rarr; Click pin on Lapuyan',
                pin: {
                    show: true,
                    left: '47.3%',
                    top: '58.3%',
                    title: 'Click pin to zoom into Lapuyan',
                    label: 'Lapuyan'
                }
            },
            {
                image: 'Images/Map/Lapuyan.gif',
                stepLabel: 'Lapuyan &rarr; Click pin on Barangay Tabon',
                pin: {
                    show: true,
                    left: '50%',
                    top: '50%',
                    title: 'Click pin to view 3D Spot Map',
                    label: 'Barangay Tabon'
                }
            },
            {
                image: 'Images/ChatGPT Image Oct 1, 2026, 07_50_28 AM.png',
                stepLabel: 'Barangay Tabon 3D Spot Map (Interactive)',
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
                mapImg.style.cursor = 'default'; // Map background is NOT clickable
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
            if (p1) p1.style.display = 'none';
            if (p2) p2.style.display = 'none';
            if (p3) p3.style.display = 'none';
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

        function changeMapImage(imageUrl) {
            const mapImg = document.getElementById('mainSpotMapImage');
            const pin = document.getElementById('barangay-pin');
            const p1 = document.getElementById('purok1-markers');
            const p2 = document.getElementById('purok2-markers');
            const p3 = document.getElementById('purok3-markers');

            if (p1) p1.style.display = 'none';
            if (p2) p2.style.display = 'none';
            if (p3) p3.style.display = 'none';

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
    </script>
    <script src="js/main.js"></script>
</body>
</html>
