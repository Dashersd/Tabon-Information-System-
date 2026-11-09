<?php
// api/verify_id_card.php - Validates uploaded ID Card and delivers request to feedback_chats
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit();
}

$fullName     = trim($_POST['fullName'] ?? '');
$email        = trim($_POST['email'] ?? '');
$phone        = trim($_POST['phone'] ?? '');
$service_type = trim($_POST['service_type'] ?? 'Contact Inquiry');
$message      = trim($_POST['message'] ?? '');
$id_type      = trim($_POST['id_type'] ?? 'Valid Government / Student ID');
$ocr_valid    = isset($_POST['ocr_valid']) && $_POST['ocr_valid'] === '1';
$ocr_text     = strtolower(trim($_POST['ocr_text'] ?? ''));

// Validate required fields
if (empty($fullName) || empty($email) || empty($message)) {
    echo json_encode(['status' => 'error', 'message' => 'Please provide your Full Name, Email, and Message.']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid Gmail / Email address.']);
    exit();
}

// Check uploaded file
if (empty($_FILES['id_card']) || $_FILES['id_card']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Please select and upload a clear photo of your ID Card.']);
    exit();
}

$file = $_FILES['id_card'];
$maxBytes = 10 * 1024 * 1024; // 10MB
$minBytes = 4 * 1024;         // 4KB

if ($file['size'] > $maxBytes) {
    echo json_encode(['status' => 'error', 'message' => 'The uploaded ID image is too large. Maximum size is 10 MB.']);
    exit();
}

if ($file['size'] < $minBytes) {
    echo json_encode(['status' => 'error', 'message' => 'The uploaded file is too small or blank. Please upload a clear photo of your ID card.']);
    exit();
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
if (!in_array($ext, $allowedExts)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid file format. Please upload an image file (JPG, PNG, or WEBP).']);
    exit();
}

// Validate MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($mime, $allowedMimes)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid image format detected. Please upload a valid ID photo.']);
    exit();
}

// Validate image dimensions via getimagesize
$imgInfo = @getimagesize($file['tmp_name']);
if (!$imgInfo) {
    echo json_encode(['status' => 'error', 'message' => 'Could not process the uploaded image. Please ensure it is not corrupted.']);
    exit();
}

$width  = $imgInfo[0];
$height = $imgInfo[1];

if ($width < 320 || $height < 200) {
    echo json_encode(['status' => 'error', 'message' => 'The image resolution is too low. Please upload a clear, legible photo of your ID card (at least 320x200 pixels).']);
    exit();
}

// 1. Enforce Landscape Orientation (All valid Philippine ID cards and Student IDs are landscape)
if ($height >= $width) {
    echo json_encode([
        'status'  => 'error',
        'code'    => 'PORTRAIT_REJECTED',
        'message' => 'Verification Rejected: The uploaded photo is portrait-oriented. ID cards must be a horizontal (landscape) photo of an actual card. Personal portraits, selfies, or standing photos cannot be accepted.'
    ]);
    exit();
}

// 2. Enforce standard ID card aspect ratio (ISO/IEC 7810 ID-1 standard is 1.58)
$aspect = $width / $height;
if ($aspect < 1.20 || $aspect > 2.25) {
    echo json_encode([
        'status'  => 'error',
        'code'    => 'INVALID_CARD_PROPORTION',
        'message' => 'Verification Rejected: The uploaded image does not match the proportions of an ID card. Please upload a clear, horizontal photo of your physical ID card.'
    ]);
    exit();
}

// 3. Strict Verification of ID Card Keywords from OCR
$primaryIdKeywords = [
    'national id', 'philid', 'philsys', 'driver', 'license', 'lisensya', 'lto', 
    'land transportation', 'umid', 'sss', 'gsis', 'tin', 'bir', 'voter', 
    'comelec', 'postal', 'philpost', 'prc', 'passport', 'barangay', 'student id',
    'student', 'university', 'college', 'school', 'academy', 'osca', 'pwd', 
    'senior citizen', 'republic of the philippines', 'republika ng pilipinas',
    'identification card', 'identity card', 'philhealth', 'pag ibig', 'pag-ibig'
];

$secondaryIdKeywords = [
    'philippines', 'pilipinas', 'republic', 'republika', 'pamahalaan', 'surname', 
    'given name', 'middle name', 'apelyido', 'pangalan', 'date of birth', 'birthdate', 
    'kapanganakan', 'birth', 'dob', 'sex', 'kasarian', 'address', 'tirahan', 'blood', 
    'blood type', 'signature', 'lagda', 'id no', 'id number', 'crn', 'tin no', 
    'license no', 'precinct', 'student no', 'lrn', 'control no', 'valid until', 
    'expiration', 'date issued', 'citizenship', 'nationality', 'cardholder'
];

$matchedPrimary = [];
$matchedSecondary = [];

if (!empty($ocr_text)) {
    foreach ($primaryIdKeywords as $pkw) {
        if (strpos($ocr_text, $pkw) !== false) {
            $matchedPrimary[] = $pkw;
        }
    }
    foreach ($secondaryIdKeywords as $skw) {
        if (strpos($ocr_text, $skw) !== false) {
            $matchedSecondary[] = $skw;
        }
    }
}

$hasCardNumber = preg_match('/[0-9]{4,}/', $ocr_text);
$totalMatches = count($matchedPrimary) + count($matchedSecondary);

// An ID is verified ONLY if:
// - It has at least 1 primary card issuer keyword + 1 secondary field/number, OR
// - It has at least 2 primary keywords, OR
// - It has at least 3 valid ID keywords overall
$isCredibleId = (count($matchedPrimary) >= 1 && ($totalMatches >= 2 || $hasCardNumber)) ||
                (count($matchedPrimary) >= 2) ||
                ($totalMatches >= 3);

if (!$isCredibleId) {
    echo json_encode([
        'status'  => 'error',
        'code'    => 'INVALID_ID_CREDENTIALS',
        'message' => 'Verification Rejected: No official government or student ID credentials were detected in this image. Random portraits, selfies, landscapes, or unrelated photos cannot be accepted. Please upload a clear photo of your actual ID card.'
    ]);
    exit();
}

try {
    // Ensure uploads/verified_ids/ directory exists
    $uploadDir = __DIR__ . '/../uploads/verified_ids/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    $uniqueFilename = 'id_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $destination = $uploadDir . $uniqueFilename;

    $saved = is_uploaded_file($file['tmp_name']) ? move_uploaded_file($file['tmp_name'], $destination) : copy($file['tmp_name'], $destination);
    if (!$saved) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save the uploaded ID card. Please try again.']);
        exit();
    }

    $relativeAttachmentPath = 'uploads/verified_ids/' . $uniqueFilename;
    $cleanClientName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fullName);
    $displayFileName = 'Verified_ID_' . $cleanClientName . '.' . $ext;

    // 0. Ensure feedback_chats table exists
    $conn->query("CREATE TABLE IF NOT EXISTS `feedback_chats` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `thread_id` varchar(100) NOT NULL,
      `sender_name` varchar(255) NOT NULL,
      `email` varchar(150) DEFAULT NULL,
      `sender_type` enum('user','admin') NOT NULL DEFAULT 'user',
      `message` text NOT NULL,
      `file_attachment` varchar(255) DEFAULT NULL,
      `file_name` varchar(255) DEFAULT NULL,
      `is_read` tinyint(1) DEFAULT 0,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `idx_thread_id` (`thread_id`),
      KEY `idx_sender_type_read` (`sender_type`, `is_read`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Self-healing: Fix any zero ID and ensure AUTO_INCREMENT is active for feedback_chats
    try {
        $chatZero = $conn->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM `feedback_chats`");
        $fix_chat = ($chatZero && $cRow = $chatZero->fetch_assoc()) ? (int)$cRow['next_id'] : 1;
        $conn->query("UPDATE `feedback_chats` SET `id` = $fix_chat WHERE `id` = 0");
        $conn->query("ALTER TABLE `feedback_chats` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT");
    } catch (\Throwable $fixEx) {}

    // Calculate safe next ID for feedback_chats
    $maxChatRes = $conn->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM `feedback_chats`");
    $assigned_chat_id = ($maxChatRes && $cr = $maxChatRes->fetch_assoc()) ? (int)$cr['next_id'] : 1;

    $thread_id = 'thread_' . bin2hex(random_bytes(16));
    $service_tag = !empty($service_type) ? $service_type : 'Contact Inquiry';

    $full_message = "[Service: " . $service_tag . "]\n" .
                    "[Verification Method: Valid ID Card Reader (" . $id_type . ")]\n\n" .
                    $message .
                    (!empty($phone) ? "\n\nContact Phone: " . $phone : "");

    $chat_stmt = $conn->prepare("INSERT INTO `feedback_chats` 
        (`id`, `thread_id`, `sender_name`, `email`, `sender_type`, `message`, `file_attachment`, `file_name`, `is_read`, `created_at`) 
        VALUES (?, ?, ?, ?, 'user', ?, ?, ?, 0, NOW())");

    if (!$chat_stmt) {
        throw new Exception("Chat prepare statement failed: " . $conn->error);
    }

    $chat_stmt->bind_param("issssss", $assigned_chat_id, $thread_id, $fullName, $email, $full_message, $relativeAttachmentPath, $displayFileName);

    if (!$chat_stmt->execute()) {
        throw new Exception("Chat execution failed: " . $chat_stmt->error);
    }
    $chat_stmt->close();

    // Mark any existing pending email_verifications record as verified
    try {
        $upd = $conn->prepare("UPDATE `email_verifications` SET `is_verified` = 1 WHERE `email` = ? AND `is_verified` = 0");
        if ($upd) {
            $upd->bind_param("s", $email);
            $upd->execute();
            $upd->close();
        }
    } catch (\Throwable $th) {}

    // Success response requested by user
    echo json_encode([
        'status'  => 'success',
        'message' => 'The Admin has received your request and wait for 3 to 5 business days to be finished and sent to your Gmail.',
        'attachment' => $relativeAttachmentPath
    ]);

} catch (\Throwable $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'ID verification submission failed: ' . $e->getMessage()
    ]);
}
