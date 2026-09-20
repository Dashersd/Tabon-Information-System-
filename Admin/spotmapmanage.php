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
    <title>SpotMap Manage - Brgy Tabon</title>
    
    <!-- FontAwesome CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet CSS for Interactive Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    
    <!-- Admin Stylesheet -->
    <link rel="stylesheet" href="css/admin_common.css">
    <link rel="stylesheet" href="css/spotmapmanage.css">
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
                <li><a href="spotmapmanage.php" class="active"><i class="fa-solid fa-map-location-dot"></i> SpotMap Manage</a></li>
                <li><a href="communitymembers.php"><i class="fa-solid fa-users"></i> Community Members</a></li>
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
        <div class="top-bar" style="margin-bottom: 15px;">
            <h1 class="page-title">Interactive SpotMap</h1>
            <button class="btn-add" id="add-marker-btn"><i class="fa-solid fa-location-dot"></i> Add New Marker</button>
        </div>

        <div class="map-container-wrapper">
            <!-- Map Area -->
            <div id="map"></div>
            
            <!-- Editing Panel -->
            <div id="edit-panel" class="widget-panel">
                <div class="widget-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h2>Edit Household</h2>
                    <button id="close-panel" style="background: none; border: none; cursor: pointer; color: #6b7280; font-size: 16px;"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="widget-body" style="overflow-y: auto; flex: 1;">
                    <form id="household-form">
                        <div class="form-group">
                            <label>Household ID</label>
                            <input type="text" id="hh-id" readonly>
                        </div>
                        <div class="form-group">
                            <label>Head of Family</label>
                            <input type="text" id="hh-head" placeholder="e.g. Juan Dela Cruz">
                        </div>
                        <div class="form-group">
                            <label>Address / Purok</label>
                            <input type="text" id="hh-address" placeholder="e.g. Purok 1, Brgy Tabon">
                        </div>
                        
                        <h3 style="font-size: 14px; margin: 25px 0 10px 0; border-bottom: 1px solid #e0e4e8; padding-bottom: 5px; color: #2b323c;">Household Members</h3>
                        <div id="members-list" style="margin-bottom: 15px;">
                            <!-- Members appended here via JS -->
                        </div>
                        
                        <div style="margin-bottom: 25px; display: flex; gap: 10px;">
                            <input type="text" id="new-member-name" placeholder="New member name..." style="flex: 1; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px;">
                            <button type="button" id="add-member-btn" class="btn-add" style="padding: 10px 15px;"><i class="fa-solid fa-plus"></i></button>
                        </div>

                        <button type="button" id="save-btn" class="btn-add" style="width: 100%; justify-content: center;"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <script>
        // Mock Database for Households
        const householdsDB = {
            'marker-1': { id: '001', head: 'Juan Dela Cruz', address: 'Street 1, Purok 1', members: ['Maria Dela Cruz', 'Jose Dela Cruz'] },
            'marker-2': { id: '002', head: 'Pedro Penduko', address: 'Street 2, Purok 2', members: ['Ana Penduko'] },
            'marker-3': { id: '003', head: 'Cardo Dalisay', address: 'Street 3, Purok 3', members: [] }
        };

        let currentActiveMarkerId = null;

        // Initialize Map (Centered around Kawit, Cavite roughly as an example, adjust as needed)
        const map = L.map('map').setView([14.4445, 120.9022], 15);

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: 'Â© OpenStreetMap contributors'
        }).addTo(map);

        // Custom Icon for Household
        const houseIcon = L.icon({
            iconUrl: 'https://cdn-icons-png.flaticon.com/512/25/25694.png',
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            popupAnchor: [0, -32]
        });

        // Add some mock markers
        const markers = [
            { id: 'marker-1', coords: [14.4450, 120.9010] },
            { id: 'marker-2', coords: [14.4420, 120.9050] },
            { id: 'marker-3', coords: [14.4460, 120.9080] }
        ];

        markers.forEach(m => {
            const marker = L.marker(m.coords, { icon: houseIcon }).addTo(map);
            
            // Add tooltip
            marker.bindTooltip(`Household #${householdsDB[m.id].id}`);
            
            // On click, open edit panel
            marker.on('click', () => {
                openEditPanel(m.id);
            });
        });

        // UI Elements
        const editPanel = document.getElementById('edit-panel');
        const closePanelBtn = document.getElementById('close-panel');
        const addMemberBtn = document.getElementById('add-member-btn');
        const newMemberInput = document.getElementById('new-member-name');
        const membersList = document.getElementById('members-list');
        const saveBtn = document.getElementById('save-btn');

        // Close Panel
        closePanelBtn.addEventListener('click', () => {
            editPanel.classList.remove('active');
            currentActiveMarkerId = null;
        });

        // Open Edit Panel and populate data
        function openEditPanel(markerId) {
            currentActiveMarkerId = markerId;
            const data = householdsDB[markerId];
            
            document.getElementById('hh-id').value = data.id;
            document.getElementById('hh-head').value = data.head;
            document.getElementById('hh-address').value = data.address;
            
            renderMembers();
            
            editPanel.classList.add('active');
        }

        // Render Members List
        function renderMembers() {
            membersList.innerHTML = '';
            const members = householdsDB[currentActiveMarkerId].members;
            
            if (members.length === 0) {
                membersList.innerHTML = '<p style="font-size: 13px; color: #9ca3af; font-style: italic;">No members added yet.</p>';
                return;
            }

            members.forEach((member, index) => {
                const div = document.createElement('div');
                div.className = 'member-item';
                div.innerHTML = `
                    <span><i class="fa-regular fa-user" style="margin-right: 8px; color: #9ca3af;"></i> ${member}</span>
                    <button type="button" onclick="removeMember(${index})"><i class="fa-solid fa-trash-can"></i></button>
                `;
                membersList.appendChild(div);
            });
        }

        // Add Member
        addMemberBtn.addEventListener('click', () => {
            const name = newMemberInput.value.trim();
            if (name && currentActiveMarkerId) {
                householdsDB[currentActiveMarkerId].members.push(name);
                newMemberInput.value = '';
                renderMembers();
            }
        });

        // Remove Member
        window.removeMember = function(index) {
            if (currentActiveMarkerId) {
                householdsDB[currentActiveMarkerId].members.splice(index, 1);
                renderMembers();
            }
        };

        // Save Button Simulation
        saveBtn.addEventListener('click', () => {
            if (currentActiveMarkerId) {
                householdsDB[currentActiveMarkerId].head = document.getElementById('hh-head').value;
                householdsDB[currentActiveMarkerId].address = document.getElementById('hh-address').value;
                
                alert('Household data saved successfully!');
                editPanel.classList.remove('active');
            }
        });

        // Optional: Add new marker feature on map click
        let isAddingMarker = false;
        document.getElementById('add-marker-btn').addEventListener('click', function() {
            isAddingMarker = !isAddingMarker;
            if (isAddingMarker) {
                this.style.backgroundColor = '#1e4f34';
                this.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> Click on map to add';
                document.getElementById('map').style.cursor = 'crosshair';
            } else {
                this.style.backgroundColor = '';
                this.innerHTML = '<i class="fa-solid fa-location-dot"></i> Add New Marker';
                document.getElementById('map').style.cursor = '';
            }
        });

        map.on('click', function(e) {
            if (isAddingMarker) {
                const newId = 'marker-' + (Object.keys(householdsDB).length + 1);
                const newHhId = String(Object.keys(householdsDB).length + 1).padStart(3, '0');
                
                // Add to DB
                householdsDB[newId] = { id: newHhId, head: '', address: '', members: [] };
                
                // Create Marker
                const marker = L.marker(e.latlng, { icon: houseIcon }).addTo(map);
                marker.bindTooltip(`Household #${newHhId}`);
                
                marker.on('click', () => {
                    openEditPanel(newId);
                });
                
                // Reset state
                isAddingMarker = false;
                const addBtn = document.getElementById('add-marker-btn');
                addBtn.style.backgroundColor = '';
                addBtn.innerHTML = '<i class="fa-solid fa-location-dot"></i> Add New Marker';
                document.getElementById('map').style.cursor = '';
                
                // Open panel immediately for the new marker
                openEditPanel(newId);
            }
        });
    </script>
</body>
</html>
