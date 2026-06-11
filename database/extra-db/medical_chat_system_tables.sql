-- Medical Chat System Database Tables
-- Comprehensive chat system with file uploads for medical records and prescriptions

-- 1. Chat Conversations Table
CREATE TABLE IF NOT EXISTS chat_conversations (
    conversation_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NULL,
    associate_id INT NULL,
    conversation_type ENUM('Patient-Doctor', 'Patient-Associate', 'Doctor-Associate') NOT NULL,
    status ENUM('Active', 'Closed', 'Archived') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES tbl_patient(id),
    FOREIGN KEY (doctor_id) REFERENCES tbl_employee(employee_id),
    FOREIGN KEY (associate_id) REFERENCES associates(associate_id)
);

-- 2. Chat Messages Table
CREATE TABLE IF NOT EXISTS chat_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    sender_type ENUM('Patient', 'Doctor', 'Associate') NOT NULL,
    message_text TEXT,
    message_type ENUM('Text', 'File', 'Prescription', 'Medical_Record') DEFAULT 'Text',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(conversation_id)
);

-- 3. Chat Files Table (for uploaded medical records and prescriptions)
CREATE TABLE IF NOT EXISTS chat_files (
    file_id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL,
    file_category ENUM('Medical_Record', 'Prescription', 'Lab_Report', 'X_Ray', 'Other') NOT NULL,
    description TEXT,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES chat_messages(message_id)
);

-- 4. Medical Records Table (for patient's medical history)
CREATE TABLE IF NOT EXISTS patient_medical_records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NULL,
    record_type ENUM('Prescription', 'Lab_Report', 'X_Ray', 'MRI', 'CT_Scan', 'Blood_Test', 'Other') NOT NULL,
    record_title VARCHAR(255) NOT NULL,
    record_description TEXT,
    file_path VARCHAR(500),
    record_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES tbl_patient(id),
    FOREIGN KEY (doctor_id) REFERENCES tbl_employee(employee_id)
);

-- 5. Prescriptions Table
CREATE TABLE IF NOT EXISTS prescriptions (
    prescription_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    prescription_text TEXT NOT NULL,
    prescription_file VARCHAR(500),
    prescribed_date DATE NOT NULL,
    status ENUM('Active', 'Completed', 'Cancelled') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES tbl_patient(id),
    FOREIGN KEY (doctor_id) REFERENCES tbl_employee(employee_id)
);

-- 6. Chat Participants Table (for tracking who can access conversations)
CREATE TABLE IF NOT EXISTS chat_participants (
    participant_id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    user_type ENUM('Patient', 'Doctor', 'Associate') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(conversation_id)
);

-- 7. File Categories Table (for organizing different types of medical files)
CREATE TABLE IF NOT EXISTS file_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    category_description TEXT,
    allowed_extensions TEXT,
    max_file_size INT DEFAULT 10485760, -- 10MB default
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default file categories
INSERT INTO file_categories (category_name, category_description, allowed_extensions, max_file_size) VALUES
('Medical Records', 'General medical records and reports', 'pdf,doc,docx,jpg,jpeg,png', 10485760),
('Prescriptions', 'Doctor prescriptions and medication records', 'pdf,doc,docx,jpg,jpeg,png', 5242880),
('Lab Reports', 'Laboratory test results and reports', 'pdf,jpg,jpeg,png', 10485760),
('X-Ray Images', 'X-Ray and imaging results', 'jpg,jpeg,png,dcm', 20971520),
('MRI/CT Scans', 'MRI and CT scan results', 'jpg,jpeg,png,dcm', 52428800),
('Blood Tests', 'Blood test results and reports', 'pdf,jpg,jpeg,png', 10485760),
('Other Documents', 'Other medical documents and files', 'pdf,doc,docx,jpg,jpeg,png,txt', 10485760);

-- Create indexes for better performance
CREATE INDEX idx_chat_conversations_patient ON chat_conversations(patient_id);
CREATE INDEX idx_chat_conversations_doctor ON chat_conversations(doctor_id);
CREATE INDEX idx_chat_conversations_associate ON chat_conversations(associate_id);
CREATE INDEX idx_chat_messages_conversation ON chat_messages(conversation_id);
CREATE INDEX idx_chat_messages_sender ON chat_messages(sender_id, sender_type);
CREATE INDEX idx_chat_files_message ON chat_files(message_id);
CREATE INDEX idx_medical_records_patient ON patient_medical_records(patient_id);
CREATE INDEX idx_medical_records_doctor ON patient_medical_records(doctor_id);
CREATE INDEX idx_prescriptions_patient ON prescriptions(patient_id);
CREATE INDEX idx_prescriptions_doctor ON prescriptions(doctor_id);
CREATE INDEX idx_chat_participants_conversation ON chat_participants(conversation_id);
CREATE INDEX idx_chat_participants_user ON chat_participants(user_id, user_type);
