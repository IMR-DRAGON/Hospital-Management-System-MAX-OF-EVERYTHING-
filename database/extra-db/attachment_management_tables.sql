-- Attachment Management System Database Tables
-- Database: hms_db
-- For associates to upload reports prescribed by doctors for individual patients

-- 1. Attachment Categories Table
CREATE TABLE IF NOT EXISTS attachment_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    category_code VARCHAR(20) NOT NULL UNIQUE,
    description TEXT,
    allowed_file_types TEXT, -- JSON array of allowed file extensions
    max_file_size_mb INT DEFAULT 10,
    is_required BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Patient Attachments Table
CREATE TABLE IF NOT EXISTS patient_attachments (
    attachment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    associate_id INT NOT NULL,
    doctor_id INT NOT NULL,
    prescription_id INT NULL, -- Link to prescription if applicable
    category_id INT NOT NULL,
    
    -- File Information
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size_bytes BIGINT NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    file_extension VARCHAR(10) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    
    -- Document Information
    document_title VARCHAR(200) NOT NULL,
    document_description TEXT,
    document_date DATE NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Medical Information
    report_type ENUM('Lab Report', 'X-Ray', 'MRI', 'CT Scan', 'Ultrasound', 'ECG', 'Blood Test', 'Urine Test', 'Pathology Report', 'Prescription', 'Discharge Summary', 'Consultation Notes', 'Other') NOT NULL,
    test_date DATE NULL,
    test_location VARCHAR(200),
    test_reference_number VARCHAR(100),
    doctor_notes TEXT,
    associate_notes TEXT,
    
    -- Status and Security
    status ENUM('Uploaded', 'Under Review', 'Approved', 'Rejected', 'Archived', 'Deleted') DEFAULT 'Uploaded',
    is_confidential BOOLEAN DEFAULT FALSE,
    access_level ENUM('Public', 'Restricted', 'Confidential', 'Top Secret') DEFAULT 'Restricted',
    password_protected BOOLEAN DEFAULT FALSE,
    password_hash VARCHAR(255) NULL,
    
    -- Version Control
    version_number INT DEFAULT 1,
    parent_attachment_id INT NULL, -- For file versions
    is_latest_version BOOLEAN DEFAULT TRUE,
    
    -- System Fields
    created_by VARCHAR(100),
    updated_by VARCHAR(100),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (associate_id) REFERENCES associates(associate_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES attachment_categories(category_id) ON DELETE RESTRICT,
    FOREIGN KEY (parent_attachment_id) REFERENCES patient_attachments(attachment_id) ON DELETE SET NULL
);

-- 3. Attachment Permissions Table
CREATE TABLE IF NOT EXISTS attachment_permissions (
    permission_id INT AUTO_INCREMENT PRIMARY KEY,
    attachment_id INT NOT NULL,
    user_type ENUM('Doctor', 'Associate', 'Patient', 'Admin', 'Nurse', 'Other') NOT NULL,
    user_id INT NOT NULL,
    permission_type ENUM('View', 'Download', 'Edit', 'Delete', 'Share') NOT NULL,
    granted_by VARCHAR(100) NOT NULL,
    granted_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expiry_date TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (attachment_id) REFERENCES patient_attachments(attachment_id) ON DELETE CASCADE
);

-- 4. Attachment Comments Table
CREATE TABLE IF NOT EXISTS attachment_comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    attachment_id INT NOT NULL,
    user_id INT NOT NULL,
    user_type ENUM('Doctor', 'Associate', 'Patient', 'Admin') NOT NULL,
    comment_text TEXT NOT NULL,
    comment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_internal BOOLEAN DEFAULT FALSE, -- Internal comments not visible to patients
    is_resolved BOOLEAN DEFAULT FALSE,
    
    FOREIGN KEY (attachment_id) REFERENCES patient_attachments(attachment_id) ON DELETE CASCADE
);

-- 5. Attachment Downloads Table (Audit Trail)
CREATE TABLE IF NOT EXISTS attachment_downloads (
    download_id INT AUTO_INCREMENT PRIMARY KEY,
    attachment_id INT NOT NULL,
    downloaded_by VARCHAR(100) NOT NULL,
    download_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    download_reason VARCHAR(200),
    
    FOREIGN KEY (attachment_id) REFERENCES patient_attachments(attachment_id) ON DELETE CASCADE
);

-- 6. Attachment Sharing Table
CREATE TABLE IF NOT EXISTS attachment_sharing (
    sharing_id INT AUTO_INCREMENT PRIMARY KEY,
    attachment_id INT NOT NULL,
    shared_by VARCHAR(100) NOT NULL,
    shared_with_email VARCHAR(100) NOT NULL,
    shared_with_name VARCHAR(100),
    access_token VARCHAR(255) NOT NULL UNIQUE,
    permission_level ENUM('View', 'Download') NOT NULL,
    expiry_date TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    shared_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_accessed TIMESTAMP NULL,
    access_count INT DEFAULT 0,
    
    FOREIGN KEY (attachment_id) REFERENCES patient_attachments(attachment_id) ON DELETE CASCADE
);

