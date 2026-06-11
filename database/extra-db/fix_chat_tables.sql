-- Fix Chat System Tables for hms_db
-- This script updates the existing chat tables to work with the comprehensive chat system

USE hms_db;

-- Drop existing chat tables if they exist (to recreate with proper structure)
DROP TABLE IF EXISTS chat_files;
DROP TABLE IF EXISTS chat_messages;
DROP TABLE IF EXISTS chat_participants;
DROP TABLE IF EXISTS chat_conversations;

-- 1. Chat Conversations Table (Updated with proper structure)
CREATE TABLE chat_conversations (
    conversation_id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_uuid VARCHAR(36) NOT NULL UNIQUE,
    patient_id INT NULL,
    doctor_id INT NULL,
    associate_id INT NULL,
    donor_id INT NULL,
    conversation_type ENUM('Patient-Doctor', 'Patient-Associate', 'Patient-Donor', 'Doctor-Associate') NOT NULL,
    title VARCHAR(200),
    description TEXT,
    status ENUM('Active', 'Closed', 'Archived') DEFAULT 'Active',
    is_active BOOLEAN DEFAULT TRUE,
    is_archived BOOLEAN DEFAULT FALSE,
    is_encrypted BOOLEAN DEFAULT TRUE,
    encryption_key VARCHAR(255) NULL,
    privacy_level ENUM('Private') DEFAULT 'Private',
    allow_file_sharing BOOLEAN DEFAULT FALSE,
    last_message_at TIMESTAMP NULL,
    last_activity_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(100),
    updated_by VARCHAR(100),
    
    -- Foreign key constraints (commented out to avoid errors if tables don't exist)
    -- FOREIGN KEY (patient_id) REFERENCES tbl_patient(id),
    -- FOREIGN KEY (doctor_id) REFERENCES tbl_employee(id),
    -- FOREIGN KEY (associate_id) REFERENCES associates(associate_id),
    -- FOREIGN KEY (donor_id) REFERENCES donors(donor_id)
);

-- 2. Chat Messages Table (Updated with proper structure)
CREATE TABLE chat_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    sender_type ENUM('Patient', 'Doctor', 'Associate', 'Donor') NOT NULL,
    message_text TEXT,
    message_type ENUM('Text', 'File', 'Prescription', 'Medical_Record') DEFAULT 'Text',
    is_edited BOOLEAN DEFAULT FALSE,
    edited_at TIMESTAMP NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    is_deleted BOOLEAN DEFAULT FALSE,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(conversation_id) ON DELETE CASCADE
);

-- 3. Chat Files Table (New table for file uploads)
CREATE TABLE chat_files (
    file_id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL,
    file_category ENUM('Medical_Record', 'Prescription', 'Lab_Report', 'X_Ray', 'MRI', 'CT_Scan', 'Blood_Test', 'Other') NOT NULL,
    description TEXT,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (message_id) REFERENCES chat_messages(message_id) ON DELETE CASCADE
);

-- 4. Chat Participants Table (Updated with proper structure)
CREATE TABLE chat_participants (
    participant_id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    user_type ENUM('Patient', 'Doctor', 'Associate', 'Donor') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(conversation_id) ON DELETE CASCADE,
    UNIQUE KEY unique_participant (conversation_id, user_id, user_type)
);

-- 5. File Categories Table (for organizing different types of medical files)
CREATE TABLE IF NOT EXISTS file_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    category_description TEXT,
    allowed_extensions TEXT,
    max_file_size INT DEFAULT 10485760, -- 10MB default
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default file categories if they don't exist
INSERT IGNORE INTO file_categories (category_name, category_description, allowed_extensions, max_file_size) VALUES
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
CREATE INDEX idx_chat_conversations_donor ON chat_conversations(donor_id);
CREATE INDEX idx_chat_conversations_type ON chat_conversations(conversation_type);
CREATE INDEX idx_chat_conversations_status ON chat_conversations(status);
CREATE INDEX idx_chat_messages_conversation ON chat_messages(conversation_id);
CREATE INDEX idx_chat_messages_sender ON chat_messages(sender_id, sender_type);
CREATE INDEX idx_chat_messages_read ON chat_messages(is_read);
CREATE INDEX idx_chat_files_message ON chat_files(message_id);
CREATE INDEX idx_chat_participants_conversation ON chat_participants(conversation_id);
CREATE INDEX idx_chat_participants_user ON chat_participants(user_id, user_type);

