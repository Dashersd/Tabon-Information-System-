<?php
require_once 'db_connect.php';
$msg_sent = false;
$msg_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_contact_msg'])) {
    $name = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!empty($name) && !empty($message)) {
        // Generate a unique thread ID for the new conversation
        $thread_id = 'thread_' . bin2hex(random_bytes(16));
        $full_message = "[Service: Contact Inquiry]\n\n" . $message . (!empty($phone) ? "\n\nContact Phone: " . $phone : "");
        
        // Insert into feedback_chats table
        $stmt = $conn->prepare("INSERT INTO feedback_chats (thread_id, sender_name, email, sender_type, message, is_read) VALUES (?, ?, ?, 'user', ?, 0)");
        $stmt->bind_param("ssss", $thread_id, $name, $email, $full_message);
        
        if ($stmt->execute()) {
            $stmt->close();
            $msg_sent = true;
        } else {
            $msg_error = "Could not send message. Please try again later.";
        }
    } else {
        $msg_error = "Please provide your name and message.";
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
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600;1,700&display=swap" rel="stylesheet">
    
    <!-- External Stylesheets -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/contact.css?v=<?php echo time(); ?>">
</head>
<body class="contact-page">

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
                <p class="form-desc">Fill out the form below and we'll get back to you shortly.</p>
                
                <?php if ($msg_sent): ?>
                    <div style="background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.92rem; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Thank you! Your message has been sent to Barangay Tabon.</span>
                    </div>
                <?php elseif (!empty($msg_error)): ?>
                    <div style="background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.92rem; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span><?= htmlspecialchars($msg_error) ?></span>
                    </div>
                <?php endif; ?>

                <form action="contact.php" method="POST">
                    <input type="hidden" name="send_contact_msg" value="1">

                    <div class="input-group">
                        <i class="fa-regular fa-user"></i>
                        <input type="text" name="fullName" class="form-input" placeholder="Full Name *" required>
                    </div>
                    
                    <div class="input-group">
                        <i class="fa-regular fa-envelope"></i>
                        <input type="email" name="email" class="form-input" placeholder="Email Address">
                    </div>
                    
                    <div class="input-group">
                        <i class="fa-solid fa-phone"></i>
                        <input type="tel" name="phone" class="form-input" placeholder="Phone Number">
                    </div>
                    
                    <div class="input-group textarea-group">
                        <i class="fa-regular fa-message"></i>
                        <textarea name="message" class="form-input" placeholder="Message *" required></textarea>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fa-regular fa-paper-plane"></i> Send Message &rarr;
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

    <script src="js/main.js?v=<?= time() ?>"></script>
</body>
</html>


