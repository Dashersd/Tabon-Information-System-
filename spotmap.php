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
            <a href="index.php" class="nav-item ">
                <i class="fa-solid fa-house"></i>
                <span>Home</span>
            </a>
            <a href="about.php" class="nav-item ">
                <i class="fa-solid fa-circle-info"></i>
                <span>About Us</span>
            </a>
            <a href="gallery.php" class="nav-item ">
                <i class="fa-regular fa-image"></i>
                <span>Gallery</span>
            </a>
            <div class="nav-item dropdown  ">
                <a href="#" class="dropdown-toggle" style="text-decoration:none;">
                    <i class="fa-solid fa-user-tie"></i>
                    <span>Officials <i class="fa-solid fa-chevron-down" style="font-size:10px; margin-left:3px;"></i></span>
                </a>
                <div class="dropdown-menu">
                    <a href="officials.php" class="dropdown-item">Barangay Officials</a>
                    <a href="skofficials.php" class="dropdown-item">SK Officials</a>
                </div>
            </div>
            <a href="spotmap.php" class="nav-item active">
                <i class="fa-solid fa-map"></i>
                <span>Spot Map</span>
            </a>
            <a href="contact.php" class="nav-item ">
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
                    <div class="map-container" style="position: relative; width: 100%; max-height: calc(100vh - 140px); display: flex; justify-content: center;">
                        <img src="Images/Map/Philippines.png" alt="Barangay Tabon Spot Map Full View" class="full-spot-map-img" id="mainSpotMapImage" style="cursor: pointer; width: 100%; object-fit: cover;">
                        
                        <!-- Map Pin Overlay (Initially hidden) -->
                        <div id="barangay-pin" style="position: absolute; top: 18%; left: 47%; display: none; transform: translate(-50%, -100%); cursor: pointer; text-align: center; animation: bounce 2s infinite;">
                            <i class="fa-solid fa-location-dot" style="color: #ff2a2a; font-size: 45px; text-shadow: 2px 2px 8px rgba(0,0,0,0.6);"></i>
                        </div>

                        <!-- Purok 1 Markers -->
                        <div id="purok1-markers" style="display: none;">
                            <?php foreach($purok1 as $m): ?>
                                <div class="saved-marker" style="position: absolute; left: <?= htmlspecialchars($m['coordinate_x']) ?>%; top: <?= htmlspecialchars($m['coordinate_y']) ?>%; transform: translate(-50%, -100%); z-index: 10;">
                                    <img src="Admin/<?= htmlspecialchars($m['marker_image']) ?>" alt="Marker" style="width: <?= htmlspecialchars($m['marker_width']) ?>px; height: <?= htmlspecialchars($m['marker_height']) ?>px; cursor: pointer; drop-shadow: 0 4px 6px rgba(0,0,0,0.3);" title="House #<?= htmlspecialchars($m['house_number']) ?>" onclick="showHouseholdDetails('Admin/<?= htmlspecialchars($m['house_image']) ?>', '<?= htmlspecialchars($m['house_number']) ?>', '<?= htmlspecialchars(addslashes($m['husband_name'])) ?>', '<?= htmlspecialchars(addslashes($m['spouse_name'])) ?>')">
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Purok 2 Markers -->
                        <div id="purok2-markers" style="display: none;">
                            <?php foreach($purok2 as $m): ?>
                                <div class="saved-marker" style="position: absolute; left: <?= htmlspecialchars($m['coordinate_x']) ?>%; top: <?= htmlspecialchars($m['coordinate_y']) ?>%; transform: translate(-50%, -100%); z-index: 10;">
                                    <img src="Admin/<?= htmlspecialchars($m['marker_image']) ?>" alt="Marker" style="width: <?= htmlspecialchars($m['marker_width']) ?>px; height: <?= htmlspecialchars($m['marker_height']) ?>px; cursor: pointer; drop-shadow: 0 4px 6px rgba(0,0,0,0.3);" title="House #<?= htmlspecialchars($m['house_number']) ?>" onclick="showHouseholdDetails('Admin/<?= htmlspecialchars($m['house_image']) ?>', '<?= htmlspecialchars($m['house_number']) ?>', '<?= htmlspecialchars(addslashes($m['husband_name'])) ?>', '<?= htmlspecialchars(addslashes($m['spouse_name'])) ?>')">
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Purok 3 Markers -->
                        <div id="purok3-markers" style="display: none;">
                            <?php foreach($purok3 as $m): ?>
                                <div class="saved-marker" style="position: absolute; left: <?= htmlspecialchars($m['coordinate_x']) ?>%; top: <?= htmlspecialchars($m['coordinate_y']) ?>%; transform: translate(-50%, -100%); z-index: 10;">
                                    <img src="Admin/<?= htmlspecialchars($m['marker_image']) ?>" alt="Marker" style="width: <?= htmlspecialchars($m['marker_width']) ?>px; height: <?= htmlspecialchars($m['marker_height']) ?>px; cursor: pointer; drop-shadow: 0 4px 6px rgba(0,0,0,0.3);" title="House #<?= htmlspecialchars($m['house_number']) ?>" onclick="showHouseholdDetails('Admin/<?= htmlspecialchars($m['house_image']) ?>', '<?= htmlspecialchars($m['house_number']) ?>', '<?= htmlspecialchars(addslashes($m['husband_name'])) ?>', '<?= htmlspecialchars(addslashes($m['spouse_name'])) ?>')">
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
            </div>
        </div>
    </div>

    <script>
        // Household Details functions
        function showHouseholdDetails(imageSrc, houseNumber, husbandName, spouseName) {
            document.getElementById('modalHouseImage').src = imageSrc;
            document.getElementById('modalHouseNumber').innerText = houseNumber;
            document.getElementById('modalHusbandName').innerText = husbandName || 'N/A';
            document.getElementById('modalSpouseName').innerText = spouseName || 'N/A';
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

        const mapSequence = [
            'Images/Map/Philippines.png',
            'Images/Map/mindanao.jpg',
            'Images/Map/Zamboanga del Sur.jpg',
            'Images/Map/Lapuyan.gif',
            'Images/Map/Barangay Tabon Forest Village.png',
            'Images/ChatGPT Image Oct 1, 2026, 07_50_28 AM.png'
        ];
        let currentSequenceIndex = 0;

        document.getElementById('mainSpotMapImage').addEventListener('click', function() {
            // Allow clicking the map to advance ONLY before reaching the Forest Village image
            if (currentSequenceIndex < mapSequence.length - 2) {
                currentSequenceIndex++;
                this.src = mapSequence[currentSequenceIndex];
                togglePinVisibility(mapSequence[currentSequenceIndex]);
                
                // Disable map click cursor once Forest Village image is reached
                if (currentSequenceIndex === mapSequence.length - 2) {
                    this.style.cursor = 'default';
                }
            }
        });

        // The pin click advances the sequence to the last image
        document.getElementById('barangay-pin').addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent bubbling
            if (currentSequenceIndex === mapSequence.length - 2) {
                currentSequenceIndex++;
                const mapImg = document.getElementById('mainSpotMapImage');
                mapImg.src = mapSequence[currentSequenceIndex];
                togglePinVisibility(mapSequence[currentSequenceIndex]);
                mapImg.style.cursor = 'default';
            }
        });

        function changeMapImage(imageUrl) {
            document.getElementById('mainSpotMapImage').src = imageUrl;
            if (imageUrl === 'Images/Map/Philippines.png') {
                currentSequenceIndex = 0;
                document.getElementById('mainSpotMapImage').style.cursor = 'pointer';
            } else {
                document.getElementById('mainSpotMapImage').style.cursor = 'default';
            }
            togglePinVisibility(imageUrl);
        }

        function togglePinVisibility(imageUrl) {
            const pin = document.getElementById('barangay-pin');
            const p1 = document.getElementById('purok1-markers');
            const p2 = document.getElementById('purok2-markers');
            const p3 = document.getElementById('purok3-markers');
            
            p1.style.display = 'none';
            p2.style.display = 'none';
            p3.style.display = 'none';

            if (imageUrl.includes('Barangay Tabon Forest Village')) {
                pin.style.display = 'block';
            } else {
                pin.style.display = 'none';
            }

            if (imageUrl.includes('Purok 1')) {
                p1.style.display = 'block';
            } else if (imageUrl.includes('Purok 2')) {
                p2.style.display = 'block';
            } else if (imageUrl.includes('Purok 3')) {
                p3.style.display = 'block';
            }
        }
    </script>
    <script src="js/main.js"></script>
</body>
</html>