-- Add conversation_uuid if it doesn't exist
ALTER TABLE chat_conversations ADD COLUMN IF NOT EXISTS conversation_uuid VARCHAR(36) NOT NULL UNIQUE;

-- Generate UUIDs for existing conversations if any
UPDATE chat_conversations SET conversation_uuid = UUID() WHERE conversation_uuid IS NULL OR conversation_uuid = '';

-- Add missing columns to chat_conversations if they don't exist
ALTER TABLE chat_conversations ADD COLUMN IF NOT EXISTS associate_id INT NULL;
ALTER TABLE chat_conversations ADD COLUMN IF NOT EXISTS donor_id INT NULL;
ALTER TABLE chat_conversations ADD COLUMN IF NOT EXISTS status ENUM('Active', 'Closed', 'Archived') DEFAULT 'Active';
ALTER TABLE chat_conversations ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE;
ALTER TABLE chat_conversations ADD COLUMN IF NOT EXISTS is_archived BOOLEAN DEFAULT FALSE;
ALTER TABLE chat_conversations ADD COLUMN IF NOT EXISTS is_encrypted BOOLEAN DEFAULT TRUE;
ALTER TABLE chat_conversations ADD COLUMN IF NOT EXISTS encryption_key VARCHAR(255) NULL;
ALTER TABLE chat_conversations ADD COLUMN IF NOT EXISTS privacy_level ENUM('Private') DEFAULT 'Private';
ALTER TABLE chat_conversations ADD COLUMN IF NOT EXISTS allow_file_sharing BOOLEAN DEFAULT FALSE;
ALTER TABLE chat_conversations ADD COLUMN IF NOT EXISTS created_by VARCHAR(100);
ALTER TABLE chat_conversations ADD COLUMN IF NOT EXISTS updated_by VARCHAR(100);

-- Add missing columns to chat_messages if they don't exist
ALTER TABLE chat_messages ADD COLUMN IF NOT EXISTS message_type ENUM('Text', 'File', 'Prescription', 'Medical_Record') DEFAULT 'Text';
ALTER TABLE chat_messages ADD COLUMN IF NOT EXISTS is_edited BOOLEAN DEFAULT FALSE;
ALTER TABLE chat_messages ADD COLUMN IF NOT EXISTS edited_at TIMESTAMP NULL;
ALTER TABLE chat_messages ADD COLUMN IF NOT EXISTS is_deleted BOOLEAN DEFAULT FALSE;
ALTER TABLE chat_messages ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL;
ALTER TABLE chat_messages ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Update conversation_type enum to include new types
ALTER TABLE chat_conversations MODIFY COLUMN conversation_type ENUM('Patient-Doctor', 'Patient-Associate', 'Patient-Donor', 'Doctor-Associate') NOT NULL;

-- Update sender_type enum to include new types
ALTER TABLE chat_messages MODIFY COLUMN sender_type ENUM('Patient', 'Doctor', 'Associate', 'Donor') NOT NULL;

-- Update user_type enum to include new types
ALTER TABLE chat_participants MODIFY COLUMN user_type ENUM('Patient', 'Doctor', 'Associate', 'Donor') NOT NULL;

-- Set conversation_uuid as NOT NULL
ALTER TABLE chat_conversations MODIFY COLUMN conversation_uuid VARCHAR(36) NOT NULL;

-- Create the uploads/chat_files directory structure
-- Note: This will be handled by PHP code
