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
    <title>Control Panel - Brgy Tabon</title>
    
    <!-- FontAwesome CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin Stylesheet -->
    <link rel="stylesheet" href="css/admin_common.css">
    <link rel="stylesheet" href="css/controlpanel.css">
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
                <li><a href="controlpanel.php" class="active"><i class="fa-solid fa-gauge"></i> Control Panel</a></li>
                <li><a href="spotmapmanage.php"><i class="fa-solid fa-map-location-dot"></i> SpotMap Manage</a></li>


                <li><a href="medialibrary.php"><i class="fa-regular fa-images"></i> Media Library</a></li>
                <li><a href="barangayboard.php"><i class="fa-solid fa-user-tie"></i>Barangay Board</a></li>
                <li><a href="adminsettings.php"><i class="fa-solid fa-gear"></i> Admin Settings</a></li>
                
                <br>
                <li><a href="../login.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Control Panel Overview</h1>
        </div>

        <div class="dashboard-grid">
            <!-- KPIs -->
            <div class="kpi-cards">
                <div class="kpi-card">
                    <div class="kpi-icon"><i class="fa-solid fa-users"></i></div>
                    <div class="kpi-info">
                        <h3>Total Population</h3>
                        <p>1,250</p>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon"><i class="fa-solid fa-house-chimney"></i></div>
                    <div class="kpi-info">
                        <h3>Total Households</h3>
                        <p>320</p>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon"><i class="fa-solid fa-location-dot"></i></div>
                    <div class="kpi-info">
                        <h3>Total Puroks</h3>
                        <p>7</p>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon"><i class="fa-solid fa-user-shield"></i></div>
                    <div class="kpi-info">
                        <h3>Barangay Officials</h3>
                        <p>12</p>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon" style="background-color: #e0e7ff; color: #4338ca;"><i class="fa-solid fa-user-check"></i></div>
                    <div class="kpi-info">
                        <h3>Total Active Users</h3>
                        <p>156</p>
                    </div>
                </div>
            </div>

            <!-- Activity & Notifications -->
            <div class="dashboard-widgets">
                <!-- User Concerns -->
                <div class="widget-panel">
                    <div class="widget-header">
                        <h2>Recent Concerns & Feedback</h2>
                    </div>
                    <div class="widget-body">
                        <ul class="activity-list">
                            <li>
                                <span class="time">10:00 AM</span>
                                <span class="desc">Juan Dela Cruz reported an issue: Broken streetlight at Purok 1.</span>
                            </li>
                            <li>
                                <span class="time">09:30 AM</span>
                                <span class="desc">Maria Santos inquired about the upcoming medical mission.</span>
                            </li>
                            <li>
                                <span class="time">Yesterday</span>
                                <span class="desc">Pedro Penduko sent feedback: Garbage collection delay in Purok 3.</span>
                            </li>
                            <li>
                                <span class="time">Yesterday</span>
                                <span class="desc">Anonymous submitted a noise complaint at Purok 2.</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Notifications & Alerts -->
                <div class="widget-panel">
                    <div class="widget-header">
                        <h2>Notifications & Alerts</h2>
                    </div>
                    <div class="widget-body">
                        <ul class="notification-list">
                            <li class="alert">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <span>Low storage space warning in Media Library (85% full).</span>
                            </li>
                            <li class="info">
                                <i class="fa-solid fa-circle-info"></i>
                                <span>System maintenance scheduled for tomorrow at 2:00 AM.</span>
                            </li>
                            <li class="success">
                                <i class="fa-solid fa-circle-check"></i>
                                <span>All SpotMap markers are currently active and functioning.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Management Widgets -->
            <div class="dashboard-widgets management-widgets">
                <!-- User Accounts -->
                <div class="widget-panel">
                    <div class="widget-header">
                        <h2>User Accounts</h2>
                    </div>
                    <div class="widget-body table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>#001</td>
                                    <td>Juan Dela Cruz</td>
                                    <td>juan@example.com</td>
                                    <td><span class="badge active">Active</span></td>
                                    <td><button class="btn-edit-sm"><i class="fa-solid fa-pen"></i></button></td>
                                </tr>
                                <tr>
                                    <td>#002</td>
                                    <td>Maria Santos</td>
                                    <td>maria@example.com</td>
                                    <td><span class="badge inactive">Inactive</span></td>
                                    <td><button class="btn-edit-sm"><i class="fa-solid fa-pen"></i></button></td>
                                </tr>
                                <tr>
                                    <td>#003</td>
                                    <td>Pedro Penduko</td>
                                    <td>pedro@example.com</td>
                                    <td><span class="badge active">Active</span></td>
                                    <td><button class="btn-edit-sm"><i class="fa-solid fa-pen"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Posts & Pages -->
                <div class="widget-panel">
                    <div class="widget-header">
                        <h2>Posts & Pages Management</h2>
                    </div>
                    <div class="widget-body">
                        <div class="pages-list">
                            <div class="page-item">
                                <div class="page-info">
                                    <div class="page-icon"><i class="fa-solid fa-house"></i></div>
                                    <div>
                                        <h4>Home Page</h4>
                                        <p>Hero section, announcements</p>
                                    </div>
                                </div>
                                <a href="../index.php" class="btn-edit-page">View / Edit</a>
                            </div>
                            <div class="page-item">
                                <div class="page-info">
                                    <div class="page-icon"><i class="fa-solid fa-users"></i></div>
                                    <div>
                                        <h4>About Us</h4>
                                        <p>Barangay history, contact info</p>
                                    </div>
                                </div>
                                <a href="../about.php" class="btn-edit-page">View / Edit</a>
                            </div>
                            <div class="page-item">
                                <div class="page-info">
                                    <div class="page-icon"><i class="fa-solid fa-bullseye"></i></div>
                                    <div>
                                        <h4>Mission & Vision</h4>
                                        <p>Core values, objectives</p>
                                    </div>
                                </div>
                                <a href="../about.php" class="btn-edit-page">View / Edit</a>
                            </div>
                            <div class="page-item">
                                <div class="page-info">
                                    <div class="page-icon"><i class="fa-solid fa-newspaper"></i></div>
                                    <div>
                                        <h4>Updates & News</h4>
                                        <p>Latest articles, announcements</p>
                                    </div>
                                </div>
                                <a href="../index.php" class="btn-edit-page">View / Edit</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

</body>
</html>
