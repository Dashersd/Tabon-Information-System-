USE tabon_db;

DROP TABLE IF EXISTS purok1_locations;
DROP TABLE IF EXISTS purok2_locations;
DROP TABLE IF EXISTS purok3_locations;

CREATE TABLE purok1_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_number VARCHAR(50) NOT NULL,
    husband_name VARCHAR(150) NOT NULL,
    spouse_name VARCHAR(150),
    house_image VARCHAR(255) NOT NULL,
    marker_image VARCHAR(255) NOT NULL,
    marker_width INT DEFAULT 40,
    marker_height INT DEFAULT 40,
    coordinate_x DECIMAL(5,2) NOT NULL,
    coordinate_y DECIMAL(5,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE purok2_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_number VARCHAR(50) NOT NULL,
    husband_name VARCHAR(150) NOT NULL,
    spouse_name VARCHAR(150),
    house_image VARCHAR(255) NOT NULL,
    marker_image VARCHAR(255) NOT NULL,
    marker_width INT DEFAULT 40,
    marker_height INT DEFAULT 40,
    coordinate_x DECIMAL(5,2) NOT NULL,
    coordinate_y DECIMAL(5,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE purok3_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_number VARCHAR(50) NOT NULL,
    husband_name VARCHAR(150) NOT NULL,
    spouse_name VARCHAR(150),
    house_image VARCHAR(255) NOT NULL,
    marker_image VARCHAR(255) NOT NULL,
    marker_width INT DEFAULT 40,
    marker_height INT DEFAULT 40,
    coordinate_x DECIMAL(5,2) NOT NULL,
    coordinate_y DECIMAL(5,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
