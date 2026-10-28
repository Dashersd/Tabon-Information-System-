<?php
require_once 'db_connect.php';

$sk_officials_map = [];
try {
    $res = $conn->query("SELECT * FROM `sk_officials` WHERE `status` = 'active' ORDER BY `id` ASC");
    if ($res && $res->num_rows > 0) {
        while ($r = $res->fetch_assoc()) {
            $k = !empty($r['official_key']) ? $r['official_key'] : ('sk_' . $r['id']);
            $photo = $r['photo'] ?? '';
            if (!empty($photo)) {
                if (strpos($photo, 'uploads/') === 0) {
                    $r['web_photo'] = 'Admin/' . $photo;
                } else {
                    $r['web_photo'] = $photo;
                }
            } else {
                $r['web_photo'] = 'Officials/Captain.jpg';
            }
            $sk_officials_map[$k] = $r;
        }
    }
} catch (Exception $e) {}

// Fallback from JSON if DB empty
if (empty($sk_officials_map)) {
    $json_f = __DIR__ . '/Admin/data/sk_officials.json';
    if (file_exists($json_f)) {
        $j_arr = json_decode(file_get_contents($json_f), true);
        if (!empty($j_arr)) {
            foreach ($j_arr as $r) {
                $k = !empty($r['official_key']) ? $r['official_key'] : ('sk_' . $r['id']);
                $photo = $r['photo'] ?? '';
                if (!empty($photo)) {
                    if (strpos($photo, 'uploads/') === 0) {
                        $r['web_photo'] = 'Admin/' . $photo;
                    } else {
                        $r['web_photo'] = $photo;
                    }
                } else {
                    $r['web_photo'] = 'Officials/Captain.jpg';
                }
                $sk_officials_map[$k] = $r;
            }
        }
    }
}

function get_pub_sk_official($key, $fallback_name, $fallback_title, $fallback_photo, $fallback_desc, $map) {
    if (isset($map[$key])) {
        $photo = !empty($map[$key]['web_photo']) ? $map[$key]['web_photo'] : (!empty($fallback_photo) ? $fallback_photo : 'Officials/Captain.jpg');
        return [
            'name' => $map[$key]['name'],
            'title' => strtoupper($map[$key]['position']),
            'image' => $photo,
            'desc' => !empty($map[$key]['message']) ? $map[$key]['message'] : $fallback_desc
        ];
    }
    return [
        'name' => $fallback_name,
        'title' => $fallback_title,
        'image' => !empty($fallback_photo) ? $fallback_photo : 'Officials/Captain.jpg',
        'desc' => $fallback_desc
    ];
}

