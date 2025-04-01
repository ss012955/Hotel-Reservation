-- Create the database
CREATE DATABASE IF NOT EXISTS hotel_db;

-- Use the database
USE hotel_db;

-- Create users table for admin and staff
CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create room types table
CREATE TABLE IF NOT EXISTS room_types (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
);

-- Create rooms table
CREATE TABLE IF NOT EXISTS rooms (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    room_number VARCHAR(10) NOT NULL UNIQUE,
    capacity ENUM('Single', 'Double', 'Family') NOT NULL,
    type_id INT NOT NULL,
    rate_regular DECIMAL(10, 2) NOT NULL,
    rate_deluxe DECIMAL(10, 2) NOT NULL,
    rate_suite DECIMAL(10, 2) NOT NULL,
    status ENUM('Available', 'Occupied', 'Maintenance') NOT NULL DEFAULT 'Available',
    guest_name VARCHAR(100) DEFAULT NULL,
    FOREIGN KEY (type_id) REFERENCES room_types(id)
);

-- Create reservations table
CREATE TABLE IF NOT EXISTS reservations (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    customer_name VARCHAR(100) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    room_type VARCHAR(50) NOT NULL,
    room_capacity VARCHAR(50) NOT NULL,
    payment_type VARCHAR(50) NOT NULL,
    from_date DATE NOT NULL,
    to_date DATE NOT NULL,
    total_cost DECIMAL(10, 2) NOT NULL,
    status ENUM('Confirmed', 'Cancelled', 'Completed') NOT NULL DEFAULT 'Confirmed',
    is_paid BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    email VARCHAR(255) NOT NULL
);

-- Insert admin user (password: admin123)
INSERT INTO users (username, password, role) 
VALUES ('admin', '$2y$10$ASQndG4P08siR2QmWApu0uQaIRGLjzAXiZWTUdLeGNzBPJde4Q8wG', 'admin');

-- Insert staff user (password: staff123)
INSERT INTO users (username, password, role) 
VALUES ('staff', '$2y$10$yzGK5t6OHNdlucII5f7FBOfCXbQ13RzVOWrGzTH2dRbc02D6FiDue', 'staff');

-- Insert room types
INSERT INTO room_types (name, description) 
VALUES 
('Regular', 'Standard room with basic amenities'),
('De Luxe', 'Upgraded room with premium amenities'),
('Suite', 'Luxury suite with separate living area and premium amenities');

-- Insert some sample rooms
INSERT INTO rooms (room_number, capacity, type_id, rate_regular, rate_deluxe, rate_suite, status)
VALUES 
('101', 'Single', 1, 100, 300, 500, 'Available'),
('102', 'Single', 2, 100, 300, 500, 'Available'),
('103', 'Single', 3, 100, 300, 500, 'Available'),
('104', 'Single', 1, 100, 300, 500, 'Available'),
('105', 'Single', 2, 100, 300, 500, 'Available'),
('106', 'Single', 3, 100, 300, 500, 'Available'),
('201', 'Double', 1, 200, 500, 800, 'Available'),
('202', 'Double', 2, 200, 500, 800, 'Available'),
('203', 'Double', 3, 200, 500, 800, 'Available'),
('204', 'Double', 1, 200, 500, 800, 'Available'),
('205', 'Double', 2, 200, 500, 800, 'Available'),
('206', 'Double', 3, 200, 500, 800, 'Available'),
('301', 'Family', 1, 500, 750, 1000, 'Available'),
('302', 'Family', 2, 500, 750, 1000, 'Available'),
('303', 'Family', 3, 500, 750, 1000, 'Available'),
('304', 'Family', 1, 500, 750, 1000, 'Available'),
('305', 'Family', 2, 500, 750, 1000, 'Available'),
('306', 'Family', 3, 500, 750, 1000, 'Available'); 