CREATE DATABASE IF NOT EXISTS tabon_db;
USE tabon_db;

-- 1. Create Legends Table
CREATE TABLE IF NOT EXISTS legends (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    icon_class VARCHAR(100) DEFAULT 'fa-location-dot',
    color VARCHAR(20) DEFAULT '#000000',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert Default Legends
INSERT INTO legends (title, icon_class, color) VALUES 
('Barangay Hall', 'fa-building', '#4a6cf7'),
('School', 'fa-book-open', '#28a745'),
('Court', 'fa-basketball', '#fca311');

-- 2. Create Purok 1 Locations Table
CREATE TABLE IF NOT EXISTS purok1_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    legend_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    coordinate_x DECIMAL(5,2) NOT NULL,
    coordinate_y DECIMAL(5,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (legend_id) REFERENCES legends(id) ON DELETE CASCADE
);

-- 3. Create Purok 2 Locations Table
CREATE TABLE IF NOT EXISTS purok2_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    legend_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    coordinate_x DECIMAL(5,2) NOT NULL,
    coordinate_y DECIMAL(5,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (legend_id) REFERENCES legends(id) ON DELETE CASCADE
);

-- 4. Create Purok 3 Locations Table
CREATE TABLE IF NOT EXISTS purok3_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    legend_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    coordinate_x DECIMAL(5,2) NOT NULL,
    coordinate_y DECIMAL(5,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (legend_id) REFERENCES legends(id) ON DELETE CASCADE
);
