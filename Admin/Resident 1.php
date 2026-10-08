<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

$jsonFile = 'data/purok1.json';
$households = [];
if (file_exists($jsonFile)) {
    $households = json_decode(file_get_contents($jsonFile), true) ?? [];
}

$records = array_filter($households, function($hh) {
    return $hh['purok'] === 'Purok 1';
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident 1 Records - Brgy Tabon</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_common.css">
    <link rel="stylesheet" href="css/Resident 1.css">
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../Barangay Logo/Logo.png" alt="Barangay Logo" onerror="this.src='https://via.placeholder.com/70'">
            <h3>Brgy Tabon</h3>
            <p>Info SpotMap</p>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="controlpanel.php" class="active"><i class="fa-solid fa-gauge"></i> Control Panel</a></li>
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

    <main class="main-content">
        <div class="top-bar">
            <div class="top-bar-left">
                <h1>Resident 1 Records</h1>
            </div>
            <div class="top-bar-right">
                <div class="admin-profile">
                    <div class="admin-info">
                        <span class="admin-name">System Admin</span>
                        <span class="admin-role">Administrator</span>
                    </div>
                    <div class="admin-avatar">A</div>
                </div>
            </div>
        </div>
        
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>House Number</th>
                        <th>Husband Name</th>
                        <th>Spouse Name</th>
                        <th>Date Added</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No records found.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($records as $record): ?>
                        <tr>
                            <td><img src="<?= htmlspecialchars($record['markerImage']) ?>" alt="Marker" style="width: 40px; height: 40px; object-fit: contain;"></td>
                            <td><?= htmlspecialchars($record['houseNumber']) ?></td>
                            <td><?= htmlspecialchars($record['husbandName']) ?></td>
                            <td><?= htmlspecialchars($record['spouseName']) ?></td>
                            <td><?= htmlspecialchars($record['dateAdded']) ?></td>
                            <td>
                                <a href="Purok 1.php" class="action-btn edit-btn"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                                <a href="process_household.php?delete_id=<?= $record['id'] ?>&return=Resident 1.php" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to remove this record?');"><i class="fa-solid fa-trash"></i> Remove</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
