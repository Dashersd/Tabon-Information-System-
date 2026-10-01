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
    <title>Manage Barangay Officials - Brgy Tabon</title>
    
    <!-- FontAwesome CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin Stylesheet -->
    <link rel="stylesheet" href="css/admin_common.css">
    <link rel="stylesheet" href="css/officials.css">
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
                <li><a href="controlpanel.php"><i class="fa-solid fa-desktop"></i> Control Panel</a></li>
                <li><a href="about.php"><i class="fa-solid fa-circle-info"></i> About</a></li>
                <li>
                    <a class="dropdown-btn"><i class="fa-solid fa-map-location-dot"></i> SpotMap Manage <i class="fa-solid fa-caret-down" style="margin-left: auto;"></i></a>
                    <div class="dropdown-container">
                        <a href="Legend.php">Legend</a>
                        <a href="Purok 1.php">Purok 1</a>
                        <a href="Purok 2.php">Purok 2</a>
                        <a href="Purok 3.php">Purok 3</a>
                    </div>
                </li>
                <li><a href="medialibrary.php"><i class="fa-regular fa-images"></i> Media Library</a></li>
                <li>
                    <a class="dropdown-btn active"><i class="fa-solid fa-user-tie"></i> Barangay Board <i class="fa-solid fa-caret-down" style="margin-left: auto;"></i></a>
                    <div class="dropdown-container" style="display: block;">
                        <a href="barangayofficial.php" class="active">Barangay Official</a>
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
        
        <div class="top-bar">
            <h1 class="page-title" style="font-size: 20px;">Manage Barangay Officials</h1>
            <div class="user-profile" style="display: flex; align-items: center; gap: 10px; text-align: right;">
                <div class="user-info">
                    <h4 style="font-size: 14px; font-weight: 700; color: #2b323c; margin: 0;">System Admin</h4>
                    <p style="font-size: 12px; color: #6b7280; margin: 0;">Administrator</p>
                </div>
                <div class="user-avatar" style="width: 40px; height: 40px; background-color: #f1c40f; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #000;">
                    A
                </div>
            </div>
        </div>

        <div class="officials-header-container">
            <h2>Barangay Officials Content</h2>
            <p>Update the information displayed on the public Barangay Officials page.</p>
        </div>

        <div class="org-chart-wrapper">
            <!-- Row 1 -->
            <div class="org-row">
                <div class="org-card">
                    <div class="org-image-placeholder">
                        <span class="org-alt-text">Barangay Captain</span>
                        <div class="org-name-pill">Name</div>
                    </div>
                    <div class="org-title">BARANGAY CAPTAIN</div>
                    <button class="org-edit-btn"><i class="fa-solid fa-pen"></i> Edit</button>
                </div>
            </div>

            <!-- Row 2 -->
            <div class="org-row">
                <div class="org-card">
                    <div class="org-image-placeholder">
                        <span class="org-alt-text">Barangay Secretary</span>
                        <div class="org-name-pill">Name</div>
                    </div>
                    <div class="org-title">BARANGAY SECRETARY</div>
                    <button class="org-edit-btn"><i class="fa-solid fa-pen"></i> Edit</button>
                </div>
                <div class="org-card">
                    <div class="org-image-placeholder">
                        <span class="org-alt-text">Barangay Treasurer</span>
                        <div class="org-name-pill">Name</div>
                    </div>
                    <div class="org-title">BARANGAY TREASURER</div>
                    <button class="org-edit-btn"><i class="fa-solid fa-pen"></i> Edit</button>
                </div>
            </div>

            <!-- Row 3 -->
            <div class="org-row">
                <div class="org-card">
                    <div class="org-image-placeholder">
                        <span class="org-alt-text">Barangay Kagawad</span>
                        <div class="org-name-pill">Name</div>
                    </div>
                    <div class="org-title">BARANGAY KAGAWAD</div>
                    <button class="org-edit-btn"><i class="fa-solid fa-pen"></i> Edit</button>
                </div>
                <div class="org-card">
                    <div class="org-image-placeholder">
                        <span class="org-alt-text">Barangay Kagawad</span>
                        <div class="org-name-pill">Name</div>
                    </div>
                    <div class="org-title">BARANGAY KAGAWAD</div>
                    <button class="org-edit-btn"><i class="fa-solid fa-pen"></i> Edit</button>
                </div>
                <div class="org-card">
                    <div class="org-image-placeholder">
                        <span class="org-alt-text">Barangay Kagawad</span>
                        <div class="org-name-pill">Name</div>
                    </div>
                    <div class="org-title">BARANGAY KAGAWAD</div>
                    <button class="org-edit-btn"><i class="fa-solid fa-pen"></i> Edit</button>
                </div>
                <div class="org-card">
                    <div class="org-image-placeholder">
                        <span class="org-alt-text">Barangay Kagawad</span>
                        <div class="org-name-pill">Name</div>
                    </div>
                    <div class="org-title">BARANGAY KAGAWAD</div>
                    <button class="org-edit-btn"><i class="fa-solid fa-pen"></i> Edit</button>
                </div>
                <div class="org-card">
                    <div class="org-image-placeholder">
                        <span class="org-alt-text">Barangay Kagawad</span>
                        <div class="org-name-pill">Name</div>
                    </div>
                    <div class="org-title">BARANGAY KAGAWAD</div>
                    <button class="org-edit-btn"><i class="fa-solid fa-pen"></i> Edit</button>
                </div>
                <div class="org-card">
                    <div class="org-image-placeholder">
                        <span class="org-alt-text">Barangay Kagawad</span>
                        <div class="org-name-pill">Name</div>
                    </div>
                    <div class="org-title">BARANGAY KAGAWAD</div>
                    <button class="org-edit-btn"><i class="fa-solid fa-pen"></i> Edit</button>
                </div>
                <div class="org-card">
                    <div class="org-image-placeholder">
                        <span class="org-alt-text">Barangay Kagawad</span>
                        <div class="org-name-pill">Name</div>
                    </div>
                    <div class="org-title">BARANGAY KAGAWAD</div>
                    <button class="org-edit-btn"><i class="fa-solid fa-pen"></i> Edit</button>
                </div>
            </div>
        </div>

    </main>
</body>
</html>
