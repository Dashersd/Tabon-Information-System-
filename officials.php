<?php
require_once 'db_connect.php';

$officials_map = [];
try {
    $off_res = $conn->query("SELECT * FROM `officials` WHERE `status` = 'active' ORDER BY `id` ASC");
    if ($off_res && $off_res->num_rows > 0) {
        while ($r = $off_res->fetch_assoc()) {
            $k = !empty($r['official_key']) ? $r['official_key'] : ('off_' . $r['id']);
            $r['web_photo'] = (strpos($r['photo'], 'uploads/') === 0 ? 'Admin/' . $r['photo'] : $r['photo']);
            $officials_map[$k] = $r;
        }
    }
} catch (Exception $e) {}

// Fallback from JSON if DB empty
if (empty($officials_map)) {
    $json_f = __DIR__ . '/Admin/data/barangay_officials.json';
    if (file_exists($json_f)) {
        $j_arr = json_decode(file_get_contents($json_f), true);
        if (!empty($j_arr)) {
            foreach ($j_arr as $r) {
                $k = !empty($r['official_key']) ? $r['official_key'] : ('off_' . $r['id']);
                $r['web_photo'] = (strpos($r['photo'], 'uploads/') === 0 ? 'Admin/' . $r['photo'] : $r['photo']);
                $officials_map[$k] = $r;
            }
        }
    }
}

function get_pub_official($key, $fallback_name, $fallback_title, $fallback_desc, $map) {
    if (isset($map[$key])) {
        return [
            'name' => $map[$key]['name'],
            'title' => strtoupper($map[$key]['position']),
            'image' => !empty($map[$key]['web_photo']) ? $map[$key]['web_photo'] : 'Officials/Captain.jpg',
            'desc' => !empty($map[$key]['message']) ? $map[$key]['message'] : $fallback_desc
        ];
    }
    return [
        'name' => $fallback_name,
        'title' => $fallback_title,
        'image' => 'Officials/Captain.jpg',
        'desc' => $fallback_desc
    ];
}