$chair = get_pub_sk_official('chairperson', 'Hon. Ryan M. Tubos', 'SK CHAIRPERSON', 'public/image/SK Officials/Ryan M. Tubos.jpeg', 'Empowering the youth of Barangay Tabon to lead with purpose, integrity, and passion. Together, we build a future anchored in education, leadership development, sportsmanship, and active civic participation.', $sk_officials_map);
$sec = get_pub_sk_official('secretary', 'Hon. Rosemer G. Banagan', 'SK SECRETARY', 'public/image/SK Officials/Rosmer G. Banagan.jpeg', 'Managing official youth council records, session minutes, and administrative coordination for Sangguniang Kabataan.', $sk_officials_map);
$tre = get_pub_sk_official('treasurer', 'Hon. Judith G. Tambagel', 'SK TREASURER', 'public/image/SK Officials/Judith G. Tambagel.jpeg', 'Custodian of SK funds, ensuring transparent allocation and budgeting for youth development projects and activities.', $sk_officials_map);
$sk1 = get_pub_sk_official('kagawad_1', 'Hon. Renzarr L. Roxas', 'SK KAGAWAD 1', '', 'Committee on Youth Leadership and Civic Engagement. Inspiring young citizens to participate actively in community development.', $sk_officials_map);
$sk2 = get_pub_sk_official('kagawad_2', 'Hon. Benie L. Ligao', 'SK KAGAWAD 2', '', 'Committee on Sports Development. Organizing tournaments, physical wellness activities, and sports programs for local youth.', $sk_officials_map);
$sk3 = get_pub_sk_official('kagawad_3', 'Hon. Jhon Mark Charton D. Singue', 'SK KAGAWAD 3', '', 'Committee on Education and Technology. Promoting scholarships, digital literacy, and student assistance programs.', $sk_officials_map);
$sk4 = get_pub_sk_official('kagawad_4', 'Hon. Mark A. Alpay', 'SK KAGAWAD 4', '', 'Committee on Environmental Protection. Championing clean and green youth initiatives and tree planting campaigns.', $sk_officials_map);
$sk5 = get_pub_sk_official('kagawad_5', 'Hon. Marlon B. Amplao', 'SK KAGAWAD 5', '', 'Committee on Health and Wellness. Promoting mental health awareness, anti-drug advocacy, and healthy lifestyle.', $sk_officials_map);
$sk6 = get_pub_sk_official('kagawad_6', 'Hon. Verdelyn G. Halilong', 'SK KAGAWAD 6', '', 'Committee on Culture and Arts. Encouraging youth creativity, local cultural preservation, and talent showcases.', $sk_officials_map);
$sk7 = get_pub_sk_official('kagawad_7', 'Hon. Mc Felco T Bugao', 'SK KAGAWAD 7', '', 'Committee on Youth Welfare and Disaster Preparedness. Mobilizing youth volunteers for disaster relief and community safety.', $sk_officials_map);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Tabon - SK Officials</title>
    
    <!-- FontAwesome CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- External Stylesheets -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/skofficials.css?v=<?php echo time(); ?>">
    
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
</head>
<body class="officials-page">

    <!-- Header / Navbar -->
    <header>
        <div class="brand-container">
            <img src="Barangay Logo/Logo.png" alt="Barangay Logo" class="brand-logo" onerror="this.src='https://via.placeholder.com/65';">
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
            <div class="nav-item dropdown">
                <a href="#" class="dropdown-toggle" style="text-decoration:none;">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>About Us <i class="fa-solid fa-chevron-down" style="font-size:10px; margin-left:3px;"></i></span>
                </a>
                <div class="dropdown-menu">
                    <a href="history.php" class="dropdown-item">History</a>
                    <a href="about.php" class="dropdown-item">Mission & Vision</a>
                </div>
            </div>
            <div class="nav-item dropdown active">
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
            <a href="spotmap.php" class="nav-item">
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

    <!-- Main Content Area -->
    <main class="officials-content">
        
        <!-- Officials Section Title -->
        <h2 id="sk-officials" class="officials-title" style="margin-top: 40px; margin-bottom: 30px;">MEET OUR SK OFFICIALS</h2>

        <!-- Officials Org Chart -->
        <div class="org-chart">
            
            <!-- SK Chairperson -->
            <div class="org-row row-captain">
                <div class="official-card" 
                     onclick="openModal(this)"
                     data-name="<?= htmlspecialchars($chair['name']) ?>"
                     data-title="<?= htmlspecialchars($chair['title']) ?>"
                     data-image="<?= htmlspecialchars($chair['image']) ?>"
                     data-desc="<?= htmlspecialchars($chair['desc']) ?>">
                    <div class="card-img-wrapper">
                        <img src="<?= htmlspecialchars($chair['image']) ?>?t=<?= time() ?>" alt="<?= htmlspecialchars($chair['name']) ?>" onerror="this.src='Officials/Captain.jpg';">
                    </div>
                    <div class="card-info">
                        <div class="official-name"><?= htmlspecialchars($chair['name']) ?></div>
                        <div class="official-title"><?= htmlspecialchars($chair['title']) ?></div>
                    </div>
                </div>
            </div>

            <!-- SK Administration (Secretary & Treasurer) -->
            <div class="org-row row-kagawad-1">
                <div class="official-card"
                     onclick="openModal(this)"
                     data-name="<?= htmlspecialchars($sec['name']) ?>"
                     data-title="<?= htmlspecialchars($sec['title']) ?>"
                     data-image="<?= htmlspecialchars($sec['image']) ?>"
                     data-desc="<?= htmlspecialchars($sec['desc']) ?>">
                    <div class="card-img-wrapper">
                        <img src="<?= htmlspecialchars($sec['image']) ?>?t=<?= time() ?>" alt="<?= htmlspecialchars($sec['name']) ?>" onerror="this.src='Officials/Captain.jpg';">
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
                        <img src="<?= htmlspecialchars($tre['image']) ?>?t=<?= time() ?>" alt="<?= htmlspecialchars($tre['name']) ?>" onerror="this.src='Officials/Captain.jpg';">
                    </div>
                    <div class="card-info">
                        <div class="official-name"><?= htmlspecialchars($tre['name']) ?></div>
                        <div class="official-title"><?= htmlspecialchars($tre['title']) ?></div>
                    </div>
                </div>
            </div>

            <!-- SK Kagawads (1 to 7) -->
            <div class="org-row row-kagawad-2">
                <?php 
                $sk_kagawads = [$sk1, $sk2, $sk3, $sk4, $sk5, $sk6, $sk7];
                foreach ($sk_kagawads as $kag): 
                ?>
                <div class="official-card"
                     onclick="openModal(this)"
                     data-name="<?= htmlspecialchars($kag['name']) ?>"
                     data-title="<?= htmlspecialchars($kag['title']) ?>"
                     data-image="<?= htmlspecialchars($kag['image']) ?>"
                     data-desc="<?= htmlspecialchars($kag['desc']) ?>">
                    <div class="card-img-wrapper">
                        <img src="<?= htmlspecialchars($kag['image']) ?>?t=<?= time() ?>" alt="<?= htmlspecialchars($kag['name']) ?>" onerror="this.src='Officials/Captain.jpg';">
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


