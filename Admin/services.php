<?php
// Admin/services.php - Barangay Tabon Services Management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/db_connect.php';

// Safe check: ensure document_file column exists in services table
$col_check = $conn->query("SHOW COLUMNS FROM services LIKE 'document_file'");
if ($col_check && $col_check->num_rows === 0) {
    $conn->query("ALTER TABLE services ADD COLUMN document_file VARCHAR(255) NULL AFTER requirements");
}

$success_msg = '';
$error_msg = '';

// Helper: Sample Document Template Image Upload (Images only, protected view)
$handle_document_upload = function($file_key) {
    if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $upload_dir = 'uploads/documents/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $orig_name = $_FILES[$file_key]['name'];
    $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($ext, $allowed, true)) {
        return false; // Only image templates allowed
    }

    // Verify it is a legitimate image
    $img_check = @getimagesize($_FILES[$file_key]['tmp_name']);
    if ($img_check === false) {
        return false;
    }

    $clean_ext = preg_replace("/[^a-zA-Z0-9]/", "", $ext);
    $filename = 'template_' . time() . '_' . uniqid() . '.' . $clean_ext;
    $target = $upload_dir . $filename;

    if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $target)) {
        return $target;
    }
    return false;
};

// Handle POST actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. CREATE SERVICE
    if ($action === 'create') {
        $name = trim($_POST['serviceName'] ?? '');
        $icon = trim($_POST['serviceIcon'] ?? 'bi-file-earmark-text');
        $status = in_array($_POST['serviceStatus'] ?? '', ['active', 'inactive']) ? $_POST['serviceStatus'] : 'active';
        $fee = trim($_POST['serviceFee'] ?? 'Free');
        $processing_time = trim($_POST['serviceProcessingTime'] ?? 'Same Day');
        $description = trim($_POST['serviceDesc'] ?? '');
        $requirements = trim($_POST['serviceRequirements'] ?? '');
        $button_text = trim($_POST['serviceButtonText'] ?? 'Request Document');
        $button_url = trim($_POST['serviceButtonUrl'] ?? 'contact.php');
        $display_order = (int)($_POST['serviceDisplayOrder'] ?? 0);

        $document_file = null;
        if (isset($_FILES['serviceDocument']) && $_FILES['serviceDocument']['error'] === UPLOAD_ERR_OK) {
            $upload_res = $handle_document_upload('serviceDocument');
            if ($upload_res === false) {
                $error_msg = "Invalid image file. Please upload a sample template image in JPG, PNG, or WEBP format.";
            } else {
                $document_file = $upload_res;
            }
        }

        if (empty($error_msg)) {
            if (!empty($name) && !empty($description)) {
                $stmt = $conn->prepare("INSERT INTO services (name, icon, status, fee, processing_time, description, requirements, document_file, button_text, button_url, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssssssi", $name, $icon, $status, $fee, $processing_time, $description, $requirements, $document_file, $button_text, $button_url, $display_order);
                if ($stmt->execute()) {
                    header("Location: services.php?msg=created");
                    exit();
                } else {
                    $error_msg = "Error creating service: " . $conn->error;
                }
                $stmt->close();
            } else {
                $error_msg = "Service name and description are required.";
            }
        }
    }

    // 2. UPDATE SERVICE
    if ($action === 'update') {
        $id = (int)($_POST['serviceId'] ?? 0);
        $name = trim($_POST['serviceName'] ?? '');
        $icon = trim($_POST['serviceIcon'] ?? 'bi-file-earmark-text');
        $status = in_array($_POST['serviceStatus'] ?? '', ['active', 'inactive']) ? $_POST['serviceStatus'] : 'active';
        $fee = trim($_POST['serviceFee'] ?? 'Free');
        $processing_time = trim($_POST['serviceProcessingTime'] ?? 'Same Day');
        $description = trim($_POST['serviceDesc'] ?? '');
        $requirements = trim($_POST['serviceRequirements'] ?? '');
        $button_text = trim($_POST['serviceButtonText'] ?? 'Request Document');
        $button_url = trim($_POST['serviceButtonUrl'] ?? 'contact.php');
        $display_order = (int)($_POST['serviceDisplayOrder'] ?? 0);
        $remove_doc = ($_POST['removeDocument'] ?? '0') === '1';

        if ($id > 0 && !empty($name) && !empty($description)) {
            // Fetch existing document_file
            $stmt_old = $conn->prepare("SELECT document_file FROM services WHERE id = ?");
            $stmt_old->bind_param("i", $id);
            $stmt_old->execute();
            $res_old = $stmt_old->get_result();
            $old_row = $res_old->fetch_assoc();
            $stmt_old->close();

            $current_doc = $old_row['document_file'] ?? null;
            $document_to_save = $current_doc;

            if (isset($_FILES['serviceDocument']) && $_FILES['serviceDocument']['error'] === UPLOAD_ERR_OK) {
                $upload_res = $handle_document_upload('serviceDocument');
                if ($upload_res === false) {
                    $error_msg = "Invalid image file. Please upload a sample template image in JPG, PNG, or WEBP format.";
                } elseif ($upload_res !== null) {
                    if (!empty($current_doc) && file_exists(__DIR__ . '/' . $current_doc)) {
                        @unlink(__DIR__ . '/' . $current_doc);
                    }
                    $document_to_save = $upload_res;
                }
            } elseif ($remove_doc) {
                if (!empty($current_doc) && file_exists(__DIR__ . '/' . $current_doc)) {
                    @unlink(__DIR__ . '/' . $current_doc);
                }
                $document_to_save = null;
            }

            if (empty($error_msg)) {
                $stmt = $conn->prepare("UPDATE services SET name = ?, icon = ?, status = ?, fee = ?, processing_time = ?, description = ?, requirements = ?, document_file = ?, button_text = ?, button_url = ?, display_order = ? WHERE id = ?");
                $stmt->bind_param("ssssssssssii", $name, $icon, $status, $fee, $processing_time, $description, $requirements, $document_to_save, $button_text, $button_url, $display_order, $id);
                if ($stmt->execute()) {
                    header("Location: services.php?msg=updated");
                    exit();
                } else {
                    $error_msg = "Error updating service: " . $conn->error;
                }
                $stmt->close();
            }
        } else {
            $error_msg = "Invalid service ID or missing required fields.";
        }
    }

    // 3. DELETE SERVICE
    if ($action === 'delete') {
        $id = (int)($_POST['serviceId'] ?? 0);
        if ($id > 0) {
            $stmt_old = $conn->prepare("SELECT document_file FROM services WHERE id = ?");
            $stmt_old->bind_param("i", $id);
            $stmt_old->execute();
            $res_old = $stmt_old->get_result();
            if ($old_row = $res_old->fetch_assoc()) {
                if (!empty($old_row['document_file']) && file_exists(__DIR__ . '/' . $old_row['document_file'])) {
                    @unlink(__DIR__ . '/' . $old_row['document_file']);
                }
            }
            $stmt_old->close();

            $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                header("Location: services.php?msg=deleted");
                exit();
            } else {
                $error_msg = "Error deleting service: " . $conn->error;
            }
            $stmt->close();
        }
    }

    // 4. TOGGLE STATUS (ACTIVE <-> INACTIVE)
    if ($action === 'toggle_status') {
        $id = (int)($_POST['serviceId'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE services SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                header("Location: services.php?msg=status_updated");
                exit();
            } else {
                $error_msg = "Error updating status: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Flash alerts
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created') $success_msg = "New service added successfully!";
    if ($_GET['msg'] === 'updated') $success_msg = "Service updated successfully!";
    if ($_GET['msg'] === 'deleted') $success_msg = "Service deleted successfully!";
    if ($_GET['msg'] === 'status_updated') $success_msg = "Service status toggled successfully!";
}

// Fetch all services ordered by display_order
$services = [];
$services_res = $conn->query("SELECT * FROM services ORDER BY display_order ASC, id ASC");
if ($services_res) {
    while ($row = $services_res->fetch_assoc()) {
        $services[] = $row;
    }
}
$next_order = count($services) + 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services - Barangay Tabon Information System</title>
    
    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Admin Common & Services Stylesheet -->
    <link rel="stylesheet" href="Admin CSS/admin_common.css?v=<?= time() ?>">
    <link rel="stylesheet" href="Admin CSS/services.css?v=<?php echo time(); ?>">
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
                <li><a href="services.php" class="active"><i class="fa-solid fa-bell-concierge"></i> Services</a></li>
                <li><a href="announcements.php"><i class="fa-solid fa-bullhorn"></i> Announcement</a></li>
                <li><a href="feedback.php"><i class="fa-solid fa-comments"></i> Feedback Chat</a></li>
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
                            dropdownContent.style.display = "none";
                            dropdownContent.classList.remove("active");
                        } else {
                            dropdownContent.style.display = "block";
                            dropdownContent.classList.add("active");
                        }
                    });
                }
            });
        </script>
    </aside>

    <!-- Main Content Area -->
    <div class="main-content">
        <!-- Top Navbar Header -->
        <div class="top-header" style="background: #ffffff; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div class="header-title">
                <h1 style="font-size: 20px; font-weight: 700; color: #1f2937; margin: 0;">Manage Services</h1>
            </div>
            
            <div class="user-profile" style="display: flex; align-items: center; gap: 12px;">
                <div class="user-info" style="text-align: right;">
                    <span style="font-size: 14px; font-weight: 600; color: #374151; display: block;">Barangay Secretary</span>
                    <p style="font-size: 12px; color: #6b7280; margin: 0;">Administrator</p>
                </div>
                <div class="user-avatar" style="width: 40px; height: 40px; background-color: #f1c40f; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #000;">
                    A
                </div>
            </div>
        </div>

        <div class="dashboard-content" style="padding: 30px; max-width: 1350px; margin: 0 auto; overflow-y: auto;">

            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($success_msg) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error_msg) ?>
                </div>
            <?php endif; ?>

            <div class="admin-header-row">
                <h2>Barangay Services (<?= count($services) ?>)</h2>
                <button class="btn-action btn-add" onclick="openModal('addServiceModal')">
                    <i class="bi bi-plus-lg"></i> Add New Service
                </button>
            </div>
            
            <div class="admin-container">
                <div class="table-responsive" style="overflow-x: auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th style="width: 65px;">Icon</th>
                                <th>Service Name</th>
                                <th>Fee & Time</th>
                                <th>Description</th>
                                <th style="width: 140px;">Sample Template</th>
                                <th style="width: 100px;">Status</th>
                                <th class="action-th" style="text-align: center; width: 220px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($services)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 45px 20px; color: #64748b; font-style: italic;">
                                        <i class="bi bi-folder2-open" style="font-size: 2.6rem; display: block; margin-bottom: 10px; color: #94a3b8;"></i>
                                        No services found. Click <strong>"+ Add New Service"</strong> to create one.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($services as $service): ?>
                                    <tr>
                                        <!-- 1. Display Order -->
                                        <td style="color: #94a3b8; font-weight: 600;">
                                            <?= (int)($service['display_order'] ?? 0) ?>
                                        </td>

                                        <!-- 2. Icon (44x44px rounded box) -->
                                        <td>
                                            <div class="table-icon-cell">
                                                <i class="bi <?= htmlspecialchars($service['icon'] ?: 'bi-file-earmark-text') ?>"></i>
                                            </div>
                                        </td>

                                        <!-- 3. Service Name -->
                                        <td>
                                            <span class="service-name-text"><?= htmlspecialchars($service['name']) ?></span>
                                        </td>

                                        <!-- 4. Fee & Time -->
                                        <td>
                                            <div class="meta-fee-time">
                                                <div>
                                                    <i class="bi bi-cash-stack fee-icon"></i> 
                                                    <span><?= !empty($service['fee']) ? htmlspecialchars($service['fee']) : 'Free' ?></span>
                                                </div>
                                                <div>
                                                    <i class="bi bi-clock time-icon"></i> 
                                                    <span><?= !empty($service['processing_time']) ? htmlspecialchars($service['processing_time']) : 'Same Day' ?></span>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- 5. Description (truncated to 90 chars) -->
                                        <td>
                                            <div class="service-desc-text">
                                                <?= htmlspecialchars(mb_strimwidth($service['description'] ?? '', 0, 90, '...')) ?>
                                            </div>
                                        </td>

                                        <!-- 6. Sample Template Image Link or 'No file' -->
                                        <td>
                                            <?php 
                                            $doc_path = $service['document_file'] ?? '';
                                            if (!empty($doc_path) && file_exists(__DIR__ . '/' . $doc_path)): 
                                            ?>
                                                <button type="button" class="badge-doc-file" onclick="openViewOnlyTemplate('<?= htmlspecialchars($doc_path, ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($service['name']), ENT_QUOTES) ?>')" title="View sample template (Cannot be downloaded)">
                                                    <i class="bi bi-eye"></i> View Sample
                                                </button>
                                            <?php else: ?>
                                                <span class="no-doc-file">No image</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- 7. Status -->
                                        <td>
                                            <span class="badge <?= $service['status'] === 'active' ? 'active' : 'inactive' ?>">
                                                <?= ucfirst(htmlspecialchars($service['status'])) ?>
                                            </span>
                                        </td>

                                        <!-- 8. Actions -->
                                        <td style="text-align: center;">
                                            <div class="action-buttons-wrapper">
                                                <button type="button" class="btn-sm btn-edit" onclick='openEditModal(<?= json_encode($service, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </button>

                                                <form method="POST" action="services.php" style="margin: 0; display: inline-flex;">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="serviceId" value="<?= (int)$service['id'] ?>">
                                                    <button type="submit" class="btn-sm btn-toggle" title="<?= $service['status'] === 'active' ? 'Set Inactive' : 'Set Active' ?>">
                                                        <i class="bi <?= $service['status'] === 'active' ? 'bi-eye-slash' : 'bi-eye' ?>"></i>
                                                    </button>
                                                </form>

                                                <button type="button" class="btn-sm btn-delete" onclick="openDeleteModal(<?= (int)$service['id'] ?>, '<?= addslashes(htmlspecialchars($service['name'])) ?>')">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
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
    </div>

    <!-- ======================================================== -->
    <!-- MODAL 1: ADD NEW SERVICE                                  -->
    <!-- ======================================================== -->
    <div id="addServiceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="bi bi-plus-circle"></i> Add New Service</h2>
                <button type="button" class="close-modal-btn" onclick="closeModal('addServiceModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="services.php" method="POST" enctype="multipart/form-data" id="addServiceForm">
                    <input type="hidden" name="action" value="create">

                    <!-- Service Name -->
                    <div class="form-group">
                        <label for="addServiceName">Service Name <span class="required">*</span></label>
                        <input type="text" id="addServiceName" name="serviceName" class="form-control" required placeholder="e.g. Barangay Clearance">
                    </div>
                    
                    <!-- 2-Column Row: Icon & Status -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="addServiceIcon">Service Icon <span class="required">*</span></label>
                            <select id="addServiceIcon" name="serviceIcon" class="form-control" required>
                                <option value="bi-person-badge" selected>🪪 Residency / ID Card</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="addServiceStatus">Status <span class="required">*</span></label>
                            <select id="addServiceStatus" name="serviceStatus" class="form-control" required>
                                <option value="active">Active (Visible)</option>
                                <option value="inactive">Inactive (Hidden)</option>
                            </select>
                        </div>
                    </div>

                    <!-- 2-Column Row: Fee & Processing Time -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="addServiceFee">Fee / Cost</label>
                            <input type="text" id="addServiceFee" name="serviceFee" class="form-control" value="Free" placeholder="e.g. Free, ₱50.00">
                        </div>

                        <div class="form-group">
                            <label for="addServiceProcessingTime">Processing Time</label>
                            <input type="text" id="addServiceProcessingTime" name="serviceProcessingTime" class="form-control" value="Same Day" placeholder="e.g. Same Day, 15-30 Minutes">
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="form-group">
                        <label for="addServiceDesc">Description <span class="required">*</span></label>
                        <textarea id="addServiceDesc" name="serviceDesc" class="form-control" rows="3" required placeholder="Brief description of the service and its purpose..."></textarea>
                    </div>

                    <!-- Requirements (Optional) -->
                    <div class="form-group">
                        <label for="addServiceRequirements">Requirements (Optional)</label>
                        <textarea id="addServiceRequirements" name="serviceRequirements" class="form-control" rows="3" placeholder="e.g. Valid Government ID, Cedula, Purok Certification"></textarea>
                        <small class="form-hint">List the required documents or qualifications needed by the resident.</small>
                    </div>

                    <!-- Sample Template Image Upload (Optional) -->
                    <div class="form-group">
                        <label for="addServiceDocument"><i class="bi bi-image" style="color: #137547;"></i> Upload Sample / Template Image (Optional)</label>
                        <input type="file" id="addServiceDocument" name="serviceDocument" class="form-control" accept="image/png, image/jpeg, image/jpg, image/webp" onchange="previewAddTemplateImage(this)">
                        <small class="form-hint">Upload an image sample of this document (e.g. Barangay Clearance template). Allowed formats: JPG, PNG, WEBP. <em>(View-Only • Protected from download)</em></small>
                        
                        <!-- Live Image Preview for Add Modal -->
                        <div id="addTemplatePreviewBox" style="display:none; margin-top:10px; padding:10px; background:#f8fafc; border:1px dashed #cbd5e1; border-radius:8px; align-items:center; gap:12px;">
                            <img id="addTemplatePreviewImg" src="" alt="Sample Template Preview" style="height:65px; border-radius:6px; border:1px solid #e2e8f0; object-fit:contain; pointer-events:none; user-select:none;" oncontextmenu="return false;">
                            <span style="font-size:0.83rem; color:#15803d; font-weight:600;"><i class="bi bi-check2-circle"></i> Image selected (View-Only mode active)</span>
                        </div>
                    </div>

                    <!-- 2-Column Row: Action Button Text & URL -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="addServiceButtonText">Action Button Text</label>
                            <input type="text" id="addServiceButtonText" name="serviceButtonText" class="form-control" value="Request Document">
                        </div>

                        <div class="form-group">
                            <label for="addServiceButtonUrl">Button Destination URL</label>
                            <input type="text" id="addServiceButtonUrl" name="serviceButtonUrl" class="form-control" value="contact.php">
                        </div>
                    </div>

                    <!-- Display Order -->
                    <div class="form-group">
                        <label for="addServiceDisplayOrder">Display Order</label>
                        <input type="number" id="addServiceDisplayOrder" name="serviceDisplayOrder" class="form-control" value="<?= $next_order ?>" min="1">
                        <small class="form-hint">Lower numbers appear first on the public services list.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('addServiceModal')">Cancel</button>
                <button type="submit" form="addServiceForm" class="btn-submit">
                    <i class="bi bi-check-lg"></i> Save Service
                </button>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL 2: EDIT SERVICE                                     -->
    <!-- ======================================================== -->
    <div id="editServiceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="bi bi-pencil-square"></i> Edit Service</h2>
                <button type="button" class="close-modal-btn" onclick="closeModal('editServiceModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="services.php" method="POST" enctype="multipart/form-data" id="editServiceForm">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="serviceId" id="editServiceId">
                    <input type="hidden" name="removeDocument" id="editRemoveDocument" value="0">

                    <!-- Service Name -->
                    <div class="form-group">
                        <label for="editServiceName">Service Name <span class="required">*</span></label>
                        <input type="text" id="editServiceName" name="serviceName" class="form-control" required>
                    </div>
                    
                    <!-- 2-Column Row: Icon & Status -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editServiceIcon">Service Icon <span class="required">*</span></label>
                            <select id="editServiceIcon" name="serviceIcon" class="form-control" required>
                                <option value="bi-person-badge" selected>🪪 Residency / ID Card</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="editServiceStatus">Status <span class="required">*</span></label>
                            <select id="editServiceStatus" name="serviceStatus" class="form-control" required>
                                <option value="active">Active (Visible)</option>
                                <option value="inactive">Inactive (Hidden)</option>
                            </select>
                        </div>
                    </div>

                    <!-- 2-Column Row: Fee & Processing Time -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editServiceFee">Fee / Cost</label>
                            <input type="text" id="editServiceFee" name="serviceFee" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="editServiceProcessingTime">Processing Time</label>
                            <input type="text" id="editServiceProcessingTime" name="serviceProcessingTime" class="form-control">
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="form-group">
                        <label for="editServiceDesc">Description <span class="required">*</span></label>
                        <textarea id="editServiceDesc" name="serviceDesc" class="form-control" rows="3" required></textarea>
                    </div>

                    <!-- Requirements (Optional) -->
                    <div class="form-group">
                        <label for="editServiceRequirements">Requirements (Optional)</label>
                        <textarea id="editServiceRequirements" name="serviceRequirements" class="form-control" rows="3"></textarea>
                    </div>

                    <!-- Sample Template Image Upload with Current Preview & Removal -->
                    <div class="form-group">
                        <label for="editServiceDocument"><i class="bi bi-image" style="color: #137547;"></i> Sample / Template Image (Optional)</label>
                        
                        <!-- Attached Template Container -->
                        <div id="editCurrentDocumentContainer" class="current-doc-container" style="display: none; align-items:center; justify-content:space-between; padding:10px 14px; background-color:#f0fdf4; border:1px dashed #86efac; border-radius:8px; margin-bottom:10px; gap:12px;">
                            <div class="current-doc-info" style="display:flex; align-items:center; gap:10px;">
                                <img id="editDocPreviewThumb" src="" alt="Template Thumbnail" style="height:48px; width:48px; object-fit:cover; border-radius:6px; border:1px solid #cbd5e1; pointer-events:none; user-select:none;" oncontextmenu="return false;">
                                <div>
                                    <strong id="editDocFileName" style="display:block; font-size:0.88rem; color:#0f172a; margin-bottom:3px;">sample.png</strong>
                                    <button type="button" class="btn-preview-only" onclick="openCurrentEditPreview()" style="background:#e8f5ee; color:#137547; border:1px solid #86efac; padding:3px 9px; border-radius:5px; font-size:0.78rem; font-weight:700; cursor:pointer;">
                                        <i class="bi bi-eye"></i> View Sample (View-Only)
                                    </button>
                                </div>
                            </div>
                            <button type="button" class="btn-remove-doc" onclick="removeCurrentDocument()">
                                <i class="bi bi-trash"></i> Remove Image
                            </button>
                        </div>

                        <input type="file" id="editServiceDocument" name="serviceDocument" class="form-control" accept="image/png, image/jpeg, image/jpg, image/webp" onchange="previewEditTemplateImage(this)">
                        <small class="form-hint">Upload a replacement image sample template (JPG, PNG, WEBP). <em>(View-Only • Protected from download)</em></small>
                        
                        <!-- Live preview of newly chosen file in edit modal -->
                        <div id="editNewTemplatePreviewBox" style="display:none; margin-top:10px; padding:10px; background:#f8fafc; border:1px dashed #cbd5e1; border-radius:8px; align-items:center; gap:12px;">
                            <img id="editNewTemplatePreviewImg" src="" alt="New Preview" style="height:65px; border-radius:6px; border:1px solid #e2e8f0; object-fit:contain; pointer-events:none; user-select:none;" oncontextmenu="return false;">
                            <span style="font-size:0.83rem; color:#15803d; font-weight:600;"><i class="bi bi-check2-circle"></i> New replacement image selected</span>
                        </div>
                    </div>

                    <!-- 2-Column Row: Action Button Text & URL -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editServiceButtonText">Action Button Text</label>
                            <input type="text" id="editServiceButtonText" name="serviceButtonText" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="editServiceButtonUrl">Button Destination URL</label>
                            <input type="text" id="editServiceButtonUrl" name="serviceButtonUrl" class="form-control">
                        </div>
                    </div>

                    <!-- Display Order -->
                    <div class="form-group">
                        <label for="editServiceDisplayOrder">Display Order</label>
                        <input type="number" id="editServiceDisplayOrder" name="serviceDisplayOrder" class="form-control" min="1">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('editServiceModal')">Cancel</button>
                <button type="submit" form="editServiceForm" class="btn-submit">
                    <i class="bi bi-check-lg"></i> Update Service
                </button>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL 3: DELETE SERVICE CONFIRMATION                      -->
    <!-- ======================================================== -->
    <div id="deleteServiceModal" class="modal">
        <div class="modal-content" style="max-width: 440px;">
            <div class="modal-header">
                <h2><i class="bi bi-trash" style="color: #ef4444;"></i> Delete Service</h2>
                <button type="button" class="close-modal-btn" onclick="closeModal('deleteServiceModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="services.php" method="POST" id="deleteServiceForm">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="serviceId" id="deleteServiceId">
                    <p style="color: #475569; font-size: 0.95rem; line-height: 1.5; margin: 0;">
                        Are you sure you want to permanently delete <strong id="deleteServiceName" style="color: #093d25;"></strong>? This action cannot be undone.
                    </p>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('deleteServiceModal')">Cancel</button>
                <button type="submit" form="deleteServiceForm" class="btn-submit" style="background-color: #ef4444;">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL 4: VIEW-ONLY SAMPLE TEMPLATE PREVIEW MODAL         -->
    <!-- ======================================================== -->
    <div id="viewTemplateModal" class="modal" onclick="closeModal('viewTemplateModal')">
        <div class="modal-content" onclick="event.stopPropagation()" style="max-width: 680px; padding: 0; overflow: hidden; border-radius: 16px;">
            <div class="modal-header" style="background: #093d25; color: #ffffff; padding: 16px 22px; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="bi bi-file-earmark-image" style="font-size: 1.25rem; color: #fbb117;"></i>
                    <div>
                        <h3 id="viewTemplateModalTitle" style="font-size: 1.05rem; margin: 0; color: #ffffff; font-weight: 700;">Sample Document Template</h3>
                        <span style="font-size: 0.76rem; color: #a7f3d0; text-transform: uppercase; letter-spacing: 0.5px;">View-Only Reference • Cannot be downloaded</span>
                    </div>
                </div>
                <button type="button" class="close-modal-btn" onclick="closeModal('viewTemplateModal')" style="color: #ffffff; font-size: 1.5rem; background: none; border: none; cursor: pointer;">&times;</button>
            </div>
            
            <div class="modal-body" style="padding: 20px; text-align: center; background: #f8fafc; position: relative;">
                <!-- Protection Watermark Banner -->
                <div style="margin-bottom: 12px; display: inline-flex; align-items: center; gap: 8px; background: #fef3c7; color: #92400e; padding: 5px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; border: 1px solid #fde68a;">
                    <i class="bi bi-shield-lock-fill"></i> Official Barangay Tabon Template Sample (Preview Only)
                </div>

                <!-- Template Image Frame with contextmenu and drag disabled -->
                <div style="position: relative; max-height: 520px; overflow-y: auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; box-shadow: inset 0 2px 6px rgba(0,0,0,0.03);">
                    <img id="viewTemplateModalImg" 
                         src="" 
                         alt="Document Template Sample" 
                         style="max-width: 100%; height: auto; display: block; margin: 0 auto; border-radius: 6px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); -webkit-user-drag: none; user-select: none; pointer-events: auto;"
                         draggable="false"
                         oncontextmenu="return false;"
                         onselectstart="return false;">
                </div>
                <p style="margin: 12px 0 0 0; font-size: 0.8rem; color: #64748b;">
                    <i class="bi bi-info-circle"></i> This sample image serves as a reference preview for residents and staff. Downloading is disabled for security and document integrity.
                </p>
            </div>
            
            <div class="modal-footer" style="padding: 12px 20px; background: #ffffff; border-top: 1px solid #e2e8f0; text-align: right;">
                <button type="button" class="btn-secondary" onclick="closeModal('viewTemplateModal')" style="padding: 8px 22px; border-radius: 8px; background: #e2e8f0; color: #334155; font-weight: 600; border: none; cursor: pointer;">Close Preview</button>
            </div>
        </div>
    </div>

    <script>
    let currentEditDocSrc = '';
    let currentEditDocName = '';

    function openViewOnlyTemplate(src, name) {
        document.getElementById('viewTemplateModalTitle').textContent = (name || 'Service') + ' - Sample Template';
        document.getElementById('viewTemplateModalImg').src = src;
        openModal('viewTemplateModal');
    }

    function openCurrentEditPreview() {
        if (currentEditDocSrc) {
            openViewOnlyTemplate(currentEditDocSrc, currentEditDocName);
        }
    }

    function previewAddTemplateImage(input) {
        const box = document.getElementById('addTemplatePreviewBox');
        const img = document.getElementById('addTemplatePreviewImg');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                img.src = e.target.result;
                box.style.display = 'flex';
            };
            reader.readAsDataURL(input.files[0]);
        } else {
            box.style.display = 'none';
        }
    }

    function previewEditTemplateImage(input) {
        const box = document.getElementById('editNewTemplatePreviewBox');
        const img = document.getElementById('editNewTemplatePreviewImg');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                img.src = e.target.result;
                box.style.display = 'flex';
            };
            reader.readAsDataURL(input.files[0]);
        } else {
            box.style.display = 'none';
        }
    }

    function openModal(id) {
        const m = document.getElementById(id);
        if (m) {
            m.style.display = 'flex';
        }
    }

    function closeModal(id) {
        const m = document.getElementById(id);
        if (m) {
            m.style.display = 'none';
        }
    }

    function removeCurrentDocument() {
        document.getElementById('editRemoveDocument').value = '1';
        const container = document.getElementById('editCurrentDocumentContainer');
        if (container) {
            container.style.display = 'none';
        }
    }

    function openEditModal(service) {
        document.getElementById('editServiceId').value = service.id;
        document.getElementById('editServiceName').value = service.name || '';
        document.getElementById('editServiceDesc').value = service.description || '';
        document.getElementById('editServiceRequirements').value = service.requirements || '';
        document.getElementById('editServiceFee').value = service.fee || 'Free';
        document.getElementById('editServiceProcessingTime').value = service.processing_time || 'Same Day';
        document.getElementById('editServiceButtonText').value = service.button_text || 'Request Document';
        document.getElementById('editServiceButtonUrl').value = service.button_url || 'contact.php';
        document.getElementById('editServiceDisplayOrder').value = service.display_order || service.id;
        document.getElementById('editServiceStatus').value = service.status || 'active';

        // Check icon option existence; dynamically append if custom icon
        const iconSelect = document.getElementById('editServiceIcon');
        const iconVal = service.icon || 'bi-file-earmark-text';
        let found = false;
        for (let i = 0; i < iconSelect.options.length; i++) {
            if (iconSelect.options[i].value === iconVal) {
                found = true;
                break;
            }
        }
        if (!found && iconVal) {
            const opt = document.createElement('option');
            opt.value = iconVal;
            opt.textContent = '⭐ ' + iconVal;
            iconSelect.appendChild(opt);
        }
        iconSelect.value = iconVal;

        // Reset document removal flag & file input
        document.getElementById('editRemoveDocument').value = '0';
        const fileInput = document.getElementById('editServiceDocument');
        if (fileInput) fileInput.value = '';

        // Reset preview boxes
        const newPreviewBox = document.getElementById('editNewTemplatePreviewBox');
        if (newPreviewBox) newPreviewBox.style.display = 'none';

        // Display current document/image if present
        const docContainer = document.getElementById('editCurrentDocumentContainer');
        if (service.document_file && service.document_file.trim() !== '') {
            const fileName = service.document_file.split('/').pop();
            document.getElementById('editDocFileName').textContent = fileName;
            const thumb = document.getElementById('editDocPreviewThumb');
            if (thumb) thumb.src = service.document_file;
            currentEditDocSrc = service.document_file;
            currentEditDocName = service.name || 'Sample Template';
            docContainer.style.display = 'flex';
        } else {
            currentEditDocSrc = '';
            currentEditDocName = '';
            docContainer.style.display = 'none';
        }

        openModal('editServiceModal');
    }

    function openDeleteModal(id, name) {
        document.getElementById('deleteServiceId').value = id;
        document.getElementById('deleteServiceName').textContent = '"' + name + '"';
        openModal('deleteServiceModal');
    }

    // Outside click closes modal
    window.onclick = function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    };

    // Escape key closes modals
    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
        }
    });
    </script>
</body>
</html>
