-- Chat System - SQL Queries (Simplified)
-- Database: hms_db

-- Conversations: patient-doctor only, text-only messages

-- 1) Create new patient-doctor conversation
INSERT INTO chat_conversations (conversation_uuid, conversation_type, patient_id, doctor_id, title, description, privacy_level, created_by)
VALUES (UUID(), 'Patient-Doctor', 1, 1, 'Consultation Chat', 'Patient consultation and follow-up', 'Private', 'admin');

-- 2) List conversations for a user (patient or doctor)
SELECT c.conversation_id, c.conversation_uuid, c.conversation_type, c.title, c.last_message_at, c.is_active,
       CONCAT(p.first_name, ' ', p.last_name) as patient_name,
       CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
       (SELECT COUNT(*) FROM chat_messages m WHERE m.conversation_id = c.conversation_id AND m.is_deleted = FALSE) as message_count,
       (SELECT COUNT(*) FROM chat_messages m WHERE m.conversation_id = c.conversation_id AND m.is_read = FALSE AND m.sender_id != 1 AND m.sender_type != 'Patient') as unread_count
FROM chat_conversations c
LEFT JOIN patients p ON c.patient_id = p.patient_id
LEFT JOIN doctors d ON c.doctor_id = d.doctor_id
WHERE c.is_active = TRUE AND c.is_archived = FALSE
  AND (c.patient_id = 1 OR c.doctor_id = 1)
ORDER BY c.last_message_at DESC;

-- 3) Conversation details
SELECT c.*, 
       CONCAT(p.first_name, ' ', p.last_name) as patient_name,
       CONCAT(d.first_name, ' ', d.last_name) as doctor_name
FROM chat_conversations c
LEFT JOIN patients p ON c.patient_id = p.patient_id
LEFT JOIN doctors d ON c.doctor_id = d.doctor_id
WHERE c.conversation_id = 1;

-- 4) Send text message
INSERT INTO chat_messages (conversation_id, sender_id, sender_type, message_text, message_type, created_by)
VALUES (1, 1, 'Patient', 'Hello Doctor, I have a question', 'Text', 'admin');

-- 5) Get messages in a conversation
SELECT m.message_id, m.message_text, m.message_type, m.created_at, m.is_read, m.is_edited, m.is_deleted,
       m.sender_id, m.sender_type,
       CASE 
         WHEN m.sender_type = 'Patient' THEN CONCAT(p.first_name, ' ', p.last_name)
         WHEN m.sender_type = 'Doctor' THEN CONCAT(d.first_name, ' ', d.last_name)
         ELSE 'System'
       END as sender_name
FROM chat_messages m
LEFT JOIN patients p ON m.sender_id = p.patient_id AND m.sender_type = 'Patient'
LEFT JOIN doctors d ON m.sender_id = d.doctor_id AND m.sender_type = 'Doctor'
WHERE m.conversation_id = 1 AND m.is_deleted = FALSE
ORDER BY m.created_at ASC;

-- 6) Mark all messages in conversation as read by the viewer (user_id=1, user_type='Patient')
UPDATE chat_messages 
SET is_read = TRUE, read_at = NOW()
WHERE conversation_id = 1 AND sender_id != 1 AND sender_type != 'Patient' AND is_read = FALSE;

-- 7) Add conversation participants (patient, doctor)
INSERT INTO chat_participants (conversation_id, user_id, user_type, created_by)
VALUES (1, 1, 'Patient', 'admin')
ON DUPLICATE KEY UPDATE is_active = TRUE;

INSERT INTO chat_participants (conversation_id, user_id, user_type, created_by)
VALUES (1, 2, 'Doctor', 'admin')
ON DUPLICATE KEY UPDATE is_active = TRUE;

-- 8) Basic cleanup: archive very old inactive conversations
UPDATE chat_conversations 
SET is_archived = TRUE
WHERE last_activity_at < DATE_SUB(NOW(), INTERVAL 1 YEAR) AND is_archived = FALSE;


