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
                                <div class="purok-pill" style="background-color: #f1f5f9; border-color: #cbd5e1; color: #475569;" onclick="changeMapImage('Images/Make_this_16;9_and_4k_20261001084446.jpg')">Full Map</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Spot Map Image (Now on Right) -->
                <div class="spotmap-main">
                    <img src="Images/Make_this_16;9_and_4k_20261001084446.jpg" alt="Barangay Tabon Spot Map Full View" class="full-spot-map-img" id="mainSpotMapImage">
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

    <script>
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

        function changeMapImage(imageUrl) {
            document.getElementById('mainSpotMapImage').src = imageUrl;
        }
    </script>
    <script src="js/main.js"></script>
</body>
</html>
