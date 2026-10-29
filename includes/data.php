<?php
/**
 * Data store for the Services section on Index.php.
 * Dynamically queries active services from the database (as in service.php),
 * resolving uploaded document template images, and providing clean fallbacks.
 */
global $conn;

if (!isset($conn) || !($conn instanceof mysqli)) {
    $dbFile = dirname(__DIR__) . '/db_connect.php';
    if (file_exists($dbFile)) {
        require_once $dbFile;
    }
}

$servicesList = [];
if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    try {
        $res = $conn->query("SELECT * FROM services WHERE status = 'active' ORDER BY display_order ASC, id ASC LIMIT 6");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $servicesList[] = $row;
            }
        }
    } catch (Exception $e) {}
}

// Fallback if no services exist in the database
if (empty($servicesList)) {
    $servicesList = [
        [
            'id' => 1,
            'name' => 'Barangay Clearance',
            'description' => 'Official certification required for job applications, postal ID, police clearance, and general legal verification.',
            'fee' => '₱50.00',
            'processing_time' => 'Same Day',
            'document_file' => null
        ],
        [
            'id' => 2,
            'name' => 'Certificate of Indigency',
            'description' => 'Issued to low-income residents for medical, educational, burial, or legal assistance from DSWD and other public agencies.',
            'fee' => 'Free',
            'processing_time' => 'Same Day',
            'document_file' => null
        ],
        [
            'id' => 3,
            'name' => 'Certificate of Residency',
            'description' => 'Formal verification confirming that a citizen has been a bonafide continuous resident of Barangay Tabon.',
            'fee' => 'Free',
            'processing_time' => 'Same Day',
            'document_file' => null
        ]
    ];
}

$items = [];
$defaultTemplate = 'assets/img/default_template.png';
if (!file_exists(dirname(__DIR__) . '/' . $defaultTemplate)) {
    $defaultTemplate = 'assets/img/stone.jpg';
}

foreach ($servicesList as $idx => $svc) {
    $numStr = sprintf('%02d', $idx + 1);
    $serviceName = trim($svc['name'] ?? ('Service ' . $numStr));
    
    // Resolve template image path
    $imgPath = $defaultTemplate;
    if (!empty($svc['document_file'])) {
        $cleanDoc = ltrim($svc['document_file'], '/\\');
        $candidate = 'Admin/' . $cleanDoc;
        if (file_exists(dirname(__DIR__) . '/' . $candidate)) {
            $imgPath = $candidate;
        } elseif (file_exists(dirname(__DIR__) . '/' . $cleanDoc)) {
            $imgPath = $cleanDoc;
        }
    }

    $items[] = [
        'id'              => 'art-item-' . $numStr,
        'db_id'           => $svc['id'] ?? ($idx + 1),
        'number'          => $numStr,
        'caption'         => $numStr . ' / ' . strtoupper($serviceName),
        'title'           => $serviceName,
        'description'     => !empty($svc['description']) ? $svc['description'] : 'Official barangay document and citizen service provided by Barangay Tabon.',
        'fee'             => $svc['fee'] ?? '',
        'processing_time' => $svc['processing_time'] ?? '',
        'image'           => $imgPath,
        'alt'             => $serviceName . ' Sample Template Preview'
    ];
}

return [
    'eyebrow' => 'FRONTLINE CITIZEN SERVICES',
    'headline' => [
        'line1'         => 'Prompt, honest',
        'accent_prefix' => 'public ',
        'accent_word'   => 'service',
        'accent_suffix' => '.'
    ],
    'intro' => 'Official certifications, community clearances, and prompt citizen assistance delivered with transparent governance.',
    'fixed_caption' => '',
    'items' => $items
];
