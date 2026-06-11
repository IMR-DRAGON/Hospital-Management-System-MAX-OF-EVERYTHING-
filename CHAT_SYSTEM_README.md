# Chat System (Simplified)

A minimal text-only chat between a patient and the doctor they have an appointment with. No file sharing, no groups, no associates.

## Overview
- Chat allowed only for patient ↔ doctor pairs with an appointment
- Text messages only
- Basic read status and unread counts

## Database Schema
- `chat_conversations`: patient_id, doctor_id, title, timestamps
- `chat_messages`: conversation_id, sender_id, sender_type ('Patient'|'Doctor'), message_text, timestamps, read flags
- `chat_participants`: conversation participants (patient and doctor only)

## Setup
1) Database
```sql
mysql -u root -p hms_db < chat_system_tables.sql
```
2) Files
- `chat.php` (UI)
- `chat-actions.php` (AJAX)

## Key Queries
```sql
-- Create conversation
INSERT INTO chat_conversations (conversation_uuid, conversation_type, patient_id, doctor_id, title, privacy_level, created_by)
VALUES (UUID(), 'Patient-Doctor', 1, 2, 'Consultation Chat', 'Private', 'admin');

-- Send text message
INSERT INTO chat_messages (conversation_id, sender_id, sender_type, message_text, message_type, created_by)
VALUES (1, 1, 'Patient', 'Hello Doctor', 'Text', 'admin');

-- Fetch messages
SELECT m.message_id, m.message_text, m.created_at, m.sender_type
FROM chat_messages m
WHERE m.conversation_id = 1 AND m.is_deleted = FALSE
ORDER BY m.created_at ASC;
```

## Notes
- No file uploads, notifications, reactions, or typing indicators
- Conversations may be archived after long inactivity
