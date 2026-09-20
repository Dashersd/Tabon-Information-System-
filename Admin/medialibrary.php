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
    <title>Media Library - Brgy Tabon</title>
    
    <!-- FontAwesome CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin Stylesheet -->
    <link rel="stylesheet" href="css/admin_common.css">
    <link rel="stylesheet" href="css/medialibrary.css">

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


                <li><a href="medialibrary.php" class="active"><i class="fa-regular fa-images"></i> Media Library</a></li>
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
            <h1 class="page-title">Media Library</h1>
            <button class="btn-add" onclick="openNewModal()"><i class="fa-solid fa-plus"></i> Upload Media</button>
        </div>

        <!-- Media Grid -->
        <div class="media-grid" id="media-grid-container">
            <!-- Items populated by JS -->
        </div>
        
    </main>

    <!-- Upload Modal -->
    <div class="modal-overlay" id="upload-modal">
        <div class="modal-container">
            <div class="modal-header">
                <h2>Upload New Image</h2>
                <button id="close-modal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <form id="upload-form">
                    <div class="form-group" style="text-align: center; margin-bottom: 20px;">
                        <img src="https://via.placeholder.com/400x300?text=Preview" id="preview-photo" style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px; border: 2px solid #e0e4e8; margin-bottom: 10px;">
                    </div>
                    <div class="form-group">
                        <label>Image URL</label>
                        <input type="text" id="media-url" placeholder="https://..." required>
                    </div>
                    <div class="form-group">
                        <label>Caption (Optional)</label>
                        <input type="text" id="media-caption" placeholder="E.g., Barangay Fiesta 2026">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" id="cancel-modal">Cancel</button>
                <button type="button" class="btn-add" id="save-modal">Save Media</button>
            </div>
        </div>
    </div>

    <script>
        // Mock Database for Media
        let mediaDB = [
            { id: 1, url: 'https://via.placeholder.com/400x300/223c3b/FFFFFF?text=Barangay+Hall', caption: 'Barangay Hall Facade' },
            { id: 2, url: 'https://via.placeholder.com/400x300/2c6e49/FFFFFF?text=Medical+Mission', caption: 'Medical Mission 2026' },
            { id: 3, url: 'https://via.placeholder.com/400x300/4a5568/FFFFFF?text=Clean+Up+Drive', caption: 'Coastal Clean-up Drive' },
            { id: 4, url: 'https://via.placeholder.com/400x300/dd6b20/FFFFFF?text=Sports+Fest', caption: 'Summer Sports Fest' }
        ];

        const gridContainer = document.getElementById('media-grid-container');
        const uploadModal = document.getElementById('upload-modal');
        
        const closeBtn = document.getElementById('close-modal');
        const cancelBtn = document.getElementById('cancel-modal');
        const saveBtn = document.getElementById('save-modal');
        
        const urlInput = document.getElementById('media-url');
        const captionInput = document.getElementById('media-caption');
        const previewPhoto = document.getElementById('preview-photo');

        // Render Gallery
        function renderMedia() {
            gridContainer.innerHTML = '';
            mediaDB.forEach(media => {
                const item = document.createElement('div');
                item.className = 'media-item';
                item.innerHTML = `
                    <img src="${media.url}" alt="${media.caption}" class="media-img" onerror="this.src='https://via.placeholder.com/400x300?text=Error'">
                    <button class="media-delete-btn" onclick="deleteMedia(${media.id})" title="Remove Image"><i class="fa-solid fa-trash"></i></button>
                    <div class="media-caption" title="${media.caption}">${media.caption || 'No Caption'}</div>
                `;
                gridContainer.appendChild(item);
            });
        }

        // Open Upload Modal
        window.openNewModal = function() {
            urlInput.value = '';
            captionInput.value = '';
            previewPhoto.src = 'https://via.placeholder.com/400x300?text=Preview';
            uploadModal.classList.add('active');
        }

        // Update preview dynamically
        urlInput.addEventListener('input', (e) => {
            previewPhoto.src = e.target.value || 'https://via.placeholder.com/400x300?text=Preview';
        });

        // Delete Media
        window.deleteMedia = function(id) {
            if (confirm("Are you sure you want to remove this image?")) {
                mediaDB = mediaDB.filter(m => m.id !== id);
                renderMedia();
            }
        }

        // Close Modal events
        closeBtn.addEventListener('click', () => uploadModal.classList.remove('active'));
        cancelBtn.addEventListener('click', () => uploadModal.classList.remove('active'));

        // Save Media
        saveBtn.addEventListener('click', () => {
            if (!urlInput.value.trim()) {
                alert("Please provide an Image URL.");
                return;
            }

            const newId = mediaDB.length > 0 ? Math.max(...mediaDB.map(m => m.id)) + 1 : 1;
            mediaDB.push({
                id: newId,
                url: urlInput.value,
                caption: captionInput.value || 'Untitled Image'
            });
            
            renderMedia();
            uploadModal.classList.remove('active');
        });

        // Initial render
        renderMedia();
    </script>
</body>
</html>