-- 7. Attachment Tags Table
CREATE TABLE IF NOT EXISTS attachment_tags (
    tag_id INT AUTO_INCREMENT PRIMARY KEY,
    tag_name VARCHAR(50) NOT NULL UNIQUE,
    tag_color VARCHAR(7) DEFAULT '#007bff', -- Hex color code
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 8. Attachment Tag Mappings Table
CREATE TABLE IF NOT EXISTS attachment_tag_mappings (
    mapping_id INT AUTO_INCREMENT PRIMARY KEY,
    attachment_id INT NOT NULL,
    tag_id INT NOT NULL,
    created_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (attachment_id) REFERENCES patient_attachments(attachment_id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES attachment_tags(tag_id) ON DELETE CASCADE,
    UNIQUE KEY unique_attachment_tag (attachment_id, tag_id)
);

-- 9. File Storage Locations Table
CREATE TABLE IF NOT EXISTS file_storage_locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    location_name VARCHAR(100) NOT NULL,
    storage_path VARCHAR(500) NOT NULL,
    storage_type ENUM('Local', 'AWS S3', 'Google Drive', 'OneDrive', 'FTP') DEFAULT 'Local',
    is_primary BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    max_capacity_gb BIGINT DEFAULT 1000,
    used_capacity_gb BIGINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 10. Attachment Notifications Table
CREATE TABLE IF NOT EXISTS attachment_notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    attachment_id INT NOT NULL,
    user_id INT NOT NULL,
    user_type ENUM('Doctor', 'Associate', 'Patient', 'Admin') NOT NULL,
    notification_type ENUM('Upload', 'Approval', 'Rejection', 'Comment', 'Share', 'Expiry') NOT NULL,
    notification_message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    notification_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (attachment_id) REFERENCES patient_attachments(attachment_id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_attachment_patient ON patient_attachments(patient_id);
CREATE INDEX idx_attachment_associate ON patient_attachments(associate_id);
CREATE INDEX idx_attachment_doctor ON patient_attachments(doctor_id);
CREATE INDEX idx_attachment_category ON patient_attachments(category_id);
CREATE INDEX idx_attachment_status ON patient_attachments(status);
CREATE INDEX idx_attachment_upload_date ON patient_attachments(upload_date);
CREATE INDEX idx_attachment_document_date ON patient_attachments(document_date);
CREATE INDEX idx_attachment_report_type ON patient_attachments(report_type);
CREATE INDEX idx_permission_attachment ON attachment_permissions(attachment_id);
CREATE INDEX idx_permission_user ON attachment_permissions(user_type, user_id);
CREATE INDEX idx_comment_attachment ON attachment_comments(attachment_id);
CREATE INDEX idx_download_attachment ON attachment_downloads(attachment_id);
CREATE INDEX idx_sharing_attachment ON attachment_sharing(attachment_id);
CREATE INDEX idx_sharing_token ON attachment_sharing(access_token);
CREATE INDEX idx_notification_attachment ON attachment_notifications(attachment_id);
CREATE INDEX idx_notification_user ON attachment_notifications(user_id, user_type);

-- Insert default attachment categories
INSERT INTO attachment_categories (category_name, category_code, description, allowed_file_types, max_file_size_mb, is_required) VALUES
('Lab Reports', 'LAB', 'Laboratory test results and reports', '["pdf", "jpg", "jpeg", "png", "doc", "docx"]', 15, TRUE),
('Imaging Reports', 'IMG', 'X-Ray, MRI, CT Scan, Ultrasound reports', '["pdf", "jpg", "jpeg", "png", "dcm", "dicom"]', 50, TRUE),
('Prescriptions', 'PRES', 'Doctor prescribed medications and treatments', '["pdf", "jpg", "jpeg", "png", "doc", "docx"]', 10, TRUE),
('Discharge Summaries', 'DIS', 'Patient discharge documentation', '["pdf", "doc", "docx"]', 10, TRUE),
('Consultation Notes', 'CONS', 'Doctor consultation and examination notes', '["pdf", "doc", "docx", "txt"]', 5, FALSE),
('ECG Reports', 'ECG', 'Electrocardiogram reports and images', '["pdf", "jpg", "jpeg", "png"]', 10, FALSE),
('Pathology Reports', 'PATH', 'Pathology and biopsy reports', '["pdf", "jpg", "jpeg", "png", "doc", "docx"]', 20, TRUE),
('Other Medical Documents', 'OTHER', 'Other medical documents and reports', '["pdf", "jpg", "jpeg", "png", "doc", "docx", "txt"]', 10, FALSE);

-- Insert default file storage location
INSERT INTO file_storage_locations (location_name, storage_path, storage_type, is_primary, max_capacity_gb) VALUES
('Primary Storage', '/var/www/hms/uploads/attachments/', 'Local', TRUE, 1000);

-- Insert default tags
INSERT INTO attachment_tags (tag_name, tag_color, description) VALUES
('Urgent', '#dc3545', 'Urgent documents requiring immediate attention'),
('Follow-up', '#ffc107', 'Documents requiring follow-up action'),
('Critical', '#dc3545', 'Critical medical information'),
('Routine', '#28a745', 'Routine medical documents'),
('Review', '#17a2b8', 'Documents under review'),
('Approved', '#28a745', 'Approved documents'),
('Confidential', '#6c757d', 'Confidential documents');
