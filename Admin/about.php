<?php
// Admin/about.php - Manage Dynamic About Us (Image, History, Mission, Vision)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../db_connect.php';

// 1. Ensure table exists
$conn->query("CREATE TABLE IF NOT EXISTS `about_content` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `history` LONGTEXT NOT NULL,
    `mission` TEXT NOT NULL,
    `vision` TEXT NOT NULL,
    `image_path` VARCHAR(255) NOT NULL DEFAULT 'Images/Tabon Barangay hall.jpg',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

// 2. Fetch current record (seed if empty)
$query = $conn->query("SELECT * FROM `about_content` WHERE `id` = 1 LIMIT 1");
if (!$query || $query->num_rows === 0) {
    $default_history = "Tabon means \"to cover\". It is derived to the characteristics of a bird name in Subanen dialect “MANGAWAG”, because when this kind of bird will lay eggs, they will dig a hole in the ground and lay eggs therein, after that it will covered with earth/soil.\n\nTabon was inhabited by Subanen who made their living by picking a fruit trees and hunting wild animals in the jungle. These Lumads made houses of “NIBONG” and “SUSAY” leaves for roofing and tying rattan for the light local materials, instead of using nails. As the Lumad hunters looked for food, he roamed around to hunt, until he saw something covered in the ground with dry leaves, branches and roots of trees with mixed soil. The hunter removed the cover and dig the hole, while digging he found out it was an eggs of a wild fowl called “MANGAWAG”. This kind of wild fowls, dig the ground to lay eggs and made it a nests. The word “TABON” came from the word “TABONAN” means “TO COVER”.\n\nBarangay Tabon is the home of the Lumad Subanen, one of the 18 major Lumad tribes in Mindanao Island. The first Subanen families who inhabited the place when it was not yet a Barangay are only the families of “MAMULO, UNGOB, & MANGEDLOG”.\n\nWhen the Barangay was not yet created it was a sitio of “BARANGAY MARWING”, and when Barangay Marwing was created into a Municipality in the year 1964, Tabon was created into Barangay by the Municipal Councilors of Marwing. That when Municipality of Marwing was dissolved in the year 1965, the municipal Government of Lapuyan still recognized Tabon as Barangay. That during those prior years a sitio will be created into a Barangay to not less than 500 inhabitants & in the year 1972, there was a Circular from the Office of the President that all Barangay should be approved by the House of Representatives and Senate and to be signed by the President before it will become a Regular Barangay. On “AUGUST 28, 1972”, by Virtue of “REPUBLIC ACT 3590” Tabon was created a regular Barangay.\n\nThe first leader of the Barangay was “SAGUILE LIPA”, because it was created into Barangay when Marwing was created into Municipality and until Marwing was dissolved, the municipality of Lapuyan still recognized it as a Barangay. That during that time said SAGUILE LIPA was a municipal councilor.\n\nThe first appointed Barangay Captain when Maruing was a Municipality was “BENTIA MALINTANG”, and when Maruing was dissolved being SAGUILE LIPA is no longer a Municipal Councilor, he was then appointed as Barangay Captain until he was again elected. Thereafter, it was again “PAPAO GALS, then RUBIO TILABAD, then Barangay Captain JANITO LIPA”, and the new elected Punong Barangay Nelcy D. Ongub.";
    $default_mission = "The Barangay Officials and the people in the Barangay united in working for the attainment of sufficient and effective services, increased income through developed agriculture and create activities that would foster greater unity and strong relationships among settlers, under the strong and good overnment administration and continually living and enjoying a balanced environment.";
    $default_vision = "The Barangay Tabon envisions a dynamic Barangay whose united, God-loving and empowered people. The Subanens and settlers will live harmoniously and peacefully, having a progressive and decent living in a balanced environment under a strong and good government administration.";
    $default_img = "Images/Tabon Barangay hall.jpg";

    $seed_stmt = $conn->prepare("INSERT INTO `about_content` (`id`, `history`, `mission`, `vision`, `image_path`) VALUES (1, ?, ?, ?, ?)");
    $seed_stmt->bind_param("ssss", $default_history, $default_mission, $default_vision, $default_img);
    $seed_stmt->execute();
    $seed_stmt->close();
    $query = $conn->query("SELECT * FROM `about_content` WHERE `id` = 1 LIMIT 1");
}
$about_data = $query->fetch_assoc();

$success_msg = '';
$error_msg = '';

if (isset($_GET['msg']) && $_GET['msg'] === 'saved') {
    $success_msg = 'About Us content (Image, History, Mission, and Vision) has been updated successfully!';
}

// 3. Handle Form Save (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $history = trim($_POST['history'] ?? '');
    $vision = trim($_POST['vision'] ?? '');
    $mission = trim($_POST['mission'] ?? '');
    
    $current_image = $about_data['image_path'] ?? 'Images/Tabon Barangay hall.jpg';
    $new_image_path = $current_image;

    // Check for Image Upload
    if (isset($_FILES['about_image']) && $_FILES['about_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $orig_name = $_FILES['about_image']['name'];
        $tmp_path = $_FILES['about_image']['tmp_name'];
        $file_size = $_FILES['about_image']['size'];
        $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_exts)) {
            $error_msg = 'Invalid image type. Please upload a JPG, PNG, WEBP, or GIF image.';
        } elseif ($file_size > 10 * 1024 * 1024) {
            $error_msg = 'Image file size is too large (maximum 10MB).';
        } else {
            $upload_dir = __DIR__ . '/uploads/about/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $clean_name = 'about_' . time() . '_' . substr(md5(uniqid(rand(), true)), 0, 8) . '.' . $ext;
            $destination = $upload_dir . $clean_name;

            if (move_uploaded_file($tmp_path, $destination)) {
                $new_image_path = 'uploads/about/' . $clean_name;
            } else {
                $error_msg = 'Failed to save the uploaded image to the server.';
            }
        }
    }

    if (empty($error_msg)) {
        if (empty($history) || empty($vision) || empty($mission)) {
            $error_msg = 'History, Vision, and Mission fields cannot be empty.';
        } else {
            $stmt = $conn->prepare("UPDATE `about_content` SET `history` = ?, `vision` = ?, `mission` = ?, `image_path` = ? WHERE `id` = 1");
            $stmt->bind_param("ssss", $history, $vision, $mission, $new_image_path);

            if ($stmt->execute()) {
                $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    $preview_url = (strpos($new_image_path, 'uploads/') === 0) ? $new_image_path : '../' . $new_image_path;
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'About Us content updated successfully!',
                        'image_path' => $new_image_path,
                        'preview_url' => $preview_url
                    ]);
                    exit();
                } else {
                    header("Location: about.php?msg=saved");
                    exit();
                }
            } else {
                $error_msg = 'Database error: ' . $conn->error;
            }
            $stmt->close();
        }
    }

    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    if ($is_ajax && !empty($error_msg)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $error_msg]);
        exit();
    }
}

