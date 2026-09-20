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
    
    <!-- Admin Stylesheet -->
    <link rel="stylesheet" href="css/admin_common.css">
    <link rel="stylesheet" href="css/spotmapmanage.css">
    <style>
        .members-section {
            background: #f9fafb;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            margin-bottom: 15px;
        }
        .members-list {
            margin-bottom: 10px;
            max-height: 100px;
            overflow-y: auto;
        }
        .member-item {
            font-size: 12px;
            display: flex;
            justify-content: space-between;
            padding: 5px;
            border-bottom: 1px dashed #d1d5db;
        }
        .member-item:last-child {
            border-bottom: none;
        }
        .btn-small-add {
            background: #2c6e49;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            width: 100%;
        }
        /* Saved Markers */
        .saved-marker {
            position: absolute;
            transform: translate(-50%, -50%);
            cursor: pointer;
            z-index: 5;
            transition: transform 0.2s;
        }
        .saved-marker:hover {
            transform: translate(-50%, -50%) scale(1.1);
            z-index: 20;
        }
        .saved-marker img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.4));
        }
        /* Popup info box */
        .map-popup {
            display: none;
            position: absolute;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            width: 250px;
            z-index: 100;
            padding: 15px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .map-popup h3 {
            margin: 0 0 5px 0;
            font-size: 16px;
            color: #2c6e49;
        }
        .map-popup p {
            margin: 0 0 10px 0;
            font-size: 13px;
            color: #4b5563;
        }
        .map-popup .popup-members {
            background: #f4f6f8;
            padding: 8px;
            border-radius: 4px;
            font-size: 12px;
            max-height: 100px;
            overflow-y: auto;
        }
        .close-popup {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
            color: #9ca3af;
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
                <li><a href="spotmapmanage.php" class="active"><i class="fa-solid fa-map-location-dot"></i> SpotMap Manage</a></li>


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
        <div class="top-bar" style="margin-bottom: 20px;">
            <div>
                <h1 class="page-title">Add House to Map</h1>
                <p class="page-subtitle" style="color: #6b7280; font-size: 14px; margin-top: 5px;">Upload a marker, add members, and drag the icon to save to the map.</p>
            </div>
        </div>

        <div class="spotmap-layout">
            
            <!-- Map Preview (Now on Top) -->
            <div class="spotmap-preview-section">
                <div class="custom-map-container" id="map-container">
                    <!-- Placeholder aerial map image -->
                    <img src="https://via.placeholder.com/800x600?text=Aerial+Map+Placeholder" alt="Map Background" class="map-bg-image" id="map-bg">
                    
                    <!-- Draggable Marker -->
                    <div id="draggable-marker" class="map-marker" style="top: 50%; left: 50%; width: 40px; height: 40px;">
                        <img src="https://cdn-icons-png.flaticon.com/512/25/25694.png" alt="Marker Icon" id="marker-icon-preview">
                    </div>
                    
                    <!-- Popup (Hidden by default) -->
                    <div id="map-popup" class="map-popup">
                        <button class="close-popup" onclick="closePopup()"><i class="fa-solid fa-xmark"></i></button>
                        <h3 id="popup-house-num">House #001</h3>
                        <p id="popup-address">Address here</p>

                    </div>
                    
                    <!-- Saved Markers Container -->
                    <div id="saved-markers-layer"></div>
                </div>
            </div>

            <!-- Form Section (Now Below) -->
            <div class="spotmap-form-section widget-panel">
                <form id="marker-form" onsubmit="return false;">
                    
                    <div class="form-group">
                        <label>House Number</label>
                        <input type="text" id="house-number" placeholder="e.g. 123">
                    </div>

                    <div class="form-group">
                        <label>Street / Address</label>
                        <input type="text" id="street-address" placeholder="e.g. Purok 1, Sitio Mangga">
                    </div>
                    


                    <div class="form-group">
                        <label>Marker Image (icon shown on map)</label>
                        <input type="file" id="marker-image-upload" accept="image/*">
                    </div>

                    <div class="form-row">
                        <div class="form-group half">
                            <label>Marker Width (px)</label>
                            <input type="number" id="marker-width" value="40" min="10" max="200">
                        </div>
                        <div class="form-group half">
                            <label>Marker Height (px)</label>
                            <input type="number" id="marker-height" value="40" min="10" max="200">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group half">
                            <label>Top position (%)</label>
                            <input type="number" id="pos-top" step="0.01" value="50.00" min="0" max="100">
                        </div>
                        <div class="form-group half">
                            <label>Left position (%)</label>
                            <input type="number" id="pos-left" step="0.01" value="50.00" min="0" max="100">
                        </div>
                    </div>

                    <button type="button" class="btn-primary" id="btn-save-marker" style="width: 100%; margin-top: 15px;"><i class="fa-solid fa-floppy-disk"></i> Save to Map</button>

                </form>
            </div>

        </div>
    </main>

    <script>
        // Data State
        let currentMembers = [];
        let savedHouses = [];

        // DOM Elements
        const markerUpload = document.getElementById('marker-image-upload');
        const markerPreview = document.getElementById('marker-icon-preview');
        const markerDraggable = document.getElementById('draggable-marker');
        const mapContainer = document.getElementById('map-container');
        
        const inputTop = document.getElementById('pos-top');
        const inputLeft = document.getElementById('pos-left');
        const inputWidth = document.getElementById('marker-width');
        const inputHeight = document.getElementById('marker-height');
        


        // Handle custom marker image upload
        markerUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    markerPreview.src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // Handle Width/Height Inputs
        inputWidth.addEventListener('input', function() {
            markerDraggable.style.width = this.value + 'px';
        });
        
        inputHeight.addEventListener('input', function() {
            markerDraggable.style.height = this.value + 'px';
        });

        // Handle Manual Top/Left Input Changes
        inputTop.addEventListener('input', function() {
            let val = parseFloat(this.value);
            if(val >= 0 && val <= 100) {
                markerDraggable.style.top = val + '%';
            }
        });

        inputLeft.addEventListener('input', function() {
            let val = parseFloat(this.value);
            if(val >= 0 && val <= 100) {
                markerDraggable.style.left = val + '%';
            }
        });

        // Draggable Logic
        let isDragging = false;

        markerDraggable.addEventListener('mousedown', function(e) {
            isDragging = true;
            e.preventDefault();
        });

        document.addEventListener('mouseup', function() {
            isDragging = false;
        });

        document.addEventListener('mousemove', function(e) {
            if (!isDragging) return;

            const containerRect = mapContainer.getBoundingClientRect();
            let x = e.clientX - containerRect.left;
            let y = e.clientY - containerRect.top;

            x = Math.max(0, Math.min(x, containerRect.width));
            y = Math.max(0, Math.min(y, containerRect.height));

            const leftPercent = (x / containerRect.width) * 100;
            const topPercent = (y / containerRect.height) * 100;

            markerDraggable.style.left = leftPercent + '%';
            markerDraggable.style.top = topPercent + '%';

            inputLeft.value = leftPercent.toFixed(2);
            inputTop.value = topPercent.toFixed(2);
        });

        // Save Button Logic
        document.getElementById('btn-save-marker').addEventListener('click', function() {
            const hnum = document.getElementById('house-number').value;
            const addr = document.getElementById('street-address').value;
            const t = inputTop.value;
            const l = inputLeft.value;
            const w = inputWidth.value;
            const h = inputHeight.value;
            const src = markerPreview.src;

            if(!hnum) {
                alert("Please provide a House Number.");
                return;
            }

            const newHouse = {
                id: Date.now(),
                houseNum: hnum,
                address: addr,
                top: t,
                left: l,
                width: w,
                height: h,
                imageSrc: src
            };

            savedHouses.push(newHouse);
            
            // Add static marker to map
            renderSavedMarker(newHouse);

            // Reset form for next house
            document.getElementById('house-number').value = '';
            
            alert('House saved to map! Click its marker to view details.');
        });

        function renderSavedMarker(house) {
            const m = document.createElement('div');
            m.className = 'saved-marker';
            m.style.top = house.top + '%';
            m.style.left = house.left + '%';
            m.style.width = house.width + 'px';
            m.style.height = house.height + 'px';
            m.innerHTML = `<img src="${house.imageSrc}" alt="Saved Marker">`;
            
            m.onclick = function() {
                openPopup(house);
            };

            savedMarkersLayer.appendChild(m);
        }

        // Popup Logic
        function openPopup(house) {
            document.getElementById('popup-house-num').innerText = "House #" + house.houseNum;
            document.getElementById('popup-address').innerText = house.address || "No address specified";

            // Position popup near marker
            mapPopup.style.top = `calc(${house.top}% - 20px)`;
            mapPopup.style.left = `calc(${house.left}% + ${parseInt(house.width)/2 + 10}px)`;
            
            // Make sure popup doesn't overflow right side
            if(parseFloat(house.left) > 70) {
                mapPopup.style.left = `calc(${house.left}% - 260px)`;
            }

            mapPopup.style.display = 'block';
        }

        function closePopup() {
            mapPopup.style.display = 'none';
        }
    </script>
</body>
</html>
