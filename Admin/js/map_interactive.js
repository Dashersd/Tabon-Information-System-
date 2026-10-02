document.addEventListener("DOMContentLoaded", function() {
    const mapContainer = document.querySelector('.map-container');
    const markerInput = document.getElementById('marker_image');
    const widthInput = document.querySelector('input[name="marker_width"]');
    const heightInput = document.querySelector('input[name="marker_height"]');
    const topInput = document.querySelector('input[name="top_pos"]');
    const leftInput = document.querySelector('input[name="left_pos"]');
    const purokInput = document.querySelector('input[name="purok_name"]');
    
    if (!mapContainer) return;

    mapContainer.style.position = 'relative';

    // Create the draggable preview icon
    let previewIcon = document.createElement('img');
    previewIcon.style.position = 'absolute';
    previewIcon.style.cursor = 'grab';
    previewIcon.style.zIndex = '100';
    previewIcon.style.display = 'none'; // hidden initially
    previewIcon.style.transform = 'translate(-50%, -50%)'; // Center it on cursor
    previewIcon.title = "Drag me to position!";
    mapContainer.appendChild(previewIcon);

    function loadPreviewImage(file, dropX, dropY) {
        if (!file || !file.type.startsWith('image/')) return;
        
        const reader = new FileReader();
        reader.onload = function(event) {
            previewIcon.src = event.target.result;
            previewIcon.style.width = (widthInput ? widthInput.value : 40) + 'px';
            previewIcon.style.height = (heightInput ? heightInput.value : 40) + 'px';
            
            let t = dropY !== undefined ? dropY : (parseFloat(topInput ? topInput.value : 50) || 50);
            let l = dropX !== undefined ? dropX : (parseFloat(leftInput ? leftInput.value : 50) || 50);
            
            previewIcon.style.top = t + '%';
            previewIcon.style.left = l + '%';
            previewIcon.style.display = 'block';
            previewIcon.style.border = '2px solid #fff';
            previewIcon.style.borderRadius = '50%';
            previewIcon.style.boxShadow = '0 0 10px rgba(0,0,0,0.5)';
            
            if (topInput) topInput.value = t;
            if (leftInput) leftInput.value = l;

            document.querySelectorAll('.map-container img.existing-marker').forEach(img => img.style.boxShadow = 'none');
            
            // Un-require marker input if we are uploading
            if (markerInput) markerInput.removeAttribute('required');
            
            // Clear the hidden ID field so it creates a NEW marker instead of updating
            let idInput = document.querySelector('input[name="household_id"]');
            if (idInput) idInput.value = '';
            
            // Reset button text
            const submitBtn = document.querySelector('.save-btn');
            if(submitBtn) submitBtn.innerHTML = '<i class="fa-solid fa-download"></i> Save to Map';
        }
        reader.readAsDataURL(file);
    }

    // 1. Handle file input change
    if (markerInput) {
        markerInput.addEventListener('change', function(e) {
            loadPreviewImage(e.target.files[0]);
        });
    }

    // 2. Handle file drag & drop from OS directly to map
    mapContainer.addEventListener('dragover', function(e) {
        e.preventDefault();
        mapContainer.style.opacity = '0.7';
    });
    mapContainer.addEventListener('dragleave', function(e) {
        e.preventDefault();
        mapContainer.style.opacity = '1';
    });
    mapContainer.addEventListener('drop', function(e) {
        e.preventDefault();
        mapContainer.style.opacity = '1';
        
        if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            const file = e.dataTransfer.files[0];
            if (markerInput) {
                // Sync file to input if possible
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                markerInput.files = dataTransfer.files;
            }
            
            const rect = mapContainer.getBoundingClientRect();
            let x = e.clientX - rect.left;
            let y = e.clientY - rect.top;
            const leftPercent = ((x / rect.width) * 100).toFixed(2);
            const topPercent = ((y / rect.height) * 100).toFixed(2);

            loadPreviewImage(file, leftPercent, topPercent);
        }
    });

    // 3. Handle dimensions change
    if (widthInput && heightInput) {
        widthInput.addEventListener('input', () => { previewIcon.style.width = widthInput.value + 'px'; });
        heightInput.addEventListener('input', () => { previewIcon.style.height = heightInput.value + 'px'; });
    }

    // 4. Drag and Drop Logic for icons on the map
    let isDragging = false;
    let dragTarget = null;

    mapContainer.addEventListener('mousedown', function(e) {
        if (e.target === previewIcon || e.target.classList.contains('existing-marker')) {
            isDragging = true;
            dragTarget = e.target;
            dragTarget.style.cursor = 'grabbing';
            e.preventDefault(); // Prevent native image drag
        }
    });

    document.addEventListener('mouseup', function() {
        if (isDragging && dragTarget) {
            isDragging = false;
            dragTarget.style.cursor = 'grab';
            dragTarget = null;
        }
    });

    mapContainer.addEventListener('mousemove', function(e) {
        if (!isDragging || !dragTarget) return;

        const rect = mapContainer.getBoundingClientRect();
        let x = e.clientX - rect.left;
        let y = e.clientY - rect.top;

        x = Math.max(0, Math.min(x, rect.width));
        y = Math.max(0, Math.min(y, rect.height));

        const leftPercent = ((x / rect.width) * 100).toFixed(2);
        const topPercent = ((y / rect.height) * 100).toFixed(2);

        dragTarget.style.left = leftPercent + '%';
        dragTarget.style.top = topPercent + '%';
        
        if (leftInput) leftInput.value = leftPercent;
        if (topInput) topInput.value = topPercent;
    });

    // 5. Also place preview icon on map click
    mapContainer.addEventListener('click', function(e) {
        if (previewIcon.style.display === 'block' && (e.target === mapContainer || e.target.classList.contains('map-preview-image'))) {
            const rect = mapContainer.getBoundingClientRect();
            let x = e.clientX - rect.left;
            let y = e.clientY - rect.top;

            const leftPercent = ((x / rect.width) * 100).toFixed(2);
            const topPercent = ((y / rect.height) * 100).toFixed(2);

            previewIcon.style.left = leftPercent + '%';
            previewIcon.style.top = topPercent + '%';
            
            if (leftInput) leftInput.value = leftPercent;
            if (topInput) topInput.value = topPercent;
        }
    });

    // 6. Fetch and display existing markers
    let jsonFile = 'data/purok1.json';
    if (purokInput) {
        if (purokInput.value === 'Purok 1') jsonFile = 'data/purok1.json';
        else if (purokInput.value === 'Purok 2') jsonFile = 'data/purok2.json';
        else if (purokInput.value === 'Purok 3') jsonFile = 'data/purok3.json';
        else if (purokInput.value === 'Legend') jsonFile = 'data/legend.json';
    }
    fetch(jsonFile + '?v=' + new Date().getTime())
        .then(response => {
            if (!response.ok) throw new Error("JSON not found");
            return response.json();
        })
        .then(data => {
            if (!Array.isArray(data)) return;
            if (purokInput) {
                const currentPurok = purokInput.value;
                data.forEach(item => {
                    if (item.purok === currentPurok) {
                        let existingMarker = document.createElement('img');
                        existingMarker.src = item.markerImage;
                        existingMarker.classList.add('existing-marker');
                        existingMarker.style.position = 'absolute';
                        existingMarker.style.width = item.markerWidth + 'px';
                        existingMarker.style.height = item.markerHeight + 'px';
                        existingMarker.style.top = item.topPos + '%';
                        existingMarker.style.left = item.leftPos + '%';
                        existingMarker.style.transform = 'translate(-50%, -50%)';
                        existingMarker.style.cursor = 'pointer';
                        existingMarker.style.zIndex = '50';
                        existingMarker.title = `Click to edit: ${item.houseNumber} - ${item.husbandName || ''} ${item.spouseName || ''}`;
                        
                        existingMarker.addEventListener('click', function() {
                            if (document.querySelector('input[name="house_number"]')) document.querySelector('input[name="house_number"]').value = item.houseNumber;
                            if (document.querySelector('input[name="husband_name"]')) document.querySelector('input[name="husband_name"]').value = item.husbandName || '';
                            if (document.querySelector('input[name="spouse_name"]')) document.querySelector('input[name="spouse_name"]').value = item.spouseName || '';
                            if (widthInput) widthInput.value = item.markerWidth;
                            if (heightInput) heightInput.value = item.markerHeight;
                            if (topInput) topInput.value = item.topPos;
                            if (leftInput) leftInput.value = item.leftPos;
                            
                            document.querySelectorAll('.map-container img.existing-marker').forEach(img => img.style.boxShadow = 'none');
                            existingMarker.style.boxShadow = '0 0 10px 3px #fca311';
                            existingMarker.style.borderRadius = '50%';
                            
                            previewIcon.style.display = 'none';

                            let idInput = document.querySelector('input[name="household_id"]');
                            if (!idInput) {
                                idInput = document.createElement('input');
                                idInput.type = 'hidden';
                                idInput.name = 'household_id';
                                document.querySelector('form').appendChild(idInput);
                            }
                            idInput.value = item.id;
                            
                            if (markerInput) markerInput.removeAttribute('required');

                            const submitBtn = document.querySelector('.save-btn');
                            if(submitBtn) submitBtn.innerHTML = '<i class="fa-solid fa-pen-to-square"></i> Update Marker';
                        });
                        
                        mapContainer.appendChild(existingMarker);
                    }
                });
            }
        })
        .catch(err => console.log('No existing markers or file missing.', err));
});
