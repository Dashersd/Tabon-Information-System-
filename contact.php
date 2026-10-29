<?php
require_once 'db_connect.php';
$msg_sent = false;
$msg_error = '';

// Optional fallback for non-JS browsers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_contact_msg'])) {
    $name    = trim($_POST['fullName'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!empty($name) && !empty($email) && !empty($message)) {
        $thread_id = 'thread_' . bin2hex(random_bytes(16));
        $full_message = "[Service: Contact Inquiry]\n\n" . $message . (!empty($phone) ? "\n\nContact Phone: " . $phone : "");
        
        $stmt = $conn->prepare("INSERT INTO feedback_chats (thread_id, sender_name, email, sender_type, message, is_read) VALUES (?, ?, ?, 'user', ?, 0)");
        $stmt->bind_param("ssss", $thread_id, $name, $email, $full_message);
        
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
            
            <div class="pin-modal-badge">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            
            <h3 class="pin-modal-title">Gmail Verification</h3>
            <p class="pin-modal-desc">
                We sent a 6-digit verification PIN to <strong id="modalTargetEmail">your Gmail</strong>. Enter the PIN below to confirm your request:
            </p>

            <!-- Alert box inside modal for error/expiry messages -->
            <div class="pin-modal-alert" id="pinModalAlert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span id="pinModalAlertText">Invalid PIN code.</span>
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
        </div>
    </div>

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

            <a href="login.php" class="btn-login">
                <i class="fa-regular fa-user"></i>
                <span>Sign In / Login</span>
            </a>
        </nav>
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
                <p class="form-desc">Fill out the form below. A verification PIN will be sent to your Gmail before submission.</p>
                
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
            pinModalAlertText.textContent = 'Your PIN code has expired after 3 minutes. Please click "Resend Code" to receive a fresh PIN.';
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
            btnSubmitText.textContent = 'Sending Verification PIN...';

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
                    showToast('info', 'PIN Code Sent to Gmail', `A 6-digit verification PIN has been sent to ${email}. Please enter it within 3 minutes.`);

                    // Open Modal
                    modalTargetEmail.textContent = email;
                    resetPinInputs();
                    pinModal.classList.add('active');

                    // Start 3-minute expiration timer & resend cooldown
                    startExpirationTimer(data.expires_in || 180);
                    startResendCooldown(30);

                    // Dev hint if local dev mode
                    if (data.dev_pin) {
                        console.log(`%c[Barangay Tabon Dev OTP] PIN for ${email}: ${data.dev_pin}`, 'color: #137547; font-weight: bold; font-size: 14px;');
                    }
                } else {
                    showToast('error', 'Dispatch Error', data.message || 'Could not send verification PIN.');
                }
            })
            .catch(err => {
                btnSubmit.disabled = false;
                btnSubmitIcon.className = 'fa-regular fa-paper-plane';
                btnSubmitText.textContent = 'Send Message →';
                showToast('error', 'Connection Error', 'Network error while requesting PIN code. Please try again.');
                console.error(err);
            });
        });

        // 2. Resend Code Handler
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
                    showToast('info', 'New PIN Sent', `A fresh PIN code has been sent to ${activeEmail}. Timer reset to 3 minutes.`);
                    resetPinInputs();
                    startExpirationTimer(data.expires_in || 180);
                    startResendCooldown(30);

                    if (data.dev_pin) {
                        console.log(`%c[Barangay Tabon Dev OTP] Resent PIN for ${activeEmail}: ${data.dev_pin}`, 'color: #137547; font-weight: bold; font-size: 14px;');
                    }
                } else {
                    btnResendPin.disabled = false;
                    btnResendPin.innerHTML = '<i class="fa-solid fa-rotate-right"></i> Resend Code';
                    pinModalAlert.className = 'pin-modal-alert active';
                    pinModalAlertText.textContent = data.message || 'Failed to resend PIN.';
                }
            })
            .catch(err => {
                btnResendPin.disabled = false;
                btnResendPin.innerHTML = '<i class="fa-solid fa-rotate-right"></i> Resend Code';
                console.error(err);
            });
        });

        // 3. Verify PIN Handler -> Commits to feedback_chats
        btnVerifyPin.addEventListener('click', function () {
            const pin = getEnteredPin();

            if (pin.length < 6) {
                pinModalAlert.className = 'pin-modal-alert active';
                pinModalAlertText.textContent = 'Please enter the complete 6-digit PIN code.';
                return;
            }

            btnVerifyPin.disabled = true;
            btnVerifyPin.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verifying Code...';

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
                pinModalAlertText.textContent = 'Network error while verifying PIN code. Please try again.';
                console.error(err);
            });
        });
    });
    </script>
</body>
</html>
