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
    <title>Manage Purok 2 - Brgy Tabon</title>
    
    <!-- FontAwesome CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin Stylesheet -->
    <link rel="stylesheet" href="css/admin_common.css">
    <link rel="stylesheet" href="css/Purok 2.css">
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../Barangay Logo/Logo.png" alt="Barangay Logo" onerror="this.src='https://via.placeholder.com/70'">
            <h3>Brgy Tabon</h3>
            <p>Info SpotMap</p>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="controlpanel.php"><i class="fa-solid fa-gauge"></i> Control Panel</a></li>
                <li><a href="about.php"><i class="fa-solid fa-circle-info"></i> About</a></li>
                <li>
                    <a class="dropdown-btn active"><i class="fa-solid fa-map-location-dot"></i> SpotMap Manage <i class="fa-solid fa-caret-down" style="margin-left: auto;"></i></a>
                    <div class="dropdown-container" style="display: block;">
                        <a href="Legend.php">Legend</a>
                        <a href="Purok 1.php">Purok 1</a>
                        <a href="Purok 2.php" class="active" style="color: #ffffff; font-weight: 600;">Purok 2</a>
                        <a href="Purok 3.php">Purok 3</a>
                    </div>
                </li>
                <li>
                    <a class="dropdown-btn"><i class="fa-solid fa-users"></i> Household <i class="fa-solid fa-caret-down" style="margin-left: auto;"></i></a>
                    <div class="dropdown-container">
                        <a href="Legend Files.php">Legend Files</a>
                        <a href="Resident 1.php">Resident 1</a>
                        <a href="Resident 2.php">Resident 2</a>
                        <a href="Resident 3.php">Resident 3</a>
                    </div>
                </li>
                <li><a href="medialibrary.php"><i class="fa-regular fa-images"></i> Media Library</a></li>
                <li>
                    <a class="dropdown-btn"><i class="fa-solid fa-user-tie"></i> Barangay Board <i class="fa-solid fa-caret-down" style="margin-left: auto;"></i></a>
                    <div class="dropdown-container">
                        <a href="barangayofficial.php">Barangay Official</a>
                        <a href="skofficial.php">SK Official</a>
                    </div>
                </li>
                <li><a href="service.php"><i class="fa-solid fa-bell-concierge"></i> Service</a></li>
                <li><a href="announcement.php"><i class="fa-solid fa-bullhorn"></i> Announcement</a></li>
                <li><a href="adminsettings.php"><i class="fa-solid fa-gear"></i> Admin Settings</a></li>
                <br>
                <li><a href="../login.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
            </ul>
        </nav>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var dropdowns = document.getElementsByClassName("dropdown-btn");
                for (var i = 0; i < dropdowns.length; i++) {
                    dropdowns[i].addEventListener("click", function() {
                        this.classList.toggle("active");
                        var dropdownContent = this.nextElementSibling;
                        if (dropdownContent.style.display === "block" || dropdownContent.classList.contains("active")) {
                            dropdownContent.classList.remove("active");
                            dropdownContent.style.display = "none";
                        } else {
                            dropdownContent.classList.add("active");
                            dropdownContent.style.display = "block";
                        }
                    });
                }
            });
        </script>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="top-bar legend-top-bar">
            <h1 class="page-title" style="margin: 0; color: #1e293b; font-size: 24px; font-weight: 700;">Manage Purok 2</h1>
            <div class="admin-profile">
                <div class="admin-info">
                    <span class="admin-name">System Admin</span>
                    <span class="admin-role">Administrator</span>
                </div>
                <div class="admin-avatar">A</div>
            </div>
        </div>
        
        <div class="legend-content-wrapper">
            <div class="content-header">
                <h2>Add House to Map</h2>
                <p>Upload a marker, add members, and drag the icon to save to the map</p>
            </div>

            <div class="map-container">
                <img src="../Images/Purok/Purok 2.jpg" alt="Purok 2 Map" class="map-preview-image">
            </div>

            <div class="form-container">
                <?php if (isset($_GET['success'])): ?>
                    <div style="background-color: #dcfce3; color: #166534; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: 600;">
                        <i class="fa-solid fa-check-circle" style="margin-right: 8px;"></i> Household successfully saved to map!
                    </div>
                <?php endif; ?>
                <form action="process_household.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="purok_name" value="Purok 2">
                    <div class="form-group">
                        <label>House Number</label>
                        <input type="text" name="house_number" placeholder="e.g. 123" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Household Name</label>
                        <div class="form-row">
                            <div class="form-group half" style="margin-bottom: 0;">
                                <input type="text" name="husband_name" placeholder="Name of the Husband (e.g. Juan Dela Cruz)" required>
                            </div>
                            <div class="form-group half" style="margin-bottom: 0;">
                                <input type="text" name="spouse_name" placeholder="Name of the Spouse (Maiden) (e.g. Maria Santos)">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Marker Image (Icon shown on map)</label>
                        <input type="file" name="marker_image" id="marker_image" class="file-input" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label>Marker Width (px)</label>
                            <input type="number" name="marker_width" value="40">
                        </div>
                        <div class="form-group half">
                            <label>Marker Height (px)</label>
                            <input type="number" name="marker_height" value="40">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label>Top position (%)</label>
                            <input type="number" name="top_pos" step="0.01" value="50.00">
                        </div>
                        <div class="form-group half">
                            <label>Left position (%)</label>
                            <input type="number" name="left_pos" step="0.01" value="50.00">
                        </div>
                    </div>
                    
                    <button type="submit" class="save-btn"><i class="fa-solid fa-download"></i> Save to Map</button>
                </form>
            </div>
        </div>
    </main>
    <script src="js/map_interactive.js?v=<?= time() ?>"></script>
</body>
</html>
