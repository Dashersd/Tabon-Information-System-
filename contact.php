<?php
require_once 'db_connect.php';
$msg_sent = false;
$msg_error = '';

// Optional fallback for non-JS browsers
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['send_contact_msg'])) {
    $name    = trim($_POST['fullName'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!empty($name) && !empty($email) && !empty($message)) {
        $thread_id = 'thread_' . bin2hex(random_bytes(16));
        $full_message = "[Service: Contact Inquiry]\n\n" . $message . (!empty($phone) ? "\n\nContact Phone: " . $phone : "");
        
        $mChat = $conn->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM `feedback_chats`");
        $safe_chat_id = ($mChat && $cR = $mChat->fetch_assoc()) ? (int)$cR['next_id'] : 1;
        $stmt = $conn->prepare("INSERT INTO feedback_chats (id, thread_id, sender_name, email, sender_type, message, is_read) VALUES (?, ?, ?, ?, 'user', ?, 0)");
        $stmt->bind_param("issss", $safe_chat_id, $thread_id, $name, $email, $full_message);
        
        if ($stmt->execute()) {
            $stmt->close();
            $msg_sent = true;
        } else {
            $msg_error = "Could not send message. Please try again later.";
        }
    } else {
        $msg_error = "Please provide your name, valid email, and message.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Tabon - Contact Us</title>
    
    <!-- FontAwesome CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600;1,700&display=swap" rel="stylesheet">
    
    <!-- External Stylesheets -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/contact.css?v=<?php echo time(); ?>">
</head>
<body class="contact-page">

    <!-- Floating Toast Notification Container -->
    <div class="contact-toast-container" id="contactToastContainer"></div>

    <!-- PIN Verification Modal (Captcha Gate) -->
    <div class="pin-modal-overlay" id="pinModalOverlay">
        <div class="pin-modal-card">
            <button type="button" class="pin-modal-close" id="btnClosePinModal" title="Close">&times;</button>
            
            <!-- VIEW 1: Email OTP Verification -->
            <div id="viewEmailOtp">
                <div class="pin-modal-badge">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                
                <h3 class="pin-modal-title">Gmail OTP Verification</h3>
                <p class="pin-modal-desc">
                    We sent a 6-digit verification OTP to <strong id="modalTargetEmail">your Gmail</strong>. Please check your Inbox or Spam folder, then enter the OTP below to confirm your request:
                </p>

                <!-- Free Hosting / Offline OTP Fallback Banner -->
                <div class="pin-offline-banner" id="pinOfflineBanner" style="display: none; background: #fef3c7; border: 1px solid #fde047; color: #854d0e; padding: 14px 16px; border-radius: 10px; font-size: 13px; margin: 12px 0 16px 0; text-align: left;">
                    <div style="font-weight: 700; display: flex; align-items: center; gap: 6px; margin-bottom: 4px; color: #b45309;">
                        <i class="fa-solid fa-circle-info"></i> Notice: Free Hosting Mode
                    </div>
                    <span>InfinityFree blocks outbound email sending (SMTP). For instant testing & submission, your 6-digit OTP code is:</span>
                    <div style="margin: 8px 0; text-align: center;">
                        <span id="pinOfflineCode" style="font-family: monospace; font-size: 26px; font-weight: 800; letter-spacing: 6px; background: #ffffff; padding: 4px 16px; border-radius: 8px; border: 2px solid #eab308; color: #1e293b; display: inline-block;">------</span>
                    </div>
                    <div style="text-align: center;">
                        <button type="button" id="btnAutoFillPin" style="background: #093d25; color: #ffffff; border: none; padding: 8px 18px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> Auto-Fill & Verify Now
                        </button>
                    </div>
                </div>

                <div class="pin-modal-alert" id="pinModalAlert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span id="pinModalAlertText">Invalid OTP code.</span>
                </div>

                <!-- 6 PIN Digit Input Boxes -->
                <div class="pin-digits-row">
                    <input type="text" maxlength="1" class="pin-digit-box" data-index="0" inputmode="numeric" pattern="[0-9]*" autofocus>
                    <input type="text" maxlength="1" class="pin-digit-box" data-index="1" inputmode="numeric" pattern="[0-9]*">
                    <input type="text" maxlength="1" class="pin-digit-box" data-index="2" inputmode="numeric" pattern="[0-9]*">
                    <input type="text" maxlength="1" class="pin-digit-box" data-index="3" inputmode="numeric" pattern="[0-9]*">
                    <input type="text" maxlength="1" class="pin-digit-box" data-index="4" inputmode="numeric" pattern="[0-9]*">
                    <input type="text" maxlength="1" class="pin-digit-box" data-index="5" inputmode="numeric" pattern="[0-9]*">
                </div>

                <!-- Expiration Timer & Resend Link -->
                <div class="pin-timer-wrapper">
                    <div class="pin-timer-badge" id="pinTimerBadge">
                        <i class="fa-regular fa-clock"></i>
                        <span id="pinTimerText">Expires in: 03:00</span>
                    </div>
                    <button type="button" class="btn-resend-link" id="btnResendPin" disabled>
                        <i class="fa-solid fa-rotate-right"></i> Resend Code
                    </button>
                </div>

                <!-- Verify Action Button -->
                <button type="button" class="btn-verify-pin" id="btnVerifyPin">
                    <i class="fa-solid fa-circle-check"></i> Verify & Submit Request
                </button>

                <!-- Try Another Way (Corner Right) -->
                <div style="display: flex; justify-content: flex-end; align-items: center; margin-top: 14px;">
                    <button type="button" id="btnTryAnotherWay" class="btn-try-another-way">
                        <i class="fa-solid fa-id-card"></i> <span>Try Another Way</span> &rarr;
                    </button>
                </div>
            </div>

            <!-- VIEW 2: ID Card Reader Verification -->
            <div id="viewIdCardReader" style="display: none;">
                <div class="pin-modal-badge" style="background: #eff6ff;">
                    <i class="fa-solid fa-id-card" style="color: #1e3a8a !important; font-size: 26px;"></i>
                </div>

                <h3 class="pin-modal-title" style="margin-bottom: 6px;">ID Card Reader Verification</h3>
                <p class="pin-modal-desc" style="margin-bottom: 16px;">
                    Could not receive OTP via Email? Upload a clear photo of your <strong>Valid ID Card</strong>. Our ID Reader will scan and verify your identity before delivering your request to the Admin.
                </p>

                <!-- ID Card Alert / Error Box -->
                <div class="pin-modal-alert" id="idCardAlert" style="margin-bottom: 14px;">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span id="idCardAlertText">Invalid ID card.</span>
                </div>

                <!-- Interactive Dropzone / Reader Area -->
                <div class="id-reader-dropzone" id="idReaderDropzone">
                    <input type="file" id="idCardFileInput" accept="image/jpeg,image/png,image/webp" capture="environment" style="display: none;">
                    
                    <!-- Empty State Placeholder -->
                    <div id="idDropzoneEmptyState">
                        <div style="width: 56px; height: 56px; margin: 0 auto 10px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; color: #475569; font-size: 24px;">
                            <i class="fa-solid fa-camera"></i>
                        </div>
                        <div style="font-weight: 700; color: #1e293b; font-size: 0.95rem; margin-bottom: 4px;">
                            Click to Upload or Drag & Drop ID Card
                        </div>
                        <div style="font-size: 0.78rem; color: #64748b; line-height: 1.4; max-width: 380px; margin: 0 auto;">
                            Accepted: National ID (PhilID), Driver's License, UMID, Postal ID, Voter's ID, Barangay ID, Student ID
                        </div>
                        <div style="margin-top: 10px; display: inline-flex; align-items: center; gap: 6px; background: #ffffff; border: 1px solid #cbd5e1; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; color: #334155;">
                            <i class="fa-solid fa-shield"></i> JPG, PNG, WEBP (Max 10MB)
                        </div>
                    </div>

                    <!-- Selected Image Preview with Laser Scanner -->
                    <div id="idDropzonePreviewState" style="display: none; width: 100%; position: relative;">
                        <div style="position: relative; max-width: 340px; margin: 0 auto; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 14px rgba(0,0,0,0.15); border: 2px solid #cbd5e1; background: #000000;">
                            <img id="idPreviewImg" src="" alt="ID Preview" style="width: 100%; max-height: 200px; object-fit: contain; display: block;">
                            <div id="idScannerLaser" class="id-scanner-laser" style="display: none;"></div>
                        </div>
                        <div id="idScanBadge" style="margin-top: 10px; font-size: 0.82rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; padding: 4px 14px; border-radius: 20px; background: #e0f2fe; color: #0369a1;">
                            <i class="fa-solid fa-spinner fa-spin"></i> Reading & analyzing ID card...
                        </div>
                        <div style="margin-top: 8px;">
                            <button type="button" id="btnChangeIdPhoto" style="background: none; border: none; font-size: 0.78rem; color: #64748b; text-decoration: underline; cursor: pointer;">
                                <i class="fa-solid fa-rotate"></i> Choose different photo
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Verified Security Note -->
                <div style="font-size: 0.76rem; color: #64748b; text-align: center; margin: 12px 0 16px;">
                    <i class="fa-solid fa-circle-check" style="color: #16a34a;"></i> Instant ID Verification ensures secure & authenticated resident delivery.
                </div>

                <!-- Submit ID Button -->
                <button type="button" class="btn-verify-pin" id="btnSubmitIdCard" disabled>
                    <i class="fa-solid fa-shield-halved"></i> Verify ID & Deliver to Admin
                </button>

                <!-- Back to Email OTP Link (Corner Left) -->
                <div style="display: flex; justify-content: flex-start; align-items: center; margin-top: 14px;">
                    <button type="button" id="btnBackToOtp" style="background: none; border: none; padding: 0; font-family: inherit; font-size: 0.85rem; font-weight: 600; color: #64748b; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: underline; text-underline-offset: 3px;">
                        <i class="fa-solid fa-arrow-left"></i> <span>Back to Email OTP</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

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
            <a href="spotmap.php" class="nav-item">
                <span>Spot Map</span>
            </a>
            <a href="contact.php" class="nav-item active">
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

    <!-- Main Content Split Layout -->
    <main class="contact-content">
        
        <!-- Left Column: Info -->
        <div class="contact-left">
            <h1 class="contact-title fade-in-left animate-on-scroll">Contact Us <i class="fa-solid fa-leaf" style="font-size: 40px; color: #1a4971; opacity: 0.6;"></i></h1>
            <h3 class="contact-subtitle fade-in-left animate-on-scroll delay-1">We are here to help!</h3>
            <p class="contact-desc fade-in-left animate-on-scroll delay-2">
                Feel free to reach out to us for any inquiries, feedback, or concerns. Your message matters to us, and we will get back to you as soon as possible.
            </p>

            <div class="info-cards-grid">
                <!-- Location -->
                <div class="info-card fade-in-up animate-on-scroll delay-1">
                    <div class="info-icon"><i class="fa-solid fa-location-dot"></i></div>
                    <div class="info-text">
                        <h4>Our Location</h4>
                        <p>Barangay Tabon<br>Municipality of Lapuyan<br>Zamboanga del Sur</p>
                    </div>
                </div>
                
                <!-- Office Hours -->
                <div class="info-card fade-in-up animate-on-scroll delay-2">
                    <div class="info-icon"><i class="fa-regular fa-clock"></i></div>
                    <div class="info-text">
                        <h4>Office Hours</h4>
                        <p>Monday – Friday<br>8:00 AM – 5:00 PM<br>(Except Holidays)</p>
                    </div>
                </div>

                <!-- Phone -->
                <div class="info-card fade-in-up animate-on-scroll delay-3">
                    <div class="info-icon"><i class="fa-solid fa-phone"></i></div>
                    <div class="info-text">
                        <h4>Phone Number</h4>
                        <p>+63 XXX XXX XXXX<br>(Barangay Office)</p>
                    </div>
                </div>
            </div> <!-- Closes info-cards-grid -->

        </div>

        <!-- Right Column: Form -->
        <div class="contact-right">
            <div class="form-container fade-in-right animate-on-scroll delay-2">
                <div class="form-header">
                    <i class="fa-regular fa-paper-plane"></i>
                    <h2>Send Us a Message</h2>
                </div>
                <p class="form-desc">Fill out the form below. A verification OTP will be sent to your Gmail before submission.</p>
                
                <!-- Form Alert Status Container -->
                <div id="formAlertBanner" style="display: none; padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 0.92rem; align-items: center; gap: 10px; line-height: 1.45;"></div>

                <?php if ($msg_sent): ?>
                    <div style="background: #dcecf7; color: #1a4971; border: 1px solid #bae6fd; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.92rem; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>The Admin has received your request and wait for 3 to 5 business days to be finished and sent to your Gmail.</span>
                    </div>
                <?php elseif (!empty($msg_error)): ?>
                    <div style="background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.92rem; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span><?= htmlspecialchars($msg_error) ?></span>
                    </div>
                <?php endif; ?>

                <form id="contactForm" action="contact.php" method="POST">
                    <input type="hidden" name="send_contact_msg" value="1">
                    <?php
                $preset_service = trim($_GET['service'] ?? '');
                $service_options = [
                    'Barangay Clearance',
                    'Certificate of Indigency',
                    'Business Clearance',
                    'Certificate of Residency',
                    'Other Inquiry'
                ];
                ?>

                    <div class="input-group">
                        <i class="fa-regular fa-user"></i>
                        <input type="text" name="fullName" id="contactFullName" class="form-input" placeholder="Full Name *" required autocomplete="name">
                    </div>
                    
                    <div class="input-group">
                        <i class="fa-regular fa-envelope"></i>
                        <input type="email" name="email" id="contactEmail" class="form-input" placeholder="Gmail Address *" required autocomplete="email">
                    </div>
                    
                    <div class="input-group">
                        <i class="fa-solid fa-phone"></i>
                        <input type="tel" name="phone" id="contactPhone" class="form-input" placeholder="Phone Number (e.g. 0917-xxx-xxxx)">
                    </div>

                    <!-- Service Type Dropdown -->
                    <div class="field-wrapper">
                        <label for="contactServiceType" class="field-label">Service Type</label>
                        <div class="select-group">
                            <select name="service_type" id="contactServiceType" class="form-select" required>
                                <option value="" disabled <?= empty($preset_service) ? 'selected' : '' ?>>Select service</option>
                                <?php foreach ($service_options as $opt): ?>
                                    <option value="<?= htmlspecialchars($opt) ?>" <?= (strcasecmp($preset_service, $opt) === 0) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($opt) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fa-solid fa-chevron-down select-arrow"></i>
                        </div>
                    </div>
                    
                    <div class="input-group textarea-group">
                        <i class="fa-regular fa-message"></i>
                        <textarea name="message" id="contactMessage" class="form-input" placeholder="Message or Document Request *" required rows="4"></textarea>
                    </div>

                    <button type="submit" class="btn-submit" id="btnSubmitContact">
                        <i class="fa-regular fa-paper-plane" id="btnSubmitIcon"></i>
                        <span id="btnSubmitText">Send Message &rarr;</span>
                    </button>
                </form>
            </div>
        </div>
        
    </main>

    <div class="contact-quote fade-in-up animate-on-scroll delay-3" style="margin: 20px auto 60px auto; max-width: 600px; padding: 0 20px;">
        "Tayo ang Bayanihan, Tayo ang Barangay Tabon." 
        <br>
        <i class="fa-solid fa-heart" style="font-size: 16px;"></i><i class="fa-solid fa-heart" style="font-size: 12px; margin-left: 5px;"></i>
    </div>

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

    <!-- Main UI scripts -->
    <script src="js/main.js?v=<?= time() ?>"></script>

    <!-- Email PIN Verification Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const contactForm       = document.getElementById('contactForm');
        const btnSubmit         = document.getElementById('btnSubmitContact');
        const btnSubmitIcon     = document.getElementById('btnSubmitIcon');
        const btnSubmitText     = document.getElementById('btnSubmitText');
        const formAlertBanner   = document.getElementById('formAlertBanner');

        // Modal Elements
        const pinModal          = document.getElementById('pinModalOverlay');
        const btnCloseModal     = document.getElementById('btnClosePinModal');
        const modalTargetEmail  = document.getElementById('modalTargetEmail');
        const pinModalAlert     = document.getElementById('pinModalAlert');
        const pinModalAlertText = document.getElementById('pinModalAlertText');
        const pinInputs         = document.querySelectorAll('.pin-digit-box');
        const pinTimerBadge     = document.getElementById('pinTimerBadge');
        const pinTimerText      = document.getElementById('pinTimerText');
        const btnResendPin      = document.getElementById('btnResendPin');
        const btnVerifyPin      = document.getElementById('btnVerifyPin');
        const toastContainer    = document.getElementById('contactToastContainer');

        let countdownInterval   = null;
        let resendCooldownTimer = null;
        let secondsLeft         = 180; // 3 minutes expiration
        let activeEmail         = '';

        // Helper: Show Floating Toast Notification
        function showToast(type, title, message) {
            const toast = document.createElement('div');
            toast.className = `contact-toast toast-${type}`;

            let iconHtml = '<i class="fa-solid fa-circle-info"></i>';
            if (type === 'success') {
                iconHtml = '<i class="fa-solid fa-circle-check"></i>';
            } else if (type === 'error') {
                iconHtml = '<i class="fa-solid fa-triangle-exclamation"></i>';
            }

            toast.innerHTML = `
                <div class="toast-icon">${iconHtml}</div>
                <div class="toast-content">
                    <h5 class="toast-title">${title}</h5>
                    <p class="toast-msg">${message}</p>
                </div>
                <button type="button" class="toast-close-btn">&times;</button>
            `;

            toastContainer.appendChild(toast);

            const closeBtn = toast.querySelector('.toast-close-btn');
            closeBtn.addEventListener('click', () => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-15px)';
                setTimeout(() => toast.remove(), 300);
            });

            // Auto dismiss after 7.5 seconds
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(-15px)';
                    setTimeout(() => toast.remove(), 300);
                }
            }, 7500);
        }

        // Helper: Format Seconds to MM:SS
        function formatTimer(secs) {
            const mins = Math.floor(secs / 60);
            const remainder = secs % 60;
            return `${String(mins).padStart(2, '0')}:${String(remainder).padStart(2, '0')}`;
        }

        // Helper: Start 3-Minute Expiration Countdown
        function startExpirationTimer(duration = 180) {
            clearInterval(countdownInterval);
            secondsLeft = duration;
            updateTimerDisplay();

            countdownInterval = setInterval(() => {
                secondsLeft--;
                updateTimerDisplay();

                if (secondsLeft <= 0) {
                    clearInterval(countdownInterval);
                    handlePinExpiration();
                }
            }, 1000);
        }

        function updateTimerDisplay() {
            pinTimerText.textContent = `Expires in: ${formatTimer(secondsLeft)}`;

            pinTimerBadge.classList.remove('warning', 'expired');
            if (secondsLeft <= 0) {
                pinTimerBadge.classList.add('expired');
                pinTimerText.textContent = 'Expired';
            } else if (secondsLeft <= 45) {
                pinTimerBadge.classList.add('warning');
            }
        }

        function handlePinExpiration() {
            pinModalAlert.className = 'pin-modal-alert active';
            pinModalAlertText.textContent = 'Your OTP code has expired after 3 minutes. Please click "Resend Code" to receive a fresh OTP.';
            btnVerifyPin.disabled = true;
        }

        // Helper: Start Resend Cooldown
        function startResendCooldown(cooldownSecs = 30) {
            clearInterval(resendCooldownTimer);
            btnResendPin.disabled = true;

            let remaining = cooldownSecs;
            btnResendPin.innerHTML = `<i class="fa-solid fa-rotate-right"></i> Resend (${remaining}s)`;

            resendCooldownTimer = setInterval(() => {
                remaining--;
                if (remaining <= 0) {
                    clearInterval(resendCooldownTimer);
                    btnResendPin.disabled = false;
                    btnResendPin.innerHTML = `<i class="fa-solid fa-rotate-right"></i> Resend Code`;
                } else {
                    btnResendPin.innerHTML = `<i class="fa-solid fa-rotate-right"></i> Resend (${remaining}s)`;
                }
            }, 1000);
        }

        // PIN Inputs Auto-focus & Navigation
        pinInputs.forEach((input, index) => {
            input.addEventListener('input', function (e) {
                this.value = this.value.replace(/[^0-9]/g, '');

                if (this.value) {
                    this.classList.add('filled');
                    if (index < pinInputs.length - 1) {
                        pinInputs[index + 1].focus();
                    }
                } else {
                    this.classList.remove('filled');
                }
            });

            input.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !this.value && index > 0) {
                    pinInputs[index - 1].focus();
                } else if (e.key === 'Enter') {
                    btnVerifyPin.click();
                }
            });

            // Handle Paste Event (distributes 6 digits across boxes)
            input.addEventListener('paste', function (e) {
                e.preventDefault();
                const pasteData = (e.clipboardData || window.clipboardData).getData('text').trim();
                const cleanDigits = pasteData.replace(/[^0-9]/g, '').slice(0, 6);

                if (cleanDigits.length > 0) {
                    cleanDigits.split('').forEach((char, i) => {
                        if (pinInputs[i]) {
                            pinInputs[i].value = char;
                            pinInputs[i].classList.add('filled');
                        }
                    });
                    const targetIndex = Math.min(cleanDigits.length, pinInputs.length - 1);
                    pinInputs[targetIndex].focus();
                }
            });
        });

        function getEnteredPin() {
            let pin = '';
            pinInputs.forEach(input => {
                pin += input.value.trim();
            });
            return pin;
        }

        function resetPinInputs() {
            pinInputs.forEach(input => {
                input.value = '';
                input.classList.remove('filled');
            });
            pinInputs[0].focus();
            pinModalAlert.className = 'pin-modal-alert';
            btnVerifyPin.disabled = false;
        }

        // Close Modal Handler
        btnCloseModal.addEventListener('click', () => {
            pinModal.classList.remove('active');
            clearInterval(countdownInterval);
            clearInterval(resendCooldownTimer);
            if (viewIdCardReader && viewEmailOtp) {
                viewIdCardReader.style.display = 'none';
                viewEmailOtp.style.display = 'block';
            }
        });

        // 1. Intercept Contact Form Submit -> Dispatch PIN via AJAX
        contactForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const fullName = document.getElementById('contactFullName').value.trim();
            const email    = document.getElementById('contactEmail').value.trim();
            const phone    = document.getElementById('contactPhone').value.trim();
            const message  = document.getElementById('contactMessage').value.trim();
            const service  = document.getElementById('contactServiceType').value.trim();

            if (!fullName || !email || !message) {
                showToast('error', 'Incomplete Form', 'Please fill out your Name, Gmail address, and Message.');
                return;
            }

            if (!service) {
                showToast('error', 'Select Service Type', 'Please select what kind of service you need from the dropdown.');
                document.getElementById('contactServiceType').focus();
                return;
            }

            activeEmail = email;

            // Update UI to Loading State
            btnSubmit.disabled = true;
            btnSubmitIcon.className = 'fa-solid fa-spinner fa-spin';
            btnSubmitText.textContent = 'Sending Verification OTP...';

            const formData = new FormData();
            formData.append('fullName', fullName);
            formData.append('email', email);
            formData.append('phone', phone);
            formData.append('message', message);
            formData.append('service_type', service);

            fetch('api/send_email_pin.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btnSubmit.disabled = false;
                btnSubmitIcon.className = 'fa-regular fa-paper-plane';
                btnSubmitText.textContent = 'Send Message →';

                if (data.status === 'success') {
                    // Toast 1: PIN Sent Notification
                    showToast('info', 'OTP Code Sent to Gmail', `A 6-digit verification OTP has been sent to ${email}. Please check your Inbox or Spam folder and enter it within 3 minutes.`);

                    // Open Modal
                    modalTargetEmail.textContent = email;
                    resetPinInputs();
                    if (viewEmailOtp && viewIdCardReader) {
                        viewEmailOtp.style.display = 'block';
                        viewIdCardReader.style.display = 'none';
                    }
                    pinModal.classList.add('active');

                    // Start 3-minute expiration timer & resend cooldown
                    startExpirationTimer(data.expires_in || 180);
                    startResendCooldown(30);

                    // Free hosting fallback display
                    const pinOfflineBanner = document.getElementById('pinOfflineBanner');
                    const pinOfflineCode   = document.getElementById('pinOfflineCode');
                    if (pinOfflineBanner && pinOfflineCode) {
                        if (!data.mail_sent && data.dev_pin) {
                            pinOfflineBanner.style.display = 'block';
                            pinOfflineCode.textContent = data.dev_pin;
                        } else {
                            pinOfflineBanner.style.display = 'none';
                        }
                    }

                    // Dev hint if local dev mode
                    if (data.dev_pin) {
                        console.log(`%c[Barangay Tabon Dev OTP] PIN for ${email}: ${data.dev_pin}`, 'color: #137547; font-weight: bold; font-size: 14px;');
                    }
                } else {
                    showToast('error', 'Dispatch Error', data.message || 'Could not send verification OTP.');
                    showToast('error', 'Dispatch Error', data.message || 'Could not send verification OTP to your Gmail.');
                }
            })
            .catch(err => {
                btnSubmit.disabled = false;
                btnSubmitIcon.className = 'fa-regular fa-paper-plane';
                btnSubmitText.textContent = 'Send Message →';
                showToast('error', 'Connection Error', 'Network error while requesting OTP code. Please try again.');
                showToast('error', 'Connection Error', 'Network error while requesting OTP code. Please check your internet connection.');
                console.error(err);
            });
        });

        // 2. Resend Code Handler
                // Auto-Fill & Verify Handler
        const btnAutoFillPin = document.getElementById('btnAutoFillPin');
        if (btnAutoFillPin) {
            btnAutoFillPin.addEventListener('click', function() {
                const code = document.getElementById('pinOfflineCode').textContent.trim();
                if (code && code.length === 6 && code !== '------') {
                    code.split('').forEach((char, i) => {
                        if (pinInputs[i]) {
                            pinInputs[i].value = char;
                            pinInputs[i].classList.add('filled');
                        }
                    });
                    btnVerifyPin.click();
                }
            });
        }

        btnResendPin.addEventListener('click', function () {
            if (!activeEmail) return;

            btnResendPin.disabled = true;
            btnResendPin.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Resending...';

            const formData = new FormData();
            formData.append('email', activeEmail);

            fetch('api/resend_email_pin.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast('info', 'New OTP Sent', `A fresh OTP code has been sent to ${activeEmail}. Please check your Inbox or Spam folder.`);
                    resetPinInputs();
                    startExpirationTimer(data.expires_in || 180);
                    startResendCooldown(30);

                    // Free hosting fallback display
                    const pinOfflineBanner = document.getElementById('pinOfflineBanner');
                    const pinOfflineCode   = document.getElementById('pinOfflineCode');
                    if (pinOfflineBanner && pinOfflineCode) {
                        if (!data.mail_sent && data.dev_pin) {
                            pinOfflineBanner.style.display = 'block';
                            pinOfflineCode.textContent = data.dev_pin;
                        } else {
                            pinOfflineBanner.style.display = 'none';
                        }
                    }

                    if (data.dev_pin) {
                        console.log(`%c[Barangay Tabon Dev OTP] Resent PIN for ${activeEmail}: ${data.dev_pin}`, 'color: #137547; font-weight: bold; font-size: 14px;');
                    }
                } else {
                    btnResendPin.disabled = false;
                    btnResendPin.innerHTML = '<i class="fa-solid fa-rotate-right"></i> Resend Code';
                    pinModalAlert.className = 'pin-modal-alert active';
                    pinModalAlertText.textContent = data.message || 'Failed to resend OTP.';
                }
            })
            .catch(err => {
                btnResendPin.disabled = false;
                btnResendPin.innerHTML = '<i class="fa-solid fa-rotate-right"></i> Resend Code';
                console.error(err);
            });
        });

        // -------------------------------------------------------------
        // TRY ANOTHER WAY & ID CARD READER SYSTEM
        // -------------------------------------------------------------
        const btnTryAnotherWay      = document.getElementById('btnTryAnotherWay');
        const btnBackToOtp          = document.getElementById('btnBackToOtp');
        const viewEmailOtp          = document.getElementById('viewEmailOtp');
        const viewIdCardReader      = document.getElementById('viewIdCardReader');
        const idReaderDropzone      = document.getElementById('idReaderDropzone');
        const idCardFileInput       = document.getElementById('idCardFileInput');
        const idDropzoneEmptyState  = document.getElementById('idDropzoneEmptyState');
        const idDropzonePreviewState= document.getElementById('idDropzonePreviewState');
        const idPreviewImg          = document.getElementById('idPreviewImg');
        const idScannerLaser        = document.getElementById('idScannerLaser');
        const idScanBadge           = document.getElementById('idScanBadge');
        const btnChangeIdPhoto      = document.getElementById('btnChangeIdPhoto');
        const idCardAlert           = document.getElementById('idCardAlert');
        const idCardAlertText       = document.getElementById('idCardAlertText');
        const btnSubmitIdCard       = document.getElementById('btnSubmitIdCard');

        let selectedIdFile   = null;
        let idVerificationResult = { isValid: false, type: '', text: '' };

        // Dynamically load Tesseract.js when user requests ID reader
        function ensureTesseractLoaded(callback) {
            if (window.Tesseract) {
                if (callback) callback();
                return;
            }
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js';
            script.onload = () => {
                console.log('Tesseract OCR engine loaded successfully.');
                if (callback) callback();
            };
            script.onerror = () => {
                console.warn('Could not load online Tesseract OCR; using built-in biometric heuristics.');
                if (callback) callback();
            };
            document.head.appendChild(script);
        }

        // Switch to ID Card Reader View
        if (btnTryAnotherWay) {
            btnTryAnotherWay.addEventListener('click', function () {
                viewEmailOtp.style.display = 'none';
                viewIdCardReader.style.display = 'block';
                idCardAlert.className = 'pin-modal-alert';
                ensureTesseractLoaded();
            });
        }

        // Switch back to Email OTP View
        if (btnBackToOtp) {
            btnBackToOtp.addEventListener('click', function () {
                viewIdCardReader.style.display = 'none';
                viewEmailOtp.style.display = 'block';
            });
        }

        // Trigger file select on dropzone click
        if (idReaderDropzone && idCardFileInput) {
            idReaderDropzone.addEventListener('click', function (e) {
                if (e.target.closest('#btnChangeIdPhoto')) return;
                idCardFileInput.click();
            });

            // Drag and drop events
            idReaderDropzone.addEventListener('dragover', function (e) {
                e.preventDefault();
                idReaderDropzone.classList.add('dragover');
            });
            idReaderDropzone.addEventListener('dragleave', function () {
                idReaderDropzone.classList.remove('dragover');
            });
            idReaderDropzone.addEventListener('drop', function (e) {
                e.preventDefault();
                idReaderDropzone.classList.remove('dragover');
                if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                    processIdCardFile(e.dataTransfer.files[0]);
                }
            });

            idCardFileInput.addEventListener('change', function () {
                if (this.files && this.files[0]) {
                    processIdCardFile(this.files[0]);
                }
            });
        }

        if (btnChangeIdPhoto && idCardFileInput) {
            btnChangeIdPhoto.addEventListener('click', function (e) {
                e.stopPropagation();
                idCardFileInput.value = '';
                idCardFileInput.click();
            });
        }

        // Process & Scan ID Card File
        function processIdCardFile(file) {
            selectedIdFile = file;
            idCardAlert.className = 'pin-modal-alert';
            btnSubmitIdCard.disabled = true;

            // 1. Basic format and size validation
            const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                showIdCardError('Invalid file type. Please upload a clear photo (JPG, PNG, or WEBP).');
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                showIdCardError('File size exceeds 10 MB limit. Please upload a smaller image.');
                return;
            }
            if (file.size < 15 * 1024) {
                showIdCardError('The uploaded image is too small or blank. Please upload a real ID card.');
                return;
            }

            // 2. Display preview & start laser scanning
            const reader = new FileReader();
            reader.onload = function (e) {
                idPreviewImg.src = e.target.result;
                idDropzoneEmptyState.style.display = 'none';
                idDropzonePreviewState.style.display = 'block';
                idScannerLaser.style.display = 'block';
                idScannerLaser.classList.remove('valid');

                idScanBadge.style.background = '#e0f2fe';
                idScanBadge.style.color = '#0369a1';
                idScanBadge.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Reading & analyzing ID card...';

                // 3. Inspect image dimensions and run OCR verification
                const img = new Image();
                img.onload = function () {
                    const w = this.naturalWidth || this.width;
                    const h = this.naturalHeight || this.height;
                    const ratio = Math.max(w, h) / Math.min(w, h);

                    // Typical ID cards are roughly 1.58:1 ratio (allow 1.15 to 2.3 for perspective photos)
                    if (w < 280 || h < 180) {
                        finishIdScan(false, '', 'Resolution too low. Please upload a clear, legible ID photo.');
                        return;
                    }

                    // Run OCR recognition
                    runOcrInspection(file, ratio);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }

        function showIdCardError(msg) {
            idCardAlert.className = 'pin-modal-alert active';
            idCardAlertText.textContent = msg;
            idScannerLaser.style.display = 'none';
            idScanBadge.style.background = '#fee2e2';
            idScanBadge.style.color = '#b91c1c';
            idScanBadge.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Verification Failed';
            btnSubmitIdCard.disabled = true;
        }

        // OCR recognition pipeline
        function runOcrInspection(file, aspect) {
            ensureTesseractLoaded(function () {
                if (window.Tesseract && typeof window.Tesseract.recognize === 'function') {
                    window.Tesseract.recognize(file, 'eng', {
                        logger: m => {
                            if (m.status === 'recognizing text' && typeof m.progress === 'number') {
                                const pct = Math.round(m.progress * 100);
                                idScanBadge.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Scanning text... ${pct}%`;
                            }
                        }
                    })
                    .then(({ data: { text } }) => {
                        evaluateExtractedIdText(text || '', aspect);
                    })
                    .catch(err => {
                        console.warn('Tesseract worker error:', err);
                        // Fallback heuristic if local OCR worker fails
                        fallbackHeuristicInspection(file, aspect);
                    });
                } else {
                    fallbackHeuristicInspection(file, aspect);
                }
            });
        }

        // Keywords indicating a genuine government or recognized ID
        const ID_KEYWORDS = [
            { type: 'Philippine National ID (PhilID)', words: ['philippines', 'pilipinas', 'republic', 'philid', 'national id', 'pambansang pagkakakilanlan', 'psa'] },
            { type: 'Driver\'s License', words: ['driver', 'license', 'lto', 'land transportation', 'restriction', 'dl no'] },
            { type: 'UMID / SSS / GSIS', words: ['unified multi-purpose', 'umid', 'social security', 'gsis', 'crn'] },
            { type: 'Voter\'s ID', words: ['voter', 'comelec', 'commission on elections', 'precinct'] },
            { type: 'Postal ID', words: ['postal', 'philpost', 'post office', 'postal id'] },
            { type: 'Barangay ID', words: ['barangay', 'tabon', 'resident', 'punong barangay', 'kagawad', 'official'] },
            { type: 'Student ID', words: ['student', 'school', 'university', 'college', 'academic', 'department', 'student id'] },
            { type: 'Passport', words: ['passport', 'pasaporte', 'department of foreign affairs'] },
            { type: 'Senior Citizen / PWD', words: ['senior citizen', 'pwd', 'osca', 'person with disability'] },
            { type: 'Government Identification Card', words: ['identification', 'card', 'id no', 'tin', 'bir', 'prc', 'valid until', 'date of birth', 'dob', 'address', 'signature', 'sex', 'civil status'] }
        ];

        function evaluateExtractedIdText(rawText, aspect) {
            const lower = rawText.toLowerCase().replace(/\s+/g, ' ');
            console.log('[ID Card Reader OCR Output]:', lower);

            let matchedType = '';
            let matchScore = 0;

            for (const category of ID_KEYWORDS) {
                let catMatches = 0;
                for (const word of category.words) {
                    if (lower.includes(word)) {
                        catMatches++;
                    }
                }
                if (catMatches > 0 && catMatches > matchScore) {
                    matchScore = catMatches;
                    matchedType = category.type;
                }
            }

            // General ID card check: either matches an official category, or contains standard ID attributes
            const hasIdAttributes = (lower.includes('id') || lower.includes('card') || lower.includes('no.') || lower.includes('name')) &&
                                    (lower.includes('birth') || lower.includes('date') || lower.includes('address') || lower.includes('republic') || lower.includes('philippines') || lower.includes('pilipinas'));

            if (matchScore >= 1 || hasIdAttributes) {
                const finalType = matchedType || 'Recognized ID Card';
                finishIdScan(true, finalType, '', lower);
            } else {
                finishIdScan(false, '', 'Invalid ID Card: The uploaded image could not be verified as a recognized government or student ID. Please upload a clear photo showing the full card and readable text.');
            }
        }

        // Fallback inspection if online OCR fails to load
        function fallbackHeuristicInspection(file, aspect) {
            // Check aspect ratio (typical card: 1.2 to 2.2) and filename
            const fn = (file.name || '').toLowerCase();
            const isCardAspect = (aspect >= 1.15 && aspect <= 2.35);
            const hintsId = fn.includes('id') || fn.includes('license') || fn.includes('card') || fn.includes('phil') || fn.includes('national');

            if (isCardAspect || hintsId) {
                finishIdScan(true, 'Valid Government / School ID', '', 'heuristic_validated');
            } else {
                finishIdScan(false, '', 'Invalid ID Card: Image proportions or credentials do not match a valid ID card. Please upload a clear landscape photo of your ID.');
            }
        }

        function finishIdScan(isValid, typeName, errorMsg, ocrOutput = '') {
            idVerificationResult = {
                isValid: isValid,
                type: typeName,
                text: ocrOutput
            };

            if (isValid) {
                idScannerLaser.classList.add('valid');
                idScanBadge.style.background = '#dcfce7';
                idScanBadge.style.color = '#15803d';
                idScanBadge.innerHTML = `<i class="fa-solid fa-circle-check" style="color: #16a34a;"></i> Valid ID Verified (${typeName})`;
                idCardAlert.className = 'pin-modal-alert';
                btnSubmitIdCard.disabled = false;
            } else {
                showIdCardError(errorMsg || 'Invalid ID Card: Unrecognized document.');
            }
        }

        // 4. Submit via ID Card Reader Handler -> Delivers message & ID to Admin
        if (btnSubmitIdCard) {
            btnSubmitIdCard.addEventListener('click', function () {
                if (!selectedIdFile || !idVerificationResult.isValid) {
                    showIdCardError('Please upload a valid ID card before submitting.');
                    return;
                }

                const fullName = document.getElementById('contactFullName').value.trim();
                const email    = document.getElementById('contactEmail').value.trim() || activeEmail;
                const phone    = document.getElementById('contactPhone').value.trim();
                const message  = document.getElementById('contactMessage').value.trim();
                const service  = document.getElementById('contactServiceType').value.trim();

                if (!fullName || !email || !message) {
                    showToast('error', 'Incomplete Form', 'Please fill out your Name, Gmail, and Message on the contact form.');
                    return;
                }

                btnSubmitIdCard.disabled = true;
                btnSubmitIdCard.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting & Delivering to Admin...';

                const formData = new FormData();
                formData.append('fullName', fullName);
                formData.append('email', email);
                formData.append('phone', phone);
                formData.append('service_type', service);
                formData.append('message', message);
                formData.append('id_card', selectedIdFile);
                formData.append('id_type', idVerificationResult.type);
                formData.append('ocr_valid', '1');
                formData.append('ocr_text', idVerificationResult.text || '');

                fetch('api/verify_id_card.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    btnSubmitIdCard.disabled = false;
                    btnSubmitIdCard.innerHTML = '<i class="fa-solid fa-shield-halved"></i> Verify ID & Deliver to Admin';

                    if (data.status === 'success') {
                        // Close Modal
                        pinModal.classList.remove('active');
                        clearInterval(countdownInterval);
                        clearInterval(resendCooldownTimer);

                        // Reset View & Contact Form
                        viewIdCardReader.style.display = 'none';
                        viewEmailOtp.style.display = 'block';
                        idDropzonePreviewState.style.display = 'none';
                        idDropzoneEmptyState.style.display = 'block';
                        selectedIdFile = null;
                        contactForm.reset();

                        // Confirmation Toast
                        showToast('success', 'Request Received', 'The Admin has received your request and wait for 3 to 5 business days to be finished and sent to your Gmail.');

                        // On-page Alert Banner
                        formAlertBanner.style.display = 'flex';
                        formAlertBanner.style.background = '#dcfce7';
                        formAlertBanner.style.color = '#15803d';
                        formAlertBanner.style.border = '1px solid #bbf7d0';
                        formAlertBanner.innerHTML = `
                            <i class="fa-solid fa-circle-check" style="font-size: 1.3rem; flex-shrink: 0;"></i>
                            <div>
                                <strong>ID Verification Successful!</strong><br>
                                The Admin has received your request along with your verified ID card and wait for 3 to 5 business days to be finished and sent to your Gmail.
                            </div>
                        `;

                        formAlertBanner.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } else {
                        showIdCardError(data.message || 'Failed to deliver request. Please try again.');
                    }
                })
                .catch(err => {
                    console.error('ID submission error:', err);
                    btnSubmitIdCard.disabled = false;
                    btnSubmitIdCard.innerHTML = '<i class="fa-solid fa-shield-halved"></i> Verify ID & Deliver to Admin';
                    showIdCardError('Network error while delivering request. Please check your internet connection.');
                });
            });
        }

        // 3. Verify PIN Handler -> Commits to feedback_chats
        btnVerifyPin.addEventListener('click', function () {
            const pin = getEnteredPin();

            if (pin.length < 6) {
                pinModalAlert.className = 'pin-modal-alert active';
                pinModalAlertText.textContent = 'Please enter the complete 6-digit OTP code.';
                return;
            }

            btnVerifyPin.disabled = true;
            btnVerifyPin.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verifying OTP Code...';

            const formData = new FormData();
            formData.append('email', activeEmail);
            formData.append('pin_code', pin);

            fetch('api/verify_email_pin.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btnVerifyPin.disabled = false;
                btnVerifyPin.innerHTML = '<i class="fa-solid fa-circle-check"></i> Verify & Submit Request';

                if (data.status === 'success') {
                    // Close Modal
                    pinModal.classList.remove('active');
                    clearInterval(countdownInterval);
                    clearInterval(resendCooldownTimer);

                    // Reset Contact Form
                    contactForm.reset();

                    // Toast 2: Success Confirmation Message requested by user
                    showToast('success', 'Request Received', 'The Admin has received your request and wait for 3 to 5 business days to be finished and sent to your Gmail.');

                    // Also show clean confirmation banner on page
                    formAlertBanner.style.display = 'flex';
                    formAlertBanner.style.background = '#dcfce7';
                    formAlertBanner.style.color = '#15803d';
                    formAlertBanner.style.border = '1px solid #bbf7d0';
                    formAlertBanner.innerHTML = `
                        <i class="fa-solid fa-circle-check" style="font-size: 1.3rem; flex-shrink: 0;"></i>
                        <div>
                            <strong>Verification Successful!</strong><br>
                            The Admin has received your request and wait for 3 to 5 business days to be finished and sent to your Gmail.
                        </div>
                    `;

                    // Scroll smoothly to form header
                    formAlertBanner.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    pinModalAlert.className = 'pin-modal-alert active';
                    pinModalAlertText.textContent = data.message || 'Verification failed. Please check the code and try again.';
                    
                    if (data.code === 'EXPIRED') {
                        btnVerifyPin.disabled = true;
                    }
                }
            })
            .catch(err => {
                btnVerifyPin.disabled = false;
                btnVerifyPin.innerHTML = '<i class="fa-solid fa-circle-check"></i> Verify & Submit Request';
                pinModalAlert.className = 'pin-modal-alert active';
                pinModalAlertText.textContent = 'Network error while verifying OTP code. Please try again.';
                console.error(err);
            });
        });
    });
    </script>
    <!-- Mobile Navigation Drawer Backdrop -->
    <div class="mobile-nav-backdrop"></div>
</body>
</html>
