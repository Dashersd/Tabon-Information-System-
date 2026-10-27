<?php
// Admin/services.php - Barangay Services Management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/db_connect.php';

$success_msg = '';
$error_msg = '';

// Handle POST actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. CREATE SERVICE
    if ($action === 'create') {
        $name = trim($_POST['serviceName'] ?? '');
        $icon = trim($_POST['serviceIcon'] ?? 'bi-file-earmark-text');
        $status = in_array($_POST['serviceStatus'] ?? '', ['active', 'inactive']) ? $_POST['serviceStatus'] : 'active';
        $fee = trim($_POST['serviceFee'] ?? 'Free');
        $processing_time = trim($_POST['serviceProcessingTime'] ?? '15-30 Minutes');
        $description = trim($_POST['serviceDesc'] ?? '');
        $requirements = trim($_POST['serviceRequirements'] ?? '');
        $button_text = trim($_POST['serviceButtonText'] ?? 'Request Document');
        $button_url = trim($_POST['serviceButtonUrl'] ?? 'contact.php');
        $display_order = (int)($_POST['serviceDisplayOrder'] ?? 0);

        if (!empty($name) && !empty($description)) {
            $stmt = $conn->prepare("INSERT INTO services (name, icon, status, fee, processing_time, description, requirements, button_text, button_url, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssi", $name, $icon, $status, $fee, $processing_time, $description, $requirements, $button_text, $button_url, $display_order);
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

    // 2. UPDATE SERVICE
    if ($action === 'update') {
        $id = (int)($_POST['serviceId'] ?? 0);
        $name = trim($_POST['serviceName'] ?? '');
        $icon = trim($_POST['serviceIcon'] ?? 'bi-file-earmark-text');
        $status = in_array($_POST['serviceStatus'] ?? '', ['active', 'inactive']) ? $_POST['serviceStatus'] : 'active';
        $fee = trim($_POST['serviceFee'] ?? 'Free');
        $processing_time = trim($_POST['serviceProcessingTime'] ?? '15-30 Minutes');
        $description = trim($_POST['serviceDesc'] ?? '');
        $requirements = trim($_POST['serviceRequirements'] ?? '');
        $button_text = trim($_POST['serviceButtonText'] ?? 'Request Document');
        $button_url = trim($_POST['serviceButtonUrl'] ?? 'contact.php');
        $display_order = (int)($_POST['serviceDisplayOrder'] ?? 0);

        if ($id > 0 && !empty($name) && !empty($description)) {
            $stmt = $conn->prepare("UPDATE services SET name = ?, icon = ?, status = ?, fee = ?, processing_time = ?, description = ?, requirements = ?, button_text = ?, button_url = ?, display_order = ? WHERE id = ?");
            $stmt->bind_param("sssssssssii", $name, $icon, $status, $fee, $processing_time, $description, $requirements, $button_text, $button_url, $display_order, $id);
            if ($stmt->execute()) {
                header("Location: services.php?msg=updated");
                exit();
            } else {
                $error_msg = "Error updating service: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error_msg = "Invalid service ID or missing required fields.";
        }
    }

    // 3. DELETE SERVICE
    if ($action === 'delete') {
        $id = (int)($_POST['serviceId'] ?? 0);
        if ($id > 0) {
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
    <title>Manage Services - Brgy Tabon</title>
    
    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Admin Common & Services Stylesheet -->
    <link rel="stylesheet" href="css/admin_common.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Admin CSS/services.css?v=<?php echo time(); ?>">
</head>
<body>

    <!-- Sidebar Navigation -->
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
                <h1 style="font-size: 20px; font-weight: 700; color: #1f2937; margin: 0;">Barangay Tabon Info SpotMap</h1>
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

        <div class="dashboard-content" style="padding: 30px; max-width: 1300px; margin: 0 auto;">

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
                                <th style="width: 60px;">Icon</th>
                                <th>Service Name</th>
                                <th>Fee & Time</th>
                                <th>Description</th>
                                <th style="width: 100px;">Status</th>
                                <th class="action-th" style="text-align: center; width: 220px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($services)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: #64748b; font-style: italic;">
                                        <i class="bi bi-folder2-open" style="font-size: 2.5rem; display: block; margin-bottom: 10px; color: #94a3b8;"></i>
                                        No services found. Click <strong>"Add New Service"</strong> to create one.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($services as $service): ?>
                                    <tr>
                                        <td style="color: #94a3b8; font-weight: 600;">
                                            <?= (int)($service['display_order'] ?? 0) ?>
                                        </td>
                                        <td>
                                            <div class="table-icon-cell">
                                                <i class="bi <?= htmlspecialchars($service['icon'] ?: 'bi-file-earmark-text') ?>"></i>
                                            </div>
                                        </td>
                                        <td>
                                            <strong style="color: #093d25;"><?= htmlspecialchars($service['name']) ?></strong>
                                        </td>
                                        <td style="font-size: 0.85rem; color: #475569;">
                                            <div><i class="bi bi-cash-stack" style="color: #137547;"></i> <?= !empty($service['fee']) ? htmlspecialchars($service['fee']) : 'Free' ?></div>
                                            <div><i class="bi bi-clock" style="color: #64748b;"></i> <?= !empty($service['processing_time']) ? htmlspecialchars($service['processing_time']) : 'Same Day' ?></div>
                                        </td>
                                        <td style="max-width: 280px; color: #475569; font-size: 0.88rem; line-height: 1.4;">
                                            <?= htmlspecialchars(mb_strimwidth($service['description'] ?? '', 0, 95, '...')) ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $service['status'] === 'active' ? 'active' : 'inactive' ?>">
                                                <?= ucfirst(htmlspecialchars($service['status'])) ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <div class="action-buttons-wrapper">
                                                <button type="button" class="btn-sm btn-edit" onclick='openEditModal(<?= json_encode($service, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </button>

                                                <form method="POST" action="services.php" style="margin: 0; display: inline-flex;">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="serviceId" value="<?= (int)$service['id'] ?>">
                                                    <button type="submit" class="btn-sm btn-toggle" title="Toggle Active / Inactive">
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
    <!-- MODAL 1: ADD SERVICE                                      -->
    <!-- ======================================================== -->
    <div id="addServiceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="bi bi-plus-circle"></i> Add New Service</h2>
                <button type="button" class="close-modal-btn" onclick="closeModal('addServiceModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="services.php" method="POST" id="addServiceForm">
                    <input type="hidden" name="action" value="create">

                    <div class="form-group">
                        <label for="addServiceName">Service Name <span class="required">*</span></label>
                        <input type="text" id="addServiceName" name="serviceName" class="form-control" required placeholder="e.g. Barangay Clearance">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="addServiceIcon">Service Icon <span class="required">*</span></label>
                            <select id="addServiceIcon" name="serviceIcon" class="form-control" required>
                                <option value="bi-file-earmark-text">📄 Document / Clearance</option>
                                <option value="bi-house-heart">🏠 Indigency / Welfare</option>
                                <option value="bi-briefcase">💼 Business / Permit</option>
                                <option value="bi-person-badge">🪪 Residency / ID Card</option>
                                <option value="bi-card-heading">📜 Certificate / Credential</option>
                                <option value="bi-shield-check">🛡️ Protection / Blotter</option>
                                <option value="bi-activity">🩺 Health Clinic / Medical</option>
                                <option value="bi-heart-pulse">❤️ Emergency / Senior Care</option>
                                <option value="bi-people">👥 Community / Youth</option>
                                <option value="bi-cash-coin">🪙 Cedula / Financial</option>
                                <option value="bi-building">🏢 Building / Construction</option>
                                <option value="bi-mortarboard">🎓 Education / Scholarship</option>
                                <option value="bi-tools">🛠️ Maintenance / Infrastructure</option>
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

                    <div class="form-row">
                        <div class="form-group">
                            <label for="addServiceFee">Fee / Cost</label>
                            <input type="text" id="addServiceFee" name="serviceFee" class="form-control" value="Free" placeholder="e.g. Free, ₱50.00">
                        </div>

                        <div class="form-group">
                            <label for="addServiceProcessingTime">Processing Time</label>
                            <input type="text" id="addServiceProcessingTime" name="serviceProcessingTime" class="form-control" value="15-30 Minutes" placeholder="e.g. 15-30 Minutes, Same Day">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="addServiceDesc">Description <span class="required">*</span></label>
                        <textarea id="addServiceDesc" name="serviceDesc" class="form-control" rows="3" required placeholder="Brief description of the service and its purpose..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="addServiceRequirements">Requirements (Optional)</label>
                        <textarea id="addServiceRequirements" name="serviceRequirements" class="form-control" rows="3" placeholder="e.g. Valid Government ID, Cedula, Purok Certification"></textarea>
                        <small class="form-hint">List the required documents or qualifications needed by the resident.</small>
                    </div>

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

                    <div class="form-group">
                        <label for="addServiceDisplayOrder">Display Order</label>
                        <input type="number" id="addServiceDisplayOrder" name="serviceDisplayOrder" class="form-control" value="<?= $next_order ?>" min="1">
                        <small class="form-hint">Lower numbers appear first on the public services list.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('addServiceModal')">Cancel</button>
                <button type="submit" form="addServiceForm" class="btn-submit">Save Service</button>
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
                <form action="services.php" method="POST" id="editServiceForm">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="serviceId" id="editServiceId">

                    <div class="form-group">
                        <label for="editServiceName">Service Name <span class="required">*</span></label>
                        <input type="text" id="editServiceName" name="serviceName" class="form-control" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editServiceIcon">Service Icon <span class="required">*</span></label>
                            <select id="editServiceIcon" name="serviceIcon" class="form-control" required>
                                <option value="bi-file-earmark-text">📄 Document / Clearance</option>
                                <option value="bi-house-heart">🏠 Indigency / Welfare</option>
                                <option value="bi-briefcase">💼 Business / Permit</option>
                                <option value="bi-person-badge">🪪 Residency / ID Card</option>
                                <option value="bi-card-heading">📜 Certificate / Credential</option>
                                <option value="bi-shield-check">🛡️ Protection / Blotter</option>
                                <option value="bi-activity">🩺 Health Clinic / Medical</option>
                                <option value="bi-heart-pulse">❤️ Emergency / Senior Care</option>
                                <option value="bi-people">👥 Community / Youth</option>
                                <option value="bi-cash-coin">🪙 Cedula / Financial</option>
                                <option value="bi-building">🏢 Building / Construction</option>
                                <option value="bi-mortarboard">🎓 Education / Scholarship</option>
                                <option value="bi-tools">🛠️ Maintenance / Infrastructure</option>
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

                    <div class="form-group">
                        <label for="editServiceDesc">Description <span class="required">*</span></label>
                        <textarea id="editServiceDesc" name="serviceDesc" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="editServiceRequirements">Requirements (Optional)</label>
                        <textarea id="editServiceRequirements" name="serviceRequirements" class="form-control" rows="3"></textarea>
                    </div>

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

                    <div class="form-group">
                        <label for="editServiceDisplayOrder">Display Order</label>
                        <input type="number" id="editServiceDisplayOrder" name="serviceDisplayOrder" class="form-control" min="1">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('editServiceModal')">Cancel</button>
                <button type="submit" form="editServiceForm" class="btn-submit">Update Service</button>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL 3: DELETE SERVICE                                   -->
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
                        Are you sure you want to permanently delete <strong id="deleteServiceName" style="color: #093d25;"></strong>?
                    </p>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('deleteServiceModal')">Cancel</button>
                <button type="submit" form="deleteServiceForm" class="btn-submit" style="background-color: #ef4444;">Delete</button>
            </div>
        </div>
    </div>

    <script>
    function openModal(id) {
        const m = document.getElementById(id);
        if (m) m.style.display = 'flex';
    }

    function closeModal(id) {
        const m = document.getElementById(id);
        if (m) m.style.display = 'none';
    }

    function openEditModal(service) {
        document.getElementById('editServiceId').value = service.id;
        document.getElementById('editServiceName').value = service.name || '';
        document.getElementById('editServiceDesc').value = service.description || '';
        document.getElementById('editServiceRequirements').value = service.requirements || '';
        document.getElementById('editServiceFee').value = service.fee || '';
        document.getElementById('editServiceProcessingTime').value = service.processing_time || '';
        document.getElementById('editServiceButtonText').value = service.button_text || 'Request Document';
        document.getElementById('editServiceButtonUrl').value = service.button_url || 'contact.php';
        document.getElementById('editServiceDisplayOrder').value = service.display_order || service.id;
        document.getElementById('editServiceIcon').value = service.icon || 'bi-file-earmark-text';
        document.getElementById('editServiceStatus').value = service.status || 'active';
        openModal('editServiceModal');
    }

    function openDeleteModal(id, name) {
        document.getElementById('deleteServiceId').value = id;
        document.getElementById('deleteServiceName').textContent = '"' + name + '"';
        openModal('deleteServiceModal');
    }

    window.onclick = function(e) {
        if (e.target.classList.contains('modal')) e.target.style.display = 'none';
    };

    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
        }
    });
    </script>
</body>
</html>
