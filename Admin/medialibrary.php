<?php
// Admin/medialibrary.php - Media Gallery and Album Management
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once '../db_connect.php';

// Ensure upload directory exists
$upload_base_dir = 'uploads/gallery/';
if (!is_dir($upload_base_dir)) {
    mkdir($upload_base_dir, 0777, true);
}

// -------------------------------------------------------------
// POST Request Actions
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Create New Folder
    if ($action === 'create_folder') {
        $folder_name = trim($_POST['folder_name'] ?? '');
        $folder_desc = trim($_POST['folder_description'] ?? '');

        if (!empty($folder_name)) {
            $stmt = $conn->prepare("INSERT INTO gallery_folders (name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $folder_name, $folder_desc);
            $stmt->execute();
            header("Location: medialibrary.php?msg=folder_created");
            exit;
        } else {
            header("Location: medialibrary.php?error=empty_folder_name");
            exit;
        }
    }

    // 2. Edit / Rename Folder
    if ($action === 'edit_folder') {
        $folder_id = intval($_POST['folder_id'] ?? 0);
        $folder_name = trim($_POST['folder_name'] ?? '');
        $folder_desc = trim($_POST['folder_description'] ?? '');

        if ($folder_id > 0 && !empty($folder_name)) {
            $stmt = $conn->prepare("UPDATE gallery_folders SET name = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $folder_name, $folder_desc, $folder_id);
            $stmt->execute();
            header("Location: medialibrary.php?folder_id=" . $folder_id . "&msg=folder_updated");
            exit;
        }
    }

    // 3. Delete Entire Folder & Physical Files
    if ($action === 'delete_folder') {
        $folder_id = intval($_POST['folder_id'] ?? 0);
        if ($folder_id > 0) {
            $stmt_media = $conn->prepare("SELECT file_path FROM gallery_media WHERE folder_id = ?");
            $stmt_media->bind_param("i", $folder_id);
            $stmt_media->execute();
            $res = $stmt_media->get_result();
            while ($row = $res->fetch_assoc()) {
                if (!empty($row['file_path']) && file_exists($row['file_path'])) {
                    @unlink($row['file_path']);
                }
            }

            $stmt_del = $conn->prepare("DELETE FROM gallery_folders WHERE id = ?");
            $stmt_del->bind_param("i", $folder_id);
            $stmt_del->execute();

            header("Location: medialibrary.php?msg=folder_deleted");
            exit;
        }
    }

    // 4. Upload Multiple Images into Folder
    if ($action === 'upload_media') {
        $folder_id = intval($_POST['folder_id'] ?? 0);
        $caption = trim($_POST['caption'] ?? '');

        if ($folder_id > 0 && isset($_FILES['media_files']) && !empty($_FILES['media_files']['name'][0])) {
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $uploaded_count = 0;
            $files = $_FILES['media_files'];
            $file_count = count($files['name']);

            for ($i = 0; $i < $file_count; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $orig_name = $files['name'][$i];
                    $file_size = $files['size'][$i];
                    $tmp_name = $files['tmp_name'][$i];

                    $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed_extensions)) {
                        $sanitized = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', pathinfo($orig_name, PATHINFO_FILENAME));
                        $new_filename = time() . '_' . uniqid() . '_' . $sanitized . '.' . $ext;
                        $destination = $upload_base_dir . $new_filename;

                        if (move_uploaded_file($tmp_name, $destination)) {
                            $item_caption = !empty($caption) ? $caption : pathinfo($orig_name, PATHINFO_FILENAME);
                            $stmt_ins = $conn->prepare("INSERT INTO gallery_media (folder_id, file_path, file_name, caption, file_size) VALUES (?, ?, ?, ?, ?)");
                            $stmt_ins->bind_param("isssi", $folder_id, $destination, $new_filename, $item_caption, $file_size);
                            $stmt_ins->execute();
                            $uploaded_count++;
                        }
                    }
                }
            }

            if ($uploaded_count > 0) {
                header("Location: medialibrary.php?folder_id=" . $folder_id . "&msg=uploaded&count=" . $uploaded_count);
                exit;
            } else {
                header("Location: medialibrary.php?folder_id=" . $folder_id . "&error=no_valid_images");
                exit;
            }
        }
    }

    // 5. Delete Single Image
    if ($action === 'delete_media') {
        $media_id = intval($_POST['media_id'] ?? 0);
        $folder_id = intval($_POST['folder_id'] ?? 0);

        if ($media_id > 0) {
            $stmt = $conn->prepare("SELECT file_path FROM gallery_media WHERE id = ?");
            $stmt->bind_param("i", $media_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if (!empty($row['file_path']) && file_exists($row['file_path'])) {
                    @unlink($row['file_path']);
                }
                $del_stmt = $conn->prepare("DELETE FROM gallery_media WHERE id = ?");
                $del_stmt->bind_param("i", $media_id);
                $del_stmt->execute();
            }

            header("Location: medialibrary.php?folder_id=" . $folder_id . "&msg=media_deleted");
            exit;
        }
    }
}

