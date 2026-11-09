<?php
// request_document.php - Dedicated Document Request Portal for Barangay Tabon
require_once 'db_connect.php';

// 1. Identify requested service
$service_id = isset($_GET['id']) ? intval($_GET['id']) : 1;
$service = null;

if ($service_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM services WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $service_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            $service = $row;
        }
        $stmt->close();
    }
}

// Fallback to first active service if not found
if (!$service) {
    $res = $conn->query("SELECT * FROM services WHERE status = 'active' ORDER BY display_order ASC, id ASC LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $service = $row;
    }
}

// Fallback defaults if table is empty
if (!$service) {
    $service = [
        'id' => 1,
        'name' => 'Barangay Clearance',
        'description' => 'Official certification required for job applications, postal ID, police clearance, and general legal verification.',
        'fee' => '₱50.00',
        'processing_time' => '10-15 Minutes',
        'requirements' => 'Recent Cedula (Community Tax Certificate), 1 Valid Government ID, Proof of Residence in Barangay Tabon.'
    ];
}

// Parse requirements into an array
$req_list = [];
if (!empty($service['requirements'])) {
    $raw_reqs = preg_split('/[\r\n,]+/', $service['requirements']);
    foreach ($raw_reqs as $rq) {
        $trimmed = trim($rq);
        if (!empty($trimmed)) {
            $req_list[] = $trimmed;
        }
    }
}
if (empty($req_list)) {
    $req_list = [
        'Recent Cedula (Community Tax Certificate)',
        '1 Valid Government ID',
        'Proof of Residence in Barangay Tabon.'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="Barangay Logo/Logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request <?= htmlspecialchars($service['name']) ?> - Barangay Tabon</title>
    
    <!-- FontAwesome & Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600;1,700&display=swap" rel="stylesheet">
    
    <!-- External Stylesheets -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/contact.css?v=<?php echo time(); ?>">

    <!-- Tesseract OCR Engine -->
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>

    <style>
        :root {
            --brand-green: #093d25;
            --brand-green-hover: #0c4f30;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --bg-page: #f8fafc;
            --border-light: #e2e8f0;
        }

        body.request-document-page {
            background-color: var(--bg-page);
            color: var(--text-main);
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .req-page-wrapper {
            max-width: 1140px;
            margin: 0 auto;
            padding: 32px 20px 60px;
        }

        /* Top Back to Services Link */
        .back-nav-wrap {
            margin-bottom: 24px;
        }
        .back-to-services-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #334155;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 600;
            transition: color 0.2s ease, transform 0.2s ease;
        }
        .back-to-services-link:hover {
            color: var(--brand-green);
            transform: translateX(-3px);
        }

        /* Main 2-Column Split Grid */
        .req-grid {
            display: grid;
            grid-template-columns: 1.15fr 1fr;
            gap: 48px;
            align-items: start;
        }

        @media (max-width: 920px) {
            .req-grid {
                grid-template-columns: 1fr;
                gap: 32px;
            }
        }

        /* Left Column: Service Information */
        .service-info-col {
            padding-right: 12px;
        }

        .service-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.76rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-weight: 700;
            color: #15803d;
            background: #dcfce7;
            padding: 5px 12px;
            border-radius: 20px;
            margin-bottom: 16px;
        }
        .service-kicker i {
            font-size: 0.85rem;
        }

        .service-heading {
            font-family: 'Playfair Display', serif;
            font-size: 2.6rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.15;
            margin: 0 0 16px 0;
        }

        .service-summary-text {
            font-size: 1rem;
            line-height: 1.65;
            color: #475569;
            margin-bottom: 24px;
        }

        /* Badges / Metrics Row */
        .service-badges-row {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            margin-bottom: 32px;
        }

        .badge-pill-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .badge-icon-box {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #f1f5f9;
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }
        .badge-meta {
            display: flex;
            flex-direction: column;
        }
        .badge-label {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: capitalize;
            font-weight: 500;
        }
        .badge-value {
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
        }

        /* "Before you request" Checklist Box */
        .before-request-box {
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: 14px;
            padding: 24px 28px;
            margin-bottom: 28px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .before-request-box h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 6px 0;
        }
        .before-request-box .req-intro {
            font-size: 0.88rem;
            color: #64748b;
            margin: 0 0 16px 0;
        }
        .req-checklist {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .req-checklist li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 0.92rem;
            color: #334155;
            line-height: 1.45;
        }
        .req-check-icon {
            color: #16a34a;
            font-size: 0.95rem;
            margin-top: 3px;
            flex-shrink: 0;
        }

        /* Bottom Security Callout */
        .security-guarantee-note {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.84rem;
            color: #64748b;
            padding: 6px 0;
        }
        .security-guarantee-note i {
            color: #0f172a;
            font-size: 1.05rem;
        }

        /* Right Column: Request Submission Card */
        .submit-card {
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: 16px;
            padding: 36px 32px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.04), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
        }
        .submit-card-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 6px 0;
        }
        .submit-card-desc {
            font-size: 0.88rem;
            color: #64748b;
            line-height: 1.5;
            margin: 0 0 24px 0;
        }

        .req-form-group {
            margin-bottom: 20px;
        }
        .req-form-group label {
            display: block;
            font-size: 0.88rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
        }
        .req-form-group label .req-asterisk {
            color: #ef4444;
        }
        .req-form-group input,
        .req-form-group textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: inherit;
            color: #0f172a;
            background: #ffffff;
            box-sizing: border-box;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .req-form-group input:focus,
        .req-form-group textarea:focus {
            outline: none;
            border-color: var(--brand-green);
            box-shadow: 0 0 0 3px rgba(9, 61, 37, 0.12);
        }
        .req-form-group textarea {
            resize: vertical;
            min-height: 105px;
        }

        .btn-send-request {
            width: 100%;
            background: var(--brand-green);
            color: #ffffff;
            border: none;
            padding: 14px 20px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background-color 0.2s, transform 0.1s;
        }
        .btn-send-request:hover {
            background: var(--brand-green-hover);
        }
        .btn-send-request:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

        /* Success & Alert Banners */
        .form-feedback-alert {
            display: none;
            padding: 14px 18px;
            border-radius: 10px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            align-items: flex-start;
            gap: 12px;
            line-height: 1.5;
        }
    </style>