$cap = get_pub_official('captain', 'Hon. Reynaldo D. Santos', 'BARANGAY CAPTAIN', 'Dedicated to leading Barangay Tabon towards sustainable development and progressive community programs.', $officials_map);
$sec = get_pub_official('secretary', 'Hon. Maria Cristina S. Flores', 'BARANGAY SECRETARY', 'Managing official records, barangay resolutions, and administrative coordination.', $officials_map);
$tre = get_pub_official('treasurer', 'Hon. Joseph L. Reyes', 'BARANGAY TREASURER', 'Custodian of barangay funds, financial accounting, and community budget planning.', $officials_map);
$kag1 = get_pub_official('kagawad_1', 'Hon. Mark Anthony T. Cruz', 'BARANGAY KAGAWAD', 'Committee Chair on Peace and Order. Ensuring the safety and security of all residents.', $officials_map);
$kag2 = get_pub_official('kagawad_2', 'Hon. Alma B. Navarro', 'BARANGAY KAGAWAD', 'Committee Chair on Health and Sanitation. Focused on providing accessible healthcare for everyone.', $officials_map);
$kag3 = get_pub_official('kagawad_3', 'Hon. Allan P. Santos', 'BARANGAY KAGAWAD', 'Committee Chair on Infrastructure. Managing barangay road works and public facilities.', $officials_map);
$kag4 = get_pub_official('kagawad_4', 'Hon. Liza M. Torres', 'BARANGAY KAGAWAD', 'Committee Chair on Education. Advocating for better learning programs and youth development.', $officials_map);
$kag5 = get_pub_official('kagawad_5', 'Hon. Ramon C. Dela Cruz', 'BARANGAY KAGAWAD', 'Committee Chair on Sports and Youth. Promoting active lifestyle and youth empowerment.', $officials_map);
$kag6 = get_pub_official('kagawad_6', 'Hon. Nelcy D. Ongub', 'BARANGAY KAGAWAD', 'Committee Chair on Women and Family. Supporting women\'s rights and family welfare.', $officials_map);
$kag7 = get_pub_official('kagawad_7', 'Hon. Janito Lipa', 'BARANGAY KAGAWAD', 'Committee Chair on Environment. Leading clean-up drives and eco-friendly initiatives.', $officials_map);
?>
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
            <a href="index.php" class="nav-item">
                <span>Home</span>
            </a>
            <div class="nav-item dropdown active">
                <a href="#" class="dropdown-toggle" style="text-decoration:none;">
                    <span>About Us <i class="fa-solid fa-chevron-down" style="font-size:10px; margin-left:3px;"></i></span>
                </a>
                <div class="dropdown-menu">
                    <a href="history.php" class="dropdown-item">History</a>
                    <a href="mission.php" class="dropdown-item">Mission</a>
                    <a href="vision.php" class="dropdown-item">Vision</a>
                    <a href="officials.php" class="dropdown-item" style="color: #fbb117; font-weight: 600;">Barangay Officials</a>
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

    <!-- Main Content Area -->
    <main class="officials-content">
        
        <!-- Officials Section Title -->
        <h2 class="officials-title" style="margin-top: 40px; margin-bottom: 30px;">MEET OUR BARANGAY OFFICIALS</h2>

        <!-- Officials Org Chart -->
        <div class="org-chart">
            
            <!-- Captain -->
            <div class="org-row row-captain">
                <div class="official-card" 
                     onclick="openModal(this)"
                     data-name="<?= htmlspecialchars($cap['name']) ?>"
                     data-title="<?= htmlspecialchars($cap['title']) ?>"
                     data-image="<?= htmlspecialchars($cap['image']) ?>"
                     data-desc="<?= htmlspecialchars($cap['desc']) ?>">
                    <div class="card-img-wrapper">
                        <img src="<?= htmlspecialchars($cap['image']) ?>?t=<?= time() ?>" alt="Captain" onerror="this.src='Officials/Captain.jpg';">
                    </div>
                    <div class="card-info">
                        <div class="official-name"><?= htmlspecialchars($cap['name']) ?></div>
                        <div class="official-title"><?= htmlspecialchars($cap['title']) ?></div>
                    </div>
                </div>
            </div>

            <!-- Administration (Secretary & Treasurer) -->
            <div class="org-row row-kagawad-1">
                <div class="official-card"
                     onclick="openModal(this)"
                     data-name="<?= htmlspecialchars($sec['name']) ?>"
                     data-title="<?= htmlspecialchars($sec['title']) ?>"
                     data-image="<?= htmlspecialchars($sec['image']) ?>"
                     data-desc="<?= htmlspecialchars($sec['desc']) ?>">
                    <div class="card-img-wrapper">
                        <img src="<?= htmlspecialchars($sec['image']) ?>?t=<?= time() ?>" alt="Secretary" onerror="this.src='Officials/Captain.jpg';">
                    </div>
                    <div class="card-info">
                        <div class="official-name"><?= htmlspecialchars($sec['name']) ?></div>
                        <div class="official-title"><?= htmlspecialchars($sec['title']) ?></div>
                    </div>
                </div>
                <div class="official-card"
                     onclick="openModal(this)"
                     data-name="<?= htmlspecialchars($tre['name']) ?>"
                     data-title="<?= htmlspecialchars($tre['title']) ?>"
                     data-image="<?= htmlspecialchars($tre['image']) ?>"
                     data-desc="<?= htmlspecialchars($tre['desc']) ?>">
                    <div class="card-img-wrapper">
                        <img src="<?= htmlspecialchars($tre['image']) ?>?t=<?= time() ?>" alt="Treasurer" onerror="this.src='Officials/Captain.jpg';">
                    </div>
                    <div class="card-info">
                        <div class="official-name"><?= htmlspecialchars($tre['name']) ?></div>
                        <div class="official-title"><?= htmlspecialchars($tre['title']) ?></div>
                    </div>
                </div>
            </div>

            <!-- Barangay Kagawads (1 to 7) -->
            <div class="org-row row-kagawad-2">
                <?php 
                $kagawad_list = [$kag1, $kag2, $kag3, $kag4, $kag5, $kag6, $kag7];
                foreach ($kagawad_list as $kag): 
                ?>
                <div class="official-card"
                     onclick="openModal(this)"
                     data-name="<?= htmlspecialchars($kag['name']) ?>"
                     data-title="<?= htmlspecialchars($kag['title']) ?>"
                     data-image="<?= htmlspecialchars($kag['image']) ?>"
                     data-desc="<?= htmlspecialchars($kag['desc']) ?>">
                    <div class="card-img-wrapper">
                        <img src="<?= htmlspecialchars($kag['image']) ?>?t=<?= time() ?>" alt="Kagawad" onerror="this.src='Officials/Captain.jpg';">
                    </div>
                    <div class="card-info">
                        <div class="official-name"><?= htmlspecialchars($kag['name']) ?></div>
                        <div class="official-title"><?= htmlspecialchars($kag['title']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
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
    
    <script src="js/main.js?v=<?= time() ?>"></script>
</body>
</html>
