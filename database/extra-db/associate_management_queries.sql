-- Associate Management System - SQL Queries
-- Database: hms_db

-- =============================================
-- ASSOCIATES (CORE)
-- =============================================

-- 1. Add a new associate
INSERT INTO associates (first_name, last_name, email, phone, associate_type, status)
VALUES ('John', 'Smith', 'john.smith@hospital.com', '+1234567893', 'Patient Coordinator', 'Active');

-- 2. Get all active associates
SELECT associate_id, CONCAT(first_name, ' ', last_name) as full_name, email, phone, associate_type, status
FROM associates 
WHERE status = 'Active' 
ORDER BY last_name, first_name;

-- 3. Get associates by type
SELECT associate_id, CONCAT(first_name, ' ', last_name) as full_name, phone
FROM associates 
WHERE associate_type = 'Patient Coordinator' AND status = 'Active'
ORDER BY last_name;

-- 4. Get associates by department
-- Removed department-based query

-- 5. Update associate information
UPDATE associates 
SET phone = '+1234567899', email = 'updated.email@hospital.com'
WHERE associate_id = 1;

-- Removed performance summary

-- Removed: assignments (not in simplified scope)

-- Removed communications section

-- Removed availability section

-- Removed tasks section

-- Removed performance section

-- Removed training section

-- See AVAILABILITY (CORE, OPTIONAL) section above

-- Removed availability section

-- Removed: reports dependent on removed modules


-- 41. Update associate status
UPDATE associates 
SET status = 'Inactive'
WHERE associate_id = 1;

-- Removed: assignment archival (assignments removed)

-- Clean up old communications
-- Removed communications cleanup

-- Removed: performance sync (performance removed)

-- Removed: training reminders (training removed)
