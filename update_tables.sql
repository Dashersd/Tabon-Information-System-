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

