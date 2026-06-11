-- Attachment Management System - SQL Queries
-- Database: hms_db

-- =============================================
-- ATTACHMENT UPLOAD QUERIES
-- =============================================

-- 1. Upload new attachment
INSERT INTO patient_attachments (patient_id, associate_id, doctor_id, prescription_id, category_id, original_filename, stored_filename, file_path, file_size_bytes, file_type, file_extension, mime_type, document_title, document_description, document_date, report_type, test_date, test_location, test_reference_number, doctor_notes, associate_notes, access_level, created_by)
VALUES (1, 1, 1, NULL, 1, 'lab_report_001.pdf', 'att_20240315_001.pdf', '/uploads/attachments/2024/03/15/att_20240315_001.pdf', 2048576, 'application/pdf', 'pdf', 'application/pdf', 'Blood Test Report', 'Complete blood count and lipid profile', '2024-03-15', 'Lab Report', '2024-03-15', 'Central Lab', 'LAB-2024-001', 'Patient shows normal values', 'Uploaded as per doctor request', 'Restricted', 'admin');

-- 2. Get attachment by ID
SELECT pa.*, p.first_name as patient_name, p.last_name as patient_last_name,
       a.first_name as associate_name, a.last_name as associate_last_name,
       d.first_name as doctor_name, d.last_name as doctor_last_name,
       ac.category_name, ac.category_code
FROM patient_attachments pa
JOIN patients p ON pa.patient_id = p.patient_id
JOIN associates a ON pa.associate_id = a.associate_id
JOIN doctors d ON pa.doctor_id = d.doctor_id
JOIN attachment_categories ac ON pa.category_id = ac.category_id
WHERE pa.attachment_id = 1 AND pa.status != 'Deleted';

-- 3. Get attachments for a patient
SELECT pa.attachment_id, pa.document_title, pa.document_date, pa.report_type, pa.status, pa.file_size_bytes,
       ac.category_name, a.first_name as associate_name, d.first_name as doctor_name
FROM patient_attachments pa
JOIN attachment_categories ac ON pa.category_id = ac.category_id
JOIN associates a ON pa.associate_id = a.associate_id
JOIN doctors d ON pa.doctor_id = d.doctor_id
WHERE pa.patient_id = 1 AND pa.status != 'Deleted'
ORDER BY pa.document_date DESC, pa.upload_date DESC;

-- 4. Get attachments by associate
SELECT pa.attachment_id, pa.document_title, pa.document_date, pa.report_type, pa.status,
       CONCAT(p.first_name, ' ', p.last_name) as patient_name,
       CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
       ac.category_name
FROM patient_attachments pa
JOIN patients p ON pa.patient_id = p.patient_id
JOIN doctors d ON pa.doctor_id = d.doctor_id
JOIN attachment_categories ac ON pa.category_id = ac.category_id
WHERE pa.associate_id = 1 AND pa.status != 'Deleted'
ORDER BY pa.upload_date DESC;

-- 5. Get attachments by doctor
SELECT pa.attachment_id, pa.document_title, pa.document_date, pa.report_type, pa.status,
       CONCAT(p.first_name, ' ', p.last_name) as patient_name,
       CONCAT(a.first_name, ' ', a.last_name) as associate_name,
       ac.category_name
FROM patient_attachments pa
JOIN patients p ON pa.patient_id = p.patient_id
JOIN associates a ON pa.associate_id = a.associate_id
JOIN attachment_categories ac ON pa.category_id = ac.category_id
WHERE pa.doctor_id = 1 AND pa.status != 'Deleted'
ORDER BY pa.document_date DESC;

-- =============================================
-- ATTACHMENT CATEGORY QUERIES
-- =============================================

-- 6. Get all active categories
SELECT category_id, category_name, category_code, description, allowed_file_types, max_file_size_mb, is_required
FROM attachment_categories 
WHERE is_active = TRUE
ORDER BY category_name;

-- 7. Get category by code
SELECT * FROM attachment_categories 
WHERE category_code = 'LAB' AND is_active = TRUE;

-- 8. Add new category
INSERT INTO attachment_categories (category_name, category_code, description, allowed_file_types, max_file_size_mb, is_required)
VALUES ('Radiology Reports', 'RAD', 'Radiology and imaging reports', '["pdf", "jpg", "jpeg", "png", "dcm"]', 100, TRUE);

-- =============================================
-- PERMISSION MANAGEMENT QUERIES
-- =============================================

