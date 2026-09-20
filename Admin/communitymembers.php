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
    <title>Community Members - Brgy Tabon</title>
    
    <!-- FontAwesome CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin Stylesheet -->
    <link rel="stylesheet" href="css/admin_common.css">
    <link rel="stylesheet" href="css/admin_table.css">

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
        
        .resident-photo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #e5e7eb;
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
                <li><a href="communitymembers.php" class="active"><i class="fa-solid fa-users"></i> Community Members</a></li>
                <li><a href="admindashboard.php"><i class="fa-solid fa-house-chimney"></i> Households</a></li>
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
            <h1 class="page-title">Resident Database</h1>
            <button class="btn-add"><i class="fa-solid fa-plus"></i> Add Resident</button>
        </div>

        <!-- Data Container -->
        <div class="controls-container">
            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Search resident name...">
            </div>
            <div class="filter-group">
                <select class="filter-select">
                    <option value="all">All Puroks</option>
                    <option value="1">Purok 1</option>
                    <option value="2">Purok 2</option>
                </select>
                <div class="count-badge">
                    <i class="fa-solid fa-circle" style="font-size: 8px; color: #4cd137; margin-right: 5px;"></i> 3 residents
                </div>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Age / Gender</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="resident-table-body">
                    <!-- Rows will be populated by JS -->
                </tbody>
            </table>
        </div>
        
    </main>

    <!-- Edit Modal -->
    <div class="modal-overlay" id="edit-modal">
        <div class="modal-container">
            <div class="modal-header">
                <h2>Edit Resident</h2>
                <button id="close-modal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <form id="edit-form">
                    <div class="form-group" style="text-align: center; margin-bottom: 20px;">
                        <img src="" id="preview-photo" style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%; border: 3px solid #e0e4e8; margin-bottom: 10px;">
                        <div>
                            <button type="button" id="remove-photo-btn" style="background: none; border: none; color: #dc2626; cursor: pointer; font-size: 12px;"><i class="fa-solid fa-trash"></i> Remove Photo</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" id="resident-name" required>
                    </div>
                    <div class="form-group">
                        <label>Photo URL (Optional)</label>
                        <input type="text" id="resident-photo-url" placeholder="https://...">
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
        // Mock Database for Residents
        let residentsDB = [
            { id: 1, name: 'Juan Dela Cruz', photo: 'https://via.placeholder.com/150/0000FF/808080 ?Text=J', age: '45', gender: 'Male', address: 'Street 1, Purok 1' },
            { id: 2, name: 'Maria Santos', photo: 'https://via.placeholder.com/150/FF0000/FFFFFF ?Text=M', age: '38', gender: 'Female', address: 'Street 2, Purok 2' },
            { id: 3, name: 'Pedro Penduko', photo: 'https://via.placeholder.com/150/FFFF00/000000 ?Text=P', age: '29', gender: 'Male', address: 'Street 3, Purok 3' }
        ];

        let editingResidentId = null;

        const tableBody = document.getElementById('resident-table-body');
        const editModal = document.getElementById('edit-modal');
        const closeModal = document.getElementById('close-modal');
        const cancelModal = document.getElementById('cancel-modal');
        const saveModal = document.getElementById('save-modal');
        
        const resNameInput = document.getElementById('resident-name');
        const resPhotoInput = document.getElementById('resident-photo-url');
        const previewPhoto = document.getElementById('preview-photo');
        const removePhotoBtn = document.getElementById('remove-photo-btn');

        // Render Table
        function renderTable() {
            tableBody.innerHTML = '';
            residentsDB.forEach(res => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><img src="${res.photo || 'https://via.placeholder.com/150?text=No+Photo'}" alt="Photo" class="resident-photo"></td>
                    <td style="font-weight: 600;">${res.name}</td>
                    <td>${res.age} / ${res.gender}</td>
                    <td><div class="purok-badge"><i class="fa-solid fa-location-dot"></i> ${res.address}</div></td>
                    <td>
                        <div class="actions">
                            <button class="btn-action" onclick="openEdit(${res.id})"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                            <button class="btn-action" onclick="deleteResident(${res.id})" style="color: #dc2626;"><i class="fa-solid fa-trash"></i> Remove</button>
                        </div>
                    </td>
                `;
                tableBody.appendChild(tr);
            });
        }

        // Open Edit Modal
        window.openEdit = function(id) {
            editingResidentId = id;
            const res = residentsDB.find(r => r.id === id);
            
            resNameInput.value = res.name;
            resPhotoInput.value = res.photo;
            previewPhoto.src = res.photo || 'https://via.placeholder.com/150?text=No+Photo';
            
            editModal.classList.add('active');
        }

        // Remove Photo action inside modal
        removePhotoBtn.addEventListener('click', () => {
            resPhotoInput.value = '';
            previewPhoto.src = 'https://via.placeholder.com/150?text=No+Photo';
        });

        // Update preview dynamically
        resPhotoInput.addEventListener('input', (e) => {
            previewPhoto.src = e.target.value || 'https://via.placeholder.com/150?text=No+Photo';
        });

        // Delete Resident
        window.deleteResident = function(id) {
            if (confirm("Are you sure you want to remove this resident?")) {
                residentsDB = residentsDB.filter(r => r.id !== id);
                renderTable();
            }
        }

        // Close Modal events
        closeModal.addEventListener('click', () => editModal.classList.remove('active'));
        cancelModal.addEventListener('click', () => editModal.classList.remove('active'));

        // Save Changes
        saveModal.addEventListener('click', () => {
            const res = residentsDB.find(r => r.id === editingResidentId);
            if (res) {
                res.name = resNameInput.value;
                res.photo = resPhotoInput.value;
                renderTable();
                editModal.classList.remove('active');
            }
        });

        // Initial render
        renderTable();
    </script>
</body>
</html>
