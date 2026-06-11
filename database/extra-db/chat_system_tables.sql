-- Chat System Database Tables
-- Database: hms_db
-- Real-time messaging between patients and doctors

CREATE TABLE IF NOT EXISTS chat_conversations (
    conversation_id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_uuid VARCHAR(36) NOT NULL UNIQUE,
    conversation_type ENUM('Patient-Doctor') NOT NULL,
    title VARCHAR(200),
    description TEXT,
    
    -- Participants
    patient_id INT NULL,
    doctor_id INT NULL,
    
    -- Conversation Settings
    is_active BOOLEAN DEFAULT TRUE,
    is_archived BOOLEAN DEFAULT FALSE,
    is_encrypted BOOLEAN DEFAULT TRUE,
    encryption_key VARCHAR(255) NULL,
    
    -- Privacy and Security
    privacy_level ENUM('Private') DEFAULT 'Private',
    allow_file_sharing BOOLEAN DEFAULT FALSE,
    
    -- Status and Timing
    last_message_at TIMESTAMP NULL,
    last_activity_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- System Fields
    created_by VARCHAR(100),
    updated_by VARCHAR(100),
    
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS chat_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    sender_type ENUM('Patient', 'Doctor') NOT NULL,
    
    -- Message Content
    message_text TEXT NOT NULL,
    message_type ENUM('Text') DEFAULT 'Text',
    
    -- Message Metadata
    is_edited BOOLEAN DEFAULT FALSE,
    edited_at TIMESTAMP NULL,
    original_message TEXT NULL,
    edit_reason VARCHAR(200) NULL,
    
    -- Message Status
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    is_delivered BOOLEAN DEFAULT FALSE,
    delivered_at TIMESTAMP NULL,
    is_deleted BOOLEAN DEFAULT FALSE,
    deleted_at TIMESTAMP NULL,
    delete_reason VARCHAR(200) NULL,
    
    -- Reply and Threading
    reply_to_message_id INT NULL,
    thread_id VARCHAR(100) NULL,
    
    -- Encryption and Security
    is_encrypted BOOLEAN DEFAULT TRUE,
    encryption_key VARCHAR(255) NULL,
    
    -- System Fields
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(100),
    
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(conversation_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS chat_participants (
    participant_id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    user_type ENUM('Patient', 'Doctor') NOT NULL,
    
    -- Participant Settings
    is_active BOOLEAN DEFAULT TRUE,
    is_muted BOOLEAN DEFAULT FALSE,
    is_archived BOOLEAN DEFAULT FALSE,
    last_read_at TIMESTAMP NULL,
    last_activity_at TIMESTAMP NULL,
    
    -- Permissions
    can_send_messages BOOLEAN DEFAULT TRUE,
    can_send_files BOOLEAN DEFAULT FALSE,
    can_delete_messages BOOLEAN DEFAULT FALSE,
    can_add_participants BOOLEAN DEFAULT FALSE,
    can_edit_conversation BOOLEAN DEFAULT FALSE,
    
    -- Notification Settings
    notifications_enabled BOOLEAN DEFAULT TRUE,
    notification_sound BOOLEAN DEFAULT TRUE,
    notification_email BOOLEAN DEFAULT FALSE,
    notification_sms BOOLEAN DEFAULT FALSE,
    
    -- System Fields
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    left_at TIMESTAMP NULL,
    created_by VARCHAR(100),
    
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(conversation_id) ON DELETE CASCADE,
    UNIQUE KEY unique_participant (conversation_id, user_id, user_type)
);

-- Removed: chat_files (no file sharing)

-- Removed: chat_notifications (simplified, no external notifications)

-- Removed: chat_settings (not needed)

-- Removed: chat_blocked_users (not needed)

-- Removed: chat_message_reactions (not needed)

-- Removed: chat_typing_indicators (not needed)

-- Removed: chat_message_status (not needed)

-- Create indexes for better performance
CREATE INDEX idx_conversation_patient ON chat_conversations(patient_id);
CREATE INDEX idx_conversation_doctor ON chat_conversations(doctor_id);
CREATE INDEX idx_conversation_type ON chat_conversations(conversation_type);
CREATE INDEX idx_conversation_active ON chat_conversations(is_active);
CREATE INDEX idx_conversation_last_message ON chat_conversations(last_message_at);

CREATE INDEX idx_message_conversation ON chat_messages(conversation_id);
CREATE INDEX idx_message_sender ON chat_messages(sender_id, sender_type);
CREATE INDEX idx_message_created ON chat_messages(created_at);
CREATE INDEX idx_message_read ON chat_messages(is_read);
CREATE INDEX idx_message_deleted ON chat_messages(is_deleted);

CREATE INDEX idx_participant_conversation ON chat_participants(conversation_id);
CREATE INDEX idx_participant_user ON chat_participants(user_id, user_type);
CREATE INDEX idx_participant_active ON chat_participants(is_active);

-- Removed default settings inserts