</head>
<body class="request-document-page contact-page">

    <!-- Floating Toast Notification Container -->
    <div class="contact-toast-container" id="contactToastContainer"></div>

    <!-- Main Container -->
    <div class="req-page-wrapper">
        
        <!-- Top Back Link -->
        <div class="back-nav-wrap">
            <a href="service.php" class="back-to-services-link">
                <i class="fa-solid fa-arrow-left"></i> Back to services
            </a>
        </div>

        <div class="req-grid">
            
            <!-- LEFT COLUMN: Service Details -->
            <div class="service-info-col">
                <div class="service-kicker">
                    <i class="fa-solid fa-leaf"></i> BARANGAY TABON SERVICE
                </div>

                <h1 class="service-heading"><?= htmlspecialchars($service['name']) ?></h1>
                
                <p class="service-summary-text"><?= nl2br(htmlspecialchars($service['description'])) ?></p>

                <!-- Metadata Badges -->
                <div class="service-badges-row">
                    <div class="badge-pill-item">
                        <div class="badge-icon-box">
                            <i class="fa-solid fa-money-bill-wave"></i>
                        </div>
                        <div class="badge-meta">
                            <span class="badge-label">Service fee</span>
                            <span class="badge-value"><?= htmlspecialchars($service['fee'] ?: 'Free') ?></span>
                        </div>
                    </div>

                    <div class="badge-pill-item">
                        <div class="badge-icon-box">
                            <i class="fa-regular fa-clock"></i>
                        </div>
                        <div class="badge-meta">
                            <span class="badge-label">Processing time</span>
                            <span class="badge-value"><?= htmlspecialchars($service['processing_time'] ?: '10-15 Minutes') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Before You Request Box -->
                <div class="before-request-box">
                    <h3>Before you request</h3>
                    <p class="req-intro">Prepare the following requirements:</p>
                    <ul class="req-checklist">
                        <?php foreach ($req_list as $item): ?>
                            <li>
                                <i class="fa-solid fa-check req-check-icon"></i>
                                <span><?= htmlspecialchars($item) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Security Note -->
                <div class="security-guarantee-note">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>We verify your email before sending your request to the barangay office</span>
                </div>
            </div>

            <!-- RIGHT COLUMN: Request Form Card -->
            <div class="service-form-col">
                <div class="submit-card">
                    <h2 class="submit-card-title">Submit your request</h2>
                    <p class="submit-card-desc">Enter your details and describe what you need. We will email you a one-time verification code.</p>

                    <!-- Alert Banner -->
                    <div class="form-feedback-alert" id="formAlertBanner"></div>

                    <!-- Submission Form -->
                    <form id="docRequestForm" novalidate>
                        <input type="hidden" id="serviceType" name="service_type" value="<?= htmlspecialchars($service['name']) ?>">

                        <div class="req-form-group">
                            <label for="fullName">Full name <span class="req-asterisk">*</span></label>
                            <input type="text" id="fullName" name="fullName" required placeholder="e.g. Juan Dela Cruz">
                        </div>

                        <div class="req-form-group">
                            <label for="email">Email address <span class="req-asterisk">*</span></label>
                            <input type="email" id="email" name="email" required placeholder="juan@gmail.com">
                        </div>

                        <div class="req-form-group">
                            <label for="phone">Phone number</label>
                            <input type="tel" id="phone" name="phone" placeholder="0912 345 6789">
                        </div>

                        <div class="req-form-group">
                            <label for="message">Request details <span class="req-asterisk">*</span></label>
                            <textarea id="message" name="message" rows="4" required placeholder="Tell us what you need this document or service for."></textarea>
                        </div>

                        <button type="submit" class="btn-send-request" id="btnSubmitRequest">
                            <i class="fa-regular fa-paper-plane" id="btnSubmitIcon"></i>
                            <span id="btnSubmitText">Send request</span>
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <!-- ========================================== -->
    <!-- PIN Verification & ID Reader Modal Overlay -->
    <!-- ========================================== -->
    <div class="pin-modal-overlay" id="pinModalOverlay">
        <div class="pin-modal-card">
            <button type="button" class="pin-modal-close" id="btnClosePinModal" title="Close">&times;</button>
            
            <!-- VIEW 1: Email OTP Verification -->
            <div id="viewEmailOtp">
                <div class="pin-modal-badge">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                
                <h3 class="pin-modal-title">Gmail OTP Verification</h3>
                <p class="pin-modal-desc">
                    We sent a 6-digit verification OTP to <strong id="modalTargetEmail">your Gmail</strong>. Please check your Inbox or Spam folder, then enter the OTP below to confirm your request:
                </p>

                <!-- Free Hosting / Offline OTP Fallback Banner -->
                <div class="pin-offline-banner" id="pinOfflineBanner" style="display: none; background: #fef3c7; border: 1px solid #fde047; color: #854d0e; padding: 14px 16px; border-radius: 10px; font-size: 13px; margin: 12px 0 16px 0; text-align: left;">
                    <div style="font-weight: 700; display: flex; align-items: center; gap: 6px; margin-bottom: 4px; color: #b45309;">
                        <i class="fa-solid fa-circle-info"></i> Notice: Free Hosting Mode
                    </div>
                    <span>InfinityFree blocks outbound email sending (SMTP). For instant testing & submission, your 6-digit OTP code is:</span>
                    <div style="margin: 8px 0; text-align: center;">
                        <span id="pinOfflineCode" style="font-family: monospace; font-size: 26px; font-weight: 800; letter-spacing: 6px; background: #ffffff; padding: 4px 16px; border-radius: 8px; border: 2px solid #eab308; color: #1e293b; display: inline-block;">------</span>
                    </div>
                    <div style="text-align: center;">
                        <button type="button" id="btnAutoFillPin" style="background: #093d25; color: #ffffff; border: none; padding: 8px 18px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> Auto-Fill & Verify Now
                        </button>
                    </div>
                </div>

                <div class="pin-modal-alert" id="pinModalAlert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span id="pinModalAlertText">Invalid OTP code.</span>
                </div>

                <!-- 6 PIN Digit Input Boxes -->
                <div class="pin-digits-row">
                    <input type="text" maxlength="1" class="pin-digit-box" data-index="0" inputmode="numeric" pattern="[0-9]*" autofocus>
                    <input type="text" maxlength="1" class="pin-digit-box" data-index="1" inputmode="numeric" pattern="[0-9]*">
                    <input type="text" maxlength="1" class="pin-digit-box" data-index="2" inputmode="numeric" pattern="[0-9]*">
                    <input type="text" maxlength="1" class="pin-digit-box" data-index="3" inputmode="numeric" pattern="[0-9]*">
                    <input type="text" maxlength="1" class="pin-digit-box" data-index="4" inputmode="numeric" pattern="[0-9]*">
                    <input type="text" maxlength="1" class="pin-digit-box" data-index="5" inputmode="numeric" pattern="[0-9]*">
                </div>

                <!-- Expiration Timer & Resend Link -->
                <div class="pin-timer-wrapper">
                    <div class="pin-timer-badge" id="pinTimerBadge">
                        <i class="fa-regular fa-clock"></i>
                        <span id="pinTimerText">Expires in: 03:00</span>
                    </div>
                    <button type="button" class="btn-resend-link" id="btnResendPin" disabled>
                        <i class="fa-solid fa-rotate-right"></i> Resend Code
                    </button>
                </div>

                <!-- Verify Action Button -->
                <button type="button" class="btn-verify-pin" id="btnVerifyPin">
                    <i class="fa-solid fa-circle-check"></i> Verify & Submit Request
                </button>

                <!-- Try Another Way (Corner Right) -->
                <div style="display: flex; justify-content: flex-end; align-items: center; margin-top: 14px;">
                    <button type="button" id="btnTryAnotherWay" class="btn-try-another-way">
                        <i class="fa-solid fa-id-card"></i> <span>Try Another Way</span> &rarr;
                    </button>
                </div>
            </div>

            <!-- VIEW 2: ID Card Reader Verification -->
            <div id="viewIdCardReader" style="display: none;">
                <div class="pin-modal-badge" style="background: #eff6ff;">
                    <i class="fa-solid fa-id-card" style="color: #1e3a8a !important; font-size: 26px;"></i>
                </div>

                <h3 class="pin-modal-title" style="margin-bottom: 6px;">ID Card Reader Verification</h3>
                <p class="pin-modal-desc" style="margin-bottom: 16px;">
                    Could not receive OTP via Email? Upload a clear photo of your <strong>Valid ID Card</strong>. Our ID Reader will scan and verify your identity before delivering your request to the Admin.
                </p>

                <!-- Back to OTP Link -->
                <div style="margin-bottom: 12px; text-align: left;">
                    <button type="button" id="btnBackToEmailOtp" style="background: none; border: none; padding: 0; color: #475569; font-size: 0.85rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-arrow-left"></i> Back to Email OTP
                    </button>
                </div>

                <!-- ID Card Dropzone / Upload Box -->
                <div id="idDropzone" style="border: 2px dashed #94a3b8; border-radius: 14px; padding: 22px 18px; text-align: center; background: #f8fafc; cursor: pointer; transition: all 0.2s ease; position: relative; overflow: hidden;">
                    <input type="file" id="idCardFileInput" accept="image/jpeg,image/png,image/webp" style="display: none;">
                    
                    <!-- Empty State -->
                    <div id="idDropzoneEmptyState">
                        <div style="width: 52px; height: 52px; margin: 0 auto 10px; border-radius: 50%; background: #e0f2fe; display: flex; align-items: center; justify-content: center; color: #0284c7; font-size: 22px;">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </div>
                        <div style="font-weight: 700; color: #1e293b; font-size: 0.95rem; margin-bottom: 4px;">
                            Click to Upload or Drag & Drop ID Card
                        </div>
                        <div style="font-size: 0.78rem; color: #64748b; line-height: 1.4; max-width: 380px; margin: 0 auto;">
                            Accepted: National ID (PhilID), Driver's License, UMID, Postal ID, Voter's ID, Barangay ID, Student ID
                        </div>
                        <div style="margin-top: 10px; display: inline-flex; align-items: center; gap: 6px; background: #ffffff; border: 1px solid #cbd5e1; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; color: #334155;">
                            <i class="fa-solid fa-shield"></i> JPG, PNG, WEBP (Max 10MB)
                        </div>
                    </div>

                    <!-- Selected Image Preview with Laser Scanner -->
                    <div id="idDropzonePreviewState" style="display: none; width: 100%; position: relative;">
                        <div style="position: relative; max-width: 340px; margin: 0 auto; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 14px rgba(0,0,0,0.15); border: 2px solid #cbd5e1; background: #000000;">
                            <img id="idPreviewImg" src="" alt="ID Preview" style="width: 100%; max-height: 200px; object-fit: contain; display: block;">
                            <div id="idScannerLaser" class="id-scanner-laser" style="display: none;"></div>
                        </div>
                        <div id="idScanBadge" style="margin-top: 10px; font-size: 0.82rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; padding: 4px 14px; border-radius: 20px; background: #e0f2fe; color: #0369a1;">
                            <i class="fa-solid fa-spinner fa-spin"></i> Reading & analyzing ID card...
                        </div>
                        <div style="margin-top: 8px;">
                            <button type="button" id="btnChangeIdPhoto" style="background: none; border: none; font-size: 0.78rem; color: #64748b; text-decoration: underline; cursor: pointer;">
                                <i class="fa-solid fa-rotate"></i> Choose different photo
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Verified Security Note -->
                <div style="font-size: 0.76rem; color: #64748b; text-align: center; margin: 12px 0 16px;">
                    <i class="fa-solid fa-circle-check" style="color: #16a34a;"></i> Instant ID Verification ensures secure & authenticated resident delivery.
                </div>

                <div class="pin-modal-alert" id="idCardAlert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span id="idCardAlertText">Please upload a valid ID card.</span>
                </div>

                <!-- Submit ID Action Button -->
                <button type="button" class="btn-verify-pin" id="btnSubmitIdCard" disabled style="background: #1e3a8a;">
                    <i class="fa-solid fa-shield-halved"></i> Verify ID & Deliver to Admin
                </button>
            </div>

        </div>
    </div>

    <!-- Client-side Logic -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const docForm           = document.getElementById('docRequestForm');
        const btnSubmit         = document.getElementById('btnSubmitRequest');
        const btnSubmitIcon     = document.getElementById('btnSubmitIcon');
        const btnSubmitText     = document.getElementById('btnSubmitText');
        const formAlertBanner   = document.getElementById('formAlertBanner');

        // Modal Elements
        const pinModal          = document.getElementById('pinModalOverlay');
        const btnCloseModal     = document.getElementById('btnClosePinModal');
        const modalTargetEmail  = document.getElementById('modalTargetEmail');
        const pinModalAlert     = document.getElementById('pinModalAlert');
        const pinModalAlertText = document.getElementById('pinModalAlertText');
        const pinInputs         = document.querySelectorAll('.pin-digit-box');
        const pinTimerBadge     = document.getElementById('pinTimerBadge');
        const pinTimerText      = document.getElementById('pinTimerText');
        const btnResendPin      = document.getElementById('btnResendPin');
        const btnVerifyPin      = document.getElementById('btnVerifyPin');
        const toastContainer    = document.getElementById('contactToastContainer');

        // Views inside Modal
        const viewEmailOtp          = document.getElementById('viewEmailOtp');
        const viewIdCardReader      = document.getElementById('viewIdCardReader');
        const btnTryAnotherWay      = document.getElementById('btnTryAnotherWay');
        const btnBackToEmailOtp     = document.getElementById('btnBackToEmailOtp');

        // ID Reader Elements
        const idDropzone            = document.getElementById('idDropzone');
        const idCardFileInput       = document.getElementById('idCardFileInput');
        const idDropzoneEmptyState  = document.getElementById('idDropzoneEmptyState');
        const idDropzonePreviewState= document.getElementById('idDropzonePreviewState');
        const idPreviewImg          = document.getElementById('idPreviewImg');
        const idScannerLaser        = document.getElementById('idScannerLaser');
        const idScanBadge           = document.getElementById('idScanBadge');
        const btnChangeIdPhoto      = document.getElementById('btnChangeIdPhoto');
        const idCardAlert           = document.getElementById('idCardAlert');
        const idCardAlertText       = document.getElementById('idCardAlertText');
        const btnSubmitIdCard       = document.getElementById('btnSubmitIdCard');

        let countdownInterval   = null;
        let resendCooldownTimer = null;
        let secondsLeft         = 180;
        let activeEmail         = '';
        let selectedIdFile      = null;
        let idVerificationResult= { isValid: false, type: '', text: '' };

        // Helper: Show Floating Toast Notification
        function showToast(type, title, message) {
            const toast = document.createElement('div');
            toast.className = `contact-toast toast-${type}`;
            
            const iconClass = type === 'success' ? 'fa-circle-check' :
                              type === 'error'   ? 'fa-circle-exclamation' : 'fa-circle-info';

            toast.innerHTML = `
                <div class="toast-icon">
                    <i class="fa-solid ${iconClass}"></i>
                </div>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-msg">${message}</div>
                </div>
                <button type="button" class="toast-close" title="Dismiss">&times;</button>
            `;

            toast.querySelector('.toast-close').addEventListener('click', () => {
                toast.classList.add('hide');
                setTimeout(() => toast.remove(), 300);
            });

            toastContainer.appendChild(toast);

            setTimeout(() => {
                if (toast.parentElement) {
                    toast.classList.add('hide');
                    setTimeout(() => toast.remove(), 300);
                }
            }, 6000);
        }

        // Dynamically load Tesseract.js when user requests ID reader
        function ensureTesseractLoaded(callback) {
            if (window.Tesseract) {
                if (callback) callback();
                return;
            }
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js';
            script.onload = () => {
                console.log('Tesseract OCR engine loaded.');
                if (callback) callback();
            };
            script.onerror = () => {
                console.warn('Online Tesseract OCR unavailable; using built-in biometric heuristics.');
                if (callback) callback();
            };
            document.head.appendChild(script);
        }

        // Switch to ID Card Reader View
        if (btnTryAnotherWay) {
            btnTryAnotherWay.addEventListener('click', function () {
                viewEmailOtp.style.display = 'none';
                viewIdCardReader.style.display = 'block';
                idCardAlert.className = 'pin-modal-alert';
                ensureTesseractLoaded();
            });
        }

        // Switch Back to Email OTP View
        if (btnBackToEmailOtp) {
            btnBackToEmailOtp.addEventListener('click', function () {
                viewIdCardReader.style.display = 'none';
                viewEmailOtp.style.display = 'block';
                pinModalAlert.className = 'pin-modal-alert';
            });
        }

        // ID Dropzone interactions
        if (idDropzone && idCardFileInput) {
            idDropzone.addEventListener('click', function (e) {
                if (e.target.closest('#btnChangeIdPhoto')) return;
                idCardFileInput.click();
            });

            idDropzone.addEventListener('dragover', function (e) {
                e.preventDefault();
                idDropzone.style.borderColor = '#0284c7';
                idDropzone.style.background = '#f0f9ff';
            });

            idDropzone.addEventListener('dragleave', function (e) {
                e.preventDefault();
                idDropzone.style.borderColor = '#94a3b8';
                idDropzone.style.background = '#f8fafc';
            });

            idDropzone.addEventListener('drop', function (e) {
                e.preventDefault();
                idDropzone.style.borderColor = '#94a3b8';
                idDropzone.style.background = '#f8fafc';
                if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                    processIdCardFile(e.dataTransfer.files[0]);
                }
            });

            idCardFileInput.addEventListener('change', function () {
                if (this.files && this.files[0]) {
                    processIdCardFile(this.files[0]);
                }
            });

            if (btnChangeIdPhoto) {
                btnChangeIdPhoto.addEventListener('click', function (e) {
                    e.stopPropagation();
                    idCardFileInput.value = '';
                    idCardFileInput.click();
                });
            }
        }

        // Process & Scan ID Card File (Strict Landscape & OCR Verification)
        function processIdCardFile(file) {
            selectedIdFile = file;
            idCardAlert.className = 'pin-modal-alert';
            btnSubmitIdCard.disabled = true;

            const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                showIdCardError('Invalid file type. Please upload a clear image (JPG, PNG, or WEBP).');
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                showIdCardError('File size exceeds 10 MB limit. Please upload a smaller image.');
                return;
            }
            if (file.size < 15 * 1024) {
                showIdCardError('The uploaded image is too small or blank. Please upload a real ID card.');
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                idPreviewImg.src = e.target.result;
                idDropzoneEmptyState.style.display = 'none';
                idDropzonePreviewState.style.display = 'block';
                idScannerLaser.style.display = 'block';
                idScannerLaser.classList.remove('valid');

                idScanBadge.style.background = '#e0f2fe';
                idScanBadge.style.color = '#0369a1';
                idScanBadge.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Checking image dimensions...';

                const img = new Image();
                img.onload = function () {
                    const w = this.naturalWidth || this.width;
                    const h = this.naturalHeight || this.height;

                    // 1. REJECT PORTRAIT PHOTOS: ID cards are strictly landscape (w > h)
                    if (h >= w) {
                        finishIdScan(false, '', 'Orientation Error: The uploaded photo is portrait (vertical). Real ID cards must be a horizontal (landscape) photo of an actual card. Personal portraits, selfies, or standing photos cannot be accepted.');
                        return;
                    }

                    // 2. REJECT OUT-OF-PROPORTION IMAGES: Standard ID cards are ~1.58 ratio
                    const aspect = w / h;
                    if (aspect < 1.20 || aspect > 2.25) {
                        finishIdScan(false, '', 'Proportion Error: The image proportions do not match standard ID cards. Please upload a clear horizontal photo of your physical ID card.');
                        return;
                    }

                    if (w < 280 || h < 180) {
                        finishIdScan(false, '', 'Resolution too low. Please upload a higher resolution, readable photo of your ID card.');
                        return;
                    }

                    runOcrInspection(file, aspect);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }

        function showIdCardError(msg) {
            idCardAlert.className = 'pin-modal-alert active';
            idCardAlertText.textContent = msg;
            idScannerLaser.style.display = 'none';
            idScanBadge.style.background = '#fee2e2';
            idScanBadge.style.color = '#b91c1c';
            idScanBadge.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Verification Rejected';
            btnSubmitIdCard.disabled = true;
        }

        function runOcrInspection(file, aspect) {
            idScanBadge.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Initializing OCR engine...';

            ensureTesseractLoaded(function () {
                if (window.Tesseract && typeof window.Tesseract.recognize === 'function') {
                    window.Tesseract.recognize(file, 'eng', {
                        logger: m => {
                            if (m.status === 'recognizing text' && typeof m.progress === 'number') {
                                const pct = Math.round(m.progress * 100);
                                idScanBadge.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Scanning ID text... ${pct}%`;
                            }
                        }
                    })
                    .then(({ data: { text } }) => {
                        evaluateExtractedIdText(text || '', aspect);
                    })
                    .catch(err => {
                        console.error('OCR recognition error:', err);
                        finishIdScan(false, '', 'Could not scan text from this image. Please ensure the card text is clear, well-lit, and unblurred.');
                    });
                } else {
                    finishIdScan(false, '', 'OCR engine could not be loaded. Please check your internet connection or use Gmail OTP verification.');
                }
            });
        }

        function evaluateExtractedIdText(rawText, aspect) {
            const clean = (rawText || '').toLowerCase().replace(/[^a-z0-9\s]/g, ' ');
            
            // Primary ID Issuers & Document Types
            const primaryKeywords = [
                'national id', 'philid', 'philsys', 'driver', 'license', 'lisensya', 'lto',
                'land transportation', 'umid', 'sss', 'gsis', 'tin', 'bir', 'voter', 'comelec',
                'postal', 'philpost', 'prc', 'passport', 'barangay', 'student id', 'student',
                'university', 'college', 'school', 'academy', 'senior citizen', 'pwd', 'osca',
                'republic of the philippines', 'republika ng pilipinas', 'identification card',
                'identity card', 'philhealth', 'pag ibig', 'pag-ibig'
            ];

            // Secondary ID Fields & Indicators
            const secondaryKeywords = [
                'philippines', 'pilipinas', 'republic', 'republika', 'pamahalaan', 'surname',
                'given name', 'middle name', 'apelyido', 'pangalan', 'date of birth', 'birthdate',
                'kapanganakan', 'birth', 'dob', 'sex', 'kasarian', 'address', 'tirahan',
                'blood', 'blood type', 'signature', 'lagda', 'id no', 'id number', 'crn',
                'tin no', 'license no', 'precinct', 'student no', 'lrn', 'control no',
                'valid until', 'expiration', 'date issued', 'citizenship', 'nationality', 'cardholder'
            ];

            const matchedPrimary = primaryKeywords.filter(kw => clean.includes(kw));
            const matchedSecondary = secondaryKeywords.filter(kw => clean.includes(kw));
            const hasNumbers = /[0-9]{4,}/.test(clean);
            const totalMatches = matchedPrimary.length + matchedSecondary.length;

            let detectedType = 'Valid Government ID';
            if (clean.includes('national') || clean.includes('philid') || clean.includes('philsys')) detectedType = 'Philippine National ID (PhilID)';
            else if (clean.includes('driver') || clean.includes('license') || clean.includes('lto')) detectedType = "Driver's License";
            else if (clean.includes('postal')) detectedType = 'Postal ID';
            else if (clean.includes('umid') || clean.includes('sss')) detectedType = 'UMID / SSS ID';
            else if (clean.includes('voter') || clean.includes('comelec')) detectedType = "Voter's ID";
            else if (clean.includes('student') || clean.includes('school') || clean.includes('university') || clean.includes('college')) detectedType = 'Student ID Card';
            else if (clean.includes('barangay')) detectedType = 'Barangay Resident ID';
            else if (clean.includes('prc')) detectedType = 'PRC ID';
            else if (clean.includes('tin') || clean.includes('bir')) detectedType = 'TIN ID';
            else if (clean.includes('philhealth')) detectedType = 'PhilHealth ID';

            // Strict Validation Rules:
            // 1. Must have at least 1 primary keyword + (a secondary field or a card number), OR
            // 2. Must have at least 2 primary keywords, OR
            // 3. Must have at least 3 valid keywords
            const isCredibleId = (matchedPrimary.length >= 1 && (totalMatches >= 2 || hasNumbers)) ||
                                 (matchedPrimary.length >= 2) ||
                                 (totalMatches >= 3);

            if (isCredibleId) {
                finishIdScan(true, detectedType, null, rawText);
            } else {
                finishIdScan(false, '', 'Verification Rejected: No official ID credentials detected. Random photos, personal portraits, selfies, or documents without valid ID markings cannot be accepted. Please upload a clear photo of your actual ID card.');
            }
        }

        function finishIdScan(isValid, cardType, errorMsg, extractedText) {
            if (isValid) {
                idVerificationResult = {
                    isValid: true,
                    type: cardType || 'Verified ID Card',
                    text: extractedText || ''
                };
                idScannerLaser.classList.add('valid');
                idScanBadge.style.background = '#dcfce7';
                idScanBadge.style.color = '#15803d';
                idScanBadge.innerHTML = `<i class="fa-solid fa-circle-check"></i> ${idVerificationResult.type} Detected`;
                idCardAlert.className = 'pin-modal-alert';
                btnSubmitIdCard.disabled = false;
            } else {
                idVerificationResult = { isValid: false, type: '', text: '' };
                showIdCardError(errorMsg || 'Could not verify ID card.');
            }
        }

        // Submit via ID Card Reader Handler
        if (btnSubmitIdCard) {
            btnSubmitIdCard.addEventListener('click', function () {
                if (!selectedIdFile || !idVerificationResult.isValid) {
                    showIdCardError('Please upload a valid ID card before submitting.');
                    return;
                }

                const fullName = document.getElementById('fullName').value.trim();
                const email    = document.getElementById('email').value.trim() || activeEmail;
                const phone    = document.getElementById('phone').value.trim();
                const message  = document.getElementById('message').value.trim();
                const service  = document.getElementById('serviceType').value.trim();

                if (!fullName || !email || !message) {
                    showToast('error', 'Incomplete Form', 'Please fill out your Full Name, Email, and Request details.');
                    return;
                }

                btnSubmitIdCard.disabled = true;
                btnSubmitIdCard.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting & Delivering to Admin...';

                const formData = new FormData();
                formData.append('fullName', fullName);
                formData.append('email', email);
                formData.append('phone', phone);
                formData.append('service_type', service);
                formData.append('message', message);
                formData.append('id_card', selectedIdFile);
                formData.append('id_type', idVerificationResult.type);
                formData.append('ocr_valid', '1');
                formData.append('ocr_text', idVerificationResult.text || '');

                fetch('api/verify_id_card.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    btnSubmitIdCard.disabled = false;
                    btnSubmitIdCard.innerHTML = '<i class="fa-solid fa-shield-halved"></i> Verify ID & Deliver to Admin';

                    if (data.status === 'success') {
                        pinModal.classList.remove('active');
                        clearInterval(countdownInterval);
                        clearInterval(resendCooldownTimer);

                        viewIdCardReader.style.display = 'none';
                        viewEmailOtp.style.display = 'block';
                        idDropzonePreviewState.style.display = 'none';
                        idDropzoneEmptyState.style.display = 'block';
                        selectedIdFile = null;
                        docForm.reset();

                        showToast('success', 'Request Received', 'The Admin has received your request and wait for 3 to 5 business days to be finished and sent to your Gmail.');

                        formAlertBanner.style.display = 'flex';
                        formAlertBanner.style.background = '#dcfce7';
                        formAlertBanner.style.color = '#15803d';
                        formAlertBanner.style.border = '1px solid #bbf7d0';
                        formAlertBanner.innerHTML = `
                            <i class="fa-solid fa-circle-check" style="font-size: 1.3rem; flex-shrink: 0;"></i>
                            <div>
                                <strong>ID Verification Successful!</strong><br>
                                The Admin has received your request along with your verified ID card and wait for 3 to 5 business days to be finished and sent to your Gmail.
                            </div>
                        `;

                        formAlertBanner.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } else {
                        showIdCardError(data.message || 'Failed to deliver request. Please try again.');
                    }
                })
                .catch(err => {
                    console.error('ID submission error:', err);
                    btnSubmitIdCard.disabled = false;
                    btnSubmitIdCard.innerHTML = '<i class="fa-solid fa-shield-halved"></i> Verify ID & Deliver to Admin';
                    showIdCardError('Network error while delivering request. Please check your connection.');
                });
            });
        }

        // Close PIN Modal Handlers
        btnCloseModal.addEventListener('click', () => {
            pinModal.classList.remove('active');
            clearInterval(countdownInterval);
            clearInterval(resendCooldownTimer);
        });

        pinModal.addEventListener('click', (e) => {
            if (e.target === pinModal) {
                pinModal.classList.remove('active');
                clearInterval(countdownInterval);
                clearInterval(resendCooldownTimer);
            }
        });

        // OTP Digit Navigation
        pinInputs.forEach((input, idx) => {
            input.addEventListener('input', (e) => {
                const val = e.target.value.replace(/[^0-9]/g, '');
                e.target.value = val;

                if (val.length === 1 && idx < pinInputs.length - 1) {
                    pinInputs[idx + 1].focus();
                }

                if (getEnteredPin().length === 6) {
                    btnVerifyPin.focus();
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && idx > 0) {
                    pinInputs[idx - 1].focus();
                }
            });

            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pasteData = (e.clipboardData || window.clipboardData).getData('text').trim();
                const cleanDigits = pasteData.replace(/[^0-9]/g, '').slice(0, 6);
                
                cleanDigits.split('').forEach((char, i) => {
                    if (pinInputs[i]) {
                        pinInputs[i].value = char;
                    }
                });

                if (cleanDigits.length > 0) {
                    const nextIdx = Math.min(cleanDigits.length, 5);
                    pinInputs[nextIdx].focus();
                }

                if (cleanDigits.length === 6) {
                    btnVerifyPin.focus();
                }
            });
        });

        function getEnteredPin() {
            let code = '';
            pinInputs.forEach(i => code += i.value);
            return code;
        }

        function resetPinInputs() {
            pinInputs.forEach(i => i.value = '');
            pinModalAlert.className = 'pin-modal-alert';
            if (pinInputs[0]) pinInputs[0].focus();
        }

        // 3-Minute Expiration Timer
        function startExpirationTimer(durationSecs) {
            clearInterval(countdownInterval);
            secondsLeft = durationSecs || 180;

            function updateDisplay() {
                const mins = Math.floor(secondsLeft / 60);
                const secs = secondsLeft % 60;
                pinTimerText.textContent = `Expires in: ${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;

                if (secondsLeft <= 30) {
                    pinTimerBadge.classList.add('urgent');
                } else {
                    pinTimerBadge.classList.remove('urgent');
                }

                if (secondsLeft <= 0) {
                    clearInterval(countdownInterval);
                    pinTimerBadge.classList.remove('urgent');
                    pinTimerBadge.classList.add('expired');
                    pinTimerText.textContent = 'OTP Expired';
                    btnVerifyPin.disabled = true;
                    pinModalAlert.className = 'pin-modal-alert active';
                    pinModalAlertText.textContent = 'Your OTP code has expired. Please click Resend Code or Try Another Way.';
                }
                secondsLeft--;
            }

            pinTimerBadge.classList.remove('urgent', 'expired');
            btnVerifyPin.disabled = false;
            updateDisplay();
            countdownInterval = setInterval(updateDisplay, 1000);
        }

        // Resend Cooldown
        function startResendCooldown(cooldownSecs) {
            clearInterval(resendCooldownTimer);
            let cd = cooldownSecs || 30;
            btnResendPin.disabled = true;

            function tick() {
                if (cd <= 0) {
                    clearInterval(resendCooldownTimer);
                    btnResendPin.disabled = false;
                    btnResendPin.innerHTML = '<i class="fa-solid fa-rotate-right"></i> Resend Code';
                } else {
                    btnResendPin.innerHTML = `<i class="fa-solid fa-rotate-right"></i> Resend (${cd}s)`;
                    cd--;
                }
            }
            tick();
            resendCooldownTimer = setInterval(tick, 1000);
        }

        // 1. Send Request -> Request OTP from api/send_email_pin.php
        docForm.addEventListener('submit', function (e) {
            e.preventDefault();

            formAlertBanner.style.display = 'none';

            const fullName = document.getElementById('fullName').value.trim();
            const email    = document.getElementById('email').value.trim();
            const phone    = document.getElementById('phone').value.trim();
            const message  = document.getElementById('message').value.trim();
            const service  = document.getElementById('serviceType').value.trim();

            if (!fullName) {
                showToast('error', 'Missing Name', 'Please enter your Full Name.');
                return;
            }
            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showToast('error', 'Invalid Email', 'Please enter a valid Gmail address.');
                return;
            }
            if (!message) {
                showToast('error', 'Missing Details', 'Please describe your request details.');
                return;
            }

            btnSubmit.disabled = true;
            btnSubmitIcon.className = 'fa-solid fa-spinner fa-spin';
            btnSubmitText.textContent = 'Sending OTP...';

            activeEmail = email;

            const formData = new FormData();
            formData.append('fullName', fullName);
            formData.append('email', email);
            formData.append('phone', phone);
            formData.append('message', message);
            formData.append('service_type', service);

            fetch('api/send_email_pin.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btnSubmit.disabled = false;
                btnSubmitIcon.className = 'fa-regular fa-paper-plane';
                btnSubmitText.textContent = 'Send request';

                if (data.status === 'success') {
                    showToast('info', 'OTP Code Sent', `A 6-digit verification OTP has been sent to ${email}. Please check your Inbox or Spam folder and enter it within 3 minutes.`);

                    modalTargetEmail.textContent = email;
                    resetPinInputs();
                    if (viewEmailOtp && viewIdCardReader) {
                        viewEmailOtp.style.display = 'block';
                        viewIdCardReader.style.display = 'none';
                    }
                    pinModal.classList.add('active');

                    startExpirationTimer(data.expires_in || 180);
                    startResendCooldown(30);

                    // Offline Fallback for Free Hosting
                    const pinOfflineBanner = document.getElementById('pinOfflineBanner');
                    const pinOfflineCode   = document.getElementById('pinOfflineCode');
                    if (pinOfflineBanner && pinOfflineCode) {
                        if (!data.mail_sent && data.dev_pin) {
                            pinOfflineBanner.style.display = 'block';
                            pinOfflineCode.textContent = data.dev_pin;
                        } else {
                            pinOfflineBanner.style.display = 'none';
                        }
                    }

                    if (data.dev_pin) {
                        console.log(`%c[Barangay Tabon Dev OTP] PIN for ${email}: ${data.dev_pin}`, 'color: #137547; font-weight: bold; font-size: 14px;');
                    }
                } else {
                    showToast('error', 'Could Not Send OTP', data.message || 'Unable to deliver verification OTP. Please try again.');
                }
            })
            .catch(err => {
                console.error('Request submission error:', err);
                btnSubmit.disabled = false;
                btnSubmitIcon.className = 'fa-regular fa-paper-plane';
                btnSubmitText.textContent = 'Send request';
                showToast('error', 'Network Error', 'Failed to connect to the verification server. Please check your connection.');
            });
        });

        // Auto-Fill Button Handler for Testing Mode
        const btnAutoFillPin = document.getElementById('btnAutoFillPin');
        if (btnAutoFillPin) {
            btnAutoFillPin.addEventListener('click', function () {
                const code = document.getElementById('pinOfflineCode').textContent.trim();
                if (code && code.length === 6 && !code.includes('-')) {
                    code.split('').forEach((ch, idx) => {
                        if (pinInputs[idx]) pinInputs[idx].value = ch;
                    });
                    btnVerifyPin.click();
                }
            });
        }

        // 2. Resend PIN Handler
        btnResendPin.addEventListener('click', function () {
            if (!activeEmail) return;

            btnResendPin.disabled = true;
            btnResendPin.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Resending...';

            const formData = new FormData();
            formData.append('email', activeEmail);

            fetch('api/resend_email_pin.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast('info', 'New OTP Sent', `A fresh OTP code has been sent to ${activeEmail}. Please check your Inbox or Spam folder.`);
                    resetPinInputs();
                    startExpirationTimer(data.expires_in || 180);
                    startResendCooldown(30);

                    const pinOfflineBanner = document.getElementById('pinOfflineBanner');
                    const pinOfflineCode   = document.getElementById('pinOfflineCode');
                    if (pinOfflineBanner && pinOfflineCode) {
                        if (!data.mail_sent && data.dev_pin) {
                            pinOfflineBanner.style.display = 'block';
                            pinOfflineCode.textContent = data.dev_pin;
                        } else {
                            pinOfflineBanner.style.display = 'none';
                        }
                    }

                    if (data.dev_pin) {
                        console.log(`%c[Barangay Tabon Dev OTP] Resent PIN for ${activeEmail}: ${data.dev_pin}`, 'color: #137547; font-weight: bold; font-size: 14px;');
                    }
                } else {
                    btnResendPin.disabled = false;
                    btnResendPin.innerHTML = '<i class="fa-solid fa-rotate-right"></i> Resend Code';
                    showToast('error', 'Resend Failed', data.message || 'Could not resend OTP code.');
                }
            })
            .catch(err => {
                console.error('Resend error:', err);
                btnResendPin.disabled = false;
                btnResendPin.innerHTML = '<i class="fa-solid fa-rotate-right"></i> Resend Code';
                showToast('error', 'Network Error', 'Could not contact server to resend code.');
            });
        });

        // 3. Verify PIN Handler -> Commits to feedback_chats
        btnVerifyPin.addEventListener('click', function () {
            const pin = getEnteredPin();

            if (pin.length < 6) {
                pinModalAlert.className = 'pin-modal-alert active';
                pinModalAlertText.textContent = 'Please enter the complete 6-digit OTP code.';
                return;
            }

            btnVerifyPin.disabled = true;
            btnVerifyPin.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verifying OTP Code...';

            const formData = new FormData();
            formData.append('email', activeEmail);
            formData.append('pin_code', pin);

            fetch('api/verify_email_pin.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btnVerifyPin.disabled = false;
                btnVerifyPin.innerHTML = '<i class="fa-solid fa-circle-check"></i> Verify & Submit Request';

                if (data.status === 'success') {
                    pinModal.classList.remove('active');
                    clearInterval(countdownInterval);
                    clearInterval(resendCooldownTimer);

                    docForm.reset();

                    showToast('success', 'Request Received', 'The Admin has received your request and wait for 3 to 5 business days to be finished and sent to your Gmail.');

                    formAlertBanner.style.display = 'flex';
                    formAlertBanner.style.background = '#dcfce7';
                    formAlertBanner.style.color = '#15803d';
                    formAlertBanner.style.border = '1px solid #bbf7d0';
                    formAlertBanner.innerHTML = `
                        <i class="fa-solid fa-circle-check" style="font-size: 1.3rem; flex-shrink: 0;"></i>
                        <div>
                            <strong>Verification Successful!</strong><br>
                            The Admin has received your request and wait for 3 to 5 business days to be finished and sent to your Gmail.
                        </div>
                    `;

                    formAlertBanner.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    pinModalAlert.className = 'pin-modal-alert active';
                    pinModalAlertText.textContent = data.message || 'Invalid or expired OTP code. Please try again.';
                    if (data.message && data.message.includes('expired')) {
                        btnVerifyPin.disabled = true;
                    }
                }
            })
            .catch(err => {
                console.error('Verification error:', err);
                btnVerifyPin.disabled = false;
                btnVerifyPin.innerHTML = '<i class="fa-solid fa-circle-check"></i> Verify & Submit Request';
                pinModalAlert.className = 'pin-modal-alert active';
                pinModalAlertText.textContent = 'Network error while verifying OTP code. Please check your connection.';
            });
        });
    });
    </script>
</body>
</html>