// 4. Resolve Image Path for Admin preview
$admin_img_src = '';
$raw_img = $about_data['image_path'] ?? '';
if (!empty($raw_img)) {
    if (strpos($raw_img, 'uploads/') === 0) {
        $admin_img_src = $raw_img;
    } elseif (strpos($raw_img, 'Images/') === 0) {
        $admin_img_src = '../' . $raw_img;
    } else {
        $admin_img_src = '../' . $raw_img;
    }
} else {
    $admin_img_src = '../Images/Tabon Barangay hall.jpg';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage About Us - Brgy Tabon</title>
    
    <!-- FontAwesome CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin Stylesheet -->
    <link rel="stylesheet" href="css/admin_common.css">
    <link rel="stylesheet" href="css/about.css?v=<?= time() ?>">
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
                <li><a href="about.php" class="active"><i class="fa-solid fa-circle-info"></i> About</a></li>
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

    <!-- Main Content -->
    <main class="main-content">
        
        <div class="top-bar">
            <h1 class="page-title" style="font-size: 20px;">Manage About Us</h1>
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

        <div class="about-header">
            <h2>About Us Content</h2>
            <p>Update the official Image, History, Mission, and Vision displayed on the public landing and about pages.</p>
        </div>

        <!-- Feedback Alert Container -->
        <div id="alertContainer">
            <?php if (!empty($success_msg)): ?>
                <div class="alert-box success" id="alertBox">
                    <div class="alert-content">
                        <i class="fa-solid fa-circle-check"></i>
                        <span><?= htmlspecialchars($success_msg) ?></span>
                    </div>
                    <button type="button" class="alert-close" onclick="document.getElementById('alertBox').remove();">&times;</button>
                </div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <div class="alert-box error" id="alertBox">
                    <div class="alert-content">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span><?= htmlspecialchars($error_msg) ?></span>
                    </div>
                    <button type="button" class="alert-close" onclick="document.getElementById('alertBox').remove();">&times;</button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Form for Dynamic About Content -->
        <form action="about.php" method="POST" enctype="multipart/form-data" id="aboutForm">
            <div class="about-content-wrapper">
                
                <!-- Left Column: Image Upload & Preview -->
                <div class="about-left-col">
                    <div class="image-upload-card">
                        <div class="field-label">
                            <i class="fa-regular fa-image"></i> About &amp; History Image
                        </div>
                        
                        <div class="image-drop-area" id="imageDropArea" title="Click or drag and drop to change image">
                            <img id="aboutImagePreview" src="<?= htmlspecialchars($admin_img_src) ?>" alt="Barangay About Preview">
                            <div class="image-drop-overlay">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <span>Click or Drop New Image</span>
                            </div>
                        </div>

                        <!-- Hidden File Input -->
                        <input type="file" name="about_image" id="aboutImageInput" accept="image/png, image/jpeg, image/jpg, image/webp" style="display: none;">

                        <button type="button" class="btn-upload" id="btnChooseFile">
                            <i class="fa-solid fa-folder-open"></i> Choose Image
                        </button>
                        
                        <span id="selectedFileName" class="selected-file-label"></span>
                        <small class="image-hint">Supported formats: JPG, PNG, WEBP (Max 10MB). Changes appear on the public Polaroid frame and Landing sections.</small>
                    </div>
                </div>

                <!-- Right Column: History, Vision, Mission -->
                <div class="about-right-col">
                    
                    <!-- History Card -->
                    <div class="text-card">
                        <div class="card-header-bar">
                            <div class="field-label">
                                <i class="fa-solid fa-book-bookmark"></i> History
                            </div>
                            <span class="field-hint">Origin of name, Lumad Subanen roots, founding leaders</span>
                        </div>
                        <textarea name="history" id="aboutHistory" rows="10" placeholder="Enter barangay history here..." required><?= htmlspecialchars($about_data['history'] ?? '') ?></textarea>
                    </div>

                    <!-- Vision Card -->
                    <div class="text-card">
                        <div class="card-header-bar">
                            <div class="field-label">
                                <i class="fa-regular fa-eye"></i> Vision
                            </div>
                            <span class="field-hint">Aspirational future and long-term community goal</span>
                        </div>
                        <textarea name="vision" id="aboutVision" rows="4" placeholder="Enter barangay vision here..." required><?= htmlspecialchars($about_data['vision'] ?? '') ?></textarea>
                    </div>

                    <!-- Mission Card -->
                    <div class="text-card">
                        <div class="card-header-bar">
                            <div class="field-label">
                                <i class="fa-solid fa-bullseye"></i> Mission
                            </div>
                            <span class="field-hint">Public service, agriculture, unity, and environment</span>
                        </div>
                        <textarea name="mission" id="aboutMission" rows="4" placeholder="Enter barangay mission here..." required><?= htmlspecialchars($about_data['mission'] ?? '') ?></textarea>
                    </div>

                    <!-- Save Action -->
                    <div class="save-action-container">
                        <button type="submit" class="btn-save" id="btnSaveAbout">
                            <i class="fa-regular fa-floppy-disk"></i>
                            <span id="saveBtnText">Save Changes</span>
                        </button>
                    </div>

                </div>
            </div>
        </form>

    </main>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const dropArea = document.getElementById("imageDropArea");
        const fileInput = document.getElementById("aboutImageInput");
        const chooseBtn = document.getElementById("btnChooseFile");
        const previewImg = document.getElementById("aboutImagePreview");
        const fileNameLabel = document.getElementById("selectedFileName");
        const aboutForm = document.getElementById("aboutForm");
        const saveBtn = document.getElementById("btnSaveAbout");
        const saveBtnText = document.getElementById("saveBtnText");
        const alertContainer = document.getElementById("alertContainer");

        // Trigger file dialog
        chooseBtn.addEventListener("click", () => fileInput.click());
        dropArea.addEventListener("click", () => fileInput.click());

        // File selection handler
        fileInput.addEventListener("change", function(e) {
            handleFileSelect(this.files[0]);
        });

        // Drag & Drop events
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropArea.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropArea.classList.remove('dragover');
            }, false);
        });

        dropArea.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files && files.length > 0) {
                fileInput.files = files;
                handleFileSelect(files[0]);
            }
        });

        function handleFileSelect(file) {
            if (!file) return;
            if (!file.type.match('image.*')) {
                showAlert('Please select a valid image file (JPG, PNG, WEBP).', 'error');
                return;
            }

            fileNameLabel.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';

            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }

        function showAlert(msg, type = 'success') {
            const icon = type === 'success' ? 'fa-solid fa-circle-check' : 'fa-solid fa-triangle-exclamation';
            alertContainer.innerHTML = `
                <div class="alert-box ${type}" id="alertBox">
                    <div class="alert-content">
                        <i class="${icon}"></i>
                        <span>${msg}</span>
                    </div>
                    <button type="button" class="alert-close" onclick="document.getElementById('alertBox').remove();">&times;</button>
                </div>
            `;
            alertContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        // AJAX Form Submission for seamless instant update
        aboutForm.addEventListener("submit", function(e) {
            e.preventDefault();

            saveBtn.classList.add("loading");
            saveBtn.disabled = true;
            saveBtnText.textContent = "Saving Changes...";

            const formData = new FormData(aboutForm);

            fetch("about.php", {
                method: "POST",
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                saveBtn.classList.remove("loading");
                saveBtn.disabled = false;
                saveBtnText.textContent = "Save Changes";

                if (data.status === 'success') {
                    showAlert(data.message, 'success');
                    if (data.preview_url) {
                        previewImg.src = data.preview_url + '?v=' + new Date().getTime();
                    }
                    fileNameLabel.textContent = "";
                    fileInput.value = "";
                } else {
                    showAlert(data.message || 'Error saving changes.', 'error');
                }
            })
            .catch(err => {
                console.error("Save error:", err);
                // Fallback to normal form submit if fetch fails
                aboutForm.submit();
            });
        });
    });
    </script>

</body>
</html>
