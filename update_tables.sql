USE tabon_db;

-- 1. Ensure tables exist with the updated schema including children column
CREATE TABLE IF NOT EXISTS purok1_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_number VARCHAR(50) NOT NULL,
    husband_name VARCHAR(150) NOT NULL,
    spouse_name VARCHAR(150) DEFAULT NULL,
    children TEXT DEFAULT NULL, -- Stores JSON array: ["Child A", "Child B"]
    house_image VARCHAR(255) NOT NULL,
    marker_image VARCHAR(255) NOT NULL,
    marker_width INT DEFAULT 40,
    marker_height INT DEFAULT 40,
    coordinate_x DECIMAL(5,2) NOT NULL DEFAULT 50.00,
    coordinate_y DECIMAL(5,2) NOT NULL DEFAULT 50.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS purok2_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_number VARCHAR(50) NOT NULL,
    husband_name VARCHAR(150) NOT NULL,
    spouse_name VARCHAR(150) DEFAULT NULL,
    children TEXT DEFAULT NULL, -- Stores JSON array: ["Child A", "Child B"]
    house_image VARCHAR(255) NOT NULL,
    marker_image VARCHAR(255) NOT NULL,
    marker_width INT DEFAULT 40,
    marker_height INT DEFAULT 40,
    coordinate_x DECIMAL(5,2) NOT NULL DEFAULT 50.00,
    coordinate_y DECIMAL(5,2) NOT NULL DEFAULT 50.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS purok3_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_number VARCHAR(50) NOT NULL,
    husband_name VARCHAR(150) NOT NULL,
    spouse_name VARCHAR(150) DEFAULT NULL,
    children TEXT DEFAULT NULL, -- Stores JSON array: ["Child A", "Child B"]
    house_image VARCHAR(255) NOT NULL,
    marker_image VARCHAR(255) NOT NULL,
    marker_width INT DEFAULT 40,
    marker_height INT DEFAULT 40,
    coordinate_x DECIMAL(5,2) NOT NULL DEFAULT 50.00,
    coordinate_y DECIMAL(5,2) NOT NULL DEFAULT 50.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Safe ALTER TABLE statements to add children column if tables already exist
SET @col1 = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'tabon_db' AND TABLE_NAME = 'purok1_locations' AND COLUMN_NAME = 'children');
SET @sql1 = IF(@col1 = 0, 'ALTER TABLE purok1_locations ADD COLUMN children TEXT DEFAULT NULL AFTER spouse_name', 'SELECT 1');
PREPARE stmt1 FROM @sql1; EXECUTE stmt1; DEALLOCATE PREPARE stmt1;

SET @col2 = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'tabon_db' AND TABLE_NAME = 'purok2_locations' AND COLUMN_NAME = 'children');
SET @sql2 = IF(@col2 = 0, 'ALTER TABLE purok2_locations ADD COLUMN children TEXT DEFAULT NULL AFTER spouse_name', 'SELECT 1');
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

SET @col3 = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'tabon_db' AND TABLE_NAME = 'purok3_locations' AND COLUMN_NAME = 'children');
SET @sql3 = IF(@col3 = 0, 'ALTER TABLE purok3_locations ADD COLUMN children TEXT DEFAULT NULL AFTER spouse_name', 'SELECT 1');
PREPARE stmt3 FROM @sql3; EXECUTE stmt3; DEALLOCATE PREPARE stmt3;

-- 3. Media Gallery & Folders Schema
CREATE TABLE IF NOT EXISTS `gallery_folders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gallery_media` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `folder_id` INT NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `caption` VARCHAR(255) NULL,
    `file_size` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`folder_id`),
    CONSTRAINT `fk_gallery_folder` FOREIGN KEY (`folder_id`) REFERENCES `gallery_folders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- 4. Services Schema Update
CREATE TABLE IF NOT EXISTS `services` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `icon` VARCHAR(100) DEFAULT 'bi-file-earmark-text',
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `fee` VARCHAR(100) DEFAULT 'Free',
    `processing_time` VARCHAR(100) DEFAULT 'Same Day',
    `description` TEXT NOT NULL,
    `requirements` TEXT NULL,
    `document_file` VARCHAR(255) NULL,
    `button_text` VARCHAR(100) DEFAULT 'Request Document',
    `button_url` VARCHAR(255) DEFAULT 'contact.php',
    `display_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @col_services = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'tabon_db' AND TABLE_NAME = 'services' AND COLUMN_NAME = 'document_file');
SET @sql_services = IF(@col_services = 0, 'ALTER TABLE services ADD COLUMN document_file VARCHAR(255) NULL AFTER requirements', 'SELECT 1');
PREPARE stmt_svc FROM @sql_services; EXECUTE stmt_svc; DEALLOCATE PREPARE stmt_svc;


-- 5. Feedback Chat & Inquiries Messenger Tables
CREATE TABLE IF NOT EXISTS `feedback_conversations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `resident_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NULL,
    `phone` VARCHAR(50) NULL,
    `subject` VARCHAR(255) DEFAULT 'General Feedback',
    `status` ENUM('new', 'open', 'resolved') DEFAULT 'new',
    `last_message` TEXT NULL,
    `last_message_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `unread_admin` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `feedback_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `conversation_id` INT NOT NULL,
    `sender_type` ENUM('resident', 'admin') NOT NULL,
    `sender_name` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `attachment` VARCHAR(255) NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`conversation_id`),
    CONSTRAINT `fk_feedback_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `feedback_conversations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 6. Feedback Chats Table
CREATE TABLE IF NOT EXISTS `feedback_chats` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Email Verification PIN Codes Table (Contact Form Captcha Gate)
CREATE TABLE IF NOT EXISTS `email_verifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(150) NOT NULL,
  `pin_code` VARCHAR(10) NOT NULL,
  `full_name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `service_type` VARCHAR(150) DEFAULT 'Contact Inquiry',
  `message` TEXT NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `is_verified` TINYINT(1) DEFAULT 0,
  `resend_count` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`email`),
  INDEX (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
