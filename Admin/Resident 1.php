<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once '../db_connect.php';

$module_name = "Purok Mesaya";
$target_file = "purok1";
$table_name = "purok1_locations";
$spotmap_page = "Purok 1.php";
$resident_page = "Resident 1.php";

$records = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM `$table_name` ORDER BY id DESC");
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_msg = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Records - <?= $module_name ?> | Brgy Tabon</title>
    
    <!-- Google Fonts & FontAwesome -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin Stylesheet -->
    <link rel="stylesheet" href="Admin CSS/admin_common.css?v=<?= time() ?>">
    <style>
        .table-card {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 25px 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            margin-top: 20px;
            border-top: 4px solid #223c3b;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        .data-table th, .data-table td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .data-table th {
            background-color: #f8fafc;
            color: #334155;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .data-table tr:hover {
            background-color: #f8fafc;
        }
        .action-buttons-wrapper {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            white-space: nowrap;
        }
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 600;
            line-height: 1;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            white-space: nowrap;
        }
        .edit-btn {
            background-color: #2c6e49;
            color: #ffffff;
            border: 1px solid #23583a;
        }
        .edit-btn:hover {
            background-color: #1e4f34;
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(44, 110, 73, 0.25);
        }
        .delete-btn {
            background-color: #ef4444;
            color: #ffffff;
            border: 1px solid #dc2626;
            margin: 0;
        }
        .delete-btn:hover {
            background-color: #dc2626;
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(239, 68, 68, 0.25);
        }
        .child-pill {
            display: inline-block;
            background: #e2f1e8;
            color: #1d5b37;
            border: 1px solid #b7dfca;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.78rem;
            font-weight: 600;
            margin: 2px 2px 2px 0;
        }
        .children-count-badge {
            display: inline-block;
            background: #223c3b;
            color: #ffffff;
            font-size: 0.7rem;
            font-weight: 700;
            border-radius: 10px;
            padding: 1px 6px;
            margin-right: 4px;
        }
        .alert-banner {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 500;
            font-size: 0.95rem;
        }
        .alert-danger { background: #fee2e2; border-left: 5px solid #ef4444; color: #991b1b; }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../Barangay Logo/Logo.png" alt="Barangay Logo" width="70" height="70" style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover; display: inline-block;" onerror="this.src='https://via.placeholder.com/70'">
            <h3>Brgy Tabon</h3>
            <p>Info SpotMap</p>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="controlpanel.php"><i class="fa-solid fa-gauge"></i> Control Panel</a></li>
                <li><a href="about.php"><i class="fa-solid fa-circle-info"></i> About</a></li>
                <li>
                    <a class="dropdown-btn"><i class="fa-solid fa-map-location-dot"></i> SpotMap Manage <i class="fa-solid fa-caret-down" style="margin-left: auto;"></i></a>
                    <div class="dropdown-container">
                        <a href="Legend.php">Legend</a>
                        <a href="Purok 1.php">Purok Mesaya</a>
                        <a href="Purok 2.php">Purok Metamed</a>
                        <a href="Purok 3.php">Purok Melitebangay</a>
                    </div>
                </li>
                <li>
                    <a class="dropdown-btn active"><i class="fa-solid fa-users"></i> Household <i class="fa-solid fa-caret-down" style="margin-left: auto;"></i></a>
                    <div class="dropdown-container" style="display: block;">
                        <a href="Legend Files.php">Legend Files</a>
                        <a href="Resident 1.php" class="active" style="color: #ffffff; font-weight: 600;">Resident 1</a>
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
                <li><a href="services.php"><i class="fa-solid fa-bell-concierge"></i> Services</a></li>
                <li><a href="announcements.php"><i class="fa-solid fa-bullhorn"></i> Announcement</a></li>
                <li><a href="feedbackchat.php"><i class="fa-solid fa-comments"></i> Feedback Chat</a></li>
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

    <!-- Main Content Area -->
    <main class="main-content">
        <div class="top-bar">
            <div>
                <h1 class="page-title" style="margin: 0; color: #1e293b; font-size: 24px; font-weight: 700;">Resident Records - <?= $module_name ?></h1>
                <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.9rem;">View, edit pin coordinates, and manage registered households and dependents.</p>
            </div>
            <div style="display: flex; gap: 12px; align-items: center;">
                <a href="<?= $spotmap_page ?>" class="action-btn edit-btn" style="padding: 10px 18px; font-size: 0.9rem;">
                    <i class="fa-solid fa-map-location-dot"></i> View on Spot Map
                </a>
            </div>
        </div>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert-banner alert-danger">
                <div><i class="fa-solid fa-circle-check"></i> Household record successfully deleted.</div>
                <button onclick="this.parentElement.style.display='none'" style="border:none;background:none;font-size:1.2rem;cursor:pointer;color:#991b1b;">&times;</button>
            </div>
        <?php endif; ?>

        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 70px;">Photo</th>
                        <th style="width: 110px;">House #</th>
                        <th>Household Head</th>
                        <th>Spouse</th>
                        <th>Children / Dependents</th>
                        <th style="width: 140px;">Date Plotted</th>
                        <th style="width: 190px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #64748b;">
                            <i class="fa-solid fa-folder-open" style="font-size: 2rem; margin-bottom: 10px; display: block; color: #94a3b8;"></i>
                            No household records plotted in <?= $module_name ?> yet. 
                            <a href="<?= $spotmap_page ?>" style="color: #2c6e49; font-weight: 600; text-decoration: underline;">Plot a house now</a>.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($records as $record): ?>
                            <?php 
                                $children = [];
                                if (!empty($record['children'])) {
                                    $decoded = json_decode($record['children'], true);
                                    if (is_array($decoded)) $children = $decoded;
                                }
                            ?>
                        <tr>
                            <td>
                                <img src="<?= htmlspecialchars($record['house_image']) ?>" 
                                     alt="House Photo" 
                                     onerror="this.src='../Images/Icons/house-placeholder.png'"
                                     style="width: 52px; height: 52px; object-fit: cover; border-radius: 8px; border: 1px solid #cbd5e1;">
                            </td>
                            <td><strong style="color: #223c3b;"><?= htmlspecialchars($record['house_number']) ?></strong></td>
                            <td><strong><?= htmlspecialchars($record['husband_name']) ?></strong></td>
                            <td><?= !empty($record['spouse_name']) ? htmlspecialchars($record['spouse_name']) : '<span style="color:#94a3b8; font-style:italic;">N/A</span>' ?></td>
                            <td>
                                <?php if (!empty($children)): ?>
                                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 4px;">
                                        <span class="children-count-badge"><?= count($children) ?></span>
                                        <?php foreach ($children as $child): ?>
                                            <span class="child-pill"><?= htmlspecialchars($child) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: #94a3b8; font-size: 0.82rem; font-style: italic;">No dependents</span>
                                <?php endif; ?>
                            </td>
                            <td style="color: #64748b; font-size: 0.85rem;"><?= date('M d, Y', strtotime($record['created_at'])) ?></td>
                            <td style="text-align: center;">
                                <div class="action-buttons-wrapper">
                                    <a href="<?= $spotmap_page ?>?edit_id=<?= $record['id'] ?>" class="action-btn edit-btn" title="Edit details and position on map">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </a>
                                    <a href="process_household.php?delete_id=<?= $record['id'] ?>&target_file=<?= $target_file ?>&return=<?= $resident_page ?>" 
                                       class="action-btn delete-btn" 
                                       onclick="return confirm('Are you sure you want to delete House #<?= htmlspecialchars(addslashes($record['house_number'])) ?>?');" 
                                       title="Remove household from map">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </a>
                                </div>
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
