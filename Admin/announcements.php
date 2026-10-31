<?php
// Admin/announcements.php - Complete Announcements & Updates Management Dashboard
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../db_connect.php';

$success_msg = '';
$error_msg = '';

// Handle POST actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. CREATE ANNOUNCEMENT
    if ($action === 'create') {
        $title = trim($_POST['announcementTitle'] ?? '');
        $what = trim($_POST['announcementWhat'] ?? '');
        $when_date = trim($_POST['announcementWhen'] ?? '');
        $where_place = trim($_POST['announcementWhere'] ?? '');
        $who = trim($_POST['announcementWho'] ?? '');
        $why = trim($_POST['announcementWhy'] ?? '');
        $content = trim($_POST['announcementContent'] ?? '');
        $status = in_array($_POST['announcementStatus'] ?? '', ['published', 'draft']) ? $_POST['announcementStatus'] : 'published';

        $cover_image_path = null;
        if (isset($_FILES['announcementImage']) && $_FILES['announcementImage']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/announcements/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = pathinfo($_FILES['announcementImage']['name'], PATHINFO_EXTENSION);
            $clean_name = time() . '_' . uniqid() . '.' . preg_replace("/[^a-zA-Z0-9]/", "", $ext);
            $target = $upload_dir . $clean_name;
            if (move_uploaded_file($_FILES['announcementImage']['tmp_name'], $target)) {
                $cover_image_path = $target;
            }
        }

        if (!empty($title)) {
            $stmt = $conn->prepare("INSERT INTO announcements (title, what, when_date, where_place, who, why, content, cover_image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssss", $title, $what, $when_date, $where_place, $who, $why, $content, $cover_image_path, $status);
            if ($stmt->execute()) {
                header("Location: announcements.php?msg=created");
                exit();
            } else {
                $error_msg = "Error creating announcement: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error_msg = "Title is required.";
        }
    }

    // 2. UPDATE ANNOUNCEMENT
    if ($action === 'update') {
        $id = (int)($_POST['announcementId'] ?? 0);
        $title = trim($_POST['announcementTitle'] ?? '');
        $what = trim($_POST['announcementWhat'] ?? '');
        $when_date = trim($_POST['announcementWhen'] ?? '');
        $where_place = trim($_POST['announcementWhere'] ?? '');
        $who = trim($_POST['announcementWho'] ?? '');
        $why = trim($_POST['announcementWhy'] ?? '');
        $content = trim($_POST['announcementContent'] ?? '');
        $status = in_array($_POST['announcementStatus'] ?? '', ['published', 'draft']) ? $_POST['announcementStatus'] : 'published';

        if ($id > 0 && !empty($title)) {
            $current_stmt = $conn->prepare("SELECT cover_image FROM announcements WHERE id = ?");
            $current_stmt->bind_param("i", $id);
            $current_stmt->execute();
            $res = $current_stmt->get_result();
            $current_record = $res->fetch_assoc();
            $current_stmt->close();

            $cover_image_path = $current_record['cover_image'] ?? null;

            if (isset($_FILES['announcementImage']) && $_FILES['announcementImage']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/announcements/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $ext = pathinfo($_FILES['announcementImage']['name'], PATHINFO_EXTENSION);
                $clean_name = time() . '_' . uniqid() . '.' . preg_replace("/[^a-zA-Z0-9]/", "", $ext);
                $target = $upload_dir . $clean_name;
                if (move_uploaded_file($_FILES['announcementImage']['tmp_name'], $target)) {
                    if (!empty($cover_image_path) && file_exists($cover_image_path)) {
                        @unlink($cover_image_path);
                    }
                    $cover_image_path = $target;
                }
            }

            $stmt = $conn->prepare("UPDATE announcements SET title = ?, what = ?, when_date = ?, where_place = ?, who = ?, why = ?, content = ?, cover_image = ?, status = ? WHERE id = ?");
            $stmt->bind_param("sssssssssi", $title, $what, $when_date, $where_place, $who, $why, $content, $cover_image_path, $status, $id);
            if ($stmt->execute()) {
                header("Location: announcements.php?msg=updated");
                exit();
            } else {
                $error_msg = "Error updating announcement: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error_msg = "Invalid data or missing title.";
        }
    }

    // 3. DELETE ANNOUNCEMENT
    if ($action === 'delete') {
        $id = (int)($_POST['announcementId'] ?? 0);
        if ($id > 0) {
            $current_stmt = $conn->prepare("SELECT cover_image FROM announcements WHERE id = ?");
            $current_stmt->bind_param("i", $id);
            $current_stmt->execute();
            $res = $current_stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                if (!empty($row['cover_image']) && file_exists($row['cover_image'])) {
                    @unlink($row['cover_image']);
                }
            }
            $current_stmt->close();

            $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            header("Location: announcements.php?msg=deleted");
            exit();
        }
    }
}

// Flash messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created') $success_msg = "Announcement successfully published!";
    if ($_GET['msg'] === 'updated') $success_msg = "Announcement successfully updated!";
    if ($_GET['msg'] === 'deleted') $success_msg = "Announcement successfully removed!";
}

