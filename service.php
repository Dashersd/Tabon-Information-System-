<?php
// service.php - Barangay Tabon Services Landing Page
require_once 'db_connect.php';

// Fetch active services ordered by display_order
$services = [];
$res = $conn->query("SELECT * FROM services WHERE status = 'active' ORDER BY display_order ASC, id ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $services[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Services - Barangay Tabon</title>
    
    <!-- FontAwesome & Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600;1,700&display=swap" rel="stylesheet">
    
    <!-- Preloader & Stylesheets -->
    <link rel="stylesheet" href="css/preloader.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/service.css?v=<?php echo time(); ?>">
</head>
<body>

    <!-- Mobile Legends Inspired Animated Preloader -->
    <?php include 'preloader.php'; ?>

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
            <a href="gallery.php" class="nav-item">
                <span>Gallery</span>
            </a>
            <a href="service.php" class="nav-item active">
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

    <!-- Main Content Container -->
    <main class="service-page-wrapper">
        
        <!-- Hero Section -->
        <section class="service-hero">
            <span class="service-pill-badge">
                <i class="fa-solid fa-hand-holding-hand"></i> Frontline Citizen Services
            </span>
            <h1 class="service-hero-title">Barangay Tabon Services</h1>
            <p class="service-hero-sub">
                Official certifications, community clearances, public assistance, and welfare programs made accessible, transparent, and prompt for all Tabon residents.
            </p>
        </section>

        <!-- Services Grid -->
        <section class="services-grid" id="servicesGrid">
            <?php if (empty($services)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: #ffffff; border-radius: 16px; border: 1.5px dashed #cbd5e1; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
                    <i class="bi bi-folder2-open" style="font-size: 3rem; color: #94a3b8; display: block; margin-bottom: 12px;"></i>
                    <h3 style="margin: 0 0 6px 0; color: #334155;">No Available Services</h3>
                    <p style="margin: 0; color: #64748b;">Services will be displayed here as soon as they are made available by Barangay Tabon.</p>
                </div>
            <?php else: ?>
                <?php foreach ($services as $service): ?>
                    <div class="service-item-card" id="service-<?= (int)$service['id'] ?>">
                        <div>
                            <div class="service-icon-box">
                                <i class="bi <?= htmlspecialchars($service['icon'] ?: 'bi-file-earmark-text') ?>"></i>
                            </div>

                            <h2 class="service-title"><?= htmlspecialchars($service['name']) ?></h2>

                            <div class="service-meta-badges">
                                <span class="meta-pill fee"><i class="bi bi-cash-stack"></i> <?= htmlspecialchars($service['fee'] ?: 'Free') ?></span>
                                <span class="meta-pill"><i class="bi bi-clock"></i> <?= htmlspecialchars($service['processing_time'] ?: 'Same Day') ?></span>
                            </div>

                            <p class="service-desc"><?= htmlspecialchars($service['description'] ?? '') ?></p>

                            <?php if (!empty($service['requirements'])): ?>
                                <div class="requirements-preview">
                                    <strong>Key Requirements:</strong>
                                    <?= htmlspecialchars(mb_strimwidth($service['requirements'], 0, 85, '...')) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-actions">
                            <a href="<?= htmlspecialchars($service['button_url'] ?: 'contact.php') ?>" class="btn-request">
                                <?= htmlspecialchars($service['button_text'] ?: 'Request Document') ?>
                            </a>
                            <button type="button" class="btn-details" onclick='openPublicDetailModal(<?= json_encode($service, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                Details
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

    </main>

    <!-- Public Detail Modal -->
    <div id="serviceDetailModal" class="service-modal" onclick="closeDetailModal()">
        <div class="modal-dialog" onclick="event.stopPropagation()">
            <div class="modal-banner">
                <h3 id="detailModalTitle">Service Details</h3>
                <button type="button" class="modal-close" onclick="closeDetailModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-info-item">
                    <strong>Description</strong>
                    <p id="detailModalDesc"></p>
                </div>
                <div class="modal-info-item">
                    <strong>Requirements Checklist</strong>
                    <div id="detailModalReq"></div>
                </div>
                <div class="modal-info-item">
                    <strong>Fee / Cost</strong>
                    <p id="detailModalFee"></p>
                </div>
                <div class="modal-info-item">
                    <strong>Processing Time</strong>
                    <p id="detailModalTime"></p>
                </div>
                <div class="modal-info-item" id="detailModalDocItem" style="display:none;">
                    <strong>Form / Document Template</strong>
                    <p style="margin:4px 0 0 0;">
                        <a id="detailModalDocLink" href="#" target="_blank" download style="display:inline-flex; align-items:center; gap:6px; padding:6px 14px; background:#e8f5ee; color:#137547; border:1px solid #bbf7d0; border-radius:6px; font-weight:600; font-size:0.88rem; text-decoration:none;">
                            <i class="bi bi-file-earmark-arrow-down"></i> Download Attached Template
                        </a>
                    </p>
                </div>
                <a href="contact.php" id="detailModalLink" class="btn-request" style="display:block; text-align:center; margin-top:20px; text-decoration:none;">Proceed to Request</a>
            </div>
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
            <p>&copy; <?php echo date('Y'); ?> Barangay Tabon Information System. All Rights Reserved.</p>
        </div>
    </footer>

    <!-- Interactive Detail Modal Script -->
    <script>
    function openPublicDetailModal(svc) {
        document.getElementById('detailModalTitle').textContent = svc.name || 'Service Details';
        document.getElementById('detailModalDesc').textContent = svc.description || 'No description provided.';
        
        const reqBox = document.getElementById('detailModalReq');
        reqBox.innerHTML = '';
        if (svc.requirements && svc.requirements.trim() !== '') {
            const items = svc.requirements.split(',');
            const ul = document.createElement('ul');
            ul.style.margin = '6px 0 0 0';
            ul.style.paddingLeft = '20px';
            ul.style.color = '#475569';
            ul.style.fontSize = '0.92rem';
            ul.style.lineHeight = '1.6';
            items.forEach(it => {
                const trimmed = it.trim();
                if (trimmed) {
                    const li = document.createElement('li');
                    li.textContent = trimmed;
                    ul.appendChild(li);
                }
            });
            reqBox.appendChild(ul);
        } else {
            reqBox.innerHTML = '<p style="margin:0; font-size:0.92rem; color:#64748b;">No specific requirements listed.</p>';
        }

        document.getElementById('detailModalFee').textContent = svc.fee || 'Free';
        document.getElementById('detailModalTime').textContent = svc.processing_time || 'Same Day';
        
        const docItem = document.getElementById('detailModalDocItem');
        if (docItem) {
            if (svc.document_file && svc.document_file.trim() !== '') {
                const docLink = document.getElementById('detailModalDocLink');
                docLink.href = 'Admin/' + svc.document_file;
                docItem.style.display = 'block';
            } else {
                docItem.style.display = 'none';
            }
        }

        const link = document.getElementById('detailModalLink');
        link.href = svc.button_url || 'contact.php';
        link.textContent = svc.button_text || 'Proceed to Request';

        document.getElementById('serviceDetailModal').style.display = 'flex';
    }

    function closeDetailModal() {
        document.getElementById('serviceDetailModal').style.display = 'none';
    }

    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeDetailModal();
    });

    // Deep-link integration from Index.php
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        let targetId = urlParams.get('id');
        if (!targetId && window.location.hash) {
            const hashMatch = window.location.hash.match(/service-(\d+)/);
            if (hashMatch) {
                targetId = hashMatch[1];
            }
        }

        if (targetId) {
            const targetEl = document.getElementById('service-' + targetId);
            if (targetEl) {
                setTimeout(function() {
                    targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    targetEl.classList.add('service-highlight');
                    const detailBtn = targetEl.querySelector('.btn-details');
                    if (detailBtn) {
                        detailBtn.click();
                    }
                }, 350);
            }
        }
    });
    </script>

    <!-- Preloader Script -->
    <script src="js/preloader.js?v=<?php echo time(); ?>"></script>
    <script src="js/main.js?v=<?= time() ?>"></script>
</body>
</html>
