/**
 * Admin Spot Map & Household Interactive Engine
 * Project: Barangay Tabon Information System
 * Features:
 *  - Drag & drop marker positioning
 *  - Real-time preview & dimension controls
 *  - Dynamic Children / Dependents repeater (Add, Remove, Clear All)
 *  - Interactive pin hover/click tooltip cards with Edit & Delete actions
 *  - Full form editing, clear/reset controls, and direct deletion from map
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
    const targetFileInput = document.querySelector('input[name="target_file"]');
    function detectPurokKey() {
        const path = decodeURIComponent(window.location.pathname.toLowerCase());
        if (path.includes('purok 2') || path.includes('purok2')) return 'purok2';
        if (path.includes('purok 3') || path.includes('purok3')) return 'purok3';
        if (path.includes('purok 1') || path.includes('purok1')) return 'purok1';
        return targetFileInput ? targetFileInput.value : 'purok1';
    }
    const targetFile = detectPurokKey();

    function getCurrentPageClean() {
        const raw = window.location.pathname.split('/').pop() || 'Purok 1.php';
        return decodeURIComponent(raw);
    }

    // Robust native POST deletion helper: posts directly to current page (self-handling)
    function executeHouseholdDeletion(deleteId, houseNo) {
        if (!deleteId && !houseNo) {
            alert('Error: Missing household details for deletion.');
            return;
        }

        const confirmMsg = houseNo ? `Are you sure you want to permanently delete House #${houseNo} from the map?` : 'Are you sure you want to permanently delete this household?';
        if (!confirm(confirmMsg)) {
            return;
        }

        // Native form POST directly to current page (self-handling, 0% failure on InfinityFree)
        const postForm = document.createElement('form');
        postForm.method = 'POST';
        postForm.action = '';
        postForm.style.display = 'none';

        const addHidden = (name, val) => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = name;
            inp.value = val;
            postForm.appendChild(inp);
        };

        addHidden('action', 'delete');
        if (deleteId) addHidden('delete_id', deleteId);
        if (houseNo)  addHidden('house_number', houseNo);

        document.body.appendChild(postForm);
        postForm.submit();
    }

    function showNotificationToast(msg, type = 'success') {
        let toast = document.getElementById('spotmap-toast-msg');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'spotmap-toast-msg';
            toast.style.cssText = `
                position: fixed;
                top: 24px;
                right: 24px;
                z-index: 99999;
                background: #10b981;
                color: #ffffff;
                padding: 14px 22px;
                border-radius: 8px;
                font-family: 'Montserrat', sans-serif;
                font-size: 0.92rem;
                font-weight: 600;
                box-shadow: 0 10px 25px rgba(0,0,0,0.25);
                display: flex;
                align-items: center;
                gap: 10px;
                transition: opacity 0.3s ease;
            `;
            document.body.appendChild(toast);
        }
        if (type === 'error') {
            toast.style.background = '#ef4444';
            toast.innerHTML = `<i class="fa-solid fa-circle-exclamation"></i> <span>${msg}</span>`;
        } else {
            toast.style.background = '#10b981';
            toast.innerHTML = `<i class="fa-solid fa-circle-check"></i> <span>${msg}</span>`;
        }
        toast.style.display = 'flex';
        toast.style.opacity = '1';
        clearTimeout(toast._timer);
        toast._timer = setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => { toast.style.display = 'none'; }, 300);
        }, 4000);
    }

    let previewMarker = null;
    let popover = null;

    // =========================================================================
    // 1. DYNAMIC CHILDREN REPEATER (ADD, REMOVE, CLEAR ALL)
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
        removeBtn.title = 'Remove this dependent';
        removeBtn.style.cssText = 'background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; border-radius: 6px; width: 34px; height: 34px; font-size: 1.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; flex-shrink: 0;';

        removeBtn.addEventListener('mouseenter', () => removeBtn.style.background = '#fecaca');
        removeBtn.addEventListener('mouseleave', () => removeBtn.style.background = '#fee2e2');
        removeBtn.addEventListener('click', function () {
            row.remove();
        });

        row.appendChild(input);
        row.appendChild(removeBtn);
        childrenContainer.appendChild(row);

        if (!value) input.focus();
    }

    if (btnAddChild) {
        btnAddChild.addEventListener('click', function (e) {
            e.preventDefault();
            addChildRow();
        });

        // Add "Clear All Dependents" button next to "+ Add Child"
        if (btnAddChild.parentNode && !document.getElementById('btn-clear-children')) {
            const btnClearChildren = document.createElement('button');
            btnClearChildren.type = 'button';
            btnClearChildren.id = 'btn-clear-children';
            btnClearChildren.style.cssText = 'background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 12px; font-size: 0.82rem; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; margin-left: 6px; transition: all 0.2s;';
            btnClearChildren.innerHTML = '<i class="fa-solid fa-trash-can"></i> Clear All';
            btnClearChildren.title = 'Remove all children inputs';
            btnClearChildren.onclick = function(e) {
                e.preventDefault();
                if (childrenContainer) childrenContainer.innerHTML = '';
            };
            btnAddChild.parentNode.appendChild(btnClearChildren);
        }
    }

    // =========================================================================
    // 2. LIVE MARKER PREVIEW, IMAGE VALIDATION & DRAGGABLE ENGINE
    // =========================================================================
    const MAX_IMAGE_SIZE = 5 * 1024 * 1024; // 5 MB
    const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    function validateImageFile(file, errorElId) {
        const errorEl = document.getElementById(errorElId);
        if (!file) {
            if (errorEl) errorEl.style.display = 'none';
            return true;
        }

        const ext = file.name.split('.').pop().toLowerCase();
        const validExt = ['jpg', 'jpeg', 'png', 'webp'].includes(ext);
        const validType = ALLOWED_IMAGE_TYPES.includes(file.type) || validExt;

        if (!validType) {
            const msg = 'Invalid file format. Please upload a JPG, PNG, or WEBP image.';
            if (errorEl) {
                errorEl.textContent = msg;
                errorEl.style.display = 'block';
            }
            showNotificationToast(msg, 'error');
            return false;
        }

        if (file.size > MAX_IMAGE_SIZE) {
            const sizeMb = (file.size / (1024 * 1024)).toFixed(1);
            const msg = `File size is ${sizeMb} MB. Maximum allowed size is 5 MB.`;
            if (errorEl) {
                errorEl.textContent = msg;
                errorEl.style.display = 'block';
            }
            showNotificationToast(msg, 'error');
            return false;
        }

        if (errorEl) {
            errorEl.style.display = 'none';
            errorEl.textContent = '';
        }
        return true;
    }

    function formatFileSize(bytes) {
        if (!bytes || bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // House Image (Location Photo) Validation & Live Thumbnail Preview
    if (houseInput) {
        houseInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            const previewBox = document.getElementById('house_image_preview_box');
            const previewThumb = document.getElementById('house_image_preview_thumb');
            const previewName = document.getElementById('house_image_preview_name');
            const previewSize = document.getElementById('house_image_preview_size');

            if (!file) {
                if (previewBox) previewBox.style.display = 'none';
                return;
            }

            if (!validateImageFile(file, 'house_image_error_msg')) {
                houseInput.value = '';
                if (previewBox) previewBox.style.display = 'none';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (ev) {
                if (previewThumb) previewThumb.src = ev.target.result;
                if (previewName) previewName.textContent = file.name;
                if (previewSize) previewSize.textContent = formatFileSize(file.size);
                if (previewBox) previewBox.style.display = 'flex';
            };
            reader.readAsDataURL(file);
        });

        const btnClearHouse = document.getElementById('btn_clear_house_image');
        if (btnClearHouse) {
            btnClearHouse.addEventListener('click', function () {
                houseInput.value = '';
                const previewBox = document.getElementById('house_image_preview_box');
                if (previewBox) previewBox.style.display = 'none';
                const errorMsg = document.getElementById('house_image_error_msg');
                if (errorMsg) errorMsg.style.display = 'none';
            });
        }
    }

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

    // Load marker icon file from input
    if (markerInput) {
        markerInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            const previewBox = document.getElementById('marker_image_preview_box');
            const previewThumb = document.getElementById('marker_image_preview_thumb');
            const previewName = document.getElementById('marker_image_preview_name');
            const previewSize = document.getElementById('marker_image_preview_size');

            if (!file) {
                if (previewBox) previewBox.style.display = 'none';
                return;
            }

            if (!validateImageFile(file, 'marker_image_error_msg')) {
                markerInput.value = '';
                if (previewBox) previewBox.style.display = 'none';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (event) {
                if (previewThumb) previewThumb.src = event.target.result;
                if (previewName) previewName.textContent = file.name;
                if (previewSize) previewSize.textContent = formatFileSize(file.size);
                if (previewBox) previewBox.style.display = 'flex';

                const marker = ensurePreviewMarker();
                marker.src = event.target.result;
                marker.style.display = 'block';
                updatePreviewSize();
                const top = topInput ? parseFloat(topInput.value) || 50 : 50;
                const left = leftInput ? parseFloat(leftInput.value) || 50 : 50;
                updatePreviewPosition(top, left);
            };
            reader.readAsDataURL(file);
        });

        const btnClearMarker = document.getElementById('btn_clear_marker_image');
        if (btnClearMarker) {
            btnClearMarker.addEventListener('click', function () {
                markerInput.value = '';
                const previewBox = document.getElementById('marker_image_preview_box');
                if (previewBox) previewBox.style.display = 'none';
                const errorMsg = document.getElementById('marker_image_error_msg');
                if (errorMsg) errorMsg.style.display = 'none';
                if (previewMarker) previewMarker.style.display = 'none';
            });
        }
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
    // 3. INTERACTIVE PIN DETAILS MODAL (WITH EDIT & DELETE ACTIONS)
    // Centered modal dialog eliminates all screen jumps, coordinate glitches & overflow cuts
    // =========================================================================
    let pinModal = null;

    function createPinModal() {
        if (pinModal) return pinModal;
        pinModal = document.createElement('div');
        pinModal.id = 'spotmap-pin-modal-overlay';
        pinModal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 99999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            box-sizing: border-box;
        `;

        pinModal.innerHTML = `
            <div id="spotmap-modal-card" style="
                background: #ffffff;
                border-radius: 14px;
                width: 100%;
                max-width: 440px;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45);
                border-top: 5px solid #223c3b;
                overflow: hidden;
                font-family: 'Montserrat', sans-serif;
                display: flex;
                flex-direction: column;
                max-height: 90vh;
                animation: popInModal 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            ">
                <div id="spotmap-modal-header" style="
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 16px 20px;
                    border-bottom: 1px solid #e2e8f0;
                    background: #f8fafc;
                ">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span id="spotmap-modal-badge" style="
                            font-size: 0.78rem;
                            font-weight: 800;
                            background: #223c3b;
                            color: #ffffff;
                            padding: 4px 10px;
                            border-radius: 6px;
                            letter-spacing: 0.5px;
                        ">HOUSE #</span>
                    </div>
                    <button type="button" id="spotmap-modal-close" style="
                        background: none;
                        border: none;
                        font-size: 1.6rem;
                        cursor: pointer;
                        color: #64748b;
                        line-height: 1;
                        padding: 2px 6px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        border-radius: 6px;
                        transition: all 0.15s;
                    ">&times;</button>
                </div>
                
                <div id="spotmap-modal-body" style="
                    padding: 20px;
                    overflow-y: auto;
                    flex: 1;
                ">
                    <!-- Dynamic details inserted here -->
                </div>

                <div id="spotmap-modal-footer" style="
                    padding: 14px 20px;
                    border-top: 1px solid #e2e8f0;
                    background: #f8fafc;
                    display: flex;
                    gap: 12px;
                ">
                    <button type="button" id="spotmap-modal-edit-btn" style="
                        flex: 1;
                        background: #2c6e49;
                        color: #ffffff;
                        border: none;
                        border-radius: 8px;
                        padding: 11px 16px;
                        font-size: 0.9rem;
                        font-weight: 600;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 8px;
                        transition: background 0.15s;
                        box-shadow: 0 2px 6px rgba(44, 110, 73, 0.25);
                    ">
                        <i class="fa-solid fa-pen-to-square"></i> Edit Details
                    </button>
                    <button type="button" id="spotmap-modal-delete-btn" style="
                        flex: 1;
                        background: #fee2e2;
                        color: #b91c1c;
                        border: 1px solid #fca5a5;
                        border-radius: 8px;
                        padding: 11px 16px;
                        font-size: 0.9rem;
                        font-weight: 600;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 8px;
                        transition: background 0.15s;
                    ">
                        <i class="fa-solid fa-trash"></i> Delete Pin
                    </button>
                </div>
            </div>
        `;

        // Inject keyframes animation style if not already added
        if (!document.getElementById('spotmap-modal-anim-style')) {
            const style = document.createElement('style');
            style.id = 'spotmap-modal-anim-style';
            style.textContent = `
                @keyframes popInModal {
                    0% { transform: scale(0.92); opacity: 0; }
                    100% { transform: scale(1); opacity: 1; }
                }
                #spotmap-modal-close:hover {
                    background: #fee2e2;
                    color: #ef4444;
                }
                #spotmap-modal-edit-btn:hover {
                    background: #1e4f34;
                }
                #spotmap-modal-delete-btn:hover {
                    background: #fecaca;
                }
            `;
            document.head.appendChild(style);
        }

        document.body.appendChild(pinModal);

        // Close on background backdrop click
        pinModal.addEventListener('click', function (e) {
            if (e.target === pinModal) {
                closePinModal();
            }
        });

        // Close on X button
        pinModal.querySelector('#spotmap-modal-close').addEventListener('click', closePinModal);

        // Close on ESC key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && pinModal && pinModal.style.display === 'flex') {
                closePinModal();
            }
        });

        return pinModal;
    }

    function closePinModal() {
        if (pinModal) {
            pinModal.style.display = 'none';
        }
    }

    function showPinDetails(markerEl) {
        const modal = createPinModal();

        const houseNo = markerEl.getAttribute('data-house-number') || 'N/A';
        const husband = markerEl.getAttribute('data-husband') || 'N/A';
        const spouse = markerEl.getAttribute('data-spouse') || '';
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
                <div style="margin-top: 14px; padding-top: 12px; border-top: 1px dashed #cbd5e1;">
                    <div style="font-size: 0.82rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between;">
                        <span><i class="fa-solid fa-children"></i> Children / Dependents</span>
                        <span style="background: #e0f2fe; color: #0284c7; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem;">${childrenArr.length}</span>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 6px; max-height: 120px; overflow-y: auto;">
                        ${childrenArr.map(c => `
                            <span style="background: #f1f5f9; color: #1e293b; border: 1px solid #cbd5e1; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 600;">
                                ${c}
                            </span>
                        `).join('')}
                    </div>
                </div>
            `;
        } else {
            childrenHtml = `
                <div style="margin-top: 12px; font-size: 0.8rem; color: #94a3b8; font-style: italic; display: flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-circle-info"></i> No children / dependents registered
                </div>
            `;
        }

        // Update modal badge
        modal.querySelector('#spotmap-modal-badge').textContent = `HOUSE #${houseNo}`;

        // Populate modal body
        const modalBody = modal.querySelector('#spotmap-modal-body');
        modalBody.innerHTML = `
            ${houseImg ? `
                <div style="margin-bottom: 14px; border-radius: 8px; overflow: hidden; border: 1px solid #cbd5e1; max-height: 180px;">
                    <img src="${houseImg}" onerror="this.parentElement.style.display='none'" style="width: 100%; height: 180px; object-fit: cover; display: block;">
                </div>
            ` : ''}
            <div style="font-size: 0.9rem; color: #1e293b; display: flex; flex-direction: column; gap: 8px;">
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 6px;">
                    <span style="color: #64748b; font-weight: 500;">Household Head:</span>
                    <strong style="color: #0f172a;">${husband}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 6px;">
                    <span style="color: #64748b; font-weight: 500;">Spouse:</span>
                    <strong style="color: #0f172a;">${spouse || '<span style="color: #94a3b8; font-style: italic; font-weight: normal;">None / Single</span>'}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #64748b; font-weight: 500;">Map Coordinates:</span>
                    <span style="color: #2c6e49; font-weight: 700; font-family: monospace;">Top: ${markerEl.getAttribute('data-top')}%, Left: ${markerEl.getAttribute('data-left')}%</span>
                </div>
            </div>
            ${childrenHtml}
        `;

        // Wire Edit Button
        modal.querySelector('#spotmap-modal-edit-btn').onclick = function () {
            closePinModal();
            loadMarkerForEdit(markerEl);
            showNotificationToast(`Editing House #${houseNo}. Modify details below and click "Update Household Pin".`, 'success');
        };

        // Wire Delete Button
        modal.querySelector('#spotmap-modal-delete-btn').onclick = function (e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            closePinModal();
            executeHouseholdDeletion(markerId, houseNo);
        };

        // Show centered modal
        modal.style.display = 'flex';
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
    // 4. BI-DIRECTIONAL EDIT SYNC & FORM RESET/DELETE ACTIONS
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
        const houseImgSrc = markerEl.getAttribute('data-house-image') || '';
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

        // Show thumbnail preview for current house image
        const housePreviewBox = document.getElementById('house_image_preview_box');
        const houseThumb = document.getElementById('house_image_preview_thumb');
        const houseName = document.getElementById('house_image_preview_name');
        const houseSize = document.getElementById('house_image_preview_size');
        if (housePreviewBox && houseThumb && houseImgSrc) {
            houseThumb.src = houseImgSrc;
            if (houseName) houseName.innerHTML = '<i class="fa-solid fa-circle-check" style="color:#10b981;"></i> Current Location Photo Attached';
            if (houseSize) houseSize.textContent = 'Keep this image, or choose another to replace';
            housePreviewBox.style.display = 'flex';
        }

        // Show thumbnail preview for current marker icon
        const markerPreviewBox = document.getElementById('marker_image_preview_box');
        const markerThumb = document.getElementById('marker_image_preview_thumb');
        const markerName = document.getElementById('marker_image_preview_name');
        const markerSize = document.getElementById('marker_image_preview_size');
        if (markerPreviewBox && markerThumb && markerSrc) {
            markerThumb.src = markerSrc;
            if (markerName) markerName.innerHTML = '<i class="fa-solid fa-circle-check" style="color:#10b981;"></i> Current Pin Graphic';
            if (markerSize) markerSize.textContent = 'Keep this icon, or choose another to replace';
            markerPreviewBox.style.display = 'flex';
        }

        // Show image notice badges if present
        let housePreviewBadge = document.getElementById('house_image_preview');
        if (!housePreviewBadge && houseInput && houseInput.parentNode) {
            housePreviewBadge = document.createElement('div');
            housePreviewBadge.id = 'house_image_preview';
            housePreviewBadge.style.cssText = 'margin-top: 6px; font-size: 0.8rem; color: #059669; font-weight: 500;';
            houseInput.parentNode.appendChild(housePreviewBadge);
        }
        if (housePreviewBadge) {
            housePreviewBadge.style.display = 'block';
            housePreviewBadge.innerHTML = '<i class="fa-solid fa-circle-check"></i> Current house photo saved. Leave empty to keep, or select file to replace.';
        }

        let markerPreviewBadge = document.getElementById('marker_image_preview');
        if (!markerPreviewBadge && markerInput && markerInput.parentNode) {
            markerPreviewBadge = document.createElement('div');
            markerPreviewBadge.id = 'marker_image_preview';
            markerPreviewBadge.style.cssText = 'margin-top: 6px; font-size: 0.8rem; color: #059669; font-weight: 500;';
            markerInput.parentNode.appendChild(markerPreviewBadge);
        }
        if (markerPreviewBadge) {
            markerPreviewBadge.style.display = 'block';
            markerPreviewBadge.innerHTML = '<i class="fa-solid fa-circle-check"></i> Current marker pin icon saved. Leave empty to keep, or select file to replace.';
        }

        // Update save button appearance
        if (saveBtn) {
            saveBtn.innerHTML = '<i class="fa-solid fa-pen-to-square"></i> Update Household Pin';
            saveBtn.style.background = '#2c6e49';
        }

        // Container for action buttons below saveBtn
        let actionBtnGroup = document.getElementById('edit-mode-action-group');
        if (!actionBtnGroup && saveBtn && saveBtn.parentNode) {
            actionBtnGroup = document.createElement('div');
            actionBtnGroup.id = 'edit-mode-action-group';
            actionBtnGroup.style.cssText = 'display: flex; gap: 10px; margin-top: 10px;';
            saveBtn.parentNode.insertBefore(actionBtnGroup, saveBtn.nextSibling);
        }

        if (actionBtnGroup) {
            actionBtnGroup.innerHTML = `
                <button type="button" id="btn-cancel-edit" style="flex: 1; background: #64748b; color: #ffffff; padding: 12px; font-weight: 600; font-size: 0.92rem; border: none; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; transition: background 0.2s;">
                    <i class="fa-solid fa-xmark"></i> Cancel Edit
                </button>
                <button type="button" id="btn-delete-from-map" style="flex: 1; background: #ef4444; color: #ffffff; padding: 12px; font-weight: 600; font-size: 0.92rem; border: none; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; transition: background 0.2s;">
                    <i class="fa-solid fa-trash"></i> Delete Pin
                </button>
            `;

            document.getElementById('btn-cancel-edit').onclick = resetFormToNew;
            document.getElementById('btn-delete-from-map').onclick = function (e) {
                if (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                const delId = (householdIdInput && householdIdInput.value) ? householdIdInput.value : id;
                executeHouseholdDeletion(delId, houseNo);
            };
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

        const housePreviewBadge = document.getElementById('house_image_preview');
        if (housePreviewBadge) housePreviewBadge.style.display = 'none';
        const markerPreviewBadge = document.getElementById('marker_image_preview');
        if (markerPreviewBadge) markerPreviewBadge.style.display = 'none';

        const housePreviewBox = document.getElementById('house_image_preview_box');
        if (housePreviewBox) housePreviewBox.style.display = 'none';
        const markerPreviewBox = document.getElementById('marker_image_preview_box');
        if (markerPreviewBox) markerPreviewBox.style.display = 'none';

        const houseError = document.getElementById('house_image_error_msg');
        if (houseError) { houseError.style.display = 'none'; houseError.textContent = ''; }
        const markerError = document.getElementById('marker_image_error_msg');
        if (markerError) { markerError.style.display = 'none'; markerError.textContent = ''; }

        if (saveBtn) {
            saveBtn.innerHTML = '<i class="fa-solid fa-map-pin"></i> Save to Map';
            saveBtn.style.background = '#2c6e49';
        }

        const actionBtnGroup = document.getElementById('edit-mode-action-group');
        if (actionBtnGroup) actionBtnGroup.remove();

        // Clear ?edit_id from URL if present
        if (window.history.replaceState) {
            const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
            window.history.replaceState({ path: cleanUrl }, '', cleanUrl);
        }
    }

    // Add a "Clear Form" button header link if not already present
    const formTitle = document.querySelector('.form-title');
    if (formTitle && !document.getElementById('btn-quick-clear-form')) {
        const clearLink = document.createElement('button');
        clearLink.type = 'button';
        clearLink.id = 'btn-quick-clear-form';
        clearLink.style.cssText = 'margin-left: auto; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 6px; padding: 5px 12px; font-size: 0.8rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s;';
        clearLink.innerHTML = '<i class="fa-solid fa-rotate-left"></i> Reset Form';
        clearLink.onclick = function (e) {
            e.preventDefault();
            if (confirm('Clear all inputs on this form?')) {
                resetFormToNew();
            }
        };
        formTitle.appendChild(clearLink);
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
