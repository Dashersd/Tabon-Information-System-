<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Tabon - Gallery / Officials</title>
    
    <!-- FontAwesome CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- External Stylesheets -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/officials.css?v=<?php echo time(); ?>">
    
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
</head>
<body class="officials-page">

    <!-- Header / Navbar -->
    <header>
        <div class="brand-container">
            <img src="Barangay Logo/Logo.png" alt="Logo" class="brand-logo" onerror="this.src='https://via.placeholder.com/65';">
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
                <i class="fa-solid fa-users"></i>
                <span>About Us</span>
            </a>
            <div class="nav-item dropdown active">
                <a href="#" class="dropdown-toggle" style="text-decoration:none;">
                    <i class="fa-regular fa-image"></i>
                    <span>Gallery & Officials <i class="fa-solid fa-chevron-down" style="font-size:10px; margin-left:3px;"></i></span>
                </a>
                <div class="dropdown-menu">
                    <a href="officials.php" class="dropdown-item">Barangay Officials</a>
                    <a href="skofficials.php" class="dropdown-item">SK Officials</a>
                </div>
            </div>
            <a href="#" class="nav-item ">
                <i class="fa-solid fa-location-dot"></i>
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

    <!-- Main Content Area -->
    <main class="officials-content">
        
        <!-- Header Text Block -->
        <div class="officials-header">
            <div class="gallery-cursive">Gallery</div>
            <h1 class="officials-title">GALLERY & OFFICIALS</h1>
            <p class="officials-subtitle">
                A glimpse into our community programs and the dedicated leaders behind them.
            </p>
        </div>

        <!-- 3D Carousel Gallery Section -->
        <section class="gallery-carousel-section">
            <div class="swiper mySwiper">
                <div class="swiper-wrapper">
                    <!-- Slide 1 -->
                    <div class="swiper-slide">
                        <div class="carousel-card">
                            <div class="carousel-img-wrapper">
                                <img src="https://images.unsplash.com/photo-1519999482648-25049ddd37b1?auto=format&fit=crop&w=500&q=80" alt="Korea Advertising">
                                <span class="carousel-badge">No Animation</span>
                            </div>
                            <div class="carousel-content">
                                <div class="carousel-title">Korea Advertising</div>
                                <div class="carousel-desc">Your content goes here. Edit or remove this text inline or in the module Content settings. You can also style every aspect of this content in the module Design settings.</div>
                                <a href="#" class="carousel-btn">Learn More</a>
                            </div>
                        </div>
                    </div>
                    <!-- Slide 2 -->
                    <div class="swiper-slide">
                        <div class="carousel-card">
                            <div class="carousel-img-wrapper">
                                <img src="https://images.unsplash.com/photo-1542314831-c6a4d14b8fc2?auto=format&fit=crop&w=500&q=80" alt="Banner Advertising">
                                <span class="carousel-badge">Coca Cola</span>
                            </div>
                            <div class="carousel-content">
                                <div class="carousel-title">Banner Advertising</div>
                                <div class="carousel-desc">Your content goes here. Edit or remove this text inline or in the module Content settings. You can also style every aspect of this content in the module Design settings.</div>
                                <a href="#" class="carousel-btn">Learn More</a>
                            </div>
                        </div>
                    </div>
                    <!-- Slide 3 -->
                    <div class="swiper-slide">
                        <div class="carousel-card">
                            <div class="carousel-img-wrapper">
                                <img src="https://images.unsplash.com/photo-1555899434-94d1368aa7af?auto=format&fit=crop&w=500&q=80" alt="City Advertising">
                                <span class="carousel-badge">Zoom Out</span>
                            </div>
                            <div class="carousel-content">
                                <div class="carousel-title">City Advertising</div>
                                <div class="carousel-desc">Your content goes here. Edit or remove this text inline or in the module Content settings. You can also style every aspect of this content in the module Design settings.</div>
                                <a href="#" class="carousel-btn">Learn More</a>
                            </div>
                        </div>
                    </div>
                    <!-- Slide 4 -->
                    <div class="swiper-slide">
                        <div class="carousel-card">
                            <div class="carousel-img-wrapper">
                                <img src="https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?auto=format&fit=crop&w=500&q=80" alt="Community Event">
                                <span class="carousel-badge">Events</span>
                            </div>
                            <div class="carousel-content">
                                <div class="carousel-title">Community Events</div>
                                <div class="carousel-desc">Your content goes here. Edit or remove this text inline or in the module Content settings. You can also style every aspect of this content in the module Design settings.</div>
                                <a href="#" class="carousel-btn">Learn More</a>
                            </div>
                        </div>
                    </div>
                    <!-- Slide 5 -->
                    <div class="swiper-slide">
                        <div class="carousel-card">
                            <div class="carousel-img-wrapper">
                                <img src="https://images.unsplash.com/photo-1498623116890-37e912163d5d?auto=format&fit=crop&w=500&q=80" alt="Nature Drive">
                                <span class="carousel-badge">Outdoors</span>
                            </div>
                            <div class="carousel-content">
                                <div class="carousel-title">Nature Drive</div>
                                <div class="carousel-desc">Your content goes here. Edit or remove this text inline or in the module Content settings. You can also style every aspect of this content in the module Design settings.</div>
                                <a href="#" class="carousel-btn">Learn More</a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Pagination -->
                <div class="swiper-pagination"></div>
                <!-- Navigation Arrows -->
                <div class="swiper-button-next custom-nav-btn"></div>
                <div class="swiper-button-prev custom-nav-btn"></div>
            </div>
        </section>

        <!-- Officials Section Title -->
        <h2 class="officials-title" style="margin-top: 40px; margin-bottom: 30px;">MEET OUR BARANGAY OFFICIALS</h2>

        <!-- Officials Org Chart -->
        <div class="org-chart">
            
            <!-- Captain -->
            <div class="org-row row-captain">
                <div class="official-card" 
                     onclick="openModal(this)"
                     data-name="Hon. Reynaldo D. Santos"
                     data-title="BARANGAY CAPTAIN"
                     data-image="Officials/Captain.jpg"
                     data-desc="Dedicated to leading Barangay Tabon towards sustainable development and progressive community programs.">
                    <div class="card-img-wrapper">
                        <img src="Officials/Captain.jpg" alt="Captain" onerror="this.src='https://via.placeholder.com/150';">
                    </div>
                    <div class="card-info">
                        <div class="official-name">Hon. Reynaldo D. Santos</div>
                        <div class="official-title">BARANGAY CAPTAIN</div>
                    </div>
                </div>
            </div>

            <!-- Kagawad Row 1 (2 people) -->
            <div class="org-row row-kagawad-1">
                <div class="official-card"
                     onclick="openModal(this)"
                     data-name="Hon. Mark Anthony T. Cruz"
                     data-title="BARANGAY KAGAWAD"
                     data-image="Officials/Captain.jpg"
                     data-desc="Committee Chair on Peace and Order. Ensuring the safety and security of all residents.">
                    <div class="card-img-wrapper">
                        <img src="Officials/Captain.jpg" alt="Kagawad" onerror="this.src='https://via.placeholder.com/150';">
                    </div>
                    <div class="card-info">
                        <div class="official-name">Hon. Mark Anthony T. Cruz</div>
                        <div class="official-title">BARANGAY KAGAWAD</div>
                    </div>
                </div>
                <div class="official-card"
                     onclick="openModal(this)"
                     data-name="Hon. Maria Cristina S. Flores"
                     data-title="BARANGAY KAGAWAD"
                     data-image="Officials/Captain.jpg"
                     data-desc="Committee Chair on Health and Sanitation. Focused on providing accessible healthcare for everyone.">
                    <div class="card-img-wrapper">
                        <img src="Officials/Captain.jpg" alt="Kagawad" onerror="this.src='https://via.placeholder.com/150';">
                    </div>
                    <div class="card-info">
                        <div class="official-name">Hon. Maria Cristina S. Flores</div>
                        <div class="official-title">BARANGAY KAGAWAD</div>
                    </div>
                </div>
            </div>

            <!-- Kagawad Row 2 (5 people) -->
            <div class="org-row row-kagawad-2">
                <div class="official-card"
                     onclick="openModal(this)"
                     data-name="Hon. Joseph L. Reyes"
                     data-title="BARANGAY KAGAWAD"
                     data-image="Officials/Captain.jpg"
                     data-desc="Committee Chair on Infrastructure. Managing barangay road works and public facilities.">
                    <div class="card-img-wrapper">
                        <img src="Officials/Captain.jpg" alt="Kagawad" onerror="this.src='https://via.placeholder.com/150';">
                    </div>
                    <div class="card-info">
                        <div class="official-name">Hon. Joseph L. Reyes</div>
                        <div class="official-title">BARANGAY KAGAWAD</div>
                    </div>
                </div>
                <div class="official-card"
                     onclick="openModal(this)"
                     data-name="Hon. Alma B. Navarro"
                     data-title="BARANGAY KAGAWAD"
                     data-image="Officials/Captain.jpg"
                     data-desc="Committee Chair on Education. Advocating for better learning programs and youth development.">
                    <div class="card-img-wrapper">
                        <img src="Officials/Captain.jpg" alt="Kagawad" onerror="this.src='https://via.placeholder.com/150';">
                    </div>
                    <div class="card-info">
                        <div class="official-name">Hon. Alma B. Navarro</div>
                        <div class="official-title">BARANGAY KAGAWAD</div>
                    </div>
                </div>
                <div class="official-card"
                     onclick="openModal(this)"
                     data-name="Hon. Allan P. Santos"
                     data-title="BARANGAY KAGAWAD"
                     data-image="Officials/Captain.jpg"
                     data-desc="Committee Chair on Sports and Youth. Promoting active lifestyle and youth empowerment.">
                    <div class="card-img-wrapper">
                        <img src="Officials/Captain.jpg" alt="Kagawad" onerror="this.src='https://via.placeholder.com/150';">
                    </div>
                    <div class="card-info">
                        <div class="official-name">Hon. Allan P. Santos</div>
                        <div class="official-title">BARANGAY KAGAWAD</div>
                    </div>
                </div>
                <div class="official-card"
                     onclick="openModal(this)"
                     data-name="Hon. Liza M. Torres"
                     data-title="BARANGAY KAGAWAD"
                     data-image="Officials/Captain.jpg"
                     data-desc="Committee Chair on Women and Family. Supporting women's rights and family welfare.">
                    <div class="card-img-wrapper">
                        <img src="Officials/Captain.jpg" alt="Kagawad" onerror="this.src='https://via.placeholder.com/150';">
                    </div>
                    <div class="card-info">
                        <div class="official-name">Hon. Liza M. Torres</div>
                        <div class="official-title">BARANGAY KAGAWAD</div>
                    </div>
                </div>
                <div class="official-card"
                     onclick="openModal(this)"
                     data-name="Hon. Ramon C. Dela Cruz"
                     data-title="BARANGAY KAGAWAD"
                     data-image="Officials/Captain.jpg"
                     data-desc="Committee Chair on Environment. Leading clean-up drives and eco-friendly initiatives.">
                    <div class="card-img-wrapper">
                        <img src="Officials/Captain.jpg" alt="Kagawad" onerror="this.src='https://via.placeholder.com/150';">
                    </div>
                    <div class="card-info">
                        <div class="official-name">Hon. Ramon C. Dela Cruz</div>
                        <div class="official-title">BARANGAY KAGAWAD</div>
                    </div>
                </div>
            </div>

        </div>

    </main>

    <!-- Modal Container -->
    <div id="officialModal" class="modal-overlay" onclick="closeModal(event)">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal(event)">&times;</span>
            <div class="modal-img-container">
                <img id="modalImg" src="" alt="Official Profile">
            </div>
            <div class="modal-details">
                <h2 id="modalName"></h2>
                <h4 id="modalTitle"></h4>
                <p id="modalDesc"></p>
            </div>
        </div>
    </div>

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
                    <li><a href="Index.php">Home</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="officials.php">Officials</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>
            
            <div class="footer-links">
                <h4>Utilities</h4>
                <ul>
                    <li><a href="login.php">Admin Login</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms and Condition</a></li>
                </ul>
            </div>
        </div>
    </footer>

    <script>
        function openModal(card) {
            // Get data from clicked card
            const name = card.getAttribute('data-name');
            const title = card.getAttribute('data-title');
            const image = card.getAttribute('data-image');
            const desc = card.getAttribute('data-desc');

            // Set data to modal elements
            document.getElementById('modalName').textContent = name;
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalDesc').textContent = desc;
            document.getElementById('modalImg').src = image;

            // Show modal
            document.getElementById('officialModal').classList.add('active');
        }

        function closeModal(event) {
            // Only close if clicking the background overlay or the close button
            if (event.target.id === 'officialModal' || event.target.className === 'close-btn') {
                document.getElementById('officialModal').classList.remove('active');
            }
        }
    </script>

    <!-- Swiper JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    
    <!-- Initialize Swiper -->
    <script>
        var swiper = new Swiper('.swiper-container', {
            effect: 'coverflow',
            grabCursor: true,
            centeredSlides: true,
            slidesPerView: 'auto',
            initialSlide: 3, // Start on Kagawad 1 (index 3 out of 0-7)
            coverflowEffect: {
                rotate: 50,
                stretch: 0,
                depth: 100,
                modifier: 1,
                slideShadows: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
        });
    </script>
    
    <script src="js/main.js"></script>
</body>
</html>