// -------------------------------------------------------------
// View State Determination (Root View vs Inside Folder View)
// -------------------------------------------------------------
$current_folder_id = isset($_GET['folder_id']) ? intval($_GET['folder_id']) : 0;
$current_folder = null;
$folder_media = [];

if ($current_folder_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM gallery_folders WHERE id = ?");
    $stmt->bind_param("i", $current_folder_id);
    $stmt->execute();
    $current_folder = $stmt->get_result()->fetch_assoc();

    if ($current_folder) {
        $stmt_media = $conn->prepare("SELECT * FROM gallery_media WHERE folder_id = ? ORDER BY id DESC");
        $stmt_media->bind_param("i", $current_folder_id);
        $stmt_media->execute();
        $media_res = $stmt_media->get_result();
        while ($m = $media_res->fetch_assoc()) {
            $folder_media[] = $m;
        }
    } else {
        header("Location: medialibrary.php");
        exit;
    }
} else {
    // Root View: Fetch Folders with photo counts
    $folders_query = "SELECT f.*, COUNT(m.id) AS total_photos 
                      FROM gallery_folders f 
                      LEFT JOIN gallery_media m ON f.id = m.folder_id 
                      GROUP BY f.id 
                      ORDER BY f.created_at DESC";
    $folders_result = $conn->query($folders_query);
}

$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $current_folder ? htmlspecialchars($current_folder['name']) . " | " : "" ?>Manage Media Gallery - Brgy Tabon</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Admin & Gallery Stylesheets -->
    <link rel="stylesheet" href="Admin CSS/admin_common.css?v=<?= time() ?>">
    <link rel="stylesheet" href="Admin CSS/mediagallery.css?v=<?= time() ?>">
    <style>