-- 9. Grant permission to user
INSERT INTO attachment_permissions (attachment_id, user_type, user_id, permission_type, granted_by)
VALUES (1, 'Doctor', 1, 'View', 'admin');

-- 10. Get user permissions for attachment
SELECT p.permission_type, p.granted_date, p.expiry_date, p.is_active
FROM attachment_permissions p
WHERE p.attachment_id = 1 AND p.user_type = 'Doctor' AND p.user_id = 1 AND p.is_active = TRUE;

-- 11. Check if user can access attachment
SELECT pa.attachment_id, pa.access_level, p.permission_type
FROM patient_attachments pa
LEFT JOIN attachment_permissions p ON pa.attachment_id = p.attachment_id 
    AND p.user_type = 'Doctor' AND p.user_id = 1 AND p.is_active = TRUE
WHERE pa.attachment_id = 1 
  AND (pa.access_level = 'Public' OR p.permission_type IS NOT NULL);

-- 12. Revoke permission
UPDATE attachment_permissions 
SET is_active = FALSE, updated_at = NOW()
WHERE attachment_id = 1 AND user_type = 'Doctor' AND user_id = 1;

-- =============================================
-- COMMENT MANAGEMENT QUERIES
-- =============================================

-- 13. Add comment to attachment
INSERT INTO attachment_comments (attachment_id, user_id, user_type, comment_text, is_internal)
VALUES (1, 1, 'Doctor', 'Please review the lab values carefully', FALSE);

-- 14. Get comments for attachment
SELECT c.comment_id, c.comment_text, c.comment_date, c.is_internal, c.is_resolved,
       CONCAT(u.first_name, ' ', u.last_name) as user_name, c.user_type
FROM attachment_comments c
LEFT JOIN doctors u ON c.user_id = u.doctor_id AND c.user_type = 'Doctor'
LEFT JOIN associates u2 ON c.user_id = u2.associate_id AND c.user_type = 'Associate'
WHERE c.attachment_id = 1
ORDER BY c.comment_date DESC;

-- 15. Mark comment as resolved
UPDATE attachment_comments 
SET is_resolved = TRUE
WHERE comment_id = 1;

-- =============================================
-- DOWNLOAD TRACKING QUERIES
-- =============================================

-- 16. Record download
INSERT INTO attachment_downloads (attachment_id, downloaded_by, ip_address, user_agent, download_reason)
VALUES (1, 'doctor@hospital.com', '192.168.1.100', 'Mozilla/5.0...', 'Patient consultation');

-- 17. Get download history for attachment
SELECT d.download_id, d.downloaded_by, d.download_date, d.ip_address, d.download_reason
FROM attachment_downloads d
WHERE d.attachment_id = 1
ORDER BY d.download_date DESC;

