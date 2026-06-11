-- Blood Bank Management System Database Tables
-- Database: hms_db

-- 1. Blood Donors Table
CREATE TABLE IF NOT EXISTS blood_donors (
    donor_id INT AUTO_INCREMENT PRIMARY KEY,
    donor_name VARCHAR(100) NOT NULL,
    donor_email VARCHAR(100) UNIQUE,
    donor_phone VARCHAR(15) NOT NULL,
    donor_address TEXT,
    donor_age INT NOT NULL,
    donor_gender ENUM('Male', 'Female', 'Other') NOT NULL,
    blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    donor_weight DECIMAL(5,2) NOT NULL,
    last_donation_date DATE,
    is_eligible BOOLEAN DEFAULT TRUE,
    medical_conditions TEXT,
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(15),
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('Active', 'Inactive', 'Suspended') DEFAULT 'Active'
);

-- 2. Blood Inventory Table
CREATE TABLE IF NOT EXISTS blood_inventory (
    inventory_id INT AUTO_INCREMENT PRIMARY KEY,
    blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    blood_group VARCHAR(10) NOT NULL,
    collection_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    donor_id INT,
    unit_volume DECIMAL(4,2) DEFAULT 1.0, -- in liters
    storage_location VARCHAR(50),
    temperature DECIMAL(4,2), -- storage temperature
    blood_pressure_systolic INT,
    blood_pressure_diastolic INT,
    hemoglobin_level DECIMAL(4,2),
    blood_sugar_level DECIMAL(4,2),
    is_tested BOOLEAN DEFAULT FALSE,
    test_results TEXT,
    status ENUM('Available', 'Reserved', 'Transfused', 'Expired', 'Discarded') DEFAULT 'Available',
    reserved_for_patient_id INT,
    reserved_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_id) REFERENCES blood_donors(donor_id) ON DELETE SET NULL
);

-- 3. Blood Requests Table
CREATE TABLE IF NOT EXISTS blood_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    patient_name VARCHAR(100) NOT NULL,
    patient_blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    required_blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    units_required INT NOT NULL,
    urgency_level ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    doctor_name VARCHAR(100),
    department VARCHAR(50),
    request_reason TEXT,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    required_date DATE,
    status ENUM('Pending', 'Approved', 'Rejected', 'Fulfilled', 'Cancelled') DEFAULT 'Pending',
    approved_by VARCHAR(100),
    approved_date TIMESTAMP NULL,
    fulfilled_date TIMESTAMP NULL,
    notes TEXT,
    created_by VARCHAR(100),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 4. Blood Transfusions Table
CREATE TABLE IF NOT EXISTS blood_transfusions (
    transfusion_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    patient_name VARCHAR(100) NOT NULL,
    inventory_id INT NOT NULL,
    blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    units_transfused DECIMAL(4,2) NOT NULL,
    transfusion_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    doctor_name VARCHAR(100) NOT NULL,
    nurse_name VARCHAR(100),
    pre_transfusion_vitals TEXT,
    post_transfusion_vitals TEXT,
    adverse_reactions TEXT,
    transfusion_notes TEXT,
    status ENUM('Completed', 'In Progress', 'Cancelled', 'Adverse Reaction') DEFAULT 'Completed',
    created_by VARCHAR(100),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (inventory_id) REFERENCES blood_inventory(inventory_id) ON DELETE RESTRICT
);

-- 5. Blood Test Results Table
CREATE TABLE IF NOT EXISTS blood_test_results (
    test_id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_id INT NOT NULL,
    donor_id INT,
    test_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    hiv_result ENUM('Negative', 'Positive', 'Pending') DEFAULT 'Pending',
    hepatitis_b_result ENUM('Negative', 'Positive', 'Pending') DEFAULT 'Pending',
    hepatitis_c_result ENUM('Negative', 'Positive', 'Pending') DEFAULT 'Pending',
    syphilis_result ENUM('Negative', 'Positive', 'Pending') DEFAULT 'Pending',
    malaria_result ENUM('Negative', 'Positive', 'Pending') DEFAULT 'Pending',
    blood_group_confirmation ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'),
    overall_result ENUM('Suitable', 'Unsuitable', 'Pending') DEFAULT 'Pending',
    tested_by VARCHAR(100),
    test_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventory_id) REFERENCES blood_inventory(inventory_id) ON DELETE CASCADE,
    FOREIGN KEY (donor_id) REFERENCES blood_donors(donor_id) ON DELETE SET NULL
);

-- 6. Blood Bank Staff Table
CREATE TABLE IF NOT EXISTS blood_bank_staff (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    staff_name VARCHAR(100) NOT NULL,
    staff_email VARCHAR(100) UNIQUE,
    staff_phone VARCHAR(15),
    designation ENUM('Blood Bank Manager', 'Lab Technician', 'Nurse', 'Clerk', 'Pathologist') NOT NULL,
    shift_timing VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    hire_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX idx_donor_blood_type ON blood_donors(blood_type);
CREATE INDEX idx_donor_status ON blood_donors(status);
CREATE INDEX idx_inventory_blood_type ON blood_inventory(blood_type);
CREATE INDEX idx_inventory_status ON blood_inventory(status);
CREATE INDEX idx_inventory_expiry ON blood_inventory(expiry_date);
CREATE INDEX idx_requests_status ON blood_requests(status);
CREATE INDEX idx_requests_urgency ON blood_requests(urgency_level);
CREATE INDEX idx_transfusions_date ON blood_transfusions(transfusion_date);
CREATE INDEX idx_test_results ON blood_test_results(overall_result);
