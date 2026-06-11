-- Associate Management System Database Tables
-- Database: hms_db
-- Associates work as bridges between doctors and patients

CREATE TABLE IF NOT EXISTS associates (
    associate_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(15) NOT NULL,
    associate_type ENUM('Patient Coordinator', 'Doctor Coordinator') NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Removed: associate_assignments (not required)
-- Removed: associate_schedules (not required)

-- Removed: associate_communications (extra features removed)

-- Removed: associate_tasks (not required)
-- Removed: associate_performance (not required)
-- Removed: associate_training (not required)

-- Removed: associate_availability (extra features removed)

-- Create indexes for better performance
CREATE INDEX idx_associate_status ON associates(status);
CREATE INDEX idx_associate_type ON associates(associate_type);

-- Insert sample data
INSERT INTO associates (first_name, last_name, email, phone, associate_type, status) VALUES
('Sarah', 'Johnson', 'sarah.johnson@hospital.com', '+1234567890', 'Patient Coordinator', 'Active'),
('Michael', 'Chen', 'michael.chen@hospital.com', '+1234567891', 'Doctor Coordinator', 'Active');

-- Sample availability removed
