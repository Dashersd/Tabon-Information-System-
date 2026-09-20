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
    <title>Barangay Board - Brgy Tabon</title>
    
    <!-- FontAwesome CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin Stylesheet -->
    <link rel="stylesheet" href="css/admin_common.css">
    <link rel="stylesheet" href="css/barangayboard.css">

    <style>
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-container {
            background: #fff;
            width: 400px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .modal-header {
            background: #223c3b;
            color: #fff;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            font-size: 16px;
            margin: 0;
        }

        .modal-header button {
            background: none;
            border: none;
            color: #fff;
            cursor: pointer;
            font-size: 16px;
        }

        .modal-body {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 13px;
        }

        .modal-footer {
            padding: 15px 20px;
            background: #f4f6f8;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            border-top: 1px solid #e0e4e8;
        }

        .btn-cancel {
            background: #e5e7eb;
            color: #4b5563;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
        }
    </style>
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
                <li><a href="controlpanel.php"><i class="fa-solid fa-gauge"></i> Control Panel</a></li>
                <li><a href="spotmapmanage.php"><i class="fa-solid fa-map-location-dot"></i> SpotMap Manage</a></li>
                <li><a href="communitymembers.php"><i class="fa-solid fa-users"></i> Community Members</a></li>
                <li><a href="admindashboard.php"><i class="fa-solid fa-house-chimney"></i> Households</a></li>
                <li><a href="#"><i class="fa-solid fa-location-dot"></i> Puroks</a></li>
                <li><a href="medialibrary.php"><i class="fa-regular fa-images"></i> Media Library</a></li>
                <li><a href="barangayboard.php" class="active"><i class="fa-solid fa-user-tie"></i>Barangay Board</a></li>
                <li><a href="adminsettings.php"><i class="fa-solid fa-gear"></i> Admin Settings</a></li>
                
                <br>
                <li><a href="../login.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        
        <div class="top-bar">
            <h1 class="page-title">Barangay Board</h1>
            <button class="btn-add" onclick="openNewModal()"><i class="fa-solid fa-plus"></i> Add Official</button>
        </div>

        <!-- Gallery Container -->
        <div class="board-gallery" id="board-gallery-container">
            <!-- Cards populated by JS -->
        </div>
        
    </main>

    <!-- Edit/Add Modal -->
    <div class="modal-overlay" id="edit-modal">
        <div class="modal-container">
            <div class="modal-header">
                <h2 id="modal-title">Edit Official</h2>
                <button id="close-modal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <form id="edit-form">
                    <div class="form-group" style="text-align: center; margin-bottom: 20px;">
                        <img src="" id="preview-photo" style="width: 100px; height: 100px; object-fit: cover; border-radius: 10px; border: 2px solid #e0e4e8; margin-bottom: 10px;">
                        <div>
                            <button type="button" id="remove-photo-btn" style="background: none; border: none; color: #dc2626; cursor: pointer; font-size: 12px;"><i class="fa-solid fa-trash"></i> Remove Photo</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" id="official-name" required>
                    </div>
                    <div class="form-group">
                        <label>Position / Role</label>
                        <select id="official-position">
                            <option value="Barangay Captain">Barangay Captain</option>
                            <option value="Barangay Kagawad">Barangay Kagawad</option>
                            <option value="SK Chairman">SK Chairman</option>
                            <option value="Barangay Secretary">Barangay Secretary</option>
                            <option value="Barangay Treasurer">Barangay Treasurer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Photo URL</label>
                        <input type="text" id="official-photo-url" placeholder="https://...">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" id="cancel-modal">Cancel</button>
                <button type="button" class="btn-add" id="save-modal">Save Changes</button>
            </div>
        </div>
    </div>

    <script>
        // Mock Database for Officials
        let officialsDB = [
            { id: 1, name: 'Hon. Juan Dela Cruz', position: 'Barangay Captain', photo: 'https://via.placeholder.com/300x400/223c3b/FFFFFF?text=Captain' },
            { id: 2, name: 'Hon. Maria Santos', position: 'Barangay Kagawad', photo: 'https://via.placeholder.com/300x400/2c6e49/FFFFFF?text=Kagawad' },
            { id: 3, name: 'Hon. Pedro Penduko', position: 'Barangay Kagawad', photo: 'https://via.placeholder.com/300x400/2c6e49/FFFFFF?text=Kagawad' },
            { id: 4, name: 'Mr. Jose Rizal', position: 'Barangay Secretary', photo: 'https://via.placeholder.com/300x400/6b7280/FFFFFF?text=Secretary' }
        ];

        let editingId = null;

        const galleryContainer = document.getElementById('board-gallery-container');
        const editModal = document.getElementById('edit-modal');
        const modalTitle = document.getElementById('modal-title');
        
        const closeBtn = document.getElementById('close-modal');
        const cancelBtn = document.getElementById('cancel-modal');
        const saveBtn = document.getElementById('save-modal');
        
        const nameInput = document.getElementById('official-name');
        const posInput = document.getElementById('official-position');
        const photoInput = document.getElementById('official-photo-url');
        const previewPhoto = document.getElementById('preview-photo');
        const removePhotoBtn = document.getElementById('remove-photo-btn');

        // Render Gallery
        function renderGallery() {
            galleryContainer.innerHTML = '';
            officialsDB.forEach(official => {
                const card = document.createElement('div');
                card.className = 'board-card';
                card.innerHTML = `
                    <div class="board-img-container">
                        <img src="${official.photo || 'https://via.placeholder.com/300x400?text=No+Photo'}" alt="Official Photo">
                    </div>
                    <div class="board-info">
                        <h3>${official.name}</h3>
                        <p>${official.position}</p>
                        <div class="board-actions">
                            <button class="btn-action" onclick="openEdit(${official.id})"><i class="fa-solid fa-pen"></i> Edit</button>
                            <button class="btn-action" onclick="deleteOfficial(${official.id})" style="color: #dc2626;"><i class="fa-solid fa-trash"></i> Remove</button>
                        </div>
                    </div>
                `;
                galleryContainer.appendChild(card);
            });
        }

        // Open Add Modal
        window.openNewModal = function() {
            editingId = null;
            modalTitle.innerText = "Add Official";
            
            nameInput.value = '';
            posInput.value = 'Barangay Kagawad';
            photoInput.value = '';
            previewPhoto.src = 'https://via.placeholder.com/300x400?text=No+Photo';
            
            editModal.classList.add('active');
        }

        // Open Edit Modal
        window.openEdit = function(id) {
            editingId = id;
            modalTitle.innerText = "Edit Official";
            
            const official = officialsDB.find(o => o.id === id);
            
            nameInput.value = official.name;
            posInput.value = official.position;
            photoInput.value = official.photo;
            previewPhoto.src = official.photo || 'https://via.placeholder.com/300x400?text=No+Photo';
            
            editModal.classList.add('active');
        }

        // Remove Photo action inside modal
        removePhotoBtn.addEventListener('click', () => {
            photoInput.value = '';
            previewPhoto.src = 'https://via.placeholder.com/300x400?text=No+Photo';
        });

        // Update preview dynamically
        photoInput.addEventListener('input', (e) => {
            previewPhoto.src = e.target.value || 'https://via.placeholder.com/300x400?text=No+Photo';
        });

        // Delete Official
        window.deleteOfficial = function(id) {
            if (confirm("Are you sure you want to remove this official?")) {
                officialsDB = officialsDB.filter(o => o.id !== id);
                renderGallery();
            }
        }

        // Close Modal events
        closeBtn.addEventListener('click', () => editModal.classList.remove('active'));
        cancelBtn.addEventListener('click', () => editModal.classList.remove('active'));

        // Save Changes
        saveBtn.addEventListener('click', () => {
            if (editingId) {
                // Edit existing
                const official = officialsDB.find(o => o.id === editingId);
                if (official) {
                    official.name = nameInput.value;
                    official.position = posInput.value;
                    official.photo = photoInput.value;
                }
            } else {
                // Add new
                const newId = officialsDB.length > 0 ? Math.max(...officialsDB.map(o => o.id)) + 1 : 1;
                officialsDB.push({
                    id: newId,
                    name: nameInput.value || 'Unknown',
                    position: posInput.value,
                    photo: photoInput.value
                });
            }
            
            renderGallery();
            editModal.classList.remove('active');
        });

        // Initial render
        renderGallery();
    </script>
</body>
</html>
