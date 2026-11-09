<?php
require_once 'db_connect.php';
$msg_sent = false;
$msg_error = '';

// Handle Direct Feedback Submission (both standard POST and AJAX)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['send_contact_msg'])) {
    $name    = trim($_POST['fullName'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $service = trim($_POST['service_type'] ?? 'Citizen Feedback');
    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    if (!empty($name) && !empty($email) && !empty($message)) {
        try {
            $conn->query("CREATE TABLE IF NOT EXISTS `feedback_chats` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `thread_id` varchar(100) NOT NULL,
              `sender_name` varchar(255) NOT NULL,
              `email` varchar(150) DEFAULT NULL,
              `sender_type` enum('user','admin') NOT NULL DEFAULT 'user',
              `message` text NOT NULL,
              `file_attachment` varchar(255) DEFAULT NULL,
              `file_name` varchar(255) DEFAULT NULL,
              `is_read` tinyint(1) DEFAULT 0,
              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              KEY `idx_thread_id` (`thread_id`),
              KEY `idx_sender_type_read` (`sender_type`, `is_read`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $chatZero = $conn->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM `feedback_chats`");
            $fix_chat = ($chatZero && $cRow = $chatZero->fetch_assoc()) ? (int)$cRow['next_id'] : 1;
            $conn->query("UPDATE `feedback_chats` SET `id` = $fix_chat WHERE `id` = 0");
            $conn->query("ALTER TABLE `feedback_chats` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT");
        } catch (\Throwable $fixEx) {}

        $thread_id = 'thread_' . bin2hex(random_bytes(16));
        $full_message = "[Service: " . $service . "]\n\n" . $message . (!empty($phone) ? "\n\nContact Phone: " . $phone : "");
        
        $mChat = $conn->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM `feedback_chats`");
        $safe_chat_id = ($mChat && $cR = $mChat->fetch_assoc()) ? (int)$cR['next_id'] : 1;
        $stmt = $conn->prepare("INSERT INTO feedback_chats (id, thread_id, sender_name, email, sender_type, message, is_read, created_at) VALUES (?, ?, ?, ?, 'user', ?, 0, NOW())");
        $stmt->bind_param("issss", $safe_chat_id, $thread_id, $name, $email, $full_message);
        
        if ($stmt->execute()) {
            $stmt->close();
            $msg_sent = true;
            if ($is_ajax) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Thank you! Your feedback has been sent directly to the Barangay Admin.'
                ]);
                exit();
            }
        } else {
            $msg_error = "Could not send feedback. Please try again later.";
            if ($is_ajax) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode([
                    'status' => 'error',
                    'message' => $msg_error
                ]);
                exit();
            }
        }
    } else {
        $msg_error = "Please provide your name, valid email, and message.";
        if ($is_ajax) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'status' => 'error',
                'message' => $msg_error
            ]);
            exit();
        }
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
                <p class="form-desc">Fill out the form below with your feedback, message, or inquiry. It will be sent directly to the Barangay Admin.</p>
                
                <!-- Form Alert Status Container -->
                <div id="formAlertBanner" style="display: none; padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 0.92rem; align-items: center; gap: 10px; line-height: 1.45;"></div>

                <?php if ($msg_sent): ?>
                    <div style="background: #dcecf7; color: #1a4971; border: 1px solid #bae6fd; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.92rem; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Thank you! Your feedback has been sent directly to the Barangay Admin.</span>
                    </div>
                <?php elseif (!empty($msg_error)): ?>
                    <div style="background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.92rem; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span><?= htmlspecialchars($msg_error) ?></span>
                    </div>
                <?php endif; ?>

                <form id="contactForm" action="contact.php" method="POST">
                    <input type="hidden" name="send_contact_msg" value="1">
                    <input type="hidden" name="service_type" id="contactServiceType" value="Citizen Feedback">

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
                    
                    <div class="input-group textarea-group">
                        <i class="fa-regular fa-message"></i>
                        <textarea name="message" id="contactMessage" class="form-input" placeholder="Your Message, Feedback, or Inquiry *" required rows="4"></textarea>
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

    <!-- Direct Citizen Feedback Submission Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const contactForm     = document.getElementById('contactForm');
        const btnSubmit       = document.getElementById('btnSubmitContact');
        const btnSubmitIcon   = document.getElementById('btnSubmitIcon');
        const btnSubmitText   = document.getElementById('btnSubmitText');
        const formAlertBanner = document.getElementById('formAlertBanner');
        const toastContainer  = document.getElementById('contactToastContainer');

        function showToast(type, title, message) {
            if (!toastContainer) return;
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

            setTimeout(() => {
                if (toast.parentNode) {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(-15px)';
                    setTimeout(() => toast.remove(), 300);
                }
            }, 7000);
        }

        if (contactForm) {
            contactForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const fullName = document.getElementById('contactFullName').value.trim();
                const email    = document.getElementById('contactEmail').value.trim();
                const phone    = document.getElementById('contactPhone').value.trim();
                const message  = document.getElementById('contactMessage').value.trim();
                const service  = (document.getElementById('contactServiceType')?.value || 'Citizen Feedback').trim();

                if (!fullName || !email || !message) {
                    showToast('error', 'Incomplete Form', 'Please fill out your Name, Email address, and Message.');
                    return;
                }

                btnSubmit.disabled = true;
                btnSubmitIcon.className = 'fa-solid fa-spinner fa-spin';
                btnSubmitText.textContent = 'Sending Message...';

                const formData = new FormData();
                formData.append('send_contact_msg', '1');
                formData.append('fullName', fullName);
                formData.append('email', email);
                formData.append('phone', phone);
                formData.append('message', message);
                formData.append('service_type', service);

                fetch('contact.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    btnSubmit.disabled = false;
                    btnSubmitIcon.className = 'fa-regular fa-paper-plane';
                    btnSubmitText.textContent = 'Send Message →';

                    if (data.status === 'success') {
                        showToast('success', 'Message Sent', data.message);
                        contactForm.reset();
                        if (formAlertBanner) {
                            formAlertBanner.style.display = 'flex';
                            formAlertBanner.style.background = '#dcecf7';
                            formAlertBanner.style.color = '#1a4971';
                            formAlertBanner.style.border = '1px solid #bae6fd';
                            formAlertBanner.innerHTML = `
                                <i class="fa-solid fa-circle-check" style="font-size: 1.3rem; flex-shrink: 0; color: #0284c7;"></i>
                                <div>
                                    <strong>Message Delivered to Admin!</strong><br>
                                    ${data.message}
                                </div>
                            `;
                            formAlertBanner.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    } else {
                        showToast('error', 'Submission Failed', data.message || 'Could not send feedback. Please try again.');
                    }
                })
                .catch(err => {
                    console.error('Submission error:', err);
                    btnSubmit.disabled = false;
                    btnSubmitIcon.className = 'fa-regular fa-paper-plane';
                    btnSubmitText.textContent = 'Send Message →';
                    // Fallback to normal POST if error occurs
                    contactForm.submit();
                });
            });
        }
    });
    </script>
    <!-- Mobile Navigation Drawer Backdrop -->
    <div class="mobile-nav-backdrop"></div>
</body>
</html>