* { box-sizing: border-box; }
body { font-family: 'Montserrat', sans-serif; background-color: #f8fafc; margin: 0; color: #1e293b; }
.main-content { background-color: #f8fafc; flex: 1; display: flex; flex-direction: column; overflow-y: auto; padding: 24px 36px; }
.dashboard-content { max-width: 1400px; width: 100%; margin: 0 auto; }

/* 0. Top Bar matching Admin Mockup */
.media-top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding: 6px 2px;
}

.media-top-title {
    margin: 0;
    color: #0f172a;
    font-size: 1.55rem;
    font-weight: 800;
    font-family: 'Montserrat', sans-serif;
    letter-spacing: -0.4px;
}

.admin-profile {
    display: flex;
    align-items: center;
    gap: 14px;
}

.admin-info {
    text-align: right;
    display: flex;
    flex-direction: column;
}

.admin-name {
    font-weight: 700;
    color: #1e293b;
    font-size: 14px;
    line-height: 1.25;
    font-family: 'Montserrat', sans-serif;
}

.admin-role {
    color: #94a3b8;
    font-size: 12px;
    margin-top: 3px;
    line-height: 1.25;
    font-family: 'Montserrat', sans-serif;
}

.admin-avatar {
    width: 42px;
    height: 42px;
    background-color: #f59e0b;
    color: #0f172a;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 17px;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
    font-family: 'Montserrat', sans-serif;
}

/* 1. Header Card matching Mockup */
.category-header-card {
    background-color: #ffffff;
    border: 1px solid #f1f5f9;
    border-radius: 20px;
    padding: 26px 36px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    gap: 20px;
    flex-wrap: wrap;
}

.category-main-title {
    font-size: 1.38rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 6px 0;
    font-family: 'Montserrat', sans-serif;
    letter-spacing: -0.3px;
}

.category-sub-title {
    font-size: 0.92rem;
    color: #64748b;
    margin: 0;
    font-family: 'Montserrat', sans-serif;
    font-weight: 500;
}

.btn-create-folder {
    background-color: #064e3b;
    color: #ffffff;
    border: none;
    padding: 11px 22px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.92rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 3px 12px rgba(6, 78, 59, 0.25);
    transition: all 0.2s ease;
    font-family: 'Montserrat', sans-serif;
}

.btn-create-folder:hover {
    background-color: #043628;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(6, 78, 59, 0.35);
}

/* 2. Folder Cards Grid */
.category-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
}
.category-folder-card {
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 24px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.category-folder-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.06);
}
.folder-icon-badge {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background-color: #fff8e6;
    border: 1px solid #fef3c7;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #d97706;
    font-size: 1.7rem;
    margin-bottom: 6px;
}
.folder-card-title-link { text-decoration: none; color: inherit; }
.folder-card-title { font-size: 1.25rem; font-weight: 700; color: #0f172a; margin: 12px 0 6px 0; }
.folder-card-desc { font-size: 0.9rem; color: #64748b; margin: 0 0 18px 0; min-height: 38px; }
.card-dashed-divider { border-top: 1px dashed #cbd5e1; margin-bottom: 16px; }

.folder-card-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
.photo-count-pill { background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 20px; font-size: 0.82rem; font-weight: 600; }
.folder-created-date { font-size: 0.8rem; color: #94a3b8; }

.folder-card-footer { display: flex; justify-content: space-between; align-items: center; margin-top: auto; }
.btn-open-album {
    background-color: #0f766e;
    color: #ffffff;
    text-decoration: none;
    padding: 8px 18px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.88rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background 0.2s;
}
.btn-open-album:hover { background-color: #0d9488; }
.btn-card-trash { background: none; border: none; color: #ef4444; font-size: 1.15rem; cursor: pointer; padding: 6px; }

/* 3. Photos Grid inside Folder */
.gallery-nav-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.breadcrumbs { display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 0.95rem; }
.breadcrumb-item { color: #0f766e; text-decoration: none; }
.breadcrumb-sep { color: #94a3b8; font-size: 0.8rem; }
.breadcrumb-current { color: #0f172a; }
.btn-back-folders { color: #64748b; text-decoration: none; font-size: 0.9rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }

.media-container-white { background: #ffffff; border-radius: 18px; padding: 25px; border: 1px solid #e2e8f0; }
.media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
.media-card { border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.03); }
.media-thumb-wrap { position: relative; width: 100%; height: 180px; overflow: hidden; cursor: pointer; }
.media-thumb-wrap img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease; }
.media-thumb-wrap:hover img { transform: scale(1.06); }

/* Card Hover Information Overlay */
.card-overlay {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.35) 0%, rgba(15, 23, 42, 0.88) 100%);
    backdrop-filter: blur(2px);
    -webkit-backdrop-filter: blur(2px);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 12px 10px;
    box-sizing: border-box;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 5;
    text-align: center;
    pointer-events: auto;
}

.media-thumb-wrap:hover .card-overlay {
    opacity: 1;
    visibility: visible;
}

.card-overlay-btn {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #ffffff;
    color: #0f172a;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.35);
    margin-bottom: 8px;
    transform: translateY(6px);
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), background 0.2s ease, color 0.2s ease;
    flex-shrink: 0;
}

.card-overlay-btn i {
    font-size: 1.1rem;
    font-weight: 900;
}

.media-thumb-wrap:hover .card-overlay-btn {
    transform: translateY(0);
}

.card-overlay-title {
    color: #ffffff;
    font-family: 'Montserrat', sans-serif;
    font-size: 0.95rem;
    font-weight: 700;
    line-height: 1.25;
    margin: 0 0 6px 0;
    text-shadow: 0 2px 6px rgba(0, 0, 0, 0.85);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-break: break-word;
    max-width: 95%;
}

.card-overlay-folder {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 50px;
    border: 1.5px solid #f59e0b;
    background: rgba(245, 158, 11, 0.18);
    color: #fbbf24;
    font-family: 'Montserrat', sans-serif;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    margin-bottom: 5px;
    max-width: 95%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
}

.card-overlay-folder i {
    color: #f59e0b;
    font-size: 0.72rem;
}

.card-overlay-date {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 50px;
    background: rgba(15, 23, 42, 0.88);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: #f8fafc;
    font-family: 'Montserrat', sans-serif;
    font-size: 0.68rem;
    font-weight: 600;
    letter-spacing: 0.2px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.45);
    white-space: nowrap;
    max-width: 95%;
}

.card-overlay-date i {
    color: #fbbf24;
    font-size: 0.72rem;
}

.media-card-footer { padding: 12px; }
.media-caption { font-size: 0.88rem; font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.media-meta { display: flex; justify-content: space-between; align-items: center; margin-top: 6px; font-size: 0.78rem; color: #94a3b8; }
.btn-delete-icon { background: none; border: none; color: #ef4444; cursor: pointer; font-size: 0.95rem; }

/* 4. Dropzone & Modals */
.upload-dropzone {
    border: 2px dashed #0f766e;
    background: #f0fdfa;
    border-radius: 12px;
    padding: 30px 20px;
    text-align: center;
    cursor: pointer;
    transition: background 0.2s;
}
.upload-dropzone:hover { background: #ccfbf1; }
.dropzone-icon { font-size: 2.5rem; color: #0f766e; }
.dropzone-text { font-weight: 600; color: #0f766e; margin: 8px 0 4px 0; }
.dropzone-hint { font-size: 0.8rem; color: #64748b; }

.modal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
.modal-content { background: #fff; border-radius: 14px; width: 100%; max-width: 480px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); overflow: hidden; }
.modal-lg { max-width: 620px; }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; border-bottom: 1px solid #e2e8f0; }
.modal-header h2 { margin: 0; font-size: 1.15rem; color: #0f172a; }
.close-modal { font-size: 1.5rem; color: #94a3b8; cursor: pointer; }
.modal-body { padding: 24px; }
.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-weight: 600; font-size: 0.88rem; margin-bottom: 6px; }
.form-control { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; font-size: 0.92rem; }
.modal-footer { padding: 14px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px; }
.btn-cancel { background: #e2e8f0; color: #475569; border: none; padding: 9px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; }
.btn-submit { background: #093d25; color: #fff; border: none; padding: 9px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; }
.btn-danger-submit { background: #ef4444; color: #fff; border: none; padding: 9px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; }

/* 5. Lightbox Modal */
.lightbox-modal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.9); z-index: 2000; align-items: center; justify-content: center; }
.lightbox-container { max-width: 90vw; max-height: 85vh; text-align: center; }
.lightbox-img { max-width: 100%; max-height: 75vh; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.5); }
.lightbox-caption { color: #fff; font-size: 1rem; margin-top: 12px; font-weight: 500; }
.lightbox-close { position: absolute; top: 20px; right: 30px; font-size: 2.5rem; color: #fff; cursor: pointer; }

.empty-state { text-align: center; padding: 50px 20px; }
.empty-icon-box { font-size: 3rem; color: #94a3b8; margin-bottom: 12px; }
.empty-state h3 { margin: 0 0 6px 0; color: #1e293b; }
.empty-state p { color: #64748b; margin: 0 0 20px 0; font-size: 0.95rem; }

/* Alerts */
.alert-banner { display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 600; font-size: 0.92rem; }
.alert-success { background: #d1fae5; border-left: 5px solid #10b981; color: #065f46; }
.alert-error { background: #fee2e2; border-left: 5px solid #ef4444; color: #991b1b; }
.alert-text { display: flex; align-items: center; gap: 10px; }
.btn-close-alert { background: none; border: none; font-size: 1.4rem; cursor: pointer; color: inherit; line-height: 1; }

/* Action buttons in folder detail header */
.folder-detail-header-card { align-items: flex-start; }
.folder-title-wrap { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 6px; }
.folder-title-icon { color: #d97706; }
.badge-count { background: #e0f2fe; color: #0369a1; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; }
.folder-action-buttons { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
.btn-secondary-action { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 10px 18px; border-radius: 10px; font-weight: 600; font-size: 0.9rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
.btn-secondary-action:hover { background: #e2e8f0; }
.btn-danger-action { background: #fff; color: #ef4444; border: 1px solid #fca5a5; padding: 10px 18px; border-radius: 10px; font-weight: 600; font-size: 0.9rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
.btn-danger-action:hover { background: #fee2e2; }

    </style>
</head>
<body>

    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../Barangay Logo/Logo.png" alt="Barangay Logo" width="70" height="70" style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover; display: inline-block;" onerror="this.src='https://via.placeholder.com/70'">
            <h3>Brgy Tabon</h3>
            <p>Info SpotMap</p>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="controlpanel.php"><i class="fa-solid fa-gauge"></i> Control Panel</a></li>
                <li><a href="about.php"><i class="fa-solid fa-circle-info"></i> About</a></li>
                <li>
                    <a class="dropdown-btn"><i class="fa-solid fa-map-location-dot"></i> SpotMap Manage <i class="fa-solid fa-caret-down" style="margin-left: auto;"></i></a>
                    <div class="dropdown-container">
                        <a href="Legend.php">Legend</a>
                        <a href="Purok 1.php">Purok Mesaya</a>
                        <a href="Purok 2.php">Purok Metamed</a>
                        <a href="Purok 3.php">Purok Melitebangay</a>
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
                <li><a href="medialibrary.php" class="active"><i class="fa-regular fa-images"></i> Media Library</a></li>
                <li>
                    <a class="dropdown-btn"><i class="fa-solid fa-user-tie"></i> Barangay Board <i class="fa-solid fa-caret-down" style="margin-left: auto;"></i></a>
                    <div class="dropdown-container">
                        <a href="barangayofficial.php">Barangay Official</a>
                        <a href="skofficial.php">SK Official</a>
                    </div>
                </li>
                <li><a href="services.php"><i class="fa-solid fa-bell-concierge"></i> Services</a></li>
                <li><a href="announcements.php"><i class="fa-solid fa-bullhorn"></i> Announcement</a></li>
                <li><a href="feedback.php"><i class="fa-solid fa-comments"></i> Feedback Chat</a></li>
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

    <!-- Main Dashboard Content -->
    <div class="main-content">
        <main class="dashboard-content">

            <!-- Top Navigation Bar matching User Mockup -->
            <div class="top-bar media-top-bar">
                <h1 class="page-title media-top-title">Manage Media Gallery</h1>
                <div class="admin-profile">
                    <div class="admin-info">
                        <span class="admin-name">System Admin</span>
                        <span class="admin-role">Administrator</span>
                    </div>
                    <div class="admin-avatar">A</div>
                </div>
            </div>

            <!-- Flash Alert Message -->
            <?php if ($msg): ?>
                <div class="alert-banner alert-success" id="statusAlert">
                    <div class="alert-text">
                        <i class="bi bi-check-circle-fill"></i>
                        <?php 
                            if ($msg === 'folder_created') echo "Folder created successfully!";
                            elseif ($msg === 'folder_updated') echo "Folder details updated successfully!";
                            elseif ($msg === 'folder_deleted') echo "Folder and all its images were permanently deleted.";
                            elseif ($msg === 'uploaded') echo (isset($_GET['count']) ? intval($_GET['count']) : "") . " Image(s) uploaded successfully!";
                            elseif ($msg === 'media_deleted') echo "Image deleted successfully.";
                        ?>
                    </div>
                    <button type="button" class="btn-close-alert" onclick="document.getElementById('statusAlert').remove();">&times;</button>
                </div>
            <?php elseif ($error): ?>
                <div class="alert-banner alert-error" id="statusAlert">
                    <div class="alert-text">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <?= $error === 'empty_folder_name' ? 'Please provide a valid folder name.' : 'An error occurred. Please try again.' ?>
                    </div>
                    <button type="button" class="btn-close-alert" onclick="document.getElementById('statusAlert').remove();">&times;</button>
                </div>
            <?php endif; ?>

            <?php if ($current_folder): ?>
                <!-- ======================================================== -->
                <!-- VIEW A: INSIDE FOLDER VIEW (PHOTOS GRID)                 -->
                <!-- ======================================================== -->
                <div class="gallery-nav-bar">
                    <div class="breadcrumbs">
                        <a href="medialibrary.php" class="breadcrumb-item"><i class="bi bi-folder-fill"></i> Media Library</a>
                        <span class="breadcrumb-sep"><i class="bi bi-chevron-right"></i></span>
                        <span class="breadcrumb-current"><?= htmlspecialchars($current_folder['name']) ?></span>
                    </div>
                    <a href="medialibrary.php" class="btn-back-folders">
                        <i class="bi bi-arrow-left"></i> Back to Folders
                    </a>
                </div>

                <div class="category-header-card folder-detail-header-card">
                    <div class="category-header-info">
                        <div class="folder-title-wrap">
                            <h2 class="category-main-title"><i class="bi bi-folder2-open folder-title-icon"></i> <?= htmlspecialchars($current_folder['name']) ?></h2>
                            <span class="badge-count"><?= count($folder_media) ?> <?= count($folder_media) === 1 ? 'Photo' : 'Photos' ?></span>
                        </div>
                        <?php if (!empty($current_folder['description'])): ?>
                            <p class="category-sub-title"><?= htmlspecialchars($current_folder['description']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="folder-action-buttons">
                        <button type="button" class="btn-secondary-action" onclick="openEditFolderModal(<?= $current_folder['id'] ?>, '<?= htmlspecialchars(addslashes($current_folder['name'])) ?>', '<?= htmlspecialchars(addslashes($current_folder['description'] ?? '')) ?>')">
                            <i class="bi bi-pencil-square"></i> Rename
                        </button>
                        <button type="button" class="btn-danger-action" onclick="confirmDeleteFolder(<?= $current_folder['id'] ?>, '<?= htmlspecialchars(addslashes($current_folder['name'])) ?>')">
                            <i class="bi bi-trash3"></i> Delete
                        </button>
                        <button type="button" class="btn-create-folder" onclick="openModal('uploadMediaModal')">
                            <i class="bi bi-cloud-arrow-up-fill"></i> Upload Images
                        </button>
                    </div>
                </div>

                <div class="media-container-white">
                    <?php if (count($folder_media) > 0): ?>
                        <div class="media-grid">
                            <?php foreach ($folder_media as $media): 
                                $a_title = !empty($media['caption']) ? $media['caption'] : (!empty($media['file_name']) ? pathinfo($media['file_name'], PATHINFO_FILENAME) : 'Media File');
                                $a_folder = htmlspecialchars($current_folder['name']);
                                $a_date = !empty($media['created_at']) ? date('M d, Y • h:i A', strtotime($media['created_at'])) : date('M d, Y • h:i A');
                            ?>
                                <div class="media-card">
                                    <div class="media-thumb-wrap" onclick="viewLightbox('<?= htmlspecialchars($media['file_path']) ?>', '<?= htmlspecialchars(addslashes($a_title)) ?>', '<?= htmlspecialchars(addslashes($current_folder['name'])) ?>', '<?= htmlspecialchars(addslashes($a_date)) ?>')">
                                        <img src="<?= htmlspecialchars($media['file_path']) ?>" alt="<?= htmlspecialchars($a_title) ?>" loading="lazy">
                                        <div class="card-overlay">
                                            <div class="card-overlay-btn" title="View Fullscreen">
                                                <i class="fa-solid fa-expand"></i>
                                            </div>
                                            <div class="card-overlay-title"><?= htmlspecialchars($a_title) ?></div>
                                            <div class="card-overlay-folder">
                                                <i class="fa-solid fa-folder"></i> <?= strtoupper($a_folder) ?>
                                            </div>
                                            <div class="card-overlay-date">
                                                <i class="fa-regular fa-clock"></i> <?= htmlspecialchars($a_date) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="media-card-footer">
                                        <div class="media-caption" title="<?= htmlspecialchars($a_title) ?>">
                                            <?= htmlspecialchars($a_title) ?>
                                        </div>
                                        <div class="media-meta">
                                            <span><?= date('M d, Y', strtotime($media['created_at'])) ?></span>
                                            <button type="button" class="btn-delete-icon" title="Delete Image" onclick="confirmDeleteMedia(<?= $media['id'] ?>, <?= $current_folder['id'] ?>)">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon-box"><i class="bi bi-images"></i></div>
                            <h3>No photos in this folder yet</h3>
                            <p>Upload photos to categorize them under "<strong><?= htmlspecialchars($current_folder['name']) ?></strong>".</p>
                            <button type="button" class="btn-create-folder" onclick="openModal('uploadMediaModal')">
                                <i class="bi bi-cloud-arrow-up-fill"></i> Upload Images
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <!-- ======================================================== -->
                <!-- VIEW B: ROOT VIEW (ALBUMS & CATEGORY CARDS)             -->
                <!-- ======================================================== -->
                <div class="category-header-card">
                    <div class="category-header-info">
                        <h2 class="category-main-title">Media Categories & Folders</h2>
                        <p class="category-sub-title">Organize photos into folders (e.g., Meeting, Sports, Community Events).</p>
                    </div>
                    <button class="btn-create-folder" onclick="openModal('createFolderModal')">
                        <i class="bi bi-folder-plus"></i> + Create Folder
                    </button>
                </div>

                <?php if ($folders_result && $folders_result->num_rows > 0): ?>
                    <div class="category-cards-grid">
                        <?php while ($f = $folders_result->fetch_assoc()): ?>
                            <div class="category-folder-card">
                                <div class="folder-icon-badge">
                                    <i class="bi bi-folder-fill"></i>
                                </div>

                                <a href="medialibrary.php?folder_id=<?= $f['id'] ?>" class="folder-card-title-link">
                                    <h3 class="folder-card-title"><?= htmlspecialchars($f['name']) ?></h3>
                                </a>

                                <p class="folder-card-desc">
                                    <?= !empty($f['description']) ? htmlspecialchars($f['description']) : 'No description provided.' ?>
                                </p>

                                <div class="card-dashed-divider"></div>

                                <div class="folder-card-meta">
                                    <span class="photo-count-pill">
                                        <i class="bi bi-image"></i> <?= $f['total_photos'] ?> <?= $f['total_photos'] == 1 ? 'Photo' : 'Photos' ?>
                                    </span>
                                    <span class="folder-created-date">
                                        <?= date('M d, Y', strtotime($f['created_at'])) ?>
                                    </span>
                                </div>

                                <div class="folder-card-footer">
                                    <a href="medialibrary.php?folder_id=<?= $f['id'] ?>" class="btn-open-album">
                                        Open Album <i class="bi bi-chevron-right"></i>
                                    </a>
                                    <button type="button" class="btn-card-trash" title="Delete Folder" onclick="confirmDeleteFolder(<?= $f['id'] ?>, '<?= htmlspecialchars(addslashes($f['name'])) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="media-container-white">
                        <div class="empty-state">
                            <div class="empty-icon-box"><i class="bi bi-folder-plus"></i></div>
                            <h3>No folders created yet</h3>
                            <p>Organize your media library by creating category folders like "Meetings", "Sports", or "Events".</p>
                            <button type="button" class="btn-create-folder" onclick="openModal('createFolderModal')">
                                <i class="bi bi-folder-plus"></i> + Create Folder
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

        </main>
    </div>

    <!-- ======================================================== -->
    <!-- MODALS                                                   -->
    <!-- ======================================================== -->

    <!-- 1. Create Folder Modal -->
    <div id="createFolderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="bi bi-folder-plus"></i> Create New Folder</h2>
                <span class="close-modal" onclick="closeModal('createFolderModal')">&times;</span>
            </div>
            <form action="medialibrary.php" method="POST">
                <input type="hidden" name="action" value="create_folder">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Folder Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="folder_name" class="form-control" required placeholder="e.g. Meetings, Sports Fest">
                    </div>
                    <div class="form-group">
                        <label>Description (Optional)</label>
                        <textarea name="folder_description" class="form-control" rows="3" placeholder="Briefly describe what photos this folder contains..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('createFolderModal')">Cancel</button>
                    <button type="submit" class="btn-submit"><i class="bi bi-check-lg"></i> Create Folder</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. Edit / Rename Folder Modal -->
    <div id="editFolderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="bi bi-pencil-square"></i> Edit Folder Details</h2>
                <span class="close-modal" onclick="closeModal('editFolderModal')">&times;</span>
            </div>
            <form action="medialibrary.php" method="POST">
                <input type="hidden" name="action" value="edit_folder">
                <input type="hidden" name="folder_id" id="editFolderId" value="">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Folder Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="editFolderName" name="folder_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea id="editFolderDesc" name="folder_description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('editFolderModal')">Cancel</button>
                    <button type="submit" class="btn-submit"><i class="bi bi-check-lg"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. Upload Images Modal -->
    <?php if ($current_folder): ?>
    <div id="uploadMediaModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h2><i class="bi bi-cloud-arrow-up-fill"></i> Upload Images to "<?= htmlspecialchars($current_folder['name']) ?>"</h2>
                <span class="close-modal" onclick="closeModal('uploadMediaModal')">&times;</span>
            </div>
            <form action="medialibrary.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_media">
                <input type="hidden" name="folder_id" value="<?= $current_folder['id'] ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Select Photos <span style="color:#ef4444;">*</span></label>
                        <div class="upload-dropzone" onclick="document.getElementById('mediaFileInput').click()">
                            <i class="bi bi-cloud-arrow-up dropzone-icon"></i>
                            <p class="dropzone-text">Click or drag images here to select files</p>
                            <span class="dropzone-hint">Supports JPG, PNG, WEBP, GIF (Multiple photos supported)</span>
                            <input type="file" id="mediaFileInput" name="media_files[]" multiple accept="image/*" style="display:none;" required onchange="handleFileSelect(this)">
                        </div>
                        <div id="selectedFilesList" class="selected-files-list"></div>
                    </div>
                    <div class="form-group">
                        <label>Common Caption / Title (Optional)</label>
                        <input type="text" name="caption" class="form-control" placeholder="e.g., Barangay Assembly 2026">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('uploadMediaModal')">Cancel</button>
                    <button type="submit" class="btn-submit"><i class="bi bi-cloud-arrow-up-fill"></i> Start Upload</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- 4. Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="bi bi-exclamation-triangle-fill" style="color:#ef4444;"></i> Confirm Deletion</h2>
                <span class="close-modal" onclick="closeModal('deleteConfirmModal')">&times;</span>
            </div>
            <form action="medialibrary.php" method="POST">
                <input type="hidden" name="action" id="deleteAction" value="">
                <input type="hidden" name="folder_id" id="deleteFolderId" value="">
                <input type="hidden" name="media_id" id="deleteMediaId" value="">
                <div class="modal-body">
                    <p id="deleteConfirmMessage" style="font-size: 0.95rem; color:#475569; margin:0; line-height:1.5;">Are you sure?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('deleteConfirmModal')">Cancel</button>
                    <button type="submit" class="btn-danger-submit"><i class="bi bi-trash3"></i> Yes, Delete</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 5. Fullscreen Lightbox Modal -->
    <div id="lightboxModal" class="lightbox-modal" onclick="closeLightbox()">
        <span class="lightbox-close">&times;</span>
        <div class="lightbox-container" onclick="event.stopPropagation()">
            <img src="" alt="Fullscreen preview" id="lightboxImg" class="lightbox-img">
            <div id="lightboxCaption" class="lightbox-caption"></div>
        </div>
    </div>

    <!-- Interactive Scripts -->
    <script>
    function openModal(id) { const m = document.getElementById(id); if (m) m.style.display = 'flex'; }
    function closeModal(id) { const m = document.getElementById(id); if (m) m.style.display = 'none'; }
    window.onclick = function(e) { if (e.target.classList.contains('modal')) e.target.style.display = 'none'; };

    function openEditFolderModal(id, name, desc) {
        document.getElementById('editFolderId').value = id;
        document.getElementById('editFolderName').value = name;
        document.getElementById('editFolderDesc').value = desc;
        openModal('editFolderModal');
    }

    function confirmDeleteFolder(id, name) {
        document.getElementById('deleteAction').value = 'delete_folder';
        document.getElementById('deleteFolderId').value = id;
        document.getElementById('deleteMediaId').value = '';
        document.getElementById('deleteConfirmMessage').innerHTML = 
            'Are you sure you want to delete the folder "<strong>' + name + '</strong>"? <br><span style="color:#ef4444; font-size:0.85rem;">All images inside this folder will also be permanently deleted from the system and disk.</span>';
        openModal('deleteConfirmModal');
    }

    function confirmDeleteMedia(mediaId, folderId) {
        document.getElementById('deleteAction').value = 'delete_media';
        document.getElementById('deleteFolderId').value = folderId;
        document.getElementById('deleteMediaId').value = mediaId;
        document.getElementById('deleteConfirmMessage').innerHTML = 'Are you sure you want to permanently delete this photo?';
        openModal('deleteConfirmModal');
    }

    function handleFileSelect(input) {
        const listContainer = document.getElementById('selectedFilesList');
        listContainer.innerHTML = '';
        if (input.files && input.files.length > 0) {
            for (let i = 0; i < input.files.length; i++) {
                const f = input.files[i];
                const item = document.createElement('div');
                item.className = 'selected-file-chip';
                item.innerHTML = '<i class="bi bi-image" style="color:#0f766e;"></i> ' + f.name + ' (' + (f.size / 1024).toFixed(1) + ' KB)';
                listContainer.appendChild(item);
            }
        }
    }

    // Drag and Drop enhancements on Dropzone
    const dropzone = document.querySelector('.upload-dropzone');
    if (dropzone) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, () => dropzone.style.background = '#ccfbf1', false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, () => dropzone.style.background = '#f0fdfa', false);
        });

        dropzone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            const fileInput = document.getElementById('mediaFileInput');
            if (fileInput && files.length > 0) {
                fileInput.files = files;
                handleFileSelect(fileInput);
            }
        });
    }

    function viewLightbox(src, caption, folder, date) {
        const lightbox = document.getElementById('lightboxModal');
        const img = document.getElementById('lightboxImg');
        const cap = document.getElementById('lightboxCaption');
        if (lightbox && img) {
            img.src = src;
            let captionHtml = `<div style="font-size:1.25rem; font-weight:700; color:#ffffff; margin-bottom:8px; font-family:'Montserrat',sans-serif; text-shadow:0 2px 5px rgba(0,0,0,0.85);">${caption}</div>`;
            if (folder || date) {
                captionHtml += `<div style="display:inline-flex; align-items:center; gap:10px; justify-content:center; flex-wrap:wrap;">`;
                if (folder) {
                    captionHtml += `<span style="display:inline-flex; align-items:center; gap:5px; padding:4px 12px; border-radius:50px; border:1.5px solid #f59e0b; background:rgba(245,158,11,0.18); color:#fbbf24; font-size:0.75rem; font-weight:800; letter-spacing:0.5px; text-transform:uppercase;"><i class="fa-solid fa-folder"></i> ${folder}</span>`;
                }
                if (date) {
                    captionHtml += `<span style="display:inline-flex; align-items:center; gap:6px; padding:5px 12px; border-radius:50px; background:rgba(15,23,42,0.88); border:1px solid rgba(255,255,255,0.15); color:#f8fafc; font-size:0.75rem; font-weight:600;"><i class="fa-regular fa-clock" style="color:#fbbf24;"></i> ${date}</span>`;
                }
                captionHtml += `</div>`;
            }
            cap.innerHTML = captionHtml;
            lightbox.style.display = 'flex';
        }
    }

    function closeLightbox() {
        const lightbox = document.getElementById('lightboxModal');
        if (lightbox) lightbox.style.display = 'none';
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeLightbox();
            document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
        }
    });
    </script>
</body>
</html>
