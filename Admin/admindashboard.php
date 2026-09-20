<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Houses - Brgy Tabon</title>
    
    <!-- FontAwesome CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin Stylesheet -->
    <link rel="stylesheet" href="css/admin_common.css">
    <link rel="stylesheet" href="css/admin_table.css">
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../Barangay Logo/Logo.png" alt="Barangay Logo">
            <h3>Brgy Tabon</h3>
            <p>Info SpotMap</p>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="controlpanel.php"><i class="fa-solid fa-gauge"></i> Control Panel</a></li>
                <li><a href="spotmapmanage.php"><i class="fa-solid fa-map-location-dot"></i> SpotMap Manage</a></li>


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
            <h1 class="page-title">Manage Houses</h1>
            <button class="btn-add" onclick="openHouseholdModal()"><i class="fa-solid fa-plus"></i> Add House</button>
        </div>

        <!-- Data Container -->
        <div class="controls-container">
            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="search-input" placeholder="Search house #, address..." onkeyup="renderTable()">
            </div>
            <div class="filter-group">
                <select class="filter-select" id="purok-filter" onchange="renderTable()">
                    <option value="all">All Puroks</option>
                    <option value="Purok 1">Purok 1</option>
                    <option value="Purok 2">Purok 2</option>
                    <option value="Purok 3">Purok 3</option>
                </select>
                <div class="count-badge" id="table-count">
                    <i class="fa-solid fa-circle" style="font-size: 8px; color: #4cd137; margin-right: 5px;"></i> 0 of 0 houses
                </div>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Marker</th>
                        <th>House #</th>
                        <th>Address</th>
                        <th>Purok</th>
                        <th>Members</th>
                        <th>Position (T/L)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="household-table-body">
                    <!-- Javascript will populate rows here -->
                </tbody>
            </table>
        </div>
        
    </main>

    <!-- Modal: Add / Edit Household -->
    <div class="modal-overlay" id="modal-household">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-household-title">Add Household</h2>
                <button class="modal-close" onclick="closeModal('modal-household')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <form id="form-household" onsubmit="return false;">
                    <input type="hidden" id="hh-id">
                    <div class="modal-form-group">
                        <label>House Number</label>
                        <input type="text" id="hh-number" required>
                    </div>
                    <div class="modal-form-group">
                        <label>Address / Street</label>
                        <input type="text" id="hh-address" required>
                    </div>
                    <div class="modal-form-group">
                        <label>Purok</label>
                        <select id="hh-purok" required>
                            <option value="Purok 1">Purok 1</option>
                            <option value="Purok 2">Purok 2</option>
                            <option value="Purok 3">Purok 3</option>
                            <option value="Purok 4">Purok 4</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: 15px;">
                        <div class="modal-form-group" style="flex: 1;">
                            <label>Top Position (%)</label>
                            <input type="number" id="hh-top" step="0.01" required>
                        </div>
                        <div class="modal-form-group" style="flex: 1;">
                            <label>Left Position (%)</label>
                            <input type="number" id="hh-left" step="0.01" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeModal('modal-household')">Cancel</button>
                <button class="btn-primary" onclick="saveHousehold()">Save Household</button>
            </div>
        </div>
    </div>

    <!-- Modal: Manage Members -->
    <div class="modal-overlay" id="modal-members">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Manage Members (House #<span id="manage-members-hh-num"></span>)</h2>
                <button class="modal-close" onclick="closeModal('modal-members')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <div class="member-list-container" id="members-list">
                    <!-- Members will be listed here -->
                </div>
                
                <h4>Add New Member</h4>
                <div class="add-member-form">
                    <div style="flex: 2;">
                        <input type="text" id="new-member-name" placeholder="Full Name" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px;">
                    </div>
                    <div style="flex: 1;">
                        <select id="new-member-role" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px;">
                            <option value="Head">Head</option>
                            <option value="Spouse">Spouse</option>
                            <option value="Child">Child</option>
                            <option value="Relative">Relative</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <button class="btn-primary" onclick="addMember()"><i class="fa-solid fa-plus"></i></button>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-primary" onclick="closeModal('modal-members')">Done</button>
            </div>
        </div>
    </div>

    <script>
        // Mock Database for Households
        let householdsDB = [
            { id: 1, number: '001', address: 'Street 1, Brgy Tabon', purok: 'Purok 1', top: 50.12, left: 45.34, members: [{name: 'Juan Dela Cruz', role: 'Head'}, {name: 'Maria Dela Cruz', role: 'Spouse'}] },
            { id: 2, number: '002', address: 'Street 2, Brgy Tabon', purok: 'Purok 2', top: 60.50, left: 20.10, members: [{name: 'Pedro Penduko', role: 'Head'}] },
            { id: 3, number: '003', address: 'Street 3, Brgy Tabon', purok: 'Purok 3', top: 10.20, left: 80.50, members: [] }
        ];

        let currentEditingHhId = null;

        // Render Table
        function renderTable() {
            const tbody = document.getElementById('household-table-body');
            const searchStr = document.getElementById('search-input').value.toLowerCase();
            const filterPurok = document.getElementById('purok-filter').value;
            
            tbody.innerHTML = '';
            
            let filteredHouseholds = householdsDB.filter(hh => {
                const matchesSearch = hh.number.toLowerCase().includes(searchStr) || hh.address.toLowerCase().includes(searchStr);
                const matchesPurok = filterPurok === 'all' || hh.purok === filterPurok;
                return matchesSearch && matchesPurok;
            });

            document.getElementById('table-count').innerHTML = \`<i class="fa-solid fa-circle" style="font-size: 8px; color: #4cd137; margin-right: 5px;"></i> \${filteredHouseholds.length} of \${householdsDB.length} houses\`;

            if (filteredHouseholds.length === 0) {
                tbody.innerHTML = \`<tr><td colspan="7" style="text-align: center; color: #6b7280;">No households found.</td></tr>\`;
                return;
            }

            filteredHouseholds.forEach(hh => {
                const tr = document.createElement('tr');
                tr.innerHTML = \`
                    <td><img src="https://via.placeholder.com/40" alt="Marker" class="marker-img"></td>
                    <td>\${hh.number}</td>
                    <td>\${hh.address}</td>
                    <td><div class="purok-badge"><i class="fa-solid fa-location-dot"></i> \${hh.purok}</div></td>
                    <td><div class="members-count"><i class="fa-solid fa-users"></i> \${hh.members.length} members</div></td>
                    <td><div class="position-text">\${hh.top}%, \${hh.left}%</div></td>
                    <td>
                        <div class="actions">
                            <button class="btn-action" onclick="openHouseholdModal(\${hh.id})">Edit</button>
                            <button class="btn-action" onclick="openMembersModal(\${hh.id})">Members</button>
                            <button class="btn-action" onclick="deleteHousehold(\${hh.id})" style="color: #ef4444;">Delete</button>
                        </div>
                    </td>
                \`;
                tbody.appendChild(tr);
            });
        }

        // Modal Functions
        function openHouseholdModal(id = null) {
            currentEditingHhId = id;
            if (id) {
                document.getElementById('modal-household-title').innerText = 'Edit Household';
                const hh = householdsDB.find(h => h.id === id);
                document.getElementById('hh-number').value = hh.number;
                document.getElementById('hh-address').value = hh.address;
                document.getElementById('hh-purok').value = hh.purok;
                document.getElementById('hh-top').value = hh.top;
                document.getElementById('hh-left').value = hh.left;
            } else {
                document.getElementById('modal-household-title').innerText = 'Add Household';
                document.getElementById('form-household').reset();
            }
            document.getElementById('modal-household').classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            currentEditingHhId = null;
        }

        function saveHousehold() {
            const number = document.getElementById('hh-number').value;
            const address = document.getElementById('hh-address').value;
            const purok = document.getElementById('hh-purok').value;
            const top = parseFloat(document.getElementById('hh-top').value) || 0;
            const left = parseFloat(document.getElementById('hh-left').value) || 0;

            if (!number || !address) {
                alert("House Number and Address are required!");
                return;
            }

            if (currentEditingHhId) {
                // Edit existing
                const index = householdsDB.findIndex(h => h.id === currentEditingHhId);
                householdsDB[index].number = number;
                householdsDB[index].address = address;
                householdsDB[index].purok = purok;
                householdsDB[index].top = top;
                householdsDB[index].left = left;
            } else {
                // Add new
                const newId = householdsDB.length > 0 ? Math.max(...householdsDB.map(h => h.id)) + 1 : 1;
                householdsDB.push({
                    id: newId,
                    number: number,
                    address: address,
                    purok: purok,
                    top: top,
                    left: left,
                    members: []
                });
            }

            closeModal('modal-household');
            renderTable();
        }

        function deleteHousehold(id) {
            if (confirm('Are you sure you want to delete this household?')) {
                householdsDB = householdsDB.filter(h => h.id !== id);
                renderTable();
            }
        }

        // Manage Members Functions
        function openMembersModal(id) {
            currentEditingHhId = id;
            const hh = householdsDB.find(h => h.id === id);
            document.getElementById('manage-members-hh-num').innerText = hh.number;
            renderMembersList();
            document.getElementById('modal-members').classList.add('active');
        }

        function renderMembersList() {
            const listDiv = document.getElementById('members-list');
            const hh = householdsDB.find(h => h.id === currentEditingHhId);
            listDiv.innerHTML = '';

            if (hh.members.length === 0) {
                listDiv.innerHTML = '<p style="font-size: 13px; color: #9ca3af; font-style: italic;">No members added yet.</p>';
                return;
            }

            hh.members.forEach((member, index) => {
                const row = document.createElement('div');
                row.className = 'member-row';
                row.innerHTML = \`
                    <div>
                        <div class="member-info"><i class="fa-regular fa-user" style="margin-right: 5px; color: #9ca3af;"></i> \${member.name}</div>
                        <div class="member-role">\${member.role}</div>
                    </div>
                    <button class="btn-delete-member" onclick="removeMember(\${index})"><i class="fa-solid fa-trash-can"></i></button>
                \`;
                listDiv.appendChild(row);
            });
        }

        function addMember() {
            const nameInput = document.getElementById('new-member-name');
            const roleInput = document.getElementById('new-member-role');
            
            const name = nameInput.value.trim();
            const role = roleInput.value;

            if (!name) {
                alert("Please enter a member name.");
                return;
            }

            const hhIndex = householdsDB.findIndex(h => h.id === currentEditingHhId);
            householdsDB[hhIndex].members.push({ name: name, role: role });
            
            nameInput.value = '';
            renderMembersList();
            renderTable(); // Update the members count badge in the background table
        }

        function removeMember(index) {
            const hhIndex = householdsDB.findIndex(h => h.id === currentEditingHhId);
            householdsDB[hhIndex].members.splice(index, 1);
            renderMembersList();
            renderTable(); // Update the members count badge in the background table
        }

        // Initial render
        renderTable();
    </script>
</body>
</html>
