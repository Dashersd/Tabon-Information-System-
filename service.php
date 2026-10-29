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
                    <a href="mission.php" class="dropdown-item">Mission</a>
                    <a href="vision.php" class="dropdown-item">Vision</a>
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
                <?php foreach ($services as $service): 
                    $rawIcon = trim($service['icon'] ?? '');
                    if (empty($rawIcon)) {
                        $iconClass = 'fa-solid fa-file-lines';
                    } elseif (strpos($rawIcon, 'bi-') === 0) {
                        $iconClass = 'bi ' . $rawIcon;
                    } elseif (strpos($rawIcon, 'fa-') !== false) {
                        $iconClass = $rawIcon;
                    } else {
                        $iconClass = 'bi bi-' . $rawIcon;
                    }
                ?>
                    <div class="service-item-card" id="service-<?= (int)$service['id'] ?>">
                        <div>
                            <div class="service-icon-box">
                                <i class="<?= htmlspecialchars($iconClass) ?>"></i>
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
                    <strong>Sample Document Template (View Only)</strong>
                    <p style="margin:4px 0 0 0;">
                        <button type="button" id="detailModalDocBtn" onclick="openPublicTemplatePreview()" style="display:inline-flex; align-items:center; gap:8px; padding:8px 18px; background:#e8f5ee; color:#146c43; border:1.5px solid #86efac; border-radius:8px; font-weight:700; font-size:0.9rem; cursor:pointer; transition:all 0.2s ease;">
                            <i class="bi bi-eye"></i> View Sample Template
                        </button>
                    </p>
                    <small style="display:block; margin-top:4px; font-size:0.78rem; color:#64748b;"><i class="bi bi-shield-lock"></i> Official reference preview • Protected from download</small>
                    </p>
                </div>
                <a href="contact.php" id="detailModalLink" class="btn-request" style="display:block; text-align:center; margin-top:20px; text-decoration:none;">Proceed to Request</a>
            </div>
        </div>
    </div>

    <!-- Public View-Only Sample Template Modal -->
    <div id="publicTemplateModal" class="service-modal" onclick="closePublicTemplateModal()">
        <div class="modal-dialog" onclick="event.stopPropagation()" style="max-width: 640px; padding: 0; overflow: hidden; border-radius: 16px;">
            <div class="modal-banner" style="background:#0c3823; padding:16px 20px; display:flex; justify-content:space-between; align-items:center;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <i class="bi bi-file-earmark-image" style="color:#fbb117; font-size:1.25rem;"></i>
                    <div>
                        <h3 id="publicTemplateTitle" style="color:#ffffff; margin:0; font-size:1.05rem; font-weight:700;">Sample Template Preview</h3>
                        <span style="font-size:0.75rem; color:#a7f3d0; text-transform:uppercase; letter-spacing:0.5px;">View-Only Reference • Cannot be downloaded</span>
                    </div>
                </div>
                <button type="button" class="modal-close" onclick="closePublicTemplateModal()" style="color:#ffffff; background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <div class="modal-body" style="padding: 20px; text-align: center; background: #f8fafc;">
                <div style="display: inline-flex; align-items: center; gap: 8px; background: #fef3c7; color: #92400e; padding: 5px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; margin-bottom: 12px; border: 1px solid #fde68a;">
                    <i class="bi bi-shield-lock-fill"></i> Official Barangay Tabon Template Sample (Preview Only)
                </div>
                <div style="max-height: 480px; overflow-y: auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px;">
                    <img id="publicTemplateImg" src="" alt="Sample Template" style="max-width: 100%; height: auto; display: block; margin: 0 auto; border-radius: 6px; pointer-events: auto; user-select: none; -webkit-user-drag: none;" draggable="false" oncontextmenu="return false;">
                </div>
                <p style="margin: 12px 0 0 0; font-size: 0.8rem; color: #64748b;">
                    <i class="bi bi-info-circle"></i> This sample image serves as a reference preview for residents. Downloading is disabled for security and document integrity.
                </p>
            </div>
            <div style="padding: 12px 20px; background: #ffffff; border-top: 1px solid #e2e8f0; text-align: right;">
                <button type="button" onclick="closePublicTemplateModal()" style="padding: 8px 20px; background: #e2e8f0; color: #334155; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Close Preview</button>
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
    let activePublicTemplateSrc = '';
    let activePublicTemplateName = '';

    function openPublicTemplatePreview() {
        if (activePublicTemplateSrc) {
            document.getElementById('publicTemplateTitle').textContent = activePublicTemplateName + ' - Sample Template';
            document.getElementById('publicTemplateImg').src = activePublicTemplateSrc;
            document.getElementById('publicTemplateModal').style.display = 'flex';
        }
    }

    function closePublicTemplateModal() {
        document.getElementById('publicTemplateModal').style.display = 'none';
    }

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
                activePublicTemplateSrc = 'Admin/' + svc.document_file;
                activePublicTemplateName = svc.name || 'Sample Template';
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
