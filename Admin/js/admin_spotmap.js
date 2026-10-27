/**
 * Admin Spot Map & Household Interactive Engine
 * Project: Barangay Tabon Information System
 * Features:
 *  - Drag & drop marker positioning
 *  - Real-time preview & dimension controls
 *  - Dynamic Children / Dependents repeater
 *  - Interactive pin hover/click tooltip cards with children badges
 *  - Bi-directional edit sync
 */

document.addEventListener('DOMContentLoaded', function () {
    const mapContainer = document.getElementById('map-container') || document.querySelector('.map-container');
    const markerInput = document.getElementById('marker_image') || document.querySelector('input[name="marker_image"]');
    const houseInput = document.getElementById('house_image') || document.querySelector('input[name="house_image"]');
    const topInput = document.querySelector('input[name="top_position"]') || document.querySelector('input[name="top_pos"]');
    const leftInput = document.querySelector('input[name="left_position"]') || document.querySelector('input[name="left_pos"]');
    const widthInput = document.querySelector('input[name="marker_width"]');
    const heightInput = document.querySelector('input[name="marker_height"]');
    const childrenContainer = document.getElementById('children-container');
    const btnAddChild = document.getElementById('btn-add-child');
    const form = document.querySelector('form');
    const householdIdInput = document.getElementById('household_id') || document.querySelector('input[name="household_id"]');
    const saveBtn = document.getElementById('btn-save-record') || document.querySelector('.btn-save') || document.querySelector('.save-btn');

    let previewMarker = null;
    let popover = null;

    // =========================================================================
    // 1. DYNAMIC CHILDREN REPEATER
    // =========================================================================
    function addChildRow(value = '') {
        if (!childrenContainer) return;
        const row = document.createElement('div');
        row.className = 'child-row';
        row.style.cssText = 'display: flex; gap: 8px; align-items: center; margin-bottom: 6px; animation: fadeIn 0.2s ease;';

        const input = document.createElement('input');
        input.type = 'text';
        input.name = 'children[]';
        input.className = 'form-control child-input';
        input.placeholder = 'Child / Dependent full name';
        input.value = value;
        input.style.flex = '1';

        // Add next on Enter
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addChildRow();
            }
        });

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn-remove-child';
        removeBtn.innerHTML = '&times;';
        removeBtn.title = 'Remove dependent';
        removeBtn.style.cssText = 'background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; border-radius: 6px; width: 34px; height: 34px; font-size: 1.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;';

        removeBtn.addEventListener('mouseenter', () => removeBtn.style.background = '#fecaca');
        removeBtn.addEventListener('mouseleave', () => removeBtn.style.background = '#fee2e2');
        removeBtn.addEventListener('click', function () {
            row.remove();
        });

        row.appendChild(input);
        row.appendChild(removeBtn);
        childrenContainer.appendChild(row);

        input.focus();
    }

    if (btnAddChild) {
        btnAddChild.addEventListener('click', function (e) {
            e.preventDefault();
            addChildRow();
        });
    }

    // =========================================================================
    // 2. LIVE MARKER PREVIEW & DRAGGABLE ENGINE
    // =========================================================================
    function ensurePreviewMarker() {
        if (!previewMarker && mapContainer) {
            previewMarker = document.createElement('img');
            previewMarker.id = 'live-preview-marker';
            previewMarker.style.position = 'absolute';
            previewMarker.style.cursor = 'grab';
            previewMarker.style.transform = 'translate(-50%, -100%)';
            previewMarker.style.zIndex = '1000';
            previewMarker.style.filter = 'drop-shadow(0 4px 8px rgba(0,0,0,0.4))';
            previewMarker.style.border = 'none';
            previewMarker.style.background = 'transparent';
            previewMarker.style.padding = '0';
            previewMarker.style.objectFit = 'contain';
            previewMarker.title = 'Drag me to set location';

            mapContainer.appendChild(previewMarker);
            makeDraggable(previewMarker);
        }
        return previewMarker;
    }

    function updatePreviewPosition(topVal, leftVal) {
        if (!previewMarker) return;
        previewMarker.style.top = topVal + '%';
        previewMarker.style.left = leftVal + '%';
    }

    function updatePreviewSize() {
        if (!previewMarker) return;
        const w = widthInput ? widthInput.value : 40;
        const h = heightInput ? heightInput.value : 40;
        previewMarker.style.width = w + 'px';
        previewMarker.style.height = h + 'px';
    }

    // Load file from input
    if (markerInput) {
        markerInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function (event) {
                    const marker = ensurePreviewMarker();
                    marker.src = event.target.result;
                    marker.style.display = 'block';
                    updatePreviewSize();
                    const top = topInput ? parseFloat(topInput.value) || 50 : 50;
                    const left = leftInput ? parseFloat(leftInput.value) || 50 : 50;
                    updatePreviewPosition(top, left);
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Direct drag and drop of marker graphic onto map canvas
    if (mapContainer) {
        mapContainer.addEventListener('dragover', function (e) {
            e.preventDefault();
            mapContainer.style.filter = 'brightness(0.92)';
        });

        mapContainer.addEventListener('dragleave', function (e) {
            e.preventDefault();
            mapContainer.style.filter = 'none';
        });

        mapContainer.addEventListener('drop', function (e) {
            e.preventDefault();
            mapContainer.style.filter = 'none';

            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                const file = e.dataTransfer.files[0];
                if (file.type.startsWith('image/')) {
                    if (markerInput) {
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        markerInput.files = dt.files;
                    }

                    const rect = mapContainer.getBoundingClientRect();
                    const leftP = Math.max(0, Math.min(100, (((e.clientX - rect.left) / rect.width) * 100))).toFixed(2);
                    const topP = Math.max(0, Math.min(100, (((e.clientY - rect.top) / rect.height) * 100))).toFixed(2);

                    if (topInput) topInput.value = topP;
                    if (leftInput) leftInput.value = leftP;

                    const reader = new FileReader();
                    reader.onload = function (ev) {
                        const marker = ensurePreviewMarker();
                        marker.src = ev.target.result;
                        marker.style.display = 'block';
                        updatePreviewSize();
                        updatePreviewPosition(topP, leftP);
                    };
                    reader.readAsDataURL(file);
                }
            }
        });
    }

    if (widthInput) widthInput.addEventListener('input', updatePreviewSize);
    if (heightInput) heightInput.addEventListener('input', updatePreviewSize);
    if (topInput) topInput.addEventListener('input', () => updatePreviewPosition(topInput.value, leftInput.value));
    if (leftInput) leftInput.addEventListener('input', () => updatePreviewPosition(topInput.value, leftInput.value));

    // Make Marker Draggable
    function makeDraggable(el) {
        let isDragging = false;

        el.addEventListener('mousedown', function (e) {
            e.preventDefault();
            e.stopPropagation();
            isDragging = true;
            el.style.cursor = 'grabbing';
        });

        window.addEventListener('mousemove', function (e) {
            if (!isDragging || !mapContainer) return;
            const rect = mapContainer.getBoundingClientRect();
            let x = e.clientX - rect.left;
            let y = e.clientY - rect.top;

            let leftPercent = (x / rect.width) * 100;
            let topPercent = (y / rect.height) * 100;

            // Constrain
            leftPercent = Math.max(0, Math.min(100, leftPercent));
            topPercent = Math.max(0, Math.min(100, topPercent));

            el.style.left = leftPercent.toFixed(2) + '%';
            el.style.top = topPercent.toFixed(2) + '%';

            if (topInput) topInput.value = topPercent.toFixed(2);
            if (leftInput) leftInput.value = leftPercent.toFixed(2);
        });

        window.addEventListener('mouseup', function () {
            if (isDragging) {
                isDragging = false;
                el.style.cursor = 'grab';
            }
        });
    }

    // =========================================================================
    // 3. INTERACTIVE PIN HOVER / CLICK POPOVER (WITH CHILDREN INTEGRATION)
    // =========================================================================
    let activeMarkerEl = null;

    function createPopover() {
        if (popover) return popover;
        popover = document.createElement('div');
        popover.className = 'spotmap-pin-popover';
        popover.style.cssText = `
            position: absolute;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
            padding: 16px;
            width: 280px;
            z-index: 2000;
            display: none;
            font-family: 'Montserrat', sans-serif;
            border-top: 4px solid #223c3b;
            pointer-events: auto;
            transition: opacity 0.2s ease;
        `;
        document.body.appendChild(popover);

        // Prevent clicks inside popover from propagating to document
        popover.addEventListener('click', (e) => e.stopPropagation());

        return popover;
    }

    // Close popover when clicking anywhere outside
    document.addEventListener('click', function (e) {
        if (popover && popover.style.display === 'block') {
            if (!popover.contains(e.target) && !e.target.classList.contains('saved-marker-pin')) {
                popover.style.display = 'none';
                activeMarkerEl = null;
            }
        }
    });

    function showPinDetails(markerEl) {
        const pop = createPopover();

        // Toggle off if clicking the already open marker
        if (activeMarkerEl === markerEl && pop.style.display === 'block') {
            pop.style.display = 'none';
            activeMarkerEl = null;
            return;
        }

        activeMarkerEl = markerEl;
        const houseNo = markerEl.getAttribute('data-house-number') || 'N/A';
        const husband = markerEl.getAttribute('data-husband') || 'N/A';
        const spouse = markerEl.getAttribute('data-spouse') || 'N/A';
        const houseImg = markerEl.getAttribute('data-house-image') || '';
        const rawChildren = markerEl.getAttribute('data-children') || '[]';
        const markerId = markerEl.getAttribute('data-id');

        let childrenArr = [];
        try {
            childrenArr = JSON.parse(rawChildren);
            if (!Array.isArray(childrenArr)) childrenArr = [];
        } catch (e) {
            childrenArr = [];
        }

        let childrenHtml = '';
        if (childrenArr.length > 0) {
            childrenHtml = `
                <div style="margin-top: 10px; padding-top: 8px; border-top: 1px dashed #cbd5e1;">
                    <div style="font-size: 0.82rem; font-weight: 700; color: #475569; margin-bottom: 6px;">
                        Children / Dependents
                    </div>
                    <ul style="margin: 0; padding-left: 18px; list-style-type: disc; display: flex; flex-direction: column; gap: 4px; max-height: 90px; overflow-y: auto;">
                        ${childrenArr.map(c => `
                            <li style="color: #0284c7; font-size: 0.85rem;">
                                <span style="color: #1e293b; font-size: 0.82rem; font-weight: 600;">${c}</span>
                            </li>
                        `).join('')}
                    </ul>
                </div>
            `;
        } else {
            childrenHtml = `
                <div style="margin-top: 8px; font-size: 0.75rem; color: #94a3b8; font-style: italic;">
                    <i class="fa-solid fa-circle-info"></i> No children / dependents registered
                </div>
            `;
        }

        pop.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                <div>
                    <span style="font-size: 0.7rem; font-weight: 700; background: #223c3b; color: #fff; padding: 2px 7px; border-radius: 4px;">HOUSE #${houseNo}</span>
                </div>
                <button type="button" class="close-pop-btn" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #94a3b8; line-height: 1;">&times;</button>
            </div>
            ${houseImg ? `<img src="${houseImg}" style="width: 100%; height: 110px; object-fit: cover; border-radius: 6px; margin-bottom: 8px; border: 1px solid #e2e8f0;">` : ''}
            <div style="font-size: 0.85rem; color: #1e293b; line-height: 1.4;">
                <div><strong>Head:</strong> ${husband}</div>
                <div><strong>Spouse:</strong> ${spouse}</div>
            </div>
            ${childrenHtml}
        `;

        pop.querySelector('.close-pop-btn').onclick = () => {
            pop.style.display = 'none';
            activeMarkerEl = null;
        };

        const rect = markerEl.getBoundingClientRect();
        pop.style.display = 'block';
        pop.style.left = (window.scrollX + rect.left + rect.width / 2 - 140) + 'px';
        pop.style.top = (window.scrollY + rect.top - pop.offsetHeight - 10) + 'px';
    }

    // Bind saved markers strictly on click and lock them from being dragged
    const savedMarkers = document.querySelectorAll('.saved-marker-pin');
    savedMarkers.forEach(m => {
        m.setAttribute('draggable', 'false');
        m.addEventListener('dragstart', (e) => e.preventDefault());
        m.addEventListener('mousedown', (e) => {
            // Prevent dragging or moving the saved marker
            e.stopPropagation();
        });
        m.addEventListener('click', function (e) {
            e.stopPropagation();
            showPinDetails(m);
        });
    });

    // Also prevent dragging on base map
    const baseMap = document.getElementById('base-map-img');
    if (baseMap) {
        baseMap.setAttribute('draggable', 'false');
        baseMap.addEventListener('dragstart', (e) => e.preventDefault());
    }

    // =========================================================================
    // 4. BI-DIRECTIONAL EDIT SYNC
    // =========================================================================
    function loadMarkerForEdit(markerEl) {
        const id = markerEl.getAttribute('data-id');
        const houseNo = markerEl.getAttribute('data-house-number') || '';
        const husband = markerEl.getAttribute('data-husband') || '';
        const spouse = markerEl.getAttribute('data-spouse') || '';
        const top = markerEl.getAttribute('data-top') || '50.00';
        const left = markerEl.getAttribute('data-left') || '50.00';
        const width = markerEl.getAttribute('data-width') || '40';
        const height = markerEl.getAttribute('data-height') || '40';
        const markerSrc = markerEl.getAttribute('src');
        const rawChildren = markerEl.getAttribute('data-children') || '[]';

        if (householdIdInput) householdIdInput.value = id;
        const houseNoInput = document.querySelector('input[name="house_number"]');
        const husbandInput = document.querySelector('input[name="husband_name"]');
        const spouseInput = document.querySelector('input[name="spouse_name"]');

        if (houseNoInput) houseNoInput.value = houseNo;
        if (husbandInput) husbandInput.value = husband;
        if (spouseInput) spouseInput.value = spouse;
        if (topInput) topInput.value = top;
        if (leftInput) leftInput.value = left;
        if (widthInput) widthInput.value = width;
        if (heightInput) heightInput.value = height;

        // Reconstruct Children Repeater
        if (childrenContainer) {
            childrenContainer.innerHTML = '';
            let childrenArr = [];
            try {
                childrenArr = JSON.parse(rawChildren);
            } catch (e) {
                childrenArr = [];
            }
            if (Array.isArray(childrenArr) && childrenArr.length > 0) {
                childrenArr.forEach(c => addChildRow(c));
            }
        }

        // Setup live preview
        const marker = ensurePreviewMarker();
        marker.src = markerSrc;
        marker.style.display = 'block';
        updatePreviewSize();
        updatePreviewPosition(top, left);

        // Images no longer required during update
        if (markerInput) markerInput.removeAttribute('required');
        if (houseInput) houseInput.removeAttribute('required');

        // Update button appearance
        if (saveBtn) {
            saveBtn.innerHTML = '<i class="fa-solid fa-pen-to-square"></i> Update Household Pin';
            saveBtn.style.background = '#2c6e49';
        }

        // Add Cancel Edit button if not present
        let cancelBtn = document.getElementById('btn-cancel-edit');
        if (!cancelBtn && saveBtn && saveBtn.parentNode) {
            cancelBtn = document.createElement('button');
            cancelBtn.type = 'button';
            cancelBtn.id = 'btn-cancel-edit';
            cancelBtn.style.cssText = 'width: 100%; background: #64748b; color: #fff; padding: 10px; font-weight: 600; font-size: 0.95rem; border: none; border-radius: 8px; cursor: pointer; margin-top: 8px; transition: background 0.2s;';
            cancelBtn.innerHTML = '<i class="fa-solid fa-xmark"></i> Cancel Edit Mode';
            cancelBtn.onclick = resetFormToNew;
            saveBtn.parentNode.insertBefore(cancelBtn, saveBtn.nextSibling);
        }

        // Scroll smoothly to form
        if (form) form.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function resetFormToNew() {
        if (householdIdInput) householdIdInput.value = '';
        if (form) form.reset();
        if (childrenContainer) childrenContainer.innerHTML = '';
        if (previewMarker) previewMarker.style.display = 'none';

        if (markerInput) markerInput.setAttribute('required', 'required');
        if (houseInput) houseInput.setAttribute('required', 'required');

        if (saveBtn) {
            saveBtn.innerHTML = '<i class="fa-solid fa-map-pin"></i> Save to Map';
            saveBtn.style.background = '#2c6e49';
        }

        const cancelBtn = document.getElementById('btn-cancel-edit');
        if (cancelBtn) cancelBtn.remove();
    }

    // Check if URL has ?edit_id=
    const urlParams = new URLSearchParams(window.location.search);
    const editId = urlParams.get('edit_id');
    if (editId) {
        const targetMarker = document.querySelector(`.saved-marker-pin[data-id="${editId}"]`);
        if (targetMarker) {
            loadMarkerForEdit(targetMarker);
        }
    }
});
