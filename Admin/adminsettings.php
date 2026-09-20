<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - Brgy Tabon</title>
    
    <!-- FontAwesome CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin Stylesheet -->
    <link rel="stylesheet" href="css/admin_common.css">
    <link rel="stylesheet" href="css/adminsettings.css">
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../Barangay Logo/Logo.png" alt="Barangay Logo" onerror="this.src='https://via.placeholder.com/70'" id="sidebar-logo">
            <h3 id="sidebar-brgy-name">Brgy Tabon</h3>
            <p>Info SpotMap</p>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="controlpanel.php"><i class="fa-solid fa-gauge"></i> Control Panel</a></li>
                <li><a href="spotmapmanage.php"><i class="fa-solid fa-map-location-dot"></i> SpotMap Manage</a></li>


                <li><a href="medialibrary.php"><i class="fa-regular fa-images"></i> Media Library</a></li>
                <li><a href="barangayboard.php"><i class="fa-solid fa-user-tie"></i>Barangay Board</a></li>
                <li><a href="adminsettings.php" class="active"><i class="fa-solid fa-gear"></i> Admin Settings</a></li>
                
                <br>
                <li><a href="../login.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        
        <div class="top-bar">
            <h1 class="page-title">Admin Settings</h1>
        </div>

        <div class="settings-layout">
            
            <!-- System Configuration -->
            <div class="settings-card">
                <h2><i class="fa-solid fa-sliders"></i> System Configuration</h2>
                <form class="settings-form" id="config-form" onsubmit="saveConfig(event)">
                    <div class="form-group">
                        <label>Barangay Name</label>
                        <input type="text" id="brgy-name" value="Brgy Tabon" required>
                    </div>
                    <div class="form-group">
                        <label>Official Logo</label>
                        <input type="file" id="brgy-logo-upload" accept="image/*">
                        <div class="logo-preview-container">
                            <img src="../Barangay Logo/Logo.png" id="logo-preview" alt="Logo Preview" onerror="this.src='https://via.placeholder.com/100?text=No+Logo'">
                        </div>
                    </div>
                    
                    <div class="contact-row">
                        <div class="form-group">
                            <label>Contact Email</label>
                            <input type="email" id="contact-email" value="contact@brgytabon.gov.ph">
                        </div>
                        <div class="form-group">
                            <label>Contact Phone</label>
                            <input type="text" id="contact-phone" value="0912-345-6789">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Time on Duty</label>
                        <input type="text" id="duty-time" value="Mon - Fri, 8:00 AM to 5:00 PM">
                    </div>
                    <button type="submit" class="btn-primary">Save Configuration</button>
                </form>
            </div>

            <!-- Security & Backups -->
            <div class="settings-card">
                <h2><i class="fa-solid fa-shield-halved"></i> Security & Backups</h2>
                
                <form class="settings-form" id="security-form" onsubmit="changePassword(event)" style="margin-bottom: 30px;">
                    <h3 style="font-size: 14px; margin-bottom: 15px; color: #4b5563;">Change Password</h3>
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" id="current-pass" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" id="new-pass" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" id="confirm-pass" required>
                    </div>
                    <button type="submit" class="btn-primary">Update Password</button>
                </form>

                <div style="border-top: 1px solid #f0f2f5; padding-top: 20px;">
                    <h3 style="font-size: 14px; margin-bottom: 15px; color: #4b5563;">Data Management</h3>
                    <button class="btn-secondary" onclick="downloadBackup()"><i class="fa-solid fa-download"></i> Download Database Backup</button>
                    
                    <h3 style="font-size: 14px; margin-top: 25px; margin-bottom: 10px; color: #4b5563;">Recent Security Logs</h3>
                    <div class="security-logs">
                        <div class="log-item">[2026-09-19 19:30:12] Successful login from 192.168.1.5</div>
                        <div class="log-item">[2026-09-19 14:15:00] Admin updated Community Members</div>
                        <div class="log-item" style="color: #dc2626;">[2026-09-18 22:45:11] Failed login attempt from 10.0.0.44</div>
                        <div class="log-item">[2026-09-18 10:05:00] Database backup generated</div>
                        <div class="log-item">[2026-09-17 09:00:15] Successful login from 192.168.1.5</div>
                    </div>
                </div>
            </div>

        </div>
        
    </main>

    <script>
        // Load stored settings on page load
        window.addEventListener('DOMContentLoaded', () => {
            if(localStorage.getItem('brgy_name')) {
                document.getElementById('brgy-name').value = localStorage.getItem('brgy_name');
                document.getElementById('sidebar-brgy-name').innerText = localStorage.getItem('brgy_name');
            }
            if(localStorage.getItem('brgy_logo_src')) {
                document.getElementById('logo-preview').src = localStorage.getItem('brgy_logo_src');
                document.getElementById('sidebar-logo').src = localStorage.getItem('brgy_logo_src');
            }
            if(localStorage.getItem('brgy_email')) {
                document.getElementById('contact-email').value = localStorage.getItem('brgy_email');
            }
            if(localStorage.getItem('brgy_phone')) {
                document.getElementById('contact-phone').value = localStorage.getItem('brgy_phone');
            }
            if(localStorage.getItem('brgy_duty')) {
                document.getElementById('duty-time').value = localStorage.getItem('brgy_duty');
            }
        });

        // Handle Image Upload Preview
        const logoUpload = document.getElementById('brgy-logo-upload');
        const logoPreview = document.getElementById('logo-preview');

        logoUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    logoPreview.src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        function saveConfig(e) {
            e.preventDefault();
            const brgyName = document.getElementById('brgy-name').value;
            const brgyEmail = document.getElementById('contact-email').value;
            const brgyPhone = document.getElementById('contact-phone').value;
            const dutyTime = document.getElementById('duty-time').value;
            const currentLogoSrc = logoPreview.src;

            // Save to localStorage
            localStorage.setItem('brgy_name', brgyName);
            localStorage.setItem('brgy_email', brgyEmail);
            localStorage.setItem('brgy_phone', brgyPhone);
            localStorage.setItem('brgy_duty', dutyTime);
            localStorage.setItem('brgy_logo_src', currentLogoSrc);

            // Update UI dynamically
            document.getElementById('sidebar-brgy-name').innerText = brgyName;
            document.getElementById('sidebar-logo').src = currentLogoSrc;

            alert("System configuration updated successfully!");
        }

        function changePassword(e) {
            e.preventDefault();
            const newPass = document.getElementById('new-pass').value;
            const confirmPass = document.getElementById('confirm-pass').value;

            if (newPass !== confirmPass) {
                alert("New passwords do not match!");
                return;
            }

            alert("Password successfully updated! (Mock action)");
            document.getElementById('security-form').reset();
        }

        function downloadBackup() {
            // Mock download
            alert("Generating backup... Backup downloading as 'brgytabon_backup_20260919.sql'");
        }
    </script>
</body>
</html>
