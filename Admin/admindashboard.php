<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Houses - Brgy Tabon</title>
    
    <!-- FontAwesome CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin Stylesheet -->
    <link rel="stylesheet" href="css/admin_common.css">
    <link rel="stylesheet" href="css/admin_table.css">
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../Barangay Logo/Logo.png" alt="Barangay Logo">
            <h3>Brgy Tabon</h3>
            <p>Info SpotMap</p>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="controlpanel.php"><i class="fa-solid fa-gauge"></i> Control Panel</a></li>
                <li><a href="spotmapmanage.php"><i class="fa-solid fa-map-location-dot"></i> SpotMap Manage</a></li>
                <li><a href="communitymembers.php"><i class="fa-solid fa-users"></i> Community Members</a></li>
                <li><a href="#" class="active"><i class="fa-solid fa-house-chimney"></i> Households</a></li>
                <li><a href="#"><i class="fa-solid fa-location-dot"></i> Puroks</a></li>
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
            <h1 class="page-title">Manage Houses</h1>
            <button class="btn-add"><i class="fa-solid fa-plus"></i> Add House</button>
        </div>

        <!-- Data Container -->
        <div class="controls-container">
            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Search house #, address...">
            </div>
            <div class="filter-group">
                <select class="filter-select">
                    <option value="all">All Puroks</option>
                    <option value="1">Purok 1</option>
                    <option value="2">Purok 2</option>
                </select>
                <div class="count-badge">
                    <i class="fa-solid fa-circle" style="font-size: 8px; color: #4cd137; margin-right: 5px;"></i> 3 of 3 houses
                </div>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Marker</th>
                        <th>House #</th>
                        <th>Address</th>
                        <th>Purok</th>
                        <th>Members</th>
                        <th>Position (T/L)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Row 1 -->
                    <tr>
                        <td><img src="../Officials/Captain.jpg" alt="Marker" class="marker-img" onerror="this.src='https://via.placeholder.com/40'"></td>
                        <td>001</td>
                        <td>Street 1, Brgy Tabon</td>
                        <td>
                            <div class="purok-badge"><i class="fa-solid fa-location-dot"></i> Purok 1</div>
                        </td>
                        <td>
                            <div class="members-count"><i class="fa-solid fa-users"></i> 4 members</div>
                        </td>
                        <td>
                            <div class="position-text">14.1234, 121.5678</div>
                        </td>
                        <td>
                            <div class="actions">
                                <button class="btn-action">Edit</button>
                                <button class="btn-action">Household</button>
                                <button class="btn-action">Delete</button>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Row 2 -->
                    <tr>
                        <td><img src="../Officials/Captain.jpg" alt="Marker" class="marker-img" onerror="this.src='https://via.placeholder.com/40'"></td>
                        <td>002</td>
                        <td>Street 2, Brgy Tabon</td>
                        <td>
                            <div class="purok-badge"><i class="fa-solid fa-location-dot"></i> Purok 2</div>
                        </td>
                        <td>
                            <div class="members-count"><i class="fa-solid fa-users"></i> 2 members</div>
                        </td>
                        <td>
                            <div class="position-text">14.1235, 121.5679</div>
                        </td>
                        <td>
                            <div class="actions">
                                <button class="btn-action">Edit</button>
                                <button class="btn-action">Household</button>
                                <button class="btn-action">Delete</button>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Row 3 -->
                    <tr>
                        <td><img src="../Officials/Captain.jpg" alt="Marker" class="marker-img" onerror="this.src='https://via.placeholder.com/40'"></td>
                        <td>003</td>
                        <td>Street 3, Brgy Tabon</td>
                        <td>
                            <div class="purok-badge"><i class="fa-solid fa-location-dot"></i> Purok 3</div>
                        </td>
                        <td>
                            <div class="members-count"><i class="fa-solid fa-users"></i> 0 members</div>
                        </td>
                        <td>
                            <div class="position-text">14.1236, 121.5680</div>
                        </td>
                        <td>
                            <div class="actions">
                                <button class="btn-action">Edit</button>
                                <button class="btn-action">Household</button>
                                <button class="btn-action">Delete</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
    </main>

</body>
</html>