-- 18. Get download statistics
SELECT 
    COUNT(*) as total_downloads,
    COUNT(DISTINCT downloaded_by) as unique_downloaders,
    COUNT(CASE WHEN download_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as downloads_last_week
FROM attachment_downloads
WHERE attachment_id = 1;

-- =============================================
-- SHARING MANAGEMENT QUERIES
-- =============================================

-- 19. Share attachment externally
INSERT INTO attachment_sharing (attachment_id, shared_by, shared_with_email, shared_with_name, access_token, permission_level, expiry_date)
VALUES (1, 'admin', 'patient@email.com', 'John Patient', 'sh_abc123def456', 'View', DATE_ADD(NOW(), INTERVAL 7 DAY));

-- 20. Get shared attachments
SELECT s.sharing_id, s.shared_with_email, s.shared_with_name, s.permission_level, s.shared_date, s.expiry_date, s.is_active,
       pa.document_title, pa.document_date
FROM attachment_sharing s
JOIN patient_attachments pa ON s.attachment_id = pa.attachment_id
WHERE s.shared_by = 'admin' AND s.is_active = TRUE
ORDER BY s.shared_date DESC;

-- 21. Access shared attachment by token
SELECT pa.*, s.permission_level, s.expiry_date, s.is_active
FROM attachment_sharing s
JOIN patient_attachments pa ON s.attachment_id = pa.attachment_id
WHERE s.access_token = 'sh_abc123def456' 
  AND s.is_active = TRUE 
  AND s.expiry_date > NOW();

-- 22. Update sharing access
UPDATE attachment_sharing 
SET last_accessed = NOW(), access_count = access_count + 1
WHERE access_token = 'sh_abc123def456';

-- =============================================
-- TAG MANAGEMENT QUERIES
-- =============================================

-- 23. Add tag to attachment
INSERT INTO attachment_tag_mappings (attachment_id, tag_id, created_by)
VALUES (1, 1, 'admin');

-- 24. Get tags for attachment
SELECT t.tag_id, t.tag_name, t.tag_color, t.description
FROM attachment_tags t
JOIN attachment_tag_mappings m ON t.tag_id = m.tag_id
WHERE m.attachment_id = 1 AND t.is_active = TRUE;

-- 25. Get attachments by tag
SELECT pa.attachment_id, pa.document_title, pa.document_date, pa.report_type,
       CONCAT(p.first_name, ' ', p.last_name) as patient_name,
       t.tag_name, t.tag_color
FROM patient_attachments pa
JOIN patients p ON pa.patient_id = p.patient_id
JOIN attachment_tag_mappings m ON pa.attachment_id = m.attachment_id
JOIN attachment_tags t ON m.tag_id = t.tag_id
WHERE t.tag_id = 1 AND pa.status != 'Deleted'
ORDER BY pa.document_date DESC;

-- 26. Create new tag
INSERT INTO attachment_tags (tag_name, tag_color, description)
VALUES ('Emergency', '#dc3545', 'Emergency medical documents');

-- =============================================
-- NOTIFICATION QUERIES
-- =============================================

-- 27. Create notification
INSERT INTO attachment_notifications (attachment_id, user_id, user_type, notification_type, notification_message)
VALUES (1, 1, 'Doctor', 'Upload', 'New lab report uploaded for patient John Doe');

-- 28. Get notifications for user
SELECT n.notification_id, n.notification_type, n.notification_message, n.notification_date, n.is_read,
       pa.document_title, CONCAT(p.first_name, ' ', p.last_name) as patient_name
FROM attachment_notifications n
JOIN patient_attachments pa ON n.attachment_id = pa.attachment_id
JOIN patients p ON pa.patient_id = p.patient_id
WHERE n.user_id = 1 AND n.user_type = 'Doctor'
ORDER BY n.notification_date DESC;

-- 29. Mark notification as read
UPDATE attachment_notifications 
SET is_read = TRUE
WHERE notification_id = 1;

-- 30. Get unread notification count
SELECT COUNT(*) as unread_count
FROM attachment_notifications
WHERE user_id = 1 AND user_type = 'Doctor' AND is_read = FALSE;

-- =============================================
-- SEARCH AND FILTER QUERIES
-- =============================================

-- 31. Search attachments by title or description
SELECT pa.attachment_id, pa.document_title, pa.document_date, pa.report_type, pa.status,
       CONCAT(p.first_name, ' ', p.last_name) as patient_name,
       ac.category_name
FROM patient_attachments pa
JOIN patients p ON pa.patient_id = p.patient_id
JOIN attachment_categories ac ON pa.category_id = ac.category_id
WHERE (pa.document_title LIKE '%blood%' OR pa.document_description LIKE '%blood%')
  AND pa.status != 'Deleted'
ORDER BY pa.document_date DESC;

-- 32. Filter attachments by date range
SELECT pa.attachment_id, pa.document_title, pa.document_date, pa.report_type,
       CONCAT(p.first_name, ' ', p.last_name) as patient_name
FROM patient_attachments pa
JOIN patients p ON pa.patient_id = p.patient_id
WHERE pa.document_date BETWEEN '2024-01-01' AND '2024-12-31'
  AND pa.status != 'Deleted'
ORDER BY pa.document_date DESC;

-- 33. Filter attachments by report type
SELECT pa.attachment_id, pa.document_title, pa.document_date, pa.status,
       CONCAT(p.first_name, ' ', p.last_name) as patient_name
FROM patient_attachments pa
JOIN patients p ON pa.patient_id = p.patient_id
WHERE pa.report_type = 'Lab Report' AND pa.status != 'Deleted'
ORDER BY pa.document_date DESC;

-- 34. Filter attachments by category
SELECT pa.attachment_id, pa.document_title, pa.document_date, pa.report_type,
       CONCAT(p.first_name, ' ', p.last_name) as patient_name
FROM patient_attachments pa
JOIN patients p ON pa.patient_id = p.patient_id
JOIN attachment_categories ac ON pa.category_id = ac.category_id
WHERE ac.category_code = 'LAB' AND pa.status != 'Deleted'
ORDER BY pa.document_date DESC;

-- =============================================
-- REPORTING QUERIES
-- =============================================

-- 35. Attachment statistics by category
SELECT ac.category_name, ac.category_code,
       COUNT(pa.attachment_id) as total_attachments,
       SUM(pa.file_size_bytes) as total_size_bytes,
       AVG(pa.file_size_bytes) as avg_size_bytes,
       COUNT(CASE WHEN pa.status = 'Approved' THEN 1 END) as approved_count
FROM attachment_categories ac
LEFT JOIN patient_attachments pa ON ac.category_id = pa.category_id AND pa.status != 'Deleted'
GROUP BY ac.category_id, ac.category_name, ac.category_code
ORDER BY total_attachments DESC;

-- 36. Associate upload statistics
SELECT a.associate_id, CONCAT(a.first_name, ' ', a.last_name) as associate_name,
       COUNT(pa.attachment_id) as total_uploads,
       SUM(pa.file_size_bytes) as total_size_bytes,
       COUNT(CASE WHEN pa.upload_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as uploads_last_30_days
FROM associates a
LEFT JOIN patient_attachments pa ON a.associate_id = pa.associate_id AND pa.status != 'Deleted'
WHERE a.status = 'Active'
GROUP BY a.associate_id, a.first_name, a.last_name
ORDER BY total_uploads DESC;

-- 37. Doctor attachment statistics
SELECT d.doctor_id, CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
       COUNT(pa.attachment_id) as total_attachments,
       COUNT(DISTINCT pa.patient_id) as unique_patients,
       COUNT(CASE WHEN pa.report_type = 'Lab Report' THEN 1 END) as lab_reports,
       COUNT(CASE WHEN pa.report_type = 'X-Ray' THEN 1 END) as xray_reports
FROM doctors d
LEFT JOIN patient_attachments pa ON d.doctor_id = pa.doctor_id AND pa.status != 'Deleted'
GROUP BY d.doctor_id, d.first_name, d.last_name
ORDER BY total_attachments DESC;

-- 38. Patient attachment summary
SELECT p.patient_id, CONCAT(p.first_name, ' ', p.last_name) as patient_name,
       COUNT(pa.attachment_id) as total_attachments,
       COUNT(CASE WHEN pa.status = 'Approved' THEN 1 END) as approved_attachments,
       COUNT(CASE WHEN pa.status = 'Under Review' THEN 1 END) as pending_attachments,
       MAX(pa.document_date) as latest_document_date
FROM patients p
LEFT JOIN patient_attachments pa ON p.patient_id = pa.patient_id AND pa.status != 'Deleted'
GROUP BY p.patient_id, p.first_name, p.last_name
ORDER BY total_attachments DESC;

-- 39. Storage usage report
SELECT 
    SUM(file_size_bytes) as total_storage_bytes,
    COUNT(*) as total_files,
    AVG(file_size_bytes) as avg_file_size,
    COUNT(CASE WHEN upload_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as files_last_30_days
FROM patient_attachments
WHERE status != 'Deleted';

-- 40. Recent activity report
SELECT pa.attachment_id, pa.document_title, pa.upload_date, pa.status,
       CONCAT(p.first_name, ' ', p.last_name) as patient_name,
       CONCAT(a.first_name, ' ', a.last_name) as associate_name,
       CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
       ac.category_name
FROM patient_attachments pa
JOIN patients p ON pa.patient_id = p.patient_id
JOIN associates a ON pa.associate_id = a.associate_id
JOIN doctors d ON pa.doctor_id = d.doctor_id
JOIN attachment_categories ac ON pa.category_id = ac.category_id
WHERE pa.upload_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY pa.upload_date DESC;

-- =============================================
-- MAINTENANCE QUERIES
-- =============================================

-- 41. Soft delete attachment
UPDATE patient_attachments 
SET status = 'Deleted', deleted_at = NOW(), updated_by = 'admin'
WHERE attachment_id = 1;

-- 42. Permanently delete old deleted attachments (older than 1 year)
DELETE FROM patient_attachments 
WHERE status = 'Deleted' AND deleted_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- 43. Update attachment status
UPDATE patient_attachments 
SET status = 'Approved', updated_by = 'admin'
WHERE attachment_id = 1;

-- 44. Archive old attachments (older than 2 years)
UPDATE patient_attachments 
SET status = 'Archived', updated_by = 'admin'
WHERE document_date < DATE_SUB(NOW(), INTERVAL 2 YEAR) AND status = 'Approved';

-- 45. Clean up expired sharing links
UPDATE attachment_sharing 
SET is_active = FALSE
WHERE expiry_date < NOW() AND is_active = TRUE;