// Fetch all announcements
$announcements = [];
$result = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Announcements - Brgy Tabon</title>
    
    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Admin Common & Announcements Stylesheet -->
    <link rel="stylesheet" href="Admin CSS/admin_common.css?v=<?= time() ?>">
    <link rel="stylesheet" href="Admin CSS/announcements.css?v=<?php echo time(); ?>">
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
                <li><a href="controlpanel.php"><i class="fa-solid fa-desktop"></i> Control Panel</a></li>
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
                <li><a href="services.php"><i class="fa-solid fa-bell-concierge"></i> Services</a></li>
                <li><a href="announcements.php" class="active"><i class="fa-solid fa-bullhorn"></i> Announcement</a></li>
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

        <div class="dashboard-content">

            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success_msg); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <div class="admin-header-row">
                <h2>Announcements & Updates</h2>
                <button class="btn-action btn-add" onclick="openModal('addAnnouncementModal')">
                    <i class="bi bi-plus-lg"></i> Add New Announcement
                </button>
            </div>
            
            <div class="admin-container">
                <div class="table-responsive" style="overflow-x: auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Cover</th>
                                <th>Title & Summary</th>
                                <th>Schedule / When</th>
                                <th>Target Audience</th>
                                <th>Status</th>
                                <th>Date Added</th>
                                <th style="text-align: center; width: 190px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($announcements)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: #64748b; padding: 40px; font-style: italic;">
                                        No announcements found. Click "+ Add New Announcement" to post your first update.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($announcements as $ann): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($ann['cover_image']) && file_exists($ann['cover_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($ann['cover_image']); ?>" alt="Cover" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; display:block;">
                                            <?php else: ?>
                                                <div style="width: 50px; height: 50px; background: #f1f5f9; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 1.2rem;">
                                                    <i class="bi bi-image"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong style="color: #0f172a; font-size: 0.95rem; display: block; margin-bottom: 4px;">
                                                <?php echo htmlspecialchars($ann['title']); ?>
                                            </strong>
                                            <?php if (!empty($ann['what'])): ?>
                                                <small style="color: #64748b; display: flex; align-items: center; gap: 4px; font-size: 0.82rem;">
                                                    <i class="bi bi-tag-fill" style="font-size: 0.75rem;"></i> <?php echo htmlspecialchars($ann['what']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span style="color: #0f172a; font-weight: 600; font-size: 0.9rem; display: block;">
                                                <?php echo htmlspecialchars($ann['when_date'] ?: 'Not specified'); ?>
                                            </span>
                                            <?php if (!empty($ann['where_place'])): ?>
                                                <small style="color: #64748b; display: flex; align-items: center; gap: 4px; font-size: 0.82rem; margin-top: 2px;">
                                                    <i class="bi bi-geo-alt-fill" style="font-size: 0.75rem; color: #0284c7;"></i> <?php echo htmlspecialchars($ann['where_place']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td style="color: #475569; font-size: 0.9rem;">
                                            <?php echo htmlspecialchars($ann['who'] ?: 'General Public'); ?>
                                        </td>
                                        <td>
                                            <?php if ($ann['status'] === 'published'): ?>
                                                <span class="badge published"><i class="bi bi-eye-fill"></i> Published</span>
                                            <?php else: ?>
                                                <span class="badge draft"><i class="bi bi-eye-slash-fill"></i> Draft</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="color: #64748b; font-size: 0.85rem; font-weight: 500;">
                                            <?php echo date('M d, Y', strtotime($ann['created_at'])); ?>
                                        </td>
                                        <td style="text-align: center; white-space: nowrap; padding: 12px 14px;">
                                            <div class="table-actions-group" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; flex-wrap: nowrap;">
                                                <button type="button" class="btn-sm btn-edit" style="margin: 0; padding: 7px 14px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; font-weight: 600; font-size: 0.85rem;" onclick='openEditModal(<?php echo json_encode($ann, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'>
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </button>
                                                <form action="" method="POST" style="display: inline-block; margin: 0;" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="announcementId" value="<?php echo (int)$ann['id']; ?>">
                                                    <button type="submit" class="btn-sm btn-delete" style="margin: 0; padding: 7px 14px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; font-weight: 600; font-size: 0.85rem;">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- ======================================================== -->
    <!-- MODAL 1: ADD ANNOUNCEMENT                                 -->
    <!-- ======================================================== -->
    <div id="addAnnouncementModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Announcement</h2>
                <span class="close-modal" onclick="closeModal('addAnnouncementModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form action="" method="POST" id="addAnnouncementForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">

                    <div class="form-group">
                        <label for="addTitle">Announcement Title *</label>
                        <input type="text" id="addTitle" name="announcementTitle" class="form-control" required placeholder="e.g., General Assembly Meeting">
                    </div>
                    
                    <div class="form-group">
                        <label for="addImage">Cover Image (Optional)</label>
                        <input type="file" id="addImage" name="announcementImage" class="form-control" accept="image/*">
                        <small style="color: #64748b; font-size: 0.8rem; margin-top: 3px; display: block;">Upload a banner/poster for this announcement.</small>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="addWhat">What (Short summary)</label>
                            <input type="text" id="addWhat" name="announcementWhat" class="form-control" placeholder="e.g., Free Medical & Dental Mission">
                        </div>
                        <div class="form-group">
                            <label for="addWhen">When (Date & Time)</label>
                            <input type="text" id="addWhen" name="announcementWhen" class="form-control" placeholder="e.g., October 22, 2026, 8:00 AM">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="addWhere">Where (Location / Venue)</label>
                            <input type="text" id="addWhere" name="announcementWhere" class="form-control" placeholder="e.g., Barangay Gymnasium">
                        </div>
                        <div class="form-group">
                            <label for="addWho">Who (Target Audience)</label>
                            <input type="text" id="addWho" name="announcementWho" class="form-control" placeholder="e.g., All residents of Barangay Tabon">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="addWhy">Why (Purpose / Objective)</label>
                        <textarea id="addWhy" name="announcementWhy" class="form-control" rows="2" placeholder="e.g., To discuss upcoming infrastructure projects and community budget."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="addContent">Full Description / Message</label>
                        <textarea id="addContent" name="announcementContent" class="form-control" rows="4" placeholder="Detailed announcement instructions, requirements, or reminders..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="addStatus">Publication Status</label>
                        <select id="addStatus" name="announcementStatus" class="form-control" required>
                            <option value="published">Published (Visible on Website)</option>
                            <option value="draft">Draft (Hidden)</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('addAnnouncementModal')">Cancel</button>
                <button type="submit" form="addAnnouncementForm" class="btn-action">Publish Announcement</button>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL 2: EDIT ANNOUNCEMENT                                -->
    <!-- ======================================================== -->
    <div id="editAnnouncementModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Announcement</h2>
                <span class="close-modal" onclick="closeModal('editAnnouncementModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form action="" method="POST" id="editAnnouncementForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" id="editId" name="announcementId" value="">

                    <div class="form-group">
                        <label for="editTitle">Announcement Title *</label>
                        <input type="text" id="editTitle" name="announcementTitle" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Current Cover Image</label>
                        <div id="editImagePreview" style="margin-bottom: 8px;"></div>
                        <label for="editImage" style="font-weight: 500; font-size: 0.85rem; color: #64748b; margin-bottom: 4px;">Replace Cover Image (Optional)</label>
                        <input type="file" id="editImage" name="announcementImage" class="form-control" accept="image/*">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="editWhat">What (Short summary)</label>
                            <input type="text" id="editWhat" name="announcementWhat" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="editWhen">When (Date & Time)</label>
                            <input type="text" id="editWhen" name="announcementWhen" class="form-control">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="editWhere">Where (Location / Venue)</label>
                            <input type="text" id="editWhere" name="announcementWhere" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="editWho">Who (Target Audience)</label>
                            <input type="text" id="editWho" name="announcementWho" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="editWhy">Why (Purpose / Objective)</label>
                        <textarea id="editWhy" name="announcementWhy" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="editContent">Full Description / Message</label>
                        <textarea id="editContent" name="announcementContent" class="form-control" rows="4"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="editStatus">Publication Status</label>
                        <select id="editStatus" name="announcementStatus" class="form-control" required>
                            <option value="published">Published (Visible on Website)</option>
                            <option value="draft">Draft (Hidden)</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('editAnnouncementModal')">Cancel</button>
                <button type="submit" form="editAnnouncementForm" class="btn-action">Save Changes</button>
            </div>
        </div>
    </div>

    <!-- JavaScript Helpers -->
    <script>
    function openModal(id) { 
        document.getElementById(id).style.display = 'flex'; 
    }
    function closeModal(id) { 
        document.getElementById(id).style.display = 'none'; 
    }
    window.onclick = function(e) { 
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none'; 
        }
    };

    function openEditModal(ann) {
        document.getElementById('editId').value = ann.id;
        document.getElementById('editTitle').value = ann.title || '';
        document.getElementById('editWhat').value = ann.what || '';
        document.getElementById('editWhen').value = ann.when_date || '';
        document.getElementById('editWhere').value = ann.where_place || '';
        document.getElementById('editWho').value = ann.who || '';
        document.getElementById('editWhy').value = ann.why || '';
        document.getElementById('editContent').value = ann.content || '';
        document.getElementById('editStatus').value = ann.status || 'published';

        const preview = document.getElementById('editImagePreview');
        if (ann.cover_image) {
            preview.innerHTML = `<img src="${ann.cover_image}" alt="Cover" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; display: block;">`;
        } else {
            preview.innerHTML = `<span style="color: #94a3b8; font-size: 0.85rem; font-style: italic;">No cover image uploaded</span>`;
        }

        openModal('editAnnouncementModal');
    }
    </script>
</body>
</html>
