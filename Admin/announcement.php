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
    <title>Manage Announcements - Brgy Tabon</title>
    
    <!-- FontAwesome CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin Stylesheet -->
    <link rel="stylesheet" href="css/admin_common.css">
    <link rel="stylesheet" href="css/announcement.css">
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
                <li><a href="spotmapmanage.php"><i class="fa-solid fa-map-location-dot"></i> SpotMap Manage</a></li>
                <li><a href="medialibrary.php"><i class="fa-regular fa-images"></i> Media Library</a></li>
                <li>
                    <a class="dropdown-btn"><i class="fa-solid fa-user-tie"></i> Barangay Board <i class="fa-solid fa-caret-down" style="margin-left: auto;"></i></a>
                    <div class="dropdown-container">
                        <a href="barangayofficial.php">Barangay Official</a>
                        <a href="skofficial.php">SK Official</a>
                    </div>
                </li>
                <li><a href="service.php"><i class="fa-solid fa-bell-concierge"></i> Service</a></li>
                <li><a href="announcement.php" class="active"><i class="fa-solid fa-bullhorn"></i> Announcement</a></li>
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
            <h1 class="page-title" style="font-size: 20px;">Manage Announcements</h1>
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

        <div class="announcement-header-container">
            <h2>Announcements & Updates</h2>
            <button class="btn-add-announcement" id="addAnnouncementBtn"><i class="fa-solid fa-plus"></i> Add New Announcement</button>
        </div>

        <div class="announcement-table-container">
            <table class="announcement-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Sep 20, 2026</td>
                        <td>Upcoming Medical Mission</td>
                        <td>Free medical check-ups and distribution...</td>
                        <td><span class="status-pill published">Published</span></td>
                        <td>
                            <button class="btn-action edit">Edit</button>
                            <button class="btn-action delete">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Add New Announcement Modal -->
        <div id="announcementModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Add New Announcement</h2>
                    <span class="close-modal">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Announcement Title</label>
                        <input type="text" placeholder="e.g., Upcoming Medical Mission">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Who</label>
                            <input type="text" placeholder="e.g., All residents">
                        </div>
                        <div class="form-group">
                            <label>When</label>
                            <input type="text" placeholder="e.g., Sept 20, 2026, 8:00 AM">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>What</label>
                        <input type="text" placeholder="e.g., Free Medical Check-ups">
                    </div>
                    
                    <div class="form-group">
                        <label>Why</label>
                        <textarea placeholder="e.g., To ensure the health of our community."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn-cancel" id="cancelModalBtn">Cancel</button>
                    <button class="btn-publish">Publish Announcement</button>
                </div>
            </div>
        </div>
        
        <script>
            // Modal Logic
            var modal = document.getElementById("announcementModal");
            var btn = document.getElementById("addAnnouncementBtn");
            var span = document.getElementsByClassName("close-modal")[0];
            var cancelBtn = document.getElementById("cancelModalBtn");

            btn.onclick = function() {
                modal.style.display = "block";
            }
            span.onclick = function() {
                modal.style.display = "none";
            }
            cancelBtn.onclick = function() {
                modal.style.display = "none";
            }
            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }
        </script>

    </main>

</body>
</html>
